# 🔍 Diagnóstico Completo: Jornada do Aluno no Sistema CFC

**Data da Análise:** 2025-01-27  
**Objetivo:** Mapear a jornada completa do aluno (cadastro → conclusão) e identificar gaps para implementação de provas teórica/prática

---

## 📊 1. Tabela: Jornada do Aluno x Sistema Atual

| Etapa da Jornada | Tabela/API/Tela atual | Como está hoje | Gap / O que falta |
|------------------|----------------------|----------------|-------------------|
| **Cadastro do aluno** | `alunos` (campo `criado_em`) | ✅ Implementado - Evento na timeline via `historico_aluno.php` | Nenhum |
| **Matrícula / serviço** | `matriculas` (campos: `data_inicio`, `data_fim`, `status`, `categoria_cnh`, `tipo_servico`) | ✅ Implementado - Eventos `matricula_criada` e `matricula_concluida` na timeline | Nenhum |
| **Exame médico** | `exames` (tipo: `'medico'`, campos: `data_agendada`, `data_resultado`, `status`, `resultado`) | ✅ Implementado - Tabela e API funcionais (`admin/api/exames.php`), página de gestão (`admin/pages/exames.php`) | ❌ **Não está na timeline** - Precisa adicionar eventos de exame médico |
| **Exame psicotécnico** | `exames` (tipo: `'psicotecnico'`, mesmos campos) | ✅ Implementado - Mesma estrutura do médico | ❌ **Não está na timeline** - Precisa adicionar eventos de exame psicotécnico |
| **Aulas teóricas** | `turma_matriculas` + `turma_aulas_agendadas` + `turma_presencas` | ✅ Implementado - Sistema completo de turmas teóricas com presenças, API `progresso_teorico.php` | ❌ **Não está na timeline** - Precisa adicionar eventos de matrícula em turma teórica e conclusão |
| **Prova teórica** | ❌ **NÃO EXISTE** | ⚠️ Referência no código (`AgendamentoGuards.php` busca `resultado_prova_teorica` e `data_prova_teorica` na tabela `alunos`), mas **não existe na estrutura do banco** | ❌ **GAP CRÍTICO** - Não há estrutura para provas teóricas. Opções: (A) Estender `exames` ou (B) Criar tabela específica |
| **Aulas práticas** | `aulas` (campo `tipo_aula = 'pratica'`, campos: `data_aula`, `status`) | ✅ Implementado - Sistema de agendamento, API `progresso_pratico.php` | ❌ **Não está na timeline** - Precisa adicionar eventos de aulas práticas (primeira aula, última aula, conclusão) |
| **Prova prática** | ❌ **NÃO EXISTE** | ⚠️ Não há referência no código atual | ❌ **GAP CRÍTICO** - Não há estrutura para provas práticas. Mesma decisão da prova teórica |
| **Conclusão do processo** | `matriculas.status` (valores: `'concluida'`, `'cancelada'`, `'trancada'`) | ✅ Parcial - Status existe, mas não há evento específico de conclusão com motivo/resultado | ⚠️ **Parcial** - Falta evento detalhado de conclusão (aprovado/reprovado/evasão) |

---

## 🔬 2. Análise da Tabela `exames`

### 2.1. Estrutura Atual

**Tabela:** `exames` (definida em `install.php:145-166`)

**Campos principais:**
- `id` (INT AUTO_INCREMENT)
- `aluno_id` (INT, FK para `alunos.id`)
- `tipo` (ENUM): **`'medico'`, `'psicotecnico'`** ← **Apenas 2 tipos**
- `status` (ENUM): `'agendado'`, `'concluido'`, `'cancelado'`
- `resultado` (ENUM): `'apto'`, `'inapto'`, `'inapto_temporario'`, `'pendente'`
- `clinica_nome` (VARCHAR 200)
- `protocolo` (VARCHAR 100)
- `data_agendada` (DATE) ← **Útil para agendamento**
- `data_resultado` (DATE) ← **Útil para resultado**
- `observacoes` (TEXT)
- `anexos` (TEXT)
- `criado_por`, `atualizado_por` (INT, FK para `usuarios.id`)
- `criado_em`, `atualizado_em` (TIMESTAMP)

**API existente:** `admin/api/exames.php` (GET, POST, PUT, DELETE)

**Validações atuais:**
- Linha 254: `if (!in_array($data['tipo'], ['medico', 'psicotecnico']))` ← **Bloqueia outros tipos**

### 2.2. Campos Úteis para Provas

✅ **Já existem e são adequados:**
- `data_agendada` → Data da prova
- `data_resultado` → Data do resultado
- `resultado` → Aprovado/Reprovado (precisa ajustar valores do ENUM)
- `protocolo` → Protocolo DETRAN
- `observacoes` → Observações da prova

⚠️ **Precisariam de ajuste:**
- `tipo` ENUM → Adicionar `'teorico'` e `'pratico'`
- `resultado` ENUM → Adicionar `'aprovado'`, `'reprovado'` (além dos atuais `'apto'`, `'inapto'`)
- `clinica_nome` → Para provas, poderia ser `local_prova` ou `local_exame`

---

## 💡 3. Proposta: Opção (A) vs (B)

### Opção (A): Reaproveitar `exames` e estender `tipo`

**Prós:**
- ✅ Reaproveita estrutura existente (campos, API, validações)
- ✅ Menos mudanças no código (apenas ajustar ENUMs)
- ✅ Consistência: todos os exames/provas em um lugar
- ✅ API `exames.php` já funciona bem
- ✅ Página de gestão `exames.php` já existe

**Contras:**
- ⚠️ Campo `clinica_nome` não faz sentido para provas (mas pode ser renomeado ou reutilizado como `local_prova`)
- ⚠️ ENUM `resultado` atual (`'apto'`, `'inapto'`) não é ideal para provas (precisa adicionar `'aprovado'`, `'reprovado'`)
- ⚠️ Mistura conceitos diferentes: exames médicos/psicotécnicos (pré-requisitos) vs provas teóricas/práticas (avaliações do curso)

**Mudanças necessárias:**
1. ALTER TABLE `exames` MODIFY `tipo` ENUM('medico', 'psicotecnico', 'teorico', 'pratico')
2. ALTER TABLE `exames` MODIFY `resultado` ENUM('apto', 'inapto', 'inapto_temporario', 'pendente', 'aprovado', 'reprovado')
3. Ajustar validação em `admin/api/exames.php:254`
4. Opcional: Renomear `clinica_nome` para `local_exame` (ou criar campo novo)

---

### Opção (B): Criar tabela específica `provas_direcao`

**Prós:**
- ✅ Separação clara de responsabilidades (exames pré-requisitos vs provas do curso)
- ✅ Estrutura específica para provas (pode ter campos próprios como `instrutor_avaliador`, `veiculo_utilizado`, etc.)
- ✅ Não mexe na estrutura existente de `exames`

**Contras:**
- ❌ Duplicação de código (precisa criar nova API, nova página de gestão)
- ❌ Mais complexidade (duas tabelas para gerenciar)
- ❌ Mais trabalho de implementação

**Estrutura proposta:**
```sql
CREATE TABLE provas_direcao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    matricula_id INT, -- FK para matriculas.id
    tipo ENUM('teorico', 'pratico') NOT NULL,
    status ENUM('agendada', 'realizada', 'cancelada') DEFAULT 'agendada',
    resultado ENUM('aprovado', 'reprovado', 'pendente') DEFAULT 'pendente',
    data_agendada DATE NOT NULL,
    data_realizada DATE,
    protocolo_detran VARCHAR(100),
    local_prova VARCHAR(200),
    instrutor_avaliador_id INT, -- Para prova prática
    veiculo_id INT, -- Para prova prática
    observacoes TEXT,
    criado_por INT,
    atualizado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    FOREIGN KEY (instrutor_avaliador_id) REFERENCES instrutores(id),
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id)
)
```

---

## 🎯 4. Recomendação

**Recomendo a Opção (A) - Reaproveitar `exames`** pelos seguintes motivos:

1. **Menos trabalho:** Aproveita toda a infraestrutura existente
2. **Consistência:** Todos os exames/provas em um lugar facilita relatórios e timeline
3. **Rapidez:** Implementação mais rápida (apenas ajustar ENUMs e validações)
4. **Flexibilidade:** O campo `observacoes` e `protocolo` já cobrem necessidades específicas

**Ajustes necessários:**
- Estender ENUM `tipo` para incluir `'teorico'` e `'pratico'`
- Estender ENUM `resultado` para incluir `'aprovado'` e `'reprovado'`
- Ajustar validações na API
- Opcional: Adicionar campo `local_prova` ou reutilizar `clinica_nome`

---

## 📋 5. Jornada Mínima que o Sistema Deve Refletir

### 5.1. Eventos Mínimos para Timeline

| Evento | Tipo | Quando | Onde |
|--------|------|--------|------|
| **Cadastro do aluno** | `aluno_cadastrado` | `alunos.criado_em` | ✅ Já implementado |
| **Matrícula criada** | `matricula_criada` | `matriculas.data_inicio` | ✅ Já implementado |
| **Matrícula concluída** | `matricula_concluida` | `matriculas.data_fim` | ✅ Já implementado |
| **Exame médico agendado** | `exame_medico_agendado` | `exames.data_agendada` (tipo='medico', status='agendado') | ❌ **Falta implementar** |
| **Exame médico realizado** | `exame_medico_realizado` | `exames.data_resultado` (tipo='medico', status='concluido') | ❌ **Falta implementar** |
| **Exame psicotécnico agendado** | `exame_psicotecnico_agendado` | `exames.data_agendada` (tipo='psicotecnico', status='agendado') | ❌ **Falta implementar** |
| **Exame psicotécnico realizado** | `exame_psicotecnico_realizado` | `exames.data_resultado` (tipo='psicotecnico', status='concluido') | ❌ **Falta implementar** |
| **Matrícula em turma teórica** | `turma_teorica_matriculado` | `turma_matriculas.data_matricula` | ❌ **Falta implementar** |
| **Turma teórica concluída** | `turma_teorica_concluida` | `turma_matriculas.atualizado_em` (status='concluido') | ❌ **Falta implementar** |
| **Prova teórica agendada** | `prova_teorica_agendada` | `exames.data_agendada` (tipo='teorico', status='agendado') | ❌ **Falta implementar** (após estender ENUM) |
| **Prova teórica realizada** | `prova_teorica_realizada` | `exames.data_resultado` (tipo='teorico', status='concluido') | ❌ **Falta implementar** (após estender ENUM) |
| **Prova teórica aprovada** | `prova_teorica_aprovada` | `exames.data_resultado` (tipo='teorico', resultado='aprovado') | ❌ **Falta implementar** (após estender ENUM) |
| **Prova teórica reprovada** | `prova_teorica_reprovada` | `exames.data_resultado` (tipo='teorico', resultado='reprovado') | ❌ **Falta implementar** (após estender ENUM) |
| **Primeira aula prática** | `aula_pratica_iniciada` | `aulas.data_aula` (tipo='pratica', primeira do aluno) | ❌ **Falta implementar** |
| **Aulas práticas concluídas** | `aulas_praticas_concluidas` | Última `aulas.data_aula` (tipo='pratica', status='concluida', todas concluídas) | ❌ **Falta implementar** |
| **Prova prática agendada** | `prova_pratica_agendada` | `exames.data_agendada` (tipo='pratico', status='agendado') | ❌ **Falta implementar** (após estender ENUM) |
| **Prova prática realizada** | `prova_pratica_realizada` | `exames.data_resultado` (tipo='pratico', status='concluido') | ❌ **Falta implementar** (após estender ENUM) |
| **Prova prática aprovada** | `prova_pratica_aprovada` | `exames.data_resultado` (tipo='pratico', resultado='aprovado') | ❌ **Falta implementar** (após estender ENUM) |
| **Prova prática reprovada** | `prova_pratica_reprovada` | `exames.data_resultado` (tipo='pratico', resultado='reprovado') | ❌ **Falta implementar** (após estender ENUM) |
| **Fatura criada** | `fatura_criada` | `faturas.criado_em` ou `financeiro_faturas.criado_em` | ✅ Já implementado |
| **Fatura paga** | `fatura_paga` | `pagamentos.data_pagamento` | ✅ Já implementado |
| **Fatura vencida** | `fatura_vencida` | `faturas.vencimento` (status='vencida') | ✅ Já implementado |

### 5.2. Integração com Estruturas Existentes

#### Cards da Aba Histórico e Visualizar Aluno:
- ✅ **Situação do Processo** → Já usa `matriculas.status`
- ✅ **Progresso Teórico** → Já usa `turma_matriculas.status` e `frequencia_percentual`
- ✅ **Progresso Prático** → Já usa `aulas` (tipo='pratica')
- ✅ **Situação Financeira** → Já usa `faturas` ou `financeiro_faturas`

**Futuro (após implementar provas):**
- ⚠️ Adicionar card "Status das Provas" mostrando: Prova teórica (aprovada/pendente), Prova prática (aprovada/pendente)

#### Linha do Tempo (API `historico_aluno.php`):
- ✅ Já retorna eventos de cadastro, matrícula, faturas
- ❌ **Falta:** Exames médico/psicotécnico, turmas teóricas, aulas práticas, provas

#### Seções da Aba Matrícula:
- ✅ **Processo DETRAN** → Campos `renach`, `processo_numero`, `processo_numero_detran`, `processo_situacao`
- ✅ **Vinculação Teórica** → Preenchido via `progresso_teorico.php`
- ✅ **Vinculação Prática** → Preenchido via `progresso_pratico.php`

**Futuro (após implementar provas):**
- ⚠️ Adicionar seção "Provas" mostrando: Data prova teórica, Resultado, Data prova prática, Resultado

---

## ✅ 6. Implementação Leve (O que foi feito)

### 6.1. Eventos de Exames Médico/Psicotécnico na Timeline

**Arquivo:** `admin/api/historico_aluno.php`

**Implementado:**
- ✅ Busca exames da tabela `exames` (tipo='medico' e tipo='psicotecnico')
- ✅ Cria eventos:
  - `exame_medico_agendado` (data: `data_agendada`, status='agendado')
  - `exame_medico_realizado` (data: `data_resultado`, status='concluido')
  - `exame_psicotecnico_agendado` (data: `data_agendada`, status='agendado')
  - `exame_psicotecnico_realizado` (data: `data_resultado`, status='concluido')
- ✅ Descrições amigáveis: "Exame médico agendado", "Exame médico realizado - Resultado: apto/inapto"
- ✅ Meta inclui: `exame_id`, `tipo`, `status`, `resultado`, `protocolo`

### 6.2. Renderização no Frontend

**Arquivo:** `admin/pages/alunos.php` (função `carregarHistoricoAluno`)

**Implementado:**
- ✅ Tags específicas para exames:
  - Exames médico/psicotécnico → Badge "Exame" (cor: `bg-info text-white`)
- ✅ Formatação de data brasileira (dd/mm/aaaa HH:MM)
- ✅ Títulos e descrições amigáveis

### 6.3. TODOs Documentados

**Arquivo:** `admin/api/historico_aluno.php`
- ✅ TODO: Adicionar eventos de aulas teóricas/práticas na timeline
- ✅ TODO: Adicionar eventos de exames (provas teóricas/práticas) na timeline
- ✅ TODO: Adicionar eventos de mudanças de status
- ✅ TODO: Adicionar eventos de atualizações de dados pessoais

**Arquivo:** `admin/pages/alunos.php`
- ✅ TODO: Adicionar eventos de aulas teóricas/práticas na timeline
- ✅ TODO: Adicionar eventos de exames (provas teóricas/práticas) na timeline

---

## 📝 7. Resumo do que foi Implementado

### ✅ Implementado Agora:

1. **Eventos de Exames Médico/Psicotécnico na Timeline:**
   - `exame_medico_agendado` / `exame_medico_realizado`
   - `exame_psicotecnico_agendado` / `exame_psicotecnico_realizado`
   - Renderização com badge "Exame" (azul)

2. **TODOs Documentados:**
   - Onde adicionar eventos de provas teóricas/práticas (após estender ENUM)
   - Onde adicionar eventos de aulas teóricas/práticas
   - Campos necessários para provas

### ❌ Não Implementado (Aguardando Decisão):

1. **Provas Teóricas/Práticas:**
   - Aguardando decisão: Opção (A) estender `exames` ou Opção (B) criar `provas_direcao`
   - Após decisão, será necessário:
     - ALTER TABLE para estender ENUMs (se Opção A)
     - Criar tabela nova (se Opção B)
     - Ajustar API `exames.php` ou criar `provas.php`
     - Adicionar eventos na timeline

2. **Eventos de Aulas Teóricas/Práticas:**
   - Aguardando implementação na timeline (já temos dados via APIs)

---

## 🎯 8. Próximos Passos Recomendados

1. **Decidir sobre provas:** Opção (A) ou (B) - Recomendo (A)
2. **Estender ENUMs** (se Opção A) ou criar tabela (se Opção B)
3. **Adicionar eventos de aulas** na timeline (dados já existem)
4. **Adicionar eventos de provas** na timeline (após estrutura estar pronta)
5. **Adicionar seção "Provas"** na aba Matrícula
6. **Adicionar card "Status das Provas"** na aba Histórico

---

---

## 📌 9. Atualização: Estrutura de Provas Implementada

**Data:** 2025-01-27

### 9.1. Decisão Final

✅ **Opção (A) escolhida:** Reaproveitar tabela `exames` para provas teóricas e práticas.

### 9.2. Alterações Realizadas

**Migration criada:** `admin/migrations/003-alter-exames-add-provas.sql`
- Estende `tipo` ENUM para incluir `'teorico'` e `'pratico'`
- Estende `resultado` ENUM para incluir `'aprovado'` e `'reprovado'`

**API atualizada:** `admin/api/exames.php`
- Validação de tipos atualizada para aceitar `'teorico'` e `'pratico'`
- Validação de resultados atualizada para aceitar `'aprovado'` e `'reprovado'`
- TODO documentado para validação futura de combinações tipo+resultado

**Install.php atualizado:** Definição da tabela `exames` já nasce com os novos valores.

### 9.3. Próximas Etapas (UI + Timeline)

⚠️ **Ainda não implementado:**
- UI para agendar/gerenciar provas teóricas/práticas
- Eventos de provas na timeline (`historico_aluno.php`)
- Seção "Provas" na aba Matrícula do modal de aluno
- Card "Status das Provas" na aba Histórico

**Essas funcionalidades serão implementadas em etapas posteriores.**

---

**Fim do Diagnóstico**

