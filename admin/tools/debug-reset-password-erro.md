# Como Debugar "Erro ao atualizar senha"

## Problema
Após preencher o formulário e clicar em "Redefinir Senha", aparece:
**"Erro ao atualizar senha. Tente novamente."**

## Diagnóstico Rápido

### 1. Verificar Resposta do Servidor (MAIS IMPORTANTE)

1. **Abra o DevTools (F12)**
2. **Vá na aba "Network" (Rede)**
3. **Limpe a lista** (botão 🚫 ou Ctrl+L)
4. **Preencha o formulário novamente** e clique em "Redefinir Senha"
5. **Procure a requisição POST** para `reset-password.php`
6. **Clique nela** e veja:
   - **Status:** Qual é o código? (200, 500, 400, etc.)
   - **Response (aba "Response" ou "Preview"):** O que o servidor retornou?
   - **Headers (aba "Headers"):** Verifique os headers da requisição

**O que procurar:**
- Se Status = **500**: Erro no servidor PHP (ver logs do servidor)
- Se Status = **200**: Servidor processou, mas retornou erro (ver Response)
- Se Status = **400**: Dados inválidos enviados

### 2. Verificar Logs do Servidor PHP

Os logs do PHP devem estar em:
- **Local (XAMPP):** `C:\xampp\php\logs\php_error_log` ou `C:\xampp\apache\logs\error.log`
- **Produção:** Depende do servidor (Hostinger geralmente em `/logs/` ou painel de controle)

**Procurar por:**
```
[RESET_PASSWORD]
[PASSWORD_RESET]
```

**Comandos úteis (se tiver acesso SSH):**
```bash
# Últimas 50 linhas do log
tail -n 50 /caminho/do/log/php_error_log | grep RESET_PASSWORD

# Todas as ocorrências de hoje
grep "$(date +%Y-%m-%d)" /caminho/do/log/php_error_log | grep RESET_PASSWORD
```

### 3. Verificar Console do Navegador (Limpar e Tentar Novamente)

1. **Limpe o console** (botão 🚫 ou Ctrl+L)
2. **Recarregue a página** (F5)
3. **Preencha o formulário**
4. **Clique em "Redefinir Senha"**
5. **Veja se aparece algum erro JavaScript** (vermelho)

### 4. Verificar Dados Enviados

Na aba **Network** do DevTools:
1. Clique na requisição POST `reset-password.php`
2. Vá na aba **"Payload"** ou **"Request"**
3. Verifique se os dados estão corretos:
   - `token`: Deve estar presente
   - `new_password`: Deve estar presente
   - `confirm_password`: Deve estar presente

## Possíveis Causas

### A) Token Inválido ou Expirado
**Sintoma:** Status 200, mas mensagem "Link inválido ou expirado"
**Solução:** Solicitar novo token

### B) Senha Muito Curta
**Sintoma:** Status 200, mensagem "A senha deve ter no mínimo 8 caracteres"
**Solução:** Usar senha com 8+ caracteres

### C) Senhas Não Coincidem
**Sintoma:** Status 200, mensagem "As senhas não coincidem"
**Solução:** Verificar se os dois campos estão iguais

### D) Erro no Banco de Dados
**Sintoma:** Status 500 ou logs mostram erro SQL
**Solução:** Verificar logs do servidor, verificar conexão com banco

### E) Erro ao Atualizar Senha (Usuário não encontrado)
**Sintoma:** Status 200, mensagem genérica "Erro ao atualizar senha"
**Solução:** Verificar logs do servidor - pode ser que o usuário não existe ou o ID está incorreto

## Informações para Enviar

Quando reportar o problema, envie:

1. **Status HTTP** da requisição POST (da aba Network)
2. **Response** completa (da aba Network → Response)
3. **Últimas linhas do log PHP** (se tiver acesso)
4. **Screenshot da aba Network** mostrando a requisição
5. **Mensagem de erro exata** que aparece na tela

## Script de Teste Rápido

Cole no console do navegador (após limpar):

```javascript
// Verificar se o formulário existe
console.log('Formulário:', document.getElementById('resetForm'));

// Verificar campos
console.log('Token:', document.querySelector('input[name="token"]')?.value?.substring(0, 20) + '...');
console.log('Nova senha preenchida:', !!document.getElementById('new_password')?.value);
console.log('Confirmar senha preenchida:', !!document.getElementById('confirm_password')?.value);

// Verificar se há erros na página
console.log('Mensagens de erro:', document.querySelectorAll('.alert-error, .error, [class*="error"]'));
```
