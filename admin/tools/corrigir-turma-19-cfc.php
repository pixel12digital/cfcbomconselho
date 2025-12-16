<?php
/**
 * Script para corrigir CFC da Turma 19
 * 
 * PROBLEMA: Turma 19 está com CFC 1 (inexistente)
 * SOLUÇÃO: Atualizar para CFC 36 (único CFC ativo)
 */

// Verificar se é admin (via sessão)
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

$db = Database::getInstance();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrigir CFC da Turma 19</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #2196F3;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #2196F3;
            color: white;
        }
        .ok {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .fail {
            color: #dc3545;
            font-weight: bold;
        }
        .btn {
            background: #2196F3;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #1976D2;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-left: 4px solid #2196F3;
            margin: 20px 0;
        }
        .success-box {
            background: #d4edda;
            padding: 15px;
            border-left: 4px solid #28a745;
            margin: 20px 0;
        }
        .error-box {
            background: #f8d7da;
            padding: 15px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Corrigir CFC da Turma 19</h1>
        
        <?php
        try {
            // 1. Verificar turma 19
            echo "<h2>1. Status da Turma 19</h2>";
            $turma19 = $db->fetch("
                SELECT id, nome, cfc_id, status, curso_tipo, data_inicio, data_fim
                FROM turmas_teoricas 
                WHERE id = 19
            ");
            
            if (!$turma19) {
                echo "<div class='error-box'>";
                echo "<p class='fail'>❌ Turma 19 não encontrada no banco de dados!</p>";
                echo "</div>";
                exit;
            }
            
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>ID</td><td><strong>{$turma19['id']}</strong></td></tr>";
            echo "<tr><td>Nome</td><td><strong>{$turma19['nome']}</strong></td></tr>";
            echo "<tr><td>CFC ID (ATUAL)</td><td><strong class='fail'>{$turma19['cfc_id']}</strong></td></tr>";
            echo "<tr><td>Status</td><td>{$turma19['status']}</td></tr>";
            echo "<tr><td>Curso</td><td>{$turma19['curso_tipo']}</td></tr>";
            echo "</table>";
            
            // 2. Verificar se CFC 1 existe
            echo "<h2>2. Verificação de CFCs</h2>";
            $cfc1 = $db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = 1");
            $cfc36 = $db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = 36");
            
            if ($cfc1) {
                echo "<p class='warning'>⚠️ CFC 1 existe: <strong>{$cfc1['nome']}</strong> (Ativo: " . ($cfc1['ativo'] ? 'Sim' : 'Não') . ")</p>";
            } else {
                echo "<p class='fail'>❌ CFC 1 NÃO existe na tabela cfcs</p>";
            }
            
            if ($cfc36) {
                echo "<p class='ok'>✅ CFC 36 existe: <strong>{$cfc36['nome']}</strong> (Ativo: " . ($cfc36['ativo'] ? 'Sim' : 'Não') . ")</p>";
            } else {
                echo "<p class='fail'>❌ CFC 36 NÃO existe na tabela cfcs!</p>";
                exit;
            }
            
            // 3. Processar correção
            if (isset($_POST['corrigir_cfc'])) {
                echo "<h2>3. Correção Aplicada</h2>";
                
                if ($turma19['cfc_id'] == 36) {
                    echo "<div class='info-box'>";
                    echo "<p class='ok'>✅ A turma 19 já está com CFC 36. Nenhuma correção necessária.</p>";
                    echo "</div>";
                } else {
                    try {
                        $db->beginTransaction();
                        
                        // Atualizar CFC da turma
                        $db->update('turmas_teoricas', ['cfc_id' => 36], 'id = ?', [19]);
                        
                        // Verificar se atualizou corretamente
                        $turmaAtualizada = $db->fetch("
                            SELECT id, nome, cfc_id 
                            FROM turmas_teoricas 
                            WHERE id = 19
                        ");
                        
                        $db->commit();
                        
                        echo "<div class='success-box'>";
                        echo "<p class='ok'>✅ <strong>Correção aplicada com sucesso!</strong></p>";
                        echo "<p>Turma 19 agora está associada ao CFC 36.</p>";
                        echo "<p><strong>CFC anterior:</strong> {$turma19['cfc_id']}</p>";
                        echo "<p><strong>CFC atual:</strong> {$turmaAtualizada['cfc_id']}</p>";
                        echo "</div>";
                        
                        echo "<div class='info-box'>";
                        echo "<h3>Próximos Passos:</h3>";
                        echo "<ol>";
                        echo "<li>Recarregue a página de detalhes da turma 19</li>";
                        echo "<li>Abra o modal 'Matricular Alunos na Turma'</li>";
                        echo "<li>O aluno 167 deve aparecer na lista (se tiver exames OK)</li>";
                        echo "</ol>";
                        echo "<p><a href='../pages/turmas-teoricas.php?acao=detalhes&turma_id=19' class='btn'>Ir para Turma 19</a></p>";
                        echo "</div>";
                        
                    } catch (Exception $e) {
                        $db->rollback();
                        echo "<div class='error-box'>";
                        echo "<p class='fail'>❌ Erro ao corrigir: " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "</div>";
                    }
                }
            } else {
                // Mostrar formulário de correção
                echo "<h2>3. Aplicar Correção</h2>";
                
                if ($turma19['cfc_id'] == 1 || !$cfc1) {
                    echo "<div class='info-box'>";
                    echo "<p><strong>Problema identificado:</strong></p>";
                    echo "<ul>";
                    echo "<li>Turma 19 está com CFC ID: <strong class='fail'>{$turma19['cfc_id']}</strong></li>";
                    if (!$cfc1) {
                        echo "<li>CFC 1 <strong>não existe</strong> na tabela cfcs</li>";
                    }
                    echo "<li>Por isso, a API não encontra alunos (busca alunos do CFC da turma)</li>";
                    echo "</ul>";
                    echo "<p><strong>Solução:</strong> Atualizar turma 19 para usar CFC 36 (único CFC ativo)</p>";
                    echo "</div>";
                    
                    echo "<form method='POST'>";
                    echo "<p><strong>Ao clicar no botão abaixo, a turma 19 será atualizada para CFC 36.</strong></p>";
                    echo "<button type='submit' name='corrigir_cfc' class='btn'>✅ Corrigir CFC da Turma 19</button>";
                    echo "</form>";
                } else {
                    echo "<div class='info-box'>";
                    echo "<p class='ok'>✅ A turma 19 já está com um CFC válido (ID: {$turma19['cfc_id']}).</p>";
                    echo "</div>";
                }
            }
            
            // 4. Verificar alunos disponíveis no CFC 36
            echo "<h2>4. Alunos Disponíveis no CFC 36</h2>";
            $alunosCfc36 = $db->fetchAll("
                SELECT id, nome, cpf, status, cfc_id
                FROM alunos 
                WHERE cfc_id = 36 
                AND status IN ('ativo', 'em_andamento')
                ORDER BY nome
                LIMIT 10
            ");
            
            if (empty($alunosCfc36)) {
                echo "<p class='warning'>⚠️ Nenhum aluno encontrado no CFC 36 com status ativo/em_andamento</p>";
            } else {
                echo "<p>Total de alunos no CFC 36 (ativos/em_andamento): <strong>" . count($alunosCfc36) . "</strong></p>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Nome</th><th>CPF</th><th>Status</th></tr>";
                foreach ($alunosCfc36 as $aluno) {
                    $destaque = ($aluno['id'] == 167) ? 'style="background-color:#fff3cd;"' : '';
                    echo "<tr {$destaque}>";
                    echo "<td>{$aluno['id']}</td>";
                    echo "<td>{$aluno['nome']}</td>";
                    echo "<td>{$aluno['cpf']}</td>";
                    echo "<td>{$aluno['status']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                if (count($alunosCfc36) >= 10) {
                    echo "<p><em>(Mostrando apenas os primeiros 10)</em></p>";
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

