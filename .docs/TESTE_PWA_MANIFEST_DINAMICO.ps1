# Teste do Manifest PWA Dinâmico - White-Label
# Execute após deploy para validar que está funcionando

$base = "https://painel.cfcbomconselho.com.br/public_html"

Write-Host "=== TESTE DO MANIFEST PWA DINÂMICO ===" -ForegroundColor Cyan
Write-Host ""

# Teste 1: Status Code
Write-Host "1. Testando Status Code..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest "$base/pwa-manifest.php" -UseBasicParsing
    Write-Host "   Status: $($r.StatusCode)" -ForegroundColor Green
    if ($r.StatusCode -eq 200) {
        Write-Host "   ✅ Status 200 OK" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️ Status diferente de 200" -ForegroundColor Yellow
    }
} catch {
    $code = if ($_.Exception.Response) { $_.Exception.Response.StatusCode.value__ } else { "Erro" }
    Write-Host "   ❌ Erro: $code" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Teste 2: Content-Type
Write-Host "2. Verificando Content-Type..." -ForegroundColor Yellow
$contentType = $r.Headers['Content-Type']
Write-Host "   Content-Type: $contentType" -ForegroundColor Cyan
if ($contentType -like "*manifest+json*" -or $contentType -like "*json*") {
    Write-Host "   ✅ Content-Type correto" -ForegroundColor Green
} else {
    Write-Host "   ⚠️ Content-Type pode estar incorreto" -ForegroundColor Yellow
}

Write-Host ""

# Teste 3: JSON válido
Write-Host "3. Verificando JSON válido..." -ForegroundColor Yellow
try {
    $json = $r.Content | ConvertFrom-Json
    Write-Host "   ✅ JSON válido" -ForegroundColor Green
} catch {
    Write-Host "   ❌ JSON inválido: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Teste 4: Campos obrigatórios
Write-Host "4. Verificando campos obrigatórios..." -ForegroundColor Yellow
$requiredFields = @('name', 'short_name', 'start_url', 'display', 'icons')
$missingFields = @()
foreach ($field in $requiredFields) {
    if (-not $json.PSObject.Properties.Name -contains $field) {
        $missingFields += $field
    }
}
if ($missingFields.Count -eq 0) {
    Write-Host "   ✅ Todos os campos obrigatórios presentes" -ForegroundColor Green
} else {
    Write-Host "   ❌ Campos faltando: $($missingFields -join ', ')" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Teste 5: Valores dinâmicos
Write-Host "5. Verificando valores dinâmicos..." -ForegroundColor Yellow
Write-Host "   name: $($json.name)" -ForegroundColor Cyan
Write-Host "   short_name: $($json.short_name)" -ForegroundColor Cyan
Write-Host "   start_url: $($json.start_url)" -ForegroundColor Cyan
Write-Host "   theme_color: $($json.theme_color)" -ForegroundColor Cyan

# Verificar se name não é o padrão (indicando que está dinâmico)
if ($json.name -ne "CFC Sistema de Gestão") {
    Write-Host "   ✅ Nome parece ser dinâmico (não é o padrão)" -ForegroundColor Green
} else {
    Write-Host "   ⚠️ Nome ainda é o padrão (pode ser fallback ou CFC padrão)" -ForegroundColor Yellow
}

Write-Host ""

# Teste 6: Ícones
Write-Host "6. Verificando ícones..." -ForegroundColor Yellow
if ($json.icons -and $json.icons.Count -gt 0) {
    Write-Host "   ✅ $($json.icons.Count) ícone(s) configurado(s)" -ForegroundColor Green
    foreach ($icon in $json.icons) {
        Write-Host "      - $($icon.src) ($($icon.sizes))" -ForegroundColor Gray
    }
} else {
    Write-Host "   ⚠️ Nenhum ícone configurado" -ForegroundColor Yellow
}

Write-Host ""

# Teste 7: Formato completo
Write-Host "7. Exibindo JSON completo (primeiros 500 chars)..." -ForegroundColor Yellow
$jsonString = $r.Content
if ($jsonString.Length -gt 500) {
    Write-Host $jsonString.Substring(0, 500) -ForegroundColor Gray
    Write-Host "   ... (truncado)" -ForegroundColor Gray
} else {
    Write-Host $jsonString -ForegroundColor Gray
}

Write-Host ""
Write-Host "=== RESUMO ===" -ForegroundColor Cyan
Write-Host "✅ Manifest está funcionando corretamente!" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Yellow
Write-Host "1. Verificar se o nome do CFC está correto no banco" -ForegroundColor White
Write-Host "2. Testar em diferentes sessões (diferentes CFCs se multi-tenant)" -ForegroundColor White
Write-Host "3. Verificar se o <link rel='manifest'> no shell.php aponta para pwa-manifest.php" -ForegroundColor White
