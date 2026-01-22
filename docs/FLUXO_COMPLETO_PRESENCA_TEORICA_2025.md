# FLUXO COMPLETO - PRESENÇA TEÓRICA

**Data:** 2025-12-12  
**Status:** ✅ Funcionando corretamente  
**Objetivo:** Documentar o fluxo completo de presença teórica, desde a marcação até a exibição no histórico

---

## Visão Geral

O sistema de presença teórica permite que **instrutores** e **administradores/secretaria** marquem presenças dos alunos em aulas teóricas agendadas. As presenças são registradas na tabela `turma_presencas` e exibidas em múltiplas telas do sistema.

---

## Arquitetura do Sistema

### Tabelas Principais

#### 1. `turma_aulas_agendadas`
Armazena as aulas teóricas agendadas para cada turma.

**Campos relevantes:**
- `id` (PK) - ID da aula
- `turma_id` (FK) - ID da turma teórica
- `nome_aula` - Nome da aula (ex: "Legislação de Trânsito - Aula 1")
- `disciplina` - Disciplina (legislacao_transito, direcao_defensiva, etc.)
- `data_aula` - Data da aula
- `hora_inicio`, `hora_fim` - Horários
- `status` - Status da aula (agendada, realizada, cancelada)
- `ordem_global` - Ordem da aula na sequência

#### 2. `turma_presencas`
Armazena as presenças dos alunos nas aulas.

**Campos:**
- `id` (PK) - ID do registro de presença
- `turma_id` (FK) - ID da turma teórica
- `turma_aula_id` (FK) - ID da aula agendada (referencia `turma_aulas_agendadas.id`)
- `aluno_id` (FK) - ID do aluno
- `presente` (BOOLEAN) - 1 = presente, 0 = ausente
- `registrado_por` (FK) - ID do usuário que registrou
- `registrado_em` (TIMESTAMP) - Data/hora do registro

**Índices:**
- UNIQUE KEY: `(turma_aula_id, aluno_id)` - Um aluno só pode ter uma presença por aula

#### 3. `turma_matriculas`
Armazena as matrículas dos alunos nas turmas teóricas.

**Campos relevantes:**
- `id` (PK)
- `turma_id` (FK)
- `aluno_id` (FK)
- `status` - Status da matrícula (matriculado, cursando, concluido, etc.)
- `frequencia_percentual` - Frequência calculada (atualizada automaticamente)

---

## Fluxo de Marcação de Presença

### 1. Acesso à Tela de Chamada

#### Para Instrutor:
```
Menu → Dashboard Instrutor → Aulas → [Selecionar Aula] → Botão "Chamada"
URL: /instrutor/aulas.php?acao=chamada&aula_id={AULA_ID}
```

#### Para Admin/Secretaria:
```
Menu → Acadêmico → Turmas Teóricas → [Selecionar Turma] → "Ver Diário" → "Chamada"
OU
Menu → Acadêmico → Turmas Teóricas → [Selecionar Turma] → "Ver Detalhes" → Tab "Diário / Presenças" → "Chamada"
URL: /admin/index.php?page=turma-chamada&turma_id={TURMA_ID}&aula_id={AULA_ID}
```

### 2. Tela de Chamada (`admin/pages/turma-chamada.php`)

**Funcionalidades:**
- Exibe lista de alunos matriculados na turma
- Permite marcar presença/ausência individual
- Permite marcar todos como presente/ausente
- Exibe frequência individual de cada aluno (chip rosa)
- Exibe estatísticas gerais (cards no topo)

**Componentes principais:**

**Cards de Estatísticas:**
- Total de alunos
- Total de presentes
- Frequência média

**Lista de Alunos:**
- Nome do aluno
- Chip de frequência (badge rosa com percentual)
- Toggle para marcar presença/ausência
- Botão de observação (se necessário)

### 3. Marcação de Presença (JavaScript → API)

**Função JavaScript:** `criarPresenca()` ou `atualizarPresenca()`

**Fluxo:**
1. Usuário clica no toggle de presença
2. JavaScript captura o evento
3. Determina se é criação ou atualização
4. Prepara dados:
   ```javascript
   {
       turma_id: 19,
       turma_aula_id: 227,
       aluno_id: 167,
       presente: true/false,
       origem: 'admin' ou 'instrutor'
   }
   ```
5. Envia requisição POST/PUT para API:
   ```
   POST /admin/api/turma-presencas.php
   ```

### 4. API de Presenças (`admin/api/turma-presencas.php`)

**Endpoint:** `POST /admin/api/turma-presencas.php`

**Validações:**
- ✅ Aluno deve estar matriculado na turma
- ✅ Aula deve existir e estar válida (status: agendada ou realizada)
- ✅ Usuário deve ter permissão (admin/secretaria ou instrutor da turma)
- ✅ Não permite duplicar presença (verifica se já existe)

**Processo:**
1. Valida dados recebidos
2. Verifica se presença já existe
3. Se existe: atualiza registro existente
4. Se não existe: cria novo registro
5. Insere/atualiza na tabela `turma_presencas`:
   ```php
   INSERT INTO turma_presencas (
       turma_id,
       turma_aula_id,
       aluno_id,
       presente,
       registrado_por
   ) VALUES (?, ?, ?, ?, ?)
   ```
6. Registra log de auditoria (se implementado)
7. Retorna sucesso/erro

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Presença registrada com sucesso",
  "presenca_id": 52
}
```

### 5. Atualização da Interface (JavaScript)

Após sucesso da API:

1. **Atualiza interface do aluno:**
   - Muda cor do toggle
   - Atualiza contadores dos cards
   - Mostra toast de sucesso

2. **Atualiza frequência do aluno:**
   - Chama função `atualizarFrequenciaAluno(alunoId)`
   - Faz requisição GET para `/admin/api/turma-frequencia.php?turma_id={ID}&aluno_id={ID}`
   - Atualiza chip de frequência com novo percentual

---

## Fluxo de Exibição de Presença

### 1. Tela de Chamada (`admin/pages/turma-chamada.php`)

**Query para buscar alunos e presenças:**
```sql
SELECT 
    a.*,
    tm.status as status_matricula,
    tm.frequencia_percentual,
    tp.presente,
    tp.id as presenca_id
FROM alunos a
JOIN turma_matriculas tm ON a.id = tm.aluno_id
LEFT JOIN turma_presencas tp ON (
    tp.turma_aula_id = ? 
    AND tp.turma_id = ? 
    AND tp.aluno_id = a.id
)
WHERE tm.turma_id = ?
ORDER BY a.nome
```

**Exibição:**
- Toggle mostra estado atual (presente/ausente)
- Chip de frequência mostra percentual calculado

### 2. Tela de Diário (`admin/pages/turma-diario.php`)

**Seção "Aulas Agendadas":**
- Lista todas as aulas agendadas da turma
- Mostra estatísticas de presença por aula:
  - Total de presentes
  - Total de ausentes
  - Total de registrados
- Botão "Chamada" para cada aula

**Seção "Alunos Matriculados":**
- Lista alunos com frequência
- Link "👁 Ver detalhes" que leva ao histórico do aluno

### 3. Histórico do Aluno (`admin/pages/historico-aluno.php`)

**Query para buscar presenças:**
```sql
SELECT 
    taa.id as aula_id,
    taa.nome_aula,
    taa.disciplina,
    taa.data_aula,
    taa.hora_inicio,
    taa.hora_fim,
    taa.status as aula_status,
    taa.ordem_global,
    tp.id as presenca_id,
    tp.presente,
    tp.registrado_em,
    tp.turma_id as presenca_turma_id,
    tp.turma_aula_id as presenca_turma_aula_id
FROM turma_aulas_agendadas taa
LEFT JOIN turma_presencas tp ON (
    tp.turma_aula_id = taa.id 
    AND tp.turma_id = taa.turma_id
    AND tp.aluno_id = ?
)
WHERE taa.turma_id = ?
AND taa.status IN ('agendada', 'realizada')
ORDER BY taa.ordem_global ASC
```

**Processamento:**
1. Para cada aula retornada:
   - Verifica se há `presenca_id` (indica que há registro)
   - Verifica valor de `presente` (1 = presente, 0 = ausente, null = não registrado)
   - Determina status: `presente`, `ausente` ou `nao_registrado`

2. Calcula frequência em tempo real:
   ```php
   $totalAulasValidas = count($presencasComAulas);
   $totalPresentes = count de registros com presente = 1;
   $frequencia = ($totalPresentes / $totalAulasValidas) * 100;
   ```

**Exibição:**
- Badge de frequência no topo do card da turma
- Tabela com todas as aulas e status de presença:
  - ✅ **Presente** (badge verde)
  - ❌ **Ausente** (badge vermelho)
  - ⚪ **Não registrado** (badge cinza)

### 4. API de Frequência (`admin/api/turma-frequencia.php`)

**Endpoint:** `GET /admin/api/turma-frequencia.php?turma_id={ID}&aluno_id={ID}`

**Cálculo:**
```sql
-- Total de aulas programadas
SELECT COUNT(*) 
FROM turma_aulas_agendadas 
WHERE turma_id = ? 
AND status IN ('agendada', 'realizada')

-- Total de presenças
SELECT 
    COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as presentes
FROM turma_presencas tp
INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
WHERE tp.turma_id = ? 
AND tp.aluno_id = ?
AND taa.status IN ('agendada', 'realizada')

-- Frequência percentual
frequencia = (presentes / total_aulas) * 100
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "estatisticas": {
      "total_aulas_programadas": 45,
      "aulas_presentes": 1,
      "percentual_frequencia": 2.22
    }
  }
}
```

---

## Regras de Negócio

### 1. Validação de Matrícula
- Aluno deve estar matriculado na turma para ter presença registrada
- Status da matrícula deve ser válido (matriculado, cursando)

### 2. Validação de Aula
- Aula deve existir e estar com status `agendada` ou `realizada`
- Aulas canceladas não devem ter presença registrada

### 3. Permissões
- **Admin/Secretaria:** Pode marcar/editar presenças de qualquer turma
- **Instrutor:** Pode marcar/editar presenças apenas de suas próprias turmas
- Validação feita via `instrutor_id` na tabela `turmas_teoricas`

### 4. Unicidade
- Um aluno só pode ter uma presença por aula (UNIQUE KEY)
- Se tentar criar presença duplicada, atualiza a existente

### 5. Cálculo de Frequência
- Baseado em aulas com status `agendada` ou `realizada`
- Fórmula: `(aulas_presentes / total_aulas_programadas) * 100`
- Atualizado em tempo real (não usa cache)

---

## Fluxo de Navegação

### Admin/Secretaria → Chamada

```
Menu Lateral
  └─ Acadêmico
      └─ Turmas Teóricas
          └─ [Selecionar Turma]
              ├─ Opção 1: "Ver Diário" (menu dropdown)
              │   └─ Tela de Diário
              │       └─ Seção "Aulas Agendadas"
              │           └─ Botão "Chamada" → Tela de Chamada
              │
              └─ Opção 2: "Ver Detalhes" (card da turma)
                  └─ Tab "Diário / Presenças"
                      └─ Botão "Chamada" → Tela de Chamada
```

### Admin/Secretaria → Histórico do Aluno

```
Menu Lateral
  └─ Acadêmico
      └─ Turmas Teóricas
          └─ [Selecionar Turma]
              └─ "Ver Diário"
                  └─ Seção "Alunos Matriculados"
                      └─ Link "👁 Ver detalhes" → Histórico do Aluno
```

### Instrutor → Chamada

```
Menu Lateral
  └─ Dashboard
      └─ Aulas
          └─ [Selecionar Aula]
              └─ Botão "Chamada" → Tela de Chamada
```

---

## Correções Implementadas

### 1. Correção do JOIN na Query de Presença
**Problema:** Query usava `aula_id` em vez de `turma_aula_id`

**Solução:** Corrigido JOIN para usar `tp.turma_aula_id = taa.id`

### 2. Remoção de Coluna Inexistente
**Problema:** Query tentava buscar `tp.justificativa` que não existe na tabela

**Solução:** Removido campo `justificativa` de todas as queries

### 3. Detecção Melhorada de Presença
**Problema:** Verificação apenas checava `presente !== null`, não considerava `presenca_id`

**Solução:** Verificação melhorada:
- Se há `presenca_id`, há registro (mesmo que `presente` seja null)
- Se `presente` não é null e não é string vazia, há registro
- Se `presente` é '0' ou 0, há registro (ausente, mas registrado)

### 4. Cálculo de Frequência em Tempo Real
**Problema:** Frequência calculada antes da query principal

**Solução:** Cálculo feito durante o processamento dos resultados do JOIN

### 5. Caminho da API de Frequência
**Problema:** Caminho incorreto causava 404

**Solução:** Cálculo robusto do caminho base usando múltiplas estratégias

---

## Arquivos Principais

### Backend (PHP)

1. **`admin/pages/turma-chamada.php`**
   - Tela principal de marcação de presença
   - Exibe alunos e permite marcar presença
   - Atualiza frequência via JavaScript

2. **`admin/pages/turma-diario.php`**
   - Diário da turma com estatísticas
   - Lista aulas agendadas e alunos matriculados
   - Links para chamada e histórico

3. **`admin/pages/historico-aluno.php`**
   - Histórico completo do aluno
   - Exibe presenças teóricas com frequência
   - Cálculo em tempo real

4. **`admin/api/turma-presencas.php`**
   - API para criar/atualizar/excluir presenças
   - Validações e permissões
   - Logs de auditoria

5. **`admin/api/turma-frequencia.php`**
   - API para calcular frequência
   - Retorna estatísticas detalhadas

### Frontend (JavaScript)

1. **`admin/pages/turma-chamada.php` (seção JavaScript)**
   - Funções: `criarPresenca()`, `atualizarPresenca()`, `marcarTodos()`
   - Função: `atualizarFrequenciaAluno()`
   - Tratamento de erros robusto

---

## Testes de Validação

### Cenário 1: Marcar Presença como Admin

**Passos:**
1. Acessar `admin/index.php?page=turma-chamada&turma_id=19&aula_id=227`
2. Marcar aluno 167 como "Presente"
3. Verificar toast "Presença registrada com sucesso"
4. Verificar cards: 1 aluno / 1 presente / 100% frequência média
5. Verificar chip de frequência: deve mostrar valor > 0%

**Resultado Esperado:**
- ✅ Toast de sucesso
- ✅ Cards atualizados
- ✅ Chip de frequência atualizado
- ✅ Nenhum erro 404 no console
- ✅ Presença salva no banco

### Cenário 2: Verificar no Histórico

**Passos:**
1. Acessar `admin/index.php?page=historico-aluno&id=167&turma_id=19`
2. Verificar seção "Presença Teórica"
3. Verificar frequência da turma
4. Verificar status da aula

**Resultado Esperado:**
- ✅ Frequência > 0% (ex: 100% se 1/1 aula)
- ✅ Aula aparece como "✓ PRESENTE" (badge verde)
- ✅ Data e disciplina corretas

### Cenário 3: Verificar no Diário

**Passos:**
1. Acessar `admin/index.php?page=turma-diario&turma_id=19`
2. Verificar seção "Aulas Agendadas"
3. Verificar estatísticas da aula 227
4. Verificar seção "Alunos Matriculados"

**Resultado Esperado:**
- ✅ Aula 227 mostra "1/1" em presenças
- ✅ Status "Concluída" ou "Em andamento"
- ✅ Aluno 167 aparece com frequência > 0%

---

## Logs de Debug

### JavaScript (Console do Navegador)

**Marcação de Presença:**
```
[Frequência] Constantes definidas: {API_TURMA_FREQUENCIA: "...", ...}
[Frequência] Iniciando atualização para aluno: 167 turma: 19
[Frequência] Fazendo requisição para: /cfc-bom-conselho/admin/api/turma-frequencia.php?turma_id=19&aluno_id=167
[Frequência] Resposta recebida: 200 OK
[Frequência] Dados recebidos: {success: true, data: {...}}
[Frequência] Percentual calculado: 2.22
[Frequência] Badge atualizado com sucesso!
```

### PHP (Logs do Servidor)

**Busca de Presenças no Histórico:**
```
[Histórico] Buscando presenças - turma_id: 19, aluno_id: 167
[Histórico] Aulas encontradas: 1
[Histórico] Processando aula_id: 227, presenca_id: 52, presente: 1 (tipo: integer), status: presente
[Histórico] Frequência calculada - presentes: 1, total: 1, percentual: 100.0%
```

---

## Troubleshooting

### Problema: Presença não aparece no histórico

**Diagnóstico:**
1. Verificar se presença existe no banco:
   ```sql
   SELECT * FROM turma_presencas 
   WHERE aluno_id = 167 AND turma_id = 19 AND turma_aula_id = 227;
   ```

2. Verificar logs PHP para ver se query está retornando dados

3. Verificar se JOIN está correto (usando `turma_aula_id`)

**Solução:**
- Se presença existe mas não aparece: problema no JOIN
- Se presença não existe: problema na API de criação

### Problema: Frequência mostra 0%

**Diagnóstico:**
1. Verificar se cálculo está usando dados corretos
2. Verificar se `totalAulasValidas` está correto
3. Verificar se `totalPresentes` está correto

**Solução:**
- Verificar logs `[Histórico] Frequência calculada`
- Comparar com cálculo manual via SQL

### Problema: Chip de frequência não atualiza

**Diagnóstico:**
1. Verificar console do navegador para erros
2. Verificar se `API_TURMA_FREQUENCIA` está definida
3. Verificar se endpoint está acessível

**Solução:**
- Verificar logs `[Frequência]` no console
- Testar endpoint diretamente no navegador
- Verificar cálculo do caminho base

---

## Documentação Relacionada

- **`docs/RESUMO_AJUSTE_PRESENCAS_2025.md`** - Ajustes iniciais de presença
- **`docs/RESUMO_FLUXO_ADMIN_DIARIO_CHAMADA_2025.md`** - Fluxo de navegação
- **`docs/RESUMO_CORRECAO_HISTORICO_PRESENCA_2025.md`** - Correções no histórico
- **`docs/RESUMO_CORRECAO_CHIP_FREQUENCIA_CHAMADA_2025.md`** - Correções no chip de frequência
- **`docs/TROUBLESHOOTING_PRESENCA_FREQUENCIA_2025.md`** - Guia de troubleshooting

---

## Próximos Passos (Opcional)

- [ ] Implementar campo `justificativa` na tabela `turma_presencas` (se necessário)
- [ ] Adicionar exportação de presenças (PDF/Excel)
- [ ] Implementar notificações para alunos sobre presença
- [ ] Adicionar gráficos de frequência
- [ ] Implementar relatórios de frequência por período

---

---

## Integração com Progresso Geral do Aluno

### Cálculo de Aulas Teóricas Concluídas no Histórico

**CORREÇÃO 2025-12:** O cálculo de progresso teórico no histórico do aluno (`historico-aluno.php`) foi alinhado para usar a mesma fonte de verdade da presença teórica.

**Fonte de Verdade:**
- Tabela: `turma_presencas` com `presente = 1`
- JOIN com: `turma_aulas_agendadas` com status `agendada` ou `realizada`
- Contagem: Disciplinas teóricas únicas onde o aluno está presente

**Cards Atualizados:**
- ✅ **Total Aulas Teóricas**: Agora usa `turma_presencas` (ex: 1/45 em vez de 0/45)
- ✅ **Resumo Teórico do Curso**: Alinhado com presenças reais
- ✅ **Resumo Geral**: Horas concluídas calculadas a partir de presenças teóricas reais
- ✅ **Progresso Detalhado por Categoria**: Usa mesma lógica de presença teórica

**Regra de Cálculo:**
```sql
-- Contar aulas teóricas concluídas
SELECT COUNT(DISTINCT taa.disciplina)
FROM turma_presencas tp
INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
WHERE tp.aluno_id = ?
AND tp.presente = 1
AND taa.status IN ('agendada', 'realizada')
```

**Horas Concluídas:**
- Cada aula teórica presente conta como 1 hora
- Total de horas teóricas concluídas = quantidade de presenças teóricas
- Horas restantes = Total necessário - Horas concluídas

---

## Correção do Bloco "Histórico Completo de Aulas"

**CORREÇÃO 2025-12:** Removidos registros fantasmas com dados N/A.

**Mudanças:**
- ✅ Filtro aplicado: Apenas aulas com `id` válido e `data_aula` preenchida são exibidas
- ✅ Mensagem amigável quando não há aulas práticas registradas
- ✅ Validação de dados antes de exibir (evita N/A)
- ✅ Botões de ação só aparecem quando há ID válido

**Query Original:**
```sql
SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa, v.modelo, v.marca
FROM aulas a
LEFT JOIN instrutores i ON a.instrutor_id = i.id
LEFT JOIN usuarios u ON i.usuario_id = u.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.aluno_id = ?
ORDER BY a.data_aula DESC, a.hora_inicio DESC
LIMIT 500
```

**Filtro Aplicado:**
```php
// Filtrar apenas aulas com dados válidos
$aulasValidas = array_filter($aulas, function($aula) {
    return !empty($aula['id']) && !empty($aula['data_aula']);
});
```

---

**Autor:** Sistema CFC Bom Conselho  
**Revisão:** 2025-12-12  
**Última atualização:** 2025-12-12  
**Status:** ✅ Funcionando corretamente (incluindo progresso geral e histórico completo)
