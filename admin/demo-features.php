<?php
// Página de demonstração das funcionalidades modernas do Sistema CFC
// Baseado no sistema e-condutor para mesma experiência do usuário
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demonstração de Funcionalidades - Sistema CFC</title>
    
    <!-- CSS do sistema -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <!-- Componentes JavaScript -->
    <script src="assets/js/components.js"></script>
    
    <style>
        .demo-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .demo-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .demo-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .demo-title {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        
        .demo-subtitle {
            color: #34495e;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-icon {
            font-size: 1.2rem;
            width: 30px;
            text-align: center;
        }
        
        .demo-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }
        
        .demo-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .demo-button.success {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .demo-button.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .demo-button.danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .demo-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            margin: 0.5rem 0;
        }
        
        .demo-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .demo-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .demo-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        .comparison-table th,
        .comparison-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .comparison-table th {
            background: #2c3e50;
            color: white;
            font-weight: 600;
        }
        
        .comparison-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .comparison-table tr:hover {
            background: #e9ecef;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.info {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container-fluid" style="padding: 2rem; margin-top: 0;">
        <div class="demo-section">
            <h1 class="demo-title">🚀 Demonstração das Funcionalidades Modernas</h1>
            <p class="demo-subtitle">
                Sistema CFC implementado com as mesmas funcionalidades e experiência do usuário do sistema e-condutor
            </p>
        </div>

        <!-- Sistema de Notificações -->
        <div class="demo-section">
            <h2 class="demo-title">🔔 Sistema de Notificações</h2>
            <p class="demo-subtitle">Sistema de notificações moderno similar ao Alertify do e-condutor</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>Notificações Simples</h3>
                    <p>Clique nos botões para testar diferentes tipos de notificações:</p>
                    <button class="demo-button" onclick="notifications.success('Operação realizada com sucesso!')">
                        ✅ Sucesso
                    </button>
                    <button class="demo-button warning" onclick="notifications.warning('Atenção! Verifique os dados.')">
                        ⚠️ Aviso
                    </button>
                    <button class="demo-button danger" onclick="notifications.error('Erro ao processar solicitação.')">
                        ❌ Erro
                    </button>
                    <button class="demo-button" onclick="notifications.info('Informação importante para você.')">
                        ℹ️ Info
                    </button>
                </div>
                
                <div class="demo-card">
                    <h3>Notificações Personalizadas</h3>
                    <p>Notificações com duração personalizada:</p>
                    <button class="demo-button" onclick="notifications.success('Notificação que desaparece em 2 segundos', 2000)">
                        ⏱️ 2 segundos
                    </button>
                    <button class="demo-button" onclick="notifications.info('Notificação que permanece até ser fechada', 0)">
                        🔒 Permanente
                    </button>
                </div>
            </div>
        </div>

        <!-- Sistema de Máscaras -->
        <div class="demo-section">
            <h2 class="demo-title">🎭 Sistema de Máscaras de Input</h2>
            <p class="demo-subtitle">Máscaras automáticas para CPF, telefone, CEP e outros campos</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>Máscaras Automáticas</h3>
                    <div class="demo-form">
                        <label>CPF:</label>
                        <input type="text" class="demo-input" data-mask="cpf" placeholder="000.000.000-00">
                        
                        <label>CNPJ:</label>
                        <input type="text" class="demo-input" data-mask="cnpj" placeholder="00.000.000/0000-00">
                        
                        <label>Telefone:</label>
                        <input type="text" class="demo-input" data-mask="telefone" placeholder="(00) 00000-0000">
                        
                        <label>CEP:</label>
                        <input type="text" class="demo-input" data-mask="cep" placeholder="00000-000">
                    </div>
                </div>
                
                <div class="demo-card">
                    <h3>Máscaras Específicas</h3>
                    <div class="demo-form">
                        <label>Data:</label>
                        <input type="text" class="demo-input" data-mask="data" placeholder="00/00/0000">
                        
                        <label>Hora:</label>
                        <input type="text" class="demo-input" data-mask="hora" placeholder="00:00">
                        
                        <label>Placa:</label>
                        <input type="text" class="demo-input" data-mask="placa" placeholder="AAA-0000">
                        
                        <label>Valor:</label>
                        <input type="text" class="demo-input" data-mask="valor" placeholder="0,00">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sistema de Validação -->
        <div class="demo-section">
            <h2 class="demo-title">✅ Sistema de Validação em Tempo Real</h2>
            <p class="demo-subtitle">Validação automática de campos com feedback visual</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>Validação de Formulário</h3>
                    <form data-validate>
                        <div class="demo-form">
                            <label>Nome (obrigatório):</label>
                            <input type="text" class="demo-input" data-validate="required|minLength:3" placeholder="Digite seu nome">
                            
                            <label>Email:</label>
                            <input type="text" class="demo-input" data-validate="required|email" placeholder="seu@email.com">
                            
                            <label>CPF:</label>
                            <input type="text" class="demo-input" data-validate="required|cpf" data-mask="cpf" placeholder="000.000.000-00">
                            
                            <button type="submit" class="demo-button success">Enviar Formulário</button>
                        </div>
                    </form>
                </div>
                
                <div class="demo-card">
                    <h3>Validações Disponíveis</h3>
                    <ul class="feature-list">
                        <li><span class="feature-icon">✅</span>Campo obrigatório</li>
                        <li><span class="feature-icon">✅</span>Validação de email</li>
                        <li><span class="feature-icon">✅</span>Validação de CPF</li>
                        <li><span class="feature-icon">✅</span>Validação de CNPJ</li>
                        <li><span class="feature-icon">✅</span>Comprimento mínimo/máximo</li>
                        <li><span class="feature-icon">✅</span>Validação de telefone</li>
                        <li><span class="feature-icon">✅</span>Validação de CEP</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sistema de Modais -->
        <div class="demo-section">
            <h2 class="demo-title">🪟 Sistema de Modais</h2>
            <p class="demo-subtitle">Modais modernos e responsivos para confirmações e formulários</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>Modais Simples</h3>
                    <button class="demo-button" onclick="modals.show('Este é um modal simples de demonstração!', { title: 'Modal Simples' })">
                        📱 Modal Simples
                    </button>
                    
                    <button class="demo-button warning" onclick="modals.show('<div style=\'text-align: center; padding: 2rem;\'><h3>🎉 Parabéns!</h3><p>Você completou o cadastro com sucesso!</p></div>', { title: 'Sucesso!', width: '400px' })">
                        🎉 Modal Personalizado
                    </button>
                </div>
                
                <div class="demo-card">
                    <h3>Confirmações</h3>
                    <button class="demo-button danger" onclick="modals.confirm('Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.', () => { notifications.success('Registro excluído!'); }, () => { notifications.info('Operação cancelada.'); })">
                        🗑️ Confirmar Exclusão
                    </button>
                    
                    <button class="demo-button warning" onclick="modals.confirm('Deseja salvar as alterações antes de sair?', () => { notifications.success('Alterações salvas!'); }, () => { notifications.info('Alterações descartadas.'); })">
                        💾 Confirmar Salvamento
                    </button>
                </div>
            </div>
        </div>

        <!-- Sistema de Loading -->
        <div class="demo-section">
            <h2 class="demo-title">⏳ Sistema de Loading</h2>
            <p class="demo-subtitle">Indicadores de carregamento para melhor experiência do usuário</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>Loading Global</h3>
                    <button class="demo-button" onclick="loading.showGlobal('Processando dados...')">
                        🌐 Mostrar Loading Global
                    </button>
                    
                    <button class="demo-button" onclick="loading.hideGlobal()">
                        ❌ Ocultar Loading Global
                    </button>
                    
                    <button class="demo-button warning" onclick="loading.showGlobal('Carregando...'); setTimeout(() => loading.hideGlobal(), 3000)">
                        ⏱️ Auto-hide em 3s
                    </button>
                </div>
                
                <div class="demo-card">
                    <h3>Loading de Botões</h3>
                    <button id="demoButton" class="demo-button success" onclick="testButtonLoading()">
                        🚀 Testar Loading de Botão
                    </button>
                    
                    <button class="demo-button" onclick="loading.showElement(document.getElementById('demoCard'), 'Carregando conteúdo...')">
                        📋 Loading de Elemento
                    </button>
                    
                    <button class="demo-button" onclick="loading.hideElement(document.getElementById('demoCard'))">
                        ❌ Ocultar Loading de Elemento
                    </button>
                </div>
            </div>
            
            <div id="demoCard" class="demo-card" style="margin-top: 1rem;">
                <h3>Área de Teste para Loading</h3>
                <p>Esta área será usada para demonstrar o sistema de loading de elementos.</p>
            </div>
        </div>

        <!-- Funções Utilitárias -->
        <div class="demo-section">
            <h2 class="demo-title">🛠️ Funções Utilitárias</h2>
            <p class="demo-subtitle">Funções para formatação e manipulação de dados</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>Formatação de Dados</h3>
                    <div class="demo-form">
                        <label>CPF (12345678901):</label>
                        <input type="text" class="demo-input" id="cpfInput" value="12345678901" readonly>
                        <button class="demo-button" onclick="document.getElementById('cpfInput').value = formatCPF('12345678901')">
                            Formatar CPF
                        </button>
                        
                        <label>Telefone (11987654321):</label>
                        <input type="text" class="demo-input" id="telInput" value="11987654321" readonly>
                        <button class="demo-button" onclick="document.getElementById('telInput').value = formatTelefone('11987654321')">
                            Formatar Telefone
                        </button>
                        
                        <label>Valor (1234.56):</label>
                        <input type="text" class="demo-input" id="valorInput" value="1234.56" readonly>
                        <button class="demo-button" onclick="document.getElementById('valorInput').value = formatMoney(1234.56)">
                            Formatar Valor
                        </button>
                    </div>
                </div>
                
                <div class="demo-card">
                    <h3>Performance e Otimização</h3>
                    <button class="demo-button" onclick="testDebounce()">
                        🚀 Testar Debounce
                    </button>
                    
                    <button class="demo-button warning" onclick="testThrottle()">
                        ⏱️ Testar Throttle
                    </button>
                    
                    <div id="debounceResult" style="margin-top: 1rem; padding: 1rem; background: #e9ecef; border-radius: 5px; display: none;">
                        Resultado do teste aparecerá aqui...
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparação com e-condutor -->
        <div class="demo-section">
            <h2 class="demo-title">📊 Comparação com Sistema e-condutor</h2>
            <p class="demo-subtitle">Funcionalidades implementadas para garantir a mesma experiência</p>
            
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Funcionalidade</th>
                        <th>e-condutor</th>
                        <th>Sistema CFC</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Sistema de Notificações</td>
                        <td>Alertify</td>
                        <td>NotificationSystem</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                    <tr>
                        <td>Máscaras de Input</td>
                        <td>jQuery Mask</td>
                        <td>InputMask</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                    <tr>
                        <td>Validação em Tempo Real</td>
                        <td>Vue.js + RegEx</td>
                        <td>FormValidator</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                    <tr>
                        <td>Sistema de Modais</td>
                        <td>Bootstrap Modal</td>
                        <td>ModalSystem</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                    <tr>
                        <td>Indicadores de Loading</td>
                        <td>Spinners + Overlays</td>
                        <td>LoadingSystem</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                    <tr>
                        <td>Formatação de Dados</td>
                        <td>Moment.js + Currency.js</td>
                        <td>Funções Utilitárias</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                    <tr>
                        <td>Interface Responsiva</td>
                        <td>Bootstrap 3</td>
                        <td>CSS Customizado</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                    <tr>
                        <td>Validação de CPF/CNPJ</td>
                        <td>Algoritmos nativos</td>
                        <td>Algoritmos implementados</td>
                        <td><span class="status-badge success">✅ Implementado</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Instruções de Uso -->
        <div class="demo-section">
            <h2 class="demo-title">📖 Como Usar as Funcionalidades</h2>
            <p class="demo-subtitle">Guia rápido para implementar as funcionalidades em suas páginas</p>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <h3>1. Incluir Componentes</h3>
                    <pre style="background: #f8f9fa; padding: 1rem; border-radius: 5px; overflow-x: auto;">
&lt;script src="assets/js/components.js"&gt;&lt;/script&gt;</pre>
                    
                    <h4>2. Usar Notificações</h4>
                    <pre style="background: #f8f9fa; padding: 1rem; border-radius: 5px; overflow-x: auto;">
notifications.success('Sucesso!');
notifications.error('Erro!');
notifications.warning('Atenção!');
notifications.info('Informação!');</pre>
                </div>
                
                <div class="demo-card">
                    <h3>3. Aplicar Máscaras</h3>
                    <pre style="background: #f8f9fa; padding: 1rem; border-radius: 5px; overflow-x: auto;">
&lt;input data-mask="cpf"&gt;
&lt;input data-mask="telefone"&gt;
&lt;input data-mask="cep"&gt;</pre>
                    
                    <h4>4. Validar Formulários</h4>
                    <pre style="background: #f8f9fa; padding: 1rem; border-radius: 5px; overflow-x: auto;">
&lt;form data-validate&gt;
&lt;input data-validate="required|email"&gt;
&lt;/form&gt;</pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para testar loading de botão
        function testButtonLoading() {
            const button = document.getElementById('demoButton');
            loading.showButton(button, 'Processando...');
            
            setTimeout(() => {
                loading.hideButton(button);
                notifications.success('Processamento concluído!');
            }, 3000);
        }
        
        // Função para testar debounce
        function testDebounce() {
            const resultDiv = document.getElementById('debounceResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testando debounce...';
            
            const debouncedFunction = debounce(() => {
                resultDiv.innerHTML = 'Debounce executado após 500ms de inatividade!';
            }, 500);
            
            // Simular múltiplas chamadas
            for (let i = 0; i < 10; i++) {
                setTimeout(() => debouncedFunction(), i * 100);
            }
        }
        
        // Função para testar throttle
        function testThrottle() {
            const resultDiv = document.getElementById('debounceResult');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testando throttle...';
            
            const throttledFunction = throttle(() => {
                resultDiv.innerHTML = 'Throttle executado (máximo 1x por segundo)!';
            }, 1000);
            
            // Simular múltiplas chamadas
            for (let i = 0; i < 10; i++) {
                setTimeout(() => throttledFunction(), i * 200);
            }
        }
        
        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof notifications !== 'undefined') {
                notifications.success('Página de demonstração carregada com sucesso!');
                notifications.info('Teste todas as funcionalidades implementadas!');
            }
            
            console.log('🎉 Sistema CFC - Demonstração de Funcionalidades carregado!');
            console.log('✅ Todas as funcionalidades do e-condutor foram implementadas');
        });
    </script>
</body>
</html>
