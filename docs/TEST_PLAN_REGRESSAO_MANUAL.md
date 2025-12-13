# Planejamento de Checklists Manuais de Regressão - CFC Bom Conselho

**Versão:** 1.0  
**Data:** Janeiro 2025  
**Objetivo:** Definir quais checklists manuais serão executados antes de cada deploy para garantir regressão zero

---

## Introdução

Este documento vincula os **9 fluxos críticos** do sistema aos **checklists manuais** que devem ser executados antes de cada deploy (homologação e produção). Os checklists detalhados estão em `docs/testes/`.

**Referências:**
- `docs/PLANO_IMPL_PRODUCAO_CFC.md` - Fluxos críticos que NÃO podem ser quebrados
- `docs/testes/` - Checklists detalhados por módulo

---

## Mapeamento: Fluxo Crítico → Checklist → Cenários

| Fluxo Crítico | Checklist | Cenários Obrigatórios (resumo) |
|---------------|-----------|--------------------------------|
| **1. Nova matrícula de aluno** | `TESTES_ADMIN_ALUNOS.md` | Cadastrar novo aluno, editar aluno, criar matrícula, vincular matrícula a aluno |
| **2. Cadastro e gestão de turmas teóricas** | `TESTES_ADMIN_TURMAS.md` | Criar turma, agendar aulas em lote, matricular aluno na turma, editar turma |
| **3. Agendamento e execução de aulas práticas** | `TESTES_ADMIN_TURMAS.md` (parcialmente)<br>`TESTES_PWA_INSTRUTOR.md` (parcialmente) | Agendar aula prática, validar conflitos, ver aulas do instrutor |
| **4. Registro de presenças teóricas** | `TESTES_ADMIN_TURMAS.md` | Marcar presença em chamada teórica, conferir cálculo de frequência |
| **5. Fluxo de provas/exames** | `TESTES_ADMIN_ALUNOS.md` (parcialmente) | Registrar exame médico, registrar exame psicotécnico, validar bloqueio de matrícula se exames não OK |
| **6. Fluxo financeiro (lançamento, cobrança, baixa)** | `TESTES_ADMIN_FINANCEIRO.md` | Criar fatura, registrar pagamento, ver resumo financeiro do aluno, validar bloqueio por inadimplência |
| **7. Acesso e uso do painel do aluno** | `TESTES_PWA_ALUNO.md` | Login como aluno, ver dashboard, ver aulas (teóricas/práticas), ver financeiro, ver presenças |
| **8. Acesso e uso do painel do instrutor** | `TESTES_PWA_INSTRUTOR.md` | Login como instrutor, ver dashboard, ver agenda de aulas, visualizar detalhes do aluno |
| **9. Acesso administrativo (login/admin, permissões)** | `TESTES_REGRESSAO_LOGIN.md` | Login como admin, login como secretaria, validar permissões, validar multi-tenant (cfc_id) |

---

## Checklists Disponíveis

Os seguintes arquivos de checklist estão disponíveis em `docs/testes/`:

1. **`TESTES_PWA_ALUNO.md`**
   - Objetivo: Validar funcionalidades do PWA do Aluno
   - Cenários principais: Login, dashboard, aulas, financeiro, presenças

2. **`TESTES_PWA_INSTRUTOR.md`**
   - Objetivo: Validar funcionalidades do PWA do Instrutor
   - Cenários principais: Login, dashboard, agenda, detalhes do aluno, ações em aulas

3. **`TESTES_ADMIN_ALUNOS.md`**
   - Objetivo: Validar módulo Admin: Alunos
   - Cenários principais: Cadastro, edição, matrículas, histórico, exames

4. **`TESTES_ADMIN_TURMAS.md`**
   - Objetivo: Validar módulo Admin: Turmas Teóricas
   - Cenários principais: Criação de turma, agendamento de aulas, matrícula de alunos, presenças

5. **`TESTES_ADMIN_FINANCEIRO.md`**
   - Objetivo: Validar módulo Admin: Financeiro
   - Cenários principais: Criação de faturas, registro de pagamentos, resumos, bloqueios

6. **`TESTES_REGRESSAO_LOGIN.md`**
   - Objetivo: Validar sistema de login e autenticação
   - Cenários principais: Login de todos os perfis, logout, permissões, redirecionamentos

7. **`TESTES_REGRESSAO_FLUXOS_CRITICOS.md`**
   - Objetivo: Validação completa dos 9 fluxos críticos
   - Cenários principais: Um cenário por fluxo crítico (smoke test rápido)

---

## Estratégia de Execução

### Antes de cada deploy em homologação

**Checklist mínimo obrigatório:**
- Executar **pelo menos 1 cenário** de cada fluxo crítico (1 a 9)
- Usar o checklist `TESTES_REGRESSAO_FLUXOS_CRITICOS.md` como guia rápido
- Tempo estimado: 30-45 minutos

**Checklist completo recomendado:**
- Executar todos os cenários de `TESTES_REGRESSAO_LOGIN.md` (todos os perfis)
- Executar cenários principais de `TESTES_ADMIN_ALUNOS.md`, `TESTES_ADMIN_TURMAS.md`, `TESTES_ADMIN_FINANCEIRO.md`
- Executar cenários principais de `TESTES_PWA_ALUNO.md` e `TESTES_PWA_INSTRUTOR.md`
- Tempo estimado: 2-3 horas

### Antes de cada deploy em produção

**Checklist obrigatório completo:**
- Executar **todos os cenários** de todos os checklists listados acima
- Garantir que nenhum fluxo crítico foi quebrado
- Validar especialmente:
  - Login de todos os perfis
  - Nova matrícula completa
  - Agendamento e execução de aula prática
  - Registro de presença teórica
  - Fluxo financeiro completo (criar fatura → pagar → ver resumo)
- Tempo estimado: 4-6 horas

### Quando um módulo específico for alterado

**Checklist específico + regressão mínima:**
- Executar o checklist completo do módulo alterado
  - Exemplo: se alterar módulo de alunos → executar `TESTES_ADMIN_ALUNOS.md` completo
- Executar o checklist de regressão de fluxos críticos (`TESTES_REGRESSAO_FLUXOS_CRITICOS.md`)
- Tempo estimado: 1-2 horas

---

## Matriz de Cobertura por Fluxo Crítico

| Fluxo Crítico | Checklist Principal | Checklist Complementar | Cenários Mínimos |
|---------------|---------------------|------------------------|------------------|
| 1. Nova matrícula | `TESTES_ADMIN_ALUNOS.md` | - | 3 cenários |
| 2. Turmas teóricas | `TESTES_ADMIN_TURMAS.md` | - | 4 cenários |
| 3. Aulas práticas | `TESTES_ADMIN_TURMAS.md`<br>`TESTES_PWA_INSTRUTOR.md` | - | 3 cenários |
| 4. Presenças teóricas | `TESTES_ADMIN_TURMAS.md` | - | 2 cenários |
| 5. Exames | `TESTES_ADMIN_ALUNOS.md` | - | 3 cenários |
| 6. Financeiro | `TESTES_ADMIN_FINANCEIRO.md` | `TESTES_PWA_ALUNO.md` (visualização) | 4 cenários |
| 7. Painel aluno | `TESTES_PWA_ALUNO.md` | - | 5 cenários |
| 8. Painel instrutor | `TESTES_PWA_INSTRUTOR.md` | - | 4 cenários |
| 9. Login/permissões | `TESTES_REGRESSAO_LOGIN.md` | - | 5 cenários |

---

## Detalhamento dos Checklists

### TESTES_REGRESSAO_LOGIN.md

**Cenários obrigatórios:**
- [ ] Login bem-sucedido como Administrador
- [ ] Login bem-sucedido como Secretaria
- [ ] Login bem-sucedido como Instrutor
- [ ] Login bem-sucedido como Aluno
- [ ] Tentativa de login com credenciais inválidas
- [ ] Tentativa de login com usuário inativo
- [ ] Logout de todos os perfis
- [ ] Acesso a páginas protegidas sem autenticação (deve redirecionar)

**Quando executar:** Antes de cada deploy (homolog e produção)

---

### TESTES_ADMIN_ALUNOS.md

**Cenários obrigatórios:**
- [ ] Cadastro de um novo aluno com dados completos
- [ ] Edição de dados pessoais de um aluno existente
- [ ] Criação de matrícula para um aluno
- [ ] Registro de exames (médico, psicotécnico)
- [ ] Consulta do histórico completo do aluno

**Quando executar:** Antes de deploy em produção, sempre que módulo de alunos for alterado

---

### TESTES_ADMIN_TURMAS.md

**Cenários obrigatórios:**
- [ ] Criação de uma nova turma teórica (wizard completo)
- [ ] Agendamento de aulas teóricas em lote para uma turma
- [ ] Matrícula de alunos em uma turma teórica
- [ ] Registro de presença em aulas teóricas (chamada)
- [ ] Verificação do cálculo de frequência dos alunos na turma

**Quando executar:** Antes de deploy em produção, sempre que módulo de turmas for alterado

---

### TESTES_ADMIN_FINANCEIRO.md

**Cenários obrigatórios:**
- [ ] Criação de uma nova fatura para um aluno
- [ ] Registro de um pagamento parcial ou total para uma fatura
- [ ] Verificação do status da fatura (aberta, paga, vencida, parcial)
- [ ] Consulta do resumo financeiro de um aluno
- [ ] Aplicação de bloqueio por inadimplência

**Quando executar:** Antes de deploy em produção, sempre que módulo financeiro for alterado

---

### TESTES_PWA_ALUNO.md

**Cenários obrigatórios:**
- [ ] Login do aluno
- [ ] Visualização do dashboard (próxima aula, fase atual)
- [ ] Visualização de aulas (teóricas e práticas)
- [ ] Visualização de presenças teóricas
- [ ] Visualização de informações financeiras (parcelas, status)

**Quando executar:** Antes de deploy em produção, sempre que PWA do aluno for alterado

---

### TESTES_PWA_INSTRUTOR.md

**Cenários obrigatórios:**
- [ ] Login do instrutor
- [ ] Visualização da agenda de aulas do dia
- [ ] Visualização de detalhes do aluno (nome, CPF, categoria)
- [ ] Visualização de detalhes do veículo (placa, modelo)
- [ ] (Futuro) Início de uma aula prática (registro de horário e KM inicial)
- [ ] (Futuro) Encerramento de uma aula prática (registro de horário e KM final)

**Quando executar:** Antes de deploy em produção, sempre que PWA do instrutor for alterado

---

### TESTES_REGRESSAO_FLUXOS_CRITICOS.md

**Cenários obrigatórios (smoke test rápido):**
- [ ] Fluxo completo de Nova Matrícula de Aluno
- [ ] Fluxo completo de Cadastro e Gestão de Turmas Teóricas
- [ ] Fluxo completo de Agendamento e Execução de Aulas Práticas
- [ ] Fluxo completo de Registro de Presenças Teóricas
- [ ] Fluxo completo de Provas/Exames (agendamento, registro de resultado)
- [ ] Fluxo completo Financeiro (lançamento de fatura, registro de pagamento)
- [ ] Acesso e uso do Painel do Aluno (visualização de dados)
- [ ] Acesso e uso do Painel do Instrutor (visualização de agenda)
- [ ] Acesso administrativo (navegação básica, criação/edição de entidades)

**Quando executar:** Antes de cada deploy (homolog e produção) - checklist mínimo obrigatório

---

## Uso deste Plano

### Checklist de Pre-Deploy (Homologação)

```
[ ] Executar TESTES_REGRESSAO_FLUXOS_CRITICOS.md (9 cenários principais)
[ ] Executar TESTES_REGRESSAO_LOGIN.md completo
[ ] Executar cenários principais dos módulos alterados
[ ] Documentar resultados (OK / Falhou / Observações)
```

### Checklist de Pre-Deploy (Produção)

```
[ ] Executar TESTES_REGRESSAO_FLUXOS_CRITICOS.md completo
[ ] Executar TESTES_REGRESSAO_LOGIN.md completo
[ ] Executar TESTES_ADMIN_ALUNOS.md completo
[ ] Executar TESTES_ADMIN_TURMAS.md completo
[ ] Executar TESTES_ADMIN_FINANCEIRO.md completo
[ ] Executar TESTES_PWA_ALUNO.md completo
[ ] Executar TESTES_PWA_INSTRUTOR.md completo
[ ] Validar que nenhum fluxo crítico foi quebrado
[ ] Documentar resultados completos
```

### Checklist Pós-Alteração de Módulo

```
[ ] Executar checklist completo do módulo alterado
[ ] Executar TESTES_REGRESSAO_FLUXOS_CRITICOS.md (smoke test)
[ ] Validar que módulos relacionados não foram afetados
[ ] Documentar resultados
```

---

## Observações Importantes

1. **Ambiente de teste:** Os testes manuais devem ser executados no ambiente de homologação antes de produção.

2. **Dados de teste:** Garantir que dados mínimos de teste estejam disponíveis (seguir `docs/SEED_HOMOLOG.md`).

3. **Documentação de resultados:** Sempre documentar resultados dos testes, especialmente falhas ou observações, para rastreabilidade.

4. **Tempo de execução:** Respeitar os tempos estimados; não pular etapas para "economizar tempo" - isso pode resultar em bugs em produção.

5. **Priorização:** Se houver limitação de tempo, priorizar os 9 fluxos críticos (smoke test) sobre checklists completos de módulos não alterados.

6. **Integração com testes automatizados:** Os checklists manuais complementam (não substituem) os testes automatizados planejados em `docs/TEST_PLAN_API_AUTOMATIZADOS.md`.

---

**Próximos passos:**
- Detalhamento dos cenários específicos será feito nas fases 2, 3 e 4 conforme cada módulo for implementado
- Os arquivos de checklist em `docs/testes/` serão atualizados com casos de teste detalhados
- Checklist de regressão rápida (`TESTES_REGRESSAO_FLUXOS_CRITICOS.md`) será mantido sempre atualizado

