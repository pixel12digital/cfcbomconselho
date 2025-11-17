# 📘 PLANO ESTRATÉGICO DO SISTEMA CFC – PIXEL12



**Objetivo:**  

Definir a estrutura completa (menus, papéis, jornadas e regras) de um sistema profissional para CFC, focado no essencial que funciona na prática, sem excesso de funcionalidades, pronto para ser implementado em fases e usado como checklist.



Não inicie a implementação apenas salve.

---

## 1. PAPÉIS DO SISTEMA

### 1.1. Admin Master (Dono da Plataforma / CFC ou Multi-CFC)

**Responsabilidade:** visão global, configuração e supervisão.

**Principais capacidades:**

- Gerenciar CFCs (se multi-unidade): dados da unidade, logomarca, endereço, parâmetros.

- Gerenciar usuários:

  - Criar/editar: Admin Secretaria, Instrutores, Usuários internos.

  - Atribuir permissões e perfis.

- Configurações gerais:

  - Regras de agendamento (limite de aulas/dia, intervalos mínimos).

  - Regras de bloqueio financeiro (quando impedir aulas práticas).

  - Parâmetros de faltas e reteste (ex.: "após 3 faltas práticas, bloquear até pagamento de reteste").

  - Modelos de documentos:

    - LADV (upload/URL/padrão)

    - Declaração para trabalho/escola

    - Contrato do aluno

  - Templates de notificações (push/app + e-mail/SMS se houver).

- Relatórios macro:

  - Quantidade de alunos por status (em processo, concluídos, trancados).

  - Indicadores teóricos, práticos, provas, financeiro (inadimplência, etc.).

---

### 1.2. Admin Secretaria (Operacional do CFC)

**Responsabilidade:** operação diária.

**Principais capacidades:**

- **Alunos & Matrículas**

  - Cadastrar e editar dados do aluno.

  - Criar/editar matrícula:

    - Tipo de serviço (1ª habilitação, adição, mudança, reciclagem…)

    - Categoria CNH

    - Processo DETRAN (RENACH, protocolo, situação)

    - Situação da matrícula (em análise, em formação, em exame, concluída, trancada, cancelada)

- **Exames**

  - Registrar e gerenciar:

    - Exame médico

    - Exame psicotécnico

    - Prova teórica

    - Prova prática

  - Agendar exame/prova (data + local + protocolo + resultado depois).

- **Turmas Teóricas**

  - Criar/editar turmas.

  - Matricular alunos em turma.

  - Registrar presenças/faltas teóricas.

- **Aulas Práticas**

  - Gerenciar agenda prática (por instrutor, por veículo, por aluno).

  - Remarcar/cancelar aulas.

  - Ver histórico de aulas do aluno.

- **Financeiro**

  - Gerar faturas/parcelas da matrícula.

  - Ver situação financeira do aluno.

  - Marcar pagamento, aplicar juros/multas, registrar "reteste".

  - Painel básico de inadimplência.

- **Documentos**

  - Gerar/baixar:

    - LADV (vinculada à aprovação teórica).

    - Declaração para escola/trabalho (período de aulas).

  - Histórico de documentos gerados.

- **Suporte / Comunicação**

  - Ver observações do aluno (internas).

  - (Futuro) Chat interno ou registro de contatos importantes.

---

### 1.3. Instrutor (PWA – Aplicativo de Aulas Práticas)

**Responsabilidade:** execução das aulas práticas em campo.

**Principais capacidades:**

- **Agenda do dia**

  - Lista de aulas práticas do dia (com hora, aluno, ponto de encontro, veículo).

  - Visualização por semana (resumida).

- **Detalhe da aula / ficha do aluno**

  - Foto do candidato.

  - Dados básicos (nome, categoria, status).

  - LADV disponível (PDF/imagem) para exibição rápida.

- **Controle da aula**

  - Botão **Iniciar aula**:

    - Registrar horário de início.

    - Registrar local atual (geo, se disponível).

    - Registrar KM inicial (campo manual + sugestão geolocalização, se houver).

    - Verificar regras antes de iniciar:

      - ✅ Verificar se aluno não está bloqueado por financeiro.

      - ✅ Verificar se o aluno não excedeu limite de faltas bloqueantes.

      - ✅ Verificar regra de intervalo e limite máximo diário de aulas.

  - Botão **Encerrar aula**:

    - Registrar horário de término.

    - Registrar KM final.

    - Marcar status da aula: concluída / falta do aluno / cancelada por outro motivo.

    - Campo de observações rápidas (ex.: "aluno muito nervoso", "chegou 20 min atrasado", etc.).

- **Comunicação rápida**

  - Botão "WhatsApp" (abre conversa com aluno).

  - Botão "Ligar" (discagem direta).

- **Notificações (para instrutor)**

  - Toque/vibração quando:

    - Aula do dia for alterada pela autoescola.

    - Nova aula for agendada.

    - Aula do dia for cancelada.

  - Quando instrutor alterar/encerrar aula:

    - **Notificação/registro na secretaria** (backoffice recebe evento).

**Regras especiais (instrutor):**

- Máximo **3 aulas práticas por dia por aluno**, com **intervalo mínimo de 30 minutos**:

  - Ex.: 1 aula + intervalo + 2 aulas, ou 2 aulas + intervalo + 1 aula.

- Após marcar a **primeira aula prática**:

  - Instrutor pode remanejar horários dentro da "janela permitida" pela autoescola (regras a definir em parâmetros).

- Se aluno está em atraso no financeiro (regra definida no painel financeiro):

  - ❌ Não permitir **Iniciar aula** (mostrar motivo na tela).

- Ao registrar **falta**:

  - Incrementar contador de faltas práticas.

  - Após 3 faltas → bloquear aulas, exibir mensagem:

    - "Candidato faltou 3 aulas; aulas bloqueadas. Necessário pagamento de reteste prático."

---

### 1.4. Aluno (PWA – Aplicativo do Aluno)

**Responsabilidade:** acompanhar o processo de habilitação e compromissos.

**Principais capacidades:**

- **Painel Geral**

  - Situação do processo: em formação / em exame / concluído.

  - Progresso:

    - Teórico: aulas cursadas, faltas, aulas restantes.

    - Prático: aulas realizadas, faltosas, faltantes.

    - Provas: teórica/prática – situação (aprovado/presente/agendado/reprovado).

  - Status financeiro resumo (em dia / em atraso / não lançado).

- **Aulas Teóricas**

  - Calendário/lista de próximas aulas teóricas (turma).

  - Histórico de participação: presentes x faltas.

- **Aulas Práticas**

  - Lista das próximas aulas práticas com horário, instrutor e ponto de encontro.

  - Histórico de aulas realizadas, faltas, aulas restantes (exibir claramente).

- **Notificações**

  - Toque e vibração:

    - Ao alterar qualquer aula (teórica ou prática).

    - 10 minutos antes do início de cada aula prática.

    - (Opcional) 30–60 minutos antes de provas.

  - Alerta após 3 faltas práticas:

    - "Candidato faltou 3 aulas; aulas bloqueadas. Procure a autoescola para regularização (reteste prático)."

- **Financeiro**

  - Listagem das parcelas/faturas da matrícula.

  - Status de cada parcela: paga / em aberto / vencida.

  - Botão para ver detalhes (data de vencimento, valor, forma de pagamento).

  - Link/Copiar PIX / boleto / instruções (se integração existir).

- **Documentos**

  - LADV:

    - Disponível após aprovação na prova teórica.

    - Visualizar e baixar (PDF/imagem) – porte obrigatório.

  - Declaração para escola/trabalho:

    - Gerar ou solicitar:

      - "Declaração de que está realizando aulas na autoescola do período X a Y".

    - Histórico de declarações emitidas.

- **Observações**

  - Campo para o aluno ver orientações importantes da autoescola (não editável).

---

## 2. JORNADA COMPLETA DO ALUNO (VISÃO MACRO)

1. **Chegada / Cadastro**

   - Secretaria cadastra aluno.

   - Define tipo de serviço e categoria (ou já cria matrícula).

2. **Matrícula**

   - Criar matrícula principal (sem duplicidade ativa para mesma categoria/serviço).

   - Gerar financeiro inicial (parcelas).

3. **Exames médico e psicotécnico**

   - Secretaria agenda exames (clínica/local, datas, resultados).

   - Sistema registra na timeline e atualiza cards.

4. **Aulas teóricas**

   - Aluno é matriculado em turma teórica.

   - Registro de presenças/faltas.

   - Progresso teórico é acompanhado (secretaria + aluno).

5. **Prova teórica**

   - Secretaria agenda prova teórica (tipo `teorico` em EXAMES).

   - Registra resultado (aprovado/reprovado).

   - Se aprovado:

     - Sistema libera LADV.

     - Abre etapa de aulas práticas.

6. **Aulas práticas**

   - Secretaria agenda ou libera agenda para instrutor.

   - Instrutor usa PWA para iniciar/encerrar aula, marcar km, registrar presença/falta.

   - Sistema controla:

     - Total contratadas x realizadas x faltas.

     - Regras de bloqueio por faltas e financeiro.

7. **Prova prática**

   - Secretaria agenda prova prática (tipo `pratico` em EXAMES).

   - Registra resultado (aprovado/reprovado).

8. **Conclusão**

   - Se provas concluídas com sucesso e financeiro OK, matrícula muda para "Concluída".

   - Timeline registra evento.

   - Sistema pode emitir declaração/certificado final.

---

## 3. ESTRUTURA DE MENUS POR PERFIL

### 3.1. Painel Admin Master

**Menu principal (web):**

- **Dashboard**

  - Visão geral (alunos por status, inadimplência, exames/provas).

- **CFCs / Unidades** (se multi-CFC)

  - Lista de unidades

  - Dados gerais e parâmetros específicos

- **Usuários & Permissões**

  - Admins

  - Instrutores

  - Perfis e permissões

- **Configurações do Sistema**

  - Regras de agendamento (limites, intervalos, turno).

  - Regras de bloqueio financeiro e por faltas.

  - Templates de documentos (LADV, declarações, contrato).

  - Templates de notificações (texto base, variáveis).

- **Relatórios**

  - Relatório de processos em andamento / concluídos.

  - Relatório de aulas práticas (por instrutor, por veículo).

  - Relatório financeiro macro.

---

### 3.2. Painel Admin Secretaria

**Menu principal (web):**

- **Dashboard**

  - Lista rápida de próximos exames/provas.

  - Alunos com aulas hoje.

  - Alertas de inadimplência e bloqueios.

- **Alunos**

  - Listagem, filtro por status, busca.

  - Botão "Detalhes" (abre modal completo que você já está refinando).

- **Matrículas**

  - Visão de matrículas por status.

  - Filtro por categoria, tipo de serviço, situação.

- **Teórico**

  - Turmas teóricas (criar/editar/ver alunos).

  - Presenças e faltas.

- **Prático / Agenda**

  - Agenda de aulas práticas (visualizações por:

    - Instrutor

    - Veículo

    - Aluno

  )

  - Ferramentas:

    - Remarcar aula

    - Cancelar aula

    - Bloquear períodos/instrutor/veículo

- **Exames & Provas**

  - Lista unificada:

    - Médico

    - Psicotécnico

    - Prova teórica

    - Prova prática

  - Filtros por tipo, status, data.

- **Financeiro**

  - Faturas/Parcelas

  - Atrasos por aluno

  - Retestes registrados

- **Documentos**

  - LADV

  - Declarações

  - Histórico gerado

- **Configuração do CFC**

  - Dados da unidade

  - Parametrizações locais (se não for centralizado no Master)

---

### 3.3. Painel Instrutor (PWA)

**Home (após login):**

- **Hoje**

  - Lista das aulas do dia com:

    - Hora

    - Aluno (nome + foto)

    - Local (ponto de encontro)

    - Veículo

- **Botões na aula:**

  - Iniciar aula

  - Encerrar aula

  - WhatsApp

  - Ligar

  - Ver LADV do aluno

**Menu lateral simples:**

- Hoje  

- Semana  

- Histórico recente  

- Perfil do instrutor (dados básicos)  

(Manter extremamente simples e rápido.)

---

### 3.4. Painel Aluno (PWA)

**Home (Dashboard):**

- Card "Processo": status atual (em formação, em exame, concluído).

- Card "Teórico": aulas cursadas / totais / faltas.

- Card "Prático": aulas realizadas / faltas / restantes.

- Card "Provas": situação teórica e prática.

- Card "Financeiro": resumo (em dia / em atraso / x parcelas em aberto).

**Menus:**

- Agenda:

  - Próximas aulas teóricas.

  - Próximas aulas práticas.

- Financeiro:

  - Lista de parcelas.

  - Detalhes e meios de pagamento.

- Documentos:

  - LADV.

  - Declarações.

- Perfil:

  - Dados básicos, contatos.

---

## 4. REGRAS DE NEGÓCIO CRÍTICAS (CHECKLIST)

### 4.1. Agendamento de Aulas Práticas

- [ ] Máx. **3 aulas por dia por aluno**.

- [ ] Mín. **30 minutos de intervalo** entre blocos de aulas.

- [ ] Permitir combinações:

  - [ ] 1 aula + intervalo + 2 aulas

  - [ ] 2 aulas + intervalo + 1 aula

- [ ] Após primeira aula prática agendada:

  - [ ] Instrutor pode remanejar horários **sem ultrapassar limites definidos**.

- [ ] Bloquear início de aula quando:

  - [ ] Aluno com parcelas em atraso (regra parametrizável).

  - [ ] Aluno com 3 faltas práticas (bloqueio por reteste).

### 4.2. Faltas

- [ ] Registrar falta no teórico e no prático.

- [ ] Contabilizar faltas por tipo (teórico/prático).

- [ ] Regra especial:

  - [ ] Ao atingir 3 faltas práticas:

    - [ ] Bloquear novas aulas.

    - [ ] Notificar aluno (PWA).

    - [ ] Exibir orientação: "necessário pagamento de reteste prático".

### 4.3. Financeiro

- [ ] Cada matrícula ligada a um conjunto de faturas.

- [ ] Status financeiro consolidado:

  - Não lançado / Em aberto / Em dia / Em atraso / Quitado.

- [ ] Integração com bloqueios:

  - [ ] Se "Em atraso" além de X dias → bloquear práticas (parametrizável).

- [ ] Campo/flag de "reteste" associado a pagamentos específicos.

### 4.4. Provas

- [ ] Prova Teórica:

  - [ ] Usar tabela EXAMES com tipo = `teorico`.

  - [ ] Registrar agendamento, resultado (aprovado/reprovado).

  - [ ] Se aprovado → liberar LADV e etapa prática.

- [ ] Prova Prática:

  - [ ] Usar tabela EXAMES com tipo = `pratico`.

  - [ ] Registrar agendamento, resultado (aprovado/reprovado).

  - [ ] Se aprovado e financeiro OK → conclusão da matrícula.

### 4.5. LADV

- [ ] Disponível somente após aprovação na prova teórica.

- [ ] Acessível em:

  - [ ] PWA do instrutor (para apresentação em fiscalização).

  - [ ] PWA do aluno (para porte).

- [ ] Formato: PDF ou imagem (upload ou gerado pelo sistema).

### 4.6. Notificações

- [ ] Para aluno:

  - [ ] Alteração em qualquer aula (teórica/prática).

  - [ ] Alerta 10 minutos antes da aula prática.

  - [ ] Aviso após 3 faltas práticas.

  - [ ] Aviso de financeiro em atraso (opcional).

- [ ] Para instrutor:

  - [ ] Alteração na agenda do dia.

  - [ ] Cancelamentos importantes.

- [ ] Para secretaria:

  - [ ] Aula marcada como falta.

  - [ ] Problemas recorrentes (opcional).

---

## 5. ROADMAP DE IMPLEMENTAÇÃO (FASES)

### Fase 0 – Raio-X do Sistema Atual

- [ ] Rodar script de diagnóstico (menus, tabelas, APIs, telas).

- [ ] Gerar arquivo `_DIAGNOSTICO-SISTEMA.md` com:

  - [ ] O que existe hoje (por módulo).

  - [ ] O que está parcialmente feito.

  - [ ] O que está faltando.

### Fase 1 – Consolidação do Módulo ALUNOS/MATRÍCULA/HISTÓRICO

- [ ] Finalizar modal de aluno (Dados / Matrícula / Histórico / Visualização).

- [ ] Garantir integração com:

  - [ ] Matrícula principal.

  - [ ] Histórico consolidado.

  - [ ] Cards de resumo (processo, teórico, prático, financeiro, provas).

### Fase 2 – Jornada Teórica Completa

- [ ] Revisar/ajustar:

  - [ ] Turmas teóricas.

  - [ ] Matrícula em turma.

  - [ ] Presenças/faltas.

- [ ] Timeline:

  - [ ] Início das aulas teóricas.

  - [ ] Conclusão das aulas teóricas.

### Fase 3 – Jornada Prática Completa

- [ ] Revisar modelo de aulas práticas:

  - [ ] Agendamento.

  - [ ] Presenças/faltas.

- [ ] Regras:

  - [ ] Limite diário de aulas.

  - [ ] Intervalo mínimo.

  - [ ] Bloqueios por faltas/financeiro.

- [ ] Timeline:

  - [ ] Primeira aula prática.

  - [ ] Aulas práticas concluídas.

### Fase 4 – Provas (Teórica e Prática)

- [ ] Confirmar uso de EXAMES (teorico/pratico).

- [ ] Ajustar telas de Exames.

- [ ] Preencher seção Provas na aba Matrícula.

- [ ] Atualizar card "Provas" no Histórico.

- [ ] Timeline completa de provas.

### Fase 5 – PWA Instrutor

- [ ] Definir layout mínimo da home e da lista de aulas.

- [ ] Implementar:

  - [ ] Iniciar/Encerrar aula.

  - [ ] KM inicial/final.

  - [ ] Botões WhatsApp/Ligar.

  - [ ] LADV no app.

  - [ ] Regras de bloqueio.

- [ ] Notificações básicas de alteração de aula.

### Fase 6 – PWA Aluno

- [ ] Dashboard com cards.

- [ ] Agenda (teórico + prático).

- [ ] Financeiro básico.

- [ ] LADV e declarações.

- [ ] Notificações (alterações e lembretes).

### Fase 7 – Refinos & Limpeza

- [ ] Remover código/telas/lixo não usados.

- [ ] Padronizar menus e nomes.

- [ ] Revisar performance e UX.

- [ ] Fechar checklist de "MVP pronto para uso real em CFC".

---

## 6. USO DESTE ARQUIVO

- Este arquivo serve como **guia mestre** do projeto.

- Cada item com `[ ]` vira checklist para o Cursor ir marcando/relatando.

- Qualquer mudança de escopo deve ser refletida aqui antes de alterar o código.
