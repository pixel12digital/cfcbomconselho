if (Test-Path test-asaas-key.php) {
    $c = Get-Content test-asaas-key.php -Raw
    $old = '$aact_prod_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjFkZGExMjcyLWMzN2MtNGM3MS1iMTBmLTY4YWU4MjM4ZmE1Nzo6JGFhY2hfM2EzNTI4OTUtOGFjNC00MmFlLTliZTItNjRkZDg2YTAzOWRj'
    $new = 'Env::get(''ASAAS_API_KEY'')'
    if ($c.Contains($old)) {
        $c = $c.Replace($old, $new)
        [System.IO.File]::WriteAllText((Resolve-Path test-asaas-key.php), $c, [System.Text.Encoding]::UTF8)
        git add test-asaas-key.php
        Write-Host "Chave API substituída e arquivo adicionado ao git"
    } else {
        Write-Host "Chave antiga não encontrada no arquivo"
    }
} else {
    Write-Host "Arquivo test-asaas-key.php não encontrado"
}
