# Sistema CFC - Gestão Completa

Sistema profissional de gestão para Centro de Formação de Condutores (CFC).

## Estrutura do Projeto

```
cfc-v.1/
├── app/
│   ├── Config/          # Configurações (Database, Constants, Env)
│   ├── Controllers/     # Controladores MVC
│   ├── Core/            # Core do sistema (Router, etc)
│   ├── Middlewares/     # Middlewares (Auth, Role)
│   ├── Models/          # Models (ORM básico)
│   ├── Services/        # Serviços (Auth, etc)
│   ├── Views/           # Views/Templates
│   ├── routes/          # Definição de rotas
│   └── Bootstrap.php    # Inicialização
├── assets/
│   ├── css/             # Design System + Layout
│   └── js/              # JavaScript
├── database/
│   ├── migrations/      # Migrations SQL
│   └── seeds/           # Seeds SQL
├── public_html/         # DocumentRoot (ponto de entrada)
└── storage/             # Logs e uploads (protegido)
```

## Instalação

1. **Configurar banco de dados:**
   - Criar banco de dados MySQL
   - Executar migrations: `database/migrations/001_create_base_tables.sql`
   - Executar seeds: `database/seeds/001_seed_initial_data.sql`

2. **Configurar ambiente:**
   - Copiar `.env.example` para `.env` (se necessário)
   - Configurar conexão com banco no `.env` ou diretamente em `app/Config/Database.php`

3. **Acesso inicial:**
   - Email: `admin@cfc.local`
   - Senha: `admin123` (ALTERAR APÓS PRIMEIRO LOGIN!)

## Credenciais Padrão

Ver arquivo: `app/Config/Credentials.php`

- **Email:** `admin@cfc.local`
- **Senha:** `admin123`

⚠️ **IMPORTANTE:** Alterar a senha após o primeiro login!

## Arquitetura

- **Front Controller:** `public_html/index.php`
- **Router:** Sistema de rotas próprio
- **RBAC:** Sistema de papéis e permissões
- **Design System:** CSS modular e controlado
- **Mobile-first:** Totalmente responsivo

## Status - Fase 0 ✅

- ✅ Estrutura base do projeto
- ✅ Sistema de rotas
- ✅ Autenticação e sessão
- ✅ RBAC (papéis e permissões)
- ✅ Seletor de papel (troca sem logout)
- ✅ Layout único (Topbar + Sidebar)
- ✅ Design System (tokens + componentes)
- ✅ Database migrations e seeds

## Próximas Fases

- **Fase 1:** Alunos, Matrículas, Agenda, Aulas, Instrutores, Veículos
- **Fase 2:** Financeiro + Asaas
- **Fase 3:** Comunicação + Notificações + PWA

## Desenvolvimento

Sistema desenvolvido em PHP 8+ com MySQL, seguindo princípios de código limpo e arquitetura preparada para escalar.
