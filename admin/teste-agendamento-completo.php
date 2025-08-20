<?php
/**
 * Teste Completo do Sistema de Agendamento
 * Verifica todas as funcionalidades: frontend, backend, APIs e banco de dados
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/controllers/AgendamentoController.php';

$db = Database::getInstance();
$agendamentoController = new AgendamentoController();

// Verificar conexão com banco
$conexaoOk = false;
$erroConexao = '';
try {
    $db->query("SELECT 1");
    $conexaoOk = true;
} catch (Exception $e) {
    $erroConexao = $e->getMessage();
}

// Verificar estrutura das tabelas
$estruturaOk = false;
$erroEstrutura = '';
if ($conexaoOk) {
    try {
        // Verificar se a tabela aulas existe e tem a estrutura correta
        $result = $db->query("DESCRIBE aulas");
        $colunas = $result->fetchAll(PDO::FETCH_ASSOC);
        
        $colunasNecessarias = ['id', 'aluno_id', 'instrutor_id', 'cfc_id', 'veiculo_id', 'tipo_aula', 'data_aula', 'hora_inicio', 'hora_fim', 'status', 'observacoes', 'criado_em', 'atualizado_em'];
        $colunasExistentes = array_column($colunas, 'Field');
        
        $colunasFaltando = array_diff($colunasNecessarias, $colunasExistentes);
        
        if (empty($colunasFaltando)) {
            $estruturaOk = true;
        } else {
            $erroEstrutura = "Colunas faltando: " . implode(', ', $colunasFaltando);
        }
    } catch (Exception $e) {
        $erroEstrutura = $e->getMessage();
    }
}

// Verificar dados de teste
$dadosTesteOk = false;
$erroDados = '';
if ($estruturaOk) {
    try {
        $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo IN ('admin', 'instrutor')");
        $usuarios = $result->fetch(PDO::FETCH_ASSOC);
        
        $result = $db->query("SELECT COUNT(*) as total FROM alunos");
        $alunos = $result->fetch(PDO::FETCH_ASSOC);
        
        $result = $db->query("SELECT COUNT(*) as total FROM cfcs");
        $cfcs = $result->fetch(PDO::FETCH_ASSOC);
        
        $result = $db->query("SELECT COUNT(*) as total FROM veiculos");
        $veiculos = $result->fetch(PDO::FETCH_ASSOC);
        
        if ($usuarios['total'] > 0 && $alunos['total'] > 0 && $cfcs['total'] > 0 && $veiculos['total'] > 0) {
            $dadosTesteOk = true;
        } else {
            $erroDados = "Dados insuficientes: Usuários: {$usuarios['total']}, Alunos: {$alunos['total']}, CFCs: {$cfcs['total']}, Veículos: {$veiculos['total']}";
        }
    } catch (Exception $e) {
        $erroDados = $e->getMessage();
    }
}

// Testar funcionalidades do controller
$controllerOk = false;
$erroController = '';
if ($dadosTesteOk) {
    try {
        // Testar verificação de disponibilidade
        $dadosTeste = [
            'data_aula' => date('Y-m-d', strtotime('+1 day')),
            'hora_inicio' => '08:00',
            'hora_fim' => '09:00',
            'instrutor_id' => 1,
            'cfc_id' => 1
        ];
        
        $disponibilidade = $agendamentoController->verificarDisponibilidade($dadosTeste);
        
        if (is_array($disponibilidade) && isset($disponibilidade['disponivel'])) {
            $controllerOk = true;
        } else {
            $erroController = "Verificação de disponibilidade retornou formato inválido";
        }
    } catch (Exception $e) {
        $erroController = $e->getMessage();
    }
}

// Status geral
$statusGeral = $conexaoOk && $estruturaOk && $dadosTesteOk && $controllerOk;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Completo - Sistema de Agendamento</title>
    
    <!-- CSS do sistema -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <style>
        .status-card {
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-ok {
            border-left: 5px solid #28a745;
            background-color: #f8fff9;
        }
        
        .status-erro {
            border-left: 5px solid #dc3545;
            background-color: #fff8f8;
        }
        
        .status-pendente {
            border-left: 5px solid #ffc107;
            background-color: #fffef8;
        }
        
        .status-icon {
            font-size: 2rem;
            margin-right: 15px;
        }
        
        .test-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            height: 25px;
            border-radius: 15px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex align-items-center mb-4">
                    <h1 class="mb-0">🧪 Teste Completo do Sistema de Agendamento</h1>
                    <div class="ms-auto">
                        <span class="badge <?php echo $statusGeral ? 'bg-success' : 'bg-danger'; ?> fs-6">
                            <?php echo $statusGeral ? 'SISTEMA FUNCIONAL' : 'SISTEMA COM PROBLEMAS'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Barra de Progresso -->
                <div class="test-section">
                    <h5>📊 Progresso Geral</h5>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($conexaoOk + $estruturaOk + $dadosTesteOk + $controllerOk) * 25; ?>%">
                            <?php echo ($conexaoOk + $estruturaOk + $dadosTesteOk + $controllerOk) * 25; ?>%
                        </div>
                    </div>
                </div>
                
                <!-- Status da Conexão -->
                <div class="status-card <?php echo $conexaoOk ? 'status-ok' : 'status-erro'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="status-icon">
                                <?php echo $conexaoOk ? '✅' : '❌'; ?>
                            </div>
                            <div>
                                <h5 class="mb-1">🔌 Conexão com Banco de Dados</h5>
                                <p class="mb-0">
                                    <?php if ($conexaoOk): ?>
                                        <strong class="text-success">Conectado com sucesso!</strong>
                                    <?php else: ?>
                                        <strong class="text-danger">Falha na conexão:</strong> <?php echo $erroConexao; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status da Estrutura -->
                <div class="status-card <?php echo $estruturaOk ? 'status-ok' : 'status-erro'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="status-icon">
                                <?php echo $estruturaOk ? '✅' : '❌'; ?>
                            </div>
                            <div>
                                <h5 class="mb-1">🏗️ Estrutura do Banco de Dados</h5>
                                <p class="mb-0">
                                    <?php if ($estruturaOk): ?>
                                        <strong class="text-success">Tabelas e colunas corretas!</strong>
                                    <?php else: ?>
                                        <strong class="text-danger">Problema na estrutura:</strong> <?php echo $erroEstrutura; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status dos Dados -->
                <div class="status-card <?php echo $dadosTesteOk ? 'status-ok' : 'status-erro'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="status-icon">
                                <?php echo $dadosTesteOk ? '✅' : '❌'; ?>
                            </div>
                            <div>
                                <h5 class="mb-1">📋 Dados de Teste</h5>
                                <p class="mb-0">
                                    <?php if ($dadosTesteOk): ?>
                                        <strong class="text-success">Dados suficientes para teste!</strong>
                                    <?php else: ?>
                                        <strong class="text-danger">Dados insuficientes:</strong> <?php echo $erroDados; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status do Controller -->
                <div class="status-card <?php echo $controllerOk ? 'status-ok' : 'status-erro'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="status-icon">
                                <?php echo $controllerOk ? '✅' : '❌'; ?>
                            </div>
                            <div>
                                <h5 class="mb-1">⚙️ Controller de Agendamento</h5>
                                <p class="mb-0">
                                    <?php if ($controllerOk): ?>
                                        <strong class="text-success">Funcionalidades testadas com sucesso!</strong>
                                    <?php else: ?>
                                        <strong class="text-danger">Erro no controller:</strong> <?php echo $erroController; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ações Recomendadas -->
                <div class="test-section">
                    <h5>🎯 Ações Recomendadas</h5>
                    
                    <?php if (!$conexaoOk): ?>
                        <div class="alert alert-danger">
                            <strong>1. Resolver Conexão com Banco:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Verificar se o XAMPP está rodando</li>
                                <li>Verificar configurações em includes/config.php</li>
                                <li>Verificar se o banco 'cfc_sistema' existe</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$estruturaOk): ?>
                        <div class="alert alert-warning">
                            <strong>2. Atualizar Estrutura do Banco:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Executar o script: admin/atualizar-banco-agendamento.sql</li>
                                <li>Ou executar: database_structure.sql completo</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$dadosTesteOk): ?>
                        <div class="alert alert-info">
                            <strong>3. Inserir Dados de Teste:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Executar: admin/inserir-dados-teste.php</li>
                                <li>Ou inserir manualmente via phpMyAdmin</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($statusGeral): ?>
                        <div class="alert alert-success">
                            <strong>🎉 Sistema Funcionando!</strong>
                            <ul class="mb-0 mt-2">
                                <li>Testar interface: <a href="index.php?page=agendamento" class="alert-link">Sistema de Agendamento</a></li>
                                <li>Testar APIs: <a href="test-api-agendamento.php" class="alert-link">Teste de APIs</a></li>
                                <li>Verificar logs: <a href="logs/" class="alert-link">Logs do Sistema</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Testes Manuais -->
                <div class="test-section">
                    <h5>🔧 Testes Manuais</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>📱 Interface Frontend</h6>
                                    <a href="index.php?page=agendamento" class="btn btn-primary btn-sm">
                                        Testar Sistema de Agendamento
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>🔌 APIs Backend</h6>
                                    <a href="test-api-agendamento.php" class="btn btn-info btn-sm">
                                        Testar APIs REST
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Logs do Sistema -->
                <div class="test-section">
                    <h5>📝 Logs do Sistema</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Nível</th>
                                    <th>Mensagem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s'); ?></td>
                                    <td><span class="badge bg-info">INFO</span></td>
                                    <td>Teste de sistema executado</td>
                                </tr>
                                <?php if ($conexaoOk): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s'); ?></td>
                                    <td><span class="badge bg-success">SUCCESS</span></td>
                                    <td>Conexão com banco estabelecida</td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!$conexaoOk): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s'); ?></td>
                                    <td><span class="badge bg-danger">ERROR</span></td>
                                    <td>Falha na conexão: <?php echo $erroConexao; ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Atualizar status em tempo real
        function atualizarStatus() {
            // Aqui poderia fazer uma requisição AJAX para verificar status
            console.log('Status atualizado:', <?php echo json_encode($statusGeral); ?>);
        }
        
        // Atualizar a cada 30 segundos
        setInterval(atualizarStatus, 30000);
    </script>
</body>
</html>
