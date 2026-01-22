# ✅ Checklist Pós-Deploy - Produção

## 🚀 Após fazer deploy (upload dos arquivos)

### 1. 📤 **Upload dos Arquivos**

Faça upload dos seguintes arquivos para a Hostinger:

- [ ] **`index.php`** → `public_html/painel/index.php`
- [ ] **`.htaccess`** → `public_html/painel/.htaccess` (substituir o existente)

---

### 2. 🔒 **Verificar Permissões**

Após upload, verifique as permissões:

- [ ] **`index.php`** (raiz): **644** ou **755**
- [ ] **`.htaccess`** (raiz): **644** ou **755**
- [ ] **Diretório `public_html/`**: **755**
- [ ] **Diretório `painel/`**: **755**

---

### 3. 🧪 **Testes Básicos**

Após fazer upload, teste:

- [ ] **Acesso à raiz:** `https://painel.cfcbomconselho.com.br/`
  - ✅ Deve carregar a página de login (não mais 403)
  
- [ ] **Acesso direto ao index.php:** `https://painel.cfcbomconselho.com.br/index.php`
  - ✅ Deve funcionar normalmente

- [ ] **Teste de login:**
  - ✅ Acessar com credenciais do banco
  - ✅ Verificar se redireciona para dashboard

---

### 4. 📁 **Verificar Estrutura de Arquivos**

Confirme que a estrutura está assim:

```
public_html/painel/
├── index.php  ← NOVO (deve existir)
├── .htaccess  ← ATUALIZADO (deve ter novo conteúdo)
├── app/
├── public_html/
│   ├── index.php  ← mantém como está
│   ├── .htaccess  ← mantém como está
│   └── assets/
├── certificados/
└── .env
```

---

### 5. 🔍 **Verificar Logs (se necessário)**

Se ainda houver problemas:

- [ ] Verificar logs do PHP: `storage/logs/php_errors.log`
- [ ] Verificar logs do servidor na Hostinger (Error Log)

---

### 6. ✅ **Verificações Finais**

- [ ] **CSS/JS carregam:** Verificar se assets estão acessíveis
- [ ] **Rotas funcionam:** Navegar pelo sistema após login
- [ ] **Banco de dados:** Verificar se conexão funciona
- [ ] **Sem erros 403:** Confirmar que não há mais 403 Forbidden

---

## ⚠️ **Se Ainda Der 403**

### Verificações Adicionais:

1. **Aguardar propagação:**
   - Aguarde 2-5 minutos após fazer upload
   - Limpe cache do navegador (Ctrl+F5)

2. **Verificar se arquivos foram enviados:**
   - Confirme que `index.php` existe em `public_html/painel/`
   - Confirme que `.htaccess` foi atualizado

3. **Verificar permissões novamente:**
   - Todos os arquivos devem ter permissões corretas

4. **Testar com arquivo simples:**
   - Crie `test.php` com `<?php echo "OK"; ?>`
   - Acesse: `https://painel.cfcbomconselho.com.br/test.php`
   - Se funcionar: PHP está OK, problema é com rotas
   - Se não funcionar: Problema é com permissões/DocumentRoot

---

## 📋 **Resumo das Ações Necessárias**

### ✅ Já Feito (no código):
- [x] Criado `index.php` na raiz
- [x] Atualizado `.htaccess` da raiz
- [x] Commit e push realizados

### 🔄 Precisa Fazer (na Hostinger):
- [ ] Fazer upload do `index.php` para `public_html/painel/`
- [ ] Atualizar `.htaccess` em `public_html/painel/`
- [ ] Verificar permissões
- [ ] Testar acesso

---

## 🎯 **Ordem de Execução**

1. ✅ **Commit/Push** (já feito)
2. ⏳ **Upload dos arquivos** na Hostinger
3. ⏳ **Verificar permissões**
4. ⏳ **Testar acesso**
5. ⏳ **Verificar funcionamento completo**

---

## 📝 **Notas Importantes**

- **Não delete** o `index.php` e `.htaccess` em `public_html/painel/public_html/`
- Eles continuam sendo necessários
- O novo `index.php` na raiz apenas redireciona para o real
- Mantém compatibilidade total com a estrutura existente
