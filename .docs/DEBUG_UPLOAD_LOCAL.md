# Debug Upload Local - Passo a Passo

## Problema Reportado
Logo não foi salvo ou não foi exibido após upload.

## Diagnóstico

### 1. Verificar Console do Navegador

Abra o Console (F12) e procure por:
- `[UPLOAD DEBUG]` - logs do JavaScript
- Erros em vermelho
- Mensagens de sucesso/erro

### 2. Verificar se o Form está sendo encontrado

No console, você deve ver:
```
[UPLOAD DEBUG] Form encontrado: true
[UPLOAD DEBUG] Form action: http://localhost/cfc-v.1/public_html/configuracoes/cfc/logo/upload
[UPLOAD DEBUG] Form method: POST
[UPLOAD DEBUG] Form enctype: multipart/form-data
```

### 3. Verificar se o Submit está sendo interceptado

Ao clicar em "Fazer Upload", você deve ver:
```
[UPLOAD DEBUG] ========================================
[UPLOAD DEBUG] Form submit iniciado
[UPLOAD DEBUG] Action: ...
[UPLOAD DEBUG] Arquivo selecionado: {...}
[UPLOAD DEBUG] ✅ Validações passaram, enviando requisição...
```

### 4. Verificar Resposta do Servidor

Após enviar, você deve ver:
```
[UPLOAD DEBUG] Resposta recebida
[UPLOAD DEBUG] Status: 302
[UPLOAD DEBUG] Headers de debug: {...}
```

### 5. Verificar Logs do Servidor

Execute no PowerShell:
```powershell
Get-Content "storage\logs\upload_logo.log" -Tail 50
```

### 6. Verificar Arquivos Salvos

Execute no PowerShell:
```powershell
Get-ChildItem "storage\uploads\cfcs" | Sort-Object LastWriteTime -Descending
```

## Possíveis Problemas e Soluções

### ❌ Console mostra: "Form não encontrado"
**Causa:** JavaScript não está encontrando o formulário
**Solução:** Verifique se o HTML do form está correto

### ❌ Console mostra: "Nenhum arquivo selecionado"
**Causa:** Arquivo não foi selecionado antes do submit
**Solução:** Selecione um arquivo antes de clicar em "Fazer Upload"

### ❌ Console mostra erro de rede (CORS, 404, etc)
**Causa:** Rota não encontrada ou problema de rede
**Solução:** Verifique se a rota está correta em `app/routes/web.php`

### ❌ Log do servidor não existe
**Causa:** Método `uploadLogo()` não foi chamado
**Solução:** Verifique se a requisição está chegando ao servidor (Network tab no DevTools)

### ❌ Arquivo não aparece em `storage/uploads/cfcs`
**Causa:** `move_uploaded_file()` falhou
**Solução:** Verifique permissões do diretório e logs do servidor

### ❌ Logo não aparece na página após upload
**Causa:** Banco não foi atualizado ou view não está lendo corretamente
**Solução:** 
1. Verifique no banco: `SELECT logo_path FROM cfcs WHERE id = 1;`
2. Verifique se a view está usando `$hasLogo` corretamente

## Teste Manual (Sem JavaScript)

Se o JavaScript estiver com problemas, você pode testar diretamente:

1. **Desabilite temporariamente o JavaScript** (ou comente o `addEventListener`)
2. **Faça upload normalmente** (submit tradicional do form)
3. **Verifique se salva** (mesmo sem logs no console)

## Próximos Passos

1. Execute os testes acima
2. Me informe o que aparece no console
3. Me informe o que aparece nos logs do servidor
4. Me informe se o arquivo foi salvo em disco
