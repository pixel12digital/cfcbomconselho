# 🔍 AUDITORIA COMPLETA - Painel do Instrutor: Exibição de Dados do Aluno

**Data da Auditoria:** 2025-01-27  
**Sistema:** CFC Bom Conselho  
**Objetivo:** Mapear todas as telas, rotas, endpoints e componentes relacionados à exibição de dados do aluno no painel do instrutor, evitando duplicidades e propondo arquitetura consistente.

---

## 📋 ÍNDICE

1. [Mapeamento do que já existe (UI e rotas)](#1-mapeamento-do-que-já-existe-ui-e-rotas)
2. [Mapeamento backend: fonte dos dados e segurança](#2-mapeamento-backend-fonte-dos-dados-e-segurança)
3. [Evitar duplicidade: recomendação de reaproveitamento](#3-evitar-duplicidade-recomendação-de-reaproveitamento)
4. [Melhor forma de implementar (arquitetura + UX)](#4-melhor-forma-de-implementar-arquitetura--ux)
5. [Entregáveis](#5-entregáveis)

---

## 1. MAPEAMENTO DO QUE JÁ EXISTE (UI E ROTAS)

### 1.1. Dashboard do Instrutor (`instrutor/dashboard.php`)

**Rota:** `/instrutor/dashboard.php`

**Onde o aluno aparece:**
- **Card "Próxima Aula"** (linhas 487-610)
  - Exibe: `aluno_nome` (para aulas práticas)
  - Dados carregados: Query direta na linha 164-176
  - **NÃO exibe:** CPF, telefone, foto, categoria CNH
  - **NÃO tem:** Botão "Ver Aluno" ou modal de detalhes

- **Tabela "Aulas de Hoje"** (linhas 718-895)
  - Exibe: `aluno_nome` (coluna "Disciplina / Turma")
  - Dados carregados: Mesma query do card próxima aula
  - **NÃO exibe:** CPF, telefone, foto, categoria CNH
  - **NÃO tem:** Link clicável no nome ou botão de detalhes

- **Lista "Próximas Aulas (7 dias)"** (linhas 1022-1089)
  - Exibe: `aluno_nome` (apenas nome)
  - Dados carregados: Query linha 267-281
  - **NÃO exibe:** CPF, telefone, foto, categoria CNH
  - **NÃO tem:** Link ou botão de detalhes

**Como o aluno é carregado:**
```php
// Linha 164-176: Query direta no arquivo
SELECT a.*, 
       al.nome as aluno_nome, al.telefone as aluno_telefone,
       v.modelo as veiculo_modelo, v.placa as veiculo_placa,
       'pratica' as tipo_aula
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.instrutor_id = ? 
  AND a.data_aula = ?
  AND a.status != 'cancelada'
```

**Dados disponíveis mas não exibidos:**
- `aluno_telefone` (está na query mas não é usado na UI)
- CPF, foto, categoria CNH (não estão na query)

**Modal/Página de detalhes:** ❌ Não existe

**Botão "Ver Aluno":** ❌ Não existe

---

### 1.2. Lista de Aulas (`instrutor/aulas.php`)

**Rota:** `/instrutor/aulas.php`

**Onde o aluno aparece:**
- **Lista de Aulas Práticas** (linhas 304-414)
  - Exibe: `aluno_nome` (linha 341)
  - Dados carregados: Query direta linha 88-110
  - **NÃO exibe:** CPF, telefone, foto, categoria CNH
  - **NÃO tem:** Link clicável ou botão de detalhes

**Como o aluno é carregado:**
```php
// Linha 88-110: Query direta
SELECT a.*, 
       al.nome as aluno_nome, al.telefone as aluno_telefone,
       v.modelo as veiculo_modelo, v.placa as veiculo_placa,
       'pratica' as tipo_aula
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.instrutor_id = ?
  AND a.data_aula >= ?
  AND a.data_aula <= ?
```

**Dados disponíveis mas não exibidos:**
- `aluno_telefone` (está na query mas não é usado)

**Modal/Página de detalhes:** ❌ Não existe

**Botão "Ver Aluno":** ❌ Não existe

---

### 1.3. Interface de Chamada (`admin/pages/turma-chamada.php`)

**Rota:** `/admin/index.php?page=turma-chamada&turma_id={id}&aula_id={id}&origem=instrutor`

**Onde o aluno aparece:**
- **Lista de Chamada** (linhas 913-989)
  - Exibe: `aluno.nome` e `aluno.cpf` (linhas 928-929)
  - Dados carregados: Query linha 291-311 (com LEFT JOIN em `turma_presencas`)
  - **NÃO exibe:** Telefone, foto, categoria CNH
  - **TEM:** Função `visualizarAlunoInstrutor()` (linha 1033) que abre modal

**Como o aluno é carregado:**
```php
// Linha 291-311: Query com JOIN em turma_matriculas
SELECT 
    a.*,
    tm.status as status_matricula,
    tm.data_matricula,
    tm.frequencia_percentual,
    tp.presente,
    tp.justificativa as observacao_presenca,
    tp.registrado_em as presenca_registrada_em,
    tp.id as presenca_id
FROM alunos a
JOIN turma_matriculas tm ON a.id = tm.aluno_id
LEFT JOIN turma_presencas tp ON (
    a.id = tp.aluno_id 
    AND tp.turma_id = ? 
    AND tp.turma_aula_id = ?
)
WHERE tm.turma_id = ? 
AND tm.status IN ('matriculado', 'cursando', 'concluido')
```

**Modal existente:**
- ✅ **Modal `#modalAlunoInstrutor`** (linhas 1008-1030)
- ✅ **Função JavaScript `visualizarAlunoInstrutor(alunoId, turmaId)`** (linha 1033)
- ✅ **Endpoint usado:** `../admin/api/aluno-detalhes-instrutor.php?aluno_id={id}&turma_id={id}`

**Dados exibidos no modal:**
- Nome, CPF, email, telefone, data de nascimento
- Categoria CNH (`aluno.categoria_cnh`)
- Foto (`aluno.foto`)
- Status do aluno
- Dados da turma e frequência

**Botão "Ver Aluno":** ⚠️ **EXISTE mas não está visível na lista** - função existe mas não há botão na UI para acioná-la

---

### 1.4. Diário da Turma (`admin/pages/turma-diario.php`)

**Rota:** `/admin/index.php?page=turma-diario&turma_id={id}&aula_id={id}&origem=instrutor`

**Onde o aluno aparece:**
- **Lista de Alunos Matriculados** (linhas 184-200)
  - Exibe: Nome, CPF, email, telefone, foto (via query)
  - Dados carregados: Query linha 184-200
  - **TEM:** Modal `#modalAlunoInstrutor` (linha 1008) e função `visualizarAlunoInstrutor()` (linha 1033)

**Como o aluno é carregado:**
```php
// Linha 184-200: Query completa
SELECT 
    a.id,
    a.nome,
    a.cpf,
    a.email,
    a.telefone,
    a.data_nascimento,
    a.foto,
    tm.data_matricula,
    tm.status as status_matricula,
    tm.observacoes
FROM turma_matriculas tm
INNER JOIN alunos a ON tm.aluno_id = a.id
WHERE tm.turma_id = ?
ORDER BY a.nome ASC
```

**Modal existente:**
- ✅ Mesmo modal e função da chamada (reutilizado)

**Botão "Ver Aluno":** ⚠️ **EXISTE mas não está visível na lista** - função existe mas não há botão na UI

---

### 1.5. Ocorrências (`instrutor/ocorrencias.php`)

**Rota:** `/instrutor/ocorrencias.php`

**Onde o aluno aparece:**
- **Lista de Aulas** (linha 336)
  - Exibe: `aluno_nome` apenas
  - Dados carregados: Query linha 175
  - **NÃO exibe:** CPF, telefone, foto, categoria CNH
  - **NÃO tem:** Link ou botão de detalhes

**Como o aluno é carregado:**
```php
// Linha 175: Query simples
SELECT a.id, a.data_aula, a.hora_inicio, al.nome as aluno_nome
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
WHERE a.instrutor_id = ?
```

**Modal/Página de detalhes:** ❌ Não existe

**Botão "Ver Aluno":** ❌ Não existe

---

### 1.6. Contato (`instrutor/contato.php`)

**Rota:** `/instrutor/contato.php`

**Onde o aluno aparece:**
- **Lista de Aulas** (linha 346)
  - Exibe: `aluno_nome` apenas
  - Dados carregados: Query linha 171
  - **NÃO exibe:** CPF, telefone, foto, categoria CNH
  - **NÃO tem:** Link ou botão de detalhes

**Como o aluno é carregado:**
```php
// Linha 171: Query simples
SELECT a.id, a.data_aula, a.hora_inicio, al.nome as aluno_nome
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
WHERE a.instrutor_id = ?
```

**Modal/Página de detalhes:** ❌ Não existe

**Botão "Ver Aluno":** ❌ Não existe

---

### 1.7. Dashboard Mobile (`instrutor/dashboard-mobile.php`)

**Rota:** `/instrutor/dashboard-mobile.php`

**Onde o aluno aparece:**
- **Lista de Aulas** (linhas 348, 485)
  - Exibe: `aluno_nome` apenas
  - Dados carregados: Query linha 55
  - **NÃO exibe:** CPF, telefone, foto, categoria CNH
  - **NÃO tem:** Link ou botão de detalhes

**Modal/Página de detalhes:** ❌ Não existe

**Botão "Ver Aluno":** ❌ Não existe

---

## 2. MAPEAMENTO BACKEND: FONTE DOS DADOS E SEGURANÇA

### 2.1. Estrutura de Banco de Dados

#### Tabela `alunos`

**Campos relevantes para o instrutor:**

| Campo | Tipo | Descrição | Localização |
|-------|------|-----------|-------------|
| `id` | INT (PK) | ID único do aluno | `install.php:58-72` |
| `nome` | VARCHAR(100) | Nome completo | ✅ Usado em todas as queries |
| `cpf` | VARCHAR(14) | CPF (sem formatação) | ✅ Usado em `turma-chamada.php` e `turma-diario.php` |
| `telefone` | VARCHAR(20) | Telefone principal | ⚠️ Carregado mas não exibido em `dashboard.php` e `aulas.php` |
| `email` | VARCHAR(100) | Email | ✅ Usado em `aluno-detalhes-instrutor.php` |
| `foto` | VARCHAR(255) | Caminho da foto | ✅ Usado em `aluno-detalhes-instrutor.php` |
| `categoria_cnh` | ENUM | Categoria CNH (A, B, C, D, E, AB, AC, AD, AE) | ✅ Usado em `aluno-detalhes-instrutor.php` |
| `data_nascimento` | DATE | Data de nascimento | ✅ Usado em `aluno-detalhes-instrutor.php` |
| `status` | ENUM | Status (ativo, inativo, concluido) | ✅ Usado em `aluno-detalhes-instrutor.php` |

**Observação importante:** 
- `categoria_cnh` na tabela `alunos` é considerado **legado** (documentação em `docs/PADRONIZACAO_CATEGORIA_TIPO_SERVICO.md`)
- A fonte de verdade deve ser `matriculas.categoria_cnh` ou `turma_matriculas` (quando aplicável)
- Porém, para aulas práticas, o instrutor precisa ver a categoria pretendida do aluno, que pode estar apenas em `alunos.categoria_cnh`

#### Tabela `matriculas`

**Campos relevantes:**
- `categoria_cnh` - Categoria da matrícula ativa
- `tipo_servico` - Tipo de serviço (primeira_habilitacao, adicao, renovacao, etc.)
- `status` - Status da matrícula (ativa, concluida, trancada, cancelada)

**Regra de padronização:** Sempre priorizar dados da matrícula ativa quando existir, usando dados do aluno como fallback.

#### Tabela `turma_matriculas`

**Campos relevantes:**
- `frequencia_percentual` - Frequência do aluno na turma teórica
- `status` - Status da matrícula na turma (matriculado, cursando, concluido)

---

### 2.2. Endpoints Existentes

#### ✅ `admin/api/aluno-detalhes-instrutor.php`

**Rota:** `/admin/api/aluno-detalhes-instrutor.php?aluno_id={id}&turma_id={id}`

**Método:** GET

**Autenticação:**
- ✅ Verifica sessão (`$_SESSION['user_id']`, `$_SESSION['user_type']`)
- ✅ Verifica se é instrutor (`$userType !== 'instrutor'`)
- ✅ Obtém `instrutor_id` via `getCurrentInstrutorId($userId)`

**Validações de segurança:**
1. ✅ Verifica se instrutor tem aulas na turma:
   ```php
   SELECT COUNT(*) as total 
   FROM turma_aulas_agendadas 
   WHERE turma_id = ? AND instrutor_id = ?
   ```

2. ✅ Verifica se aluno está matriculado na turma:
   ```php
   SELECT id, status, data_matricula, frequencia_percentual 
   FROM turma_matriculas 
   WHERE turma_id = ? AND aluno_id = ?
   ```

**Dados retornados:**
```json
{
  "success": true,
  "aluno": {
    "id": 123,
    "nome": "João Silva",
    "cpf": "12345678900",
    "email": "joao@email.com",
    "telefone": "(87) 99999-9999",
    "data_nascimento": "1990-01-01",
    "categoria_cnh": "B",
    "foto": "/uploads/alunos/foto.jpg",
    "status_aluno": "ativo"
  },
  "turma": { ... },
  "matricula": { ... },
  "frequencia": { ... }
}
```

**Limitações:**
- ⚠️ **Requer `turma_id`** - Não funciona para aulas práticas (apenas teóricas)
- ⚠️ **Não valida vínculo de aula prática** - Só valida vínculo de turma teórica

**Uso atual:**
- ✅ Usado em `turma-chamada.php` (linha 1051)
- ✅ Usado em `turma-diario.php` (linha 1051)

---

#### ⚠️ `admin/api/alunos.php`

**Rota:** `/admin/api/alunos.php?id={id}`

**Método:** GET

**Autenticação:**
- ✅ Verifica sessão
- ⚠️ **Permite admin e secretaria** - Não restrito a instrutor

**Validações de segurança:**
- ⚠️ **NÃO valida vínculo instrutor-aluno** - Qualquer admin pode ver qualquer aluno
- ⚠️ **Retorna dados financeiros** - Não adequado para instrutor

**Dados retornados:**
- ✅ Dados completos do aluno (incluindo financeiro)
- ✅ Matrícula ativa com `categoria_cnh_matricula` e `tipo_servico_matricula`

**Recomendação:** ❌ **NÃO usar para instrutor** - Endpoint muito permissivo e expõe dados desnecessários

---

### 2.3. Segurança e Permissões

#### Validação de Vínculo Instrutor-Aluno

**Para aulas práticas:**
```sql
-- Verificar se instrutor tem aula com o aluno
SELECT COUNT(*) as total
FROM aulas
WHERE instrutor_id = ? AND aluno_id = ? AND status != 'cancelada'
```

**Para aulas teóricas:**
```sql
-- Verificar se instrutor tem aula na turma do aluno
SELECT COUNT(*) as total
FROM turma_aulas_agendadas taa
INNER JOIN turma_matriculas tm ON taa.turma_id = tm.turma_id
WHERE taa.instrutor_id = ? 
  AND tm.aluno_id = ? 
  AND tm.status IN ('matriculado', 'cursando', 'concluido')
```

**Status atual:**
- ✅ `aluno-detalhes-instrutor.php` valida vínculo de turma teórica
- ❌ **NÃO existe validação para aulas práticas** - Precisa ser implementada

---

### 2.4. Armazenamento de Foto

**Campo:** `alunos.foto` (VARCHAR 255)

**Formato esperado:**
- Caminho relativo: `/uploads/alunos/{filename}.jpg`
- Aceita: JPG, PNG, GIF, WebP
- Tamanho máximo: 2MB

**Upload:**
- Processado em `admin/api/alunos.php` (POST/PUT)
- Validação de tipo e tamanho implementada

**Exibição:**
- ✅ Usado em `aluno-detalhes-instrutor.php` (retorna caminho)
- ⚠️ **NÃO há fallback** - Se foto não existir, retorna string vazia ou NULL

---

### 2.5. Formatação de CPF

**Armazenamento:** Sem formatação (apenas números)

**Exibição:**
- ⚠️ **Não há formatação consistente** - Alguns lugares exibem sem máscara
- Recomendação: Criar função helper `formatarCPF($cpf)`

**Exemplo de formatação esperada:**
```php
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}
```

---

## 3. EVITAR DUPLICIDADE: RECOMENDAÇÃO DE REAPROVEITAMENTO

### 3.1. Endpoint Único Recomendado

#### ✅ Reaproveitar `admin/api/aluno-detalhes-instrutor.php`

**Vantagens:**
- ✅ Já implementado e testado
- ✅ Validações de segurança existentes
- ✅ Retorna dados essenciais (sem dados financeiros)
- ✅ Já usado em `turma-chamada.php` e `turma-diario.php`

**Limitações atuais:**
- ⚠️ Requer `turma_id` (não funciona para aulas práticas)
- ⚠️ Não valida vínculo de aula prática

**Refatoração necessária:**

1. **Tornar `turma_id` opcional:**
   ```php
   // Se turma_id fornecido: validar vínculo de turma teórica
   // Se não fornecido: validar vínculo de aula prática
   ```

2. **Adicionar validação de aula prática:**
   ```php
   // Se não tem turma_id, verificar se instrutor tem aula prática com o aluno
   if (!$turmaId) {
       $temAulaPratica = $db->fetch(
           "SELECT COUNT(*) as total 
            FROM aulas 
            WHERE instrutor_id = ? AND aluno_id = ? AND status != 'cancelada'",
           [$instrutorId, $alunoId]
       );
       if (!$temAulaPratica || $temAulaPratica['total'] == 0) {
           responderJsonErro('Você não tem aulas com este aluno', 403);
       }
   }
   ```

3. **Buscar categoria CNH da matrícula ativa (fallback para aluno):**
   ```php
   // Priorizar categoria da matrícula ativa
   $matriculaAtiva = $db->fetch(
       "SELECT categoria_cnh, tipo_servico 
        FROM matriculas 
        WHERE aluno_id = ? AND status = 'ativa' 
        ORDER BY data_inicio DESC LIMIT 1",
       [$alunoId]
   );
   $categoriaCNH = $matriculaAtiva['categoria_cnh'] ?? $aluno['categoria_cnh'] ?? 'Não informado';
   ```

---

### 3.2. Service/Repository Centralizado

#### Recomendação: Criar `includes/services/AlunoService.php`

**Responsabilidades:**
1. **Consulta do aluno com validação de permissão:**
   ```php
   public static function buscarDadosAlunoParaInstrutor($alunoId, $instrutorId, $turmaId = null)
   ```

2. **Regras de permissão:**
   ```php
   private static function validarPermissaoInstrutor($alunoId, $instrutorId, $turmaId = null)
   ```

3. **Formatação/máscara de CPF:**
   ```php
   public static function formatarCPF($cpf)
   ```

4. **Montagem do payload para UI:**
   ```php
   public static function montarPayloadAluno($aluno, $matriculaAtiva = null, $frequencia = null)
   ```

**Vantagens:**
- ✅ Centraliza lógica de negócio
- ✅ Facilita testes
- ✅ Reutilizável em múltiplos endpoints
- ✅ Mantém consistência de dados

**Estrutura proposta:**
```
includes/
  services/
    AlunoService.php
      - buscarDadosAlunoParaInstrutor()
      - validarPermissaoInstrutor()
      - formatarCPF()
      - montarPayloadAluno()
      - buscarCategoriaCNH() // Prioriza matrícula ativa
```

---

### 3.3. Componente Reutilizável de UI

#### Recomendação: Criar componente JavaScript `assets/js/components/aluno-card.js`

**Responsabilidades:**
- Renderizar card de aluno com foto, nome, CPF, telefone, categoria
- Abrir modal de detalhes
- Formatação de dados (CPF, telefone)

**Uso:**
```javascript
// Em qualquer página do instrutor
import { AlunoCard } from '../assets/js/components/aluno-card.js';

// Renderizar card
AlunoCard.render({
  alunoId: 123,
  nome: 'João Silva',
  cpf: '12345678900',
  telefone: '87999999999',
  foto: '/uploads/alunos/foto.jpg',
  categoriaCNH: 'B',
  onViewDetails: (alunoId) => {
    // Abrir modal de detalhes
  }
});
```

---

## 4. MELHOR FORMA DE IMPLEMENTAR (ARQUITETURA + UX)

### 4.1. Análise de Opções

#### Opção A: Modal Leve "Detalhes do Aluno"

**Vantagens:**
- ✅ Não interrompe fluxo de trabalho
- ✅ Rápido de abrir/fechar
- ✅ Já existe parcialmente (`#modalAlunoInstrutor`)
- ✅ Funciona bem em mobile

**Desvantagens:**
- ⚠️ Limitado em espaço (pode precisar scroll)
- ⚠️ Não permite deep linking (não tem URL única)
- ⚠️ Histórico do navegador não funciona

**Implementação:**
- Reutilizar modal existente `#modalAlunoInstrutor`
- Adicionar botões "Ver Aluno" nas listagens
- Endpoint: `admin/api/aluno-detalhes-instrutor.php` (refatorado)

---

#### Opção B: Página Dedicada `/instrutor/alunos/:id`

**Vantagens:**
- ✅ URL única e compartilhável
- ✅ Mais espaço para informações
- ✅ Histórico do navegador funciona
- ✅ Permite adicionar mais funcionalidades futuras

**Desvantagens:**
- ⚠️ Interrompe fluxo (precisa navegar e voltar)
- ⚠️ Mais complexo de implementar
- ⚠️ Pode ser menos ágil em mobile

**Implementação:**
- Criar `instrutor/aluno-detalhes.php?id={aluno_id}`
- Endpoint: `admin/api/aluno-detalhes-instrutor.php` (refatorado)
- Layout seguindo padrão do projeto

---

### 4.2. Recomendação: **OPÇÃO A (Modal) + Atalho para Página**

**Justificativa:**
1. **Modal é mais ágil** - Instrutor precisa de acesso rápido durante a aula
2. **Já existe infraestrutura** - Modal e endpoint já implementados
3. **Mobile-first** - Modal funciona melhor em dispositivos móveis
4. **Página como complemento** - Para casos onde mais informações são necessárias

**Implementação híbrida:**
- **Modal como padrão** - Botão "Ver Aluno" abre modal
- **Link "Ver mais detalhes"** - Dentro do modal, link para página completa
- **Página como fallback** - Se modal não carregar, redirecionar para página

---

### 4.3. Fluxo do Usuário Recomendado

#### Cenário 1: Dashboard / Lista de Aulas

1. Instrutor vê nome do aluno na lista
2. Clica em **botão "Ver Aluno"** ou **nome clicável**
3. Modal abre com loading
4. Dados carregam via AJAX (`aluno-detalhes-instrutor.php`)
5. Modal exibe:
   - Foto do aluno (ou avatar padrão)
   - Nome completo
   - CPF formatado
   - Telefone (com botão para ligar/WhatsApp)
   - Email
   - Categoria CNH
   - Status do aluno
6. Botão "Fechar" fecha modal
7. Link "Ver mais detalhes" (opcional) abre página completa

#### Cenário 2: Chamada / Diário

1. Instrutor já está na tela de chamada/diário
2. Clica em **botão "Ver Aluno"** na linha do aluno
3. Mesmo fluxo do modal acima
4. Modal não fecha a chamada (permite marcar presença depois)

---

### 4.4. Componentes Reaproveitáveis

#### 1. Card de Aluno (Avatar + Info Básica)

**Localização:** `assets/js/components/aluno-card.js` ou componente PHP

**Props:**
- `alunoId`
- `nome`
- `cpf` (formatado)
- `telefone` (formatado)
- `foto` (ou avatar padrão)
- `categoriaCNH`
- `onViewDetails` (callback)

**Uso:**
```php
// Em dashboard.php, aulas.php, etc.
<?php include __DIR__ . '/../assets/components/aluno-card.php'; ?>
<button onclick="abrirModalAluno(<?= $aula['aluno_id'] ?>)">
    Ver Aluno
</button>
```

---

#### 2. Modal de Detalhes do Aluno

**Localização:** `assets/components/modal-aluno-instrutor.php`

**Reutilização:**
- Incluir em todas as páginas do instrutor
- JavaScript centralizado em `assets/js/modal-aluno-instrutor.js`

**Estrutura:**
```html
<div class="modal fade" id="modalAlunoInstrutor">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- Header -->
      <!-- Body (carregado via AJAX) -->
      <!-- Footer -->
    </div>
  </div>
</div>
```

---

#### 3. Função JavaScript Centralizada

**Localização:** `assets/js/modal-aluno-instrutor.js`

**Funções:**
```javascript
// Abrir modal e carregar dados
function abrirModalAluno(alunoId, turmaId = null)

// Formatar CPF
function formatarCPF(cpf)

// Formatar telefone
function formatarTelefone(telefone)

// Renderizar dados no modal
function renderizarDadosAluno(dados)
```

---

### 4.5. Padrão de Layout

#### Seguir padrão existente do projeto

**Referências:**
- Modal `#modalAlunoInstrutor` em `turma-chamada.php` (linha 1008)
- Layout Bootstrap 5 (já usado no projeto)
- Cards com sombra e bordas arredondadas

**Estrutura do modal:**
```
┌─────────────────────────────────────┐
│ [Ícone] Detalhes do Aluno    [X]   │
├─────────────────────────────────────┤
│                                     │
│  [Foto/Avatar]  Nome Completo      │
│                  CPF: 123.456.789-00│
│                  Telefone: (87) ... │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ Categoria CNH: B               │ │
│  │ Status: Ativo                  │ │
│  │ Email: aluno@email.com         │ │
│  └───────────────────────────────┘ │
│                                     │
│  [Botão Ligar] [Botão WhatsApp]    │
│                                     │
│  [Ver mais detalhes] [Fechar]      │
└─────────────────────────────────────┘
```

---

### 4.6. Regras de Fallback

#### Foto do Aluno

```php
// Se foto não existir ou estiver vazia
$fotoUrl = !empty($aluno['foto']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $aluno['foto'])
    ? $aluno['foto']
    : '/assets/images/avatar-default.png'; // Avatar padrão
```

#### Categoria CNH

```php
// Priorizar matrícula ativa, depois aluno, depois "Não informado"
$categoriaCNH = $matriculaAtiva['categoria_cnh'] 
    ?? $aluno['categoria_cnh'] 
    ?? 'Não informado';
```

#### Telefone

```php
// Se telefone não existir
$telefone = $aluno['telefone'] ?? 'Não informado';
```

#### CPF

```php
// Sempre formatar, mesmo se vazio
$cpfFormatado = !empty($aluno['cpf']) 
    ? formatarCPF($aluno['cpf'])
    : 'Não informado';
```

---

### 4.7. Privacidade Mínima

#### Dados NÃO retornados para instrutor

- ❌ Dados financeiros (faturas, pagamentos, inadimplência)
- ❌ Dados administrativos (observações internas)
- ❌ Histórico completo (apenas resumo)
- ❌ Dados de outros alunos

#### Dados retornados (essenciais)

- ✅ Nome completo
- ✅ CPF (formatado)
- ✅ Telefone
- ✅ Email
- ✅ Foto (se existir)
- ✅ Categoria CNH
- ✅ Status do aluno
- ✅ Frequência (apenas se for turma teórica)

---

## 5. ENTREGÁVEIS

### 5.1. Lista de Arquivos/Rotas Existentes

#### Frontend (UI)

| Arquivo | Rota | Onde Aluno Aparece | Dados Exibidos | Modal/Botão |
|---------|------|-------------------|----------------|-------------|
| `instrutor/dashboard.php` | `/instrutor/dashboard.php` | Card próxima aula, tabela hoje, próximas aulas | Nome apenas | ❌ Não |
| `instrutor/aulas.php` | `/instrutor/aulas.php` | Lista de aulas práticas | Nome apenas | ❌ Não |
| `instrutor/dashboard-mobile.php` | `/instrutor/dashboard-mobile.php` | Lista de aulas | Nome apenas | ❌ Não |
| `instrutor/ocorrencias.php` | `/instrutor/ocorrencias.php` | Lista de aulas | Nome apenas | ❌ Não |
| `instrutor/contato.php` | `/instrutor/contato.php` | Lista de aulas | Nome apenas | ❌ Não |
| `admin/pages/turma-chamada.php` | `/admin/index.php?page=turma-chamada` | Lista de chamada | Nome, CPF | ✅ Modal existe, botão não visível |
| `admin/pages/turma-diario.php` | `/admin/index.php?page=turma-diario` | Lista de alunos | Nome, CPF, telefone, foto | ✅ Modal existe, botão não visível |

#### Backend (APIs)

| Arquivo | Rota | Método | Validação | Dados Retornados |
|---------|------|--------|-----------|------------------|
| `admin/api/aluno-detalhes-instrutor.php` | `/admin/api/aluno-detalhes-instrutor.php` | GET | ✅ Turma teórica | Nome, CPF, telefone, email, foto, categoria, frequência |
| `admin/api/alunos.php` | `/admin/api/alunos.php` | GET | ⚠️ Admin apenas | Todos os dados (incluindo financeiro) |

---

### 5.2. Estratégia Recomendada

#### **OPÇÃO A: Modal Leve + Refatoração do Endpoint**

**Justificativa:**
1. ✅ **Já existe infraestrutura** - Modal e endpoint parcialmente implementados
2. ✅ **Mais ágil** - Não interrompe fluxo de trabalho do instrutor
3. ✅ **Mobile-first** - Funciona melhor em dispositivos móveis
4. ✅ **Menos código** - Reaproveita componentes existentes

**Implementação:**
- Refatorar `aluno-detalhes-instrutor.php` para suportar aulas práticas
- Adicionar botões "Ver Aluno" nas listagens
- Centralizar modal em componente reutilizável
- Criar service centralizado para lógica de negócio

---

### 5.3. Plano de Implementação (Passos Curtos)

#### Fase 1: Refatoração do Endpoint (2-3 horas)

**Arquivo:** `admin/api/aluno-detalhes-instrutor.php`

**Tarefas:**
1. ✅ Tornar `turma_id` opcional (linha 77-89)
2. ✅ Adicionar validação de aula prática quando `turma_id` não fornecido (novo bloco após linha 102)
3. ✅ Buscar categoria CNH da matrícula ativa (fallback para aluno) (após linha 132)
4. ✅ Adicionar formatação de CPF no retorno (linha 220)
5. ✅ Adicionar fallback de foto (avatar padrão) (linha 225)

**Validações adicionais:**
```php
// Se não tem turma_id, validar aula prática
if (!$turmaId) {
    $temAulaPratica = $db->fetch(
        "SELECT COUNT(*) as total 
         FROM aulas 
         WHERE instrutor_id = ? AND aluno_id = ? AND status != 'cancelada'",
        [$instrutorId, $alunoId]
    );
    if (!$temAulaPratica || $temAulaPratica['total'] == 0) {
        responderJsonErro('Você não tem aulas com este aluno', 403, [
            'code' => 'INSTRUTOR_SEM_AULA_PRATICA',
        ]);
    }
}
```

---

#### Fase 2: Criar Service Centralizado (1-2 horas)

**Arquivo:** `includes/services/AlunoService.php` (novo)

**Tarefas:**
1. ✅ Criar classe `AlunoService`
2. ✅ Método `buscarDadosAlunoParaInstrutor($alunoId, $instrutorId, $turmaId = null)`
3. ✅ Método `validarPermissaoInstrutor($alunoId, $instrutorId, $turmaId = null)`
4. ✅ Método `formatarCPF($cpf)`
5. ✅ Método `buscarCategoriaCNH($alunoId)` (prioriza matrícula ativa)
6. ✅ Método `montarPayloadAluno($aluno, $matriculaAtiva = null)`

**Estrutura:**
```php
<?php
class AlunoService {
    public static function buscarDadosAlunoParaInstrutor($alunoId, $instrutorId, $turmaId = null) {
        // Validação de permissão
        self::validarPermissaoInstrutor($alunoId, $instrutorId, $turmaId);
        
        // Buscar dados do aluno
        $aluno = ...;
        
        // Buscar matrícula ativa
        $matriculaAtiva = ...;
        
        // Montar payload
        return self::montarPayloadAluno($aluno, $matriculaAtiva);
    }
    
    // ... outros métodos
}
```

---

#### Fase 3: Componente Modal Reutilizável (2-3 horas)

**Arquivo:** `assets/components/modal-aluno-instrutor.php` (novo)

**Tarefas:**
1. ✅ Extrair HTML do modal de `turma-chamada.php` (linhas 1008-1030)
2. ✅ Tornar componente reutilizável (aceitar parâmetros)
3. ✅ Incluir em todas as páginas do instrutor:
   - `instrutor/dashboard.php`
   - `instrutor/aulas.php`
   - `instrutor/dashboard-mobile.php`
   - `instrutor/ocorrencias.php`
   - `instrutor/contato.php`

**JavaScript:** `assets/js/modal-aluno-instrutor.js` (novo)

**Tarefas:**
1. ✅ Extrair função `visualizarAlunoInstrutor()` de `turma-chamada.php`
2. ✅ Tornar função genérica (funciona com ou sem `turma_id`)
3. ✅ Adicionar formatação de CPF e telefone
4. ✅ Adicionar fallback de foto (avatar padrão)

---

#### Fase 4: Adicionar Botões "Ver Aluno" (1-2 horas)

**Arquivos a modificar:**
- `instrutor/dashboard.php`
- `instrutor/aulas.php`
- `instrutor/dashboard-mobile.php`
- `instrutor/ocorrencias.php`
- `instrutor/contato.php`

**Tarefas:**
1. ✅ Adicionar botão "Ver Aluno" ao lado do nome do aluno
2. ✅ Tornar nome do aluno clicável (alternativa ao botão)
3. ✅ Conectar botão/clique à função `abrirModalAluno(alunoId)`
4. ✅ Para aulas práticas: passar apenas `alunoId`
5. ✅ Para aulas teóricas: passar `alunoId` e `turmaId`

**Exemplo de implementação:**
```php
// Em dashboard.php, linha ~787
<div class="fw-bold" style="font-size: 0.875rem; line-height: 1.3;">
    <a href="#" onclick="abrirModalAluno(<?= $aula['aluno_id'] ?>); return false;" 
       class="text-primary" style="text-decoration: none;">
        <?php echo htmlspecialchars($aula['aluno_nome'] ?? 'Aluno não informado'); ?>
    </a>
    <button class="btn btn-sm btn-outline-primary ml-2" 
            onclick="abrirModalAluno(<?= $aula['aluno_id'] ?>);">
        <i class="fas fa-user"></i> Ver Aluno
    </button>
</div>
```

---

#### Fase 5: Melhorias de UX (1 hora)

**Tarefas:**
1. ✅ Adicionar loading state no modal
2. ✅ Adicionar tratamento de erros (aluno não encontrado, sem permissão)
3. ✅ Adicionar botão "Ligar" (tel:)
4. ✅ Adicionar botão "WhatsApp" (wa.me)
5. ✅ Adicionar tooltip nos botões
6. ✅ Melhorar responsividade mobile

---

#### Fase 6: Testes Manuais (Checklist)

**Cenários de teste:**

1. **Dashboard - Aula Prática:**
   - [ ] Clicar em nome do aluno → Modal abre
   - [ ] Modal exibe: nome, CPF formatado, telefone, foto (ou avatar), categoria CNH
   - [ ] Botão "Ligar" funciona
   - [ ] Botão "WhatsApp" funciona
   - [ ] Botão "Fechar" fecha modal

2. **Lista de Aulas - Aula Prática:**
   - [ ] Clicar em "Ver Aluno" → Modal abre
   - [ ] Dados corretos exibidos
   - [ ] Modal não quebra layout da página

3. **Chamada - Aula Teórica:**
   - [ ] Clicar em "Ver Aluno" → Modal abre
   - [ ] Modal exibe frequência da turma
   - [ ] Dados corretos exibidos

4. **Validação de Permissão:**
   - [ ] Instrutor A tenta ver aluno de Instrutor B → Erro 403
   - [ ] Instrutor sem aulas com aluno → Erro 403
   - [ ] Mensagem de erro clara exibida

5. **Fallbacks:**
   - [ ] Aluno sem foto → Avatar padrão exibido
   - [ ] Aluno sem categoria CNH → "Não informado" exibido
   - [ ] Aluno sem telefone → "Não informado" exibido

6. **Mobile:**
   - [ ] Modal responsivo em mobile
   - [ ] Botões acessíveis (tamanho adequado)
   - [ ] Texto legível

---

### 5.4. Arquivos a Criar/Modificar

#### Novos Arquivos

1. `includes/services/AlunoService.php` - Service centralizado
2. `assets/components/modal-aluno-instrutor.php` - Componente modal
3. `assets/js/modal-aluno-instrutor.js` - JavaScript do modal

#### Arquivos a Modificar

1. `admin/api/aluno-detalhes-instrutor.php` - Refatorar para suportar aulas práticas
2. `instrutor/dashboard.php` - Adicionar botão "Ver Aluno" e incluir modal
3. `instrutor/aulas.php` - Adicionar botão "Ver Aluno" e incluir modal
4. `instrutor/dashboard-mobile.php` - Adicionar botão "Ver Aluno" e incluir modal
5. `instrutor/ocorrencias.php` - Adicionar botão "Ver Aluno" e incluir modal
6. `instrutor/contato.php` - Adicionar botão "Ver Aluno" e incluir modal
7. `admin/pages/turma-chamada.php` - Adicionar botão "Ver Aluno" visível na lista
8. `admin/pages/turma-diario.php` - Adicionar botão "Ver Aluno" visível na lista

---

### 5.5. Estimativa de Tempo

| Fase | Tempo Estimado | Prioridade |
|------|----------------|------------|
| Fase 1: Refatoração do Endpoint | 2-3 horas | 🔴 Alta |
| Fase 2: Service Centralizado | 1-2 horas | 🟡 Média |
| Fase 3: Componente Modal | 2-3 horas | 🔴 Alta |
| Fase 4: Adicionar Botões | 1-2 horas | 🔴 Alta |
| Fase 5: Melhorias de UX | 1 hora | 🟢 Baixa |
| Fase 6: Testes Manuais | 1-2 horas | 🔴 Alta |
| **TOTAL** | **8-13 horas** | |

---

## 📝 CONCLUSÃO

### Resumo Executivo

1. **Situação Atual:**
   - ✅ Modal e endpoint existem mas são limitados a turmas teóricas
   - ❌ Botões "Ver Aluno" não estão visíveis na maioria das telas
   - ❌ Endpoint não funciona para aulas práticas
   - ⚠️ Dados do aluno aparecem apenas como nome em várias telas

2. **Recomendação:**
   - ✅ **Opção A: Modal leve** (mais ágil, já existe infraestrutura)
   - ✅ Refatorar endpoint para suportar aulas práticas
   - ✅ Adicionar botões "Ver Aluno" em todas as listagens
   - ✅ Criar service centralizado para lógica de negócio

3. **Próximos Passos:**
   - Implementar Fase 1 (Refatoração do Endpoint)
   - Implementar Fase 3 (Componente Modal)
   - Implementar Fase 4 (Adicionar Botões)
   - Testar e validar

---

**Documento gerado em:** 2025-01-27  
**Versão:** 1.0  
**Autor:** Sistema de Auditoria CFC Bom Conselho
