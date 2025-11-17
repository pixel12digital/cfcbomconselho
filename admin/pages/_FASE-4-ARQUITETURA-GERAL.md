# FASE 4 – ARQUITETURA GERAL DO SISTEMA CFC

**Data:** 2025-01-28  
**Status:** Rascunho aprovado para implementação  
**Contexto:**  
- Fase 0 – Raio-X geral ✔  
- Fase 1 – Limpeza & Base (matrículas, financeiro-faturas, pagamentos) ✔  
- Fase 2 – Financeiro & Pagamentos ✔  
- Fase 3 – Acadêmico & Agenda ✔  

**Objetivo desta fase:**  
Definir a arquitetura final do sistema de CFC (painéis, menus, jornadas e regras de negócio), em nível funcional, para que as próximas fases sejam apenas de **execução técnica** (sem dúvidas de escopo).

---

## 0. VISÃO GERAL DO PRODUTO

Sistema multi-CFC com 4 perfis principais:

1. **Admin Master (dono do sistema / SaaS)**

2. **Admin Local / Secretaria (CFC)**

3. **Instrutor (PWA)**

4. **Aluno (PWA)**

Pilares:

- **Acadêmico Teórico** (turmas, presenças, frequência, conclusão)

- **Acadêmico Prático** (agenda, aulas, instrutor, veículo, geolocalização)

- **Provas** (exame médico, psicotécnico, teoria e prática)

- **Financeiro** (faturas, pagamentos, bloqueios)

- **Jornada do Aluno** (linha do tempo unificada)

- **Notificações & Regras de Bloqueio** (faltas, inadimplência, documentação)

---

## 1. PERFIS E PERMISSÕES

### 1.1. Admin Master (plataforma SaaS)

Responsável por:

- Gerenciar CFCs clientes (tenants)

- Configurações globais

- Planos / Assinaturas

- Limites de uso (nº de alunos, instrutores, veículos)

- Relatórios globais

Visão: painel separado (já mapeado em fases anteriores) – **não é o foco imediato desta fase**, mas deve ser respeitado como camada de cima.

---

### 1.2. Admin Local / Secretaria (CFC)

Responsável por:

- **Cadastro e gestão de alunos**

- **Gestão de matrículas**

- **Gestão acadêmica (teórico + prático)**

- **Gestão financeira**

- **Agendamento de provas**

- Emissão de documentos (declarações, LADV digital, etc.)

- Relacionamento com alunos (mensagens/avisos básicos)

O Admin Local é "o usuário principal" do painel atual em `/admin`.

---

### 1.3. Instrutor (PWA)

Responsável por:

- Ver sua agenda diária de aulas práticas

- Iniciar aula prática

- Encerrar aula prática

- Registrar:

  - Km inicial / Km final

  - Situação da aula (realizada, falta, remarcada)

  - Observações

- Ter acesso rápido aos contatos do aluno:

  - Botão WhatsApp

  - Botão ligação

- Ver LADV do aluno (documento obrigatório de porte)

- Seguir regras do sistema:

  - No máximo 3 aulas/dia por instrutor

  - Intervalo mínimo de 30 minutos entre aulas

  - Não iniciar aula se aluno inadimplente

  - Não iniciar aula se aluno sem LADV

  - Notificações de alterações feitas pela autoescola

---

### 1.4. Aluno (PWA)

Responsável por:

- Acompanhar sua jornada no CFC:

  - **Aulas teóricas** (realizadas, faltas, restantes)

  - **Aulas práticas** (realizadas, faltas, restantes)

  - **Provas** (agendadas, resultados)

  - **Financeiro** (parcelas, vencimentos, pendências)

- Ter acesso à LADV (digital) para apresentação em fiscalização

- Baixar declaração de que está realizando aulas no CFC

- Receber notificações:

  - Alteração de aula

  - Aula em 10 minutos

  - Bloqueio por faltas

  - Bloqueio por inadimplência

  - Datas de prova

---

## 2. ESTRUTURA DE MENUS (PAINEL ADMIN LOCAL)

> Obs.: aqui é o "menu ideal" alvo. Nem tudo está implementado hoje; as próximas fases irão aproximar o sistema disso.

### 2.1. MENU PRINCIPAL

1. **Dashboard**

   - KPIs gerais

   - Próximos exames

   - Alunos em risco (faltas, inadimplência)

2. **Alunos**

   - Lista de alunos

   - Filtro por status (em formação, em exame, concluído, trancado, cancelado)

   - Acesso rápido ao **Modal Aluno** (já melhorado nas fases anteriores)

   - Sub-itens (se necessário):

     - Alunos ativos

     - Alunos em exame

     - Alunos concluídos

3. **Acadêmico**

   - **Turmas Teóricas**

   - **Presenças Teóricas**

   - **Aulas Práticas**

   - **Agenda Geral**

   - **Instrutores**

   - **Veículos**

   - **Salas**

4. **Provas & Exames**

   - Exame médico

   - Exame psicotécnico

   - Prova teórica

   - Prova prática

5. **Financeiro**

   - Faturas

   - Pagamentos

   - Relatórios financeiros

   - Configurações financeiras

6. **Configurações**

   - Dados do CFC

   - Cursos / categorias

   - Tabela de horários

   - Regras de bloqueio (faltas, inadimplência)

   - Modelos de documentos (declaração, etc.)

7. **Relatórios**

   - Frequência teórica

   - Conclusão prática

   - Provas (taxa de aprovação)

   - Inadimplência

8. **Sistema / Ajuda**

   - Logs

   - FAQ

   - Suporte

---

## 3. JORNADA DO ALUNO (DO CADASTRO À CONCLUSÃO)

### 3.1. Macro-jornada

1. Chegada na autoescola

2. Cadastro do aluno

3. Matrícula no curso

4. Exames médico e psicotécnico

5. Aulas teóricas

6. Prova teórica

7. Liberação LADV

8. Aulas práticas

9. Prova prática

10. Conclusão do processo e emissão de documentos finais

---

### 3.2. Jornada detalhada + sistemas que tocam

#### Etapa 1 – Cadastro do aluno

- Tela: **Alunos → Novo Aluno**

- Tabelas:

  - `alunos`

- Ações:

  - Cria aluno

  - Define categoria CNH e tipo de serviço

- Timeline:

  - Evento `aluno_cadastrado`

#### Etapa 2 – Matrícula

- Tela: Modal do aluno → Aba **Matrícula**

- Tabelas:

  - `matriculas`

- Regras:

  - 1 matrícula principal ativa por aluno (modelo atual)

- Timeline:

  - `matricula_criada`

  - `matricula_concluida` (quando status = concluída)

#### Etapa 3 – Exame médico + psicotécnico

- Tela: **Provas & Exames**

- Tabelas:

  - `exames` (tipo = 'medico', 'psicotecnico')

- Regras:

  - Não pode agendar prova teórica sem médico + psico aprovados

- Timeline:

  - `exame_medico_agendado`, `exame_medico_realizado`

  - `exame_psicotecnico_agendado`, `exame_psicotecnico_realizado`

#### Etapa 4 – Aulas teóricas

- Tela: **Acadêmico → Turmas Teóricas**

- Tabelas:

  - `turmas_teoricas`

  - `turma_matriculas`

  - `turma_aulas_agendadas`

  - `turma_presencas`

- Regras:

  - Frequência mínima (ex.: 75%)

  - Conclusão automática da parte teórica quando cumprir carga horária

- Timeline:

  - `aulas_teoricas_inicio`

  - `aulas_teoricas_concluidas`

#### Etapa 5 – Prova teórica

- Tela: **Provas & Exames**

- Tabelas:

  - `exames` (tipo = 'teorico')

- Regras:

  - Só libera prova teórica se:

    - Exame médico + psico ok

    - Teórico concluído

- Timeline:

  - `prova_teorica_agendada`

  - `prova_teorica_realizada`

  - `prova_teorica_aprovada` / `prova_teorica_reprovada`

#### Etapa 6 – LADV

- Tela: pode ser integrada na aba **Matrícula** / **Documentos**

- Tabelas:

  - `exames` ou `documentos_aluno` (a definir/talvez futura)

- Regras:

  - LADV só liberada se:

    - Prova teórica aprovada

    - Situação financeira mínima ok (regra configurável)

- PWA Instrutor / Aluno:

  - Exibe LADV em tela (PDF ou imagem)

#### Etapa 7 – Aulas práticas

- Tela Admin: **Acadêmico → Aulas Práticas / Agenda**

- PWA Instrutor: agenda do dia

- Tabelas:

  - `aulas` (tipo = 'pratica')

- Regras:

  - Máx. 3 aulas por dia / instrutor

  - Intervalo mínimo de 30min

  - Não permitir iniciar aula se:

    - Inadimplente

    - Sem LADV

    - Bloqueado por faltas

- Timeline:

  - `aulas_praticas_inicio`

  - `aulas_praticas_concluidas`

#### Etapa 8 – Prova prática

- Tela: **Provas & Exames**

- Tabelas:

  - `exames` (tipo = 'pratico')

- Regras:

  - Só libera prova prática se:

    - Carga prática mínima concluída (regras DETRAN/parametrizável)

- Timeline:

  - `prova_pratica_agendada`

  - `prova_pratica_realizada`

  - `prova_pratica_aprovada` / `prova_pratica_reprovada`

#### Etapa 9 – Conclusão do processo

- Tela: Modal Aluno → Aba Histórico / Matrícula

- Tabelas:

  - `matriculas` (status = concluída)

- Timeline:

  - `matricula_concluida`

- Ações:

  - Emissão de declaração final

  - Encerramento do financeiro (se ainda aberto)

---

## 4. REGRAS DE BLOQUEIO (VISÃO GERAL)

> Implementação técnica detalhada virá em uma fase própria; aqui é apenas a especificação.

1. **Financeiro**

   - Se parcela vencida > X dias:

     - Bloquear novas aulas práticas

     - Opcional: notificar aluno + secretaria

2. **Faltas teóricas**

   - Ao faltar ≥ N aulas:

     - Notificação de risco

   - Ao reprovar por frequência:

     - Regra para rematrícula / recuperação

3. **Faltas práticas**

   - Ao faltar 3 aulas práticas:

     - Bloquear novas marcações

     - Exibir mensagem:  

       "Candidato faltou três aulas, aulas bloqueadas. Necessário pagamento de reteste prático."

4. **LADV**

   - Sem LADV válida:

     - Não permite iniciar aula prática no app do instrutor

5. **Regras de horário**

   - Máx. 3 aulas/dia por instrutor

   - Intervalo mínimo de 30min entre aulas do mesmo instrutor

6. **Sequência lógica**

   - Médico+Psico → Teórico → Prova Teórica → LADV → Práticas → Prova Prática → Conclusão

---

## 5. APPS PWA – ESPECIFICAÇÃO FUNCIONAL

### 5.1. PWA – Instrutor

**Home / Agenda do dia**

- Lista de aulas do dia agrupadas por horário

- Info por aula:

  - Nome do aluno

  - Foto do aluno

  - Categoria CNH

  - Veículo

  - Local de início (se definido)

  - Status (agendada, em andamento, concluída, falta)

**Tela da aula (detalhe)**

- Foto do aluno (alta prioridade)

- Botões:

  - "Iniciar aula"

  - "Encerrar aula"

  - "WhatsApp"

  - "Ligar"

- Campos:

  - Km inicial (obrigatório ao iniciar)

  - Km final (obrigatório ao encerrar)

  - Observações

- Ações especiais:

  - Marcar falta

  - Solicitar remarcação (gera notificação para secretaria)

**Regras automáticas no PWA**

- Ao clicar "Iniciar aula":

  - Verifica:

    - horário

    - limite de 3 aulas/dia

    - intervalo mínimo

    - se aluno não está bloqueado por:

      - financeiro

      - faltas

      - LADV

- Ao "Encerrar aula":

  - Atualiza progresso prático

  - Impacta card de "Aulas Práticas" no painel admin

---

### 5.2. PWA – Aluno

**Home**

- Resumo:

  - Próxima aula

  - Próxima prova

  - Situação financeira (OK / pendente)

  - Situação geral (em formação / em exame / concluído)

**Aulas Teóricas**

- Lista de aulas + presenças

- Contadores:

  - Realizadas

  - Faltas

  - Restantes

- Avisos:

  - Faltas em excesso

**Aulas Práticas**

- Histórico e próximas

- Status e observações

- Aviso de bloqueio (se existir)

**Provas**

- Médico

- Psicotécnico

- Teórica

- Prática

- Resultado, datas, local

**Financeiro**

- Lista de parcelas

- Status (paga, em aberto, vencida)

- Links (futuro: pagamento online)

**Documentos**

- LADV digital

- Declaração (realização de aulas)

**Notificações**

- Push / in-app:

  - Aula em 10 minutos

  - Aula alterada/cancelada

  - Prova agendada

  - Bloqueios

---

## 6. CHECKLIST DE IMPLEMENTAÇÃO (ALTA NÍVEL)

> Este checklist será detalhado em fases futuras (Fase 5, 6…).

### 6.1. Organização / Limpeza

- [ ] Confirmar e remover páginas legadas mapeadas no raio-x

- [ ] Alinhar menu real com menu alvo (esconder o que não será usado)

- [ ] Centralizar rotas principais em um único lugar de documentação

### 6.2. Acadêmico

- [ ] Finalizar fluxo de presenças teóricas (faltas, frequência, conclusão)

- [ ] Finalizar fluxo prático (sincronização de aulas x progresso x bloqueios)

- [ ] Padronizar agenda (única fonte de verdade)

### 6.3. Financeiro

- [ ] Garantir bloqueios por inadimplência conectados às aulas práticas

- [ ] Relatórios mínimos (visão secretaria)

### 6.4. Provas

- [ ] UI para cadastro gerenciado de provas teóricas e práticas

- [ ] Amarração com regras (pré-requisitos)

- [ ] Atualização de cards e timeline

### 6.5. PWA Instrutor

- [ ] Definição técnica (rotas, APIs, autenticação)

- [ ] Tela de agenda

- [ ] Tela de aula (início/encerramento)

- [ ] Regras de bloqueio aplicadas no front

### 6.6. PWA Aluno

- [ ] Definição técnica (rotas, APIs, autenticação)

- [ ] Tela de resumo

- [ ] Aulas (teórica/prática)

- [ ] Provas

- [ ] Financeiro

- [ ] Documentos

---

## 7. COMO USAR ESTE ARQUIVO COM O CURSOR

1. Este arquivo é a **fonte oficial** da arquitetura do sistema CFC.

2. Cada fase de implementação futura deve citar explicitamente seções deste documento.

3. Exemplos de prompts para o Cursor:

   - "Implemente as regras de bloqueio descritas em **Seção 4 – Regras de Bloqueio**, começando pelo bloqueio por inadimplência (4.1)."

   - "Ajuste o menu atual para se aproximar da estrutura descrita em **Seção 2 – Estrutura de Menus (Painel Admin Local)**, mantendo apenas itens que já existem tecnicamente."

---

_Fim do documento da FASE 4 – ARQUITETURA GERAL._

