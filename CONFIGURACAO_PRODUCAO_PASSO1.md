# ✅ PASSO 1: Criar Arquivo .env

## Localização
**Caminho completo:** `public_html/painel/.env`

**Como criar:**
1. No File Browser, você está em: `public_html/painel/`
2. Clique em "New file" na sidebar
3. Nome do arquivo: `.env` (com ponto no início)
   - ⚠️ Se não conseguir criar arquivo oculto, crie `env.txt` e depois renomeie

## Conteúdo do .env

Cole este conteúdo e **preencha com suas credenciais da Hostinger**:

```env
# ============================================
# CONFIGURAÇÃO DO BANCO DE DADOS (PRODUÇÃO)
# ============================================
# Obtenha no painel da Hostinger:
# Banco de Dados → Seu Banco → Detalhes
DB_HOST=localhost
DB_PORT=3306
DB_NAME=COLE_AQUI_NOME_DO_BANCO
DB_USER=COLE_AQUI_USUARIO_DO_BANCO
DB_PASS=COLE_AQUI_SENHA_DO_BANCO

# ============================================
# EFÍ (GERENCIANET) - GATEWAY DE PAGAMENTO
# ============================================
# Preencher apenas se usar gateway de pagamento
EFI_CLIENT_ID=seu_client_id_producao
EFI_CLIENT_SECRET=seu_client_secret_producao
EFI_SANDBOX=false
EFI_CERT_PATH=/caminho/completo/para/certificado.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret

# ============================================
# AMBIENTE
# ============================================
APP_ENV=production
```

## ⚠️ Onde obter as credenciais do banco?

1. Acesse o painel da Hostinger
2. Vá em **"Banco de Dados MySQL"** ou **"phpMyAdmin"**
3. Clique no seu banco de dados
4. Veja os detalhes:
   - **Nome do banco**
   - **Usuário do banco**
   - **Senha do banco** (ou resetar se não lembrar)
   - **Host**: geralmente `localhost` na Hostinger

## ✅ Após criar o .env

Após criar e salvar o `.env` com as credenciais, me avise para continuarmos com os ajustes no código.
