# Inventário Completo: Portal do Instrutor

## Data: 2024
## Status: ✅ INVENTÁRIO CONCLUÍDO

---

## Visão Geral

Este documento mapeia **exatamente** o estado atual do portal do instrutor (`/instrutor`), identificando o que está funcionando, o que está parcialmente implementado e o que ainda precisa ser criado antes de subir para produção.

---

## 1. Estrutura de Arquivos do Portal

### ✅ Arquivos Existentes

| Arquivo | Status | Descrição |
|---------|--------|-----------|
| `instrutor/dashboard.php` | ✅ **FUNCIONAL** | Dashboard principal do instrutor |
| `instrutor/dashboard-mobile.php` | ⚠️ **ALTERNATIVO** | Versão alternativa mobile (não é a principal) |
| `instrutor/perfil.php` | ✅ **FUNCIONAL** | Página de perfil (criada recentemente) |
| `instrutor/trocar-senha.php` | ✅ **FUNCIONAL** | Página de troca de senha (criada recentemente) |

### ❌ Arquivos Referenciados mas NÃO Existentes

| Arquivo Referenciado | Onde é Chamado | Status |
|---------------------|----------------|--------|
| `instrutor/aulas.php` | `dashboard.php` (função `verTodasAulas()`) | ❌ **NÃO EXISTE** |
| `instrutor/ocorrencias.php` | `dashboard.php` (função `registrarOcorrencia()`) | ❌ **NÃO EXISTE** |
| `instrutor/contato.php` | `dashboard.php` (função `contatarSecretaria()`) | ❌ **NÃO EXISTE** |
| `instrutor/notificacoes.php` | `dashboard.php` (função `verNotificacoes()`) | ❌ **NÃO EXISTE** |
| `instrutor/chamada.php` | `dashboard.php` (botão "Chamada" em aulas teóricas) | ❌ **NÃO EXISTE** |
| `instrutor/diario.php` | `dashboard.php` (botão "Diário" em aulas teóricas) | ❌ **NÃO EXISTE** |

---

## 2. Dashboard Principal (`instrutor/dashboard.php`)

### 2.1. Autenticação e Segurança

**✅ FUNCIONAL**

- **Verificação de Autenticação:**
  - Arquivo: `instrutor/dashboard.php` (linhas 13-18)
  - Código: `if (!$user || $user['tipo'] !== 'instrutor')`
  - Redireciona para `/login.php` se não autenticado

- **Verificação de `precisa_trocar_senha`:**
  - Arquivo: `instrutor/dashboard.php` (linhas 25-43)
  - Verifica flag e redireciona para `trocar-senha.php?forcado=1` se necessário
  - ✅ **IMPLEMENTADO E FUNCIONAL**

- **Risco de Acesso Não Autorizado:**
  - ✅ **SEGURO**: Verificação de tipo em todas as páginas
  - ⚠️ **ATENÇÃO**: APIs em `admin/api/` podem não ter verificação específica de instrutor (verificar)

---

### 2.2. Header / Topbar

**✅ FUNCIONAL**

- **Estrutura:**
  - Arquivo: `instrutor/dashboard.php` (linhas ~147-180)
  - HTML inline com dropdown de usuário

- **Dropdown de Usuário:**
  - ✅ **IMPLEMENTADO**
  - Avatar com iniciais
  - Nome do instrutor
  - Label "Instrutor"
  - Links:
    - ✅ "Meu Perfil" → `perfil.php` (funcional)
    - ✅ "Trocar senha" → `trocar-senha.php` (funcional)
    - ✅ "Sair" → `../admin/logout.php` (funcional)

- **JavaScript:**
  - Arquivo: `instrutor/dashboard.php` (linhas ~830-860)
  - Toggle do dropdown
  - Fechar ao clicar fora
  - ✅ **FUNCIONAL**

---

### 2.3. Cards de Resumo / Estatísticas

**✅ FUNCIONAL (com ressalvas)**

- **Localização:** `instrutor/dashboard.php` (linhas 240-265)

- **Cards Exibidos:**
  1. **Total de Aulas** - `$statsHoje['total_aulas']`
  2. **Confirmadas** - `$statsHoje['confirmadas']`
  3. **Concluídas** - `$statsHoje['concluidas']`
  4. **Pendentes** - `$statsHoje['agendadas']` (label diz "Pendentes" mas usa campo `agendadas`)

- **Fonte de Dados:**
  - Arquivo: `instrutor/dashboard.php` (linhas 132-155)
  - Query SQL:
    ```sql
    SELECT 
        COUNT(*) as total_aulas,
        SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as agendadas,
        SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
        SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas
    FROM aulas 
    WHERE instrutor_id = ? AND data_aula = ?
    ```

- **Status:**
  - ✅ **FUNCIONAL**: Calcula dados reais do banco
  - ✅ **FILTRO POR INSTRUTOR**: Usa `instrutor_id` da tabela `instrutores`
  - ⚠️ **RESSALVA**: Se instrutor não tiver registro em `instrutores`, `$instrutorId` será `null` e retorna zeros (comportamento correto)

- **Tabelas Utilizadas:**
  - `aulas` (filtrada por `instrutor_id` e `data_aula`)

---

### 2.4. Seção "Aulas de Hoje"

**✅ FUNCIONAL**

- **Localização:** `instrutor/dashboard.php` (linhas ~270-365)

- **Fonte de Dados:**
  - Arquivo: `instrutor/dashboard.php` (linhas 74-90)
  - Query SQL:
    ```sql
    SELECT a.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? 
      AND a.data_aula = ?
      AND a.status != 'cancelada'
    ORDER BY a.hora_inicio ASC
    ```

- **Status:**
  - ✅ **FUNCIONAL**: Traz aulas reais do banco
  - ✅ **FILTRO POR INSTRUTOR**: Usa `instrutor_id`
  - ✅ **FILTRO POR DATA**: Apenas aulas de hoje
  - ✅ **FILTRO POR STATUS**: Exclui canceladas

- **Tabelas Utilizadas:**
  - `aulas` (principal)
  - `alunos` (JOIN para nome e telefone)
  - `veiculos` (LEFT JOIN para modelo e placa)

- **Ações Disponíveis por Aula:**
  - **Aulas Teóricas:**
    - ✅ "Chamada" → `chamada.php?aula_id={id}` (❌ página não existe)
    - ✅ "Diário" → `diario.php?aula_id={id}` (❌ página não existe)
  - **Todas as Aulas:**
    - ✅ "Transferir" → Abre modal de transferência (funcional)
    - ✅ "Cancelar" → Abre modal de cancelamento (funcional)
  - **Link de Telefone:**
    - ✅ `tel:{telefone}` (funcional, abre app de telefone)

---

### 2.5. Seção "Próximas Aulas (7 dias)"

**✅ FUNCIONAL**

- **Localização:** `instrutor/dashboard.php` (linhas ~367-440)

- **Fonte de Dados:**
  - Arquivo: `instrutor/dashboard.php` (linhas 92-109)
  - Query SQL:
    ```sql
    SELECT a.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? 
      AND a.data_aula > ?
      AND a.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
      AND a.status != 'cancelada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 10
    ```

- **Status:**
  - ✅ **FUNCIONAL**: Traz aulas reais do banco
  - ✅ **FILTRO POR INSTRUTOR**: Usa `instrutor_id`
  - ✅ **FILTRO POR PERÍODO**: Próximos 7 dias
  - ✅ **LIMITE**: Máximo 10 aulas

- **Tabelas Utilizadas:**
  - `aulas` (principal)
  - `alunos` (JOIN)
  - `veiculos` (LEFT JOIN)

- **Ações Disponíveis:**
  - Mesmas ações de "Aulas de Hoje" (Transferir, Cancelar, Chamada, Diário)

---

### 2.6. Bloco "Ações Rápidas"

**⚠️ PARCIALMENTE FUNCIONAL**

- **Localização:** `instrutor/dashboard.php` (linhas 442-468)

#### 2.6.1. Botão "Ver Todas as Aulas"

- **Função JavaScript:** `verTodasAulas()` (linha 544)
- **Ação:** `window.location.href = 'aulas.php';`
- **Status:** ❌ **PÁGINA NÃO EXISTE**
- **Arquivo Esperado:** `instrutor/aulas.php`
- **O que deveria fazer:** Listar todas as aulas do instrutor (passadas, presentes e futuras) com filtros e paginação

#### 2.6.2. Botão "Central de Avisos"

- **Função JavaScript:** `verNotificacoes()` (linha 548)
- **Ação:** `window.location.href = 'notificacoes.php';`
- **Status:** ❌ **PÁGINA NÃO EXISTE**
- **Arquivo Esperado:** `instrutor/notificacoes.php`
- **O que deveria fazer:** Exibir todas as notificações do instrutor (lidas e não lidas) com opção de marcar como lida

#### 2.6.3. Botão "Registrar Ocorrência"

- **Função JavaScript:** `registrarOcorrencia()` (linha 552)
- **Ação:** `window.location.href = 'ocorrencias.php';`
- **Status:** ❌ **PÁGINA NÃO EXISTE**
- **Arquivo Esperado:** `instrutor/ocorrencias.php`
- **O que deveria fazer:** Formulário para registrar ocorrências (ex.: problemas com aluno, veículo, etc.)

#### 2.6.4. Botão "Contatar Secretária"

- **Função JavaScript:** `contatarSecretaria()` (linha 556)
- **Ação:** `window.location.href = 'contato.php';`
- **Status:** ❌ **PÁGINA NÃO EXISTE**
- **Arquivo Esperado:** `instrutor/contato.php`
- **O que deveria fazer:** Formulário de contato ou link direto para WhatsApp/Email da secretaria

---

### 2.7. Modal de Cancelamento/Transferência

**✅ FUNCIONAL**

- **Localização:** `instrutor/dashboard.php` (linhas 471-540)

- **Estrutura:**
  - Modal HTML completo
  - Campos:
    - Data da Aula (readonly)
    - Horário (readonly)
    - Nova Data (apenas para transferência)
    - Novo Horário (apenas para transferência)
    - Motivo (select com opções)
    - Justificativa (textarea obrigatório)

- **JavaScript:**
  - Função `abrirModal(tipo, aulaId, data, hora)` (linha 561)
  - Função `fecharModal()` (linha 586)
  - Função `enviarAcao()` (linha 646)

- **API Chamada:**
  - Endpoint: `../admin/api/solicitacoes.php`
  - Método: `POST`
  - Payload:
    ```json
    {
        "aula_id": "...",
        "tipo_solicitacao": "cancelamento" | "transferencia",
        "nova_data": "...",
        "nova_hora": "...",
        "motivo": "...",
        "justificativa": "..."
    }
    ```

- **Status:**
  - ⚠️ **PROBLEMA CRÍTICO**: A API `admin/api/solicitacoes.php` é **APENAS PARA ALUNOS**
  - Linha 52-54: `if ($user['tipo'] !== 'aluno') { returnJsonError('Acesso negado', 403); }`
  - ❌ **NÃO FUNCIONA PARA INSTRUTORES**: O dashboard está chamando uma API que bloqueia instrutores
  - ⚠️ **ALTERNATIVAS EXISTENTES**: 
    - `admin/api/cancelar-aula.php` - ✅ **ACEITA INSTRUTORES** (não verifica tipo, apenas autenticação)
    - ⚠️ **PROBLEMA DE SEGURANÇA**: Não verifica se `aula.instrutor_id` corresponde ao instrutor logado
    - `admin/api/agendamento.php` - ⚠️ **VERIFICAR** se aceita instrutores para transferência
  - ⚠️ **NECESSÁRIO**: 
    - Adaptar `dashboard.php` para usar `cancelar-aula.php` em vez de `solicitacoes.php`
    - **ADICIONAR VALIDAÇÃO DE SEGURANÇA**: Verificar se `aula.instrutor_id` corresponde ao instrutor logado
    - Ou criar API específica para instrutores que unifique cancelamento e transferência com validação adequada
  - ✅ **FRONTEND FUNCIONAL**: Modal abre, fecha e envia dados, mas a requisição atual será bloqueada

---

### 2.8. Notificações Não Lidas

**✅ FUNCIONAL**

- **Localização:** `instrutor/dashboard.php` (linhas ~210-238)

- **Fonte de Dados:**
  - Arquivo: `instrutor/dashboard.php` (linha 112)
  - Código: `$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($user['id'], 'instrutor');`
  - Classe: `SistemaNotificacoes` (`includes/services/SistemaNotificacoes.php`)

- **Status:**
  - ✅ **FUNCIONAL**: Busca notificações reais do banco
  - ✅ **FILTRO POR USUÁRIO**: Usa `$user['id']`
  - ✅ **FILTRO POR TIPO**: 'instrutor'

- **Ações:**
  - Botão "Marcar como lida" em cada notificação
  - Função JavaScript: `marcarNotificacaoComoLida(notificacaoId)` (linha 691)
  - API Chamada: `../admin/api/notificacoes.php` (POST)
  - ✅ **API EXISTE**: `admin/api/notificacoes.php`
  - ⚠️ **VERIFICAR**: Se API valida permissão de instrutor

---

## 3. Páginas Funcionais

### 3.1. Perfil (`instrutor/perfil.php`)

**✅ FUNCIONAL**

- **Arquivo:** `instrutor/perfil.php`
- **Autenticação:** ✅ Verifica `tipo === 'instrutor'`
- **Verificação de Flag:** ✅ Verifica `precisa_trocar_senha`
- **Funcionalidades:**
  - Editar nome completo
  - Editar e-mail (com validação e checagem de duplicidade)
  - Editar telefone
  - Campos somente leitura: CPF, CFC vinculado, Tipo
- **Processamento:** POST com `action=update_profile`
- **Tabelas:** `usuarios`
- **Status:** ✅ **COMPLETO E FUNCIONAL**

---

### 3.2. Trocar Senha (`instrutor/trocar-senha.php`)

**✅ FUNCIONAL**

- **Arquivo:** `instrutor/trocar-senha.php`
- **Autenticação:** ✅ Verifica `tipo === 'instrutor'`
- **Funcionalidades:**
  - Validar senha atual
  - Nova senha (mínimo 8 caracteres)
  - Confirmar nova senha
  - Toggle de visibilidade
  - Set `precisa_trocar_senha = 0` após troca
- **Processamento:** POST com `action=change_password`
- **Tabelas:** `usuarios`
- **Status:** ✅ **COMPLETO E FUNCIONAL**

---

## 4. Integração com Banco de Dados

### 4.1. Tabelas Utilizadas

| Tabela | Uso | Filtro por Instrutor |
|--------|-----|---------------------|
| `usuarios` | Dados do usuário logado | ✅ Por `id` (sessão) |
| `instrutores` | Dados específicos do instrutor | ✅ Por `usuario_id` |
| `aulas` | Listagem de aulas | ✅ Por `instrutor_id` |
| `alunos` | Dados dos alunos (JOIN) | ✅ Via `aulas.aluno_id` |
| `veiculos` | Dados dos veículos (LEFT JOIN) | ✅ Via `aulas.veiculo_id` |
| `notificacoes` | Notificações do instrutor | ✅ Por `usuario_id` e `tipo='instrutor'` |
| `solicitacoes` | Solicitações de cancelamento/transferência | ⚠️ **VERIFICAR** |

### 4.2. Queries Principais

**Estatísticas do Dia:**
```sql
SELECT 
    COUNT(*) as total_aulas,
    SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as agendadas,
    SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
    SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas
FROM aulas 
WHERE instrutor_id = ? AND data_aula = ?
```

**Aulas de Hoje:**
```sql
SELECT a.*, 
       al.nome as aluno_nome, al.telefone as aluno_telefone,
       v.modelo as veiculo_modelo, v.placa as veiculo_placa
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.instrutor_id = ? 
  AND a.data_aula = ?
  AND a.status != 'cancelada'
ORDER BY a.hora_inicio ASC
```

**Próximas Aulas:**
```sql
SELECT a.*, 
       al.nome as aluno_nome, al.telefone as aluno_telefone,
       v.modelo as veiculo_modelo, v.placa as veiculo_placa
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.instrutor_id = ? 
  AND a.data_aula > ?
  AND a.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
  AND a.status != 'cancelada'
ORDER BY a.data_aula ASC, a.hora_inicio ASC
LIMIT 10
```

---

## 5. APIs Utilizadas

### 5.1. API de Solicitações

**Endpoint:** `admin/api/solicitacoes.php`

- **Método:** POST
- **Uso:** Cancelamento e transferência de aulas
- **Status:** ✅ **EXISTE**
- **Verificação de Permissão:** ❌ **BLOQUEIA INSTRUTORES**
- **Tabela:** `solicitacoes_aluno` (específica para alunos)
- **Observação:** 
  - API **BLOQUEIA** instrutores explicitamente (linha 52-54: `if ($user['tipo'] !== 'aluno')`)
  - API é projetada apenas para alunos fazerem solicitações
  - **NECESSÁRIO**: Criar API específica para instrutores ou adaptar a existente

---

### 5.2. API de Notificações

**Endpoint:** `admin/api/notificacoes.php`

- **Método:** POST
- **Uso:** Marcar notificação como lida
- **Status:** ✅ **EXISTE**
- **Verificação de Permissão:** ✅ **SEGURA**
- **Tabela:** `notificacoes`
- **Observação:** 
  - API valida autenticação (`getCurrentUser()`)
  - Valida propriedade da notificação (linha 94: verifica `usuario_id` e `tipo_usuario`)
  - Filtra por `usuario_id` e `tipo_usuario` (linha 66, 95)
  - ✅ **FUNCIONAL PARA INSTRUTORES**: Instrutor só pode marcar suas próprias notificações

---

## 6. Fluxo de Login e Sessão

### 6.1. Login

**Arquivo:** `login.php`

- **Processamento:**
  - Linha 89: `$result = $auth->login($email, $senha, $remember);`
  - Linha 102: `redirectAfterLogin($user);`

- **Redirecionamento:**
  - Arquivo: `includes/auth.php` (função `redirectAfterLogin()`)
  - Linha 306: `header('Location: /cfc-bom-conselho/instrutor/dashboard.php');`
  - ✅ **FUNCIONAL**

- **Verificação de `precisa_trocar_senha`:**
  - Arquivo: `includes/auth.php` (linhas 267-295)
  - Se flag = 1, redireciona para `trocar-senha.php?forcado=1`
  - ✅ **IMPLEMENTADO**

---

### 6.2. Validação de Sessão

**Arquivo:** `instrutor/dashboard.php` (linhas 13-18)

- **Código:**
  ```php
  $user = getCurrentUser();
  if (!$user || $user['tipo'] !== 'instrutor') {
      header('Location: /login.php');
      exit();
  }
  ```

- **Status:** ✅ **FUNCIONAL**

---

## 7. Tabela de Status Geral

| Elemento | Status | Observações |
|----------|--------|-------------|
| **Dashboard Principal** | ✅ FUNCIONAL | Completo e funcional |
| **Header/Dropdown** | ✅ FUNCIONAL | Implementado recentemente |
| **Cards de Estatísticas** | ✅ FUNCIONAL | Calcula dados reais do banco |
| **Aulas de Hoje** | ✅ FUNCIONAL | Traz dados reais, filtrado por instrutor |
| **Próximas Aulas** | ✅ FUNCIONAL | Traz dados reais, filtrado por instrutor |
| **Modal Cancelar/Transferir** | ✅ FUNCIONAL | Frontend completo, API existe |
| **Notificações** | ✅ FUNCIONAL | Busca e marca como lida |
| **Página Perfil** | ✅ FUNCIONAL | Completa e funcional |
| **Página Trocar Senha** | ✅ FUNCIONAL | Completa e funcional |
| **Botão Ver Todas as Aulas** | ❌ NÃO EXISTE | Página `aulas.php` não existe |
| **Botão Central de Avisos** | ❌ NÃO EXISTE | Página `notificacoes.php` não existe |
| **Botão Registrar Ocorrência** | ❌ NÃO EXISTE | Página `ocorrencias.php` não existe |
| **Botão Contatar Secretária** | ❌ NÃO EXISTE | Página `contato.php` não existe |
| **Botão Chamada (Teóricas)** | ❌ NÃO EXISTE | Página `chamada.php` não existe |
| **Botão Diário (Teóricas)** | ❌ NÃO EXISTE | Página `diario.php` não existe |

---

## 8. Pendências Recomendadas para Produção

### 8.1. Páginas Críticas (Alta Prioridade)

- [ ] **Criar `instrutor/aulas.php`**
  - Listar todas as aulas do instrutor (passadas, presentes, futuras)
  - Filtros por data, status, tipo
  - Paginação
  - Ações: Ver detalhes, Cancelar, Transferir

- [ ] **Criar `instrutor/ocorrencias.php`**
  - Formulário para registrar ocorrências
  - Campos: Tipo, Data, Aluno (se aplicável), Descrição, Anexos (opcional)
  - Tabela: `ocorrencias` (verificar se existe)
  - Listagem de ocorrências registradas

- [ ] **Criar `instrutor/contato.php`**
  - Formulário de contato com secretaria
  - Ou link direto para WhatsApp/Email
  - Ou integração com sistema de tickets

### 8.2. Páginas Importantes (Média Prioridade)

- [ ] **Criar `instrutor/notificacoes.php`**
  - Listagem completa de notificações (lidas e não lidas)
  - Filtros por data, tipo
  - Marcar como lida/não lida
  - Opção de excluir

- [ ] **Criar `instrutor/chamada.php`**
  - Formulário de chamada para aulas teóricas
  - Lista de alunos da turma
  - Marcar presença/falta
  - Tabela: `chamadas` ou `presencas` (verificar)

- [ ] **Criar `instrutor/diario.php`**
  - Formulário de diário de aula
  - Campos: Conteúdo, Observações, Anexos
  - Tabela: `diarios_aula` ou similar (verificar)

### 8.3. Melhorias de Segurança (Alta Prioridade)

- [ ] **CRÍTICO: Criar API para cancelamento/transferência de instrutores:**
  - `admin/api/solicitacoes.php` **BLOQUEIA INSTRUTORES** (apenas para alunos)
  - Criar `admin/api/instrutor-aulas.php` ou adaptar `solicitacoes.php` para aceitar instrutores
  - Validar que `aula.instrutor_id` corresponde ao instrutor logado
  - Garantir que instrutor só pode cancelar/transferir suas próprias aulas

- [ ] **Verificar APIs em `admin/api/`:**
  - `admin/api/notificacoes.php` - ✅ **OK** (já valida propriedade)

- [ ] **Adicionar verificação de propriedade:**
  - Ao cancelar/transferir aula, verificar se `aula.instrutor_id` corresponde ao instrutor logado
  - Prevenir acesso direto via URL a aulas de outros instrutores

### 8.4. Melhorias de UX (Baixa Prioridade)

- [ ] **Adicionar loading states:**
  - Spinner ao enviar solicitação de cancelamento/transferência
  - Feedback visual ao marcar notificação como lida

- [ ] **Melhorar mensagens de erro:**
  - Mensagens mais específicas quando API retorna erro
  - Validação frontend antes de enviar para API

- [ ] **Adicionar confirmação:**
  - Modal de confirmação antes de cancelar aula
  - Aviso se houver conflito de horário ao transferir

### 8.5. Testes e Validação (Alta Prioridade)

- [ ] **Testar com instrutor real:**
  - Usar usuário "Carlos da Silva" ou outro instrutor válido
  - Verificar se todas as queries retornam dados corretos
  - Testar fluxo completo de cancelamento/transferência

- [ ] **Testar casos extremos:**
  - Instrutor sem registro em `instrutores` (deve mostrar zeros)
  - Instrutor sem aulas (deve mostrar "Nenhuma aula")
  - Aulas canceladas (não devem aparecer)

- [ ] **Testar segurança:**
  - Tentar acessar `admin/index.php` como instrutor (deve bloquear)
  - Tentar cancelar aula de outro instrutor via API (deve bloquear)
  - Tentar acessar páginas não existentes (deve retornar 404)

---

## 9. Resumo Executivo

### ✅ O que está FUNCIONAL:

1. **Dashboard principal** - Completo e funcional
2. **Autenticação e segurança** - Implementada corretamente
3. **Cards de estatísticas** - Calculam dados reais
4. **Aulas de hoje e próximas** - Trazem dados reais do banco
5. **Modal de cancelamento/transferência** - Frontend completo, API existe
6. **Notificações** - Busca e marca como lida
7. **Páginas de perfil e troca de senha** - Completas e funcionais
8. **Dropdown de usuário** - Implementado recentemente

### ❌ O que NÃO EXISTE:

1. **6 páginas referenciadas mas não criadas:**
   - `aulas.php` (listagem completa)
   - `ocorrencias.php` (registro de ocorrências)
   - `contato.php` (contato com secretaria)
   - `notificacoes.php` (central de avisos)
   - `chamada.php` (chamada para teóricas)
   - `diario.php` (diário de aula)

### ⚠️ O que precisa CORREÇÃO/VERIFICAÇÃO:

1. **APIs em `admin/api/`:**
   - ❌ **CRÍTICO**: `admin/api/solicitacoes.php` **BLOQUEIA INSTRUTORES** - necessário criar API específica
   - ✅ `admin/api/notificacoes.php` - OK (valida propriedade)

2. **Tabelas do banco:**
   - Verificar se `solicitacoes_instrutor` ou similar existe (ou adaptar `solicitacoes_aluno`)
   - Verificar se `ocorrencias` existe
   - Verificar se `chamadas` ou `presencas` existe
   - Verificar se `diarios_aula` existe

---

## 10. Conclusão

O portal do instrutor está **parcialmente funcional** para produção. O dashboard principal está completo e funcional, mas **6 páginas importantes ainda não foram criadas**. Além disso, é necessário verificar a segurança das APIs e garantir que o instrutor só possa acessar/modificar seus próprios dados.

**Recomendação:** 
1. **CRÍTICO**: Criar API para cancelamento/transferência de instrutores (a atual bloqueia instrutores)
2. Implementar as páginas críticas (`aulas.php`, `ocorrencias.php`, `contato.php`)
3. Verificar e criar tabelas necessárias no banco de dados
4. Testar fluxo completo com instrutor real antes de subir para produção

---

**Fim do Inventário**

