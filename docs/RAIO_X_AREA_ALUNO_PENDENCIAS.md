# 🔍 RAIO-X: ÁREA DO ALUNO (PENDÊNCIAS)
## Sistema CFC Bom Conselho - Análise Completa

**Data:** 24/11/2025  
**Objetivo:** Mapear o que já existe e o que pode ser reaproveitado para implementar as 4 páginas pendentes da área do aluno

---

## 📋 ÍNDICE

1. [Visão Geral da Área do Aluno](#1-visão-geral-da-área-do-aluno)
2. [Pendência 1: Aulas (aluno/aulas.php)](#2-pendência-1-aulas-alunoaulasphp)
3. [Pendência 2: Notificações (aluno/notificacoes.php)](#3-pendência-2-notificações-alunonotificacoesphp)
4. [Pendência 3: Financeiro (aluno/financeiro.php)](#4-pendência-3-financeiro-alunofinanceirophp)
5. [Pendência 4: Contato (aluno/contato.php)](#5-pendência-4-contato-alunocontatophp)
6. [Checklist de Reaproveitamento](#6-checklist-de-reaproveitamento)

---

## 1. VISÃO GERAL DA ÁREA DO ALUNO

### 1.1. Páginas Existentes

✅ **Implementadas e Funcionais:**
- `aluno/dashboard.php` - Dashboard principal com ações rápidas
- `aluno/presencas-teoricas.php` - Presenças teóricas do aluno
- `aluno/historico.php` - Histórico completo do aluno

### 1.2. Páginas Pendentes

❌ **A Implementar:**
- `aluno/aulas.php` - Ver todas as aulas (teóricas + práticas)
- `aluno/notificacoes.php` - Central de avisos do aluno
- `aluno/financeiro.php` - Financeiro do aluno
- `aluno/contato.php` - Contato com o CFC

### 1.3. Funções de Autenticação

✅ **Disponíveis:**
- `getCurrentAlunoId()` - Retorna o ID do aluno logado (em `includes/auth.php`)
- `isLoggedIn()` - Verifica se usuário está autenticado
- `getCurrentUser()` - Retorna dados do usuário logado

### 1.4. APIs Existentes

✅ **Disponíveis:**
- `admin/api/turma-frequencia.php` - Frequência teórica (já suporta aluno)
- `admin/api/turma-presencas.php` - Presenças teóricas (já suporta aluno)
- `admin/api/notificacoes.php` - Notificações (suporta aluno via `tipo_usuario = 'aluno'`)
- `admin/api/financeiro-faturas.php` - Faturas (precisa adaptar para aluno)
- `admin/api/solicitacoes.php` - Solicitações (já usado no dashboard do aluno)

---

## 2. PENDÊNCIA 1: AULAS (aluno/aulas.php)

### 2.1. O que já existe no backend/API

#### 2.1.1. Tabelas de Banco de Dados

**Aulas Práticas:**
- `aulas` - Tabela principal de aulas práticas
  - Campos: `id`, `aluno_id`, `instrutor_id`, `tipo_aula`, `data_aula`, `hora_inicio`, `hora_fim`, `status`
  - Status: `agendada`, `em_andamento`, `concluida`, `cancelada`
  - Relacionamentos: `aluno_id` → `alunos.id`, `instrutor_id` → `instrutores.id`

**Aulas Teóricas:**
- `turma_aulas_agendadas` - Aulas teóricas agendadas
  - Campos: `id`, `turma_id`, `disciplina`, `nome_aula`, `data_aula`, `hora_inicio`, `hora_fim`, `status`
  - Status: `agendada`, `realizada`, `cancelada`
  - Relacionamento: `turma_id` → `turmas_teoricas.id`
- `turma_matriculas` - Matrículas do aluno em turmas teóricas
  - Campos: `turma_id`, `aluno_id`, `status`
  - Relacionamento: `aluno_id` → `alunos.id`

#### 2.1.2. Queries Existentes

**Dashboard do Aluno (`aluno/dashboard.php`):**
```php
// Aulas práticas próximas (próximos 14 dias)
SELECT a.*, i.nome as instrutor_nome, v.modelo as veiculo_modelo, v.placa as veiculo_placa
FROM aulas a
JOIN instrutores i ON a.instrutor_id = i.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.aluno_id = ?
  AND a.data_aula >= CURDATE() 
  AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
  AND a.status != 'cancelada'
ORDER BY a.data_aula ASC, a.hora_inicio ASC
```

**Instrutor (`instrutor/aulas.php`):**
```php
// Aulas práticas do instrutor
SELECT a.*, al.nome as aluno_nome, al.telefone as aluno_telefone,
       v.modelo as veiculo_modelo, v.placa as veiculo_placa
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.instrutor_id = ?
  AND a.data_aula >= ? AND a.data_aula <= ?
ORDER BY a.data_aula DESC, a.hora_inicio DESC

// Aulas teóricas do instrutor
SELECT taa.*, tt.nome as turma_nome, s.nome as sala_nome
FROM turma_aulas_agendadas taa
JOIN turmas_teoricas tt ON taa.turma_id = tt.id
LEFT JOIN salas s ON taa.sala_id = s.id
WHERE taa.instrutor_id = ?
  AND taa.data_aula >= ? AND taa.data_aula <= ?
ORDER BY taa.data_aula DESC, taa.hora_inicio DESC
```

**Presenças Teóricas (`aluno/presencas-teoricas.php`):**
```php
// Turmas teóricas do aluno
SELECT tm.*, tt.nome as turma_nome, tt.curso_tipo, tt.data_inicio, tt.data_fim
FROM turma_matriculas tm
JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = ?
  AND tm.status IN ('matriculado', 'cursando', 'concluido')

// Aulas teóricas da turma
SELECT taa.*, i.nome as instrutor_nome, s.nome as sala_nome
FROM turma_aulas_agendadas taa
LEFT JOIN instrutores i ON taa.instrutor_id = i.id
LEFT JOIN salas s ON taa.sala_id = s.id
WHERE taa.turma_id = ?
  AND taa.status IN ('agendada', 'realizada')
ORDER BY taa.ordem_global ASC
```

#### 2.1.3. APIs Existentes

**Nenhuma API específica para listar aulas do aluno encontrada.**

**APIs que podem ser adaptadas:**
- `includes/controllers/AgendamentoController.php` - Método `listarAulas()` (linha 283)
  - Aceita filtros: `aluno_id`, `data_inicio`, `data_fim`, `status`, `tipo_aula`
  - Retorna aulas práticas com JOINs completos

### 2.2. Referências em Admin/Instrutor

**Páginas de Referência:**
- `instrutor/aulas.php` - Lista aulas práticas e teóricas do instrutor
  - Filtros: período (data_inicio/data_fim), status, tipo
  - Layout: cards responsivos, tabela com dados completos
  - **Pode ser adaptado diretamente para aluno**

**Estrutura de Dados Necessária:**
```php
// Aulas Práticas
[
    'id' => int,
    'tipo' => 'pratica',
    'data_aula' => 'YYYY-MM-DD',
    'hora_inicio' => 'HH:MM:SS',
    'hora_fim' => 'HH:MM:SS',
    'status' => 'agendada|em_andamento|concluida|cancelada',
    'instrutor_nome' => string,
    'veiculo_modelo' => string,
    'veiculo_placa' => string
]

// Aulas Teóricas
[
    'id' => int,
    'tipo' => 'teorica',
    'turma_id' => int,
    'turma_nome' => string,
    'disciplina' => string,
    'nome_aula' => string,
    'data_aula' => 'YYYY-MM-DD',
    'hora_inicio' => 'HH:MM:SS',
    'hora_fim' => 'HH:MM:SS',
    'status' => 'agendada|realizada|cancelada',
    'instrutor_nome' => string,
    'sala_nome' => string
]
```

### 2.3. O que precisamos criar

**API Mínima (opcional):**
- `admin/api/aluno-aulas.php` - GET apenas
  - Filtrar por `aluno_id` da sessão (via `getCurrentAlunoId()`)
  - Aceitar filtros: `tipo` (pratica/teorica/todas), `periodo` (7dias/30dias/todas), `status`
  - Retornar aulas práticas e teóricas unificadas

**Ou implementar diretamente na página:**
- Fazer queries diretas na página (como em `instrutor/aulas.php`)
- Mais simples, sem necessidade de API adicional

### 2.4. Checklist de Reaproveitamento

✅ **Queries SQL:** Reaproveitar queries de `instrutor/aulas.php` e `aluno/dashboard.php`  
✅ **Layout:** Adaptar estrutura de `instrutor/aulas.php`  
✅ **Filtros:** Reaproveitar lógica de filtros de período e status  
❌ **API:** Criar API mínima ou fazer queries diretas na página  

---

## 3. PENDÊNCIA 2: NOTIFICAÇÕES (aluno/notificacoes.php)

### 3.1. Como as notificações estão estruturadas hoje

#### 3.1.1. Tabela de Banco de Dados

**`notificacoes`:**
- Campos: `id`, `usuario_id`, `tipo_usuario`, `titulo`, `mensagem`, `dados` (JSON), `lida`, `lida_em`, `criado_em`
- `tipo_usuario`: `'aluno'`, `'instrutor'`, `'admin'`, `'secretaria'`
- `usuario_id`: ID do usuário na tabela correspondente (alunos.id, instrutores.id, usuarios.id)

#### 3.1.2. API Existente

**`admin/api/notificacoes.php` - JÁ SUPORTA ALUNO:**
```php
// GET - Buscar notificações
SELECT n.*, a.nome as nome_usuario
FROM notificacoes n
LEFT JOIN alunos a ON n.usuario_id = a.id AND n.tipo_usuario = 'aluno'
WHERE n.usuario_id = ? AND n.tipo_usuario = ?
ORDER BY n.criado_em DESC LIMIT ?

// POST - Marcar como lida
UPDATE notificacoes SET lida = TRUE, lida_em = NOW() WHERE id = ?

// PUT - Marcar todas como lidas
UPDATE notificacoes SET lida = TRUE, lida_em = NOW() 
WHERE usuario_id = ? AND tipo_usuario = ? AND lida = FALSE
```

**Validação de Segurança:**
- Verifica `usuario_id` e `tipo_usuario` na sessão
- Aluno só vê suas próprias notificações

#### 3.1.3. Serviço de Notificações

**`includes/services/SistemaNotificacoes.php`:**
- Método `buscarNotificacoesNaoLidas($usuarioId, $tipoUsuario)` - Já usado no dashboard
- Método `marcarComoLida($notificacaoId)`
- Método `marcarTodasComoLidas($usuarioId, $tipoUsuario)`

### 3.2. Como o instrutor vê

**`instrutor/notificacoes.php` - ESTRUTURA COMPLETA:**
- Lista todas as notificações do instrutor
- Estatísticas: Total, Não lidas, Lidas
- Ações: Marcar como lida, Marcar todas como lidas, Filtrar não lidas
- Layout: Cards com detalhes expansíveis
- API: Usa `admin/api/notificacoes.php` (GET, POST, PUT)

**Query usada:**
```php
SELECT n.*, i.nome as nome_usuario
FROM notificacoes n
LEFT JOIN instrutores i ON n.usuario_id = i.id AND n.tipo_usuario = 'instrutor'
WHERE n.usuario_id = ? AND n.tipo_usuario = 'instrutor'
ORDER BY n.criado_em DESC LIMIT 100
```

### 3.3. Como o aluno deveria ver

**Estrutura Idêntica ao Instrutor:**
- Mesma query, apenas trocar `tipo_usuario = 'instrutor'` por `tipo_usuario = 'aluno'`
- Mesma API (`admin/api/notificacoes.php`)
- Mesmo layout e funcionalidades

**Ajuste Necessário:**
- Trocar JOIN: `LEFT JOIN alunos a ON n.usuario_id = a.id` (ao invés de instrutores)
- Usar `getCurrentAlunoId()` para obter `usuario_id` do aluno

### 3.4. Checklist de Reaproveitamento

✅ **API:** `admin/api/notificacoes.php` já suporta aluno  
✅ **Serviço:** `SistemaNotificacoes` já suporta aluno  
✅ **Layout:** Copiar `instrutor/notificacoes.php` e adaptar  
✅ **Queries:** Mesma query, apenas trocar tipo_usuario  
✅ **Segurança:** API já valida usuario_id e tipo_usuario  

---

## 4. PENDÊNCIA 3: FINANCEIRO (aluno/financeiro.php)

### 4.1. Quais tabelas e APIs tratam financeiro do aluno

#### 4.1.1. Tabelas de Banco de Dados

**`financeiro_faturas`:**
- Campos: `id`, `aluno_id`, `titulo`, `valor_total`, `data_vencimento`, `status`, `forma_pagamento`, `observacoes`, `matricula_id`, `parcelas`
- Status: `aberta`, `paga`, `vencida`, `cancelada`
- Relacionamento: `aluno_id` → `alunos.id`

**`pagamentos`:**
- Campos: `id`, `fatura_id`, `data_pagamento`, `valor_pago`, `metodo`, `comprovante_url`, `obs`, `criado_por`
- Relacionamento: `fatura_id` → `financeiro_faturas.id`

#### 4.1.2. APIs Existentes

**`admin/api/financeiro-faturas.php` - PRECISA ADAPTAR:**
```php
// GET - Listar faturas
// Atualmente: Apenas admin/secretaria podem acessar
// Precisa: Adicionar suporte para aluno (filtro por aluno_id da sessão)

function handleGet($db, $user) {
    $aluno_id = $_GET['aluno_id'] ?? null;
    // ... filtros ...
    
    // ADAPTAÇÃO NECESSÁRIA:
    // Se user['tipo'] === 'aluno', forçar aluno_id = getCurrentAlunoId()
    // Ignorar qualquer aluno_id vindo da URL
}
```

**Estrutura de Resposta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "aluno_id": 123,
            "titulo": "Matrícula",
            "valor_total": 500.00,
            "data_vencimento": "2025-12-01",
            "status": "aberta",
            "forma_pagamento": "avista",
            "observacoes": "..."
        }
    ],
    "pagination": {...}
}
```

#### 4.1.3. Páginas Admin de Referência

**`admin/pages/financeiro-faturas.php`:**
- Lista faturas com filtros
- Mostra: Nº, Aluno, Título, Valor, Vencimento, Status
- Ações: Visualizar, Editar, Baixar (admin/secretaria)
- **Aluno só precisa visualizar**

**`admin/api/financeiro-resumo-aluno.php`:**
- Resumo financeiro por aluno
- Total em aberto, Total vencido, Próximo vencimento
- **Pode ser reaproveitado para aluno**

### 4.2. O que já é usado em alguma tela admin

**Resumo Financeiro:**
```php
// Total em aberto
SELECT SUM(valor_total) as total_aberto
FROM financeiro_faturas
WHERE aluno_id = ? AND status = 'aberta'

// Total vencido
SELECT SUM(valor_total) as total_vencido
FROM financeiro_faturas
WHERE aluno_id = ? AND status = 'vencida'

// Próximo vencimento
SELECT MIN(data_vencimento) as proximo_vencimento
FROM financeiro_faturas
WHERE aluno_id = ? AND status IN ('aberta', 'vencida')
```

### 4.3. Checklist de Reaproveitamento

✅ **Tabelas:** `financeiro_faturas` e `pagamentos` já existem  
✅ **Queries:** Queries de resumo podem ser reaproveitadas  
⚠️ **API:** `admin/api/financeiro-faturas.php` precisa adaptar para aluno  
✅ **Layout:** Adaptar `admin/pages/financeiro-faturas.php` (apenas visualização)  
❌ **API Resumo:** Criar endpoint específico ou fazer query direta na página  

---

## 5. PENDÊNCIA 4: CONTATO (aluno/contato.php)

### 5.1. Como é o fluxo de contato do instrutor

#### 5.1.1. Tabela de Banco de Dados

**`contatos_instrutor`:**
- Campos: `id`, `instrutor_id`, `usuario_id`, `assunto`, `mensagem`, `aula_id`, `status`, `resposta`, `respondido_por`, `respondido_em`, `criado_em`
- Status: `aberto`, `em_atendimento`, `respondido`, `fechado`
- Relacionamentos: `instrutor_id` → `instrutores.id`, `usuario_id` → `usuarios.id`, `aula_id` → `aulas.id`

**Script de Migração:**
- `docs/scripts/migration_contatos_instrutor.sql` - Estrutura completa

#### 5.1.2. Página do Instrutor

**`instrutor/contato.php` - ESTRUTURA COMPLETA:**
- Informações de contato do CFC (fixas)
  - WhatsApp, E-mail, Telefone, Horário, Endereço
- Formulário de mensagem
  - Assunto (mín. 5 caracteres)
  - Aula relacionada (opcional, select de aulas recentes)
  - Mensagem (mín. 10 caracteres)
- Processamento POST
  - Validações
  - Inserção em `contatos_instrutor`
  - Redirecionamento com mensagem de sucesso

**Query de Aulas para Select:**
```php
SELECT a.id, a.data_aula, a.hora_inicio, al.nome as aluno_nome
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
WHERE a.instrutor_id = ?
  AND a.data_aula >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND a.status != 'cancelada'
ORDER BY a.data_aula DESC, a.hora_inicio DESC
LIMIT 30
```

### 5.2. Se já existe algo similar para aluno

**❌ NÃO EXISTE:**
- Não há tabela `contatos_aluno`
- Não há página de contato para aluno
- Não há API de contatos para aluno

### 5.3. O que precisamos criar

#### 5.3.1. Tabela de Banco de Dados

**Criar `contatos_aluno` (similar a `contatos_instrutor`):**
```sql
CREATE TABLE IF NOT EXISTS contatos_aluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL COMMENT 'ID do aluno (tabela alunos)',
    usuario_id INT NOT NULL COMMENT 'ID do usuário que enviou (tabela usuarios)',
    assunto VARCHAR(255) NOT NULL COMMENT 'Assunto da mensagem',
    mensagem TEXT NOT NULL COMMENT 'Conteúdo da mensagem',
    aula_id INT NULL COMMENT 'ID da aula relacionada (opcional)',
    turma_id INT NULL COMMENT 'ID da turma teórica relacionada (opcional)',
    status ENUM('aberto', 'em_atendimento', 'respondido', 'fechado') DEFAULT 'aberto',
    resposta TEXT NULL COMMENT 'Resposta da secretaria/admin',
    respondido_por INT NULL COMMENT 'ID do usuário que respondeu',
    respondido_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_aluno (aluno_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_aula (aula_id),
    INDEX idx_turma (turma_id),
    INDEX idx_status (status),
    INDEX idx_criado (criado_em),
    
    -- Foreign Keys
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE SET NULL,
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mensagens de contato enviadas por alunos para a secretaria';
```

#### 5.3.2. API (Opcional)

**Criar `admin/api/contatos-aluno.php` (similar a contatos do instrutor):**
- POST: Registrar mensagem
- GET: Listar mensagens do aluno (opcional, para histórico)
- Sempre usar `getCurrentAlunoId()` para validar

**Ou processar diretamente na página:**
- Mais simples, como em `instrutor/contato.php`
- Validações e inserção direta na página

#### 5.3.3. Página

**Criar `aluno/contato.php` (copiar `instrutor/contato.php`):**
- Mesma estrutura de informações de contato
- Formulário similar
- Ajustes:
  - Trocar `instrutor_id` por `aluno_id`
  - Select de aulas: filtrar por `aluno_id` (ao invés de `instrutor_id`)
  - Select de turmas teóricas (opcional): filtrar por `aluno_id` via `turma_matriculas`

### 5.4. Checklist de Reaproveitamento

✅ **Estrutura:** Copiar `instrutor/contato.php`  
✅ **Informações de Contato:** Reaproveitar array fixo  
✅ **Validações:** Reaproveitar validações do formulário  
❌ **Tabela:** Criar `contatos_aluno` (script SQL)  
❌ **Queries:** Adaptar queries de aulas/turmas para aluno  
⚠️ **API:** Opcional (pode processar direto na página)  

---

## 6. CHECKLIST DE REAPROVEITAMENTO

### 6.1. O que conseguimos reaproveitar direto

✅ **APIs:**
- `admin/api/notificacoes.php` - Já suporta aluno (GET, POST, PUT)
- `admin/api/turma-frequencia.php` - Já suporta aluno
- `admin/api/turma-presencas.php` - Já suporta aluno

✅ **Queries SQL:**
- Queries de aulas práticas (de `aluno/dashboard.php` e `instrutor/aulas.php`)
- Queries de aulas teóricas (de `aluno/presencas-teoricas.php`)
- Queries de notificações (de `instrutor/notificacoes.php`)
- Queries de faturas (de `admin/api/financeiro-faturas.php`)

✅ **Layouts:**
- Estrutura de `instrutor/aulas.php` (para aluno/aulas.php)
- Estrutura de `instrutor/notificacoes.php` (para aluno/notificacoes.php)
- Estrutura de `instrutor/contato.php` (para aluno/contato.php)
- Estrutura de `admin/pages/financeiro-faturas.php` (para aluno/financeiro.php)

✅ **Funções:**
- `getCurrentAlunoId()` - Já implementada
- `SistemaNotificacoes` - Já suporta aluno

### 6.2. Onde vamos precisar criar endpoints específicos de forma mínima

⚠️ **APIs a Adaptar/Criar:**

1. **`admin/api/financeiro-faturas.php`** - ADAPTAR
   - Adicionar suporte para `tipo_usuario = 'aluno'`
   - Forçar `aluno_id = getCurrentAlunoId()` quando for aluno
   - Bloquear acesso a faturas de outros alunos

2. **`admin/api/aluno-aulas.php`** - OPCIONAL
   - Criar apenas se quiser separar lógica
   - Ou fazer queries diretas na página (mais simples)

3. **`admin/api/contatos-aluno.php`** - OPCIONAL
   - Criar apenas se quiser separar lógica
   - Ou processar direto na página (como instrutor faz)

### 6.3. Tabelas a Criar

❌ **Novas Tabelas:**
- `contatos_aluno` - Criar script SQL em `docs/scripts/migration_contatos_aluno.sql`

### 6.4. Resumo de Esforço

| Pendência | Reaproveitamento | Esforço | Prioridade |
|-----------|------------------|---------|------------|
| **Aulas** | 90% | Baixo | Alta |
| **Notificações** | 95% | Muito Baixo | Alta |
| **Financeiro** | 80% | Médio | Alta |
| **Contato** | 85% | Baixo | Alta |

---

## 7. PRÓXIMOS PASSOS

1. ✅ **FASE 0 CONCLUÍDA** - Raio-X completo
2. ⏭️ **FASE 1** - Implementar `aluno/aulas.php`
3. ⏭️ **FASE 2** - Implementar `aluno/notificacoes.php`
4. ⏭️ **FASE 3** - Implementar `aluno/financeiro.php`
5. ⏭️ **FASE 4** - Implementar `aluno/contato.php`
6. ⏭️ **FASE 5** - Atualizar botões no `aluno/dashboard.php`
7. ⏭️ **FASE 6** - Testes e documentação final

---

**Fim do Raio-X**

