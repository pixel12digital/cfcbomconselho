# Mapeamento de Endpoints e Rotas Críticas - CFC Bom Conselho

**Versão:** 1.0  
**Data:** Janeiro 2025  
**Objetivo:** Documentar todos os endpoints e rotas críticas do sistema para planejamento de testes

---

## Introdução

Este documento lista todos os endpoints e rotas críticas do sistema CFC Bom Conselho, organizados por painel (Admin, Aluno, Instrutor) e vinculados aos fluxos críticos definidos no plano de implementação.

**Referências:**
- `docs/PLANO_IMPL_PRODUCAO_CFC.md` - Seção "Fluxos Críticos que NÃO podem ser quebrados"
- `docs/ONBOARDING_DEV_CFC.md` - Arquitetura e módulos do sistema

---

## Convenções

- **Rota/Endpoint:** Caminho completo da rota ou endpoint
- **Método:** GET, POST, PUT, DELETE (HTTP)
- **Uso Principal:** Função principal do endpoint
- **Autenticação:** Perfis que podem acessar (admin, secretaria, instrutor, aluno, público)
- **Fluxo Crítico Relacionado:** Número do fluxo crítico conforme `PLANO_IMPL_PRODUCAO_CFC.md`

---

## Painel Admin

### Rotas de Páginas (admin/index.php?page=...)

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/index.php?page=alunos` | GET | Listagem de alunos | admin, secretaria | 1 - Nova matrícula |
| `admin/index.php?page=turmas-teoricas` | GET | Listagem de turmas teóricas | admin, secretaria | 2 - Turmas teóricas |
| `admin/index.php?page=turma-chamada&turma_id=X` | GET | Tela de chamada/presença teórica | admin, secretaria, instrutor | 4 - Presenças teóricas |
| `admin/index.php?page=agendar-aula` | GET | Tela de agendamento de aulas práticas | admin, secretaria | 3 - Aulas práticas |
| `admin/index.php?page=exames` | GET | Tela de exames do aluno | admin, secretaria | 5 - Exames |
| `admin/index.php?page=financeiro-faturas` | GET | Tela de faturas | admin, secretaria | 6 - Financeiro |
| `admin/index.php?page=dashboard` | GET | Dashboard administrativo | admin, secretaria, instrutor | 9 - Acesso administrativo |

### APIs - Gestão de Alunos

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/alunos.php` | GET | Listar/buscar alunos | admin, secretaria | 1 - Nova matrícula |
| `admin/api/alunos.php` | POST | Criar novo aluno | admin, secretaria | 1 - Nova matrícula |
| `admin/api/alunos.php` | PUT | Atualizar aluno | admin, secretaria | 1 - Nova matrícula |
| `admin/api/alunos.php` | DELETE | Excluir aluno | admin, secretaria | 1 - Nova matrícula |

### APIs - Matrículas

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/matriculas.php` | GET | Listar matrículas | admin, secretaria | 1 - Nova matrícula |
| `admin/api/matriculas.php` | POST | Criar matrícula | admin, secretaria | 1 - Nova matrícula |
| `admin/api/matriculas.php` | PUT | Atualizar matrícula | admin, secretaria | 1 - Nova matrícula |
| `admin/api/matriculas.php` | DELETE | Excluir matrícula | admin, secretaria | 1 - Nova matrícula |

### APIs - Turmas Teóricas

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/turmas-teoricas.php` | GET | Listar turmas teóricas | admin, secretaria | 2 - Turmas teóricas |
| `admin/api/turmas-teoricas.php` | POST | Criar turma teórica | admin, secretaria | 2 - Turmas teóricas |
| `admin/api/turmas-teoricas.php` | PUT | Atualizar turma teórica | admin, secretaria | 2 - Turmas teóricas |
| `admin/api/matricular-aluno-turma.php` | POST | Matricular aluno em turma | admin, secretaria | 2 - Turmas teóricas |

### APIs - Presenças Teóricas

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/turma-presencas.php` | GET | Listar presenças de uma turma/aula | admin, secretaria, instrutor, aluno | 4 - Presenças teóricas |
| `admin/api/turma-presencas.php` | POST | Registrar presença teórica | admin, secretaria, instrutor | 4 - Presenças teóricas |
| `admin/api/turma-presencas.php` | PUT | Atualizar presença teórica | admin, secretaria, instrutor | 4 - Presenças teóricas |

### APIs - Aulas Práticas

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/agendamento.php` | GET | Listar aulas práticas (calendário) | admin, secretaria, instrutor | 3 - Aulas práticas |
| `admin/api/agendamento.php` | POST | Criar agendamento de aula prática | admin, secretaria | 3 - Aulas práticas |
| `admin/api/agendamento.php` | PUT | Atualizar agendamento | admin, secretaria | 3 - Aulas práticas |
| `admin/api/agendamento.php` | DELETE | Cancelar agendamento | admin, secretaria | 3 - Aulas práticas |
| `admin/api/verificar-disponibilidade.php` | GET | Verificar disponibilidade (instrutor/veículo/horário) | admin, secretaria | 3 - Aulas práticas |

### APIs - Exames

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/exames.php` | GET | Listar exames de um aluno | admin, secretaria, instrutor | 5 - Exames |
| `admin/api/exames.php` | POST | Registrar exame | admin, secretaria | 5 - Exames |
| `admin/api/exames.php` | PUT | Atualizar exame | admin, secretaria | 5 - Exames |
| `admin/api/exames.php` | DELETE | Excluir exame | admin, secretaria | 5 - Exames |

### APIs - Financeiro

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/financeiro-faturas.php` | GET | Listar faturas | admin, secretaria, aluno | 6 - Financeiro |
| `admin/api/financeiro-faturas.php` | POST | Criar fatura | admin, secretaria | 6 - Financeiro |
| `admin/api/financeiro-faturas.php` | PUT | Atualizar fatura | admin, secretaria | 6 - Financeiro |
| `admin/api/financeiro-faturas.php` | DELETE | Excluir fatura | admin, secretaria | 6 - Financeiro |
| `admin/api/pagamentos.php` | GET | Listar pagamentos | admin, secretaria | 6 - Financeiro |
| `admin/api/pagamentos.php` | POST | Registrar pagamento (baixa de fatura) | admin, secretaria | 6 - Financeiro |
| `admin/api/pagamentos.php` | DELETE | Excluir pagamento | admin, secretaria | 6 - Financeiro |
| `admin/api/financeiro-resumo-aluno.php` | GET | Resumo financeiro de um aluno | admin, secretaria, aluno | 6 - Financeiro |

---

## Painel Aluno

### Rotas de Páginas

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `aluno/login.php` | GET/POST | Login do aluno | público (GET), nenhuma (POST) | 7 - Painel aluno, 9 - Login |
| `aluno/dashboard.php` | GET | Dashboard do aluno | aluno | 7 - Painel aluno |
| `aluno/aulas.php` | GET | Listagem de aulas do aluno | aluno | 7 - Painel aluno |
| `aluno/financeiro.php` | GET | Visualização financeira do aluno | aluno | 7 - Painel aluno |
| `aluno/presencas-teoricas.php` | GET | Visualização de presenças teóricas | aluno | 7 - Painel aluno |

### APIs Usadas pelo Painel Aluno

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/aluno-agenda.php` | GET | Agenda consolidada do aluno (práticas + teóricas) | aluno | 7 - Painel aluno |
| `admin/api/financeiro-resumo-aluno.php` | GET | Resumo financeiro do aluno | aluno | 7 - Painel aluno |
| `admin/api/turma-presencas.php` | GET | Consultar presenças teóricas do aluno | aluno | 7 - Painel aluno |

---

## Painel Instrutor

### Rotas de Páginas

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `instrutor/login.php` | GET/POST | Login do instrutor | público (GET), nenhuma (POST) | 8 - Painel instrutor, 9 - Login |
| `instrutor/dashboard.php` | GET | Dashboard do instrutor | instrutor | 8 - Painel instrutor |
| `instrutor/aulas.php` | GET | Listagem de aulas do instrutor | instrutor | 8 - Painel instrutor |

### APIs Usadas pelo Painel Instrutor

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/api/agendamento.php` | GET | Listar aulas do instrutor (filtrado por instrutor_id) | instrutor | 8 - Painel instrutor |
| `admin/api/turma-presencas.php` | GET/POST/PUT | Gerenciar presenças teóricas | instrutor | 8 - Painel instrutor, 4 - Presenças |
| `admin/api/instrutor-aulas.php` | POST | Cancelar/transferir aula prática | instrutor | 8 - Painel instrutor |

---

## Autenticação Geral

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `index.php` | GET/POST | Login geral (redireciona conforme tipo) | público (GET), nenhuma (POST) | 9 - Login |
| `admin/logout.php` | GET | Logout admin/secretaria | admin, secretaria | 9 - Login |
| `aluno/logout.php` | GET | Logout aluno | aluno | 9 - Login |
| `instrutor/logout.php` | GET | Logout instrutor | instrutor | 9 - Login |

---

## Resumo

### Estatísticas

- **Total de endpoints mapeados:** 50+
- **Endpoints diretamente ligados a fluxos críticos:** 50+
- **Endpoints de autenticação:** 4 (login admin, aluno, instrutor + index.php geral)

### Distribuição por Fluxo Crítico

| Fluxo Crítico | Endpoints Relacionados |
|---------------|------------------------|
| 1 - Nova matrícula de aluno | 8 endpoints (alunos.php, matriculas.php) |
| 2 - Turmas teóricas | 5 endpoints (turmas-teoricas.php, matricular-aluno-turma.php) |
| 3 - Aulas práticas | 5 endpoints (agendamento.php, verificar-disponibilidade.php) |
| 4 - Presenças teóricas | 3 endpoints (turma-presencas.php) |
| 5 - Exames | 4 endpoints (exames.php) |
| 6 - Financeiro | 6 endpoints (financeiro-faturas.php, pagamentos.php, financeiro-resumo-aluno.php) |
| 7 - Painel aluno | 8 endpoints (páginas aluno/ + APIs aluno-agenda.php, financeiro-resumo-aluno.php) |
| 8 - Painel instrutor | 5 endpoints (páginas instrutor/ + APIs agendamento.php, turma-presencas.php, instrutor-aulas.php) |
| 9 - Acesso administrativo/login | 4 endpoints (login/logout) |

### Observações

1. **APIs compartilhadas:** Algumas APIs são usadas por múltiplos painéis (ex: `admin/api/financeiro-resumo-aluno.php` usado por admin e aluno), com validação de permissão baseada no tipo de usuário logado.

2. **Multi-tenant:** Todos os endpoints críticos respeitam o `cfc_id` da sessão (exceto Admin Global com `cfc_id = 0`).

3. **Autenticação:** A maioria das APIs verifica autenticação via `isLoggedIn()` e permissões via `getCurrentUser()['tipo']`.

4. **CORS:** Algumas APIs têm headers CORS (`Access-Control-Allow-Origin: *`) para suportar requisições AJAX.

5. **Formato de resposta:** Todas as APIs retornam JSON com estrutura `{success: boolean, ...}` ou similar.

6. **Tratamento de erros:** APIs críticas usam `ob_clean()` para evitar output antes do JSON e têm tratamento de exceções com código HTTP apropriado.

---

**Próximos passos:**  
- Este mapeamento será usado como base para o planejamento de testes automatizados (`docs/TEST_PLAN_API_AUTOMATIZADOS.md`)
- Os endpoints serão agrupados por prioridade de teste conforme critério de fluxos críticos

