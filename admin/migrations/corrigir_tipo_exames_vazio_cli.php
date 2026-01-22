<?php
/**
 * Script CLI para corrigir exames com tipo vazio
 * Uso: php corrigir_tipo_exames_vazio_cli.php [tipo]
 * Exemplo: php corrigir_tipo_exames_vazio_cli.php teorico
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

// Tipo padrão se não fornecido
$tipoDefinir = $argv[1] ?? 'teorico';

// Validar tipo
$tiposValidos = ['medico', 'psicotecnico', 'teorico', 'pratico'];
if (!in_array($tipoDefinir, $tiposValidos, true)) {
    echo "❌ Erro: Tipo inválido. Tipos permitidos: " . implode(', ', $tiposValidos) . "\n";
    exit(1);
}

try {
    $db = Database::getInstance();
    
    echo "🔧 Corrigindo exames com tipo vazio para '{$tipoDefinir}'...\n\n";
    
    // Verificar quantos exames têm tipo vazio
    $totalVazios = $db->fetch("
        SELECT COUNT(*) as total 
        FROM exames 
        WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL
    ");
    
    $count = $totalVazios['total'] ?? 0;
    
    if ($count == 0) {
        echo "✅ Nenhum exame com tipo vazio encontrado.\n";
        exit(0);
    }
    
    echo "📊 Encontrados {$count} exames com tipo vazio.\n\n";
    
    // Obter IDs dos exames vazios
    $idsVazios = $db->fetchAll("
        SELECT id, aluno_id, data_agendada 
        FROM exames 
        WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL
        ORDER BY id
    ");
    
    echo "📋 Exames a corrigir:\n";
    foreach ($idsVazios as $row) {
        echo "  - ID {$row['id']}: Aluno {$row['aluno_id']}, Data {$row['data_agendada']}\n";
    }
    echo "\n";
    
    // Atualizar um por um
    $atualizados = 0;
    $erros = 0;
    
    foreach ($idsVazios as $row) {
        $exameId = $row['id'];
        try {
            $resultado = $db->update(
                'exames',
                ['tipo' => $tipoDefinir],
                'id = ?',
                [$exameId]
            );
            
            if ($resultado && $resultado->rowCount() > 0) {
                $atualizados++;
                echo "✅ Exame ID {$exameId} atualizado com sucesso.\n";
            } else {
                $erros++;
                echo "⚠️ Exame ID {$exameId} não foi atualizado.\n";
            }
        } catch (Exception $e) {
            $erros++;
            echo "❌ Erro ao atualizar exame ID {$exameId}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    echo "📈 Resumo:\n";
    echo "  Total encontrados: {$count}\n";
    echo "  Atualizados: {$atualizados}\n";
    echo "  Erros: {$erros}\n\n";
    
    // Verificar resultado final
    $totalCorrigidos = $db->fetch("SELECT COUNT(*) as total FROM exames WHERE tipo = ?", [$tipoDefinir]);
    $totalCorrigido = $totalCorrigidos['total'] ?? 0;
    
    $totalVaziosRestantes = $db->fetch("SELECT COUNT(*) as total FROM exames WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL");
    $restantes = $totalVaziosRestantes['total'] ?? 0;
    
    echo "📊 Status final:\n";
    echo "  Total de exames com tipo '{$tipoDefinir}': {$totalCorrigido}\n";
    echo "  Total de exames ainda vazios: {$restantes}\n\n";
    
    if ($restantes == 0) {
        echo "✅ Todos os exames foram corrigidos com sucesso!\n";
    } else {
        echo "⚠️ Ainda há {$restantes} exames com tipo vazio.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro fatal: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

?>

