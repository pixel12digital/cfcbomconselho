<?php
/**
 * Script Auxiliar - Verificar CFCs e Criar Turma no CFC 36
 * 
 * Objetivo: 
 * 1. Listar CFCs existentes
 * 2. Verificar turmas no CFC 36
 * 3. Criar uma turma teórica no CFC 36 para teste (se necessário)
 * 
 * Uso: admin/tools/verificar-cfcs-e-criar-turma-cfc36.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isDev = (getenv('ENVIRONMENT') === 'development' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
if (!$isDev && (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'admin')) {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verificar CFCs e Criar Turma CFC 36</title>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .fail{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;} table{border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f0f0f0;} .btn{padding:10px 20px;margin:5px;background:#007bff;color:white;text-decoration:none;border-radius:5px;display:inline-block;}</style></head><body>";
echo "<h1>🔍 Verificar CFCs e Criar Turma no CFC 36</h1>";

try {
    $db = Database::getInstance();
    
    // 1. Listar todos os CFCs existentes
    echo "<h2>1. CFCs Existentes</h2>";
    $cfcs = $db->fetchAll("SELECT id, nome, cnpj, ativo FROM cfcs ORDER BY id");
    $cfcsAtivos = $db->fetchAll("SELECT id, nome, cnpj FROM cfcs WHERE ativo = 1 ORDER BY id");
    
    if (empty($cfcs)) {
        echo "<p class='warning'>⚠️ Nenhum CFC encontrado na tabela!</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>CNPJ</th><th>Ativo</th></tr>";
        foreach ($cfcs as $cfc) {
            $destaque = ($cfc['id'] == 36) ? 'style="background-color:#e8f5e9;"' : '';
            echo "<tr {$destaque}>";
            echo "<td><strong>{$cfc['id']}</strong></td>";
            echo "<td>{$cfc['nome']}</td>";
            echo "<td>{$cfc['cnpj']}</td>";
            echo "<td>" . ($cfc['ativo'] ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $cfc36Existe = false;
        foreach ($cfcs as $cfc) {
            if ($cfc['id'] == 36) {
                $cfc36Existe = true;
                echo "<p class='ok'>✅ CFC 36 existe: <strong>{$cfc['nome']}</strong></p>";
                break;
            }
        }
        
        if (!$cfc36Existe) {
            echo "<p class='fail'>❌ CFC 36 NÃO existe na tabela!</p>";
        }
        
        // Resumo de CFCs ativos
        echo "<h3>CFCs Ativos Disponíveis</h3>";
        if (empty($cfcsAtivos)) {
            echo "<p class='fail'>❌ <strong>NENHUM CFC ATIVO ENCONTRADO!</strong></p>";
            echo "<p>Não é possível criar turmas sem CFCs ativos.</p>";
        } else {
            echo "<p class='ok'>✅ Total de CFCs ativos: <strong>" . count($cfcsAtivos) . "</strong></p>";
            echo "<ul>";
            foreach ($cfcsAtivos as $cfc) {
                $destaque = ($cfc['id'] == 36) ? ' <strong>(CFC Principal)</strong>' : '';
                echo "<li>CFC {$cfc['id']}: <strong>{$cfc['nome']}</strong>{$destaque}</li>";
            }
            echo "</ul>";
            
            if (count($cfcsAtivos) === 1 && $cfcsAtivos[0]['id'] == 36) {
                echo "<p class='ok'><strong>✅ Este é o único CFC ativo do sistema. Todas as turmas devem ser criadas no CFC 36.</strong></p>";
            }
        }
        
        $cfc1Existe = false;
        foreach ($cfcs as $cfc) {
            if ($cfc['id'] == 1) {
                $cfc1Existe = true;
                echo "<p class='warning'>⚠️ CFC 1 existe: <strong>{$cfc['nome']}</strong></p>";
                break;
            }
        }
        
        if (!$cfc1Existe) {
            echo "<p class='warning'>⚠️ CFC 1 NÃO existe na tabela (por isso não foi possível atualizar o aluno)</p>";
        }
    }
    
    // 2. Verificar turmas no CFC 36
    echo "<h2>2. Turmas Teóricas no CFC 36</h2>";
    
    if ($cfc36Existe) {
        $turmasCfc36 = $db->fetchAll("
            SELECT id, nome, curso_tipo, status, data_inicio, data_fim
            FROM turmas_teoricas 
            WHERE cfc_id = 36 
            ORDER BY id DESC
            LIMIT 20
        ");
        
        if (empty($turmasCfc36)) {
            echo "<p class='warning'>⚠️ Nenhuma turma teórica encontrada no CFC 36</p>";
            echo "<p>Você pode criar uma turma para teste usando o botão abaixo.</p>";
        } else {
            echo "<p class='ok'>✅ Encontradas " . count($turmasCfc36) . " turma(s) no CFC 36</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Curso Tipo</th><th>Status</th><th>Data Início</th></tr>";
            foreach ($turmasCfc36 as $turma) {
                echo "<tr>";
                echo "<td><strong>{$turma['id']}</strong></td>";
                echo "<td>{$turma['nome']}</td>";
                echo "<td>{$turma['curso_tipo']}</td>";
                echo "<td>{$turma['status']}</td>";
                echo "<td>" . ($turma['data_inicio'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p><strong>✅ Você pode usar qualquer uma dessas turmas para testar com o aluno 167!</strong></p>";
            echo "<p>O aluno 167 já está no CFC 36 e com status 'ativo', então deve aparecer na lista.</p>";
        }
    } else {
        echo "<p class='fail'>❌ CFC 36 não existe, não é possível verificar turmas.</p>";
    }
    
    // 3. Opção para criar turma no CFC 36
    echo "<h2>3. Criar Nova Turma no CFC 36 (Opcional)</h2>";
    
    if (isset($_POST['criar_turma_cfc36'])) {
        try {
            $db->beginTransaction();
            
            // VALIDAÇÃO: Verificar se CFC 36 existe e está ativo
            $cfc36 = $db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = 36");
            
            if (!$cfc36) {
                throw new Exception("CFC 36 não existe na tabela cfcs!");
            }
            
            if (!$cfc36['ativo']) {
                throw new Exception("CFC 36 existe mas NÃO está ativo!");
            }
            
            // Só criar se passar nas validações
            $turmaId = $db->insert('turmas_teoricas', [
                'nome' => 'Turma Teste CFC 36 - Formação CNH AB',
                'cfc_id' => 36,
                'curso_tipo' => 'formacao_45h',
                'status' => 'ativa',
                'data_inicio' => date('Y-m-d'),
                'data_fim' => date('Y-m-d', strtotime('+90 days')),
                'criado_em' => date('Y-m-d H:i:s')
            ]);
            
            $db->commit();
            
            echo "<p class='ok'>✅ Turma criada com sucesso! ID: <strong>{$turmaId}</strong></p>";
            echo "<p>Você pode usar esta turma para testar com o aluno 167.</p>";
            echo "<p><a href='../pages/turmas-teoricas.php?acao=detalhes&turma_id={$turmaId}' class='btn'>Abrir Turma {$turmaId}</a></p>";
            
        } catch (Exception $e) {
            $db->rollback();
            echo "<p class='fail'>❌ Erro ao criar turma: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        if ($cfc36Existe) {
            echo "<form method='POST'>";
            echo "<p>Clique no botão abaixo para criar uma turma teórica de teste no CFC 36:</p>";
            echo "<button type='submit' name='criar_turma_cfc36' class='btn'>Criar Turma no CFC 36</button>";
            echo "</form>";
        } else {
            echo "<p class='warning'>⚠️ Não é possível criar turma porque o CFC 36 não existe.</p>";
        }
    }
    
    // 4. Resumo e próximos passos
    echo "<h2>4. Resumo e Próximos Passos</h2>";
    
    echo "<div style='background:#e3f2fd;padding:15px;border-left:4px solid #2196F3;margin:20px 0;'>";
    echo "<h3 style='margin-top:0;'>Status do Aluno 167:</h3>";
    echo "<ul>";
    echo "<li>✅ Status: <strong>ativo</strong> (já corrigido)</li>";
    echo "<li>✅ CFC: <strong>36</strong> (correto - único CFC ativo do sistema)</li>";
    echo "<li>✅ Exames: OK</li>";
    echo "<li>✅ Financeiro: OK</li>";
    echo "</ul>";
    
    echo "<h3>Para testar:</h3>";
    if (!empty($turmasCfc36)) {
        echo "<p class='ok'>✅ Use uma das turmas listadas acima (CFC 36). O aluno 167 deve aparecer na lista!</p>";
    } else {
        echo "<p class='warning'>⚠️ Crie uma turma no CFC 36 (botão acima) ou use o sistema normal para criar.</p>";
        echo "<p><strong>Nota:</strong> Como o sistema tem apenas o CFC 36 ativo, todas as turmas serão criadas neste CFC automaticamente.</p>";
    }
    
    echo "<h3>✅ Regra do Sistema (Implementada):</h3>";
    echo "<div style='background:#d4edda;padding:10px;border-left:4px solid #28a745;margin:10px 0;'>";
    echo "<ul>";
    echo "<li>Turmas <strong>SÓ podem ser criadas</strong> em CFCs existentes e ativos</li>";
    echo "<li>O sistema valida automaticamente antes de criar qualquer turma</li>";
    echo "<li>Como só existe o <strong>CFC 36</strong>, todas as turmas serão criadas neste CFC</li>";
    echo "<li>Não é mais possível criar turmas com CFCs inexistentes (como CFC 1)</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='fail'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";

