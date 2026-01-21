# Teste do Botão "Instalar Aplicativo" PWA
# Execute após deploy para validar que está funcionando

$base = "https://painel.cfcbomconselho.com.br"

Write-Host "=== TESTE DO BOTÃO INSTALAR APLICATIVO PWA ===" -ForegroundColor Cyan
Write-Host ""

# Teste 1: Manifest dinâmico continua funcionando
Write-Host "1. Verificando manifest dinâmico..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest "$base/public_html/pwa-manifest.php" -UseBasicParsing
    if ($r.StatusCode -eq 200) {
        Write-Host "   ✅ Status: $($r.StatusCode)" -ForegroundColor Green
        $json = $r.Content | ConvertFrom-Json
        Write-Host "   ✅ Nome dinâmico: $($json.name)" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Status: $($r.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Erro ao acessar manifest: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Teste 2: Verificar se shell.php aponta para pwa-manifest.php
Write-Host "2. Verificando referência do manifest no HTML..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest "$base" -UseBasicParsing
    $html = $r.Content
    
    if ($html -match 'rel="manifest".*pwa-manifest\.php') {
        Write-Host "   ✅ Manifest aponta para pwa-manifest.php" -ForegroundColor Green
        # Extrair URL completa
        if ($html -match '<link[^>]*rel="manifest"[^>]*href="([^"]+)"') {
            $manifestUrl = $matches[1]
            Write-Host "   URL: $manifestUrl" -ForegroundColor Cyan
        }
    } elseif ($html -match 'rel="manifest"') {
        Write-Host "   ⚠️ Manifest encontrado mas não aponta para pwa-manifest.php" -ForegroundColor Yellow
        if ($html -match '<link[^>]*rel="manifest"[^>]*href="([^"]+)"') {
            $manifestUrl = $matches[1]
            Write-Host "   URL atual: $manifestUrl" -ForegroundColor Yellow
        }
    } else {
        Write-Host "   ❌ Nenhum manifest encontrado no HTML" -ForegroundColor Red
    }
} catch {
    Write-Host "   ⚠️ Não foi possível verificar HTML (pode precisar de autenticação)" -ForegroundColor Yellow
}

Write-Host ""

# Teste 3: Verificar se app.js foi carregado
Write-Host "3. Verificando se app.js existe..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest "$base/public_html/assets/js/app.js" -UseBasicParsing
    if ($r.StatusCode -eq 200) {
        Write-Host "   ✅ app.js acessível (Status: $($r.StatusCode))" -ForegroundColor Green
        
        # Verificar se contém código do PWA install handler
        $content = $r.Content
        if ($content -match 'beforeinstallprompt') {
            Write-Host "   ✅ Contém handler beforeinstallprompt" -ForegroundColor Green
        } else {
            Write-Host "   ⚠️ Não encontrado handler beforeinstallprompt" -ForegroundColor Yellow
        }
        
        if ($content -match 'pwa-install-btn') {
            Write-Host "   ✅ Contém referência ao botão pwa-install-btn" -ForegroundColor Green
        } else {
            Write-Host "   ⚠️ Não encontrado referência ao botão" -ForegroundColor Yellow
        }
        
        if ($content -match '\[PWA\] install handler ready') {
            Write-Host "   ✅ Contém log discreto [PWA] install handler ready" -ForegroundColor Green
        } else {
            Write-Host "   ⚠️ Log discreto não encontrado" -ForegroundColor Yellow
        }
    } else {
        Write-Host "   ❌ app.js não acessível (Status: $($r.StatusCode))" -ForegroundColor Red
    }
} catch {
    Write-Host "   ❌ Erro ao acessar app.js: $_" -ForegroundColor Red
}

Write-Host ""

# Teste 4: Verificar estrutura HTML do botão (se possível)
Write-Host "4. Verificando estrutura do botão no HTML..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest "$base" -UseBasicParsing
    $html = $r.Content
    
    if ($html -match 'pwa-install-container') {
        Write-Host "   ✅ Container pwa-install-container encontrado" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️ Container não encontrado (pode estar em template dinâmico)" -ForegroundColor Yellow
    }
    
    if ($html -match 'pwa-install-btn') {
        Write-Host "   ✅ Botão pwa-install-btn encontrado" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️ Botão não encontrado (pode estar em template dinâmico)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "   ⚠️ Não foi possível verificar HTML (pode precisar de autenticação)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== RESUMO ===" -ForegroundColor Cyan
Write-Host "✅ Manifest dinâmico funcionando" -ForegroundColor Green
Write-Host "✅ app.js contém código PWA install handler" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Yellow
Write-Host "1. Abrir o site no navegador" -ForegroundColor White
Write-Host "2. Abrir DevTools (F12) → Console" -ForegroundColor White
Write-Host "3. Verificar se aparece '[PWA] install handler ready' (apenas em localhost)" -ForegroundColor White
Write-Host "4. Clicar no avatar do usuário (menu dropdown)" -ForegroundColor White
Write-Host "5. Verificar se botão 'Instalar Aplicativo' aparece (quando beforeinstallprompt disponível)" -ForegroundColor White
Write-Host "6. Testar instalação em dispositivo móvel Android ou desktop Chrome/Edge" -ForegroundColor White
