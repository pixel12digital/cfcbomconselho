<?php
/**
 * Script para executar migration: Adicionar campo hora_agendada na tabela exames
 * 
 * Uso: Acesse via navegador ou execute via CLI:
 * php admin/migrations/run_add_hora_agendada.php
 * 
 * Ou acesse via navegador:
 * http://localhost/cfc-bom-conselho/admin/migrations/run_add_hora_agendada.php
 */

// Headers para exibição
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>\n";
echo "<html lang='pt-BR'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>Migration: Adicionar hora_agendada</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }\n";
echo "        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
echo "        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }\n";
echo "        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }\n";
echo "        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }\n";
echo "        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }\n";
echo "        h1 { color: #333; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";
echo "<h1>🔧 Executando Migration: Adicionar hora_agendada</h1>\n";

try {
    // Incluir arquivos necessários
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    
    echo "<div class='info'>✅ Arquivos incluídos com sucesso</div>\n";
    
    // Obter instância do banco
    $db = Database::getInstance();
    echo "<div class='info'>✅ Conexão com banco de dados estabelecida</div>\n";
    
    // Verificar se a coluna já existe
    echo "<div class='info'>🔍 Verificando se a coluna hora_agendada já existe...</div>\n";
    
    $checkQuery = "
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'exames'
        AND COLUMN_NAME = 'hora_agendada'
    ";
    
    $result = $db->fetch($checkQuery);
    $colunaExiste = $result['count'] > 0;
    
    if ($colunaExiste) {
        echo "<div class='info'>ℹ️ A coluna hora_agendada já existe na tabela exames. Nenhuma alteração necessária.</div>\n";
    } else {
        echo "<div class='info'>➕ A coluna hora_agendada não existe. Adicionando...</div>\n";
        
        // Executar ALTER TABLE
        $alterQuery = "ALTER TABLE exames ADD COLUMN hora_agendada TIME NULL AFTER data_agendada";
        
        try {
            $db->query($alterQuery);
            echo "<div class='success'>✅ Migration executada com sucesso!</div>\n";
            echo "<div class='info'>📋 Coluna hora_agendada adicionada à tabela exames</div>\n";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao executar migration:</div>\n";
            echo "<div class='error'><pre>" . htmlspecialchars($e->getMessage()) . "</pre></div>\n";
            throw $e;
        }
    }
    
    // Verificar estrutura atualizada
    echo "<div class='info'>🔍 Verificando estrutura da tabela exames...</div>\n";
    
    $structureQuery = "
        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'exames'
        AND COLUMN_NAME IN ('data_agendada', 'hora_agendada')
        ORDER BY ORDINAL_POSITION
    ";
    
    $columns = $db->fetchAll($structureQuery);
    
    if (!empty($columns)) {
        echo "<div class='info'><strong>Estrutura das colunas relacionadas:</strong></div>\n";
        echo "<pre>";
        foreach ($columns as $col) {
            echo sprintf(
                "%-20s %-15s NULL=%s DEFAULT=%s\n",
                $col['COLUMN_NAME'],
                $col['DATA_TYPE'],
                $col['IS_NULLABLE'],
                $col['COLUMN_DEFAULT'] ?? 'NULL'
            );
        }
        echo "</pre>\n";
    }
    
    echo "<div class='success'><strong>✅ Migration concluída com sucesso!</strong></div>\n";
    echo "<div class='info'>📝 Você pode fechar esta página agora.</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Erro durante a execução:</strong></div>\n";
    echo "<div class='error'><pre>" . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>\n";
}

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
?>

