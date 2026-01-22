# TESTES – Regressão dos Fluxos Críticos

## Objetivo

Este checklist valida que **todos os 9 fluxos críticos** do sistema continuam funcionando corretamente após alterações. Deve ser executado antes de cada deploy em homologação e produção.

## Fluxos Críticos

Os 9 fluxos críticos definidos no plano são:

1. Nova matrícula de aluno
2. Cadastro e gestão de turmas teóricas
3. Agendamento e execução de aulas práticas
4. Registro de presenças teóricas e práticas
5. Fluxo de provas/exames
6. Fluxo financeiro (lançamento, cobrança, baixa)
7. Acesso e uso do painel do aluno (web/PWA)
8. Acesso e uso do painel do instrutor (web/PWA)
9. Acesso administrativo (login/admin, permissões)

## Cenários de Regressão (rascunho)

### 1. Nova Matrícula de Aluno

- [ ] Cenário 1.1 – Cadastrar novo aluno completo (dados pessoais + matrícula)
- [ ] Cenário 1.2 – Editar aluno existente
- [ ] Cenário 1.3 – Validar que dados são salvos corretamente no banco

### 2. Cadastro e Gestão de Turmas Teóricas

- [ ] Cenário 2.1 – Criar nova turma teórica
- [ ] Cenário 2.2 – Matricular aluno na turma (com validações)
- [ ] Cenário 2.3 – Agendar aulas da turma

### 3. Agendamento e Execução de Aulas Práticas

- [ ] Cenário 3.1 – Agendar aula prática
- [ ] Cenário 3.2 – Validar conflitos (instrutor/veículo)
- [ ] Cenário 3.3 – Validar limite diário de aulas por instrutor

### 4. Registro de Presenças Teóricas e Práticas

- [ ] Cenário 4.1 – Registrar presença teórica via chamada
- [ ] Cenário 4.2 – Validar cálculo automático de frequência
- [ ] Cenário 4.3 – Registrar presença prática (implícita no status da aula)

### 5. Fluxo de Provas/Exames

- [ ] Cenário 5.1 – Registrar exame médico
- [ ] Cenário 5.2 – Registrar exame psicotécnico
- [ ] Cenário 5.3 – Validar bloqueio de matrícula se exames não estiverem OK

### 6. Fluxo Financeiro

- [ ] Cenário 6.1 – Criar fatura
- [ ] Cenário 6.2 – Registrar pagamento
- [ ] Cenário 6.3 – Validar bloqueio automático por inadimplência

### 7. Acesso e Uso do Painel do Aluno

- [ ] Cenário 7.1 – Login como aluno
- [ ] Cenário 7.2 – Acessar dashboard do aluno
- [ ] Cenário 7.3 – Visualizar aulas e financeiro

### 8. Acesso e Uso do Painel do Instrutor

- [ ] Cenário 8.1 – Login como instrutor
- [ ] Cenário 8.2 – Acessar dashboard do instrutor
- [ ] Cenário 8.3 – Visualizar agenda de aulas

### 9. Acesso Administrativo

- [ ] Cenário 9.1 – Login como admin
- [ ] Cenário 9.2 – Validar permissões (Admin Global vs Admin Secretaria)
- [ ] Cenário 9.3 – Validar controle multi-tenant (cfc_id)

## Modelo de Caso de Teste

Para cada cenário, quando formos detalhar:

- **Cenário:** `<nome>`
- **Pré-condições:** `<o que precisa existir>`
- **Passos:**
  1. `<passo 1>`
  2. `<passo 2>`
- **Resultado esperado:** `<comportamento esperado>`
- **Status:** [ ] Pendente / [x] Aprovado / [ ] Falhou

---

**⚠️ NOTA:** Este arquivo está em modo rascunho. Casos de teste serão detalhados conforme necessário, especialmente antes de cada deploy.

**Referência:** `docs/PLANO_IMPL_PRODUCAO_CFC.md` - Seção "Fluxos Críticos que NÃO podem ser quebrados"

**Quando executar:**
- Antes de cada deploy em homologação
- Antes de cada deploy em produção
- Após qualquer alteração que possa impactar fluxos críticos

