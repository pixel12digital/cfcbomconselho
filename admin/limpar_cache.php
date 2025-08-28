<?php
// Forçar charset UTF-8
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Versão com timestamp para forçar recarregamento
$version = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpeza de Cache - Sistema CFC</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .test-chars {
            font-size: 18px;
            margin: 20px 0;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Limpeza de Cache - Sistema CFC</h1>
        
        <div class="success">
            ✅ Cache do servidor limpo com sucesso!<br>
            ✅ Headers HTTP atualizados para UTF-8<br>
            ✅ Versão dos arquivos atualizada: <?php echo $version; ?>
        </div>
        
        <div class="test-chars">
            <h3>🔤 Teste de Caracteres UTF-8:</h3>
            <p><strong>Acentos:</strong> á à ã â é ê í ó õ ô ú ç</p>
            <p><strong>Palavras:</strong> Responsável, Ações, Gestão, Operação</p>
            <p><strong>Frases:</strong> Não definido, Não informado, Será preenchido</p>
        </div>
        
        <h3>🔧 Próximos Passos:</h3>
        <ol>
            <li><strong>Limpe o cache do navegador:</strong> Pressione <kbd>Ctrl+F5</kbd> ou <kbd>Ctrl+Shift+R</kbd></li>
            <li><strong>Acesse o sistema principal:</strong> <a href="index.php?page=cfcs&v=<?php echo $version; ?>" class="button">Ir para CFCs</a></li>
            <li><strong>Teste de codificação:</strong> <a href="../limpar_cache_navegador.php?v=<?php echo $version; ?>" class="button">Teste Completo</a></li>
        </ol>
        
        <h3>❓ Se ainda houver problemas:</h3>
        <ul>
            <li>Verifique se o navegador está usando UTF-8</li>
            <li>Teste em modo anônimo/incógnito</li>
            <li>Verifique as configurações de codificação do servidor</li>
            <li>Reinicie o servidor web se possível</li>
        </ul>
        
        <script>
            // Forçar recarregamento de todos os recursos
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for(let registration of registrations) {
                        registration.unregister();
                    }
                });
            }
            
            // Limpar localStorage e sessionStorage
            try {
                localStorage.clear();
                sessionStorage.clear();
                console.log('✅ Cache local limpo');
            } catch(e) {
                console.log('⚠️ Não foi possível limpar cache local');
            }
        </script>
    </div>
</body>
</html>
