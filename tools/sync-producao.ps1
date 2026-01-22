# Script PowerShell para sincronizar código de produção com o repositório remoto
# Uso: .\sync-producao.ps1

Write-Host "🔄 Sincronizando código de produção..." -ForegroundColor Cyan

# Verificar se estamos no diretório correto
if (-not (Test-Path ".git")) {
    Write-Host "❌ Erro: Este script deve ser executado na raiz do projeto" -ForegroundColor Red
    exit 1
}

# Verificar status atual
Write-Host "`n📊 Verificando status do repositório..." -ForegroundColor Yellow
git status

# Fazer fetch do repositório de produção
Write-Host "`n📥 Fazendo fetch do repositório de produção..." -ForegroundColor Yellow
git fetch production

# Verificar se há diferenças
$localCommit = git rev-parse HEAD
$remoteCommit = git rev-parse production/master

if ($localCommit -eq $remoteCommit) {
    Write-Host "`n✅ Código local e produção estão sincronizados" -ForegroundColor Green
    Write-Host "Commit: $localCommit" -ForegroundColor Gray
} else {
    Write-Host "`n⚠️  Há diferenças entre local e produção" -ForegroundColor Yellow
    Write-Host "Local:   $localCommit" -ForegroundColor Gray
    Write-Host "Remoto:  $remoteCommit" -ForegroundColor Gray
    
    # Mostrar diferenças
    Write-Host "`n📋 Arquivos diferentes:" -ForegroundColor Yellow
    git diff --name-status HEAD production/master
    
    # Perguntar se deseja fazer pull
    $response = Read-Host "`nDeseja fazer pull do repositório de produção? (s/N)"
    if ($response -eq "s" -or $response -eq "S") {
        Write-Host "`n📥 Fazendo pull do repositório de produção..." -ForegroundColor Yellow
        git pull production master
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Pull realizado com sucesso!" -ForegroundColor Green
        } else {
            Write-Host "❌ Erro ao fazer pull. Verifique os conflitos." -ForegroundColor Red
            exit 1
        }
    }
}

# Verificar se há mudanças locais não commitadas
$status = git status --porcelain
if ($status) {
    Write-Host "`n⚠️  Há mudanças locais não commitadas:" -ForegroundColor Yellow
    git status --short
    
    $response = Read-Host "`nDeseja ver as diferenças? (s/N)"
    if ($response -eq "s" -or $response -eq "S") {
        git diff
    }
} else {
    Write-Host "`n✅ Não há mudanças locais não commitadas" -ForegroundColor Green
}

Write-Host "`n✅ Sincronização concluída!" -ForegroundColor Green
