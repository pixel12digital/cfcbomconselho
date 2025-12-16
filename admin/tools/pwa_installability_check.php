<?php
/**
 * Diagnóstico Técnico de Installability PWA
 * Verifica todos os requisitos para instalação PWA
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico PWA - Installability Check</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .check-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }
        .check-item.pass {
            border-left-color: #28a745;
        }
        .check-item.fail {
            border-left-color: #dc3545;
        }
        .check-item.warn {
            border-left-color: #ffc107;
        }
        .check-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .check-details {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.pass {
            background: #d4edda;
            color: #155724;
        }
        .status.fail {
            background: #f8d7da;
            color: #721c24;
        }
        .status.warn {
            background: #fff3cd;
            color: #856404;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        .summary {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .summary h2 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Técnico PWA - Installability Check</h1>
    <div class="summary">
        <h2>Resumo</h2>
        <p><strong>URL Atual:</strong> <span id="current-url"></span></p>
        <p><strong>Data/Hora:</strong> <span id="current-time"></span></p>
        <p><strong>User Agent:</strong> <span id="user-agent"></span></p>
        <p><strong>Tipo:</strong> <span id="current-type"></span></p>
    </div>
    
    <div class="summary" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h2>⚠️ Importante</h2>
        <p><strong>Este check está sendo executado em:</strong> <code>/admin/tools/</code></p>
        <p>Para testar a installability real, você precisa executar o check nas páginas de login:</p>
        <div style="margin: 15px 0;">
            <a href="/login.php?type=instrutor" target="_blank" style="display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 5px;">
                🔗 Abrir Login Instrutor
            </a>
            <a href="/login.php?type=aluno" target="_blank" style="display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 5px;">
                🔗 Abrir Login Aluno
            </a>
        </div>
        <p><strong>Instrução:</strong> Abra uma das páginas acima, pressione F12 (DevTools), vá em Console e execute:</p>
        <pre style="background: #2c3e50; color: #fff; padding: 10px; border-radius: 5px; margin: 10px 0;">
// Verificar manifest
const manifestLink = document.querySelector('link[rel="manifest"]');
console.log('Manifest URL:', manifestLink?.href);
if (manifestLink) {
    fetch(manifestLink.href).then(r => r.json()).then(m => {
        console.log('Manifest Data:', m);
        console.log('start_url:', m.start_url);
        console.log('scope:', m.scope);
        console.log('id:', m.id);
    });
}

// Verificar SW
navigator.serviceWorker.getRegistrations().then(regs => {
    console.log('SW Registrations:', regs);
    if (regs.length > 0) {
        console.log('SW Scope:', regs[0].scope);
        console.log('SW Active:', regs[0].active?.state);
    }
});
navigator.serviceWorker.ready.then(() => {
    console.log('SW Ready:', navigator.serviceWorker.controller?.scriptURL);
});

// Verificar beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('✅ beforeinstallprompt disparado!', new Date().toISOString());
});
        </pre>
    </div>
    
    <div class="summary" style="background: #d1ecf1; border: 2px solid #0c5460;">
        <h2>🧹 Limpar Dados do Site (para testes)</h2>
        <p>Se precisar testar instalação do zero:</p>
        <ol>
            <li>Abra DevTools (F12)</li>
            <li>Vá em <strong>Application</strong> (ou Aplicativo)</li>
            <li>No menu esquerdo, clique em <strong>Clear storage</strong> (ou Limpar armazenamento)</li>
            <li>Marque todas as opções</li>
            <li>Clique em <strong>Clear site data</strong> (ou Limpar dados do site)</li>
            <li>Recarregue a página (F5)</li>
        </ol>
    </div>
    
    <div id="checks-container"></div>
    
    <script>
        const checks = [];
        const currentUrl = window.location.href;
        const currentPath = window.location.pathname;
        const userType = new URLSearchParams(window.location.search).get('type') || 'instrutor';
        
        // Preencher informações básicas
        document.getElementById('current-url').textContent = currentUrl;
        document.getElementById('current-time').textContent = new Date().toLocaleString('pt-BR');
        document.getElementById('user-agent').textContent = navigator.userAgent;
        document.getElementById('current-type').textContent = type === 'aluno' ? 'Aluno' : 'Instrutor';
        
        function addCheck(title, status, details = '') {
            checks.push({ title, status, details });
        }
        
        function renderChecks() {
            const container = document.getElementById('checks-container');
            checks.forEach(check => {
                const div = document.createElement('div');
                div.className = `check-item ${check.status}`;
                div.innerHTML = `
                    <div class="check-title">
                        ${check.title}
                        <span class="status ${check.status}">${check.status.toUpperCase()}</span>
                    </div>
                    ${check.details ? `<div class="check-details">${check.details}</div>` : ''}
                `;
                container.appendChild(div);
            });
        }
        
        async function runChecks() {
            console.log('🔍 Iniciando diagnóstico PWA...');
            
            // 1. Service Worker - Verificação completa (evitar falso negativo)
            const hasController = !!navigator.serviceWorker.controller;
            let swRegistration = null;
            let swReady = false;
            
            try {
                const regs = await navigator.serviceWorker.getRegistrations();
                if (regs.length > 0) {
                    swRegistration = regs[0];
                    addCheck(
                        'Service Worker Registration',
                        'pass',
                        `✅ SW registrado: ${swRegistration.scope} (estado: ${swRegistration.active?.state || swRegistration.installing?.state || swRegistration.waiting?.state || 'unknown'})`
                    );
                } else {
                    addCheck('Service Worker Registration', 'fail', '❌ Nenhum SW registrado');
                }
            } catch (e) {
                addCheck('Service Worker Registration', 'fail', `❌ Erro: ${e.message}`);
            }
            
            try {
                await navigator.serviceWorker.ready;
                swReady = true;
                addCheck('Service Worker Ready', 'pass', '✅ SW pronto e ativo');
            } catch (e) {
                addCheck('Service Worker Ready', 'fail', `❌ SW não está pronto: ${e.message}`);
            }
            
            if (hasController) {
                addCheck(
                    'Service Worker Controller',
                    'pass',
                    `✅ SW controlando: ${navigator.serviceWorker.controller.scriptURL}`
                );
            } else {
                if (swRegistration && swReady) {
                    addCheck(
                        'Service Worker Controller',
                        'warn',
                        '⚠️ SW registrado e pronto, mas ainda não controlando esta aba. <strong>Recarregue a página (F5)</strong> para o SW assumir controle.'
                    );
                } else {
                    addCheck(
                        'Service Worker Controller',
                        'fail',
                        '❌ SW não está controlando a página'
                    );
                }
            }
            console.log('1. SW Controller:', hasController, 'Registration:', swRegistration, 'Ready:', swReady);
            
            // 2. Manifest Link (opcional - pode não existir nesta página)
            const manifestLink = document.querySelector('link[rel="manifest"]');
            if (manifestLink) {
                addCheck('Manifest Link no HTML', 'pass', `✅ Encontrado: ${manifestLink.href}`);
            } else {
                addCheck('Manifest Link no HTML', 'warn', '⚠️ Não encontrado nesta página (normal se estiver em admin/tools)');
            }
            
            // 3. Determinar manifest correto baseado no type
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type') || 'instrutor';
            const manifestUrl = type === 'aluno' 
                ? '/pwa/manifest-aluno.json' 
                : '/pwa/manifest-instrutor.json';
            
            addCheck('Manifest Esperado (via type)', 'pass', `✅ ${type} → ${manifestUrl}`);
            
            // 4. Fetch Manifest (fonte da verdade)
            try {
                const manifestRes = await fetch(manifestUrl, {cache: 'no-store'});
                const manifestStatus = manifestRes.status;
                const manifestContentType = manifestRes.headers.get('content-type');
                
                if (manifestStatus !== 200) {
                    addCheck('Manifest HTTP Status (fetch)', 'fail', `❌ Status: ${manifestStatus}`);
                } else if (!manifestContentType || !manifestContentType.includes('json')) {
                    addCheck('Manifest Content-Type (fetch)', 'fail', `❌ Content-Type: ${manifestContentType || 'N/A'}`);
                } else {
                    addCheck('Manifest HTTP Status (fetch)', 'pass', `✅ Status: ${manifestStatus}`);
                    addCheck('Manifest Content-Type (fetch)', 'pass', `✅ ${manifestContentType}`);
                    
                    // 5. Parse Manifest JSON
                    const manifestData = await manifestRes.json();
                    console.log('📋 Manifest Data:', manifestData);
                        
                        // Validar campos obrigatórios
                        const requiredFields = ['name', 'short_name', 'start_url', 'scope', 'display', 'icons'];
                        requiredFields.forEach(field => {
                            if (!manifestData[field]) {
                                addCheck(`Manifest: ${field}`, 'fail', `❌ Campo ausente`);
                            } else {
                                let value = manifestData[field];
                                if (field === 'icons') {
                                    value = `${value.length} ícone(s)`;
                                }
                                addCheck(`Manifest: ${field}`, 'pass', `✅ ${value}`);
                            }
                        });
                        
                        // Verificar id
                        if (manifestData.id) {
                            addCheck('Manifest: id', 'pass', `✅ ${manifestData.id}`);
                        } else {
                            addCheck('Manifest: id', 'warn', '⚠️ Sem id (pode causar conflito)');
                        }
                        
                        // 5. Testar start_url
                        try {
                            const startUrl = new URL(manifestData.start_url, window.location.origin).href;
                            const startRes = await fetch(startUrl, {method: 'HEAD', redirect: 'manual'});
                            if (startRes.status === 200) {
                                addCheck('start_url HTTP Status', 'pass', `✅ ${startRes.status} OK`);
                            } else if (startRes.status >= 300 && startRes.status < 400) {
                                addCheck('start_url HTTP Status', 'warn', `⚠️ ${startRes.status} (redirect pode afetar elegibilidade)`);
                            } else {
                                addCheck('start_url HTTP Status', 'fail', `❌ ${startRes.status}`);
                            }
                        } catch (e) {
                            addCheck('start_url HTTP Status', 'fail', `❌ Erro: ${e.message}`);
                        }
                        
                        // 6. Verificar ícones
                        if (manifestData.icons && manifestData.icons.length > 0) {
                            const iconChecks = [];
                            for (const icon of manifestData.icons) {
                                try {
                                    const iconUrl = new URL(icon.src, window.location.origin).href;
                                    const iconRes = await fetch(iconUrl, {method: 'HEAD', cache: 'no-store'});
                                    if (iconRes.status === 200) {
                                        const iconType = iconRes.headers.get('content-type');
                                        iconChecks.push(`✅ ${icon.sizes} (${icon.purpose || 'any'}): ${iconRes.status} ${iconType || ''}`);
                                    } else {
                                        iconChecks.push(`❌ ${icon.sizes}: Status ${iconRes.status}`);
                                    }
                                } catch (e) {
                                    iconChecks.push(`❌ ${icon.sizes}: Erro ${e.message}`);
                                }
                            }
                            addCheck('Ícones HTTP Status', iconChecks.some(c => c.startsWith('❌')) ? 'fail' : 'pass', iconChecks.join('<br>'));
                        }
                        
                        // 7. Verificar scope vs currentUrl
                        const scope = manifestData.scope || '/';
                        const scopeUrl = new URL(scope, window.location.origin);
                        const currentUrlObj = new URL(window.location.href);
                        const inScope = currentUrlObj.pathname.startsWith(scopeUrl.pathname);
                        addCheck(
                            'URL atual no scope',
                            inScope ? 'pass' : 'warn',
                            inScope 
                                ? `✅ ${currentUrlObj.pathname} está em ${scope}`
                                : `⚠️ ${currentUrlObj.pathname} pode não estar em ${scope}`
                        );
                    }
                } catch (e) {
                    addCheck('Manifest Parse', 'fail', `❌ Erro: ${e.message}`);
                }
            }
            
            // 8. HTTPS/Secure Context
            const isSecure = window.isSecureContext;
            addCheck(
                'HTTPS/Secure Context',
                isSecure ? 'pass' : 'fail',
                isSecure ? '✅ Contexto seguro' : '❌ Não está em contexto seguro'
            );
            
            // 9. Display Mode
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            addCheck(
                'Display Mode (Standalone)',
                isStandalone ? 'warn' : 'pass',
                isStandalone ? '⚠️ App já está instalado' : '✅ Não instalado (pode instalar)'
            );
            
            // 10. getInstalledRelatedApps
            if ('getInstalledRelatedApps' in navigator) {
                try {
                    const relatedApps = await navigator.getInstalledRelatedApps();
                    if (relatedApps && relatedApps.length > 0) {
                        addCheck('getInstalledRelatedApps', 'warn', `⚠️ Apps relacionados instalados: ${JSON.stringify(relatedApps)}`);
                    } else {
                        addCheck('getInstalledRelatedApps', 'pass', '✅ Nenhum app relacionado instalado');
                    }
                } catch (e) {
                    addCheck('getInstalledRelatedApps', 'warn', `⚠️ Erro: ${e.message}`);
                }
            } else {
                addCheck('getInstalledRelatedApps', 'warn', '⚠️ API não disponível neste navegador');
            }
            
            // 11. beforeinstallprompt Event (com explicação detalhada)
            let beforeinstallpromptFired = false;
            let beforeinstallpromptTimestamp = null;
            
            const beforeinstallpromptHandler = (e) => {
                beforeinstallpromptFired = true;
                beforeinstallpromptTimestamp = new Date().toISOString();
                console.log('✅ beforeinstallprompt disparado!', e);
                addCheck(
                    'beforeinstallprompt Event',
                    'pass',
                    `✅ Disparado em ${beforeinstallpromptTimestamp}`
                );
                renderChecks();
            };
            
            window.addEventListener('beforeinstallprompt', beforeinstallpromptHandler);
            
            // Verificar se já está instalado
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            let installedRelatedAppsInfo = '';
            if ('getInstalledRelatedApps' in navigator) {
                try {
                    const relatedApps = await navigator.getInstalledRelatedApps();
                    if (relatedApps && relatedApps.length > 0) {
                        installedRelatedAppsInfo = ` Apps relacionados instalados: ${JSON.stringify(relatedApps)}.`;
                    }
                } catch (e) {
                    // Ignorar erro
                }
            }
            
            // Aguardar 5 segundos para ver se dispara
            setTimeout(() => {
                if (!beforeinstallpromptFired) {
                    let reason = '❌ Não disparou após 5 segundos.';
                    const reasons = [];
                    
                    if (isStandalone) {
                        reasons.push('App já está instalado como PWA');
                    }
                    if (installedRelatedAppsInfo) {
                        reasons.push(installedRelatedAppsInfo);
                    }
                    if (!hasController && (!swRegistration || !swReady)) {
                        reasons.push('Service Worker não está controlando');
                    }
                    if (reasons.length === 0) {
                        reasons.push('Possível cooldown do Chrome (usuário cancelou instalação recentemente)');
                        reasons.push('Ou requisitos não totalmente atendidos');
                    }
                    
                    addCheck(
                        'beforeinstallprompt Event',
                        'fail',
                        `${reason}<br><strong>Possíveis causas:</strong><ul style="margin: 10px 0; padding-left: 20px;">${reasons.map(r => `<li>${r}</li>`).join('')}</ul>`
                    );
                    renderChecks();
                }
                window.removeEventListener('beforeinstallprompt', beforeinstallpromptHandler);
            }, 5000);
            
            // Renderizar checks iniciais
            renderChecks();
        }
        
        // Executar quando DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runChecks);
        } else {
            runChecks();
        }
    </script>
</body>
</html>
