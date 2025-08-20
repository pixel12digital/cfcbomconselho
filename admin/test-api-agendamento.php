<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste API Agendamento - Sistema CFC</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        .test-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: monospace;
        }
        .test-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .test-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .test-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .btn-test {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn-test:hover { background: #0056b3; }
        .btn-test:disabled { background: #6c757d; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>🧪 Teste das APIs de Agendamento</h1>
            <p>Verificação das funcionalidades de backend do sistema de agendamento</p>
        </header>

        <main class="admin-main">
            <!-- Status das APIs -->
            <div class="test-section">
                <h2>📊 Status das APIs</h2>
                <div id="api-status" class="test-info">
                    Verificando status das APIs...
                </div>
                <button class="btn-test" onclick="verificarStatusAPIs()">🔄 Verificar Status</button>
            </div>

            <!-- Teste de Criação de Aula -->
            <div class="test-section">
                <h2>➕ Teste de Criação de Aula</h2>
                <form id="form-nova-aula">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label>Aluno ID:</label>
                            <input type="number" name="aluno_id" value="1" required>
                        </div>
                        <div>
                            <label>Instrutor ID:</label>
                            <input type="number" name="instrutor_id" value="1" required>
                        </div>
                        <div>
                            <label>CFC ID:</label>
                            <input type="number" name="cfc_id" value="1" required>
                        </div>
                        <div>
                            <label>Tipo de Aula:</label>
                            <select name="tipo_aula" required>
                                <option value="teorica">Teórica</option>
                                <option value="pratica">Prática</option>
                            </select>
                        </div>
                        <div>
                            <label>Data da Aula:</label>
                            <input type="date" name="data_aula" required>
                        </div>
                        <div>
                            <label>Hora Início:</label>
                            <input type="time" name="hora_inicio" value="08:00" required>
                        </div>
                        <div>
                            <label>Hora Fim:</label>
                            <input type="time" name="hora_fim" value="09:00" required>
                        </div>
                        <div>
                            <label>Observações:</label>
                            <input type="text" name="observacoes" value="Aula de teste">
                        </div>
                    </div>
                    <button type="submit" class="btn-test">🚀 Criar Aula</button>
                </form>
                <div id="resultado-criacao"></div>
            </div>

            <!-- Teste de Listagem de Aulas -->
            <div class="test-section">
                <h2>📋 Teste de Listagem de Aulas</h2>
                <button class="btn-test" onclick="listarAulas()">📋 Listar Aulas</button>
                <button class="btn-test" onclick="listarAulasComFiltros()">🔍 Listar com Filtros</button>
                <div id="resultado-listagem"></div>
            </div>

            <!-- Teste de Verificação de Disponibilidade -->
            <div class="test-section">
                <h2>✅ Teste de Verificação de Disponibilidade</h2>
                <form id="form-disponibilidade">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label>Data:</label>
                            <input type="date" name="data_aula" required>
                        </div>
                        <div>
                            <label>Hora Início:</label>
                            <input type="time" name="hora_inicio" value="10:00" required>
                        </div>
                        <div>
                            <label>Hora Fim:</label>
                            <input type="time" name="hora_fim" value="11:00" required>
                        </div>
                        <div>
                            <label>Instrutor ID:</label>
                            <input type="number" name="instrutor_id" value="1" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-test">🔍 Verificar Disponibilidade</button>
                </form>
                <div id="resultado-disponibilidade"></div>
            </div>

            <!-- Teste de Estatísticas -->
            <div class="test-section">
                <h2>📈 Teste de Estatísticas</h2>
                <button class="btn-test" onclick="obterEstatisticas()">📊 Obter Estatísticas</button>
                <button class="btn-test" onclick="obterEstatisticasComFiltros()">🔍 Estatísticas com Filtros</button>
                <div id="resultado-estatisticas"></div>
            </div>

            <!-- Logs de Teste -->
            <div class="test-section">
                <h2>📝 Logs de Teste</h2>
                <button class="btn-test" onclick="limparLogs()">🗑️ Limpar Logs</button>
                <div id="logs-teste" style="max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px;"></div>
            </div>
        </main>
    </div>

    <script>
        // Configurar data atual para os campos de data
        document.addEventListener('DOMContentLoaded', function() {
            const hoje = new Date();
            const amanha = new Date(hoje);
            amanha.setDate(amanha.getDate() + 1);
            
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.value = amanha.toISOString().split('T')[0];
            });
            
            verificarStatusAPIs();
        });

        // Função para adicionar logs
        function adicionarLog(mensagem, tipo = 'info') {
            const logsDiv = document.getElementById('logs-teste');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `test-result test-${tipo}`;
            logEntry.innerHTML = `[${timestamp}] ${mensagem}`;
            logsDiv.appendChild(logEntry);
            logsDiv.scrollTop = logsDiv.scrollHeight;
        }

        // Verificar status das APIs
        async function verificarStatusAPIs() {
            const statusDiv = document.getElementById('api-status');
            statusDiv.innerHTML = 'Verificando...';
            statusDiv.className = 'test-result test-info';

            try {
                const response = await fetch('api/agendamento.php/aulas');
                if (response.ok) {
                    statusDiv.innerHTML = '✅ APIs funcionando corretamente';
                    statusDiv.className = 'test-result test-success';
                    adicionarLog('✅ APIs de agendamento funcionando', 'success');
                } else {
                    statusDiv.innerHTML = '❌ Erro nas APIs';
                    statusDiv.className = 'test-result test-error';
                    adicionarLog('❌ Erro nas APIs de agendamento', 'error');
                }
            } catch (error) {
                statusDiv.innerHTML = '❌ Erro de conexão com as APIs';
                statusDiv.className = 'test-result test-error';
                adicionarLog(`❌ Erro de conexão: ${error.message}`, 'error');
            }
        }

        // Teste de criação de aula
        document.getElementById('form-nova-aula').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const dados = Object.fromEntries(formData.entries());
            
            adicionarLog('🚀 Tentando criar aula...', 'info');
            
            try {
                const response = await fetch('api/agendamento.php/aula', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                });
                
                const resultado = await response.json();
                const resultadoDiv = document.getElementById('resultado-criacao');
                
                if (resultado.sucesso) {
                    resultadoDiv.innerHTML = `<div class="test-result test-success">✅ ${resultado.mensagem}<br>Aula ID: ${resultado.aula_id}</div>`;
                    adicionarLog(`✅ Aula criada com sucesso! ID: ${resultado.aula_id}`, 'success');
                } else {
                    resultadoDiv.innerHTML = `<div class="test-result test-error">❌ ${resultado.mensagem}</div>`;
                    adicionarLog(`❌ Erro ao criar aula: ${resultado.mensagem}`, 'error');
                }
            } catch (error) {
                const resultadoDiv = document.getElementById('resultado-criacao');
                resultadoDiv.innerHTML = `<div class="test-result test-error">❌ Erro de conexão: ${error.message}</div>`;
                adicionarLog(`❌ Erro de conexão: ${error.message}`, 'error');
            }
        });

        // Teste de listagem de aulas
        async function listarAulas() {
            adicionarLog('📋 Listando aulas...', 'info');
            
            try {
                const response = await fetch('api/agendamento.php/aulas');
                const resultado = await response.json();
                const resultadoDiv = document.getElementById('resultado-listagem');
                
                if (resultado.sucesso) {
                    resultadoDiv.innerHTML = `<div class="test-result test-success">✅ ${resultado.total} aulas encontradas</div>`;
                    adicionarLog(`✅ ${resultado.total} aulas listadas com sucesso`, 'success');
                } else {
                    resultadoDiv.innerHTML = `<div class="test-result test-error">❌ ${resultado.mensagem}</div>`;
                    adicionarLog(`❌ Erro ao listar aulas: ${resultado.mensagem}`, 'error');
                }
            } catch (error) {
                const resultadoDiv = document.getElementById('resultado-listagem');
                resultadoDiv.innerHTML = `<div class="test-result test-error">❌ Erro de conexão: ${error.message}</div>`;
                adicionarLog(`❌ Erro de conexão: ${error.message}`, 'error');
            }
        }

        // Teste de listagem com filtros
        async function listarAulasComFiltros() {
            adicionarLog('🔍 Listando aulas com filtros...', 'info');
            
            try {
                const filtros = '?data_inicio=' + new Date().toISOString().split('T')[0];
                const response = await fetch('api/agendamento.php/aulas' + filtros);
                const resultado = await response.json();
                const resultadoDiv = document.getElementById('resultado-listagem');
                
                if (resultado.sucesso) {
                    resultadoDiv.innerHTML = `<div class="test-result test-success">✅ ${resultado.total} aulas encontradas com filtros</div>`;
                    adicionarLog(`✅ ${resultado.total} aulas listadas com filtros`, 'success');
                } else {
                    resultadoDiv.innerHTML = `<div class="test-result test-error">❌ ${resultado.mensagem}</div>`;
                    adicionarLog(`❌ Erro ao listar aulas com filtros: ${resultado.mensagem}`, 'error');
                }
            } catch (error) {
                const resultadoDiv = document.getElementById('resultado-listagem');
                resultadoDiv.innerHTML = `<div class="test-result test-error">❌ Erro de conexão: ${error.message}</div>`;
                adicionarLog(`❌ Erro de conexão: ${error.message}`, 'error');
            }
        }

        // Teste de verificação de disponibilidade
        document.getElementById('form-disponibilidade').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const dados = Object.fromEntries(formData.entries());
            
            adicionarLog('🔍 Verificando disponibilidade...', 'info');
            
            try {
                const queryString = new URLSearchParams(dados).toString();
                const response = await fetch('api/agendamento.php/disponibilidade?' + queryString);
                const resultado = await response.json();
                const resultadoDiv = document.getElementById('resultado-disponibilidade');
                
                if (resultado.sucesso) {
                    const disponivel = resultado.dados.disponivel;
                    const mensagem = disponivel ? '✅ Horário disponível' : '❌ Horário não disponível';
                    const classe = disponivel ? 'test-success' : 'test-error';
                    
                    resultadoDiv.innerHTML = `<div class="test-result ${classe}">${mensagem}<br>Motivo: ${resultado.dados.motivo}</div>`;
                    adicionarLog(`${mensagem}: ${resultado.dados.motivo}`, disponivel ? 'success' : 'error');
                } else {
                    resultadoDiv.innerHTML = `<div class="test-result test-error">❌ ${resultado.mensagem}</div>`;
                    adicionarLog(`❌ Erro ao verificar disponibilidade: ${resultado.mensagem}`, 'error');
                }
            } catch (error) {
                const resultadoDiv = document.getElementById('resultado-disponibilidade');
                resultadoDiv.innerHTML = `<div class="test-result test-error">❌ Erro de conexão: ${error.message}</div>`;
                adicionarLog(`❌ Erro de conexão: ${error.message}`, 'error');
            }
        });

        // Teste de estatísticas
        async function obterEstatisticas() {
            adicionarLog('📊 Obtendo estatísticas...', 'info');
            
            try {
                const response = await fetch('api/agendamento.php/estatisticas');
                const resultado = await response.json();
                const resultadoDiv = document.getElementById('resultado-estatisticas');
                
                if (resultado.sucesso) {
                    const stats = resultado.dados;
                    resultadoDiv.innerHTML = `
                        <div class="test-result test-success">
                            ✅ Estatísticas obtidas com sucesso<br>
                            Total de aulas: ${stats.total_aulas}<br>
                            Aulas da semana: ${stats.aulas_semana}
                        </div>
                    `;
                    adicionarLog(`✅ Estatísticas obtidas: ${stats.total_aulas} aulas totais`, 'success');
                } else {
                    resultadoDiv.innerHTML = `<div class="test-result test-error">❌ ${resultado.mensagem}</div>`;
                    adicionarLog(`❌ Erro ao obter estatísticas: ${resultado.mensagem}`, 'error');
                }
            } catch (error) {
                const resultadoDiv = document.getElementById('resultado-estatisticas');
                resultadoDiv.innerHTML = `<div class="test-result test-error">❌ Erro de conexão: ${error.message}</div>`;
                adicionarLog(`❌ Erro de conexão: ${error.message}`, 'error');
            }
        }

        // Teste de estatísticas com filtros
        async function obterEstatisticasComFiltros() {
            adicionarLog('🔍 Obtendo estatísticas com filtros...', 'info');
            
            try {
                const filtros = '?data_inicio=' + new Date().toISOString().split('T')[0];
                const response = await fetch('api/agendamento.php/estatisticas' + filtros);
                const resultado = await response.json();
                const resultadoDiv = document.getElementById('resultado-estatisticas');
                
                if (resultado.sucesso) {
                    const stats = resultado.dados;
                    resultadoDiv.innerHTML = `
                        <div class="test-result test-success">
                            ✅ Estatísticas com filtros obtidas<br>
                            Total de aulas: ${stats.total_aulas}<br>
                            Aulas da semana: ${stats.aulas_semana}
                        </div>
                    `;
                    adicionarLog(`✅ Estatísticas com filtros obtidas: ${stats.total_aulas} aulas`, 'success');
                } else {
                    resultadoDiv.innerHTML = `<div class="test-result test-error">❌ ${resultado.mensagem}</div>`;
                    adicionarLog(`❌ Erro ao obter estatísticas com filtros: ${resultado.mensagem}`, 'error');
                }
            } catch (error) {
                const resultadoDiv = document.getElementById('resultado-estatisticas');
                resultadoDiv.innerHTML = `<div class="test-result test-error">❌ Erro de conexão: ${error.message}</div>`;
                adicionarLog(`❌ Erro de conexão: ${error.message}`, 'error');
            }
        }

        // Limpar logs
        function limparLogs() {
            document.getElementById('logs-teste').innerHTML = '';
            adicionarLog('🗑️ Logs limpos', 'info');
        }
    </script>
</body>
</html>
