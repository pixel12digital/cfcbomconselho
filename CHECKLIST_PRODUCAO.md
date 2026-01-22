# Checklist de Configuração em Produção - Hostinger

## ⚠️ IMPORTANTE: Estrutura de Pastas

Na Hostinger, a estrutura pode ser diferente. Verifique:

### Estrutura Esperada:
```
/
├── app/
├── public_html/  (ou public_html/painel/)
│   └── index.php
├── .env  ← AQUI (na raiz, mesmo nível que app/)
└── composer.json
```

## PASSO 1: Localizar a Raiz do Projeto

1. No File Browser da Hostinger, você está em: `public_html/painel/`
2. Suba um nível até a **raiz do projeto** (onde estão as pastas `app/`, `public_html/`, etc.)
3. **Anote o caminho completo** - será algo como `/home/usuario/public_html/` ou `/home/usuario/`

## PASSO 2: Criar o Arquivo .env

1. Na **raiz do projeto** (não dentro de `public_html/painel/`)
2. Crie um arquivo chamado `.env` (com ponto no início)
3. Se não conseguir criar arquivo oculto, crie primeiro `env.txt` e depois renomeie para `.env`

## PASSO 3: Configurar o .env

Adicione estas variáveis (preencha com os valores reais da Hostinger):

```env
# Configuração do Banco de Dados (PRODUÇÃO)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nome_do_banco_hostinger
DB_USER=usuario_do_banco_hostinger
DB_PASS=senha_do_banco_hostinger

# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=seu_client_id_producao
EFI_CLIENT_SECRET=seu_client_secret_producao
EFI_SANDBOX=false
EFI_CERT_PATH=/caminho/completo/para/certificado.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret

# Ambiente
APP_ENV=production
```

**Onde obter:**
- **DB_HOST, DB_NAME, DB_USER, DB_PASS**: Painel da Hostinger → Banco de Dados → Detalhes
- **EFI_***: Dashboard da EFÍ (Gerencianet)
- **EFI_CERT_PATH**: Caminho absoluto do certificado após upload

## PASSO 4: Ajustar Bootstrap.php para Produção

O arquivo `app/Bootstrap.php` tem paths hardcoded que precisam ser ajustados.

**Verificar a estrutura:**
- Se o subdomínio `painel` aponta diretamente para `public_html/` → paths devem ser apenas `/`
- Se aponta para `public_html/painel/` → paths devem ser `/painel/`

## PASSO 5: Verificar Permissões

1. `.env` deve ter permissões 644 (apenas leitura para outros)
2. `storage/` deve ter permissões 755 (escritável)

## Próximos Passos

Após confirmar a estrutura, vamos:
1. ✅ Criar o .env com as variáveis corretas
2. ✅ Ajustar Bootstrap.php para os paths corretos
3. ✅ Verificar se index.php está no local correto
4. ✅ Testar conexão com banco de dados
