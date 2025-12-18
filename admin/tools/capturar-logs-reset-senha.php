<?php
/**
 * Script para Capturar Logs do Navegador - Reset de Senha
 * 
 * Este script gera um HTML com JavaScript que captura todos os logs
 * do navegador durante o processo de reset de senha.
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capturar Logs - Reset de Senha</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        h1 {
            color: #1A365D;
            margin-bottom: 20px;
            border-bottom: 2px solid #1A365D;
            padding-bottom: 10px;
        }
        
        .instructions {
            background: #e8f4f8;
            border-left: 4px solid #1A365D;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .instructions h2 {
            color: #1A365D;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .instructions ol {
            margin-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .log-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 20px;
            max-height: 600px;
            overflow-y: auto;
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            padding: 15px;
        }
        
        .log-entry {
            margin-bottom: 8px;
            padding: 5px;
            border-left: 3px solid transparent;
            padding-left: 10px;
        }
        
        .log-entry.error {
            border-left-color: #f44336;
            background: rgba(244, 67, 54, 0.1);
        }
        
        .log-entry.warn {
            border-left-color: #ff9800;
            background: rgba(255, 152, 0, 0.1);
        }
        
        .log-entry.info {
            border-left-color: #2196F3;
            background: rgba(33, 150, 243, 0.1);
        }
        
        .log-entry.success {
            border-left-color: #4CAF50;
            background: rgba(76, 175, 80, 0.1);
        }
        
        .log-time {
            color: #888;
            margin-right: 10px;
        }
        
        .log-type {
            font-weight: bold;
            margin-right: 10px;
        }
        
        .log-type.error {
            color: #f44336;
        }
        
        .log-type.warn {
            color: #ff9800;
        }
        
        .log-type.info {
            color: #2196F3;
        }
        
        .log-type.success {
            color: #4CAF50;
        }
        
        .controls {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #1A365D;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2d4a6b;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-success:hover {
            background: #45a049;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #da190b;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 150px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1A365D;
        }
        
        .copy-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
        }
        
        .filter-controls {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-controls label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        
        .filter-controls input[type="checkbox"] {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Capturador de Logs - Reset de Senha</h1>
        
            <div class="instructions">
            <h2>📋 Instruções (Método 1 - Recomendado):</h2>
            <ol>
                <li><strong>Abra a página de reset:</strong> <code>reset-password.php?token=SEU_TOKEN</code></li>
                <li><strong>Abra o DevTools (F12)</strong> e vá na aba "Console"</li>
                <li><strong>Cole o código abaixo</strong> no console e pressione Enter:</li>
                <li><strong>Preencha o formulário</strong> e clique em "Redefinir Senha"</li>
                <li><strong>Os logs aparecerão no console</strong> e também serão salvos em <code>window.capturedLogs</code></li>
            </ol>
            <div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; margin-top: 10px; font-family: monospace; font-size: 12px; overflow-x: auto;">
                <pre id="codeSnippet">// Cole este código no console da página reset-password.php
(function() {
    window.capturedLogs = [];
    const addLog = (type, msg, data) => {
        const log = {time: new Date().toLocaleTimeString(), type, msg, data};
        window.capturedLogs.push(log);
        console.log(`[${log.time}] ${type.toUpperCase()}:`, msg, data || '');
    };
    
    // Interceptar submit do formulário
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const formData = new FormData(form);
        const data = {};
        formData.forEach((v, k) => {
            data[k] = k.includes('password') || k.includes('senha') ? '***' : v;
        });
        addLog('REQUEST', `FORM SUBMIT: ${form.method} ${form.action || window.location.href}`, data);
    }, true);
    
    // Interceptar fetch
    const origFetch = window.fetch;
    window.fetch = (...args) => {
        addLog('REQUEST', `FETCH: ${args[0]}`, {method: args[1]?.method || 'GET'});
        return origFetch.apply(this, args).then(r => {
            addLog('RESPONSE', `FETCH: ${args[0]}`, {status: r.status, statusText: r.statusText});
            return r;
        }).catch(e => {
            addLog('ERROR', `FETCH ERROR: ${args[0]}`, e.message);
            throw e;
        });
    };
    
    // Interceptar XHR
    const OrigXHR = window.XMLHttpRequest;
    window.XMLHttpRequest = function() {
        const xhr = new OrigXHR();
        const origOpen = xhr.open;
        const origSend = xhr.send;
        xhr.open = function(m, u) {
            this._method = m; this._url = u;
            addLog('REQUEST', `XHR: ${m} ${u}`);
            return origOpen.apply(this, arguments);
        };
        xhr.send = function(d) {
            xhr.addEventListener('load', () => addLog('RESPONSE', `XHR: ${xhr._method} ${xhr._url}`, {status: xhr.status}));
            xhr.addEventListener('error', () => addLog('ERROR', `XHR ERROR: ${xhr._method} ${xhr._url}`));
            return origSend.apply(this, arguments);
        };
        return xhr;
    };
    
    addLog('INFO', '✅ Captura de logs iniciada!');
    console.log('📋 Logs serão salvos em window.capturedLogs');
})();</pre>
                <button onclick="copyCode()" class="btn btn-primary" style="margin-top: 10px;">📋 Copiar Código</button>
            </div>
            
            <h2 style="margin-top: 30px;">📋 Método 2 - Comunicação entre Abas:</h2>
            <ol>
                <li><strong>Abra esta página em uma aba</strong> (mantenha aberta)</li>
                <li><strong>Abra outra aba</strong> e acesse: <code>reset-password.php?token=SEU_TOKEN</code></li>
                <li><strong>Cole o código acima no console</strong> da página de reset</li>
                <li><strong>Os logs serão sincronizados</strong> entre as abas via localStorage</li>
            </ol>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-label">Total de Logs</div>
                <div class="stat-value" id="totalLogs">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Erros</div>
                <div class="stat-value" id="errorCount" style="color: #f44336;">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Avisos</div>
                <div class="stat-value" id="warnCount" style="color: #ff9800;">0</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Requisições</div>
                <div class="stat-value" id="requestCount" style="color: #2196F3;">0</div>
            </div>
        </div>
        
        <div class="controls">
            <button class="btn btn-primary" onclick="startCapture()">▶ Iniciar Captura</button>
            <button class="btn btn-secondary" onclick="clearLogs()">🗑 Limpar Logs</button>
            <button class="btn btn-success" onclick="copyLogs()">📋 Copiar Logs</button>
            <button class="btn btn-danger" onclick="stopCapture()">⏹ Parar Captura</button>
        </div>
        
        <div class="filter-controls">
            <label><input type="checkbox" id="filterErrors" checked> Erros</label>
            <label><input type="checkbox" id="filterWarnings" checked> Avisos</label>
            <label><input type="checkbox" id="filterInfo" checked> Informações</label>
            <label><input type="checkbox" id="filterRequests" checked> Requisições</label>
            <label><input type="checkbox" id="filterConsole" checked> Console</label>
        </div>
        
        <div class="log-container" id="logContainer">
            <div class="log-entry info">
                <span class="log-time">[Aguardando...]</span>
                <span class="log-type info">INFO</span>
                <span>Clique em "Iniciar Captura" para começar a monitorar logs.</span>
            </div>
        </div>
    </div>
    
    <div class="copy-notification" id="copyNotification">
        ✅ Logs copiados para a área de transferência!
    </div>
    
    <script>
        let isCapturing = false;
        let logCount = 0;
        let errorCount = 0;
        let warnCount = 0;
        let requestCount = 0;
        let originalConsole = {};
        let originalFetch = window.fetch;
        let originalXHR = window.XMLHttpRequest;
        let logs = [];
        
        // Salvar métodos originais do console
        ['log', 'error', 'warn', 'info', 'debug'].forEach(method => {
            originalConsole[method] = console[method];
        });
        
        function formatTime() {
            const now = new Date();
            return now.toLocaleTimeString('pt-BR', { hour12: false, milliseconds: true });
        }
        
        function addLog(type, message, data = null) {
            if (!isCapturing) return;
            
            logCount++;
            if (type === 'error') errorCount++;
            if (type === 'warn') warnCount++;
            if (type === 'request') requestCount++;
            
            const logEntry = {
                time: formatTime(),
                type: type,
                message: message,
                data: data
            };
            
            logs.push(logEntry);
            updateStats();
            renderLog(logEntry);
        }
        
        function renderLog(logEntry) {
            const container = document.getElementById('logContainer');
            const entry = document.createElement('div');
            entry.className = `log-entry ${logEntry.type}`;
            
            const message = logEntry.data 
                ? `${logEntry.message}\n${JSON.stringify(logEntry.data, null, 2)}`
                : logEntry.message;
            
            entry.innerHTML = `
                <span class="log-time">[${logEntry.time}]</span>
                <span class="log-type ${logEntry.type}">${logEntry.type.toUpperCase()}</span>
                <span>${escapeHtml(message)}</span>
            `;
            
            container.appendChild(entry);
            container.scrollTop = container.scrollHeight;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML.replace(/\n/g, '<br>');
        }
        
        function updateStats() {
            document.getElementById('totalLogs').textContent = logCount;
            document.getElementById('errorCount').textContent = errorCount;
            document.getElementById('warnCount').textContent = warnCount;
            document.getElementById('requestCount').textContent = requestCount;
        }
        
        function startCapture() {
            if (isCapturing) {
                addLog('warn', 'Captura já está ativa');
                return;
            }
            
            isCapturing = true;
            logs = [];
            logCount = 0;
            errorCount = 0;
            warnCount = 0;
            requestCount = 0;
            
            // Interceptar console
            console.log = function(...args) {
                originalConsole.log.apply(console, args);
                addLog('info', args.join(' '), args.length > 1 ? args : null);
            };
            
            console.error = function(...args) {
                originalConsole.error.apply(console, args);
                addLog('error', args.join(' '), args.length > 1 ? args : null);
            };
            
            console.warn = function(...args) {
                originalConsole.warn.apply(console, args);
                addLog('warn', args.join(' '), args.length > 1 ? args : null);
            };
            
            console.info = function(...args) {
                originalConsole.info.apply(console, args);
                addLog('info', args.join(' '), args.length > 1 ? args : null);
            };
            
            console.debug = function(...args) {
                originalConsole.debug.apply(console, args);
                addLog('info', args.join(' '), args.length > 1 ? args : null);
            };
            
            // Interceptar fetch
            window.fetch = function(...args) {
                const url = args[0];
                const options = args[1] || {};
                
                addLog('request', `FETCH: ${url}`, {
                    method: options.method || 'GET',
                    headers: options.headers,
                    body: options.body
                });
                
                return originalFetch.apply(this, args)
                    .then(response => {
                        addLog('success', `FETCH Response: ${url} - ${response.status} ${response.statusText}`);
                        return response;
                    })
                    .catch(error => {
                        addLog('error', `FETCH Error: ${url} - ${error.message}`, error);
                        throw error;
                    });
            };
            
            // Interceptar XMLHttpRequest
            const OriginalXHR = window.XMLHttpRequest;
            window.XMLHttpRequest = function() {
                const xhr = new OriginalXHR();
                const originalOpen = xhr.open;
                const originalSend = xhr.send;
                
                xhr.open = function(method, url, ...rest) {
                    this._method = method;
                    this._url = url;
                    addLog('request', `XHR: ${method} ${url}`);
                    return originalOpen.apply(this, [method, url, ...rest]);
                };
                
                xhr.send = function(data) {
                    addLog('request', `XHR Send: ${this._method} ${this._url}`, data ? { body: data } : null);
                    
                    xhr.addEventListener('load', function() {
                        addLog('success', `XHR Response: ${this._method} ${this._url} - ${this.status} ${this.statusText}`);
                    });
                    
                    xhr.addEventListener('error', function() {
                        addLog('error', `XHR Error: ${this._method} ${this._url}`);
                    });
                    
                    return originalSend.apply(this, arguments);
                };
                
                return xhr;
            };
            
            // Interceptar submissões de formulário (CRÍTICO para reset-password.php)
            document.addEventListener('submit', function(event) {
                const form = event.target;
                if (form.tagName === 'FORM') {
                    const formData = new FormData(form);
                    const formObject = {};
                    formData.forEach((value, key) => {
                        // Mascarar senhas
                        if (key.includes('password') || key.includes('senha')) {
                            formObject[key] = '***MASCARADO***';
                        } else {
                            formObject[key] = value;
                        }
                    });
                    
                    addLog('request', `FORM SUBMIT: ${form.method || 'POST'} ${form.action || window.location.href}`, {
                        method: form.method || 'POST',
                        action: form.action || window.location.href,
                        enctype: form.enctype,
                        data: formObject
                    });
                    
                    // Não prevenir o submit, apenas logar
                }
            }, true); // Usar capture phase para pegar antes de qualquer preventDefault
            
            // Interceptar cliques em botões de submit
            document.addEventListener('click', function(event) {
                const target = event.target;
                if (target.type === 'submit' || target.closest('button[type="submit"]') || target.closest('input[type="submit"]')) {
                    const form = target.closest('form');
                    if (form) {
                        addLog('info', `Botão de submit clicado: ${target.textContent || target.value || 'Submit'}`, {
                            formAction: form.action || window.location.href,
                            formMethod: form.method || 'POST'
                        });
                    }
                }
            }, true);
            
            // Capturar mudanças em campos de formulário (para debug)
            document.addEventListener('input', function(event) {
                const target = event.target;
                if (target.tagName === 'INPUT' && (target.type === 'password' || target.name.includes('password') || target.name.includes('senha'))) {
                    // Não logar o valor, apenas que foi digitado
                    addLog('info', `Campo de senha modificado: ${target.name} (${target.value.length} caracteres)`);
                }
            });
            
            // Capturar erros globais
            window.addEventListener('error', function(event) {
                addLog('error', `JavaScript Error: ${event.message}`, {
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    error: event.error ? event.error.toString() : null
                });
            });
            
            // Capturar erros de Promise não tratados
            window.addEventListener('unhandledrejection', function(event) {
                addLog('error', `Unhandled Promise Rejection: ${event.reason}`, {
                    reason: event.reason ? event.reason.toString() : null
                });
            });
            
            // Interceptar navegação (para capturar quando o formulário redireciona)
            const originalPushState = history.pushState;
            const originalReplaceState = history.replaceState;
            
            history.pushState = function() {
                addLog('info', 'History.pushState chamado', { arguments: Array.from(arguments) });
                return originalPushState.apply(history, arguments);
            };
            
            history.replaceState = function() {
                addLog('info', 'History.replaceState chamado', { arguments: Array.from(arguments) });
                return originalReplaceState.apply(history, arguments);
            };
            
            // Interceptar antes de sair da página (para capturar redirects)
            window.addEventListener('beforeunload', function(event) {
                addLog('info', 'Página está sendo descarregada/navegada', {
                    url: window.location.href
                });
            });
            
            addLog('success', '✅ Captura de logs iniciada!');
            addLog('info', 'Agora você pode testar o reset de senha em outra aba.');
            addLog('info', '⚠️ IMPORTANTE: Para capturar requisições de formulário, você precisa abrir o DevTools (F12) na aba do reset-password.php e verificar a aba "Rede" (Network).');
        }
        
        function stopCapture() {
            if (!isCapturing) {
                addLog('warn', 'Captura não está ativa');
                return;
            }
            
            isCapturing = false;
            
            // Restaurar métodos originais
            Object.keys(originalConsole).forEach(method => {
                console[method] = originalConsole[method];
            });
            
            window.fetch = originalFetch;
            window.XMLHttpRequest = originalXHR;
            
            addLog('info', '⏹ Captura parada. Métodos originais restaurados.');
        }
        
        function clearLogs() {
            document.getElementById('logContainer').innerHTML = '';
            logs = [];
            logCount = 0;
            errorCount = 0;
            warnCount = 0;
            requestCount = 0;
            updateStats();
            addLog('info', 'Logs limpos.');
        }
        
        function copyLogs() {
            const logText = logs.map(log => {
                let text = `[${log.time}] ${log.type.toUpperCase()}: ${log.message}`;
                if (log.data) {
                    text += '\n' + JSON.stringify(log.data, null, 2);
                }
                return text;
            }).join('\n\n');
            
            navigator.clipboard.writeText(logText).then(() => {
                const notification = document.getElementById('copyNotification');
                notification.style.display = 'block';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            }).catch(err => {
                addLog('error', 'Erro ao copiar logs: ' + err.message);
            });
        }
        
        function copyCode() {
            const code = document.getElementById('codeSnippet').textContent;
            navigator.clipboard.writeText(code).then(() => {
                alert('✅ Código copiado! Cole no console da página reset-password.php');
            });
        }
        
        // Monitorar localStorage para logs de outras abas
        function checkLocalStorageLogs() {
            try {
                const storedLogs = localStorage.getItem('capturedLogs');
                if (storedLogs) {
                    const logs = JSON.parse(storedLogs);
                    logs.forEach(log => {
                        if (!logs.find(l => l.time === log.time && l.msg === log.msg)) {
                            addLog(log.type, log.msg, log.data);
                        }
                    });
                    localStorage.removeItem('capturedLogs');
                }
            } catch (e) {
                // Ignorar erros
            }
        }
        
        // Verificar logs a cada segundo
        setInterval(checkLocalStorageLogs, 1000);
        
        // Iniciar captura automaticamente
        window.addEventListener('load', function() {
            setTimeout(() => {
                startCapture();
            }, 1000);
        });
    </script>
</body>
</html>
