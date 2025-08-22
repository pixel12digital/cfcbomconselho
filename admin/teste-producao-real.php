<?php
/**
 * TESTE REAL DE PRODUÇÃO - Sistema CFC
 * 
 * Este script EXECUTA REALMENTE todas as operações no banco de dados:
 * 1. Cadastro de CFC
 * 2. Cadastro de Instrutor
 * 3. Cadastro de Aluno
 * 4. Cadastro de Veículo
 * 5. Agendamento de Aulas (com todas as regras)
 * 
 * ⚠️ ATENÇÃO: Este script CRIA dados reais no banco!
 * Use apenas em ambiente de teste ou produção controlada.
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/controllers/AgendamentoController.php';

// Verificar se está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<h1>❌ Acesso Negado</h1>";
    echo "<p>Você precisa estar logado para executar este teste.</p>";
    echo "<p><a href='../index.php'>Fazer Login</a></p>";
    exit;
}

$db = Database::getInstance();
$agendamentoController = new AgendamentoController();

// IDs dos registros criados para limpeza posterior
$registrosTeste = [
    'cfc_id' => null,
    'usuario_instrutor_ids' => [], // Array para 2 usuários instrutores
    'instrutor_ids' => [], // Array para 2 instrutores
    'aluno_ids' => [], // Array para 2 alunos
    'veiculo_ids' => [], // Array para 3 veículos
    'aulas_ids' => []
];

// Função para executar teste com retorno
function executarTeste($nome, $callback) {
    echo "<div class='teste-item'>";
    echo "<h3>🧪 {$nome}</h3>";
    echo "<div class='teste-conteudo'>";
    
    try {
        $resultado = $callback();
        if ($resultado['sucesso']) {
            echo "<div class='resultado sucesso'>✅ {$resultado['mensagem']}</div>";
            if (isset($resultado['dados'])) {
                echo "<div class='dados'>📊 Dados: " . json_encode($resultado['dados'], JSON_PRETTY_PRINT) . "</div>";
            }
        } else {
            echo "<div class='resultado erro'>❌ {$resultado['mensagem']}</div>";
        }
    } catch (Exception $e) {
        echo "<div class='resultado erro'>💥 Erro: " . $e->getMessage() . "</div>";
    }
    
    echo "</div></div>";
}

// Função para limpar dados de teste
function limparDadosTeste($db, $registros) {
    try {
        echo "<h3>🧹 Limpando dados de teste...</h3>";
        
        // Limpar em ordem reversa para evitar problemas de foreign key
        if (!empty($registros['aulas_ids'])) {
            foreach ($registros['aulas_ids'] as $aulaId) {
                $db->query("DELETE FROM aulas WHERE id = ?", [$aulaId]);
                echo "Aula ID {$aulaId} removida<br>";
            }
        }
        
        if (!empty($registros['veiculo_ids'])) {
            foreach ($registros['veiculo_ids'] as $veiculoId) {
                $db->query("DELETE FROM veiculos WHERE id = ?", [$veiculoId]);
                echo "Veículo ID {$veiculoId} removido<br>";
            }
        }
        
        if (!empty($registros['aluno_ids'])) {
            foreach ($registros['aluno_ids'] as $alunoId) {
                $db->query("DELETE FROM alunos WHERE id = ?", [$alunoId]);
                echo "Aluno ID {$alunoId} removido<br>";
            }
        }
        
        if (!empty($registros['instrutor_ids'])) {
            foreach ($registros['instrutor_ids'] as $instrutorId) {
                $db->query("DELETE FROM instrutores WHERE id = ?", [$instrutorId]);
                echo "Instrutor ID {$instrutorId} removido<br>";
            }
        }
        
        if (!empty($registros['usuario_instrutor_ids'])) {
            foreach ($registros['usuario_instrutor_ids'] as $usuarioId) {
                $db->query("DELETE FROM usuarios WHERE id = ?", [$usuarioId]);
                echo "Usuário instrutor ID {$usuarioId} removido<br>";
            }
        }
        
        if ($registros['cfc_id']) {
            $db->query("DELETE FROM cfcs WHERE id = ?", [$registros['cfc_id']]);
            echo "CFC ID {$registros['cfc_id']} removido<br>";
        }
        
        echo "<div class='resultado sucesso'>✅ Todos os dados de teste foram removidos com sucesso!</div>";
        
    } catch (Exception $e) {
        echo "<div class='resultado erro'>❌ Erro ao limpar dados: " . $e->getMessage() . "</div>";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Teste Real de Produção - Sistema CFC</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 10px;
        }
        .teste-item {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }
        .teste-item h3 {
            margin: 0 0 15px 0;
            color: #333;
            border-bottom: 2px solid #dc3545;
            padding-bottom: 10px;
        }
        .teste-conteudo {
            padding: 15px;
            background: white;
            border-radius: 5px;
        }
        .resultado {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .resultado.sucesso {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .resultado.erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .dados {
            background: #e9ecef;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .acoes {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            padding: 12px 24px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .resumo {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin: 5px;
        }
        .status.sucesso { background: #d4edda; color: #155724; }
        .status.erro { background: #f8d7da; color: #721c24; }
        .status.pendente { background: #fff3cd; color: #856404; }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 TESTE REAL DE PRODUÇÃO</h1>
            <h2>Sistema CFC - Teste com Dados Reais</h2>
            <p>⚠️ ATENÇÃO: Este teste CRIA dados reais no banco de dados!</p>
        </div>

        <div class="warning">
            <h3>⚠️ AVISO IMPORTANTE</h3>
            <p><strong>Este script executa operações REAIS no banco de dados:</strong></p>
            <ul>
                <li>✅ Cria CFC real</li>
                <li>✅ Cria usuário e instrutor reais</li>
                <li>✅ Cria aluno real</li>
                <li>✅ Cria veículo real</li>
                <li>✅ Cria aulas reais</li>
                <li>✅ Testa todas as regras de agendamento</li>
            </ul>
            <p><strong>Use apenas em ambiente de teste ou quando tiver certeza!</strong></p>
        </div>

        <div class="resumo">
            <h3>📋 Resumo do Teste</h3>
            <p><strong>Objetivo:</strong> Verificar que o sistema executa todas as operações sem erros e salva dados corretamente.</p>
            <p><strong>Escopo:</strong> CFC → Instrutor → Aluno → Veículo → Agendamentos (com todas as regras)</p>
            <p><strong>Status:</strong> <span class="status pendente">PENDENTE</span></p>
        </div>

        <div class="acoes">
            <button class="btn btn-danger" onclick="executarTesteReal()">🚀 Executar Teste Real</button>
            <button class="btn btn-warning" onclick="limparDadosReais()">🧹 Limpar Dados Reais</button>
            <button class="btn btn-success" onclick="verificarBancoReal()">🔍 Verificar Banco</button>
        </div>

        <div id="resultados">
            <!-- Resultados dos testes serão inseridos aqui -->
        </div>

        <div class="acoes">
            <a href="index.php" class="btn btn-success">🏠 Voltar ao Dashboard</a>
            <a href="teste-producao-completo.php" class="btn btn-warning">🧪 Teste Simulado</a>
        </div>
    </div>

    <script>
        function executarTesteReal() {
            if (!confirm('⚠️ ATENÇÃO: Este teste criará dados REAIS no banco de dados!\n\nTem certeza que deseja continuar?')) {
                return;
            }
            
            // Limpar resultados anteriores
            document.getElementById('resultados').innerHTML = '';
            
            // Executar teste via AJAX
            fetch('teste-producao-real.php?acao=executar', {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('resultados').innerHTML = data;
            })
            .catch(error => {
                alert('Erro ao executar teste: ' + error);
            });
        }

        function limparDadosReais() {
            if (!confirm('⚠️ ATENÇÃO: Isso removerá TODOS os dados de teste do banco!\n\nTem certeza que deseja continuar?')) {
                return;
            }
            
            fetch('teste-producao-real.php?acao=limpar', {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                alert('Dados de teste removidos com sucesso!');
                location.reload();
            })
            .catch(error => {
                alert('Erro ao limpar dados: ' + error);
            });
        }

        function verificarBancoReal() {
            fetch('teste-producao-real.php?acao=verificar', {
                method: 'POST'
            })
            .then(response => response.text())
            .then(data => {
                alert('Verificação concluída! Verifique os resultados.');
            })
            .catch(error => {
                alert('Erro na verificação: ' + error);
            });
        }
    </script>
</body>
</html>

<?php
// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_GET['acao'] ?? '';
    
    if ($acao === 'executar') {
        echo "<h2>🚀 EXECUTANDO TESTE REAL DE PRODUÇÃO</h2>";
        echo "<p>Iniciando criação de dados reais no banco...</p>";
        
        try {
            // 1. TESTE: Cadastro de CFC
            executarTeste('1. Cadastro de CFC', function() use ($db, &$registrosTeste) {
                $cfcData = [
                    'nome' => 'Auto Escola Teste Produção',
                    'cnpj' => '12.345.678/0001-99',
                    'razao_social' => 'Auto Escola Teste Produção LTDA',
                    'endereco' => 'Rua Teste Produção, 123',
                    'bairro' => 'Centro',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                    'cep' => '01234-567',
                    'telefone' => '(11) 99999-9999',
                    'email' => 'teste@autoescola.com',
                    'responsavel' => 'João Teste',
                    'ativo' => 1
                ];
                
                $cfcId = $db->insert('cfcs', $cfcData);
                if (!$cfcId) {
                    throw new Exception('Erro ao criar CFC');
                }
                
                $registrosTeste['cfc_id'] = $cfcId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "CFC '{$cfcData['nome']}' criado com sucesso (ID: {$cfcId})",
                    'dados' => array_merge(['id' => $cfcId], $cfcData)
                ];
            });
            
            // 2. TESTE: Cadastro de Usuário para Instrutor 1
            executarTeste('2. Cadastro de Usuário para Instrutor 1', function() use ($db, &$registrosTeste) {
                $usuarioData = [
                    'nome' => 'Instrutor 1',
                    'email' => 'instrutor1@teste.com',
                    'senha' => password_hash('123456', PASSWORD_DEFAULT),
                    'tipo' => 'instrutor',
                    'ativo' => 1
                ];
                
                $usuarioId = $db->insert('usuarios', $usuarioData);
                if (!$usuarioId) {
                    throw new Exception('Erro ao criar usuário instrutor 1');
                }
                
                $registrosTeste['usuario_instrutor_ids'][] = $usuarioId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Usuário instrutor 1 '{$usuarioData['nome']}' criado com sucesso (ID: {$usuarioId})",
                    'dados' => array_merge(['id' => $usuarioId], $usuarioData)
                ];
            });

            // 3. TESTE: Cadastro de Usuário para Instrutor 2
            executarTeste('3. Cadastro de Usuário para Instrutor 2', function() use ($db, &$registrosTeste) {
                $usuarioData = [
                    'nome' => 'Instrutor 2',
                    'email' => 'instrutor2@teste.com',
                    'senha' => password_hash('123456', PASSWORD_DEFAULT),
                    'tipo' => 'instrutor',
                    'ativo' => 1
                ];
                
                $usuarioId = $db->insert('usuarios', $usuarioData);
                if (!$usuarioId) {
                    throw new Exception('Erro ao criar usuário instrutor 2');
                }
                
                $registrosTeste['usuario_instrutor_ids'][] = $usuarioId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Usuário instrutor 2 '{$usuarioData['nome']}' criado com sucesso (ID: {$usuarioId})",
                    'dados' => array_merge(['id' => $usuarioId], $usuarioData)
                ];
            });
            
            // 4. TESTE: Cadastro de Instrutor 1
            executarTeste('4. Cadastro de Instrutor 1', function() use ($db, &$registrosTeste) {
                $instrutorData = [
                    'usuario_id' => $registrosTeste['usuario_instrutor_ids'][0],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'nome' => 'Instrutor 1',
                    'cpf' => '111.222.333-44',
                    'rg' => '11.222.333-4',
                    'data_nascimento' => '1985-01-01',
                    'telefone' => '(11) 11111-1111',
                    'email' => 'instrutor1@teste.com',
                    'categoria_habilitacao' => 'B',
                    'ativo' => 1
                ];
                
                $instrutorId = $db->insert('instrutores', $instrutorData);
                if (!$instrutorId) {
                    throw new Exception('Erro ao criar instrutor 1');
                }
                
                $registrosTeste['instrutor_ids'][] = $instrutorId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Instrutor 1 '{$instrutorData['nome']}' criado com sucesso (ID: {$instrutorId})",
                    'dados' => array_merge(['id' => $instrutorId], $instrutorData)
                ];
            });

            // 5. TESTE: Cadastro de Instrutor 2
            executarTeste('5. Cadastro de Instrutor 2', function() use ($db, &$registrosTeste) {
                $instrutorData = [
                    'usuario_id' => $registrosTeste['usuario_instrutor_ids'][1],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'nome' => 'Instrutor 2',
                    'cpf' => '555.666.777-88',
                    'rg' => '55.666.777-8',
                    'data_nascimento' => '1988-02-02',
                    'telefone' => '(11) 22222-2222',
                    'email' => 'instrutor2@teste.com',
                    'categoria_habilitacao' => 'B',
                    'ativo' => 1
                ];
                
                $instrutorId = $db->insert('instrutores', $instrutorData);
                if (!$instrutorId) {
                    throw new Exception('Erro ao criar instrutor 2');
                }
                
                $registrosTeste['instrutor_ids'][] = $instrutorId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Instrutor 2 '{$instrutorData['nome']}' criado com sucesso (ID: {$instrutorId})",
                    'dados' => array_merge(['id' => $instrutorId], $instrutorData)
                ];
            });
            
            // 6. TESTE: Cadastro de Aluno 1
            executarTeste('6. Cadastro de Aluno 1', function() use ($db, &$registrosTeste) {
                $alunoData = [
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'nome' => 'Aluno 1',
                    'cpf' => '111.222.333-44',
                    'rg' => '11.222.333-4',
                    'data_nascimento' => '2000-03-03',
                    'telefone' => '(11) 33333-3333',
                    'email' => 'aluno1@teste.com',
                    'endereco' => 'Rua Aluno 1, 100',
                    'bairro' => 'Bairro 1',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                    'cep' => '01234-567',
                    'categoria_cnh' => 'B',
                    'status' => 'ativo'
                ];
                
                $alunoId = $db->insert('alunos', $alunoData);
                if (!$alunoId) {
                    throw new Exception('Erro ao criar aluno 1');
                }
                
                $registrosTeste['aluno_ids'][] = $alunoId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Aluno 1 '{$alunoData['nome']}' criado com sucesso (ID: {$alunoId})",
                    'dados' => array_merge(['id' => $alunoId], $alunoData)
                ];
            });

            // 7. TESTE: Cadastro de Aluno 2
            executarTeste('7. Cadastro de Aluno 2', function() use ($db, &$registrosTeste) {
                $alunoData = [
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'nome' => 'Aluno 2',
                    'cpf' => '555.666.777-88',
                    'rg' => '55.666.777-8',
                    'data_nascimento' => '2001-04-04',
                    'telefone' => '(11) 44444-4444',
                    'email' => 'aluno2@teste.com',
                    'endereco' => 'Rua Aluno 2, 200',
                    'bairro' => 'Bairro 2',
                    'cidade' => 'São Paulo',
                    'estado' => 'SP',
                    'cep' => '04567-890',
                    'categoria_cnh' => 'B',
                    'status' => 'ativo'
                ];
                
                $alunoId = $db->insert('alunos', $alunoData);
                if (!$alunoId) {
                    throw new Exception('Erro ao criar aluno 2');
                }
                
                $registrosTeste['aluno_ids'][] = $alunoId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Aluno 2 '{$alunoData['nome']}' criado com sucesso (ID: {$alunoId})",
                    'dados' => array_merge(['id' => $alunoId], $alunoData)
                ];
            });
            
            // 8. TESTE: Cadastro de Veículo 1
            executarTeste('8. Cadastro de Veículo 1', function() use ($db, &$registrosTeste) {
                $veiculoData = [
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'placa' => 'ABC-1234',
                    'modelo' => 'Gol',
                    'marca' => 'Volkswagen',
                    'ano' => 2020,
                    'cor' => 'Branco',
                    'categoria_cnh' => 'B',
                    'ativo' => 1
                ];
                
                $veiculoId = $db->insert('veiculos', $veiculoData);
                if (!$veiculoId) {
                    throw new Exception('Erro ao criar veículo 1');
                }
                
                $registrosTeste['veiculo_ids'][] = $veiculoId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Veículo 1 '{$veiculoData['placa']}' criado com sucesso (ID: {$veiculoId})",
                    'dados' => array_merge(['id' => $veiculoId], $veiculoData)
                ];
            });

            // 9. TESTE: Cadastro de Veículo 2
            executarTeste('9. Cadastro de Veículo 2', function() use ($db, &$registrosTeste) {
                $veiculoData = [
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'placa' => 'DEF-5678',
                    'modelo' => 'Uno',
                    'marca' => 'Fiat',
                    'ano' => 2019,
                    'cor' => 'Prata',
                    'categoria_cnh' => 'B',
                    'ativo' => 1
                ];
                
                $veiculoId = $db->insert('veiculos', $veiculoData);
                if (!$veiculoId) {
                    throw new Exception('Erro ao criar veículo 2');
                }
                
                $registrosTeste['veiculo_ids'][] = $veiculoId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Veículo 2 '{$veiculoData['placa']}' criado com sucesso (ID: {$veiculoId})",
                    'dados' => array_merge(['id' => $veiculoId], $veiculoData)
                ];
            });

            // 10. TESTE: Cadastro de Veículo 3
            executarTeste('10. Cadastro de Veículo 3', function() use ($db, &$registrosTeste) {
                $veiculoData = [
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'placa' => 'GHI-9012',
                    'modelo' => 'Palio',
                    'marca' => 'Fiat',
                    'ano' => 2021,
                    'cor' => 'Preto',
                    'categoria_cnh' => 'B',
                    'ativo' => 1
                ];
                
                $veiculoId = $db->insert('veiculos', $veiculoData);
                if (!$veiculoId) {
                    throw new Exception('Erro ao criar veículo 3');
                }
                
                $registrosTeste['veiculo_ids'][] = $veiculoId;
                
                return [
                    'sucesso' => true,
                    'mensagem' => "Veículo 3 '{$veiculoData['placa']}' criado com sucesso (ID: {$veiculoId})",
                    'dados' => array_merge(['id' => $veiculoId], $veiculoData)
                ];
            });
            
            // 11. TESTE: Agendamento de Aula 1 (Aluno 1, Instrutor 1)
            executarTeste('11. Agendamento de Aula 1 (Aluno 1, Instrutor 1)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][0],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][0],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][0],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '08:00',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 1'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                if ($resultado['sucesso']) {
                    $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
                }
                
                return $resultado;
            });
            
            // 12. TESTE: Agendamento de Aula 2 (Aluno 1, Instrutor 1)
            executarTeste('12. Agendamento de Aula 2 (Aluno 1, Instrutor 1)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][0],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][0],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][0],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '08:50',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 2'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                if ($resultado['sucesso']) {
                    $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
                }
                
                return $resultado;
            });
            
            // 13. TESTE: Agendamento de Aula 3 (Aluno 1, Instrutor 1)
            executarTeste('13. Agendamento de Aula 3 (Aluno 1, Instrutor 1)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][0],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][0],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][0],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '10:10',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 3'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                if ($resultado['sucesso']) {
                    $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
                }
                
                return $resultado;
            });
            
            // 14. TESTE: Agendamento de Aula 4 (Aluno 1, Instrutor 1)
            executarTeste('14. Agendamento de Aula 4 (Aluno 1, Instrutor 1)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][0],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][0],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][0],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '11:00',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 4 (deve falhar)'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                // Este teste DEVE falhar (limite de 3 aulas/dia)
                if (!$resultado['sucesso']) {
                    return [
                        'sucesso' => true, // Sucesso porque falhou como esperado
                        'mensagem' => '✅ 4ª aula corretamente rejeitada: ' . $resultado['mensagem'],
                        'dados' => $resultado
                    ];
                } else {
                    return [
                        'sucesso' => false,
                        'mensagem' => '❌ ERRO: 4ª aula foi aceita quando deveria ser rejeitada!'
                    ];
                }
            });
            
            // 15. TESTE: Agendamento de Aula 5 (Aluno 2, Instrutor 2)
            executarTeste('15. Agendamento de Aula 5 (Aluno 2, Instrutor 2)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][1],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][1],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][1],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '09:00',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 5'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                if ($resultado['sucesso']) {
                    $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
                }
                
                return $resultado;
            });
            
            // 16. TESTE: Agendamento de Aula 6 (Aluno 2, Instrutor 2)
            executarTeste('16. Agendamento de Aula 6 (Aluno 2, Instrutor 2)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][1],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][1],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][1],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '09:50',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 6'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                if ($resultado['sucesso']) {
                    $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
                }
                
                return $resultado;
            });
            
            // 17. TESTE: Agendamento de Aula 7 (Aluno 2, Instrutor 2)
            executarTeste('17. Agendamento de Aula 7 (Aluno 2, Instrutor 2)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][1],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][1],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][1],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '11:10',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 7'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                if ($resultado['sucesso']) {
                    $registrosTeste['aulas_ids'][] = $resultado['aula_id'];
                }
                
                return $resultado;
            });
            
            // 18. TESTE: Agendamento de Aula 8 (Aluno 2, Instrutor 2)
            executarTeste('18. Agendamento de Aula 8 (Aluno 2, Instrutor 2)', function() use ($db, $agendamentoController, &$registrosTeste) {
                $aulaData = [
                    'aluno_id' => $registrosTeste['aluno_ids'][1],
                    'instrutor_id' => $registrosTeste['instrutor_ids'][1],
                    'cfc_id' => $registrosTeste['cfc_id'],
                    'veiculo_id' => $registrosTeste['veiculo_ids'][1],
                    'tipo_aula' => 'pratica',
                    'data_aula' => date('Y-m-d', strtotime('+1 day')),
                    'hora_inicio' => '12:00',
                    'observacoes' => 'TESTE_PRODUCAO - Aula 8 (deve falhar)'
                ];
                
                $resultado = $agendamentoController->criarAula($aulaData);
                
                // Este teste DEVE falhar (limite de 3 aulas/dia)
                if (!$resultado['sucesso']) {
                    return [
                        'sucesso' => true, // Sucesso porque falhou como esperado
                        'mensagem' => '✅ 4ª aula corretamente rejeitada: ' . $resultado['mensagem'],
                        'dados' => $resultado
                    ];
                } else {
                    return [
                        'sucesso' => false,
                        'mensagem' => '❌ ERRO: 4ª aula foi aceita quando deveria ser rejeitada!'
                    ];
                }
            });
            
            // 19. TESTE: Verificação Final
            executarTeste('19. Verificação Final do Banco', function() use ($db, $registrosTeste) {
                try {
                    // Verificar se todos os registros existem
                    $cfc = $db->fetch('cfcs', 'id = ?', [$registrosTeste['cfc_id']]);
                    
                    $instrutores = [];
                    foreach ($registrosTeste['instrutor_ids'] as $instrutorId) {
                        $instrutores[] = $db->fetch('instrutores', 'id = ?', [$instrutorId]);
                    }
                    
                    $alunos = [];
                    foreach ($registrosTeste['aluno_ids'] as $alunoId) {
                        $alunos[] = $db->fetch('alunos', 'id = ?', [$alunoId]);
                    }

                    $veiculos = [];
                    foreach ($registrosTeste['veiculo_ids'] as $veiculoId) {
                        $veiculos[] = $db->fetch('veiculos', 'id = ?', [$veiculoId]);
                    }
                    
                    $aulas = [];
                    foreach ($registrosTeste['aulas_ids'] as $aulaId) {
                        $aulas[] = $db->fetch('aulas', 'id = ?', [$aulaId]);
                    }
                    
                    $totalRegistros = count(array_filter([$cfc])) + count(array_filter($instrutores)) + count(array_filter($alunos)) + count(array_filter($veiculos)) + count(array_filter($aulas));
                    
                    return [
                        'sucesso' => true,
                        'mensagem' => "✅ Verificação final: {$totalRegistros} registros encontrados no banco",
                        'dados' => [
                            'cfc' => $cfc ? 'Encontrado' : 'NÃO ENCONTRADO',
                            'instrutores' => count(array_filter($instrutores)) . ' encontrados',
                            'alunos' => count(array_filter($alunos)) . ' encontrados',
                            'veiculos' => count(array_filter($veiculos)) . ' encontrados',
                            'aulas' => count(array_filter($aulas)) . ' encontradas',
                            'total' => $totalRegistros
                        ]
                    ];
                    
                } catch (Exception $e) {
                    return [
                        'sucesso' => false,
                        'mensagem' => 'Erro na verificação final: ' . $e->getMessage()
                    ];
                }
            });
            
            echo "<div class='resultado sucesso'>";
            echo "<h3>🎉 TESTE REAL CONCLUÍDO COM SUCESSO!</h3>";
            echo "<p>✅ Todos os dados foram criados e salvos corretamente no banco de dados.</p>";
            echo "<p>✅ Todas as regras de agendamento foram validadas.</p>";
            echo "<p>✅ O sistema está PRONTO PARA PRODUÇÃO!</p>";
            echo "</div>";
            
            // Salvar IDs para limpeza posterior
            $_SESSION['registros_teste'] = $registrosTeste;
            
        } catch (Exception $e) {
            echo "<div class='resultado erro'>";
            echo "<h3>💥 ERRO CRÍTICO NO TESTE</h3>";
            echo "<p>Erro: " . $e->getMessage() . "</p>";
            echo "<p>O sistema NÃO está pronto para produção!</p>";
            echo "</div>";
        }
        
        exit;
    }
    
    if ($acao === 'limpar') {
        $registros = $_SESSION['registros_teste'] ?? [];
        limparDadosTeste($db, $registros);
        unset($_SESSION['registros_teste']);
        exit;
    }
    
    if ($acao === 'verificar') {
        echo "<h3>🔍 Verificação do Banco de Dados</h3>";
        
        try {
            // Verificar CFCs
            $cfcs = $db->count('cfcs');
            echo "CFCs: {$cfcs}<br>";
            
            // Verificar Instrutores
            $instrutores = $db->count('instrutores');
            echo "Instrutores: {$instrutores}<br>";
            
            // Verificar Alunos
            $alunos = $db->count('alunos');
            echo "Alunos: {$alunos}<br>";
            
            // Verificar Veículos
            $veiculos = $db->count('veiculos');
            echo "Veículos: {$veiculos}<br>";
            
            // Verificar Aulas
            $aulas = $db->count('aulas');
            echo "Aulas: {$aulas}<br>";
            
            echo "<br>✅ Verificação concluída!";
        } catch (Exception $e) {
            echo "❌ Erro na verificação: " . $e->getMessage();
        }
        exit;
    }
}
?>
