# 🚗 SISTEMA CFC - Sistema Completo para Gestão de Centros de Formação de Condutores
<!-- Teste deploy automático - versão 2 -->

## 📋 Descrição

Sistema web completo desenvolvido em **PHP**, **HTML**, **CSS** e **JavaScript** para gestão de Centros de Formação de Condutores (CFCs). Baseado na análise do sistema e-condutor, oferece funcionalidades similares com arquitetura moderna e otimizada para hospedagem na Hostinger.

## ✨ Funcionalidades Principais

- 🔐 **Sistema de Autenticação Seguro**
  - Login com email/senha
  - Controle de tentativas de acesso
  - Recaptcha v3 integrado
  - Sessões seguras com expiração

- 👥 **Gestão de Usuários**
  - Administradores, instrutores e secretaria
  - Sistema de permissões por tipo
  - Controle de acesso granular

- 🏫 **Gestão de CFCs**
  - Cadastro completo de CFCs
  - Informações de contato e endereço
  - Relacionamento com usuários responsáveis

- 👨‍🎓 **Gestão de Alunos**
  - Cadastro completo de alunos
  - Categorias de CNH
  - Status de matrícula
  - Histórico de aulas

- 👨‍🏫 **Gestão de Instrutores**
  - Cadastro de instrutores
  - Credenciais e categorias de habilitação
  - Relacionamento com CFCs

- 📅 **Agendamento de Aulas**
  - Aulas teóricas e práticas
  - Controle de horários
  - Status de aulas
  - Observações e feedback
  - **Regras de Agendamento:**
    - Cada aula tem duração fixa de **50 minutos**
    - Instrutor pode dar **máximo de 3 aulas por dia**
    - **Padrão de aulas:** 2 aulas consecutivas + intervalo de 30 min + 1 aula final
    - **Alternativa:** 1 aula + intervalo de 30 min + 2 aulas consecutivas
    - Sistema previne conflitos de horário (mesmo instrutor/veículo)
    - Validação automática antes do agendamento
    - Mensagens explicativas para agendamentos inválidos

- 🚗 **Gestão de Veículos**
  - Cadastro de veículos do CFC
  - Categorias de CNH compatíveis
  - Controle de disponibilidade

- 📊 **Relatórios e Estatísticas**
  - Dashboard administrativo
  - Relatórios de alunos
  - Estatísticas de aulas
  - Exportação de dados

- 🔒 **Segurança e Auditoria**
  - Logs de todas as ações
  - Controle de sessões
  - Backup automático
  - Proteção contra ataques

## 📅 Regras de Agendamento de Aulas

### ⏰ Duração e Estrutura
- **Duração da Aula:** Cada aula tem exatamente **50 minutos**
- **Máximo Diário:** Instrutor pode dar no máximo **3 aulas por dia**

### 🔄 Padrões de Aulas
1. **Padrão Principal:** 2 aulas consecutivas → intervalo de 30 min → 1 aula final
2. **Padrão Alternativo:** 1 aula → intervalo de 30 min → 2 aulas consecutivas

### ✅ Validações Automáticas
- **Conflito de Instrutor:** Sistema verifica se o instrutor já possui aula no mesmo horário
- **Conflito de Veículo:** Sistema verifica se o veículo já está agendado no mesmo horário
- **Limite Diário:** Sistema verifica se o instrutor não excedeu o limite de 3 aulas/dia
- **Intervalos:** Sistema garante intervalo mínimo de 30 minutos entre blocos de aulas

### 🚫 Prevenção de Conflitos
- Mesmo instrutor não pode ter múltiplos agendamentos simultâneos
- Mesmo veículo não pode ser usado em múltiplas aulas simultâneas
- Sistema analisa todos os critérios antes de permitir agendamento
- Mensagens explicativas detalhadas para agendamentos inválidos

## 🛠️ Tecnologias Utilizadas

### Backend
- **PHP 8.0+** - Linguagem principal
- **MySQL** - Banco de dados
- **PDO** - Conexão com banco
- **Sessions** - Gerenciamento de sessões

### Frontend
- **HTML5** - Estrutura semântica
- **CSS3** - Estilos responsivos
- **JavaScript (ES6+)** - Funcionalidades interativas
- **Bootstrap 5** - Framework CSS
- **Font Awesome** - Ícones

### Segurança
- **Recaptcha v3** - Proteção contra bots
- **Password Hashing** - Criptografia de senhas
- **SQL Injection Protection** - Prepared Statements
- **XSS Protection** - Sanitização de dados
- **CSRF Protection** - Tokens de segurança

## 📁 Estrutura do Projeto

```
public_html/
├── assets/                 # Recursos estáticos
│   ├── css/               # Arquivos CSS
│   ├── js/                # Arquivos JavaScript
│   ├── img/               # Imagens
│   └── fonts/             # Fontes
├── includes/               # Arquivos PHP incluídos
│   ├── config.php         # Configurações
│   ├── database.php       # Conexão com banco
│   └── auth.php           # Sistema de autenticação
├── admin/                  # Área administrativa
│   ├── dashboard.php      # Painel principal
│   ├── usuarios.php       # Gestão de usuários
│   └── relatorios.php     # Relatórios
├── api/                    # APIs REST
│   ├── auth.php           # Autenticação
│   ├── usuarios.php       # Usuários
│   └── cfc.php            # CFCs
├── logs/                   # Logs do sistema
├── backups/                # Backups automáticos
├── index.php               # Página de login
├── .htaccess               # Configurações Apache
└── README.md               # Esta documentação
```

## 🚀 Instalação

### Pré-requisitos

- **Servidor Web**: Apache 2.4+ ou Nginx
- **PHP**: 8.0 ou superior
- **MySQL**: 5.7+ ou MariaDB 10.2+
- **Extensões PHP**: PDO, PDO_MySQL, JSON, cURL, OpenSSL
- **Hospedagem**: Hostinger (recomendado) ou similar

### Passo a Passo

#### 1. Preparar o Banco de Dados

```sql
-- Conectar ao MySQL
mysql -u root -p

-- Criar banco de dados
CREATE DATABASE cfc_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário para o sistema
CREATE USER 'cfc_user'@'localhost' IDENTIFIED BY 'sua_senha_segura';
GRANT ALL PRIVILEGES ON cfc_sistema.* TO 'cfc_user'@'localhost';
FLUSH PRIVILEGES;

-- Importar estrutura
mysql -u cfc_user -p cfc_sistema < database_structure.sql
```

#### 2. Configurar o Sistema

1. **Editar `includes/config.php`**:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cfc_sistema');
   define('DB_USER', 'cfc_user');
   define('DB_PASS', 'sua_senha_segura');
   define('APP_URL', 'https://seudominio.com');
   ```

2. **Configurar Recaptcha** (opcional):
   - Obter chaves em: https://www.google.com/recaptcha/
   - Editar as constantes `RECAPTCHA_SITE_KEY` e `RECAPTCHA_SECRET_KEY`

#### 3. Upload dos Arquivos

1. **Via FTP/File Manager**:
   - Fazer upload de todos os arquivos para `public_html/`
   - Manter a estrutura de pastas

2. **Via Git** (se disponível):
   ```bash
   cd public_html
   git clone https://github.com/seu-usuario/sistema-cfc.git .
   ```

#### 4. Configurar Permissões

```bash
# Diretórios que precisam de permissão de escrita
chmod 755 logs/
chmod 755 backups/
chmod 755 assets/img/uploads/

# Arquivos de configuração
chmod 644 includes/config.php
chmod 644 .htaccess
```

#### 5. Testar a Instalação

1. Acessar: `https://seudominio.com`
2. Fazer login com as credenciais padrão:
   - **Email**: `admin@cfc.com`
   - **Senha**: `admin123`

3. **IMPORTANTE**: Alterar a senha padrão após o primeiro login!

## ⚙️ Configurações

### Banco de Dados

O sistema suporta configurações avançadas de banco:

```php
// Configurações de performance
define('DB_CACHE_ENABLED', true);
define('DB_CACHE_DURATION', 1800); // 30 minutos

// Configurações de backup
define('BACKUP_ENABLED', true);
define('BACKUP_FREQUENCY', 'daily');
```

### Segurança

```php
// Controle de tentativas de login
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_TIMEOUT', 900); // 15 minutos

// Configurações de sessão
define('SESSION_TIMEOUT', 3600); // 1 hora
```

### Email

```php
// Configurações SMTP (Hostinger)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@seudominio.com');
define('SMTP_PASS', 'sua_senha_smtp');
```

## 🔧 Personalização

### Tema e Cores

Editar `assets/css/login.css`:

```css
:root {
    --primary-color: #0d6efd;     /* Cor principal */
    --primary-dark: #0b5ed7;      /* Cor escura */
    --success-color: #198754;     /* Cor de sucesso */
    --danger-color: #dc3545;      /* Cor de erro */
}
```

### Logo e Imagens

1. Substituir `assets/img/logo.png`
2. Substituir `assets/img/favicon.ico`
3. Ajustar dimensões no CSS se necessário

### Textos e Mensagens

Editar as constantes em `includes/config.php`:

```php
define('APP_NAME', 'Nome do Seu CFC');
define('SUPPORT_EMAIL', 'suporte@seudominio.com');
define('SUPPORT_PHONE', '(11) 99999-9999');
```

## 📱 Responsividade

O sistema é totalmente responsivo e funciona em:

- ✅ **Desktop** (1024px+)
- ✅ **Tablet** (768px - 1023px)
- ✅ **Mobile** (até 767px)
- ✅ **Mobile pequeno** (até 480px)

## 🔒 Segurança

### Recursos Implementados

- **HTTPS obrigatório** em produção
- **Headers de segurança** configurados
- **Content Security Policy** ativo
- **Proteção contra XSS** e SQL Injection
- **Rate limiting** para APIs
- **Logs de auditoria** completos
- **Backup automático** configurável

### Recomendações Adicionais

1. **SSL/HTTPS**: Sempre usar em produção
2. **Firewall**: Configurar no servidor
3. **Backup**: Manter cópias externas
4. **Atualizações**: Manter PHP e MySQL atualizados
5. **Monitoramento**: Implementar alertas de segurança

## 📊 Monitoramento

### Logs Disponíveis

- **Acesso**: Todas as tentativas de login
- **Ações**: Todas as operações no sistema
- **Erros**: Erros de aplicação e banco
- **Performance**: Tempo de resposta das queries

### Métricas Importantes

- Usuários ativos
- Sessões simultâneas
- Performance do banco
- Uso de recursos

## 🚀 Performance

### Otimizações Implementadas

- **Cache de banco** configurável
- **Compressão GZIP** ativa
- **Headers de cache** otimizados
- **Lazy loading** de recursos
- **Minificação** de CSS/JS

### Recomendações

1. **CDN**: Usar para recursos estáticos
2. **Cache**: Implementar Redis se necessário
3. **Banco**: Otimizar queries e índices
4. **Imagens**: Comprimir e otimizar

## 🔄 Backup e Restauração

### Backup Automático

```php
// Configurar em config.php
define('AUTO_BACKUP_ENABLED', true);
define('AUTO_BACKUP_TIME', '02:00'); // 2:00 AM
define('AUTO_BACKUP_RETENTION', 30); // 30 dias
```

### Backup Manual

```php
// Via código
$backupFile = backup();

// Via linha de comando
php -r "require 'includes/config.php'; require 'includes/database.php'; echo backup();"
```

### Restauração

```bash
# Restaurar backup
mysql -u cfc_user -p cfc_sistema < backup_2024-01-01_12-00-00.sql
```

## 🐛 Troubleshooting

### Problemas Comuns

#### 1. Erro de Conexão com Banco

```bash
# Verificar se o MySQL está rodando
systemctl status mysql

# Testar conexão
mysql -u cfc_user -p -h localhost
```

#### 2. Erro de Permissões

```bash
# Verificar permissões
ls -la includes/
ls -la logs/

# Corrigir se necessário
chmod 644 includes/config.php
chmod 755 logs/
```

#### 3. Erro de Sessão

```php
// Verificar configurações de sessão
php -r "phpinfo();" | grep session
```

#### 4. Página em Branco

```bash
# Verificar logs de erro
tail -f /var/log/apache2/error.log
tail -f logs/php_errors.log
```

### Logs de Debug

```php
// Ativar modo debug
define('DEBUG_MODE', true);
define('LOG_LEVEL', 'DEBUG');
```

## 📞 Suporte

### Contato

- **Email**: suporte@seudominio.com
- **Telefone**: (11) 99999-9999
- **Horário**: Segunda a Sexta, 8h às 18h

### Documentação Adicional

- **Manual do Usuário**: `/docs/manual-usuario.pdf`
- **Manual Técnico**: `/docs/manual-tecnico.pdf`
- **API Reference**: `/docs/api-reference.md`

## 🔄 Atualizações

### Verificar Atualizações

```php
// Configurar em config.php
define('AUTO_UPDATE_ENABLED', true);
define('UPDATE_CHECK_FREQUENCY', 'weekly');
```

### Atualização Manual

1. Fazer backup completo
2. Baixar nova versão
3. Substituir arquivos
4. Executar migrações (se houver)
5. Testar funcionalidades

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 🤝 Contribuição

Contribuições são bem-vindas! Para contribuir:

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📈 Roadmap

### Versão 1.1 (Próxima)
- [ ] Sistema de notificações push
- [ ] App mobile nativo
- [ ] Integração com WhatsApp
- [ ] Relatórios avançados

### Versão 1.2
- [ ] PWA (Progressive Web App)
- [ ] Modo offline
- [ ] Sincronização em tempo real
- [ ] Dashboard personalizável

### Versão 2.0
- [ ] Arquitetura microserviços
- [ ] API GraphQL
- [ ] Machine Learning para previsões
- [ ] Integração com DETRAN

## 🙏 Agradecimentos

- **e-condutor CFC** - Sistema de referência
- **Bootstrap** - Framework CSS
- **Font Awesome** - Ícones
- **Comunidade PHP** - Suporte e recursos

---

**Desenvolvido com ❤️ para a comunidade de CFCs brasileiros**

*Última atualização: Janeiro 2025*
*Versão: 1.0.0*
#   T e s t e   d e   a t u a l i z a � � o   v i a   w e b h o o k 
 
 