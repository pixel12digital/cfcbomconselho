<?php
// =====================================================
// TESTE DAS APIS DE AGENDAMENTO - SISTEMA CFC
// =====================================================

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Verificar autenticação
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste APIs - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .api-response {
            background: #f1f3f4;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.9em;
            max-height: 300px;
            overflow-y: auto;
        }
        .success { color: #198754; }
        .error { color: #dc3545; }
        .info { color: #0dcaf0; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-vial me-2"></i>Teste das APIs de Agendamento
                </h1>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta página testa as funcionalidades das APIs de agendamento implementadas.
                </div>
            </div>
        </div>

        <!-- Teste da API de Verificação de Disponibilidade -->
        <div class="test-section">
            <h3><i class="fas fa-search me-2"></i>Teste: Verificar Disponibilidade</h3>
            <p class="text-muted">Testa a API que verifica se um horário está disponível</p>
            
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Data da Aula</label>
                    <input type="date" id="testData" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hora de Início</label>
                    <input type="time" id="testHora" class="form-control" value="08:00">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Instrutor ID</label>
                    <input type="number" id="testInstrutor" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Aula</label>
                    <select id="testTipo" class="form-select">
                        <option value="teorica">Teórica</option>
                        <option value="pratica">Prática</option>
                        <option value="simulador">Simulador</option>
                    </select>
                </div>
            </div>
            
            <button class="btn btn-primary mt-3" onclick="testarDisponibilidade()">
                <i class="fas fa-play me-1"></i>Testar Disponibilidade
            </button>
            
            <div id="resultadoDisponibilidade" class="api-response" style="display: none;"></div>
        </div>

        <!-- Teste da API de Agendamento -->
        <div class="test-section">
            <h3><i class="fas fa-calendar-plus me-2"></i>Teste: Agendar Aula</h3>
            <p class="text-muted">Testa a API que agenda uma nova aula</p>
            
            <div class="row">
                <div class="col-md-2">
                    <label class="form-label">Aluno ID</label>
                    <input type="number" id="testAlunoId" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data</label>
                    <input type="date" id="testAgendData" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hora</label>
                    <input type="time" id="testAgendHora" class="form-control" value="08:00">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Instrutor ID</label>
                    <input type="number" id="testAgendInstrutor" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select id="testAgendTipo" class="form-select">
                        <option value="teorica">Teórica</option>
                        <option value="pratica">Prática</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Veículo ID</label>
                    <input type="number" id="testAgendVeiculo" class="form-control" value="" placeholder="Opcional">
                </div>
            </div>
            
            <button class="btn btn-success mt-3" onclick="testarAgendamento()">
                <i class="fas fa-save me-1"></i>Testar Agendamento
            </button>
            
            <div id="resultadoAgendamento" class="api-response" style="display: none;"></div>
        </div>

        <!-- Status das APIs -->
        <div class="test-section">
            <h3><i class="fas fa-server me-2"></i>Status das APIs</h3>
            <p class="text-muted">Verifica se as APIs estão acessíveis</p>
            
            <div class="row">
                <div class="col-md-6">
                    <button class="btn btn-outline-info w-100" onclick="verificarStatusAPI('verificar-disponibilidade.php')">
                        <i class="fas fa-search me-1"></i>Status: Verificar Disponibilidade
                    </button>
                    <div id="statusDisponibilidade" class="mt-2"></div>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-outline-info w-100" onclick="verificarStatusAPI('agendamento.php')">
                        <i class="fas fa-calendar me-1"></i>Status: Agendamento
                    </button>
                    <div id="statusAgendamento" class="mt-2"></div>
                </div>
            </div>
        </div>

        <!-- Voltar -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Voltar ao Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testarDisponibilidade() {
            const data = document.getElementById('testData').value;
            const hora = document.getElementById('testHora').value;
            const instrutor = document.getElementById('testInstrutor').value;
            const tipo = document.getElementById('testTipo').value;
            
            if (!data || !hora || !instrutor) {
                alert('Preencha todos os campos obrigatórios');
                return;
            }
            
            const formData = new FormData();
            formData.append('data_aula', data);
            formData.append('hora_inicio', hora);
            formData.append('duracao', '50');
            formData.append('instrutor_id', instrutor);
            formData.append('tipo_aula', tipo);
            
            fetch('api/verificar-disponibilidade.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultado = document.getElementById('resultadoDisponibilidade');
                resultado.style.display = 'block';
                resultado.innerHTML = `
                    <h6 class="text-${data.sucesso ? 'success' : 'danger'}">
                        <i class="fas fa-${data.sucesso ? 'check' : 'times'} me-2"></i>
                        ${data.sucesso ? 'API Funcionando' : 'Erro na API'}
                    </h6>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            })
            .catch(error => {
                const resultado = document.getElementById('resultadoDisponibilidade');
                resultado.style.display = 'block';
                resultado.innerHTML = `
                    <h6 class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro na Requisição
                    </h6>
                    <pre>${error.message}</pre>
                `;
            });
        }
        
        function testarAgendamento() {
            const alunoId = document.getElementById('testAlunoId').value;
            const data = document.getElementById('testAgendData').value;
            const hora = document.getElementById('testAgendHora').value;
            const instrutor = document.getElementById('testAgendInstrutor').value;
            const tipo = document.getElementById('testAgendTipo').value;
            const veiculo = document.getElementById('testAgendVeiculo').value;
            
            if (!alunoId || !data || !hora || !instrutor) {
                alert('Preencha todos os campos obrigatórios');
                return;
            }
            
            const formData = new FormData();
            formData.append('aluno_id', alunoId);
            formData.append('data_aula', data);
            formData.append('hora_inicio', hora);
            formData.append('duracao', '50');
            formData.append('tipo_aula', tipo);
            formData.append('instrutor_id', instrutor);
            if (veiculo) formData.append('veiculo_id', veiculo);
            formData.append('observacoes', 'Teste via API');
            
            fetch('api/agendamento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultado = document.getElementById('resultadoAgendamento');
                resultado.style.display = 'block';
                resultado.innerHTML = `
                    <h6 class="text-${data.sucesso ? 'success' : 'danger'}">
                        <i class="fas fa-${data.sucesso ? 'check' : 'times'} me-2"></i>
                        ${data.sucesso ? 'Aula Agendada com Sucesso!' : 'Erro ao Agendar'}
                    </h6>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            })
            .catch(error => {
                const resultado = document.getElementById('resultadoAgendamento');
                resultado.style.display = 'block';
                resultado.innerHTML = `
                    <h6 class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro na Requisição
                    </h6>
                    <pre>${error.message}</pre>
                `;
            });
        }
        
        function verificarStatusAPI(api) {
            const statusDiv = api === 'verificar-disponibilidade.php' ? 
                document.getElementById('statusDisponibilidade') : 
                document.getElementById('statusAgendamento');
            
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin text-info"></i> Verificando...';
            
            fetch(`api/${api}`, {
                method: 'OPTIONS'
            })
            .then(response => {
                if (response.ok) {
                    statusDiv.innerHTML = '<i class="fas fa-check-circle text-success"></i> API Online';
                } else {
                    statusDiv.innerHTML = '<i class="fas fa-times-circle text-danger"></i> API Offline';
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Erro de Conexão';
            });
        }
        
        // Verificar status das APIs ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            verificarStatusAPI('verificar-disponibilidade.php');
            verificarStatusAPI('agendamento.php');
        });
    </script>
</body>
</html>
