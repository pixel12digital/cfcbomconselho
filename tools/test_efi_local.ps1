# Script PowerShell para Testar Autenticacao EFI Localmente
# Uso: .\tools\test_efi_local.ps1

Write-Host "Teste de Autenticacao EFI - Local" -ForegroundColor Cyan
Write-Host ("=" * 70) -ForegroundColor Cyan
Write-Host ""

# Ler variaveis do .env
$envFile = ".\.env"
if (-not (Test-Path $envFile)) {
    Write-Host "ERRO: Arquivo .env nao encontrado!" -ForegroundColor Red
    exit 1
}

Write-Host "Lendo configuracoes do .env..." -ForegroundColor Yellow

$envContent = Get-Content $envFile
$config = @{}

foreach ($line in $envContent) {
    if ($line -match '^\s*([^#=]+)=(.*)$') {
        $key = $matches[1].Trim()
        $value = $matches[2].Trim()
        $config[$key] = $value
    }
}

$clientId = $config['EFI_CLIENT_ID']
$clientSecret = $config['EFI_CLIENT_SECRET']
$certPath = $config['EFI_CERT_PATH']
$certPassword = $config['EFI_CERT_PASSWORD']
if ($config['EFI_SANDBOX'] -eq 'false') {
    $sandbox = $false
} else {
    $sandbox = $true
}

Write-Host ""
Write-Host "Configuracao:" -ForegroundColor Yellow
Write-Host "   CLIENT_ID: $($clientId.Length) caracteres | Tail: $($clientId.Substring([Math]::Max(0, $clientId.Length - 6)))"
Write-Host "   CLIENT_SECRET: $($clientSecret.Length) caracteres | Tail: $($clientSecret.Substring([Math]::Max(0, $clientSecret.Length - 6)))"
Write-Host "   CERT_PATH: $certPath"
if ($certPath) {
    Write-Host "   CERT_EXISTS: $(if (Test-Path $certPath) { 'SIM' } else { 'NAO' })"
} else {
    Write-Host "   CERT_EXISTS: NAO CONFIGURADO"
}
Write-Host "   CERT_PASSWORD: $(if ($certPassword) { 'Configurada' } else { 'Nao configurada' })"
Write-Host "   SANDBOX: $(if ($sandbox) { 'true (SANDBOX)' } else { 'false (PRODUCAO)' })"
Write-Host ""

if (-not $clientId -or -not $clientSecret) {
    Write-Host "ERRO: Credenciais nao configuradas no .env!" -ForegroundColor Red
    exit 1
}

# URL do OAuth
$oauthUrl = if ($sandbox) { 
    "https://sandbox.gerencianet.com.br/oauth/token" 
} else { 
    "https://apis.gerencianet.com.br/oauth/token" 
}

Write-Host "Testando autenticacao..." -ForegroundColor Yellow
Write-Host "   URL: $oauthUrl"
Write-Host ""

# Criar credenciais Basic Auth
$authString = "$clientId`:$clientSecret"
$authBytes = [System.Text.Encoding]::UTF8.GetBytes($authString)
$authBase64 = [Convert]::ToBase64String($authBytes)

# Body
$body = "grant_type=client_credentials"

Write-Host "Enviando requisicao..." -ForegroundColor Yellow

# Usar curl.exe se disponivel
$curlPath = Get-Command curl.exe -ErrorAction SilentlyContinue
if ($curlPath) {
    Write-Host "   Usando curl.exe..." -ForegroundColor Gray
    
    if ($certPath -and (Test-Path $certPath)) {
        Write-Host "   Usando certificado: $certPath" -ForegroundColor Gray
        
        $curlArgs = @(
            "-X", "POST",
            $oauthUrl,
            "-H", "Content-Type: application/x-www-form-urlencoded",
            "-H", "Authorization: Basic $authBase64",
            "-d", $body,
            "--cert", $certPath,
            "--cert-type", "P12",
            "--verbose"
        )
        
        if ($certPassword) {
            $curlArgs += "--pass", $certPassword
        }
        
        Write-Host ""
        & curl.exe $curlArgs
        Write-Host ""
    } else {
        Write-Host "   AVISO: Certificado nao encontrado. Tentando sem certificado..." -ForegroundColor Yellow
        Write-Host ""
        & curl.exe -X POST $oauthUrl `
            -H "Content-Type: application/x-www-form-urlencoded" `
            -H "Authorization: Basic $authBase64" `
            -d $body `
            --verbose
        Write-Host ""
    }
} else {
    Write-Host "   ERRO: curl.exe nao encontrado!" -ForegroundColor Red
    Write-Host "   Instale Git for Windows ou use o script PHP no servidor." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "   Tentando com Invoke-WebRequest (sem certificado)..." -ForegroundColor Yellow
    
    $headers = @{
        "Content-Type" = "application/x-www-form-urlencoded"
        "Authorization" = "Basic $authBase64"
    }
    
    try {
        $response = Invoke-WebRequest -Uri $oauthUrl -Method POST -Headers $headers -Body $body -UseBasicParsing
        Write-Host "   HTTP Code: $($response.StatusCode)" -ForegroundColor $(if ($response.StatusCode -eq 200) { "Green" } else { "Red" })
        Write-Host "   Response: $($response.Content)"
    } catch {
        Write-Host "   ERRO: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
            $statusCode = $_.Exception.Response.StatusCode.value__
            Write-Host "   HTTP Code: $statusCode" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host ("=" * 70) -ForegroundColor Cyan
