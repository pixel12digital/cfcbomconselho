<?php
/**
 * Job diário: Marcar faturas vencidas e atualizar status financeiro
 * Sistema CFC - Bom Conselho
 * 
 * CORREÇÃO FASE 1 (2025-01-27):
 * - Tabela oficial alterada de 'faturas' para 'financeiro_faturas'
 * - Campo de vencimento alterado para 'data_vencimento' (campo oficial)
 * - Referências atualizadas conforme uso em admin/api/financeiro-faturas.php
 *   e admin/pages/financeiro-faturas.php
 * 
 * Baseado em: admin/pages/_RAIO-X-COMPLETO-SISTEMA.md
 * Documentação: admin/pages/_FASE-1-LIMPEZA-E-BASE.md
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "🚀 Iniciando job diário de atualização financeira...\n";
    echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";
    
    // 1. Marcar faturas vencidas
    // TABELA OFICIAL: financeiro_faturas (não 'faturas')
    // CAMPO OFICIAL: data_vencimento (não 'vencimento')
    echo "📝 Marcando faturas vencidas...\n";
    $resultadoVencidas = $db->query("
        UPDATE financeiro_faturas 
        SET status = 'vencida' 
        WHERE status = 'aberta' AND data_vencimento < CURDATE()
    ");
    
    $faturasVencidas = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM financeiro_faturas 
        WHERE status = 'vencida' AND data_vencimento < CURDATE()
    ");
    
    echo "✅ Faturas marcadas como vencidas: $faturasVencidas\n";
    
    // 2. Atualizar status financeiro das matrículas
    echo "📝 Atualizando status financeiro das matrículas...\n";
    
    // Marcar como inadimplente matrículas com faturas vencidas
    // NOTA: Matricula_id pode ser NULL em financeiro_faturas, então verificamos por aluno_id também
    $resultadoInadimplente = $db->query("
        UPDATE matriculas m
        JOIN (
            SELECT DISTINCT matricula_id, aluno_id
            FROM financeiro_faturas
            WHERE status = 'vencida' AND matricula_id IS NOT NULL
        ) f ON f.matricula_id = m.id
        SET m.status_financeiro = 'inadimplente'
        WHERE m.status_financeiro != 'inadimplente'
    ");
    
    $matriculasInadimplentes = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM matriculas 
        WHERE status_financeiro = 'inadimplente'
    ");
    
    echo "✅ Matrículas marcadas como inadimplentes: $matriculasInadimplentes\n";
    
    // Marcar como regular matrículas sem faturas vencidas
    $resultadoRegular = $db->query("
        UPDATE matriculas
        SET status_financeiro = 'regular'
        WHERE id NOT IN (
            SELECT DISTINCT matricula_id 
            FROM financeiro_faturas 
            WHERE status = 'vencida' AND matricula_id IS NOT NULL
        )
        AND status_financeiro != 'regular'
    ");
    
    $matriculasRegulares = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM matriculas 
        WHERE status_financeiro = 'regular'
    ");
    
    echo "✅ Matrículas marcadas como regulares: $matriculasRegulares\n";
    
    // 3. Estatísticas finais
    echo "\n📊 Estatísticas finais:\n";
    
    // Usando a tabela oficial financeiro_faturas
    $stats = [
        'total_faturas' => $db->count('financeiro_faturas'),
        'faturas_abertas' => $db->count('financeiro_faturas', 'status = ?', ['aberta']),
        'faturas_pagas' => $db->count('financeiro_faturas', 'status = ?', ['paga']),
        'faturas_vencidas' => $db->count('financeiro_faturas', 'status = ?', ['vencida']),
        'faturas_parciais' => $db->count('financeiro_faturas', 'status = ?', ['parcial']),
        'matriculas_regulares' => $db->count('matriculas', 'status_financeiro = ?', ['regular']),
        'matriculas_inadimplentes' => $db->count('matriculas', 'status_financeiro = ?', ['inadimplente'])
    ];
    
    echo "- Total de faturas: {$stats['total_faturas']}\n";
    echo "- Faturas abertas: {$stats['faturas_abertas']}\n";
    echo "- Faturas pagas: {$stats['faturas_pagas']}\n";
    echo "- Faturas vencidas: {$stats['faturas_vencidas']}\n";
    echo "- Faturas parciais: {$stats['faturas_parciais']}\n";
    echo "- Matrículas regulares: {$stats['matriculas_regulares']}\n";
    echo "- Matrículas inadimplentes: {$stats['matriculas_inadimplentes']}\n";
    
    // 4. Log da execução
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'faturas_vencidas_marcadas' => $faturasVencidas,
        'matriculas_inadimplentes' => $matriculasInadimplentes,
        'matriculas_regulares' => $matriculasRegulares,
        'stats' => $stats,
        'tabela_usada' => 'financeiro_faturas',
        'campo_vencimento' => 'data_vencimento'
    ];
    
    // Salvar log em arquivo
    $logFile = '../../logs/job_financeiro_' . date('Y-m') . '.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
    echo "\n🎉 Job executado com sucesso!\n";
    echo "Log salvo em: $logFile\n";
    echo "📋 Tabela usada: financeiro_faturas (oficial)\n";
    echo "📋 Campo vencimento: data_vencimento (oficial)\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao executar job: " . $e->getMessage() . "\n";
    
    // Log de erro
    $errorLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'tabela_tentada' => 'financeiro_faturas'
    ];
    
    $logFile = '../../logs/job_financeiro_errors_' . date('Y-m') . '.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    file_put_contents($logFile, json_encode($errorLog) . "\n", FILE_APPEND | LOCK_EX);
    
    exit(1);
}
