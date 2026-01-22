<?php
/**
 * Script para verificar CFC do Instrutor Carlos da Silva
 * 
 * Objetivo: Identificar por que o instrutor não aparece na lista
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificar CFC do Instrutor Carlos da Silva</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #2196F3; color: white; }
        .ok { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .info-box { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
        .error-box { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0; }
        .success-box { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificar CFC do Instrutor Carlos da Silva</h1>
        
        <?php
        try {
            $db = Database::getInstance();
            
            // 1. Buscar instrutor Carlos da Silva
            echo "<h2>1. Dados do Instrutor</h2>";
            $instrutor = $db->fetch("
                SELECT i.*, 
                       COALESCE(u.nome, i.nome) as nome,
                       COALESCE(u.email, i.email) as email,
                       c.nome as cfc_nome
                FROM instrutores i
                LEFT JOIN usuarios u ON i.usuario_id = u.id
                LEFT JOIN cfcs c ON i.cfc_id = c.id
                WHERE COALESCE(u.nome, i.nome) LIKE '%Carlos%Silva%' 
                   OR i.email LIKE '%carlos%silva%'
                   OR i.credencial LIKE '%TESTE_API%'
                ORDER BY i.id DESC
                LIMIT 1
            ");
            
            if (!$instrutor) {
                echo "<div class='error-box'>";
                echo "<p class='fail'>❌ Instrutor Carlos da Silva não encontrado!</p>";
                echo "</div>";
                
                // Listar todos os instrutores para referência
                echo "<h2>Instrutores Disponíveis:</h2>";
                $todosInstrutores = $db->fetchAll("
                    SELECT i.id, 
                           COALESCE(u.nome, i.nome) as nome,
                           i.cfc_id,
                           i.ativo,
                           c.nome as cfc_nome
                    FROM instrutores i
                    LEFT JOIN usuarios u ON i.usuario_id = u.id
                    LEFT JOIN cfcs c ON i.cfc_id = c.id
                    ORDER BY COALESCE(u.nome, i.nome)
                ");
                
                echo "<table>";
                echo "<tr><th>ID</th><th>Nome</th><th>CFC ID</th><th>CFC Nome</th><th>Ativo</th></tr>";
                foreach ($todosInstrutores as $inst) {
                    echo "<tr>";
                    echo "<td>{$inst['id']}</td>";
                    echo "<td><strong>{$inst['nome']}</strong></td>";
                    echo "<td>{$inst['cfc_id']}</td>";
                    echo "<td>{$inst['cfc_nome']}</td>";
                    echo "<td>" . ($inst['ativo'] ? 'Sim' : 'Não') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                exit;
            }
            
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>ID</td><td><strong>{$instrutor['id']}</strong></td></tr>";
            echo "<tr><td>Nome</td><td><strong>{$instrutor['nome']}</strong></td></tr>";
            echo "<tr><td>Email</td><td>{$instrutor['email']}</td></tr>";
            echo "<tr><td>Credencial</td><td>{$instrutor['credencial']}</td></tr>";
            echo "<tr><td>CFC ID</td><td><strong>{$instrutor['cfc_id']}</strong></td></tr>";
            echo "<tr><td>CFC Nome</td><td><strong>{$instrutor['cfc_nome']}</strong></td></tr>";
            echo "<tr><td>Ativo</td><td>" . ($instrutor['ativo'] ? '<span class="ok">Sim</span>' : '<span class="fail">Não</span>') . "</td></tr>";
            echo "</table>";
            
            // 2. Verificar CFC da turma 19
            echo "<h2>2. CFC da Turma 19</h2>";
            $turma19 = $db->fetch("
                SELECT id, nome, cfc_id, status
                FROM turmas_teoricas
                WHERE id = 19
            ");
            
            if (!$turma19) {
                echo "<div class='error-box'>";
                echo "<p class='fail'>❌ Turma 19 não encontrada!</p>";
                echo "</div>";
            } else {
                $cfcTurma = $db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = ?", [$turma19['cfc_id']]);
                
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                echo "<tr><td>Turma ID</td><td><strong>{$turma19['id']}</strong></td></tr>";
                echo "<tr><td>Turma Nome</td><td><strong>{$turma19['nome']}</strong></td></tr>";
                echo "<tr><td>CFC ID</td><td><strong>{$turma19['cfc_id']}</strong></td></tr>";
                echo "<tr><td>CFC Nome</td><td><strong>" . ($cfcTurma ? $cfcTurma['nome'] : 'CFC não existe!') . "</strong></td></tr>";
                echo "<tr><td>CFC Ativo</td><td>" . ($cfcTurma && $cfcTurma['ativo'] ? '<span class="ok">Sim</span>' : '<span class="fail">Não</span>') . "</td></tr>";
                echo "</table>";
            }
            
            // 3. Diagnóstico
            echo "<h2>3. Diagnóstico</h2>";
            
            if ($turma19 && $instrutor) {
                $cfcInstrutor = (int)$instrutor['cfc_id'];
                $cfcTurma = (int)$turma19['cfc_id'];
                
                echo "<div class='info-box'>";
                echo "<p><strong>Comparação:</strong></p>";
                echo "<ul>";
                echo "<li>CFC do Instrutor: <strong>{$cfcInstrutor}</strong></li>";
                echo "<li>CFC da Turma: <strong>{$cfcTurma}</strong></li>";
                echo "</ul>";
                echo "</div>";
                
                if ($cfcInstrutor != $cfcTurma) {
                    echo "<div class='error-box'>";
                    echo "<p class='fail'>❌ <strong>PROBLEMA IDENTIFICADO:</strong></p>";
                    echo "<p>O instrutor está no CFC {$cfcInstrutor}, mas a turma está no CFC {$cfcTurma}.</p>";
                    echo "<p>A query de instrutores filtra por <code>i.cfc_id = cfc_id_da_turma</code>, então o instrutor não aparece na lista.</p>";
                    echo "</div>";
                    
                    echo "<div class='success-box'>";
                    echo "<h3>✅ Soluções:</h3>";
                    echo "<ol>";
                    echo "<li><strong>Atualizar CFC do instrutor:</strong> Atualizar o instrutor para usar o CFC {$cfcTurma} (CFC da turma)</li>";
                    echo "<li><strong>OU atualizar CFC da turma:</strong> Se o instrutor deveria estar no CFC {$cfcInstrutor}, atualizar a turma 19 para usar esse CFC</li>";
                    echo "</ol>";
                    echo "</div>";
                    
                    if ($cfcTurma == 36) {
                        echo "<form method='POST' style='margin-top: 20px;'>";
                        echo "<h3>Corrigir CFC do Instrutor</h3>";
                        echo "<p>Atualizar o instrutor '{$instrutor['nome']}' para usar o CFC 36 (mesmo CFC da turma)?</p>";
                        echo "<button type='submit' name='corrigir_cfc' class='btn' style='background:#28a745;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;'>✅ Corrigir CFC do Instrutor</button>";
                        echo "</form>";
                    }
                    
                } else {
                    echo "<div class='success-box'>";
                    echo "<p class='ok'>✅ CFCs coincidem! O instrutor deveria aparecer na lista.</p>";
                    echo "<p>Verifique se o instrutor está ativo: " . ($instrutor['ativo'] ? '<span class="ok">Sim</span>' : '<span class="fail">Não (por isso não aparece)</span>') . "</p>";
                    echo "</div>";
                }
            }
            
            // 4. Processar correção se solicitado
            if (isset($_POST['corrigir_cfc']) && $turma19) {
                echo "<h2>4. Correção Aplicada</h2>";
                
                try {
                    $db->beginTransaction();
                    
                    $db->update('instrutores', ['cfc_id' => $turma19['cfc_id']], 'id = ?', [$instrutor['id']]);
                    
                    $db->commit();
                    
                    echo "<div class='success-box'>";
                    echo "<p class='ok'>✅ CFC do instrutor atualizado com sucesso!</p>";
                    echo "<p>O instrutor '{$instrutor['nome']}' agora está no CFC {$turma19['cfc_id']} (mesmo CFC da turma 19).</p>";
                    echo "<p><strong>Recarregue a página da turma 19 e abra o modal de agendar aula.</strong> O instrutor deve aparecer na lista agora!</p>";
                    echo "</div>";
                    
                    // Recarregar dados
                    $instrutor = $db->fetch("
                        SELECT i.*, 
                               COALESCE(u.nome, i.nome) as nome,
                               c.nome as cfc_nome
                        FROM instrutores i
                        LEFT JOIN usuarios u ON i.usuario_id = u.id
                        LEFT JOIN cfcs c ON i.cfc_id = c.id
                        WHERE i.id = ?
                    ", [$instrutor['id']]);
                    
                } catch (Exception $e) {
                    $db->rollback();
                    echo "<div class='error-box'>";
                    echo "<p class='fail'>❌ Erro ao corrigir: " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error-box'>";
            echo "<p class='fail'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>





