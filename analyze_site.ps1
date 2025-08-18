try {
    Write-Host "Analisando o site e-condutor CFC..." -ForegroundColor Green
    
    # Fazer requisição para o site
    $response = Invoke-WebRequest -Uri "https://econdutorcfc.com/" -UseBasicParsing
    
    # Salvar o HTML completo
    $response.Content | Out-File "econdutor_source.html" -Encoding UTF8
    Write-Host "HTML salvo em econdutor_source.html" -ForegroundColor Green
    
    # Analisar estrutura básica
    Write-Host "`n=== ANÁLISE DA ESTRUTURA ===" -ForegroundColor Yellow
    
    # Verificar título
    if ($response.ParsedHtml.title) {
        Write-Host "Título: $($response.ParsedHtml.title)" -ForegroundColor Cyan
    }
    
    # Verificar meta tags
    $metaTags = $response.ParsedHtml.getElementsByTagName("meta")
    Write-Host "Meta tags encontradas: $($metaTags.Count)" -ForegroundColor Cyan
    
    # Verificar scripts
    $scripts = $response.ParsedHtml.getElementsByTagName("script")
    Write-Host "Scripts encontrados: $($scripts.Count)" -ForegroundColor Cyan
    
    # Verificar CSS
    $css = $response.ParsedHtml.getElementsByTagName("link") | Where-Object { $_.rel -eq "stylesheet" }
    Write-Host "Arquivos CSS encontrados: $($css.Count)" -ForegroundColor Cyan
    
    # Verificar formulários
    $forms = $response.ParsedHtml.getElementsByTagName("form")
    Write-Host "Formulários encontrados: $($forms.Count)" -ForegroundColor Cyan
    
    # Verificar inputs
    $inputs = $response.ParsedHtml.getElementsByTagName("input")
    Write-Host "Campos de input encontrados: $($inputs.Count)" -ForegroundColor Cyan
    
    # Verificar se há elementos específicos do sistema
    $loginElements = $response.ParsedHtml.getElementsByTagName("*") | Where-Object { 
        $_.className -like "*login*" -or $_.id -like "*login*" -or $_.innerText -like "*login*" 
    }
    Write-Host "Elementos relacionados ao login: $($loginElements.Count)" -ForegroundColor Cyan
    
    # Salvar análise detalhada
    $analysis = @"
=== ANÁLISE DETALHADA DO SISTEMA E-CONDUTOR CFC ===
Data: $(Get-Date)
URL: https://econdutorcfc.com/

ESTRUTURA HTML:
- Título: $($response.ParsedHtml.title)
- Meta tags: $($metaTags.Count)
- Scripts: $($scripts.Count)
- CSS: $($css.Count)
- Formulários: $($forms.Count)
- Inputs: $($inputs.Count)
- Elementos de login: $($loginElements.Count)

HEADERS DA RESPOSTA:
$($response.Headers | ConvertTo-Json)

STATUS: $($response.StatusCode)
"@
    
    $analysis | Out-File "econdutor_analysis.txt" -Encoding UTF8
    Write-Host "`nAnálise detalhada salva em econdutor_analysis.txt" -ForegroundColor Green
    
} catch {
    Write-Host "Erro durante a análise: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Stack trace: $($_.Exception.StackTrace)" -ForegroundColor Red
}
