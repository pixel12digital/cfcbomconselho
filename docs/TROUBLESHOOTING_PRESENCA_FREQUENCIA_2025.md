# TROUBLESHOOTING - PRESENÇA E FREQUÊNCIA TEÓRICA

**Data:** 2025-12-12  
**Objetivo:** Documentar problemas comuns, erros e soluções relacionadas a presença e frequência teórica

---

## Problemas Conhecidos e Soluções

### 1. Chip de Frequência Mostra 0,0% na Chamada

#### Sintoma
- Chip de frequência do aluno na tela de chamada permanece em 0,0% mesmo após marcar presença
- Console pode mostrar erro 404 ou erro de parsing JSON

#### Possíveis Causas

**Causa 1: Caminho da API incorreto**
- **Erro no console:** `Failed to load resource: the server responded with a status of 404`
- **Solução:** Verificar se `API_TURMA_FREQUENCIA` está definida corretamente
- **Verificação:**
  ```javascript
  console.log('[Frequência] Constantes definidas:', {
      API_TURMA_FREQUENCIA: API_TURMA_FREQUENCIA,
      turmaId: turmaId,
      aulaId: aulaId
  });
  ```

**Causa 2: Resposta não é JSON válido**
- **Erro no console:** `SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON`
- **Causa:** Endpoint retornando HTML (erro 404/500) em vez de JSON
- **Solução:** Verificar se o endpoint `admin/api/turma-frequencia.php` existe e está acessível
- **Verificação:**
  ```javascript
  fetch(API_TURMA_FREQUENCIA + '?turma_id=19&aluno_id=167')
      .then(response => {
          console.log('Status:', response.status);
          console.log('Content-Type:', response.headers.get('content-type'));
          return response.text();
      })
      .then(text => console.log('Resposta:', text.substring(0, 200)));
  ```

**Causa 3: Função não está sendo chamada**
- **Sintoma:** Nenhum log `[Frequência]` aparece no console
- **Solução:** Verificar se `atualizarFrequenciaAluno()` está sendo chamada após marcar presença
- **Verificação:** Adicionar log no início da função:
  ```javascript
  function atualizarFrequenciaAluno(alunoId) {
      console.log('[Frequência] Função chamada para aluno:', alunoId);
      // ...
  }
  ```

**Causa 4: Elemento badge não encontrado**
- **Sintoma:** Log mostra `[Frequência] Elemento badge não encontrado`
- **Causa:** ID do elemento não corresponde ao ID esperado
- **Solução:** Verificar se o elemento tem `id="freq-badge-{aluno_id}"`
- **Verificação:**
  ```javascript
  const badgeElement = document.getElementById(`freq-badge-${alunoId}`);
  console.log('Badge encontrado:', badgeElement);
  console.log('ID esperado:', `freq-badge-${alunoId}`);
  ```

**Causa 5: Dados da API não no formato esperado**
- **Sintoma:** Log mostra `[Frequência] Resposta da API não contém dados de frequência`
- **Causa:** Estrutura da resposta diferente do esperado
- **Solução:** Verificar estrutura da resposta:
  ```javascript
  .then(data => {
      console.log('[Frequência] Estrutura completa:', JSON.stringify(data, null, 2));
      // Verificar se existe: data.success, data.data, data.data.estatisticas, data.data.estatisticas.percentual_frequencia
  });
  ```

---

### 2. Histórico do Aluno Mostra "NÃO REGISTRADO" e Frequência 0,0%

#### Sintoma
- Bloco "Presença Teórica" no histórico mostra frequência 0,0%
- Aulas aparecem como "NÃO REGISTRADO" mesmo com presença registrada
- Warning PHP: `Undefined variable $isTurmaFoco`

#### Possíveis Causas

**Causa 1: Variável não definida (Warning PHP)**
- **Erro:** `Warning: Undefined variable $isTurmaFoco`
- **Solução:** Variável deve ser definida dentro do loop `foreach`
- **Código correto:**
  ```php
  foreach ($presencaTeoricaDetalhada as $item) {
      $turma = $item['turma'];
      // Definir $isTurmaFoco dentro do loop
      $isTurmaFoco = ($turmaIdFoco && (int)$turmaIdFoco === (int)$turma['turma_id']);
      // ...
  }
  ```

**Causa 2: JOIN não está encontrando presenças**
- **Sintoma:** Consulta retorna aulas mas `presente` sempre `null`
- **Causa:** Problema na condição do JOIN ou dados não existem na tabela
- **Solução:** Verificar dados diretamente no banco:
  ```sql
  SELECT * FROM turma_presencas 
  WHERE turma_id = 19 
    AND turma_aula_id = 227 
    AND aluno_id = 167;
  ```
- **Verificação no código:**
  ```php
  error_log("[Histórico] Buscando presenças - turma_id: {$turma['turma_id']}, aluno_id: {$alunoId}");
  error_log("[Histórico] Aulas encontradas: " . count($presencasComAulas));
  ```

**Causa 3: Tipo de dados incorreto**
- **Sintoma:** Presença existe mas não é detectada
- **Causa:** Campo `presente` pode ser string `'1'` em vez de int `1`
- **Solução:** Usar conversão explícita:
  ```php
  if ($row['presente'] !== null && $row['presente'] !== '') {
      $statusPresenca = ((int)$row['presente'] == 1) ? 'presente' : 'ausente';
  }
  ```

**Causa 4: Cálculo de frequência usando dados errados**
- **Sintoma:** Frequência calculada como 0% mesmo com presenças
- **Causa:** `$totalAulasValidas` calculado antes da consulta
- **Solução:** Contar durante o processamento dos resultados:
  ```php
  $totalAulasValidas = 0;
  foreach ($presencasComAulas as $row) {
      $totalAulasValidas++; // Contar durante o loop
      // ...
  }
  ```

**Causa 5: Status da aula não está correto**
- **Sintoma:** Aulas não aparecem no histórico
- **Causa:** Filtro `status IN ('agendada', 'realizada')` pode estar excluindo aulas
- **Solução:** Verificar status das aulas:
  ```sql
  SELECT id, status, data_aula 
  FROM turma_aulas_agendadas 
  WHERE turma_id = 19;
  ```

---

### 3. Erros de Console Relacionados

#### Erro: "API_TURMA_FREQUENCIA não está definida"
- **Causa:** Constante não foi exposta do PHP para JavaScript
- **Solução:** Verificar se `$apiTurmaFrequenciaUrl` está definida e exposta:
  ```php
  $apiTurmaFrequenciaUrl = $baseRoot . '/admin/api/turma-frequencia.php';
  // ...
  const API_TURMA_FREQUENCIA = <?php echo json_encode($apiTurmaFrequenciaUrl); ?>;
  ```

#### Erro: "Turma ID ou Aluno ID não disponível"
- **Causa:** Variáveis `turmaId` ou `alunoId` não estão definidas no escopo JavaScript
- **Solução:** Verificar se variáveis estão sendo definidas:
  ```php
  let turmaId = <?= $turmaId ?>;
  let aulaId = <?= $aulaId ?>;
  ```

#### Erro: "Resposta não é JSON"
- **Causa:** Endpoint retornando HTML (erro 404/500) ou redirecionamento
- **Solução:** 
  1. Verificar se endpoint existe: `admin/api/turma-frequencia.php`
  2. Verificar permissões de acesso
  3. Verificar se há erros PHP no endpoint

---

## Checklist de Diagnóstico

### Para Chip de Frequência na Chamada

- [ ] Verificar se `API_TURMA_FREQUENCIA` está definida no console
- [ ] Verificar se `turmaId` e `alunoId` estão definidos
- [ ] Verificar se função `atualizarFrequenciaAluno()` está sendo chamada
- [ ] Verificar resposta da API no Network tab (F12 → Network)
- [ ] Verificar se elemento badge existe no DOM
- [ ] Verificar estrutura da resposta JSON da API

### Para Histórico do Aluno

- [ ] Verificar se warning PHP desapareceu
- [ ] Verificar logs do PHP para ver consultas executadas
- [ ] Verificar se presença existe no banco de dados
- [ ] Verificar se JOIN está correto (usando `turma_aula_id`)
- [ ] Verificar se cálculo de frequência está usando dados corretos
- [ ] Verificar se status das aulas está correto

---

## Queries SQL Úteis para Diagnóstico

### Verificar Presença Específica
```sql
SELECT 
    tp.*,
    taa.nome_aula,
    taa.data_aula,
    a.nome as aluno_nome
FROM turma_presencas tp
JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
JOIN alunos a ON tp.aluno_id = a.id
WHERE tp.turma_id = 19
  AND tp.turma_aula_id = 227
  AND tp.aluno_id = 167;
```

### Verificar Todas as Presenças do Aluno na Turma
```sql
SELECT 
    tp.*,
    taa.nome_aula,
    taa.data_aula,
    taa.status as aula_status
FROM turma_presencas tp
JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
WHERE tp.turma_id = 19
  AND tp.aluno_id = 167
ORDER BY taa.data_aula, taa.hora_inicio;
```

### Verificar Aulas Válidas da Turma
```sql
SELECT 
    id,
    nome_aula,
    data_aula,
    status,
    ordem_global
FROM turma_aulas_agendadas
WHERE turma_id = 19
  AND status IN ('agendada', 'realizada')
ORDER BY ordem_global;
```

### Calcular Frequência Manualmente
```sql
SELECT 
    COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as total_presentes,
    COUNT(DISTINCT taa.id) as total_aulas,
    ROUND((COUNT(CASE WHEN tp.presente = 1 THEN 1 END) / COUNT(DISTINCT taa.id)) * 100, 1) as frequencia_percentual
FROM turma_aulas_agendadas taa
LEFT JOIN turma_presencas tp ON (
    tp.turma_aula_id = taa.id 
    AND tp.turma_id = taa.turma_id
    AND tp.aluno_id = 167
)
WHERE taa.turma_id = 19
  AND taa.status IN ('agendada', 'realizada');
```

---

## Logs de Debug Disponíveis

### JavaScript (Console do Navegador)

**Constantes:**
```
[Frequência] Constantes definidas: {API_TURMA_FREQUENCIA: "...", turmaId: 19, aulaId: 227}
```

**Chamada da função:**
```
[Frequência] Iniciando atualização para aluno: 167 turma: 19
```

**Requisição:**
```
[Frequência] Fazendo requisição para: /cfc-bom-conselho/admin/api/turma-frequencia.php?turma_id=19&aluno_id=167
```

**Resposta:**
```
[Frequência] Resposta recebida: 200 OK
[Frequência] Dados recebidos: {success: true, data: {...}}
[Frequência] Percentual calculado: 2.22
```

**Elemento:**
```
[Frequência] Elemento badge encontrado: <span id="freq-badge-167">...</span>
[Frequência] Atualizando badge de 0,0% para 2,2%
[Frequência] Badge atualizado com sucesso!
```

### PHP (Logs do Servidor)

**Busca de presenças:**
```
[Histórico] Buscando presenças - turma_id: 19, aluno_id: 167
[Histórico] Aulas encontradas: 1
```

**Presenças encontradas:**
```
[Histórico] Presença encontrada - aula_id: 227, presente: 1, status: presente
```

**Cálculo:**
```
[Histórico] Frequência calculada - presentes: 1, total: 45, percentual: 2.2%
```

---

## Soluções por Problema Específico

### Problema: Chip mostra 0,0% mas API retorna dados corretos

**Diagnóstico:**
1. Verificar logs `[Frequência] Dados recebidos:` - se mostra percentual correto
2. Verificar logs `[Frequência] Elemento badge encontrado:` - se elemento existe
3. Verificar logs `[Frequência] Atualizando badge de X para Y` - se atualização está sendo feita

**Solução:**
- Se dados estão corretos mas badge não atualiza: problema de DOM
- Verificar se elemento está sendo recriado após atualização
- Adicionar delay antes de atualizar:
  ```javascript
  setTimeout(() => {
      const badgeElement = document.getElementById(`freq-badge-${alunoId}`);
      if (badgeElement) {
          badgeElement.textContent = novoValor;
      }
  }, 100);
  ```

### Problema: Histórico mostra 0,0% mas presença existe no banco

**Diagnóstico:**
1. Executar query SQL de verificação
2. Verificar logs PHP `[Histórico] Presença encontrada`
3. Verificar se JOIN está correto

**Solução:**
- Se presença existe mas não aparece: problema no JOIN
- Verificar se `turma_aula_id` está correto na tabela `turma_presencas`
- Verificar se `aluno_id` está correto
- Verificar se `turma_id` está correto

### Problema: Frequência calculada incorretamente

**Diagnóstico:**
1. Verificar logs `[Histórico] Frequência calculada`
2. Comparar com cálculo manual via SQL
3. Verificar se `$totalAulasValidas` está correto

**Solução:**
- Se `totalAulasValidas` está errado: verificar se está contando durante o loop
- Se `totalPresentes` está errado: verificar se está contando apenas `presente = 1`
- Se percentual está errado: verificar fórmula `(presentes / total) * 100`

---

## Arquivos com Logs de Debug

### `admin/pages/turma-chamada.php`
- Logs JavaScript no console do navegador
- Prefixo: `[Frequência]`

### `admin/pages/historico-aluno.php`
- Logs PHP no log do servidor
- Prefixo: `[Histórico]`
- Localização: `error_log()` do PHP

---

## Como Ativar/Desativar Logs

### JavaScript (Console)
Os logs já estão ativos. Para desativar, comentar as linhas `console.log()`.

### PHP (Servidor)
Os logs estão ativos via `error_log()`. Para desativar, comentar as chamadas `error_log()`.

**Localização dos logs PHP:**
- XAMPP Windows: `C:\xampp\php\logs\php_error_log`
- Ou verificar configuração `error_log` no `php.ini`

---

## Testes de Validação

### Teste 1: Verificar se API está acessível
```bash
curl "http://localhost/cfc-bom-conselho/admin/api/turma-frequencia.php?turma_id=19&aluno_id=167"
```

**Resultado esperado:**
```json
{
  "success": true,
  "data": {
    "estatisticas": {
      "percentual_frequencia": 2.22
    }
  }
}
```

### Teste 2: Verificar se presença existe
```sql
SELECT COUNT(*) as total
FROM turma_presencas
WHERE turma_id = 19
  AND turma_aula_id = 227
  AND aluno_id = 167
  AND presente = 1;
```

**Resultado esperado:** `total = 1`

### Teste 3: Verificar cálculo manual
```sql
-- Ver query SQL acima "Calcular Frequência Manualmente"
```

**Resultado esperado:** `frequencia_percentual > 0`

---

## Próximos Passos se Problema Persistir

1. **Verificar dados no banco:**
   - Executar queries SQL de diagnóstico
   - Confirmar que presença existe e está correta

2. **Verificar logs:**
   - Revisar logs do PHP no servidor
   - Revisar logs do JavaScript no console

3. **Testar endpoint diretamente:**
   - Usar curl ou Postman para testar API
   - Verificar resposta JSON

4. **Verificar permissões:**
   - Confirmar que usuário tem permissão para ver frequência
   - Verificar se endpoint está acessível

5. **Verificar cache:**
   - Limpar cache do navegador
   - Verificar se há cache no servidor

---

## Contatos e Referências

- **Documentação relacionada:**
  - `docs/RESUMO_CORRECAO_HISTORICO_PRESENCA_2025.md`
  - `docs/RESUMO_CORRECAO_CHIP_FREQUENCIA_CHAMADA_2025.md`
  - `docs/RESUMO_AJUSTE_PRESENCAS_2025.md`

- **Arquivos principais:**
  - `admin/pages/turma-chamada.php` - Tela de chamada
  - `admin/pages/historico-aluno.php` - Histórico do aluno
  - `admin/api/turma-frequencia.php` - API de frequência
  - `admin/api/turma-presencas.php` - API de presenças

---

**Autor:** Sistema CFC Bom Conselho  
**Revisão:** 2025-12-12  
**Última atualização:** 2025-12-12




