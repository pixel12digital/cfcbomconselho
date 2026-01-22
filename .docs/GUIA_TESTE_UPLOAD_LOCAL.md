# 🧪 Guia de Teste Local - Upload de Logo

## 1. Acessar o Sistema Localmente

### URL de Acesso
```
http://localhost/cfc-v.1/public_html/
```

### Login
- **Email:** `admin@cfc.local`
- **Senha:** `admin123`

### Navegar para Configurações do CFC
```
http://localhost/cfc-v.1/public_html/configuracoes/cfc
```

---

## 2. Verificar Estrutura Local

### Verificar se diretórios existem
```bash
# No PowerShell (Windows) ou Terminal
cd C:\xampp\htdocs\cfc-v.1

# Verificar estrutura
dir storage\uploads\cfcs
dir storage\logs
```

### Criar diretórios se não existirem
```bash
# Criar diretório de upload
mkdir storage\uploads\cfcs

# Verificar permissões (Windows geralmente não precisa)
```

---

## 3. Teste do Upload - Passo a Passo

### Passo 1: Abrir Console do Navegador
1. Acesse: `http://localhost/cfc-v.1/public_html/configuracoes/cfc`
2. Abra o DevTools (F12)
3. Vá para a aba **Console**

### Passo 2: Selecionar Arquivo
1. Clique em "Escolher ficheiro"
2. Selecione uma imagem (PNG, JPG, WEBP)
3. **Verifique no console:** Deve aparecer:
   ```
   [UPLOAD DEBUG] Arquivo selecionado: {name: "...", size: ..., type: "..."}
   ```

### Passo 3: Verificar Form no Console
Digite no console:
```javascript
// Verificar se form existe
document.querySelector('form[action*="logo/upload"]')

// Ver todos os forms
document.querySelectorAll('form')

// Ver form de upload (alternativo)
document.querySelector('form[enctype="multipart/form-data"]')
```

### Passo 4: Fazer Upload
1. Clique em "Fazer Upload"
2. **Observe o console** - deve aparecer:
   ```
   [UPLOAD DEBUG] ========================================
   [UPLOAD DEBUG] Form submit iniciado
   [UPLOAD DEBUG] Action: ...
   [UPLOAD DEBUG] Method: POST
   ...
   ```

### Passo 5: Verificar Resposta
No console, deve aparecer:
```
[UPLOAD DEBUG] Resposta recebida
[UPLOAD DEBUG] Status: 302 Found (ou 200)
[UPLOAD DEBUG] Headers de debug: {...}
```

---

## 4. Verificar Logs Localmente

### Ver log de upload
```bash
# No PowerShell
type storage\logs\upload_logo.log

# Ou abrir o arquivo diretamente
notepad storage\logs\upload_logo.log
```

### Verificar se arquivo foi criado
```bash
# Ver arquivos no diretório
dir storage\uploads\cfcs
```

---

## 5. Debug no Console - Comandos Úteis

### Verificar se JavaScript está carregado
```javascript
// Ver se form existe
const form = document.querySelector('form[action*="logo/upload"]');
console.log('Form encontrado:', form);

// Ver action do form
console.log('Action:', form?.action);

// Ver se input existe
const input = document.getElementById('logo');
console.log('Input encontrado:', input);
```

### Testar submit manualmente
```javascript
// Simular submit (para debug)
const form = document.querySelector('form[action*="logo/upload"]');
if (form) {
    const fileInput = document.getElementById('logo');
    if (fileInput.files.length > 0) {
        console.log('Arquivo selecionado:', fileInput.files[0]);
        // Não submeter de verdade, apenas verificar
    } else {
        console.log('Nenhum arquivo selecionado');
    }
}
```

### Verificar Network (aba Network do DevTools)
1. Abra DevTools (F12)
2. Vá para aba **Network**
3. Tente fazer upload
4. Procure por requisição POST para `/configuracoes/cfc/logo/upload`
5. Clique na requisição e veja:
   - **Status:** 200, 302, 404, 500?
   - **Headers:** Request e Response
   - **Payload:** Se o arquivo está sendo enviado

---

## 6. Checklist de Verificação

### ✅ Antes do Teste
- [ ] Apache está rodando no XAMPP
- [ ] MySQL está rodando no XAMPP
- [ ] Banco de dados está configurado no `.env`
- [ ] Diretório `storage/uploads/cfcs/` existe
- [ ] Diretório `storage/logs/` existe

### ✅ Durante o Teste
- [ ] Console do navegador está aberto (F12)
- [ ] Arquivo foi selecionado (aparece preview)
- [ ] Console mostra `[UPLOAD DEBUG] Arquivo selecionado`
- [ ] Ao clicar em "Fazer Upload", console mostra `[UPLOAD DEBUG] Form submit iniciado`
- [ ] Network tab mostra requisição POST

### ✅ Após o Teste
- [ ] Log `storage/logs/upload_logo.log` foi criado
- [ ] Log contém informações do upload
- [ ] Arquivo foi criado em `storage/uploads/cfcs/`
- [ ] Console mostra resposta com headers de debug

---

## 7. Problemas Comuns e Soluções

### Problema: Console não mostra logs
**Solução:** Verificar se JavaScript está carregado corretamente
```javascript
// No console, verificar se script está carregado
console.log('Script carregado:', typeof uploadForm !== 'undefined');
```

### Problema: Form não encontrado
**Solução:** Verificar seletor
```javascript
// Testar seletores alternativos
document.querySelector('form[enctype="multipart/form-data"]')
document.querySelectorAll('form')[0] // Primeiro form da página
```

### Problema: Upload não envia
**Solução:** Verificar Network tab
- Se não aparece requisição POST → JavaScript não está interceptando
- Se aparece 404 → Rota não encontrada
- Se aparece 500 → Erro no servidor (ver logs PHP)

### Problema: Log não é criado
**Solução:** Verificar permissões e caminho
```bash
# Verificar se diretório existe
dir storage\logs

# Verificar permissões (Windows geralmente não é problema)
# Mas verificar se PHP pode escrever
```

---

## 8. Informações para Enviar

Após testar localmente, me envie:

1. **Console do navegador:**
   - Screenshot ou copiar todos os logs `[UPLOAD DEBUG]`
   - Qualquer erro em vermelho

2. **Network tab:**
   - Status da requisição POST
   - Headers de Request
   - Headers de Response (especialmente `X-Upload-Debug-*`)

3. **Log do servidor:**
   - Conteúdo de `storage/logs/upload_logo.log`
   - Ou confirmação de que não existe

4. **Arquivo criado:**
   - Se arquivo foi criado em `storage/uploads/cfcs/`
   - Nome do arquivo criado

Com essas informações, identifico exatamente onde está o problema!
