# Fase 1.2 — Padronização de UF/Cidades no Cadastro

## Objetivo
Eliminar campo "Cidade" digitável e padronizar cidade por UF usando tabelas `states` e `cities`, com seleção dinâmica (UF -> lista de cidades). Melhorar consistência do banco e UX.

## Implementação Completa

### ✅ Migrations Criadas

1. **004_create_states_cities_tables.sql**
   - Cria tabela `states` (id, uf, name)
   - Cria tabela `cities` (id, state_id, name, ibge_code)
   - Índices e foreign keys configurados

2. **005_add_city_id_to_students.sql**
   - Adiciona `city_id` (FK cities.id) na tabela `students`
   - Mantém `state_uf` e `city` (texto) para compatibilidade

### ✅ Seeds Criados

1. **003_seed_states.sql**
   - Popula 27 estados brasileiros (UFs)
   - Idempotente (INSERT IGNORE)

2. **004_seed_cities_sample.sql**
   - Exemplo com algumas cidades de SC, SP, RS, PR
   - Estrutura pronta para importação completa do IBGE
   - **NOTA**: Para produção, importe todas as cidades do IBGE

### ✅ Models Criados

1. **State.php**
   - `findAll()` - Lista todos os estados
   - `findByUf($uf)` - Busca estado por UF

2. **City.php**
   - `findByUf($uf)` - Lista cidades por UF
   - `findById($id)` - Busca cidade por ID
   - `findByIdAndUf($cityId, $uf)` - Valida cidade pertence ao estado

### ✅ Endpoint API

**GET /api/geo/cidades?uf=SC**
- Retorna JSON: `[{id, name}]`
- Protegido com `AuthMiddleware`
- Implementado em `ApiController::getCidades()`

### ✅ Frontend Atualizado

**Formulário de Alunos (`app/Views/alunos/form.php`)**
- UF: Select com estados da tabela `states`
- Cidade: Select dinâmico (desabilitado até UF escolhida)
- JavaScript carrega cidades via API ao selecionar UF
- Campo `city_id` obrigatório apenas quando UF está preenchida
- Compatibilidade: mostra cidade texto se `city_id` estiver null

### ✅ Backend Atualizado

**AlunosController**
- `novo()` e `editar()`: Carregam estados e cidade atual
- `validateStudentData()`: Valida `city_id` quando `state_uf` preenchido
- Validação: cidade deve pertencer ao estado selecionado
- `prepareStudentData()`: Salva `city_id` e mantém `city` (texto) para compatibilidade

**Student Model**
- Adicionado método `getCityName()`: Retorna nome da cidade (com fallback para texto)

## Como Executar

### 1. Executar Migrations e Seeds

```sql
-- Opção 1: Executar script completo
SOURCE database/PHASE1_2_SETUP.sql;

-- Opção 2: Executar individualmente
SOURCE database/migrations/004_create_states_cities_tables.sql;
SOURCE database/migrations/005_add_city_id_to_students.sql;
SOURCE database/seeds/003_seed_states.sql;
SOURCE database/seeds/004_seed_cities_sample.sql;
```

### 2. Importar Todas as Cidades do IBGE (Opcional)

1. Baixe o arquivo de municípios do IBGE
2. Gere um SQL no formato:
   ```sql
   INSERT IGNORE INTO `cities` (`state_id`, `name`, `ibge_code`) VALUES
   (@sc_id, 'Florianópolis', 4205407),
   -- ... todas as cidades
   ```
3. Execute o arquivo SQL completo

### 3. Testar

1. Acesse `/alunos/novo`
2. Selecione uma UF no campo "UF"
3. O campo "Cidade" deve ser habilitado e carregar as cidades
4. Selecione uma cidade
5. Salve o aluno
6. Edite o aluno e verifique se UF e cidade estão pré-selecionados

## Critérios de Aceite ✅

- ✅ Cidade não é mais digitável (select dinâmico)
- ✅ Seleção UF -> lista de cidades funciona
- ✅ Salva `city_id` consistente
- ✅ Form editar carrega corretamente UF + cidade selecionada
- ✅ Validação: cidade deve pertencer ao estado
- ✅ Compatibilidade: mantém campo `city` (texto) para dados antigos

## Compatibilidade

- Alunos existentes com `city` (texto) e `city_id` null:
  - Exibição: mostra texto se `city_id` estiver null
  - Edição: ao salvar novamente, exige seleção por UF/cidade para preencher `city_id`
- Campo `city` (texto) mantido no banco para compatibilidade
- Campo `state_uf` mantido (char(2))

## Estrutura de Dados

### Tabela `states`
```sql
id (PK)
uf (CHAR(2), UNIQUE)
name (VARCHAR)
```

### Tabela `cities`
```sql
id (PK)
state_id (FK states.id)
name (VARCHAR)
ibge_code (INT, UNIQUE nullable)
Índice: (state_id, name)
```

### Tabela `students` (atualizada)
```sql
...
city_id (FK cities.id, nullable)
state_uf (CHAR(2)) -- mantido
city (VARCHAR) -- mantido para compatibilidade
```

## Próximos Passos (Opcional)

1. Criar script de migração manual para mapear cidades existentes (texto) para `city_id`
2. Adicionar busca por cidade no cadastro de alunos
3. Estatísticas por cidade/estado
4. Validação de CEP integrada com cidade/UF
