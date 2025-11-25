<?php
/**
 * Script para aplicar migration: turma_presencas_log
 * FASE 1 - LOG PRESENCA TEORICA
 * 
 * Uso: Acessar via navegador ou executar via CLI
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar se é CLI ou web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Verificar autenticação se for web
    if (!isLoggedIn()) {
        die('Acesso negado. Faça login primeiro.');
    }
    
    $currentUser = getCurrentUser();
    if (!in_array($currentUser['tipo'] ?? '', ['admin', 'secretaria'])) {
        die('Acesso negado. Apenas admin/secretaria podem aplicar migrations.');
    }
}

echo "=== APLICANDO MIGRATION: turma_presencas_log ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

$db = Database::getInstance();

// Ler arquivo SQL
$sqlFile = __DIR__ . '/migrations/20251124_create_turma_presencas_log.sql';

if (!file_exists($sqlFile)) {
    die("ERRO: Arquivo de migration não encontrado: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

if (empty($sql)) {
    die("ERRO: Arquivo de migration está vazio.\n");
}

try {
    // Verificar se tabela já existe
    $tabelaExiste = $db->fetch("
        SELECT COUNT(*) as total 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'turma_presencas_log'
    ");
    
    if ($tabelaExiste && $tabelaExiste['total'] > 0) {
        echo "⚠️  AVISO: Tabela 'turma_presencas_log' já existe.\n";
        echo "Deseja continuar mesmo assim? (CREATE TABLE IF NOT EXISTS será usado)\n";
        
        if (!$isCli) {
            echo "<br><a href='?force=1'>Continuar mesmo assim</a> | <a href='javascript:history.back()'>Voltar</a>";
            if (!isset($_GET['force'])) {
                exit;
            }
        }
    }
    
    // Remover comentários SQL (-- e /* */)
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove comentários de linha
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove comentários de bloco
    
    // Dividir SQL em comandos individuais (separados por ;)
    $comandos = array_filter(
        array_map('trim', explode(';', $sql)),
        function($cmd) {
            $cmd = trim($cmd);
            return !empty($cmd) && strlen($cmd) > 10; // Ignorar comandos muito curtos
        }
    );
    
    $sucessos = 0;
    $erros = [];
    
    foreach ($comandos as $index => $comando) {
        $comando = trim($comando);
        if (empty($comando)) {
            continue;
        }
        
        try {
            // Adicionar ; no final se não tiver
            if (substr($comando, -1) !== ';') {
                $comando .= ';';
            }
            
            $db->query($comando);
            $sucessos++;
            echo "✅ Comando " . ($index + 1) . " executado com sucesso\n";
        } catch (Exception $e) {
            $erros[] = [
                'comando' => $index + 1,
                'erro' => $e->getMessage(),
                'sql' => substr($comando, 0, 150) . '...'
            ];
            echo "❌ Erro no comando " . ($index + 1) . ": " . $e->getMessage() . "\n";
            echo "   SQL: " . substr($comando, 0, 100) . "...\n";
        }
    }
    
    echo "\n=== RESULTADO ===\n";
    echo "Comandos executados com sucesso: $sucessos\n";
    echo "Erros: " . count($erros) . "\n";
    
    if (!empty($erros)) {
        echo "\n=== DETALHES DOS ERROS ===\n";
        foreach ($erros as $erro) {
            echo "Comando {$erro['comando']}: {$erro['erro']}\n";
            echo "SQL: {$erro['sql']}\n\n";
        }
    }
    
    // Verificar se tabela foi criada
    $tabelaCriada = $db->fetch("
        SELECT COUNT(*) as total 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'turma_presencas_log'
    ");
    
    if ($tabelaCriada && $tabelaCriada['total'] > 0) {
        // Verificar estrutura
        $colunas = $db->fetchAll("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'turma_presencas_log'
            ORDER BY ORDINAL_POSITION
        ");
        
        echo "\n=== ESTRUTURA DA TABELA ===\n";
        echo "Tabela: turma_presencas_log\n";
        echo "Colunas encontradas: " . count($colunas) . "\n\n";
        
        foreach ($colunas as $coluna) {
            echo "- {$coluna['COLUMN_NAME']} ({$coluna['DATA_TYPE']})";
            if ($coluna['IS_NULLABLE'] === 'YES') {
                echo " NULL";
            }
            if ($coluna['COLUMN_DEFAULT'] !== null) {
                echo " DEFAULT {$coluna['COLUMN_DEFAULT']}";
            }
            echo "\n";
        }
        
        // Verificar índices
        $indices = $db->fetchAll("
            SELECT INDEX_NAME, COLUMN_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'turma_presencas_log'
            AND INDEX_NAME != 'PRIMARY'
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ");
        
        if (!empty($indices)) {
            echo "\n=== ÍNDICES ===\n";
            $indicesAgrupados = [];
            foreach ($indices as $idx) {
                if (!isset($indicesAgrupados[$idx['INDEX_NAME']])) {
                    $indicesAgrupados[$idx['INDEX_NAME']] = [];
                }
                $indicesAgrupados[$idx['INDEX_NAME']][] = $idx['COLUMN_NAME'];
            }
            
            foreach ($indicesAgrupados as $nome => $colunas) {
                echo "- $nome: " . implode(', ', $colunas) . "\n";
            }
        }
        
        echo "\n✅ Migration aplicada com sucesso!\n";
    } else {
        echo "\n⚠️  AVISO: Tabela não foi criada. Verifique os erros acima.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== FIM ===\n";

