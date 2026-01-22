# Mapeamento de Campos e Informações do Aluno

## 📋 Resumo Executivo

Este documento mapeia todos os campos e informações exibidas nas diferentes abas do modal de aluno (`#modalAluno`) e no modal de visualização (`#modalVisualizarAluno`), incluindo origem no banco de dados e se são editáveis.

---

## 1️⃣ ABA "DADOS" (Edição/Criação)

### Seção: Informações Pessoais

| Campo | ID do Campo | Origem no Banco | É Editável? | Obrigatório? | Observações |
|-------|-------------|-----------------|-------------|--------------|-------------|
| Foto | `foto` | `alunos.foto` (VARCHAR 255) | ✅ Sim (upload) | ❌ Não | Aceita JPG, PNG, GIF, WebP até 2MB |
| Nome Completo | `nome` | `alunos.nome` (VARCHAR 100) | ✅ Sim | ✅ Sim | Campo principal de identificação |
| CPF | `cpf` | `alunos.cpf` (VARCHAR 14) | ✅ Sim | ✅ Sim | Com validação e máscara |
| RG | `rg` | `alunos.rg` (VARCHAR 20) | ✅ Sim | ❌ Não | Aceita letras |
| Renach | `renach` | `alunos.renach` (VARCHAR 11) | ✅ Sim | ✅ Sim | Máscara PE000000000 |
| Data de Nascimento | `data_nascimento` | `alunos.data_nascimento` (DATE) | ✅ Sim | ✅ Sim | Input type="date" |
| Status | `status` | `alunos.status` (ENUM) | ✅ Sim | ❌ Não | Select: ativo/inativo/concluido |
| Atividade Remunerada | `atividade_remunerada` | `alunos.atividade_remunerada` (INT) | ✅ Sim | ❌ Não | Checkbox (0/1) |
| Estado (Naturalidade) | `naturalidade_estado` | `alunos.naturalidade` (TEXT) | ✅ Sim | ❌ Não | Select de estados, usado para compor `naturalidade` |
| Município (Naturalidade) | `naturalidade_municipio` | `alunos.naturalidade` (TEXT) | ✅ Sim | ❌ Não | Select dependente do estado, usado para compor `naturalidade` |
| Naturalidade (campo oculto) | `naturalidade` | `alunos.naturalidade` (TEXT) | ✅ Sim (via JS) | ❌ Não | Composto automaticamente: "Município, Estado" |
| Nacionalidade | `nacionalidade` | `alunos.nacionalidade` (VARCHAR) | ✅ Sim | ❌ Não | Text input, padrão "Brasileira" |
| E-mail | `email` | `alunos.email` (VARCHAR 100) | ✅ Sim | ❌ Não | Input type="email" |
| Telefone | `telefone` | `alunos.telefone` (VARCHAR 20) | ✅ Sim | ❌ Não | Com máscara de telefone |

### Seção: CFC

| Campo | ID do Campo | Origem no Banco | É Editável? | Obrigatório? | Observações |
|-------|-------------|-----------------|-------------|--------------|-------------|
| CFC | `cfc_id` | `alunos.cfc_id` (INT) | ✅ Sim | ✅ Sim | Select com lista de CFCs (FK para `cfcs.id`) |

### Seção: Tipo de Serviço

| Campo | ID do Campo | Origem no Banco | É Editável? | Obrigatório? | Observações |
|-------|-------------|-----------------|-------------|--------------|-------------|
| Operações | `operacoes-container` | `alunos.operacoes` (TEXT/JSON) | ✅ Sim | ❌ Não | Container dinâmico com múltiplas operações. Cada operação tem: `operacao_tipo_{id}`, `operacao_categoria_{id}`. Armazenado como JSON no banco. |

**Estrutura de uma Operação:**
- Tipo de Operação: `operacao_tipo_{id}` → Valores: `primeira_habilitacao`, `adicao`, `mudanca`, `aula_avulsa`
- Categoria: `operacao_categoria_{id}` → Valores: A, B, AB, ACC, C, D, E (depende do tipo)

### Seção: Endereço

| Campo | ID do Campo | Origem no Banco | É Editável? | Obrigatório? | Observações |
|-------|-------------|-----------------|-------------|--------------|-------------|
| CEP | `cep` | `alunos.cep` (VARCHAR 10) | ✅ Sim | ❌ Não | Com botão de busca CEP (Correios) |
| Logradouro | `logradouro` | `alunos.endereco` (TEXT/JSON) | ✅ Sim | ❌ Não | Pode vir de busca CEP ou manual |
| Número | `numero` | `alunos.numero` (VARCHAR) | ✅ Sim | ❌ Não | Número do endereço |
| Bairro | `bairro` | `alunos.bairro` (VARCHAR) | ✅ Sim | ❌ Não | Pode vir de busca CEP ou manual |
| Cidade | `cidade` | `alunos.cidade` (VARCHAR) | ✅ Sim | ❌ Não | Pode vir de busca CEP ou manual |
| UF | `uf` | `alunos.estado` (CHAR 2) | ✅ Sim | ❌ Não | Select de estados |

**Nota:** O campo `alunos.endereco` pode ser armazenado como JSON ou string simples. O sistema trata ambos os casos.

### Seção: Observações

| Campo | ID do Campo | Origem no Banco | É Editável? | Obrigatório? | Observações |
|-------|-------------|-----------------|-------------|--------------|-------------|
| Observações | `observacoes` | `alunos.observacoes` (TEXT) | ✅ Sim | ❌ Não | Textarea para informações adicionais |

---

## 2️⃣ ABA "MATRÍCULA"

### Estrutura Atual

| Componente | ID/Classe | Origem no Banco | É Editável? | Observações |
|------------|-----------|-----------------|-------------|-------------|
| Cabeçalho | `h6.text-primary` | - | ❌ Não | Título fixo "Matrícula do Aluno" |
| Descrição | `p.text-muted` | - | ❌ Não | Texto informativo fixo |
| Lista de Matrículas | `#matriculas-list` | `matriculas` (tabela) | ⚠️ Parcial | Preenchido via AJAX (`api/matriculas.php`) |

### Campos Exibidos na Lista (via JS `carregarMatriculas()`)

| Campo Exibido | Origem no Banco | É Editável? | Observações |
|---------------|-----------------|-------------|-------------|
| Categoria CNH | `matriculas.categoria_cnh` | ❌ Não (apenas visualização) | Exibido na tabela |
| Tipo de Serviço | `matriculas.tipo_servico` | ❌ Não (apenas visualização) | Exibido na tabela |
| Status | `matriculas.status` | ❌ Não (apenas visualização) | Badge colorido (ativa=success, outras=secondary) |
| Data Início | `matriculas.data_inicio` | ❌ Não (apenas visualização) | Formatado como data BR |
| Ações | - | ⚠️ Sim (via botão) | Botão "Editar" chama `editarMatricula(id)` |

### Campos Planejados (não implementados ainda)

| Campo Planejado | Origem no Banco | É Editável? | Observações |
|-----------------|-----------------|-------------|-------------|
| Turma Teórica | `turma_matriculas.turma_id` → `turmas_teoricas` | ⚠️ Planejado | Relacionamento com turmas teóricas |
| Frequência | `turma_matriculas.frequencia_percentual` | ⚠️ Planejado | Percentual de presença |
| Data de Conclusão | `matriculas.data_conclusao` (se existir) | ⚠️ Planejado | Data de conclusão da matrícula |

**Nota:** A aba Matrícula atualmente exibe dados da tabela `matriculas` (se existir), mas pode ser expandida para incluir informações de `turma_matriculas` e `turmas_teoricas`.

---

## 3️⃣ ABA "HISTÓRICO"

### Estrutura Atual (Layout Base)

| Componente | ID/Classe | Origem no Banco | É Editável? | Observações |
|------------|-----------|-----------------|-------------|-------------|
| Título | `h5.text-primary` | - | ❌ Não | "Jornada do Aluno" |
| Descrição | `p.text-muted` | - | ❌ Não | "Visão completa da trajetória do aluno no CFC" |
| Card: Situação do Processo | `.card` | ⚠️ Calculado | ❌ Não | Placeholder - "Em breve resumo do progresso" |
| Card: Progresso Teórico | `.card` | ⚠️ Calculado | ❌ Não | Placeholder - "Em breve resumo do progresso" |
| Card: Progresso Prático | `.card` | ⚠️ Calculado | ❌ Não | Placeholder - "Em breve resumo do progresso" |
| Card: Situação Financeira | `.card` | ⚠️ Calculado | ❌ Não | Placeholder - "Em breve resumo do progresso" |
| Timeline | `#historico-container` | Múltiplas tabelas | ❌ Não | Placeholder - "Os eventos mais recentes do aluno aparecerão aqui" |
| Atalhos | Botões | - | ⚠️ Sim (navegação) | Links para: Agenda Completa, Financeiro, Turma Teórica |

### Fontes de Dados Planejadas para Timeline

| Tipo de Evento | Tabela(s) de Origem | Campos Relevantes | É Editável? |
|----------------|---------------------|-------------------|-------------|
| Cadastro do Aluno | `alunos` | `criado_em`, `nome`, `cpf` | ❌ Não |
| Alteração de Dados | `logs` / `auditoria` | `acao`, `tabela_afetada`, `registro_id`, `data` | ❌ Não |
| Matrícula Criada | `matriculas` | `data_inicio`, `categoria_cnh`, `tipo_servico`, `status` | ❌ Não |
| Aula Agendada | `aulas` | `data_aula`, `hora_inicio`, `tipo_aula`, `status` | ❌ Não |
| Aula Realizada | `aulas` | `data_aula`, `status='realizada'`, `observacoes` | ❌ Não |
| Aula Faltada | `aulas` | `data_aula`, `status='faltou'` | ❌ Não |
| Matrícula em Turma Teórica | `turma_matriculas` | `data_matricula`, `turma_id`, `status` | ❌ Não |
| Presença em Aula Teórica | `turma_presencas` | `presente`, `registrado_em`, `aula_id` | ❌ Não |
| Exame Agendado | `exames` | `data_agendada`, `tipo`, `status` | ❌ Não |
| Exame Realizado | `exames` | `data_realizacao`, `resultado`, `status` | ❌ Não |
| Fatura Criada | `financeiro_faturas` | `data_vencimento`, `valor`, `titulo`, `status` | ❌ Não |
| Pagamento Recebido | `financeiro_pagamentos` | `data_pagamento`, `valor`, `forma_pagamento` | ❌ Não |
| Status Alterado | `alunos` | `status`, `atualizado_em` | ❌ Não |

**Nota:** A aba Histórico está em fase de planejamento. A função `carregarHistorico()` existe mas está comentada, aguardando implementação do endpoint unificado de timeline.

---

## 4️⃣ MODAL "VISUALIZAR ALUNO" (Detalhes - Somente Leitura)

### Estrutura do Modal

| Seção | Componentes | Origem no Banco | É Editável? | Observações |
|-------|-------------|-----------------|-------------|-------------|
| Header | Foto + Nome + CPF + Badge Status | `alunos.foto`, `alunos.nome`, `alunos.cpf`, `alunos.status` | ❌ Não | Layout horizontal com foto circular |
| Informações Pessoais | RG, Renach, Data Nascimento, Naturalidade, Nacionalidade, E-mail, Telefone, Atividade Remunerada | `alunos.rg`, `alunos.renach`, `alunos.data_nascimento`, `alunos.naturalidade`, `alunos.nacionalidade`, `alunos.email`, `alunos.telefone`, `alunos.atividade_remunerada` | ❌ Não | Exibido em formato de lista (p tags) |
| CFC | Nome do CFC | `alunos.cfc_id` → `cfcs.nome` (JOIN) | ❌ Não | Exibido como `aluno.cfc_nome` (vem do JOIN na API) |
| Endereço | Logradouro, Número, Bairro, Cidade, UF, CEP | `alunos.endereco` (JSON ou string), `alunos.numero`, `alunos.bairro`, `alunos.cidade`, `alunos.estado`, `alunos.cep` | ❌ Não | Exibido em formato de endereço completo |
| Observações | Texto de observações | `alunos.observacoes` | ❌ Não | Exibido apenas se houver conteúdo |

### Campos Específicos Exibidos

| Campo | Origem no Banco | Formato de Exibição | É Editável? |
|-------|-----------------|---------------------|-------------|
| Foto | `alunos.foto` | Imagem circular 60x60px ou ícone placeholder | ❌ Não |
| Nome | `alunos.nome` | `<h4>` grande | ❌ Não |
| CPF | `alunos.cpf` | Texto abaixo do nome | ❌ Não |
| Status | `alunos.status` | Badge colorido (ativo=success, concluído=info, inativo=danger) | ❌ Não |
| RG | `alunos.rg` | Texto "RG: {valor}" ou "Não informado" | ❌ Não |
| Renach | `alunos.renach` | Texto "Renach: {valor}" ou "Não informado" | ❌ Não |
| Data de Nascimento | `alunos.data_nascimento` | Formatado como data BR (`toLocaleDateString('pt-BR')`) | ❌ Não |
| Naturalidade | `alunos.naturalidade` | Texto simples ou "Não informado" | ❌ Não |
| Nacionalidade | `alunos.nacionalidade` | Texto simples ou "Não informado" | ❌ Não |
| E-mail | `alunos.email` | Texto simples ou "Não informado" | ❌ Não |
| Telefone | `alunos.telefone` | Texto simples ou "Não informado" | ❌ Não |
| Atividade Remunerada | `alunos.atividade_remunerada` | Badge (1=Sim com ícone briefcase, 0=Não com ícone user) | ❌ Não |
| CFC Nome | `cfcs.nome` (via JOIN) | Texto "CFC: {nome}" ou "Não informado" | ❌ Não |
| Endereço Completo | `alunos.endereco` + campos separados | Formato de endereço completo (logradouro + número, bairro, cidade - UF, CEP) | ❌ Não |
| Observações | `alunos.observacoes` | Texto simples (exibido apenas se houver conteúdo) | ❌ Não |

### Componentes de Leitura (Não Editáveis)

- **Botão "Editar Aluno"**: Abre o modal de edição (`#modalAluno`) em modo edição
- **Botão "Fechar"**: Fecha o modal de visualização
- **Overlay**: Modal overlay com z-index controlado

---

## 5️⃣ CAMPOS NÃO EXIBIDOS (mas existentes no banco)

| Campo | Tabela | Tipo | Observações |
|-------|--------|------|-------------|
| `id` | `alunos` | INT (PK) | Usado internamente, não exibido diretamente |
| `criado_em` | `alunos` | TIMESTAMP | Usado para histórico, não exibido na aba Dados |
| `atualizado_em` | `alunos` | TIMESTAMP | Usado para histórico, não exibido na aba Dados |
| `categoria_cnh` | `alunos` | ENUM | Campo legado, substituído por `operacoes` (JSON) |
| `tipo_servico` | `alunos` | VARCHAR(50) | Campo legado, substituído por `operacoes` (JSON) |

---

## 6️⃣ RELACIONAMENTOS E TABELAS RELACIONADAS

### Tabelas Relacionadas ao Aluno

| Tabela | Relação | Campos Relevantes | Uso Atual |
|--------|----------|-------------------|-----------|
| `matriculas` | `aluno_id` → `alunos.id` | `categoria_cnh`, `tipo_servico`, `status`, `data_inicio` | Exibida na aba Matrícula |
| `turma_matriculas` | `aluno_id` → `alunos.id` | `turma_id`, `data_matricula`, `status`, `frequencia_percentual` | Planejado para Histórico/Matrícula |
| `aulas` | `aluno_id` → `alunos.id` | `data_aula`, `hora_inicio`, `tipo_aula`, `status`, `instrutor_id`, `veiculo_id` | Planejado para Histórico |
| `exames` | `aluno_id` → `alunos.id` | `tipo`, `data_agendada`, `data_realizacao`, `resultado`, `status` | Planejado para Histórico |
| `financeiro_faturas` | `aluno_id` → `alunos.id` | `titulo`, `valor`, `data_vencimento`, `status` | Planejado para Histórico |
| `cfcs` | `id` → `alunos.cfc_id` | `nome`, `cnpj`, `razao_social` | Exibido na aba Dados e Visualizar |
| `logs` / `auditoria` | `registro_id` → `alunos.id` | `acao`, `tabela_afetada`, `data`, `usuario_id` | Planejado para Histórico |

---

## 7️⃣ RESUMO POR ABA

### Aba Dados
- **Total de campos editáveis**: ~20 campos
- **Campos obrigatórios**: Nome, CPF, Renach, Data Nascimento, CFC
- **Seções**: 5 (Informações Pessoais, CFC, Tipo de Serviço, Endereço, Observações)
- **Funcionalidade principal**: Criação e edição completa do aluno

### Aba Matrícula
- **Total de campos exibidos**: 4 campos (Categoria, Tipo Serviço, Status, Data Início)
- **Campos editáveis**: Apenas via botão "Editar" (abre modal específico)
- **Funcionalidade principal**: Visualização de matrículas ativas do aluno

### Aba Histórico
- **Total de componentes**: 4 cards de resumo + timeline + 3 atalhos
- **Campos editáveis**: Nenhum (somente leitura)
- **Funcionalidade principal**: Visão cronológica da jornada do aluno (planejado)

### Modal Visualizar Aluno
- **Total de campos exibidos**: ~15 campos
- **Campos editáveis**: Nenhum (somente leitura)
- **Funcionalidade principal**: Visualização rápida de todos os dados do aluno sem possibilidade de edição

---

## 8️⃣ OBSERVAÇÕES IMPORTANTES

1. **Campo `operacoes`**: Armazenado como JSON no banco, permite múltiplas operações (tipo + categoria) por aluno. Substitui os campos legados `categoria_cnh` e `tipo_servico`.

2. **Campo `endereco`**: Pode ser armazenado como JSON ou string simples. O sistema trata ambos os casos no JavaScript.

3. **Campo `naturalidade`**: Composto automaticamente a partir de `naturalidade_estado` + `naturalidade_municipio` no formato "Município, Estado".

4. **Aba Histórico**: Ainda em fase de planejamento. A estrutura HTML existe, mas a função `carregarHistorico()` está comentada, aguardando endpoint unificado de timeline.

5. **Aba Matrícula**: Atualmente exibe dados da tabela `matriculas`. Pode ser expandida para incluir informações de `turma_matriculas` e relacionamento com turmas teóricas.

6. **Modal Visualizar**: Todos os campos são somente leitura. O botão "Editar Aluno" abre o modal de edição (`#modalAluno`) em modo edição.

---

**Data do Mapeamento**: 2025-01-14  
**Arquivo Analisado**: `admin/pages/alunos.php`  
**Versão do Sistema**: Baseado em estrutura atual do código

