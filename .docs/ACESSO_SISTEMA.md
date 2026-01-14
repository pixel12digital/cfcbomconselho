# 🔗 Como Acessar o Sistema CFC

## URLs de Acesso

### ✅ URL Correta (Recomendada)
```
http://localhost/cfc-v.1/public_html/login
```

### ✅ URL Alternativa (Redireciona automaticamente)
```
http://localhost/cfc-v.1/login
```
Esta URL será automaticamente redirecionada para `public_html/login` pelo `.htaccess`.

### ❌ URL Incorreta (Não funciona)
```
http://localhost/login
```
Esta URL não funciona porque o Apache procura na raiz do `htdocs`, não no diretório do projeto.

## Credenciais Padrão

- **Email:** `admin@cfc.local`
- **Senha:** `admin123`

⚠️ **IMPORTANTE:** Alterar a senha após o primeiro login!

## Configuração de VirtualHost (Opcional)

Se você quiser acessar apenas `localhost/login` diretamente, você precisa configurar um VirtualHost no Apache.

### Passos para configurar VirtualHost:

1. **Editar o arquivo `httpd-vhosts.conf` do XAMPP:**
   - Localização: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

2. **Adicionar a seguinte configuração:**
   ```apache
   <VirtualHost *:80>
       ServerName localhost
       DocumentRoot "C:/xampp/htdocs/cfc-v.1/public_html"
       <Directory "C:/xampp/htdocs/cfc-v.1/public_html">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Reiniciar o Apache no XAMPP**

4. **Agora você poderá acessar:**
   - `http://localhost/login` ✅
   - `http://localhost/` ✅

## Solução Rápida (Sem VirtualHost)

Se você não quiser configurar o VirtualHost, sempre use:
```
http://localhost/cfc-v.1/public_html/login
```

Ou adicione um bookmark/favorito no navegador para facilitar o acesso.
