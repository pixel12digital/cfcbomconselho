/**
 * Script para Injetar na Página reset-password.php
 * 
 * INSTRUÇÕES:
 * 1. Abra reset-password.php?token=SEU_TOKEN
 * 2. Abra o DevTools (F12) e vá na aba "Console"
 * 3. Cole este código completo e pressione Enter
 * 4. Preencha o formulário e clique em "Redefinir Senha"
 * 5. Os logs aparecerão no console e em window.capturedLogs
 */

(function() {
    'use strict';
    
    // Armazenar logs
    window.capturedLogs = [];
    const logsDiv = document.createElement('div');
    logsDiv.id = 'captured-logs-display';
    logsDiv.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        width: 500px;
        max-height: 400px;
        overflow-y: auto;
        background: #1e1e1e;
        color: #d4d4d4;
        padding: 15px;
        border-radius: 8px;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 12px;
        z-index: 10000;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        border: 2px solid #4CAF50;
    `;
    document.body.appendChild(logsDiv);
    
    const addLog = (type, msg, data = null) => {
        const time = new Date().toLocaleTimeString('pt-BR');
        const log = {time, type, msg, data};
        window.capturedLogs.push(log);
        
        // Log no console
        const consoleMethod = type === 'error' ? 'error' : type === 'warn' ? 'warn' : 'log';
        console[consoleMethod](`[${time}] ${type.toUpperCase()}:`, msg, data || '');
        
        // Adicionar na div
        const logEntry = document.createElement('div');
        logEntry.style.cssText = `
            margin-bottom: 8px;
            padding: 5px;
            border-left: 3px solid ${type === 'error' ? '#f44336' : type === 'warn' ? '#ff9800' : type === 'request' ? '#2196F3' : '#4CAF50'};
            padding-left: 10px;
        `;
        logEntry.innerHTML = `
            <span style="color: #888;">[${time}]</span>
            <span style="color: ${type === 'error' ? '#f44336' : type === 'warn' ? '#ff9800' : type === 'request' ? '#2196F3' : '#4CAF50'}; font-weight: bold;">${type.toUpperCase()}</span>
            <span>${msg}</span>
            ${data ? `<pre style="margin-top: 5px; color: #aaa;">${JSON.stringify(data, null, 2)}</pre>` : ''}
        `;
        logsDiv.appendChild(logEntry);
        logsDiv.scrollTop = logsDiv.scrollHeight;
        
        // Salvar no localStorage para outras abas
        try {
            const existing = JSON.parse(localStorage.getItem('capturedLogs') || '[]');
            existing.push(log);
            localStorage.setItem('capturedLogs', JSON.stringify(existing.slice(-100))); // Últimos 100 logs
        } catch (e) {
            // Ignorar erros de localStorage
        }
    };
    
    // Interceptar submit do formulário (CRÍTICO)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM') {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = (key.includes('password') || key.includes('senha')) ? '***MASCARADO***' : value;
            });
            
            addLog('request', `FORM SUBMIT: ${form.method || 'POST'} ${form.action || window.location.href}`, {
                method: form.method || 'POST',
                action: form.action || window.location.href,
                enctype: form.enctype,
                data: data
            });
        }
    }, true);
    
    // Interceptar cliques em botão submit
    document.addEventListener('click', function(e) {
        const target = e.target;
        if (target.type === 'submit' || target.closest('button[type="submit"]') || target.closest('input[type="submit"]')) {
            const form = target.closest('form');
            if (form) {
                addLog('info', `Botão submit clicado: ${target.textContent || target.value || 'Submit'}`, {
                    formAction: form.action || window.location.href,
                    formMethod: form.method || 'POST'
                });
            }
        }
    }, true);
    
    // Interceptar fetch
    const origFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        const options = args[1] || {};
        addLog('request', `FETCH: ${url}`, {
            method: options.method || 'GET',
            headers: options.headers,
            body: options.body ? (typeof options.body === 'string' ? options.body.substring(0, 200) : 'FormData') : null
        });
        
        return origFetch.apply(this, args)
            .then(response => {
                addLog('success', `FETCH Response: ${url}`, {
                    status: response.status,
                    statusText: response.statusText,
                    ok: response.ok
                });
                return response;
            })
            .catch(error => {
                addLog('error', `FETCH Error: ${url}`, {message: error.message});
                throw error;
            });
    };
    
    // Interceptar XMLHttpRequest
    const OrigXHR = window.XMLHttpRequest;
    window.XMLHttpRequest = function() {
        const xhr = new OrigXHR();
        const origOpen = xhr.open;
        const origSend = xhr.send;
        
        xhr.open = function(method, url, ...rest) {
            this._method = method;
            this._url = url;
            addLog('request', `XHR: ${method} ${url}`);
            return origOpen.apply(this, [method, url, ...rest]);
        };
        
        xhr.send = function(data) {
            addLog('request', `XHR Send: ${this._method} ${this._url}`, data ? {bodyLength: data.length} : null);
            
            xhr.addEventListener('load', function() {
                addLog('success', `XHR Response: ${this._method} ${this._url}`, {
                    status: this.status,
                    statusText: this.statusText,
                    responseText: this.responseText ? this.responseText.substring(0, 500) : null
                });
            });
            
            xhr.addEventListener('error', function() {
                addLog('error', `XHR Error: ${this._method} ${this._url}`);
            });
            
            return origSend.apply(this, arguments);
        };
        
        return xhr;
    };
    
    // Capturar erros JavaScript
    window.addEventListener('error', function(e) {
        addLog('error', `JavaScript Error: ${e.message}`, {
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno
        });
    });
    
    window.addEventListener('unhandledrejection', function(e) {
        addLog('error', `Unhandled Promise Rejection: ${e.reason}`, {
            reason: e.reason ? e.reason.toString() : null
        });
    });
    
    // Interceptar console
    const origConsole = {
        log: console.log,
        error: console.error,
        warn: console.warn,
        info: console.info
    };
    
    console.log = function(...args) {
        origConsole.log.apply(console, args);
        addLog('info', args.join(' '));
    };
    
    console.error = function(...args) {
        origConsole.error.apply(console, args);
        addLog('error', args.join(' '));
    };
    
    console.warn = function(...args) {
        origConsole.warn.apply(console, args);
        addLog('warn', args.join(' '));
    };
    
    console.info = function(...args) {
        origConsole.info.apply(console, args);
        addLog('info', args.join(' '));
    };
    
    addLog('success', '✅ Captura de logs iniciada!');
    addLog('info', '📋 Logs salvos em window.capturedLogs');
    addLog('info', '📋 Use console.log(window.capturedLogs) para ver todos os logs');
    addLog('info', '📋 Use JSON.stringify(window.capturedLogs) para copiar');
    
    console.log('✅ Script de captura de logs injetado com sucesso!');
    console.log('📋 Logs serão exibidos no console e em window.capturedLogs');
})();
