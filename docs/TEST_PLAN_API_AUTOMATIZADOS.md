# Planejamento de Testes Automatizados - CFC Bom Conselho

**Versão:** 1.0  
**Data:** Janeiro 2025  
**Objetivo:** Definir quais endpoints terão testes automatizados e quais cenários serão cobertos

---

## Introdução

Este documento define o planejamento de testes automatizados para os endpoints críticos do sistema CFC Bom Conselho. Os testes serão implementados incrementalmente, priorizando os fluxos críticos.

**Referências:**
- `docs/TEST_PLAN_ENDPOINTS_CFC.md` - Mapeamento completo de endpoints
- `docs/PLANO_IMPL_PRODUCAO_CFC.md` - Estratégia de testes e fluxos críticos

---

## Estratégia de Testes

### Tipos de Teste por Endpoint

Para cada endpoint crítico, serão planejados pelo menos:

1. **Teste de Autenticação:**
   - Resposta esperada quando não autenticado (401 Unauthorized, redirect para login)
   - Validação de que endpoints protegidos não aceitam requisições sem sessão válida

2. **Teste de Sucesso (Happy Path):**
   - Status HTTP 200 (ou apropriado)
   - Estrutura JSON esperada válida
   - Valores mínimos a serem validados (ex: `id` retornado após criação)

3. **Teste de Validação/Erro:**
   - Resposta esperada quando dados inválidos são enviados (400 Bad Request)
   - Mensagens de erro apropriadas
   - Validações de campos obrigatórios

4. **Teste de Permissão (quando aplicável):**
   - Resposta esperada quando usuário sem permissão tenta acessar (403 Forbidden)
   - Validação de que alunos não podem acessar endpoints admin, etc.

---

## Estrutura de Arquivos de Teste

**Localização planejada:** `tests/api/`

**Convenção de nomenclatura:** `test-{modulo}-api.php`

**Exemplos:**
- `tests/api/test-alunos-api.php`
- `tests/api/test-agendamento-api.php`
- `tests/api/test-financeiro-faturas-api.php`

---

## Planejamento Detalhado por Endpoint

### 1. API de Alunos (`admin/api/alunos.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/alunos.php` POST | Autenticação | Não | 401 Unauthorized ou JSON `{success: false, error: "Não autenticado"}` | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` POST | Permissão | Sim (aluno) | 403 Forbidden | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` POST | Sucesso | Sim (admin) | 200 OK, JSON com `id` do aluno criado, `success: true` | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` POST | Validação | Sim (admin) | 400 Bad Request, erro de campo obrigatório (ex: nome, cpf) | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` GET | Autenticação | Não | 401 Unauthorized | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de alunos | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` PUT | Autenticação | Não | 401 Unauthorized | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` PUT | Sucesso | Sim (admin) | 200 OK, JSON com aluno atualizado | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` DELETE | Autenticação | Não | 401 Unauthorized | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` DELETE | Sucesso | Sim (admin) | 200 OK, JSON `{success: true}` | `tests/api/test-alunos-api.php` |

**Total de cenários:** 10

---

### 2. API de Matrículas (`admin/api/matriculas.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/matriculas.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-matriculas-api.php` |
| `admin/api/matriculas.php` POST | Sucesso | Sim (admin) | 200 OK, JSON com `id` da matrícula criada | `tests/api/test-matriculas-api.php` |
| `admin/api/matriculas.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `aluno_id` inválido | `tests/api/test-matriculas-api.php` |
| `admin/api/matriculas.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de matrículas | `tests/api/test-matriculas-api.php` |

**Total de cenários:** 4

---

### 3. API de Turmas Teóricas (`admin/api/turmas-teoricas.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/turmas-teoricas.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-turmas-teoricas-api.php` |
| `admin/api/turmas-teoricas.php` POST | Permissão | Sim (aluno) | 403 Forbidden | `tests/api/test-turmas-teoricas-api.php` |
| `admin/api/turmas-teoricas.php` POST | Sucesso | Sim (admin) | 200 OK, JSON com `id` da turma criada | `tests/api/test-turmas-teoricas-api.php` |
| `admin/api/turmas-teoricas.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `nome` ou `sala_id` inválidos | `tests/api/test-turmas-teoricas-api.php` |
| `admin/api/turmas-teoricas.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de turmas | `tests/api/test-turmas-teoricas-api.php` |
| `admin/api/turmas-teoricas.php` PUT | Sucesso | Sim (admin) | 200 OK, JSON com turma atualizada | `tests/api/test-turmas-teoricas-api.php` |

**Total de cenários:** 6

---

### 4. API de Matricular Aluno em Turma (`admin/api/matricular-aluno-turma.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/matricular-aluno-turma.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-matricular-aluno-turma-api.php` |
| `admin/api/matricular-aluno-turma.php` POST | Sucesso | Sim (admin) | 200 OK, JSON `{sucesso: true, mensagem: "..."}` | `tests/api/test-matricular-aluno-turma-api.php` |
| `admin/api/matricular-aluno-turma.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se exames não estiverem OK | `tests/api/test-matricular-aluno-turma-api.php` |
| `admin/api/matricular-aluno-turma.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `aluno_id` ou `turma_id` inválidos | `tests/api/test-matricular-aluno-turma-api.php` |

**Total de cenários:** 4

---

### 5. API de Presenças Teóricas (`admin/api/turma-presencas.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/turma-presencas.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-turma-presencas-api.php` |
| `admin/api/turma-presencas.php` POST | Permissão | Sim (aluno) | 403 Forbidden (aluno só pode ler) | `tests/api/test-turma-presencas-api.php` |
| `admin/api/turma-presencas.php` POST | Sucesso | Sim (admin) | 200 OK, JSON `{success: true}` | `tests/api/test-turma-presencas-api.php` |
| `admin/api/turma-presencas.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `turma_id` ou `aluno_id` inválidos | `tests/api/test-turma-presencas-api.php` |
| `admin/api/turma-presencas.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de presenças | `tests/api/test-turma-presencas-api.php` |
| `admin/api/turma-presencas.php` GET | Sucesso | Sim (aluno) | 200 OK, JSON array (apenas suas próprias presenças) | `tests/api/test-turma-presencas-api.php` |

**Total de cenários:** 6

---

### 6. API de Agendamento (`admin/api/agendamento.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/agendamento.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-agendamento-api.php` |
| `admin/api/agendamento.php` POST | Sucesso | Sim (admin) | 200 OK, JSON com `id` do agendamento criado | `tests/api/test-agendamento-api.php` |
| `admin/api/agendamento.php` POST | Validação | Sim (admin) | 400 Bad Request, erro de conflito de horário/instrutor/veículo | `tests/api/test-agendamento-api.php` |
| `admin/api/agendamento.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `aluno_id`, `instrutor_id`, `data_aula`, `hora_inicio` inválidos | `tests/api/test-agendamento-api.php` |
| `admin/api/agendamento.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de aulas | `tests/api/test-agendamento-api.php` |
| `admin/api/agendamento.php` PUT | Sucesso | Sim (admin) | 200 OK, JSON com agendamento atualizado | `tests/api/test-agendamento-api.php` |
| `admin/api/agendamento.php` DELETE | Sucesso | Sim (admin) | 200 OK, JSON `{success: true}` | `tests/api/test-agendamento-api.php` |

**Total de cenários:** 7

---

### 7. API de Verificar Disponibilidade (`admin/api/verificar-disponibilidade.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/verificar-disponibilidade.php` GET | Autenticação | Não | 401 Unauthorized | `tests/api/test-verificar-disponibilidade-api.php` |
| `admin/api/verificar-disponibilidade.php` GET | Sucesso | Sim (admin) | 200 OK, JSON `{disponivel: true/false, motivo: "..."}` | `tests/api/test-verificar-disponibilidade-api.php` |
| `admin/api/verificar-disponibilidade.php` GET | Validação | Sim (admin) | 400 Bad Request, erro se parâmetros obrigatórios ausentes | `tests/api/test-verificar-disponibilidade-api.php` |

**Total de cenários:** 3

---

### 8. API de Exames (`admin/api/exames.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/exames.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-exames-api.php` |
| `admin/api/exames.php` POST | Permissão | Sim (instrutor) | 403 Forbidden (instrutor só pode ler) | `tests/api/test-exames-api.php` |
| `admin/api/exames.php` POST | Sucesso | Sim (admin) | 200 OK, JSON com `id` do exame criado | `tests/api/test-exames-api.php` |
| `admin/api/exames.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `aluno_id`, `tipo`, `resultado` inválidos | `tests/api/test-exames-api.php` |
| `admin/api/exames.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de exames | `tests/api/test-exames-api.php` |
| `admin/api/exames.php` PUT | Sucesso | Sim (admin) | 200 OK, JSON com exame atualizado | `tests/api/test-exames-api.php` |
| `admin/api/exames.php` DELETE | Sucesso | Sim (admin) | 200 OK, JSON `{success: true}` | `tests/api/test-exames-api.php` |

**Total de cenários:** 7

---

### 9. API de Faturas (`admin/api/financeiro-faturas.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/financeiro-faturas.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-financeiro-faturas-api.php` |
| `admin/api/financeiro-faturas.php` POST | Permissão | Sim (aluno) | 403 Forbidden (aluno só pode ler) | `tests/api/test-financeiro-faturas-api.php` |
| `admin/api/financeiro-faturas.php` POST | Sucesso | Sim (admin) | 200 OK, JSON com `id` da fatura criada | `tests/api/test-financeiro-faturas-api.php` |
| `admin/api/financeiro-faturas.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `aluno_id`, `valor`, `data_vencimento` inválidos | `tests/api/test-financeiro-faturas-api.php` |
| `admin/api/financeiro-faturas.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de faturas | `tests/api/test-financeiro-faturas-api.php` |
| `admin/api/financeiro-faturas.php` GET | Sucesso | Sim (aluno) | 200 OK, JSON array (apenas suas próprias faturas) | `tests/api/test-financeiro-faturas-api.php` |
| `admin/api/financeiro-faturas.php` PUT | Sucesso | Sim (admin) | 200 OK, JSON com fatura atualizada | `tests/api/test-financeiro-faturas-api.php` |
| `admin/api/financeiro-faturas.php` DELETE | Sucesso | Sim (admin) | 200 OK, JSON `{success: true}` | `tests/api/test-financeiro-faturas-api.php` |

**Total de cenários:** 8

---

### 10. API de Pagamentos (`admin/api/pagamentos.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/pagamentos.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-pagamentos-api.php` |
| `admin/api/pagamentos.php` POST | Permissão | Sim (aluno) | 403 Forbidden | `tests/api/test-pagamentos-api.php` |
| `admin/api/pagamentos.php` POST | Sucesso | Sim (admin) | 200 OK, JSON com `id` do pagamento criado, status da fatura atualizado | `tests/api/test-pagamentos-api.php` |
| `admin/api/pagamentos.php` POST | Validação | Sim (admin) | 400 Bad Request, erro se `fatura_id`, `valor` inválidos | `tests/api/test-pagamentos-api.php` |
| `admin/api/pagamentos.php` GET | Sucesso | Sim (admin) | 200 OK, JSON array de pagamentos | `tests/api/test-pagamentos-api.php` |

**Total de cenários:** 5

---

### 11. API de Resumo Financeiro do Aluno (`admin/api/financeiro-resumo-aluno.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/financeiro-resumo-aluno.php` GET | Autenticação | Não | 401 Unauthorized | `tests/api/test-financeiro-resumo-aluno-api.php` |
| `admin/api/financeiro-resumo-aluno.php` GET | Sucesso | Sim (admin) | 200 OK, JSON com resumo financeiro completo | `tests/api/test-financeiro-resumo-aluno-api.php` |
| `admin/api/financeiro-resumo-aluno.php` GET | Sucesso | Sim (aluno) | 200 OK, JSON com resumo financeiro (apenas seus dados) | `tests/api/test-financeiro-resumo-aluno-api.php` |
| `admin/api/financeiro-resumo-aluno.php` GET | Validação | Sim (admin) | 400 Bad Request, erro se `aluno_id` inválido | `tests/api/test-financeiro-resumo-aluno-api.php` |

**Total de cenários:** 4

---

### 12. API de Agenda do Aluno (`admin/api/aluno-agenda.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/aluno-agenda.php` GET | Autenticação | Não | 401 Unauthorized | `tests/api/test-aluno-agenda-api.php` |
| `admin/api/aluno-agenda.php` GET | Sucesso | Sim (aluno) | 200 OK, JSON com agenda consolidada (práticas + teóricas) | `tests/api/test-aluno-agenda-api.php` |
| `admin/api/aluno-agenda.php` GET | Validação | Sim (aluno) | 400 Bad Request, erro se `aluno_id` inválido ou ausente | `tests/api/test-aluno-agenda-api.php` |

**Total de cenários:** 3

---

### 13. API de Aulas do Instrutor (`admin/api/instrutor-aulas.php`)

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste Planejado |
|----------|---------------|--------------|-------------------|---------------------------|
| `admin/api/instrutor-aulas.php` POST | Autenticação | Não | 401 Unauthorized | `tests/api/test-instrutor-aulas-api.php` |
| `admin/api/instrutor-aulas.php` POST | Permissão | Sim (admin) | 403 Forbidden (apenas instrutores) | `tests/api/test-instrutor-aulas-api.php` |
| `admin/api/instrutor-aulas.php` POST | Sucesso | Sim (instrutor) | 200 OK, JSON `{success: true}` (cancelar/transferir aula) | `tests/api/test-instrutor-aulas-api.php` |
| `admin/api/instrutor-aulas.php` POST | Validação | Sim (instrutor) | 400 Bad Request, erro se tentar cancelar aula de outro instrutor | `tests/api/test-instrutor-aulas-api.php` |

**Total de cenários:** 4

---

## Resumo Geral

### Estatísticas

- **Total de endpoints com testes planejados:** 13 APIs principais
- **Total de cenários planejados:** 71 cenários
- **Distribuição por tipo:**
  - Testes de Autenticação: ~20
  - Testes de Sucesso: ~25
  - Testes de Validação: ~20
  - Testes de Permissão: ~6

### Prioridade Alta (rodar em todo deploy)

**APIs críticas que DEVEM ter testes automatizados:**

1. **`admin/api/alunos.php`** - CRUD de alunos (fluxo crítico 1)
2. **`admin/api/matricular-aluno-turma.php`** - Matrícula em turma (fluxo crítico 2)
3. **`admin/api/turma-presencas.php`** - Presenças teóricas (fluxo crítico 4)
4. **`admin/api/agendamento.php`** - Agendamento de aulas práticas (fluxo crítico 3)
5. **`admin/api/exames.php`** - Exames (fluxo crítico 5)
6. **`admin/api/financeiro-faturas.php`** - Faturas (fluxo crítico 6)
7. **`admin/api/pagamentos.php`** - Pagamentos (fluxo crítico 6)

**Total de cenários prioritários:** ~45 cenários

### Prioridade Média (rodar antes de deploy em produção)

- `admin/api/matriculas.php`
- `admin/api/turmas-teoricas.php`
- `admin/api/verificar-disponibilidade.php`
- `admin/api/financeiro-resumo-aluno.php`
- `admin/api/aluno-agenda.php`
- `admin/api/instrutor-aulas.php`

### Observações Importantes

1. **Ambiente de teste:** Os testes devem usar ambiente de homologação (`config_homolog.php`) ou banco de dados de teste isolado.

2. **Sessões:** Testes de autenticação precisarão simular sessões PHP ou usar cookies/headers de autenticação.

3. **Dados de teste:** Será necessário criar dados mínimos de teste (CFC, usuários, alunos, instrutores) antes de executar os testes.

4. **Multi-tenant:** Testes devem validar que dados de um CFC não são acessíveis por usuários de outro CFC (exceto Admin Global).

5. **Validações de negócio:** Alguns testes precisarão validar regras de negócio complexas (ex: exames obrigatórios antes de matricular em turma).

---

**Próximos passos:**
- Implementação dos testes será feita tarefa por tarefa nas próximas fases
- Cada arquivo de teste será criado seguindo a estrutura definida aqui
- Testes serão executados automaticamente antes de cada deploy (idealmente via CI/CD)

