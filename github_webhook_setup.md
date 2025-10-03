# 🚀 Setup Webhook GitHub → Hostinger (Alternativo)

## Configuração Direta via GitHub Webhooks

Se o Git SSH não funcionar na Hostinger, configure webhook manual:

### 1. Configurar Webhook no GitHub

1. **Acesse:** https://github.com/pixel12digital/cfcbomconselho/settings/hooks
2. **Clique:** "Add webhook"
3. **Preencha:**
   ```
   Payload URL: https://cfcbomconselho.com.br/deploy.php
   Content type: application/json
   Secret: (opcional) seu_token_secreto
   Events: Just the push event
   ```
4. **Salvar**

### 2. Upload Manual dos Arquivos

1. **Baixe ZIP do GitHub:**
   - Acesse: https://github.com/pixel12digital/cfcbomconselho
   - Clique "Code" → "Download ZIP"

2. **Extraia e faça upload:**
   - Via FTP ou File Manager da Hostinger
   - Para `/public_html/`

3. **Configure o webhook:**
   - Arquivos `deploy.php` e `git_deploy_setup.md` já estão no repositório
   - Só fazer upload para funcionar

### 3. Teste o Deploy Automático

Após push no GitHub:
- O webhook vai chamar `https://cfcbomconselho.com.br/deploy.php`
- Arquivo vai executar `git pull` automaticamente
- Logs ficam em `/logs/deploy.log`

## ✅ Vantagens desta abordagem:
- ✅ Funciona mesmo se SSH Git falhar
- ✅ Controle total do processo
- ✅ Logs detalhados
- ✅ Backup automático
