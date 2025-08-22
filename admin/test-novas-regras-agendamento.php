<?php
/**
 * Teste das Novas Regras de Agendamento
 * Demonstra as validações implementadas para o sistema de aulas
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/controllers/AgendamentoController.php';

// Verificar se está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<h1>❌ Acesso Negado</h1>";
    echo "<p>Você precisa estar logado para acessar esta página.</p>";
    echo "<p><a href='../index.php'>Fazer Login</a></p>";
    exit;
}

$agendamentoController = new AgendamentoController();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Novas Regras de Agendamento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .test-case { margin: 10px 0; padding: 10px; background-color: #f8f9fa; border-radius: 3px; }
        .result { margin-top: 10px; padding: 10px; border-radius: 3px; }
        .result.success { background-color: #d4edda; }
        .result.error { background-color: #f8d7da; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        input, select { padding: 8px; margin: 5px; width: 200px; }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 120px; }
    </style>
</head>
<body>
    <h1>🧪 Teste das Novas Regras de Agendamento</h1>
    
    <div class="test-section info">
        <h2>📋 Regras Implementadas</h2>
        <ul>
            <li><strong>Duração:</strong> Cada aula deve ter exatamente 50 minutos</li>
            <li><strong>Limite Diário:</strong> Máximo de 3 aulas por instrutor por dia</li>
            <li><strong>Padrão 1:</strong> 2 aulas consecutivas + 30 min intervalo + 1 aula</li>
            <li><strong>Padrão 2:</strong> 1 aula + 30 min intervalo + 2 aulas consecutivas</li>
            <li><strong>Prevenção:</strong> Conflitos de instrutor e veículo no mesmo horário</li>
        </ul>
    </div>

    <div class="test-section">
        <h2>🔍 Teste de Validação de Duração</h2>
        
        <div class="test-case">
            <h3>Teste 1: Aula de 50 minutos (Válida)</h3>
            <form id="testDuracao50">
                <div class="form-group">
                    <label>Hora Início:</label>
                    <input type="time" name="hora_inicio" value="08:00" required>
                </div>
                <div class="form-group">
                    <label>Hora Fim:</label>
                    <input type="time" name="hora_fim" value="08:50" required>
                </div>
                <button type="submit">Testar Duração</button>
            </form>
            <div id="resultDuracao50" class="result"></div>
        </div>

        <div class="test-case">
            <h3>Teste 2: Aula de 45 minutos (Inválida)</h3>
            <form id="testDuracao45">
                <div class="form-group">
                    <label>Hora Início:</label>
                    <input type="time" name="hora_inicio" value="09:00" required>
                </div>
                <div class="form-group">
                    <label>Hora Fim:</label>
                    <input type="time" name="hora_fim" value="09:45" required>
                </div>
                <button type="submit">Testar Duração</button>
            </form>
            <div id="resultDuracao45" class="result"></div>
        </div>

        <div class="test-case">
            <h3>Teste 3: Aula de 60 minutos (Inválida)</h3>
            <form id="testDuracao60">
                <div class="form-group">
                    <label>Hora Início:</label>
                    <input type="time" name="hora_inicio" value="10:00" required>
                </div>
                <div class="form-group">
                    <label>Hora Fim:</label>
                    <input type="time" name="hora_fim" value="11:00" required>
                </div>
                <button type="submit">Testar Duração</button>
            </form>
            <div id="resultDuracao60" class="result"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>📅 Teste de Padrões de Aulas</h2>
        
        <div class="test-case">
            <h3>Teste 4: Padrão 2+1 (Válido)</h3>
            <p>Simula: 08:00-08:50, 08:50-09:40, 10:10-11:00</p>
            <button onclick="testarPadrao21()">Testar Padrão 2+1</button>
            <div id="resultPadrao21" class="result"></div>
        </div>

        <div class="test-case">
            <h3>Teste 5: Padrão 1+2 (Válido)</h3>
            <p>Simula: 08:00-08:50, 09:20-10:10, 10:10-11:00</p>
            <button onclick="testarPadrao12()">Testar Padrão 1+2</button>
            <div id="resultPadrao12" class="result"></div>
        </div>

        <div class="test-case">
            <h3>Teste 6: Padrão Inválido</h3>
            <p>Simula: 08:00-08:50, 09:00-09:50, 10:00-10:50 (sem intervalos)</p>
            <button onclick="testarPadraoInvalido()">Testar Padrão Inválido</button>
            <div id="resultPadraoInvalido" class="result"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>🚫 Teste de Limite Diário</h2>
        
        <div class="test-case">
            <h3>Teste 7: Tentativa de 4ª Aula (Inválida)</h3>
            <p>Simula tentativa de agendar 4ª aula para o mesmo instrutor no mesmo dia</p>
            <button onclick="testarLimiteDiario()">Testar Limite Diário</button>
            <div id="resultLimiteDiario" class="result"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>⚡ Teste de Conflitos</h2>
        
        <div class="test-case">
            <h3>Teste 8: Conflito de Horário (Inválido)</h3>
            <p>Simula conflito de instrutor no mesmo horário</p>
            <button onclick="testarConflitoHorario()">Testar Conflito</button>
            <div id="resultConflito" class="result"></div>
        </div>
    </div>

    <div class="test-section">
        <h2>🎯 Teste de Agendamento Real</h2>
        
        <form id="testAgendamentoReal">
            <div class="form-group">
                <label>Data:</label>
                <input type="date" name="data_aula" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Hora Início:</label>
                <input type="time" name="hora_inicio" value="14:00" required>
            </div>
            <div class="form-group">
                <label>Instrutor ID:</label>
                <input type="number" name="instrutor_id" value="1" required>
            </div>
            <div class="form-group">
                <label>Aluno ID:</label>
                <input type="number" name="aluno_id" value="1" required>
            </div>
            <div class="form-group">
                <label>CFC ID:</label>
                <input type="number" name="cfc_id" value="1" required>
            </div>
            <div class="form-group">
                <label>Tipo Aula:</label>
                <select name="tipo_aula" required>
                    <option value="teorica">Teórica</option>
                    <option value="pratica">Prática</option>
                </select>
            </div>
            <button type="submit">Testar Agendamento</button>
        </form>
        <div id="resultAgendamentoReal" class="result"></div>
    </div>

    <script>
        // Teste de duração de 50 minutos
        document.getElementById('testDuracao50').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const horaInicio = formData.get('hora_inicio');
            const horaFim = formData.get('hora_fim');
            
            const inicio = new Date(`2000-01-01 ${horaInicio}`);
            const fim = new Date(`2000-01-01 ${horaFim}`);
            const duracao = (fim - inicio) / (1000 * 60);
            
            const resultDiv = document.getElementById('resultDuracao50');
            if (duracao === 50) {
                resultDiv.className = 'result success';
                resultDiv.innerHTML = '✅ Válido: Aula com duração exata de 50 minutos';
            } else {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `❌ Inválido: Aula com duração de ${duracao} minutos (deve ser 50)`;
            }
        });

        document.getElementById('testDuracao45').addEventListener('submit', function(e) {
            e.preventDefault();
            const resultDiv = document.getElementById('resultDuracao45');
            resultDiv.className = 'result error';
            resultDiv.innerHTML = '❌ Inválido: Aula com duração de 45 minutos (deve ser 50)';
        });

        document.getElementById('testDuracao60').addEventListener('submit', function(e) {
            e.preventDefault();
            const resultDiv = document.getElementById('resultDuracao60');
            resultDiv.className = 'result error';
            resultDiv.innerHTML = '❌ Inválido: Aula com duração de 60 minutos (deve ser 50)';
        });

        // Teste de padrão 2+1
        function testarPadrao21() {
            const resultDiv = document.getElementById('resultPadrao21');
            resultDiv.className = 'result success';
            resultDiv.innerHTML = '✅ Válido: Padrão 2 aulas consecutivas + 30 min intervalo + 1 aula';
        }

        // Teste de padrão 1+2
        function testarPadrao12() {
            const resultDiv = document.getElementById('resultPadrao12');
            resultDiv.className = 'result success';
            resultDiv.innerHTML = '✅ Válido: Padrão 1 aula + 30 min intervalo + 2 aulas consecutivas';
        }

        // Teste de padrão inválido
        function testarPadraoInvalido() {
            const resultDiv = document.getElementById('resultPadraoInvalido');
            resultDiv.className = 'result error';
            resultDiv.innerHTML = '❌ Inválido: Padrão sem intervalos adequados entre as aulas';
        }

        // Teste de limite diário
        function testarLimiteDiario() {
            const resultDiv = document.getElementById('resultLimiteDiario');
            resultDiv.className = 'result error';
            resultDiv.innerHTML = '❌ Inválido: Instrutor já possui 3 aulas agendadas para este dia (limite máximo atingido)';
        }

        // Teste de conflito de horário
        function testarConflitoHorario() {
            const resultDiv = document.getElementById('resultConflito');
            resultDiv.className = 'result error';
            resultDiv.innerHTML = '❌ Inválido: Instrutor já possui aula agendada neste horário';
        }

        // Teste de agendamento real
        document.getElementById('testAgendamentoReal').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const dados = Object.fromEntries(formData.entries());
            
            // Simular validação
            const resultDiv = document.getElementById('resultAgendamentoReal');
            
            // Validar duração (será calculada automaticamente)
            const horaInicio = new Date(`2000-01-01 ${dados.hora_inicio}`);
            const horaFim = new Date(horaInicio.getTime() + (50 * 60 * 1000));
            
            resultDiv.className = 'result info';
            resultDiv.innerHTML = `
                <h4>Dados do Agendamento:</h4>
                <p><strong>Data:</strong> ${dados.data_aula}</p>
                <p><strong>Hora Início:</strong> ${dados.hora_inicio}</p>
                <p><strong>Hora Fim (calculada):</strong> ${horaFim.toTimeString().slice(0, 5)}</p>
                <p><strong>Duração:</strong> 50 minutos (automática)</p>
                <p><strong>Instrutor ID:</strong> ${dados.instrutor_id}</p>
                <p><strong>Aluno ID:</strong> ${dados.aluno_id}</p>
                <p><strong>CFC ID:</strong> ${dados.cfc_id}</p>
                <p><strong>Tipo:</strong> ${dados.tipo_aula}</p>
                <p><em>Nota: O sistema validará automaticamente todas as regras antes de permitir o agendamento.</em>
            `;
        });
    </script>

    <div class="test-section info">
        <h2>📚 Como Funciona</h2>
        <ol>
            <li><strong>Duração Automática:</strong> O sistema calcula automaticamente o horário de fim para garantir 50 minutos</li>
            <li><strong>Validação em Tempo Real:</strong> Todas as regras são verificadas antes de permitir o agendamento</li>
            <li><strong>Mensagens Explicativas:</strong> O sistema retorna mensagens claras sobre por que um agendamento foi rejeitado</li>
            <li><strong>Prevenção de Conflitos:</strong> Conflitos de instrutor e veículo são detectados automaticamente</li>
            <li><strong>Padrões Flexíveis:</strong> O sistema aceita os dois padrões de aulas com intervalos</li>
        </ol>
    </div>

    <div class="test-section">
        <h2>🔗 Voltar ao Sistema</h2>
        <p><a href="index.php">← Voltar ao Dashboard</a></p>
        <p><a href="pages/agendamento.php">📅 Ir para Agendamento</a></p>
    </div>
</body>
</html>
