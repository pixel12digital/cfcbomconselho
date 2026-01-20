# Solução - Erro 403 EFI "Invalid key=value pair"

## Problema

OAuth funciona (obtém token), mas requisições à API retornam HTTP 403:
```
"Invalid key=value pair (missing equal-sign) in Authorization header (hashed with SHA-256 and encoded with Base64)"
```

## Causa Raiz

O certificado cliente (.p12) **deve corresponder exatamente às credenciais** (Client ID/Secret) usadas. Se o certificado foi gerado para credenciais diferentes, a API rejeita o header Authorization.

## Solução

### 1. Verificar Correspondência Certificado ↔ Credenciais

O certificado deve ter sido gerado para as **mesmas credenciais** que estão no `.env`:

1. Acesse: https://dev.gerencianet.com.br/
2. Vá em: **API → Meus Certificados → Produção**
3. Verifique qual **Client ID** está associado ao certificado
4. Compare com o `EFI_CLIENT_ID` no seu `.env`

**Se não corresponderem:**
- Baixe um novo certificado para as credenciais corretas, OU
- Use as credenciais que correspondem ao certificado atual

### 2. Verificar Formato do Certificado

O certificado deve ser:
- Formato: `.p12` (PFX)
- Ambiente: **Produção** (não Homologação)
- Validade: Certificado não expirado

### 3. Verificar Configuração no .env

```env
EFI_CLIENT_ID=Client_Id_ef3cc7db59...  # Deve corresponder ao certificado
EFI_CLIENT_SECRET=Client_Secret_...     # Deve corresponder ao certificado
EFI_SANDBOX=false                       # Produção
EFI_CERT_PATH=C:\xampp\htdocs\cfc-v.1\certificados\certificado.p12
EFI_CERT_PASSWORD=                      # Se o certificado tiver senha
```

### 4. Testar Certificado

Execute o script de diagnóstico:
```
http://localhost/cfc-v.1/public_html/tools/diagnostico_erro_403_efi.php
```

## Verificações Adicionais

### Certificado Correto
- ✅ Certificado de **Produção** (não Sandbox/Homologação)
- ✅ Certificado gerado para o **mesmo Client ID** do `.env`
- ✅ Certificado não expirado
- ✅ Arquivo `.p12` válido e acessível

### Credenciais Corretas
- ✅ `EFI_CLIENT_ID` corresponde ao certificado
- ✅ `EFI_CLIENT_SECRET` corresponde ao certificado
- ✅ Credenciais de **Produção** (não Sandbox)

### Configuração
- ✅ `EFI_SANDBOX=false` para produção
- ✅ `EFI_CERT_PATH` com caminho absoluto correto
- ✅ Certificado existe no caminho especificado
- ✅ Se certificado tiver senha, `EFI_CERT_PASSWORD` configurado

## Próximos Passos

1. **Verificar correspondência** certificado ↔ credenciais na dashboard EFI
2. **Baixar novo certificado** se necessário (para as credenciais corretas)
3. **Atualizar `.env`** com o caminho correto do certificado
4. **Reiniciar servidor web**
5. **Testar novamente** a geração de cobrança

## Contato com Suporte EFI

Se o problema persistir após verificar tudo acima:
- Acesse: https://dev.gerencianet.com.br/
- Abra um chamado no suporte
- Informe: "Erro 403 - Invalid key=value pair in Authorization header"
- Forneça: Client ID e data/hora do erro
