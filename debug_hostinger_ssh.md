# 🔧 Debug SSH Hostinger

## Teste Manual da Conexão SSH

### 1. Acesse "Terminal SSH" na Hostinger
- Vá em: **Avesso SSH** ou **Terminal**
- Tente rodar: `ssh -T git@github.com`

### 2. Se der erro, teste:
```bash
# Teste simples SSH
ssh -T git@github.com

# Verificar chave SSH
ls -la ~/.ssh/

# Ver conteúdo da chave
cat ~/.ssh/id_rsa.pub
```

### 3. Se não funcionar, recrie a chave SSH
- Na Hostinger: **Remover chave SSH** → **Gerar nova chave**
- Copie a nova chave e adicione no GitHub novamente

## Alternativas se não funcionar:

### Opção A: Usar HTTPS em vez de SSH
Na Hostinger, tente usar:
```
Repositório: https://github.com/pixel12digital/cfcbomconselho.git
(CUIDADO: Vai pedir credenciais GitHub)
```

### Opção B: Deploy Manual via Upload
- Baixe o ZIP do GitHub
- Faça upload manual dos arquivos

### Opção C: Usar .htaccess para GitHub
Configure webhook direto do GitHub para seu site
