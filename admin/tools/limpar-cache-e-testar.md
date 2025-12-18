# Como Limpar Cache e Testar Reset de Senha

## Problema 1: Service Worker em Cache

O Service Worker está servindo uma versão antiga do script. Siga estes passos:

### Opção A: Desabilitar Service Worker Temporariamente

1. Abra o DevTools (F12)
2. Vá na aba **"Aplicação"** (Application) ou **"Armazenamento"** (Storage)
3. No menu lateral, clique em **"Service Workers"**
4. Clique em **"Unregister"** ou **"Desregistrar"** no service worker ativo
5. Recarregue a página (Ctrl+F5 ou Cmd+Shift+R para hard refresh)

### Opção B: Limpar Cache do Navegador

1. Abra o DevTools (F12)
2. Vá na aba **"Aplicação"** (Application)
3. No menu lateral, clique em **"Cache Storage"**
4. Clique com botão direito e selecione **"Delete"** ou **"Limpar"**
5. Recarregue a página (Ctrl+F5)

### Opção C: Modo Anônimo/Incógnito

1. Abra uma janela anônima/incógnita (Ctrl+Shift+N)
2. Acesse a página de reset
3. O Service Worker não será usado em modo anônimo

## Problema 2: Token Inválido

A URL mostra `token=SEU_TOKEN` que é um placeholder. Você precisa:

1. **Solicitar um novo email de recuperação:**
   - Acesse: `https://cfcbomconselho.com.br/forgot-password.php?type=aluno`
   - Digite seu CPF
   - Clique em "Enviar instruções"
   - Verifique seu email

2. **Copiar o token real do email:**
   - Abra o email recebido
   - Copie o token completo do link
   - Exemplo: `https://cfcbomconselho.com.br/reset-password.php?token=abc123def456...`

3. **Usar o token real na URL:**
   - Acesse: `https://cfcbomconselho.com.br/reset-password.php?token=TOKEN_REAL_DO_EMAIL`

## Procedimento Completo de Teste

1. **Limpar cache do Service Worker** (usar uma das opções acima)

2. **Solicitar novo token de recuperação:**
   ```
   https://cfcbomconselho.com.br/forgot-password.php?type=aluno
   ```

3. **Acessar página de reset com token real:**
   ```
   https://cfcbomconselho.com.br/reset-password.php?token=TOKEN_DO_EMAIL
   ```

4. **Abrir DevTools (F12) → Console**

5. **Limpar console** (botão 🚫 ou Ctrl+L)

6. **Injetar o script:**
   - Acesse: `https://cfcbomconselho.com.br/admin/tools/injetar-logs-reset-senha.js`
   - Copie TODO o conteúdo
   - Cole no console e pressione Enter

7. **Verificar se funcionou:**
   - Deve aparecer: "✅ Script de captura de logs injetado com sucesso!"
   - Deve aparecer uma caixa verde no canto superior direito
   - NÃO deve aparecer erro de "Maximum call stack size exceeded"

8. **Preencher formulário e submeter:**
   - Digite nova senha
   - Confirme senha
   - Clique em "Redefinir Senha"

9. **Verificar logs capturados:**
   - Na caixa visual (canto superior direito)
   - No console
   - Em `window.capturedLogs` (digite no console)

## Se Ainda Der Erro

Se ainda aparecer o erro de loop infinito:

1. **Feche TODAS as abas** do site
2. **Feche o navegador completamente**
3. **Abra novamente** em modo anônimo
4. **Siga os passos acima**

Isso garante que nenhum script antigo esteja em memória.
