# RAIO-X COMPLETO DAS ABAS DO MODAL DE ALUNO

**Data:** 2025-01-XX  
**Objetivo:** Mapear o estado atual de implementação de todas as abas do modal `#modalAluno` antes de implementar novas features ou refatorações.

---

## 1. CONTEXTO DO MODAL DE ALUNO

**Arquivo principal:** `admin/pages/alunos.php`  
**Modal ID:** `#modalAluno`  
**Total de abas:** 7 abas

### Abas existentes:
1. **Dados** (ativa por padrão)
2. **Matrícula**
3. **Financeiro** (oculta por padrão - `display: none`)
4. **Documentos** (oculta por padrão - `display: none`)
5. **Agenda**
6. **Teórico**
7. **Histórico**

---

## 2. MAPEAMENTO ABA POR ABA

### 2.1. ABA "DADOS"

#### HTML (`admin/pages/alunos.php`, linhas 1824-2160)
**Status:** ✅ **ESSENCIAL E FUNCIONAL**

**Estrutura:**
- Formulário completo com campos de cadastro/edição
- Seções organizadas:
  - **Informações Pessoais:** Foto, Nome, CPF, RG, Renach, Data Nascimento, Status, Atividade Remunerada, Naturalidade, Nacionalidade, Email, Telefone
  - **CFC:** Seleção de CFC vinculado
  - **Tipo de Serviço:** Sistema dinâmico de operações (tipo + categoria CNH)
  - **Endereço:** CEP, Logradouro, Número, Bairro, Cidade, UF (com busca de CEP)
  - **Observações:** Campo de texto livre

**Campos exibidos:**
- ~20 campos de formulário
- Upload de foto (opcional)
- Validação de CPF
- Máscaras de entrada (CPF, Renach, CEP, Telefone)
- Seleção dinâmica de municípios baseada em estado

**Botões:**
- "Adicionar Tipo de Serviço" (dinâmico)
- "Buscar CEP" (integração com API)
- "Limpar Naturalidade"

#### JavaScript (`admin/pages/alunos.php` + `admin/assets/js/alunos.js`)
**Status:** ✅ **FUNCIONAL**

**Funções relacionadas:**
- `salvarAluno()` - Salva/atualiza aluno via API
- `editarAluno(id)` - Carrega dados do aluno para edição
- `previewFotoAluno(input)` - Preview de foto antes do upload
- `removerFotoAluno()` - Remove foto selecionada
- `adicionarOperacao()` - Adiciona linha de tipo de serviço
- `removerOperacao(id)` - Remove linha de tipo de serviço
- `carregarCategoriasOperacao()` - Carrega categorias baseadas no tipo
- `coletarOperacoes()` - Coleta todas as operações do formulário
- `carregarOperacoesExistentes(operacoes)` - Carrega operações ao editar
- `buscarCEP()` - Busca endereço via API dos Correios
- `extrairEstadoNaturalidade()` / `extrairMunicipioNaturalidade()` - Parsers de naturalidade

**Event listeners:**
- Submit do formulário (`formAluno`)
- Mudança de estado para carregar municípios
- Mudança de tipo de serviço para carregar categorias

#### API/Backend
**Endpoint:** `admin/api/alunos.php`  
**Métodos:** GET, POST, PUT, DELETE  
**Tabelas:** `alunos`, `cfcs`  
**Operações:** SELECT, INSERT, UPDATE (com upload de foto)

**Status:** ✅ **FUNCIONAL**

---

### 2.2. ABA "MATRÍCULA"

#### HTML (`admin/pages/alunos.php`, linha 2163-2165)
**Status:** ⚠️ **PLACEHOLDER / PARCIALMENTE IMPLEMENTADA**

**Estrutura atual:**
```html
<div class="tab-pane fade modal-tab-pane" id="matricula" role="tabpanel">
    <p>Conteúdo da aba Matrícula será reintroduzido depois.</p>
</div>
```

**Observação:** O HTML real foi removido durante a simplificação do modal. Existe backup em `_modalAluno-legacy.php`, mas não foi restaurado.

**Estrutura esperada (baseada no JS):**
- Container: `#matriculas-container`
- Tabela de matrículas com colunas: Categoria, Tipo Serviço, Status, Data Início, Ações
- Botão "Nova Matrícula" (não implementado)

#### JavaScript (`admin/pages/alunos.php`, linhas 5979-6038)
**Status:** ✅ **FUNCIONAL (mas sem HTML para renderizar)**

**Funções:**
- `carregarMatriculas(alunoId)` - Carrega matrículas via API
  - Chama: `api/matriculas.php?aluno_id=${alunoId}`
  - Renderiza tabela HTML dinamicamente
  - Exibe mensagem "Nenhuma matrícula encontrada" se vazio
- `adicionarMatricula()` - **NÃO IMPLEMENTADA** (mostra alerta "em desenvolvimento")
- `editarMatricula(id)` - **NÃO IMPLEMENTADA** (referenciada no HTML gerado, mas função não existe)

**Event listeners:**
- Disparado via `carregarDadosAba('matricula', alunoId)` quando a aba é ativada (`shown.bs.tab`)

#### API/Backend
**Endpoint:** `admin/api/matriculas.php`  
**Métodos:** GET, POST, PUT, DELETE  
**Tabelas:** `matriculas`, `alunos`  
**Operações:** SELECT (filtrado por `aluno_id`), INSERT, UPDATE, DELETE

**Status:** ✅ **FUNCIONAL**

**Observações:**
- API completa e funcional
- Validação de matrícula duplicada (mesma categoria + tipo_servico ativa)
- Verificação de aulas vinculadas antes de deletar

#### Redundância com outros módulos
**Status:** ⚠️ **REDUNDANTE / PODERIA SER ATALHO**

**Módulo relacionado:**
- Não existe página dedicada de "Matrículas" no sistema
- Matrículas são criadas/gerenciadas dentro do contexto do aluno
- **Conclusão:** Esta aba faz sentido como módulo próprio, mas precisa ser implementada completamente

---

### 2.3. ABA "FINANCEIRO"

#### HTML (`admin/pages/alunos.php`, linha 2168-2170)
**Status:** ⚠️ **PLACEHOLDER / NÃO IMPLEMENTADA**

**Estrutura atual:**
```html
<div class="tab-pane fade modal-tab-pane" id="financeiro" role="tabpanel">
    <p>Conteúdo da aba Financeiro será reintroduzido depois.</p>
</div>
```

**Observação:** A aba está **oculta por padrão** (`#financeiro-tab-container` com `display: none`). Só aparece se `ajustarAbasPorPerfil()` a habilitar.

#### JavaScript
**Status:** ❌ **NÃO IMPLEMENTADA**

**Funções:**
- Nenhuma função específica encontrada
- `carregarDadosAba('financeiro', alunoId)` não tem case no switch

**Event listeners:**
- Nenhum

#### API/Backend
**Status:** ❌ **NÃO EXISTE API ESPECÍFICA**

**Observação:** Existe módulo financeiro completo em `?page=financeiro-faturas`, mas não há API específica para listar faturas de um aluno.

#### Redundância com outros módulos
**Status:** ⚠️ **REDUNDANTE / DEVERIA SER ATALHO**

**Módulo relacionado:**
- **Página principal:** `?page=financeiro-faturas` (ou `admin/pages/financeiro-faturas.php`)
- **Função existente:** `abrirFinanceiroAluno(id)` redireciona para `?page=financeiro-faturas&aluno_id=${id}`
- **Conclusão:** Esta aba deveria ser um **resumo/atalho** mostrando:
  - Total de faturas (abertas, pagas, vencidas)
  - Últimas 5-10 faturas
  - Link "Ver todas as faturas" → redireciona para página completa
  - **NÃO deveria tentar ser uma tela completa de gestão financeira**

---

### 2.4. ABA "DOCUMENTOS"

#### HTML (`admin/pages/alunos.php`, linha 2173-2175)
**Status:** ⚠️ **PLACEHOLDER / PARCIALMENTE IMPLEMENTADA**

**Estrutura atual:**
```html
<div class="tab-pane fade modal-tab-pane" id="documentos" role="tabpanel">
    <p>Conteúdo da aba Documentos será reintroduzido depois.</p>
</div>
```

**Observação:** A aba está **oculta por padrão** (`#documentos-tab-container` com `display: none`). Só aparece se `ajustarAbasPorPerfil()` a habilitar.

**Estrutura esperada (baseada no JS):**
- Container: `#documentos-container`
- Grid de cards com documentos
- Botão "Novo Documento" / Upload (não implementado)

#### JavaScript (`admin/pages/alunos.php`, linhas 6040-6094)
**Status:** ✅ **FUNCIONAL (mas sem HTML para renderizar)**

**Funções:**
- `carregarDocumentos(alunoId)` - Carrega documentos via API
  - Chama: `api/aluno-documentos.php?aluno_id=${alunoId}`
  - Renderiza grid de cards HTML dinamicamente
  - Exibe mensagem "Nenhum documento encontrado" se vazio
- `adicionarDocumento()` - **NÃO IMPLEMENTADA** (mostra alerta "em desenvolvimento")
- `visualizarDocumento(id)` - **NÃO IMPLEMENTADA** (referenciada no HTML gerado, mas função não existe)
- `excluirDocumento(id)` - **NÃO IMPLEMENTADA** (referenciada no HTML gerado, mas função não existe)

**Event listeners:**
- Disparado via `carregarDadosAba('documentos', alunoId)` quando a aba é ativada (`shown.bs.tab`)

#### API/Backend
**Endpoint:** `admin/api/aluno-documentos.php`  
**Métodos:** GET, POST, PUT, DELETE  
**Tabelas:** `aluno_documentos` (criada automaticamente se não existir), `alunos`  
**Operações:** SELECT (filtrado por `aluno_id`), INSERT (com upload de arquivo), UPDATE, DELETE (remove arquivo físico)

**Status:** ✅ **FUNCIONAL E COMPLETA**

**Observações:**
- API completa com suporte a upload de arquivos (PDF, imagens)
- Validação de tipo e tamanho de arquivo
- Armazenamento em `uploads/aluno_documentos/{aluno_id}/`
- Status de documentos: pendente, aprovado, rejeitado

#### Redundância com outros módulos
**Status:** ✅ **MÓDULO PRÓPRIO (ÚNICO DO ALUNO)**

**Conclusão:** Esta aba faz sentido como módulo próprio, pois documentos são específicos do aluno. Precisa apenas:
- Restaurar HTML da aba
- Implementar funções de upload/visualização/exclusão
- Conectar com a API já existente

---

### 2.5. ABA "AGENDA"

#### HTML (`admin/pages/alunos.php`, linha 2178-2180)
**Status:** ⚠️ **PLACEHOLDER / PARCIALMENTE IMPLEMENTADA**

**Estrutura atual:**
```html
<div class="tab-pane fade modal-tab-pane" id="agenda" role="tabpanel">
    <p>Conteúdo da aba Agenda será reintroduzido depois.</p>
</div>
```

**Estrutura esperada (baseada no JS):**
- Container: `#aulas-container`
- Lista/timeline de aulas agendadas

#### JavaScript (`admin/pages/alunos.php`, linhas 6107-6114)
**Status:** ⚠️ **PARCIALMENTE IMPLEMENTADA**

**Funções:**
- `carregarDadosAba('agenda', alunoId)` - Apenas mostra "Carregando aulas agendadas..."
- **NÃO chama nenhuma API**
- **NÃO renderiza conteúdo real**

**API disponível:**
- `admin/api/aluno-agenda.php` - **EXISTE E ESTÁ FUNCIONAL**
  - Retorna: resumo de práticas/teóricas, timeline unificada, próxima aula
  - Tabelas: `aulas`, `turma_aulas_agendadas`, `turma_matriculas`, `instrutores`, `veiculos`

**Event listeners:**
- Disparado via `carregarDadosAba('agenda', alunoId)` quando a aba é ativada (`shown.bs.tab`)

#### Redundância com outros módulos
**Status:** ⚠️ **REDUNDANTE / DEVERIA SER RESUMO**

**Módulo relacionado:**
- **Página principal:** `?page=agendar-aula` (ou `admin/pages/agendamento.php`)
- **Função existente:** `agendarAulaAluno(id)` redireciona para `?page=agendar-aula&aluno_id=${id}`
- **Conclusão:** Esta aba deveria ser um **resumo/visualização** mostrando:
  - Próxima aula agendada
  - Progresso (X/Y aulas concluídas)
  - Timeline resumida (últimas 5-10 aulas)
  - Botão "Agendar Nova Aula" → redireciona para página completa
  - **NÃO deveria tentar ser uma tela completa de agendamento**

---

### 2.6. ABA "TEÓRICO"

#### HTML (`admin/pages/alunos.php`, linha 2183-2185)
**Status:** ⚠️ **PLACEHOLDER / NÃO IMPLEMENTADA**

**Estrutura atual:**
```html
<div class="tab-pane fade modal-tab-pane" id="teorico" role="tabpanel">
    <p>Conteúdo da aba Teórico será reintroduzido depois.</p>
</div>
```

**Estrutura esperada (baseada no JS):**
- Container: `#turma-container`
- Informações de turmas teóricas do aluno

#### JavaScript (`admin/pages/alunos.php`, linhas 6115-6122)
**Status:** ⚠️ **PARCIALMENTE IMPLEMENTADA**

**Funções:**
- `carregarDadosAba('teorico', alunoId)` - Apenas mostra "Carregando informações da turma..."
- **NÃO chama nenhuma API**
- **NÃO renderiza conteúdo real**

**APIs disponíveis:**
- `admin/api/aluno-agenda.php` - Retorna aulas teóricas do aluno (via `turma_matriculas` e `turma_aulas_agendadas`)
- `admin/api/matricular-aluno-turma.php` - Matricula aluno em turma
- `admin/api/remover-matricula-turma.php` - Remove matrícula

**Tabelas relacionadas:**
- `turma_matriculas` (ou `turma_alunos`) - Matrícula do aluno em turmas
- `turma_aulas_agendadas` - Aulas específicas da turma
- `turma_presencas` - Presença do aluno nas aulas
- `turmas_teoricas` - Dados da turma

#### Redundância com outros módulos
**Status:** ⚠️ **REDUNDANTE / DEVERIA SER RESUMO**

**Módulo relacionado:**
- **Página principal:** `?page=turmas-teoricas` (ou `admin/pages/turmas-teoricas.php`)
- **Página de matrículas:** `?page=turma-matriculas`
- **Conclusão:** Esta aba deveria ser um **resumo** mostrando:
  - Turmas em que o aluno está matriculado
  - Presença por turma (X/Y aulas)
  - Próxima aula teórica
  - Link "Ver detalhes da turma" → redireciona para página completa
  - **NÃO deveria tentar ser uma tela completa de gestão de turmas**

---

### 2.7. ABA "HISTÓRICO"

#### HTML (`admin/pages/alunos.php`, linha 2188-2190)
**Status:** ⚠️ **PLACEHOLDER / PARCIALMENTE IMPLEMENTADA**

**Estrutura atual:**
```html
<div class="tab-pane fade modal-tab-pane" id="historico" role="tabpanel">
    <p>Conteúdo da aba Histórico será reintroduzido depois.</p>
</div>
```

**Estrutura esperada (baseada no JS):**
- Container: `#historico-container`
- Timeline de eventos do aluno

#### JavaScript (`admin/pages/alunos.php`, linhas 6132-6172)
**Status:** ⚠️ **PARCIALMENTE IMPLEMENTADA**

**Funções:**
- `carregarHistorico(alunoId)` - Chama API, mas renderiza apenas placeholder
  - Chama: `api/historico.php?tipo=aluno&id=${alunoId}`
  - Renderiza apenas "Cadastro do Aluno" hardcoded
  - **NÃO processa dados reais da API**

**Função relacionada:**
- `visualizarHistoricoAluno(id)` - Redireciona para `?page=historico-aluno&id=${id}`

#### API/Backend
**Endpoint:** `admin/api/historico.php`  
**Métodos:** GET  
**Tabelas:** `alunos`, `aulas`, `instrutores`, `veiculos`, `cfcs`  
**Operações:** SELECT (filtrado por `aluno_id`)

**Status:** ✅ **FUNCIONAL (mas retorna dados não utilizados)**

**Dados retornados pela API:**
- Estatísticas de aulas (total, concluídas, canceladas, agendadas)
- Progresso percentual
- Lista de aulas (práticas)
- Próximas aulas
- **NÃO inclui:** matrículas, faturas, documentos, aulas teóricas, exames, mudanças de status

#### Página relacionada
**Arquivo:** `admin/pages/historico-aluno.php`  
**Status:** ✅ **EXISTE E FUNCIONAL**

**Conteúdo:**
- Página standalone com histórico mais completo
- Inclui aulas e exames
- Visualização em timeline

#### Redundância com outros módulos
**Status:** ✅ **MÓDULO PRÓPRIO (MAS PRECISA SER EXPANDIDO)**

**Conclusão:** Esta aba faz sentido como módulo próprio, mas precisa:
- Expandir API para incluir todos os eventos (matrículas, faturas, documentos, teóricas, exames, mudanças de status)
- Implementar renderização real da timeline
- Ou simplesmente redirecionar para `?page=historico-aluno&id=${alunoId}` se a página standalone for suficiente

---

## 3. RESUMO DO ESTADO ATUAL

| Aba | HTML | JavaScript | API | Status Geral | Redundância |
|-----|------|------------|-----|--------------|-------------|
| **Dados** | ✅ Completo | ✅ Funcional | ✅ Funcional | ✅ **ESSENCIAL E FUNCIONAL** | N/A |
| **Matrícula** | ❌ Placeholder | ✅ Funcional | ✅ Funcional | ⚠️ **PARCIALMENTE IMPLEMENTADA** | Módulo próprio |
| **Financeiro** | ❌ Placeholder | ❌ Não existe | ❌ Não existe | ❌ **NÃO IMPLEMENTADA** | Redundante (deveria ser resumo) |
| **Documentos** | ❌ Placeholder | ✅ Funcional | ✅ Funcional | ⚠️ **PARCIALMENTE IMPLEMENTADA** | Módulo próprio |
| **Agenda** | ❌ Placeholder | ⚠️ Parcial | ✅ Funcional | ⚠️ **PARCIALMENTE IMPLEMENTADA** | Redundante (deveria ser resumo) |
| **Teórico** | ❌ Placeholder | ⚠️ Parcial | ⚠️ Parcial | ⚠️ **PARCIALMENTE IMPLEMENTADA** | Redundante (deveria ser resumo) |
| **Histórico** | ❌ Placeholder | ⚠️ Parcial | ✅ Funcional | ⚠️ **PARCIALMENTE IMPLEMENTADA** | Módulo próprio (precisa expandir) |

---

## 4. IDENTIFICAÇÃO DE REDUNDÂNCIAS

### 4.1. Abas que são resumos de módulos existentes

#### **Financeiro**
- **Módulo principal:** `?page=financeiro-faturas`
- **Função de atalho:** `abrirFinanceiroAluno(id)` já existe
- **Recomendação:** Aba deveria mostrar apenas:
  - Resumo financeiro (total aberto, pago, vencido)
  - Últimas 5-10 faturas
  - Botão "Ver todas as faturas" → `?page=financeiro-faturas&aluno_id=${id}`

#### **Agenda**
- **Módulo principal:** `?page=agendar-aula`
- **Função de atalho:** `agendarAulaAluno(id)` já existe
- **API disponível:** `admin/api/aluno-agenda.php` (completa)
- **Recomendação:** Aba deveria mostrar:
  - Próxima aula agendada
  - Progresso (X/Y aulas concluídas)
  - Timeline resumida (últimas 5-10 aulas)
  - Botão "Agendar Nova Aula" → `?page=agendar-aula&aluno_id=${id}`

#### **Teórico**
- **Módulo principal:** `?page=turmas-teoricas`
- **Página de matrículas:** `?page=turma-matriculas`
- **Recomendação:** Aba deveria mostrar:
  - Turmas em que o aluno está matriculado
  - Presença por turma (X/Y aulas)
  - Próxima aula teórica
  - Link "Ver detalhes da turma" → página completa

### 4.2. Abas que são módulos próprios (únicos do aluno)

#### **Matrícula**
- Não existe página dedicada
- Matrículas são gerenciadas no contexto do aluno
- **Recomendação:** Implementar completamente (HTML + funções de CRUD)

#### **Documentos**
- Não existe página dedicada
- Documentos são específicos do aluno
- **Recomendação:** Implementar completamente (HTML + funções de upload/visualização/exclusão)

#### **Histórico**
- Existe página standalone (`?page=historico-aluno`)
- **Recomendação:** Expandir API para incluir todos os eventos OU simplesmente redirecionar para página standalone

---

## 5. SUGESTÃO DE ORGANIZAÇÃO PARA EXPERIÊNCIA DE CFC

### 5.1. Abas essenciais (manter e completar)

#### **1. Dados** ✅
- **Papel:** Cadastro/edição completa do aluno
- **O que o atendente deve conseguir:** Ver e editar todas as informações do aluno em poucos segundos
- **Status:** Funcional - apenas manter

#### **2. Matrícula** ⚠️
- **Papel:** Gerenciar matrículas do aluno (categoria CNH, tipo de serviço, status)
- **O que o atendente deve conseguir:** Ver matrículas ativas, criar nova matrícula, editar status
- **Status:** Implementar completamente
- **Ações necessárias:**
  - Restaurar HTML da aba
  - Implementar `adicionarMatricula()` e `editarMatricula(id)`
  - Conectar com API já existente

#### **3. Histórico** ⚠️
- **Papel:** Linha do tempo completa da jornada do aluno
- **O que o atendente deve conseguir:** Ver rapidamente todos os eventos importantes (cadastro, matrículas, aulas, faturas, documentos, exames)
- **Status:** Expandir API e implementar renderização
- **Ações necessárias:**
  - Expandir `admin/api/historico.php` para incluir:
    - Matrículas (criação, alteração, cancelamento)
    - Faturas (criação, vencimento, pagamento)
    - Documentos (upload, aprovação, rejeição)
    - Aulas teóricas (matrícula, presença)
    - Exames (agendamento, resultado)
    - Mudanças de status do aluno
  - Implementar renderização de timeline com todos os eventos
  - Ordenar cronologicamente

### 5.2. Abas de resumo/atalho (simplificar)

#### **4. Financeiro** (resumo)
- **Papel:** Resumo financeiro rápido + atalho para módulo completo
- **O que o atendente deve conseguir:** Ver rapidamente situação financeira e acessar faturas completas
- **Conteúdo sugerido:**
  - Card com totais: Aberto (R$ X), Pago (R$ Y), Vencido (R$ Z)
  - Tabela resumida com últimas 5-10 faturas
  - Botão "Ver todas as faturas" → `?page=financeiro-faturas&aluno_id=${id}`
- **Ações necessárias:**
  - Criar endpoint resumido: `admin/api/aluno-financeiro-resumo.php`
  - Implementar HTML da aba
  - Implementar `carregarFinanceiroResumo(alunoId)`

#### **5. Agenda** (resumo)
- **Papel:** Resumo de aulas + atalho para agendamento
- **O que o atendente deve conseguir:** Ver próxima aula, progresso, e agendar nova aula rapidamente
- **Conteúdo sugerido:**
  - Card "Próxima Aula" (data, hora, instrutor, veículo)
  - Barra de progresso (X/Y aulas concluídas)
  - Timeline resumida (últimas 5-10 aulas)
  - Botão "Agendar Nova Aula" → `?page=agendar-aula&aluno_id=${id}`
- **Ações necessárias:**
  - Usar API existente: `admin/api/aluno-agenda.php`
  - Implementar HTML da aba
  - Implementar `carregarAgendaResumo(alunoId)`

#### **6. Teórico** (resumo)
- **Papel:** Resumo de turmas teóricas + atalho para módulo completo
- **O que o atendente deve conseguir:** Ver turmas do aluno, presença, e acessar detalhes
- **Conteúdo sugerido:**
  - Lista de turmas matriculadas
  - Presença por turma (X/Y aulas)
  - Próxima aula teórica
  - Link "Ver detalhes da turma" → página completa
- **Ações necessárias:**
  - Criar endpoint resumido: `admin/api/aluno-teorico-resumo.php`
  - Implementar HTML da aba
  - Implementar `carregarTeoricoResumo(alunoId)`

### 5.3. Aba opcional (completar se necessário)

#### **7. Documentos** (módulo próprio)
- **Papel:** Gerenciar documentos do aluno (upload, aprovação, visualização)
- **O que o atendente deve conseguir:** Ver documentos, fazer upload, aprovar/rejeitar
- **Status:** API completa, falta apenas HTML e funções de UI
- **Ações necessárias:**
  - Restaurar HTML da aba
  - Implementar `adicionarDocumento()` (modal de upload)
  - Implementar `visualizarDocumento(id)` (modal de visualização)
  - Implementar `excluirDocumento(id)` (confirmação + delete)
  - Conectar com API já existente

---

## 6. RECOMENDAÇÕES FINAIS

### Prioridade ALTA (essenciais)
1. ✅ **Dados** - Manter como está (funcional)
2. ⚠️ **Matrícula** - Completar implementação (HTML + funções CRUD)
3. ⚠️ **Histórico** - Expandir API e implementar renderização completa

### Prioridade MÉDIA (resumos/atalhos)
4. ⚠️ **Financeiro** - Criar resumo + atalho
5. ⚠️ **Agenda** - Criar resumo + atalho (usar API existente)
6. ⚠️ **Teórico** - Criar resumo + atalho

### Prioridade BAIXA (opcional)
7. ⚠️ **Documentos** - Completar implementação (HTML + funções de UI)

### Abordagem sugerida
1. **Fase 1:** Completar Matrícula e Histórico (módulos próprios essenciais)
2. **Fase 2:** Implementar resumos de Financeiro, Agenda e Teórico (atalhos para módulos existentes)
3. **Fase 3:** Completar Documentos (se necessário para o fluxo do CFC)

---

## 7. OBSERVAÇÕES TÉCNICAS

### Event Listeners de Tabs
- **Localização:** `admin/pages/alunos.php`, linhas 6197-6218
- **Padrão:** Bootstrap 5 `shown.bs.tab` event
- **Função central:** `carregarDadosAba(abaId, alunoId)` - switch case que chama função específica de cada aba

### Visibilidade de Abas
- **Função:** `ajustarAbasPorPerfil()` - controla quais abas aparecem baseado no perfil do usuário
- **Abas ocultas por padrão:** Financeiro e Documentos (`display: none`)

### Estrutura de Containers
- Cada aba precisa de um container específico:
  - Matrícula: `#matriculas-container`
  - Documentos: `#documentos-container`
  - Agenda: `#aulas-container`
  - Teórico: `#turma-container`
  - Histórico: `#historico-container`

### Backup do Modal Original
- **Arquivo:** `admin/pages/_modalAluno-legacy.php`
- **Conteúdo:** Estrutura HTML original antes da simplificação
- **Uso:** Referência para restaurar conteúdo das abas

---

**FIM DO RELATÓRIO**

