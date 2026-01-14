# Sistema CFC - Gestão Completa

Sistema profissional de gestão para Centro de Formação de Condutores (CFC).

## 📚 Documentação

Toda a documentação do projeto foi organizada no diretório [`.docs/`](.docs/).

### Documentação Principal

- **[README Completo](.docs/README.md)** - Documentação completa do projeto
- **[Fase 1.1 - Implementação](.docs/FASE1_1_IMPLEMENTACAO.md)** - Refino do módulo de Alunos

### Guias e Instruções

- **[Como Executar Fase 1](.docs/COMO_EXECUTAR_FASE1.md)** - Instruções para executar a Fase 1
- **[Instruções Fase 1](.docs/FASE1_INSTRUCOES.md)** - Guia completo da Fase 1
- **[Setup Completo](.docs/SETUP_COMPLETE.md)** - Guia de configuração inicial

### Diagnósticos e Correções

- **[Credenciais](.docs/CREDENCIAIS.md)**
- **[Diagnóstico de Login](.docs/DIAGNOSTICO_LOGIN.md)**
- **[Resumo Diagnóstico](.docs/RESUMO_DIAGNOSTICO.md)**
- **[Correções Fase 0](.docs/CORRECOES_FASE0.md)**
- **[Correção Final Login](.docs/CORRECAO_FINAL_LOGIN.md)**
- **[Validação Final](.docs/VALIDACAO_FINAL.md)**
- **[Debug Login](.docs/DEBUG_LOGIN.md)**

### Validações do Banco de Dados

- **[Validação Fase 0](.docs/PHASE0_VALIDATION_COMPLETE.md)**
- **[Relatório de Validação](.docs/validation_report.md)**

## 🚀 Início Rápido

1. **Configurar banco de dados:**
   - Criar banco de dados MySQL
   - Executar migrations: `database/migrations/001_create_base_tables.sql`
   - Executar seeds: `database/seeds/001_seed_initial_data.sql`

2. **Configurar ambiente:**
   - Configurar conexão com banco em `app/Config/Database.php` ou `.env`

3. **Acesso inicial:**
   - Email: `admin@cfc.local`
   - Senha: `admin123` (ALTERAR APÓS PRIMEIRO LOGIN!)

⚠️ **IMPORTANTE:** Ver [`.docs/CREDENCIAIS.md`](.docs/CREDENCIAIS.md) para mais informações sobre credenciais.

## 📁 Estrutura do Projeto

```
cfc-v.1/
├── app/              # Aplicação (Controllers, Models, Views)
├── assets/           # CSS e JavaScript
├── database/         # Migrations e Seeds
├── public_html/      # DocumentRoot (ponto de entrada)
├── storage/          # Logs e uploads (protegido)
├── .docs/            # Documentação completa
└── README.md         # Este arquivo
```

## 🔗 Links Úteis

- [Documentação Completa](.docs/README.md)
- [Implementação Fase 1.1](.docs/FASE1_1_IMPLEMENTACAO.md)
