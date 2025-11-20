# CHECKLIST DE TESTES FUNCIONAIS – CFC BOM CONSELHO

**Data:** 2025-01-19  
**Baseado em:** `docs/RAIO-X-PROJETO-CFC-COMPLETO.md`  
**Objetivo:** Validar todas as funcionalidades marcadas como "PRONTO PARA TESTE" no raio-X do projeto.

---

## 1. COMO VAMOS TESTAR (VISÃO GERAL)

### Objetivo
✅ Validar o que já está marcado como **PRONTO PARA TESTE** no raio-X:

- Dashboard
- Alunos (+ Matrículas + Histórico + Resumo financeiro)
- Financeiro – Faturas / Pagamentos
- Turmas Teóricas + Chamada
- Agenda (global)
- Provas & Exames
- Instrutores
- Veículos
- Salas

### Ordem Sugerida de Testes (Operacional)

1. **Dashboard** - Visão geral do sistema
2. **Configurações básicas** (Salas, Instrutores, Veículos) - Preparar dados de teste
3. **Alunos** (cadastro + matrícula) - Base para outros módulos
4. **Turmas teóricas** (criar turma, matricular aluno, presenças) - Depende de alunos
5. **Agenda global** (aulas práticas e teóricas) - Depende de alunos, instrutores, veículos
6. **Provas & Exames** - Depende de alunos
7. **Financeiro** (faturas + pagamentos + resumo do aluno) - Depende de alunos

> **Nota:** Após validar estes módulos, podemos entrar nos pontos "parciais" (bloqueios, relatórios, etc.) em outra rodada.

---

## 2. CHECKLIST DE TESTES – POR MÓDULO

### 2.1. Dashboard – `/admin/index.php?page=dashboard`

**Objetivo:** Verificar se o painel geral está consistente, sem erros e com números coerentes.

- [ ] **Acessar a URL do dashboard** e confirmar que carrega sem erros de PHP/JS
- [ ] **Conferir KPIs principais:**
  - [ ] Total de Alunos aparece e está correto
  - [ ] Total de Instrutores aparece e está correto
  - [ ] Total de Aulas aparece e está correto
  - [ ] Total de Veículos aparece e está correto
- [ ] **Verificar abas do dashboard:**
  - [ ] Aba "Visão Geral" carrega e exibe dados
  - [ ] Aba "Fases" carrega e exibe dados
  - [ ] Aba "Volume" carrega e exibe dados
  - [ ] Aba "Financeiro" carrega e exibe dados
  - [ ] Aba "Agenda" carrega e exibe dados
  - [ ] Aba "Exames" carrega e exibe dados
  - [ ] Aba "Prazos" carrega e exibe dados
- [ ] **Testar atalhos rápidos:**
  - [ ] Clicar em "Novo Aluno" → redireciona para tela de alunos
  - [ ] Clicar em "Nova Fatura" → redireciona para tela de faturas
  - [ ] Clicar em "Nova Turma" → redireciona para tela de turmas teóricas
  - [ ] Clicar em "Agendar Aula" → redireciona para agenda

**Arquivos relacionados:**
- `admin/pages/dashboard.php`
- `admin/index.php` (estatísticas)

---

### 2.2. Configurações Básicas

#### 2.2.1. Salas – `/admin/index.php?page=configuracoes-salas`

**Objetivo:** Validar cadastro e edição de salas para uso em turmas teóricas.

- [ ] **Abrir a página de Salas** e confirmar que lista as salas existentes (ou mostra mensagem se vazio)
- [ ] **Criar uma nova sala:**
  - [ ] Preencher nome da sala
  - [ ] Preencher capacidade
  - [ ] Preencher equipamentos (opcional)
  - [ ] Salvar e confirmar que aparece na listagem
- [ ] **Editar sala criada:**
  - [ ] Alterar capacidade
  - [ ] Salvar e verificar se alteração foi persistida
- [ ] **Verificar integração:**
  - [ ] A sala aparece como opção ao criar turma teórica
  - [ ] A sala aparece na agenda (se aplicável)

**Arquivos relacionados:**
- `admin/pages/configuracoes-salas.php`
- `admin/api/salas-real.php`

---

#### 2.2.2. Instrutores – `/admin/index.php?page=instrutores`

**Objetivo:** Validar cadastro e edição de instrutores para uso em aulas práticas.

- [ ] **Abrir a listagem de instrutores** e confirmar que carrega sem erros
- [ ] **Criar um instrutor de teste:**
  - [ ] Preencher nome completo
  - [ ] Preencher CPF
  - [ ] Selecionar categoria CNH
  - [ ] Preencher credencial (opcional)
  - [ ] Salvar e confirmar que aparece na listagem
- [ ] **Editar instrutor criado:**
  - [ ] Alterar categoria CNH
  - [ ] Salvar e verificar se alteração foi persistida
- [ ] **Verificar integração:**
  - [ ] O instrutor aparece como opção ao agendar aula prática na Agenda
  - [ ] O instrutor aparece na listagem de instrutores disponíveis

**Arquivos relacionados:**
- `admin/pages/instrutores.php`
- `admin/api/instrutores.php`

---

#### 2.2.3. Veículos – `/admin/index.php?page=veiculos`

**Objetivo:** Validar cadastro e edição de veículos para uso em aulas práticas.

- [ ] **Abrir a listagem de veículos** e confirmar que carrega sem erros
- [ ] **Cadastrar um veículo novo:**
  - [ ] Preencher modelo
  - [ ] Preencher marca
  - [ ] Preencher placa
  - [ ] Preencher ano
  - [ ] Selecionar categoria CNH compatível
  - [ ] Salvar e confirmar que aparece na listagem
- [ ] **Editar veículo criado:**
  - [ ] Alterar categoria CNH
  - [ ] Salvar e verificar se alteração foi persistida
- [ ] **Verificar integração:**
  - [ ] O veículo aparece como opção ao agendar aula prática na Agenda
  - [ ] O veículo aparece na listagem de veículos disponíveis

**Arquivos relacionados:**
- `admin/pages/veiculos.php`
- `admin/api/veiculos.php`

---

### 2.3. Alunos – `/admin/index.php?page=alunos`

**Objetivo:** Validar o módulo completo de alunos, incluindo cadastro, matrícula, histórico e resumo financeiro.

#### 2.3.1. Listagem e Filtros

- [ ] **Abrir a tela de Alunos** e confirmar que a lista carrega sem erros
- [ ] **Testar filtros de status:**
  - [ ] Filtro "Todos os Alunos" exibe todos
  - [ ] Filtro "Alunos Ativos" (em_formacao) exibe apenas alunos ativos
  - [ ] Filtro "Alunos em Exame" exibe apenas alunos em exame
  - [ ] Filtro "Alunos Concluídos" exibe apenas alunos concluídos
- [ ] **Testar busca:**
  - [ ] Buscar por nome do aluno → encontra corretamente
  - [ ] Buscar por CPF → encontra corretamente
  - [ ] Buscar por termo inexistente → retorna "Nenhum aluno encontrado"

**Arquivos relacionados:**
- `admin/pages/alunos.php`
- `admin/api/alunos.php`

---

#### 2.3.2. Cadastro de Novo Aluno

- [ ] **Clicar em "Novo Aluno"** (ou botão equivalente)
- [ ] **Preencher dados básicos:**
  - [ ] Nome completo
  - [ ] CPF (validar formato)
  - [ ] Data de nascimento
  - [ ] Telefone
  - [ ] E-mail (opcional)
  - [ ] Endereço completo
- [ ] **Salvar e verificar:**
  - [ ] Aluno aparece na listagem imediatamente
  - [ ] Não há erros no console do navegador
  - [ ] Mensagem de sucesso é exibida
- [ ] **Abrir modal de edição do aluno criado:**
  - [ ] Todos os campos estão preenchidos corretamente
  - [ ] Modal abre sem erros

**Arquivos relacionados:**
- `admin/pages/alunos.php` (função de cadastro)
- `admin/api/alunos.php` (POST)

---

#### 2.3.3. Matrícula do Aluno

- [ ] **Abrir modal de edição do aluno** (criado anteriormente)
- [ ] **Ir para aba "Matrícula"**
- [ ] **Criar uma matrícula nova:**
  - [ ] Selecionar categoria CNH
  - [ ] Selecionar tipo de serviço (1ª habilitação, adição, etc.)
  - [ ] Preencher valor do curso
  - [ ] Selecionar forma de pagamento
  - [ ] Preencher RENACH (opcional)
  - [ ] Preencher número do processo DETRAN (opcional)
- [ ] **Salvar e verificar:**
  - [ ] Matrícula aparece na aba Matrícula
  - [ ] Resumo financeiro aparece abaixo (read-only):
    - [ ] Total contratado
    - [ ] Total pago
    - [ ] Saldo em aberto
    - [ ] Status financeiro (badge)
    - [ ] Próximo vencimento (se houver)
    - [ ] Faturas vencidas (se houver)
  - [ ] Link "Ver Financeiro do Aluno" está funcional

**Arquivos relacionados:**
- `admin/pages/alunos.php` (aba Matrícula)
- `admin/api/matriculas.php`
- `admin/includes/FinanceiroService.php`
- `admin/api/financeiro-resumo-aluno-html.php`

---

#### 2.3.4. Detalhes do Aluno (Modal "Detalhes")

- [ ] **Abrir modal de detalhes do aluno** (ícone de olho ou botão "Detalhes")
- [ ] **Verificar cards de resumo:**
  - [ ] **Card "Situação Financeira":**
    - [ ] Badge de status aparece (Em dia, Em aberto, Inadimplente, etc.)
    - [ ] Valores aparecem (Contratado, Pago, Saldo)
    - [ ] Próximo vencimento aparece (se houver)
    - [ ] Quantidade de faturas vencidas aparece (se houver)
    - [ ] Link "Ver financeiro do aluno" está funcional
  - [ ] **Card "Progresso Teórico":**
    - [ ] Status aparece (Não iniciado, Em andamento, Concluído)
    - [ ] Frequência percentual aparece (se aluno estiver em turma)
    - [ ] Nome da turma aparece (se vinculado)
  - [ ] **Card "Progresso Prático":**
    - [ ] Status aparece (Não iniciado, Em andamento, Concluído)
    - [ ] Total de aulas aparece (X de Y aulas)
    - [ ] Percentual concluído aparece
  - [ ] **Card "Provas":**
    - [ ] Status de cada prova aparece (Médico, Psicotécnico, Teórica, Prática)
    - [ ] Resultados aparecem (se houver)
- [ ] **Verificar Timeline:**
  - [ ] Eventos do aluno aparecem em ordem cronológica
  - [ ] Matrícula aparece na timeline
  - [ ] Aulas aparecem na timeline (se houver)
  - [ ] Provas aparecem na timeline (se houver)
  - [ ] Faturas aparecem na timeline (se houver)

**Arquivos relacionados:**
- `admin/pages/alunos.php` (modal de detalhes)
- `admin/api/progresso_teorico.php`
- `admin/api/progresso_pratico.php`
- `admin/api/historico_aluno.php`
- `admin/api/financeiro-resumo-aluno.php`

---

#### 2.3.5. Edição de Aluno (Modal "Editar")

- [ ] **Abrir modal de edição do aluno** (ícone de lápis ou botão "Editar")
- [ ] **Testar aba "Dados":**
  - [ ] Campos são editáveis
  - [ ] Alterar telefone e salvar → verificar se persistiu
  - [ ] Alterar e-mail e salvar → verificar se persistiu
- [ ] **Testar aba "Matrícula":**
  - [ ] Campos da matrícula são editáveis
  - [ ] Resumo financeiro aparece (read-only)
  - [ ] Alterar valor do curso e salvar → verificar se persistiu
- [ ] **Testar aba "Histórico":**
  - [ ] Cards de resumo aparecem (mesmos do modal Detalhes)
  - [ ] Timeline aparece
  - [ ] Dados são consistentes com modal Detalhes

**Arquivos relacionados:**
- `admin/pages/alunos.php` (modal de edição)

---

### 2.4. Turmas Teóricas – `/admin/index.php?page=turmas-teoricas`

**Objetivo:** Validar criação de turmas teóricas, matrícula de alunos e registro de presenças.

#### 2.4.1. Criação de Turma (Wizard)

- [ ] **Acessar a tela de Turmas Teóricas**
- [ ] **Clicar em "Nova Turma"** (ou botão equivalente)
- [ ] **Passar pelas 4 etapas do wizard:**

  **Etapa 1 - Informações da Turma:**
  - [ ] Preencher nome da turma
  - [ ] Selecionar sala (usar sala criada anteriormente)
  - [ ] Selecionar curso/tipo
  - [ ] Selecionar modalidade
  - [ ] Preencher período (início e fim)
  - [ ] Preencher carga horária total
  - [ ] Preencher máximo de alunos
  - [ ] Salvar rascunho ou avançar

  **Etapa 2 - Agendamento de Aulas:**
  - [ ] Selecionar disciplina
  - [ ] Agendar aula (data, horário, instrutor)
  - [ ] Adicionar múltiplas aulas
  - [ ] Verificar se conflitos são detectados (mesmo horário, mesma sala)
  - [ ] Avançar para próxima etapa

  **Etapa 3 - Disciplinas/Carga Horária:**
  - [ ] Verificar se todas as disciplinas obrigatórias estão configuradas
  - [ ] Verificar carga horária total
  - [ ] Avançar para próxima etapa

  **Etapa 4 - Revisão/Confirmação:**
  - [ ] Revisar todas as informações
  - [ ] Finalizar criação da turma

- [ ] **Verificar resultado:**
  - [ ] Turma aparece na listagem de turmas
  - [ ] Status da turma está correto (ativa, completa, etc.)
  - [ ] Carga horária agendada está correta

**Arquivos relacionados:**
- `admin/pages/turmas-teoricas.php`
- `admin/includes/TurmaTeoricaManager.php`
- `admin/api/turmas-teoricas.php`

---

#### 2.4.2. Matrícula de Alunos na Turma

- [ ] **Abrir detalhes de uma turma criada**
- [ ] **Ir para etapa de matrícula de alunos:**
  - [ ] Lista de alunos elegíveis aparece (com exames OK)
  - [ ] Buscar aluno de teste criado anteriormente
- [ ] **Matricular aluno na turma:**
  - [ ] Selecionar aluno
  - [ ] Confirmar matrícula
  - [ ] Verificar se validações funcionam:
    - [ ] Aluno sem exames aprovados é bloqueado
    - [ ] Turma sem vagas bloqueia nova matrícula
    - [ ] Aluno já matriculado é detectado
- [ ] **Verificar resultado:**
  - [ ] Aluno aparece na lista de alunos da turma
  - [ ] Contador de alunos matriculados é atualizado
  - [ ] Aluno aparece vinculado na aba Matrícula do modal do aluno

**Arquivos relacionados:**
- `admin/pages/turmas-teoricas-step4.php`
- `admin/api/matricular-aluno-turma.php`
- `admin/api/alunos-aptos-turma.php`

---

#### 2.4.3. Chamada / Presenças – `/admin/index.php?page=turma-chamada`

- [ ] **Abrir a tela de chamada da turma**
- [ ] **Selecionar turma e aula:**
  - [ ] Selecionar turma criada anteriormente
  - [ ] Selecionar uma aula agendada
- [ ] **Marcar presenças:**
  - [ ] Lista de alunos matriculados aparece
  - [ ] Marcar presença de 1 aluno (checkbox ou botão)
  - [ ] Marcar presença de múltiplos alunos (marcação em lote)
  - [ ] Adicionar justificativa para falta (se aplicável)
  - [ ] Salvar presenças
- [ ] **Verificar resultado:**
  - [ ] Presenças ficam registradas ao recarregar a tela
  - [ ] Frequência do aluno é atualizada (se houver exibição)
  - [ ] Frequência percentual aparece no card "Progresso Teórico" do aluno
  - [ ] Não há erros no console

**Arquivos relacionados:**
- `admin/pages/turma-chamada.php`
- `admin/api/turma-presencas.php`

---

### 2.5. Agenda (Global) – `/admin/index.php?page=agendamento`

**Objetivo:** Validar calendário visual, criação de aulas práticas e validações de conflito.

#### 2.5.1. Visualização

- [ ] **Abrir a tela da Agenda** e confirmar que carrega sem erros
- [ ] **Navegar no calendário:**
  - [ ] Navegar entre dias (setas ou botões)
  - [ ] Navegar entre semanas
  - [ ] Navegar entre meses
  - [ ] Voltar para data atual
- [ ] **Verificar exibição de aulas:**
  - [ ] Aulas teóricas aparecem (se houver turmas com aulas agendadas)
  - [ ] Aulas práticas aparecem (se houver)
  - [ ] Cores/ícones diferenciam tipos de aula
  - [ ] Informações básicas aparecem (aluno, instrutor, veículo)

**Arquivos relacionados:**
- `admin/pages/agendamento.php`

---

#### 2.5.2. Criação de Aula Prática

- [ ] **No calendário, criar uma aula prática:**
  - [ ] Clicar em data/horário disponível
  - [ ] Selecionar aluno (usar aluno de teste criado anteriormente)
  - [ ] Selecionar instrutor (usar instrutor criado anteriormente)
  - [ ] Selecionar veículo (usar veículo criado anteriormente)
  - [ ] Escolher data/hora
  - [ ] Preencher observações (opcional)
- [ ] **Salvar e verificar:**
  - [ ] Aula aparece no calendário no horário correto
  - [ ] Informações aparecem corretamente (aluno, instrutor, veículo)
  - [ ] Status da aula está correto (agendada)
- [ ] **Testar validações de conflito:**
  - [ ] Tentar criar aula no mesmo horário com mesmo instrutor → sistema deve bloquear
  - [ ] Tentar criar aula no mesmo horário com mesmo veículo → sistema deve bloquear
  - [ ] Tentar criar mais de 3 aulas no mesmo dia para mesmo aluno → sistema deve bloquear (se regra estiver ativa)
  - [ ] Mensagens de erro são claras e explicativas

**Arquivos relacionados:**
- `admin/pages/agendamento.php`
- `admin/api/agendamento.php`
- `admin/api/verificar-disponibilidade.php`

---

#### 2.5.3. Edição e Cancelamento

- [ ] **Editar uma aula já criada:**
  - [ ] Clicar na aula no calendário
  - [ ] Alterar horário
  - [ ] Alterar instrutor
  - [ ] Alterar veículo
  - [ ] Salvar e verificar se alteração foi persistida
- [ ] **Cancelar uma aula:**
  - [ ] Clicar na aula no calendário
  - [ ] Clicar em "Cancelar" ou equivalente
  - [ ] Confirmar cancelamento
  - [ ] Verificar se status/visual muda (cor diferente, ícone, etc.)
  - [ ] Verificar se aula não aparece mais como disponível

**Arquivos relacionados:**
- `admin/pages/agendamento.php`
- `admin/api/agendamento.php` (PUT, DELETE)
- `admin/api/cancelar-aula.php`

---

### 2.6. Provas & Exames – `/admin/index.php?page=exames&tipo={tipo}`

**Objetivo:** Validar cadastro, agendamento e registro de resultados de exames.

#### 2.6.1. Cadastro e Filtros

**Para cada tipo de exame (médico, psicotécnico, teórico, prático):**

- [ ] **Acessar a URL com o tipo correspondente:**
  - [ ] `/admin/index.php?page=exames&tipo=medico`
  - [ ] `/admin/index.php?page=exames&tipo=psicotecnico`
  - [ ] `/admin/index.php?page=exames&tipo=teorico`
  - [ ] `/admin/index.php?page=exames&tipo=pratico`
- [ ] **Verificar listagem:**
  - [ ] Lista carrega sem erros
  - [ ] Filtros funcionam (por data, por aluno, por resultado)
- [ ] **Criar um exame de teste:**
  - [ ] Selecionar aluno (usar aluno de teste)
  - [ ] Preencher data do exame
  - [ ] Preencher local (opcional)
  - [ ] Salvar e confirmar que aparece na listagem

**Arquivos relacionados:**
- `admin/pages/exames.php`
- `admin/api/exames.php`

---

#### 2.6.2. Agendamento e Resultado

- [ ] **Agendar exame:**
  - [ ] Abrir exame criado
  - [ ] Preencher data/hora do agendamento
  - [ ] Preencher local do exame
  - [ ] Salvar agendamento
- [ ] **Registrar resultado:**
  - [ ] Abrir exame agendado
  - [ ] Selecionar resultado (Aprovado/Reprovado/Pendente)
  - [ ] Preencher observações (opcional)
  - [ ] Salvar resultado
- [ ] **Verificar integração:**
  - [ ] Exame aparece no card "Provas" do modal de detalhes do aluno
  - [ ] Status do exame está correto
  - [ ] Resultado aparece corretamente
  - [ ] Exame aparece na timeline do aluno

**Arquivos relacionados:**
- `admin/pages/exames.php`
- `admin/api/exames.php`
- `admin/includes/ExamesRulesService.php`

---

### 2.7. Financeiro – Faturas / Pagamentos – `/admin/index.php?page=financeiro-faturas`

**Objetivo:** Validar módulo financeiro completo, incluindo criação, pagamentos, cancelamento e resumo.

#### 2.7.1. Listagem e Filtros

- [ ] **Abrir a tela de Faturas** e confirmar que carrega sem erros
- [ ] **Verificar cards de resumo:**
  - [ ] Total de Faturas aparece e está correto
  - [ ] Faturas Pagas aparece e está correto
  - [ ] Faturas Vencidas aparece e está correto
  - [ ] Valor em Aberto aparece e está correto (formatado em R$)
- [ ] **Testar filtros:**
  - [ ] Filtrar por aluno → retorna apenas faturas do aluno selecionado
  - [ ] Filtrar por status (Aberta, Paga, Vencida, Cancelada) → retorna corretamente
  - [ ] Filtrar por período (data início e fim) → retorna faturas no período
  - [ ] Limpar filtros → retorna todas as faturas
- [ ] **Verificar exibição:**
  - [ ] Colunas aparecem corretamente (Aluno, Descrição, Valor, Vencimento, Status, Ações)
  - [ ] Descrição curta funciona (Entrada, Xª parcela, ou título completo)
  - [ ] Status visual "EM ATRASO" aparece em vermelho para faturas vencidas

**Arquivos relacionados:**
- `admin/pages/financeiro-faturas.php`
- `admin/api/financeiro-faturas.php`

---

#### 2.7.2. Criação de Nova Fatura

- [ ] **Criar uma nova fatura para um aluno de teste:**
  - [ ] Clicar em "Nova Fatura" ou botão equivalente
  - [ ] Selecionar aluno (usar aluno de teste criado anteriormente)
  - [ ] Preencher título/descrição
  - [ ] Preencher valor total
  - [ ] Preencher data de vencimento
  - [ ] Selecionar forma de pagamento
  - [ ] Preencher observações (opcional)
- [ ] **Salvar e verificar:**
  - [ ] Fatura aparece na listagem imediatamente
  - [ ] Descrição foi gerada corretamente (Entrada, Xª parcela, etc., se aplicável)
  - [ ] Status inicial está correto (Aberta)
  - [ ] Cards de resumo são atualizados
- [ ] **Verificar integração:**
  - [ ] Fatura aparece no resumo financeiro do aluno (modal Detalhes)
  - [ ] Fatura aparece no resumo financeiro do aluno (aba Matrícula)
  - [ ] Fatura aparece no resumo financeiro do aluno (aba Histórico)
  - [ ] Fatura aparece na timeline do aluno

**Arquivos relacionados:**
- `admin/pages/financeiro-faturas.php` (modal de criação)
- `admin/api/financeiro-faturas.php` (POST)

---

#### 2.7.3. Visualização de Fatura

- [ ] **Abrir modal de visualização** (ícone de olho)
- [ ] **Verificar informações básicas:**
  - [ ] Aluno (nome + CPF) aparece
  - [ ] Descrição completa aparece
  - [ ] Valor total aparece (formatado em R$)
  - [ ] Data de vencimento aparece (formatada dd/mm/aaaa)
  - [ ] Status aparece (badge colorido)
  - [ ] Forma de pagamento aparece
  - [ ] Observações aparecem (se houver)
- [ ] **Verificar resumo financeiro:**
  - [ ] Total da fatura aparece
  - [ ] Total pago aparece (soma dos pagamentos)
  - [ ] Saldo em aberto aparece (calculado corretamente)
  - [ ] Status financeiro aparece
- [ ] **Verificar tabela de pagamentos:**
  - [ ] Histórico de pagamentos aparece (se houver)
  - [ ] Colunas aparecem (Data, Valor, Método, Observações)
  - [ ] Valores estão formatados corretamente
  - [ ] Mensagem "Nenhum pagamento registrado" aparece se não houver pagamentos

**Arquivos relacionados:**
- `admin/pages/financeiro-faturas.php` (modal de visualização)
- `admin/api/financeiro-faturas.php` (GET)
- `admin/api/pagamentos.php` (GET)

---

#### 2.7.4. Edição de Fatura

- [ ] **Abrir modal de edição** (ícone de lápis)
- [ ] **Verificar campos editáveis:**
  - [ ] Título/Descrição é editável
  - [ ] Valor total é editável (verificar se há restrições)
  - [ ] Data de vencimento é editável
  - [ ] Status é editável (verificar regras de negócio)
  - [ ] Forma de pagamento é editável
  - [ ] Observações são editáveis
- [ ] **Verificar resumo de pagamentos (read-only):**
  - [ ] Total pago aparece
  - [ ] Saldo em aberto aparece
  - [ ] Último pagamento aparece (data)
- [ ] **Testar edição:**
  - [ ] Alterar data de vencimento
  - [ ] Salvar e verificar se alteração foi persistida
  - [ ] Alterar valor total
  - [ ] Salvar e verificar se alteração foi persistida

**Arquivos relacionados:**
- `admin/pages/financeiro-faturas.php` (modal de edição)
- `admin/api/financeiro-faturas.php` (PUT)

---

#### 2.7.5. Registro de Pagamento

- [ ] **Abrir modal de registrar pagamento** (ícone de check ou botão "Marcar como paga")
- [ ] **Verificar pré-preenchimento:**
  - [ ] Data de pagamento vem com data de hoje
  - [ ] Valor pago vem com saldo em aberto (calculado automaticamente)
  - [ ] Informações da fatura aparecem (aluno, valor total, saldo)
- [ ] **Registrar pagamento total:**
  - [ ] Preencher método de pagamento
  - [ ] Preencher observações (opcional)
  - [ ] Salvar pagamento
  - [ ] Verificar se:
    - [ ] Status da fatura muda para "PAGA"
    - [ ] Saldo em aberto fica zerado
    - [ ] Resumo financeiro do aluno é atualizado
    - [ ] Cards de resumo na tela de faturas são atualizados
- [ ] **Registrar pagamento parcial:**
  - [ ] Criar nova fatura de teste
  - [ ] Registrar pagamento com valor menor que o total
  - [ ] Verificar se:
    - [ ] Status da fatura muda para "PARCIAL"
    - [ ] Saldo em aberto é recalculado corretamente
    - [ ] Resumo financeiro do aluno é atualizado

**Arquivos relacionados:**
- `admin/pages/financeiro-faturas.php` (modal de pagamento)
- `admin/api/pagamentos.php` (POST)
- `admin/api/financeiro-faturas.php` (GET - para calcular saldo)

---

#### 2.7.6. Cancelamento de Fatura

- [ ] **Testar cancelamento de fatura sem pagamento:**
  - [ ] Criar fatura de teste sem pagamentos
  - [ ] Clicar em "Cancelar" (ícone X)
  - [ ] Confirmar cancelamento no diálogo
  - [ ] Verificar se:
    - [ ] Status muda para "CANCELADA"
    - [ ] Badge aparece com cor adequada
    - [ ] Cards de resumo são atualizados (sem recarregar página)
    - [ ] Fatura não aparece mais em "valor em aberto"
- [ ] **Testar cancelamento de fatura com pagamento:**
  - [ ] Criar fatura de teste
  - [ ] Registrar um pagamento (total ou parcial)
  - [ ] Tentar cancelar a fatura
  - [ ] Verificar se:
    - [ ] Sistema bloqueia o cancelamento
    - [ ] Mensagem de erro é clara ("Não é possível cancelar fatura que já possui pagamentos")
- [ ] **Testar cancelamento de fatura já cancelada:**
  - [ ] Tentar cancelar fatura já cancelada
  - [ ] Verificar se sistema bloqueia com mensagem adequada

**Arquivos relacionados:**
- `admin/pages/financeiro-faturas.php` (função cancelarFatura)
- `admin/api/financeiro-faturas.php` (PUT - validação de negócio)

---

#### 2.7.7. Faturas Vencidas / Status "EM ATRASO"

- [ ] **Criar fatura com vencimento no passado:**
  - [ ] Criar fatura de teste
  - [ ] Definir data de vencimento anterior à data atual
  - [ ] Salvar fatura
- [ ] **Verificar exibição:**
  - [ ] Status visual aparece como "EM ATRASO" (badge vermelho)
  - [ ] Badge está destacado visualmente
  - [ ] Fatura aparece na contagem de "Faturas Vencidas"
  - [ ] Fatura aparece no cálculo de "Valor em Aberto"
- [ ] **Testar job de marcar vencidas:**
  - [ ] Executar `admin/jobs/marcar_faturas_vencidas.php` (se possível)
  - [ ] Verificar se faturas vencidas têm status atualizado no banco
  - [ ] Verificar se exibição continua correta

**Arquivos relacionados:**
- `admin/pages/financeiro-faturas.php` (função formatarStatusFatura)
- `admin/jobs/marcar_faturas_vencidas.php`

---

## 3. TESTES DE INTEGRAÇÃO ENTRE MÓDULOS

### 3.1. Fluxo Completo: Aluno → Matrícula → Turma → Presenças → Progresso

- [ ] Criar aluno
- [ ] Criar matrícula para o aluno
- [ ] Criar turma teórica
- [ ] Matricular aluno na turma
- [ ] Agendar aulas da turma
- [ ] Marcar presenças do aluno
- [ ] Verificar se progresso teórico aparece no modal do aluno
- [ ] Verificar se frequência está correta

### 3.2. Fluxo Completo: Aluno → Fatura → Pagamento → Resumo Financeiro

- [ ] Criar aluno
- [ ] Criar matrícula para o aluno
- [ ] Criar fatura para o aluno
- [ ] Registrar pagamento da fatura
- [ ] Verificar se resumo financeiro aparece em:
  - [ ] Modal Detalhes do Aluno
  - [ ] Aba Matrícula do modal Editar Aluno
  - [ ] Aba Histórico do modal Editar Aluno
- [ ] Verificar se valores estão consistentes em todos os lugares

### 3.3. Fluxo Completo: Aluno → Agenda → Aula Prática → Progresso Prático

- [ ] Criar aluno
- [ ] Criar instrutor
- [ ] Criar veículo
- [ ] Agendar aula prática para o aluno
- [ ] Marcar aula como concluída (se possível)
- [ ] Verificar se progresso prático aparece no modal do aluno
- [ ] Verificar se contagem de aulas está correta

---

## 4. TESTES DE VALIDAÇÃO E REGRAS DE NEGÓCIO

### 4.1. Validações de Conflito na Agenda

- [ ] Tentar agendar aula no mesmo horário com mesmo instrutor → deve bloquear
- [ ] Tentar agendar aula no mesmo horário com mesmo veículo → deve bloquear
- [ ] Tentar agendar mais de 3 aulas no mesmo dia para mesmo aluno → deve bloquear (se regra estiver ativa)
- [ ] Mensagens de erro são claras e explicativas

### 4.2. Validações de Cancelamento de Fatura

- [ ] Tentar cancelar fatura sem pagamento → deve permitir
- [ ] Tentar cancelar fatura com pagamento → deve bloquear
- [ ] Tentar cancelar fatura já cancelada → deve bloquear
- [ ] Mensagens de erro são claras

### 4.3. Validações de Matrícula em Turma

- [ ] Tentar matricular aluno sem exames aprovados → deve bloquear
- [ ] Tentar matricular aluno em turma sem vagas → deve bloquear
- [ ] Tentar matricular aluno já matriculado → deve detectar
- [ ] Mensagens de erro são claras

---

## 5. TESTES DE PERFORMANCE E UX

### 5.1. Performance

- [ ] Modal de aluno abre em tempo razoável (< 3 segundos)
- [ ] Cards de resumo carregam sem travamentos
- [ ] Listagem de faturas carrega rapidamente mesmo com muitas faturas
- [ ] Agenda não trava ao navegar entre datas
- [ ] Não há erros no console do navegador

### 5.2. UX / Responsividade

- [ ] Modais são responsivos (funcionam em mobile)
- [ ] Tabelas são responsivas (scroll horizontal se necessário)
- [ ] Botões são clicáveis e têm feedback visual
- [ ] Mensagens de sucesso/erro são claras
- [ ] Loading states aparecem durante carregamentos
- [ ] Tooltips e ajuda contextual funcionam (se houver)

---

## 6. REGISTRO DE PROBLEMAS ENCONTRADOS

Durante os testes, registrar:

- [ ] **Erros de JavaScript** (console do navegador)
- [ ] **Erros de PHP** (páginas em branco, mensagens de erro)
- [ ] **Inconsistências de dados** (valores diferentes em lugares diferentes)
- [ ] **Problemas de UX** (botões que não funcionam, modais que não abrem)
- [ ] **Problemas de performance** (telas lentas, travamentos)
- [ ] **Problemas de validação** (sistema permite ações que não deveria)

**Template para registro:**

```
## Problema #X - [Título]

**Módulo:** [Alunos/Financeiro/etc.]
**URL:** [URL onde ocorreu]
**Passos para reproduzir:**
1. ...
2. ...
3. ...

**Comportamento esperado:**
...

**Comportamento atual:**
...

**Screenshots/Logs:**
...
```

---

## 7. PRÓXIMOS PASSOS APÓS TESTES

Após completar este checklist:

1. **Compilar lista de problemas encontrados**
2. **Priorizar correções** (P0 = bloqueante, P1 = importante, P2 = melhoria)
3. **Planejar correções** baseado no raio-X e nas pendências identificadas
4. **Testar módulos "parciais"** em rodada seguinte (bloqueios, relatórios, etc.)

---

**Última atualização:** 2025-01-19  
**Status:** Pronto para execução

