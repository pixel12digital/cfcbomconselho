<?php
/**
 * Script de Correção de Foreign Key - turma_presencas
 * 
 * Objetivo: Corrigir a foreign key que aponta para turmas (antiga) 
 *           para apontar para turmas_teoricas (nova)
 * 
 * Uso: Acesse via navegador ou execute via CLI
 *       http://localhost/cfc-bom-conselho/admin/tools/corrigir-foreign-key-turma-presencas.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance();
$erros = [];
$sucessos = [];

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Correção de Foreign Key - turma_presencas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; border-radius: 4px; }
        .success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .info { background: #d1ecf1; border-left-color: #17a2b8; color: #0c5460; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Correção de Foreign Key - turma_presencas</h1>";

try {
    // PASSO 1: Verificar estrutura atual da foreign key
    echo "<div class='step info'><h2>📋 PASSO 1: Verificando estrutura atual</h2>";
    
    $foreignKeys = $db->fetchAll("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'turma_presencas'
        AND CONSTRAINT_NAME LIKE '%ibfk%'
    ");
    
    if (empty($foreignKeys)) {
        echo "<p>⚠️ Nenhuma foreign key encontrada na tabela turma_presencas.</p>";
    } else {
        echo "<table>
            <tr>
                <th>Constraint</th>
                <th>Coluna</th>
                <th>Tabela Referenciada</th>
                <th>Coluna Referenciada</th>
                <th>Status</th>
            </tr>";
        
        foreach ($foreignKeys as $fk) {
            $status = ($fk['REFERENCED_TABLE_NAME'] === 'turmas_teoricas') ? 
                "<span style='color: green;'>✅ Correto</span>" : 
                "<span style='color: red;'>❌ Incorreto (aponta para {$fk['REFERENCED_TABLE_NAME']})</span>";
            
            echo "<tr>
                <td>{$fk['CONSTRAINT_NAME']}</td>
                <td>{$fk['COLUMN_NAME']}</td>
                <td>{$fk['REFERENCED_TABLE_NAME']}</td>
                <td>{$fk['REFERENCED_COLUMN_NAME']}</td>
                <td>{$status}</td>
            </tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // PASSO 2: Verificar se turma 19 existe
    echo "<div class='step info'><h2>🔍 PASSO 2: Verificando turma ID 19</h2>";
    
    $turma19_teoricas = $db->fetch("SELECT id, nome, status FROM turmas_teoricas WHERE id = 19");
    $turma19_antiga = $db->fetch("SELECT id, nome FROM turmas WHERE id = 19");
    
    if ($turma19_teoricas) {
        echo "<p class='success'>✅ Turma 19 encontrada em <strong>turmas_teoricas</strong>:</p>";
        echo "<pre>ID: {$turma19_teoricas['id']}\nNome: {$turma19_teoricas['nome']}\nStatus: {$turma19_teoricas['status']}</pre>";
    } else {
        echo "<p class='warning'>⚠️ Turma 19 NÃO encontrada em <strong>turmas_teoricas</strong></p>";
    }
    
    if ($turma19_antiga) {
        echo "<p class='warning'>⚠️ Turma 19 encontrada em <strong>turmas</strong> (tabela antiga):</p>";
        echo "<pre>ID: {$turma19_antiga['id']}\nNome: {$turma19_antiga['nome']}</pre>";
    }
    echo "</div>";
    
    // PASSO 3: Verificar dados órfãos
    echo "<div class='step info'><h2>🔍 PASSO 3: Verificando dados órfãos</h2>";
    
    $presencasOrfas = $db->fetchAll("
        SELECT COUNT(*) as total
        FROM turma_presencas tp
        LEFT JOIN turmas_teoricas tt ON tp.turma_id = tt.id
        WHERE tt.id IS NULL
    ");
    
    $totalOrfas = $presencasOrfas[0]['total'] ?? 0;
    
    if ($totalOrfas > 0) {
        echo "<p class='error'>❌ Encontradas <strong>{$totalOrfas}</strong> presenças com turma_id que não existe em turmas_teoricas</p>";
        
        $detalhesOrfas = $db->fetchAll("
            SELECT DISTINCT tp.turma_id, COUNT(*) as total_presencas
            FROM turma_presencas tp
            LEFT JOIN turmas_teoricas tt ON tp.turma_id = tt.id
            WHERE tt.id IS NULL
            GROUP BY tp.turma_id
        ");
        
        echo "<table>
            <tr><th>Turma ID</th><th>Total de Presenças</th></tr>";
        foreach ($detalhesOrfas as $orf) {
            echo "<tr><td>{$orf['turma_id']}</td><td>{$orf['total_presencas']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='success'>✅ Nenhuma presença órfã encontrada. Todos os turma_id existem em turmas_teoricas.</p>";
    }
    echo "</div>";
    
    // PASSO 4: Verificar se pode corrigir
    $podeCorrigir = true;
    $fkIncorreta = false;
    
    foreach ($foreignKeys as $fk) {
        if ($fk['REFERENCED_TABLE_NAME'] === 'turmas' && $fk['COLUMN_NAME'] === 'turma_id') {
            $fkIncorreta = true;
            break;
        }
    }
    
    if (!$fkIncorreta) {
        echo "<div class='step success'><h2>✅ PASSO 4: Status da Foreign Key</h2>";
        echo "<p>✅ A foreign key já está correta ou não existe. Nenhuma correção necessária.</p></div>";
        $podeCorrigir = false;
    }
    
    // PASSO 5: Executar correção (se necessário e se solicitado)
    if ($podeCorrigir && isset($_GET['executar']) && $_GET['executar'] === 'sim') {
        echo "<div class='step warning'><h2>🔧 PASSO 5: Executando correção</h2>";
        
        // NOTA: MySQL não suporta transações para comandos DDL (ALTER TABLE)
        // Cada ALTER TABLE faz commit automático, então não usamos transações aqui
        
        try {
            // Remover foreign key antiga
            echo "<p>1️⃣ Removendo foreign key antiga...</p>";
            $db->query("ALTER TABLE turma_presencas DROP FOREIGN KEY turma_presencas_ibfk_1");
            echo "<p class='success'>✅ Foreign key antiga removida com sucesso</p>";
            
            // Adicionar foreign key correta
            echo "<p>2️⃣ Adicionando foreign key correta...</p>";
            $db->query("
                ALTER TABLE turma_presencas 
                ADD CONSTRAINT turma_presencas_ibfk_1 
                FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE
            ");
            echo "<p class='success'>✅ Foreign key correta adicionada com sucesso</p>";
            
            echo "<div class='step success'><h2>✅ Correção concluída com sucesso!</h2>";
            echo "<p>A foreign key foi corrigida. Agora turma_presencas.turma_id referencia turmas_teoricas.id</p></div>";
            
            // Recarregar a página para mostrar o novo status
            echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erro ao executar correção: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "<p class='warning'>⚠️ Se a primeira operação (DROP) foi bem-sucedida mas a segunda falhou, você pode precisar executar o script novamente.</p>";
        }
        echo "</div>";
    } elseif ($podeCorrigir) {
        echo "<div class='step warning'><h2>⚠️ PASSO 5: Correção necessária</h2>";
        echo "<p>A foreign key está apontando para a tabela <strong>turmas</strong> (antiga).</p>";
        echo "<p>É necessário corrigir para apontar para <strong>turmas_teoricas</strong> (nova).</p>";
        
        if ($totalOrfas > 0) {
            echo "<p class='error'><strong>⚠️ ATENÇÃO:</strong> Existem {$totalOrfas} presenças órfãs. Corrigir a foreign key pode causar problemas.</p>";
            echo "<p>Recomendação: Corrija os dados órfãos primeiro antes de corrigir a foreign key.</p>";
        }
        
        echo "<p><strong>Ações que serão executadas:</strong></p>";
        echo "<ol>
            <li>Remover foreign key antiga: <code>turma_presencas_ibfk_1</code></li>
            <li>Adicionar foreign key correta apontando para <code>turmas_teoricas(id)</code></li>
        </ol>";
        
        echo "<p><a href='?executar=sim' class='btn btn-danger' onclick='return confirm(\"Tem certeza que deseja executar a correção? Esta ação não pode ser desfeita.\")'>🔧 Executar Correção</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='step error'><h2>❌ Erro</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

echo "</div></body></html>";
?>





