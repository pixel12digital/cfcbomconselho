# AUDITORIA COMPLETA - Campos do Módulo de Alunos
## CFC Bom Conselho

**Data da Auditoria:** 20/11/2025  
**Objetivo:** Comparar campos existentes nas abas Dados e Matrícula com o que é exibido no modal de Detalhes do Aluno

---

## 1. MAPEAMENTO - ABA DADOS

### Tabela: Campos da Aba Dados

| Campo (name/id) | Rótulo na Tela | Coluna no Banco | Seção no Formulário |
|----------------|----------------|-----------------|---------------------|
| `foto` | Foto (Opcional) | `alunos.foto` | Informações Pessoais |
| `nome` | Nome Completo * | `alunos.nome` | Informações Pessoais |
| `cpf` | CPF * | `alunos.cpf` | Informações Pessoais |
| `rg` | RG | `alunos.rg` | Informações Pessoais |
| `rg_orgao_emissor` | Órgão Emissor | `alunos.rg_orgao_emissor` | Informações Pessoais |
| `rg_uf` | UF do RG | `alunos.rg_uf` | Informações Pessoais |
| `rg_data_emissao` | Data Emissão RG | `alunos.rg_data_emissao` | Informações Pessoais |
| `data_nascimento` | Data Nasc. * | `alunos.data_nascimento` | Informações Pessoais |
| `atividade_remunerada` | Atividade Remunerada | `alunos.atividade_remunerada` | Informações Pessoais |
| `estado_civil` | Estado Civil | `alunos.estado_civil` | Informações Pessoais |
| `profissao` | Profissão | `alunos.profissao` | Informações Pessoais |
| `escolaridade` | Escolaridade | `alunos.escolaridade` | Informações Pessoais |
| `naturalidade_estado` | Estado (Naturalidade) | `alunos.naturalidade` (parcial) | Informações Pessoais |
| `naturalidade_municipio` | Município (Naturalidade) | `alunos.naturalidade` (parcial) | Informações Pessoais |
| `naturalidade` | (hidden) | `alunos.naturalidade` | Informações Pessoais |
| `nacionalidade` | Nacionalidade | `alunos.nacionalidade` | Informações Pessoais |
| `telefone` | Telefone | `alunos.telefone` | Contatos |
| `telefone_secundario` | Telefone Secundário | `alunos.telefone_secundario` | Contatos |
| `email` | E-mail | `alunos.email` | Contatos |
| `contato_emergencia_nome` | Contato de Emergência (Nome) | `alunos.contato_emergencia_nome` | Contatos |
| `contato_emergencia_telefone` | Telefone de Emergência | `alunos.contato_emergencia_telefone` | Contatos |
| `cep` | CEP | `alunos.cep` | Endereço |
| `logradouro` | Logradouro | `alunos.endereco` | Endereço |
| `numero` | Número | `alunos.numero` | Endereço |
| `complemento` | Complemento | `alunos.complemento` | Endereço |
| `bairro` | Bairro | `alunos.bairro` | Endereço |
| `cidade` | Cidade | `alunos.cidade` | Endereço |
| `uf` | UF | `alunos.estado` | Endereço |
| `cfc_id` | CFC * | `alunos.cfc_id` | Configurações Gerais |
| `status` | Status do Aluno | `alunos.status` | Configurações Gerais |
| `lgpd_consentimento` | Autorizo o CFC a utilizar meus dados... | `alunos.lgpd_consentimento` | LGPD |
| `lgpd_consentimento_em` | Data/Hora do Consentimento | `alunos.lgpd_consentimento_em` | LGPD |
| `observacoes` | Observações | `alunos.observacoes` | Observações Gerais |

**Total de campos na aba Dados: 33**

---

## 2. MAPEAMENTO - ABA MATRÍCULA

### Tabela: Campos da Aba Matrícula

| Campo (name/id) | Rótulo na Tela | Coluna no Banco | Seção no Formulário |
|----------------|----------------|-----------------|---------------------|
| `operacoes[]` | Tipo de Serviço (dinâmico) | `alunos.operacoes` (JSON) | Curso e Serviços |
| `data_matricula` | Data da Matrícula | `alunos.data_matricula` | Curso e Serviços |
| `previsao_conclusao` | Previsão de Conclusão | (não mapeado no banco) | Curso e Serviços |
| `data_conclusao` | Data de Conclusão | `alunos.data_conclusao` | Curso e Serviços |
| `status_matricula` | Status da Matrícula | (não mapeado diretamente) | Curso e Serviços |
| `renach` | RENACH * | `alunos.renach` | Processo DETRAN |
| `processo_numero` | Número do Processo | `alunos.numero_processo` | Processo DETRAN |
| `processo_numero_detran` | Número DETRAN / Protocolo | `alunos.detran_numero` | Processo DETRAN |
| `processo_situacao` | Situação do Processo | (não mapeado diretamente) | Processo DETRAN |
| `turma_teorica_atual_id` | Turma Teórica Atual | (relacionamento com turma_matriculas) | Vinculação Teórica |
| `situacao_teorica` | Situação das Aulas Teóricas | (calculado/readonly) | Vinculação Teórica |
| `aulas_praticas_contratadas` | Aulas Práticas Contratadas | (não mapeado diretamente) | Vinculação Prática |
| `aulas_praticas_extras` | Aulas Extras | (não mapeado diretamente) | Vinculação Prática |
| `instrutor_principal_id` | Instrutor Principal | (não mapeado diretamente) | Vinculação Prática |
| `situacao_pratica` | Situação das Aulas Práticas | (calculado/readonly) | Vinculação Prática |
| `prova_teorica_resumo` | Prova Teórica | (readonly - calculado) | Provas |
| `prova_pratica_resumo` | Prova Prática | (readonly - calculado) | Provas |
| `valor_curso` | Valor do Curso | `alunos.valor_curso` | Financeiro da Matrícula |
| `forma_pagamento` | Forma de Pagamento | `alunos.forma_pagamento` | Financeiro da Matrícula |
| `status_pagamento` | Status de Pagamento | `alunos.status_pagamento` | Financeiro da Matrícula |
| `resumo-financeiro-matricula` | Resumo Financeiro do Aluno | (readonly - calculado via API) | Resumo Financeiro |

**Total de campos na aba Matrícula: 21**

**Nota:** Alguns campos da aba Matrícula são calculados dinamicamente ou vêm de relacionamentos (turmas, aulas, provas, faturas), não sendo armazenados diretamente na tabela `alunos`.

---

## 3. MAPEAMENTO - MODAL DE DETALHES DO ALUNO

### Tabela: Campos Exibidos no Modal de Detalhes

| Campo Exibido | Rótulo na Tela | Origem (tabela.coluna / campo) | Bloco/Seção no Modal |
|---------------|----------------|--------------------------------|----------------------|
| Foto | (imagem) | `alunos.foto` | Cabeçalho |
| Nome | Nome | `alunos.nome` | Cabeçalho |
| CPF | CPF | `alunos.cpf` | Cabeçalho |
| Status | (badge) | `alunos.status` | Cabeçalho |
| Tipo de Serviço | (texto) | `alunos.operacoes` (calculado) | Cabeçalho |
| RG | RG | `alunos.rg` | Documento e Processo |
| Órgão Emissor | (concat com RG) | `alunos.rg_orgao_emissor` | Documento e Processo |
| UF do RG | (concat com RG) | `alunos.rg_uf` | Documento e Processo |
| Data de Emissão | Data de Emissão | `alunos.rg_data_emissao` | Documento e Processo |
| RENACH | RENACH | `alunos.renach` | Documento e Processo |
| Data de Nascimento | Data de Nascimento | `alunos.data_nascimento` | Dados Pessoais |
| Naturalidade | Naturalidade | `alunos.naturalidade` | Dados Pessoais |
| Nacionalidade | Nacionalidade | `alunos.nacionalidade` | Dados Pessoais |
| Estado Civil | Estado Civil | `alunos.estado_civil` | Dados Pessoais |
| Profissão | Profissão | `alunos.profissao` | Dados Pessoais |
| Escolaridade | Escolaridade | `alunos.escolaridade` | Dados Pessoais |
| Atividade Remunerada | Atividade Remunerada | `alunos.atividade_remunerada` | Dados Pessoais |
| Telefone | Telefone | `alunos.telefone` | Contatos |
| Telefone Secundário | Telefone Secundário | `alunos.telefone_secundario` | Contatos |
| E-mail | E-mail | `alunos.email` | Contatos |
| Contato de Emergência | Contato de Emergência | `alunos.contato_emergencia_nome` + `alunos.contato_emergencia_telefone` | Contatos |
| Logradouro | (endereço completo) | `alunos.endereco` | Endereço |
| Número | (endereço completo) | `alunos.numero` | Endereço |
| Complemento | (endereço completo) | `alunos.complemento` | Endereço |
| Bairro | (endereço completo) | `alunos.bairro` | Endereço |
| Cidade | (endereço completo) | `alunos.cidade` | Endereço |
| UF | (endereço completo) | `alunos.estado` | Endereço |
| CEP | CEP | `alunos.cep` | Endereço |
| CFC | CFC | `alunos.cfc_id` → `cfcs.nome` | CFC |
| Observações | Observações do Aluno | `alunos.observacoes` | Observações do Aluno |
| Situação do Processo | Situação do Processo | (calculado - card) | Cards Resumo (direita) |
| Progresso Teórico | Progresso Teórico | (calculado - card) | Cards Resumo (direita) |
| Progresso Prático | Progresso Prático | (calculado - card) | Cards Resumo (direita) |
| Situação Financeira | Situação Financeira | (calculado - card) | Cards Resumo (direita) |
| Provas | Provas | (calculado - card) | Cards Resumo (direita) |
| Linha do Tempo | (eventos) | (calculado - histórico) | Linha do Tempo (direita) |
| Atalhos Rápidos | (links) | (navegação) | Atalhos Rápidos (direita) |

**Total de campos exibidos em Detalhes: 36 (incluindo cards calculados)**

---

## 4. TABELA CONSOLIDADA DE AUDITORIA

### Tabela: Comparação Completa - Campos vs. Detalhes

| Campo Backend (tabela.coluna) | Aba/Seção de Origem | Rótulo no Formulário | Exibido em Detalhes? | Onde Aparece em Detalhes |
|-------------------------------|---------------------|----------------------|----------------------|--------------------------|
| `alunos.foto` | Dados > Informações Pessoais | Foto (Opcional) | **SIM** | Cabeçalho (imagem) |
| `alunos.nome` | Dados > Informações Pessoais | Nome Completo * | **SIM** | Cabeçalho |
| `alunos.cpf` | Dados > Informações Pessoais | CPF * | **SIM** | Cabeçalho |
| `alunos.rg` | Dados > Informações Pessoais | RG | **SIM** | Bloco "Documento e Processo" |
| `alunos.rg_orgao_emissor` | Dados > Informações Pessoais | Órgão Emissor | **SIM** | Bloco "Documento e Processo" (concat com RG) |
| `alunos.rg_uf` | Dados > Informações Pessoais | UF do RG | **SIM** | Bloco "Documento e Processo" (concat com RG) |
| `alunos.rg_data_emissao` | Dados > Informações Pessoais | Data Emissão RG | **SIM** | Bloco "Documento e Processo" |
| `alunos.data_nascimento` | Dados > Informações Pessoais | Data Nasc. * | **SIM** | Bloco "Dados Pessoais" |
| `alunos.atividade_remunerada` | Dados > Informações Pessoais | Atividade Remunerada | **SIM** | Bloco "Dados Pessoais" |
| `alunos.estado_civil` | Dados > Informações Pessoais | Estado Civil | **SIM** | Bloco "Dados Pessoais" |
| `alunos.profissao` | Dados > Informações Pessoais | Profissão | **SIM** | Bloco "Dados Pessoais" |
| `alunos.escolaridade` | Dados > Informações Pessoais | Escolaridade | **SIM** | Bloco "Dados Pessoais" |
| `alunos.naturalidade` | Dados > Informações Pessoais | Naturalidade (Estado + Município) | **SIM** | Bloco "Dados Pessoais" |
| `alunos.nacionalidade` | Dados > Informações Pessoais | Nacionalidade | **SIM** | Bloco "Dados Pessoais" |
| `alunos.telefone` | Dados > Contatos | Telefone | **SIM** | Bloco "Contatos" |
| `alunos.telefone_secundario` | Dados > Contatos | Telefone Secundário | **SIM** | Bloco "Contatos" |
| `alunos.email` | Dados > Contatos | E-mail | **SIM** | Bloco "Contatos" |
| `alunos.contato_emergencia_nome` | Dados > Contatos | Contato de Emergência (Nome) | **SIM** | Bloco "Contatos" |
| `alunos.contato_emergencia_telefone` | Dados > Contatos | Telefone de Emergência | **SIM** | Bloco "Contatos" |
| `alunos.cep` | Dados > Endereço | CEP | **SIM** | Bloco "Endereço" |
| `alunos.endereco` | Dados > Endereço | Logradouro | **SIM** | Bloco "Endereço" |
| `alunos.numero` | Dados > Endereço | Número | **SIM** | Bloco "Endereço" |
| `alunos.complemento` | Dados > Endereço | Complemento | **SIM** | Bloco "Endereço" |
| `alunos.bairro` | Dados > Endereço | Bairro | **SIM** | Bloco "Endereço" |
| `alunos.cidade` | Dados > Endereço | Cidade | **SIM** | Bloco "Endereço" |
| `alunos.estado` | Dados > Endereço | UF | **SIM** | Bloco "Endereço" |
| `alunos.cfc_id` | Dados > Configurações Gerais | CFC * | **SIM** | Bloco "CFC" (nome do CFC) |
| `alunos.status` | Dados > Configurações Gerais | Status do Aluno | **SIM** | Cabeçalho (badge) |
| `alunos.lgpd_consentimento` | Dados > LGPD | Autorizo o CFC... | **NÃO** | - |
| `alunos.lgpd_consentimento_em` | Dados > LGPD | Data/Hora do Consentimento | **NÃO** | - |
| `alunos.observacoes` | Dados > Observações Gerais | Observações | **SIM** | Bloco "Observações do Aluno" |
| `alunos.renach` | Matrícula > Processo DETRAN | RENACH * | **SIM** | Bloco "Documento e Processo" |
| `alunos.numero_processo` | Matrícula > Processo DETRAN | Número do Processo | **NÃO** | - |
| `alunos.detran_numero` | Matrícula > Processo DETRAN | Número DETRAN / Protocolo | **NÃO** | - |
| `alunos.data_matricula` | Matrícula > Curso e Serviços | Data da Matrícula | **NÃO** | - |
| `alunos.data_conclusao` | Matrícula > Curso e Serviços | Data de Conclusão | **NÃO** | - |
| `alunos.valor_curso` | Matrícula > Financeiro da Matrícula | Valor do Curso | **NÃO** | - |
| `alunos.forma_pagamento` | Matrícula > Financeiro da Matrícula | Forma de Pagamento | **NÃO** | - |
| `alunos.status_pagamento` | Matrícula > Financeiro da Matrícula | Status de Pagamento | **NÃO** | - |
| `alunos.operacoes` | Matrícula > Curso e Serviços | Tipo de Serviço (dinâmico) | **SIM** | Cabeçalho (tipo de serviço calculado) |
| `alunos.categoria_cnh` | (não visível no form, mas existe) | - | **SIM** | Cabeçalho (via operacoes) |
| `alunos.tipo_servico` | (não visível no form, mas existe) | - | **SIM** | Cabeçalho (via operacoes) |
| Campos calculados (turma teórica) | Matrícula > Vinculação Teórica | Turma Teórica Atual | **SIM** | Card "Progresso Teórico" |
| Campos calculados (aulas práticas) | Matrícula > Vinculação Prática | Aulas Práticas | **SIM** | Card "Progresso Prático" |
| Campos calculados (provas) | Matrícula > Provas | Provas | **SIM** | Card "Provas" |
| Campos calculados (financeiro) | Matrícula > Resumo Financeiro | Resumo Financeiro | **SIM** | Card "Situação Financeira" |
| Campos calculados (processo) | Matrícula > Processo DETRAN | Situação do Processo | **SIM** | Card "Situação do Processo" |

---

## 5. RESUMO EXECUTIVO

### Estatísticas Gerais

**Aba Dados:**
- **Total de campos:** 33
- **Exibidos em Detalhes:** 30
- **NÃO exibidos em Detalhes:** 3

**Aba Matrícula:**
- **Total de campos:** 21 (incluindo calculados)
- **Exibidos em Detalhes:** 6 (diretos) + 5 (calculados via cards) = 11
- **NÃO exibidos em Detalhes:** 10

### Campos que NÃO aparecem em Detalhes

#### Aba Dados (3 campos faltantes):

1. **`alunos.lgpd_consentimento`** - Checkbox de consentimento LGPD
   - **Relevância:** ALTA - Importante para compliance e auditoria
   - **Sugestão:** Adicionar no bloco "Dados Pessoais" ou criar seção "LGPD"

2. **`alunos.lgpd_consentimento_em`** - Data/Hora do consentimento LGPD
   - **Relevância:** ALTA - Importante para compliance e auditoria
   - **Sugestão:** Adicionar junto com `lgpd_consentimento`

#### Aba Matrícula (10 campos faltantes):

3. **`alunos.numero_processo`** - Número do Processo
   - **Relevância:** ALTA - Informação importante do processo de habilitação
   - **Sugestão:** Adicionar no bloco "Documento e Processo"

4. **`alunos.detran_numero`** - Número DETRAN / Protocolo
   - **Relevância:** ALTA - Informação importante do processo de habilitação
   - **Sugestão:** Adicionar no bloco "Documento e Processo"

5. **`alunos.data_matricula`** - Data da Matrícula
   - **Relevância:** MÉDIA - Útil para histórico e acompanhamento
   - **Sugestão:** Adicionar no bloco "Documento e Processo" ou criar seção "Histórico da Matrícula"

6. **`alunos.data_conclusao`** - Data de Conclusão
   - **Relevância:** MÉDIA - Útil para histórico e acompanhamento
   - **Sugestão:** Adicionar junto com `data_matricula`

7. **`alunos.valor_curso`** - Valor do Curso
   - **Relevância:** MÉDIA - Informação financeira relevante
   - **Sugestão:** Adicionar no card "Situação Financeira" ou criar seção "Financeiro"

8. **`alunos.forma_pagamento`** - Forma de Pagamento
   - **Relevância:** MÉDIA - Informação financeira relevante
   - **Sugestão:** Adicionar junto com informações financeiras

9. **`alunos.status_pagamento`** - Status de Pagamento
   - **Relevância:** ALTA - Informação financeira crítica
   - **Sugestão:** Adicionar no card "Situação Financeira" ou expandir seção financeira

10. **`status_matricula`** - Status da Matrícula (campo do formulário, não mapeado diretamente)
    - **Relevância:** ALTA - Status importante do processo
    - **Sugestão:** Adicionar no bloco "Documento e Processo" ou card "Situação do Processo"

11. **`processo_situacao`** - Situação do Processo (campo do formulário, não mapeado diretamente)
    - **Relevância:** ALTA - Status importante do processo
    - **Sugestão:** Adicionar no bloco "Documento e Processo" ou card "Situação do Processo"

12. **`previsao_conclusao`** - Previsão de Conclusão (campo do formulário, não mapeado no banco)
    - **Relevância:** BAIXA - Campo não persistido no banco
    - **Sugestão:** Verificar se deve ser persistido ou removido do formulário

### Campos Calculados que JÁ aparecem em Detalhes

Os seguintes campos são calculados dinamicamente e já aparecem nos cards de resumo:
- Progresso Teórico (via turma_matriculas e presenças)
- Progresso Prático (via aulas práticas)
- Situação Financeira (via faturas)
- Provas (via exames)
- Situação do Processo (calculado)

---

## 6. RECOMENDAÇÕES TÉCNICAS

### Prioridade ALTA - Incluir Imediatamente

1. **LGPD (2 campos):**
   - `lgpd_consentimento` e `lgpd_consentimento_em`
   - **Localização sugerida:** Nova seção "LGPD" ou dentro de "Dados Pessoais"
   - **Formato:** Checkbox + Data/Hora formatada

2. **Processo DETRAN (2 campos):**
   - `numero_processo` e `detran_numero`
   - **Localização sugerida:** Bloco "Documento e Processo" (expandir)
   - **Formato:** Texto simples

3. **Status Financeiro (1 campo):**
   - `status_pagamento`
   - **Localização sugerida:** Card "Situação Financeira" (expandir) ou nova seção "Financeiro"
   - **Formato:** Badge colorido

### Prioridade MÉDIA - Incluir em Segunda Fase

4. **Datas da Matrícula (2 campos):**
   - `data_matricula` e `data_conclusao`
   - **Localização sugerida:** Nova seção "Histórico da Matrícula" ou dentro de "Documento e Processo"
   - **Formato:** Data formatada (dd/mm/aaaa)

5. **Financeiro (2 campos):**
   - `valor_curso` e `forma_pagamento`
   - **Localização sugerida:** Expandir card "Situação Financeira" ou criar seção "Financeiro"
   - **Formato:** Valor monetário + texto

6. **Status da Matrícula (2 campos):**
   - `status_matricula` e `processo_situacao`
   - **Localização sugerida:** Bloco "Documento e Processo" ou card "Situação do Processo"
   - **Formato:** Badge ou texto

### Prioridade BAIXA - Revisar Necessidade

7. **Previsão de Conclusão:**
   - Campo não persistido no banco
   - **Ação:** Decidir se deve ser persistido ou removido do formulário

---

## 7. ESTRUTURA SUGERIDA PARA MODAL DE DETALHES

### Layout Proposto (Coluna Esquerda - Expandido)

```
┌─────────────────────────────────────┐
│ Cabeçalho (Foto, Nome, CPF, Status) │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Documento e Processo                 │
│ - RG + Órgão + UF + Data Emissão    │
│ - RENACH                             │
│ - Número do Processo (NOVO)          │
│ - Número DETRAN (NOVO)               │
│ - Situação do Processo (NOVO)       │
│ - Status da Matrícula (NOVO)         │
│ - Data Matrícula (NOVO)              │
│ - Data Conclusão (NOVO)              │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Dados Pessoais                       │
│ - Data Nascimento                    │
│ - Naturalidade                       │
│ - Nacionalidade                      │
│ - Estado Civil                       │
│ - Profissão                          │
│ - Escolaridade                       │
│ - Atividade Remunerada               │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ LGPD (NOVO)                          │
│ - Consentimento (Sim/Não)            │
│ - Data/Hora do Consentimento         │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Contatos                             │
│ - Telefone                           │
│ - Telefone Secundário                │
│ - E-mail                             │
│ - Contato de Emergência              │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Endereço                             │
│ - (endereço completo)                │
│ - CEP                                │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ CFC                                  │
│ - Nome do CFC                        │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Financeiro (NOVO - Expandido)        │
│ - Valor do Curso                     │
│ - Forma de Pagamento                 │
│ - Status de Pagamento                │
│ - [Link para Financeiro Completo]    │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Observações do Aluno                 │
│ - (texto)                            │
└─────────────────────────────────────┘
```

### Layout Proposto (Coluna Direita - Mantido)

```
┌─────────────────────────────────────┐
│ Cards de Resumo (mantidos)           │
│ - Situação do Processo               │
│ - Progresso Teórico                  │
│ - Progresso Prático                  │
│ - Situação Financeira (expandido)    │
│ - Provas                             │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Linha do Tempo (mantida)             │
└─────────────────────────────────────┘
┌─────────────────────────────────────┐
│ Atalhos Rápidos (mantidos)           │
└─────────────────────────────────────┘
```

---

## 8. CONCLUSÃO

A auditoria identificou **13 campos** que não estão sendo exibidos no modal de Detalhes do Aluno:

- **3 campos da aba Dados** (todos relacionados a LGPD)
- **10 campos da aba Matrícula** (processo, datas, financeiro, status)

**Recomendação:** Implementar a inclusão dos campos de **Prioridade ALTA** imediatamente, seguidos pelos de **Prioridade MÉDIA** em uma segunda fase.

**Impacto:** A inclusão desses campos melhorará significativamente a visibilidade das informações do aluno, especialmente para:
- Compliance LGPD
- Acompanhamento do processo de habilitação
- Gestão financeira
- Histórico e auditoria

---

**Documento gerado em:** 20/11/2025  
**Versão:** 1.0  
**Autor:** Sistema de Auditoria Automatizada

