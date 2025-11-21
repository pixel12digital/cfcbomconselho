# Implementação: Regra Global de Bloqueio pelo Financeiro - Exames

## Resumo

Implementação da regra global de bloqueio pelo financeiro para exames médico e psicotécnico, garantindo que nenhum avanço operacional aconteça se o financeiro do aluno não estiver OK.

## Arquivos Criados/Modificados

### 1. Novo Arquivo: `admin/includes/FinanceiroAlunoHelper.php`

**Função:** Helper centralizado para verificação de permissão financeira do aluno.

**Método Principal:**
```php
FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno(int $alunoId): array
```

**Retorno:**
```php
[
    'liberado' => bool,      // true se pode avançar, false se bloqueado
    'status' => string,      // 'EM_DIA', 'EM_ATRASO', 'NAO_LANCADO', etc.
    'motivo' => string       // mensagem amigável pronta para exibir
]
```

**Critérios de Bloqueio:**
1. Se não houver matrícula ativa → BLOQUEIA
2. Se não houver nenhuma fatura lançada → BLOQUEIA (NAO_LANCADO)
3. Se existir qualquer fatura em atraso → BLOQUEIA (EM_ATRASO)
4. Somente libera quando status for 'em_dia' (todas as faturas pagas)

**Lógica:**
- Reutiliza a mesma lógica do `FinanceiroService::calcularResumoFinanceiroAluno()`
- Verifica matrícula ativa antes de verificar faturas
- Calcula total contratado, total pago, saldo aberto
- Identifica faturas vencidas (status 'aberta' ou 'parcial' com data_vencimento < hoje)
- Retorna status padronizado e mensagem amigável

### 2. Modificado: `admin/api/exames_simple.php`

**Alteração:** Adicionada verificação de bloqueio financeiro antes de inserir exame.

**Localização:** Linha ~131 (após validação de aluno_id)

**Código Adicionado:**
```php
// Verificação de bloqueio financeiro
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';
$verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);

if (!$verificacaoFinanceira['liberado']) {
    returnJson([
        'success' => false,
        'error' => $verificacaoFinanceira['motivo'],
        'codigo' => 'BLOQUEIO_FINANCEIRO',
        'status_financeiro' => $verificacaoFinanceira['status'],
        'http_status' => 403
    ]);
}
```

**Comportamento:**
- Bloqueia criação de exame se financeiro não estiver OK
- Retorna erro 403 (Forbidden) com mensagem amigável
- Aplica tanto para agendamento pelo histórico quanto pela tela geral

### 3. Modificado: `admin/pages/historico-aluno.php`

**Alterações:**

#### 3.1. Verificação no Backend (PHP)
- Linha ~180: Adicionada verificação financeira usando `FinanceiroAlunoHelper`
- Resultado armazenado em `$verificacaoFinanceiraExames`

#### 3.2. Atributos nos Botões "Agendar Exame"
- Linhas ~1090-1093 (Exame Médico) e ~1176-1179 (Exame Psicotécnico)
- Adicionados atributos:
  - `data-bloqueado="1"` ou `data-bloqueado="0"`
  - `data-motivo="..."` com mensagem de bloqueio
  - Classe `btn-disabled` e estilo visual quando bloqueado

#### 3.3. JavaScript para Interceptar Cliques
- Linha ~2105: Adicionado listener `DOMContentLoaded`
- Intercepta cliques em botões com `data-bloqueado="1"`
- Exibe `alert()` com motivo do bloqueio
- Previne navegação (`preventDefault()`)

**Comportamento:**
- Botões bloqueados aparecem visualmente desabilitados (opacity: 0.6)
- Clique em botão bloqueado exibe alerta e não navega
- Botões liberados funcionam normalmente

### 4. Modificado: `admin/pages/exames.php`

**Alteração:** Melhorado tratamento de erro para bloqueio financeiro.

**Localização:** Linha ~2418 (função `agendarExame`)

**Código Adicionado:**
```javascript
// Para bloqueio financeiro, destacar visualmente
if (data.codigo === 'BLOQUEIO_FINANCEIRO') {
    console.log('[BLOQUEIO FINANCEIRO] Exame bloqueado - Status: ' + (data.status_financeiro || 'N/A'));
    alert('⚠️ BLOQUEIO FINANCEIRO\n\n' + errorMsg + '\n\nRegularize a situação financeira do aluno para continuar.');
} else {
    alert(mensagem);
}
```

**Comportamento:**
- Quando backend retorna `BLOQUEIO_FINANCEIRO`, exibe alerta destacado
- Mensagem inclui instrução para regularizar situação financeira
- Log no console para debug

## Fluxo Completo

### 1. Histórico do Aluno

1. **Backend (PHP):**
   - Carrega dados do aluno
   - Chama `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)`
   - Armazena resultado em `$verificacaoFinanceiraExames`

2. **Renderização (HTML):**
   - Botões "Agendar Exame" recebem atributos `data-bloqueado` e `data-motivo`
   - Se bloqueado, botão aparece visualmente desabilitado

3. **Frontend (JavaScript):**
   - Listener intercepta cliques em botões bloqueados
   - Exibe alerta e previne navegação

### 2. Tela Geral de Exames

1. **Usuário seleciona aluno e preenche formulário**
2. **Ao clicar "Agendar Exame":**
   - Frontend valida campos obrigatórios
   - Envia POST para `api/exames_simple.php`

3. **Backend (API):**
   - Valida tipo, campos obrigatórios, aluno_id
   - **Chama `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)`**
   - Se bloqueado, retorna erro 403 com mensagem
   - Se liberado, insere exame no banco

4. **Frontend (Resposta):**
   - Se sucesso: fecha modal, redireciona ou recarrega
   - Se bloqueio financeiro: exibe alerta destacado
   - Se outro erro: exibe mensagem padrão

## Logs de Debug

### Backend (PHP)
- `error_log('[BLOQUEIO FINANCEIRO] Aluno {id} - Liberado: {SIM/NÃO} - Status: {status}')`
- `error_log('[HISTORICO ALUNO] Aluno {id} - Verificação Financeira: {json}')`

### Frontend (JavaScript)
- `console.log('[BLOQUEIO FINANCEIRO] Encontrados {n} botões bloqueados')`
- `console.log('[BLOQUEIO FINANCEIRO] Clique bloqueado - Motivo: {motivo}')`
- `console.log('[BLOQUEIO FINANCEIRO] Exame bloqueado - Status: {status}')`

## Testes Esperados

### Teste 1: Aluno sem nenhuma fatura
- **Esperado:** Não deve conseguir agendar exame
- **Mensagem:** "Não é possível avançar: ainda não existem faturas lançadas para este aluno."
- **Status:** `NAO_LANCADO`

### Teste 2: Aluno com fatura em atraso
- **Esperado:** Não deve conseguir agendar exame
- **Mensagem:** "Não é possível avançar: existem faturas em atraso para este aluno."
- **Status:** `EM_ATRASO`

### Teste 3: Aluno com faturas todas pagas / em dia
- **Esperado:** Deve agendar normalmente
- **Status:** `EM_DIA`
- **Comportamento:** Exame é criado no banco, modal fecha, página atualiza

### Teste 4: Aluno sem matrícula ativa
- **Esperado:** Não deve conseguir agendar exame
- **Mensagem:** "Não é possível avançar: aluno não possui matrícula ativa."
- **Status:** `SEM_MATRICULA`

## Garantias

✅ **Backend sempre valida:** Mesmo que frontend não bloqueie, backend recusa criação
✅ **Não quebra funcionalidades existentes:** Redirecionamento com `origem=historico` continua funcionando
✅ **Lançamento de resultado não é afetado:** Bloqueio aplica apenas para NOVOS agendamentos
✅ **Lista de exames não é afetada:** Filtros e visualização continuam funcionando
✅ **Modal não reabre após salvar:** Comportamento corrigido anteriormente é mantido

## Próximos Passos (Futuro)

- Aplicar mesma regra para agendamento de aulas
- Aplicar mesma regra para agendamento de provas
- Criar endpoint API para verificação financeira via AJAX (opcional, para validação em tempo real no select de aluno)

