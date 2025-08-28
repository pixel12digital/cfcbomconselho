<?php
// Teste de URLs das APIs - Ambiente de Produção
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de URLs das APIs - Sistema CFC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .url { font-family: monospace; background-color: #f8f9fa; padding: 5px; border-radius: 3px; }
        .response { font-family: monospace; background-color: #f8f9fa; padding: 10px; border-radius: 3px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Teste de URLs das APIs - Sistema CFC</h1>
    
    <div class="test-section info">
        <h3>📍 Informações do Ambiente</h3>
        <p><strong>URL Atual:</strong> <span class="url"><?php echo $_SERVER['REQUEST_URI']; ?></span></p>
        <p><strong>Host:</strong> <span class="url"><?php echo $_SERVER['HTTP_HOST']; ?></span></p>
        <p><strong>Script:</strong> <span class="url"><?php echo $_SERVER['SCRIPT_NAME']; ?></span></p>
        <p><strong>Document Root:</strong> <span class="url"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></span></p>
    </div>

    <div class="test-section info">
        <h3>🔧 URLs das APIs para Teste</h3>
        <p><strong>Instrutores:</strong> <span class="url">admin/api/instrutores.php</span></p>
        <p><strong>Usuários:</strong> <span class="url">admin/api/usuarios.php</span></p>
        <p><strong>CFCs:</strong> <span class="url">admin/api/cfcs.php</span></p>
    </div>

    <div class="test-section">
        <h3>🧪 Teste de Acesso às APIs</h3>
        <div id="test-results"></div>
    </div>

    <script>
        // Configuração das APIs
        const API_CONFIG = {
            ENDPOINTS: {
                INSTRUTORES: 'admin/api/instrutores.php',
                USUARIOS: 'admin/api/usuarios.php',
                CFCs: 'admin/api/cfcs.php'
            },
            getRelativeApiUrl: function(endpoint) {
                return this.ENDPOINTS[endpoint];
            }
        };

        // Função para testar uma API
        async function testAPI(name, endpoint) {
            const url = API_CONFIG.getRelativeApiUrl(endpoint);
            const resultsDiv = document.getElementById('test-results');
            
            try {
                console.log(`🧪 Testando ${name}: ${url}`);
                
                const response = await fetch(url);
                const status = response.status;
                const statusText = response.statusText;
                
                let resultClass = 'success';
                let resultIcon = '✅';
                let resultText = `Sucesso: ${status} ${statusText}`;
                
                if (status !== 200) {
                    resultClass = 'error';
                    resultIcon = '❌';
                    resultText = `Erro: ${status} ${statusText}`;
                }
                
                const resultDiv = document.createElement('div');
                resultDiv.className = `test-section ${resultClass}`;
                resultDiv.innerHTML = `
                    <h4>${resultIcon} ${name}</h4>
                    <p><strong>URL:</strong> <span class="url">${url}</span></p>
                    <p><strong>Status:</strong> ${resultText}</p>
                    <p><strong>URL Completa:</strong> <span class="url">${window.location.origin}/${url}</span></p>
                `;
                
                resultsDiv.appendChild(resultDiv);
                
                // Tentar obter o conteúdo da resposta
                try {
                    const text = await response.text();
                    if (text.length > 200) {
                        text = text.substring(0, 200) + '...';
                    }
                    
                    const contentDiv = document.createElement('div');
                    contentDiv.className = 'response';
                    contentDiv.innerHTML = `<strong>Conteúdo da Resposta:</strong><br>${text}`;
                    resultDiv.appendChild(contentDiv);
                } catch (e) {
                    console.log(`Erro ao ler resposta de ${name}:`, e);
                }
                
            } catch (error) {
                const resultDiv = document.createElement('div');
                resultDiv.className = 'test-section error';
                resultDiv.innerHTML = `
                    <h4>❌ ${name} - Erro de Rede</h4>
                    <p><strong>URL:</strong> <span class="url">${url}</span></p>
                    <p><strong>Erro:</strong> ${error.message}</p>
                `;
                resultsDiv.appendChild(resultDiv);
            }
        }

        // Executar testes quando a página carregar
        window.addEventListener('load', async () => {
            console.log('🚀 Iniciando testes das APIs...');
            
            await testAPI('Instrutores', 'INSTRUTORES');
            await testAPI('Usuários', 'USUARIOS');
            await testAPI('CFCs', 'CFCs');
            
            console.log('✅ Testes concluídos!');
        });
    </script>
</body>
</html>
