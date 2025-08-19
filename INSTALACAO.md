# 🚀 Instalação do Sistema CFC

## 📋 Pré-requisitos

- **Servidor Web:** Apache/Nginx com PHP 8.0+
- **Banco de Dados:** MySQL 5.7+ ou MariaDB 10.2+
- **PHP Extensions:** PDO, PDO_MySQL, JSON, mbstring
- **Permissões:** Pasta `uploads/` com permissão de escrita

## 🔧 Passos para Instalação

### 1. **Configurar Banco de Dados**

Edite o arquivo `includes/config.php` e configure suas credenciais:

```php
// Configurações do Banco de Dados
define('DB_HOST', 'seu_host_mysql');
define('DB_NAME', 'nome_do_banco');
define('DB_USER', 'usuario_mysql');
define('DB_PASS', 'senha_mysql');
```

### 2. **Executar Script de Instalação**

Acesse no navegador:
```
http://seu-dominio.com/install.php
```

O script irá:
- ✅ Criar todas as tabelas necessárias
- ✅ Inserir usuário administrador padrão
- ✅ Configurar índices de performance
- ✅ Criar CFC padrão

### 3. **Credenciais de Acesso**

Após a instalação, use:

- **Email:** `admin@cfc.com`
- **Senha:** `admin123`
- **URL:** `http://seu-dominio.com/index.php`

### 4. **Primeiro Acesso**

1. Faça login com as credenciais acima
2. **IMPORTANTE:** Altere a senha padrão do administrador
3. Configure os dados do seu CFC
4. Comece a usar o sistema!

## 🗄️ Estrutura do Banco

O sistema criará automaticamente as seguintes tabelas:

- **`usuarios`** - Usuários do sistema (admin, instrutores, secretaria)
- **`cfcs`** - Centros de Formação de Condutores
- **`alunos`** - Cadastro de alunos
- **`instrutores`** - Cadastro de instrutores
- **`aulas`** - Agendamento de aulas
- **`veiculos`** - Frota de veículos
- **`sessoes`** - Controle de sessões e tokens
- **`logs`** - Auditoria e logs do sistema

## 🔒 Segurança

- ✅ Senhas criptografadas com `password_hash()`
- ✅ Proteção contra SQL Injection
- ✅ Controle de sessões
- ✅ Logs de auditoria
- ✅ Controle de tentativas de login

## 🚨 Solução de Problemas

### **Erro de Conexão com Banco**
- Verifique as credenciais em `includes/config.php`
- Confirme se o MySQL está rodando
- Verifique se o usuário tem permissões

### **Erro de Permissões**
- Pasta `uploads/` deve ter permissão 755 ou 775
- Usuário do servidor web deve ter acesso de escrita

### **Página em Branco**
- Verifique se há erros no log do PHP
- Confirme se todas as extensões PHP estão instaladas

### **Login Não Funciona**
- Execute o script de instalação novamente
- Verifique se as tabelas foram criadas
- Confirme se o usuário admin foi inserido

## 📞 Suporte

- **Email:** suporte@seudominio.com
- **Horário:** Segunda a Sexta, 8h às 18h
- **Documentação:** Consulte o README.md principal

## 🔄 Reinstalação

Para reinstalar o sistema:

1. Delete o arquivo `installed.lock`
2. Acesse `install.php` novamente
3. O sistema recriará todas as estruturas

---

**⚠️ IMPORTANTE:** Após a instalação, delete o arquivo `install.php` por segurança!
