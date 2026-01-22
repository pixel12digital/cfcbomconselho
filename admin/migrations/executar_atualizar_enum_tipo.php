<?php
/**
 * Script para atualizar o ENUM do campo tipo na tabela exames
 * 
 * Uso: Acesse via navegador:
 * http://localhost/cfc-bom-conselho/admin/migrations/executar_atualizar_enum_tipo.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html lang='pt-BR'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Atualizar ENUM tipo exames</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }\n";
echo "        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo "        .success { color: #28a745; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 15px 0; }\n";
echo "        .error { color: #dc3545; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 15px 0; }\n";
echo "        .info { color: #0c5460; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 15px 0; }\n";
echo "        .warning { color: #856404; padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 4px; margin: 15px 0; }\n";
echo "        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #dee2e6; }\n";
echo "        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }\n";
echo "        h2 { color: #495057; margin-top: 30px; }\n";
echo "        table { width: 100%; border-collapse: collapse; margin: 15px 0; }\n";
echo "        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6; }\n";
echo "        th { background: #007bff; color: white; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";
echo "<h1>🔧 Atualizar ENUM do Campo tipo na Tabela exames</h1>\n";

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    
    $db = Database::getInstance();
    
    // Verificar estrutura atual
    echo "<h2>📊 Estrutura Atual do Campo tipo</h2>\n";
    $estruturaAtual = $db->fetch("
        SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'exames'
        AND COLUMN_NAME = 'tipo'
    ");
    
    if ($estruturaAtual) {
        echo "<div class='info'>\n";
        echo "<strong>Estrutura atual:</strong><br>\n";
        echo "Tipo: <code>" . htmlspecialchars($estruturaAtual['COLUMN_TYPE']) . "</code><br>\n";
        echo "Permite NULL: " . ($estruturaAtual['IS_NULLABLE'] === 'YES' ? 'SIM' : 'NÃO') . "<br>\n";
        echo "Valor padrão: " . ($estruturaAtual['COLUMN_DEFAULT'] ?? 'Nenhum') . "\n";
        echo "</div>\n";
    }
    
    // Verificar se precisa atualizar
    $precisaAtualizar = false;
    if ($estruturaAtual && strpos($estruturaAtual['COLUMN_TYPE'], "'teorico'") === false) {
        $precisaAtualizar = true;
        echo "<div class='warning'>\n";
        echo "⚠️ <strong>O ENUM não inclui 'teorico' e 'pratico'!</strong><br>\n";
        echo "É necessário atualizar a estrutura para incluir esses valores.\n";
        echo "</div>\n";
    } elseif ($estruturaAtual && strpos($estruturaAtual['COLUMN_TYPE'], "'pratico'") === false) {
        $precisaAtualizar = true;
        echo "<div class='warning'>\n";
        echo "⚠️ <strong>O ENUM não inclui 'pratico'!</strong><br>\n";
        echo "É necessário atualizar a estrutura.\n";
        echo "</div>\n";
    } else {
        echo "<div class='success'>\n";
        echo "✅ O ENUM já inclui 'teorico' e 'pratico'. Nenhuma atualização necessária.\n";
        echo "</div>\n";
    }
    
    // Executar atualização se necessário
    $acao = $_GET['acao'] ?? '';
    
    if ($precisaAtualizar && $acao === 'executar') {
        echo "<h2>⚙️ Executando Atualização</h2>\n";
        
        try {
            // Executar ALTER TABLE
            $db->query("
                ALTER TABLE exames 
                MODIFY COLUMN tipo ENUM('medico', 'psicotecnico', 'teorico', 'pratico') NOT NULL
            ");
            
            // Verificar estrutura atualizada
            $estruturaNova = $db->fetch("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'exames'
                AND COLUMN_NAME = 'tipo'
            ");
            
            if ($estruturaNova) {
                echo "<div class='success'>\n";
                echo "✅ <strong>ENUM atualizado com sucesso!</strong><br>\n";
                echo "Nova estrutura: <code>" . htmlspecialchars($estruturaNova['COLUMN_TYPE']) . "</code><br>\n";
                echo "Agora você pode corrigir os exames com tipo vazio usando o script corrigir_tipo_exames_vazio.php\n";
                echo "</div>\n";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>\n";
            echo "❌ Erro ao atualizar ENUM: " . htmlspecialchars($e->getMessage()) . "<br>\n";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
            echo "</div>\n";
        }
        
    } elseif ($precisaAtualizar) {
        echo "<h2>🔧 Pronto para Atualizar</h2>\n";
        echo "<div class='warning'>\n";
        echo "⚠️ <strong>Atenção:</strong> Esta operação irá alterar a estrutura da tabela exames.<br>\n";
        echo "Certifique-se de fazer backup antes de continuar.\n";
        echo "</div>\n";
        
        echo "<a href='?acao=executar' class='btn' style='display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px;'>\n";
        echo "⚠️ Executar Atualização\n";
        echo "</a>\n";
        
        echo "<a href='?' class='btn' style='display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px;'>\n";
        echo "↻ Recarregar\n";
        echo "</a>\n";
    }
    
    echo "<h2>📝 SQL para Executar Manualmente</h2>\n";
    echo "<div class='info'>\n";
    echo "Se preferir executar manualmente no MySQL:\n";
    echo "</div>\n";
    echo "<pre>";
    echo "ALTER TABLE exames \n";
    echo "MODIFY COLUMN tipo ENUM('medico', 'psicotecnico', 'teorico', 'pratico') NOT NULL;\n";
    echo "</pre>\n";
    
    echo "<div class='info'>\n";
    echo "✅ Script executado com sucesso!<br>\n";
    echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'>\n";
    echo "❌ Erro ao executar script: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    echo "</div>\n";
}

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>

