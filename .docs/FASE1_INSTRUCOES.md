# FASE 1 - Instruções de Instalação

## ✅ Status: Implementação Completa

A Fase 1 foi implementada com sucesso. Siga os passos abaixo para configurar o banco de dados.

## 📋 Passos para Executar

### 1. Executar Migrations e Seeds

Execute o arquivo SQL consolidado no banco de dados `cfc_db`:

```sql
-- Opção 1: Via MySQL Command Line
mysql -u root -p cfc_db < database/PHASE1_SETUP.sql

-- Opção 2: Via phpMyAdmin ou cliente MySQL
-- Abra o arquivo database/PHASE1_SETUP.sql e execute todo o conteúdo
```

**OU** execute os arquivos separadamente:

```sql
-- 1. Migration
SOURCE database/migrations/002_create_phase1_tables.sql;

-- 2. Seed
SOURCE database/seeds/002_seed_phase1_data.sql;
```

### 2. Verificar Tabelas Criadas

As seguintes tabelas devem estar criadas:

- ✅ `services` - Catálogo de serviços
- ✅ `students` - Cadastro de alunos
- ✅ `enrollments` - Matrículas
- ✅ `steps` - Catálogo de etapas
- ✅ `student_steps` - Etapas por aluno/matrícula

### 3. Verificar Seeds

- ✅ 7 serviços padrão cadastrados
- ✅ 8 etapas padrão cadastradas
- ✅ Permissões adicionadas para novos módulos
- ✅ Permissões associadas aos roles ADMIN e SECRETARIA

## 🧪 Teste Rápido

Após executar as migrations e seeds, faça login e teste:

1. **Criar um serviço:**
   - Acesse `/servicos`
   - Clique em "Novo Serviço"
   - Preencha os dados e salve

2. **Criar um aluno:**
   - Acesse `/alunos`
   - Clique em "Novo Aluno"
   - Preencha os dados e salve

3. **Criar uma matrícula:**
   - Acesse o aluno criado
   - Vá na aba "Matrícula"
   - Clique em "Nova Matrícula"
   - Selecione um serviço, defina valores e salve

4. **Verificar progresso:**
   - Na aba "Progresso" do aluno
   - Verifique se as etapas foram criadas automaticamente
   - Marque uma etapa como concluída

5. **Verificar auditoria:**
   - Todas as ações devem estar registradas na tabela `auditoria`

## 📝 Funcionalidades Implementadas

### ✅ Serviços (CRUD Completo)
- Listar serviços
- Criar novo serviço
- Editar serviço
- Ativar/Desativar serviço
- Exclusão lógica (soft delete)

### ✅ Alunos (CRUD + Busca)
- Listar alunos com busca (nome, CPF, telefone)
- Criar novo aluno
- Editar aluno
- Visualizar detalhes do aluno
- Página do aluno com abas: Dados | Matrícula | Progresso

### ✅ Matrícula
- Criar matrícula a partir do aluno
- Selecionar serviço
- Definir desconto e acréscimo (em R$)
- Cálculo automático do valor final
- Selecionar forma de pagamento
- Status financeiro (em_dia, pendente, bloqueado)
- Status da matrícula (ativa, concluída, cancelada)
- Editar matrícula existente

### ✅ Etapas/Progresso
- Timeline com todas as etapas
- Marcar/desmarcar etapas (secretaria/admin)
- Registro de origem (CFC ou aluno)
- Registro de validação (quem validou e quando)
- Etapa MATRÍCULA marcada automaticamente ao criar matrícula

### ✅ Auditoria
- Todas as ações relevantes registradas
- Logs de create, update, toggle
- Dados antes e depois
- IP e User Agent registrados

### ✅ Preparação Financeira
- Status financeiro na matrícula
- Helper `EnrollmentPolicy` criado (canSchedule, canStartLesson)
- Pronto para bloqueios quando Agenda/Aulas forem implementadas

## 🔐 Permissões

As seguintes permissões foram adicionadas:

- `servicos.view`, `servicos.create`, `servicos.update`, `servicos.toggle`
- `alunos.view`, `alunos.create`, `alunos.update`
- `enrollments.view`, `enrollments.create`, `enrollments.update`
- `steps.view`, `steps.update`

**ADMIN** tem todas as permissões.
**SECRETARIA** tem permissões para todos os módulos da Fase 1.

## 🐛 Troubleshooting

### Erro: "Tabela não existe"
- Execute as migrations novamente
- Verifique se o banco de dados `cfc_db` existe

### Erro: "Foreign key constraint fails"
- Verifique se a migration 001 (Fase 0) foi executada
- Verifique se o CFC padrão (id=1) existe na tabela `cfcs`

### Erro: "Permissão negada"
- Verifique se as permissões foram associadas aos roles
- Verifique se o usuário tem o role correto

## 📸 Próximos Passos

Após validar a Fase 1, você deve fornecer:
- Print da lista de alunos
- Print do detalhe do aluno (aba progresso)
- Print da criação de matrícula

Isso permitirá validar a UX antes de iniciar a Fase 2.
