# 🚀 Guia de Instalação em Produção - Hostinger

## 📍 PASSO 1: Confirmar Estrutura de Pastas

No File Browser da Hostinger, verifique:

### Opção A: Subdomínio `painel` aponta para pasta raiz
```
/home/usuario/public_html/
├── app/
├── public_html/  ← index.php aqui
├── assets/
├── .env  ← CRIAR AQUI (raiz do projeto)
└── composer.json
```

### Opção B: Subdomínio `painel` aponta para subpasta
```
/home/usuario/public_html/
└── painel/
    ├── app/
    ├── public_html/  ← index.php aqui (ou pode ser index.php na raiz de painel/)
    ├── assets/
    ├── .env  ← CRIAR AQUI (dentro de painel/)
    └── composer.json
```

**⚠️ IMPORTANTE:** O `.env` deve estar na **mesma raiz onde estão as pastas `app/`, `public_html/`, etc.**

---

## 📝 PASSO 2: Criar Arquivo .env

### Como criar no File Browser da Hostinger:

1. **Navegue até a raiz do projeto** (onde está a pasta `app/`)
2. **Clique em "New file"** na sidebar esquerda
3. **Digite o nome:** `.env` (com ponto no início)
   - ⚠️ Se não conseguir criar arquivo oculto, crie `env.txt` primeiro
4. **Abra o arquivo e cole o conteúdo abaixo:**

```env
# ============================================
# CONFIGURAÇÃO DO BANCO DE DADOS (PRODUÇÃO)
# ============================================
# Obtenha estes dados no painel da Hostinger:
# Banco de Dados → Detalhes do Banco
DB_HOST=localhost
DB_PORT=3306
DB_NAME=SEU_BANCO_AQUI
DB_USER=SEU_USUARIO_AQUI
DB_PASS=SUA_SENHA_AQUI

# ============================================
# EFÍ (GERENCIANET) - GATEWAY DE PAGAMENTO
# ============================================
# Obtenha no Dashboard da EFÍ (Gerencianet)
EFI_CLIENT_ID=seu_client_id_producao
EFI_CLIENT_SECRET=seu_client_secret_producao
EFI_SANDBOX=false
EFI_CERT_PATH=/caminho/completo/para/certificado.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret

# ============================================
# AMBIENTE
# ============================================
APP_ENV=production

# ============================================
# BASE PATH (será ajustado automaticamente)
# ============================================
# Para subdomínio raiz: deixe vazio ou "/"
# Para subpasta painel: "/painel"
BASE_PATH=
```

### ⚠️ Preencha os valores:

- **DB_NAME, DB_USER, DB_PASS**: Painel Hostinger → Banco de Dados → Detalhes
- **EFI_*****: Dashboard da EFÍ (se usar pagamentos)
- **EFI_CERT_PATH**: Caminho absoluto após fazer upload do certificado `.p12`

---

## ⚙️ PASSO 3: Ajustar Bootstrap.php e Router.php

Após criar o `.env`, precisaremos ajustar os paths hardcoded para produção.

**Aguardando confirmação da estrutura para fazer os ajustes corretos.**

---

## ✅ PASSO 4: Verificar Permissões

1. **`.env`**: Permissão `644` (proprietário pode ler/escrever, outros apenas ler)
2. **`storage/`**: Permissão `755` (escritável)
3. **`storage/logs/`**: Permissão `755`
4. **`storage/uploads/`**: Permissão `755`

---

## 🧪 PASSO 5: Testar

1. Acesse seu subdomínio `painel`
2. Verifique se a página carrega
3. Tente fazer login

---

## ❓ DÚVIDAS?

**Q: Onde exatamente criar o .env?**  
A: Na raiz do projeto, mesmo nível que `app/`, `public_html/`, `composer.json`

**Q: Como saber qual é a raiz?**  
A: É onde você vê as pastas `app/`, `public_html/`, `assets/` todas juntas

**Q: Não consigo criar arquivo oculto (.env)**  
A: Crie `env.txt` e depois renomeie para `.env`

**Q: Onde pegar credenciais do banco?**  
A: Painel da Hostinger → Banco de Dados → Seu banco → Detalhes
