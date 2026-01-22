# FASE 4: PLANO DE APLICAÇÃO DE ÍNDICES NO BANCO DE DADOS

## Objetivo
Criar índices recomendados no banco de dados para melhorar significativamente a performance das queries mais usadas, especialmente nas APIs que estão apresentando timeouts.

## Índices Recomendados

### Prioridade ALTA (Impacto Imediato)
1. **`idx_aulas_aluno_tipo_status`** - Tabela `aulas`
   - Impacto: Queries de progresso prático/teórico
   - Benefício: Redução de 80-90% no tempo de execução

2. **`idx_aulas_aluno_tipo_data`** - Tabela `aulas`
   - Impacto: Histórico de aluno ordenado por data
   - Benefício: Redução de 70-85% no tempo de execução

3. **`idx_pagamentos_fatura_data`** - Tabela `pagamentos`
   - Impacto: Eliminação de N+1 em faturas
   - Benefício: Redução de 90%+ no tempo de execução

4. **`idx_exames_aluno_tipo_data`** - Tabela `exames`
   - Impacto: Resumo de exames do aluno
   - Benefício: Redução de 60-75% no tempo de execução

### Prioridade MÉDIA (Melhorias Adicionais)
5. **`idx_faturas_aluno_vencimento`** - Tabela `faturas`
6. **`idx_matriculas_aluno_status_data`** - Tabela `matriculas`
7. **`idx_turma_matriculas_aluno_data`** - Tabela `turma_matriculas`

### Prioridade BAIXA (Otimizações Futuras)
8. Índices adicionais para relatórios e filtros avançados

## Plano de Execução

### FASE 4.1: Preparação (Antes de Executar)

1. **Backup do Banco de Dados**
   ```bash
   # Criar backup completo antes de aplicar índices
   mysqldump -u usuario -p nome_banco > backup_antes_indices_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Verificar Espaço em Disco**
   - Índices ocupam espaço adicional
   - Verificar se há espaço suficiente (estimativa: 10-20% do tamanho atual das tabelas)

3. **Verificar Índices Existentes**
   ```sql
   -- Verificar índices já existentes
   SHOW INDEX FROM aulas;
   SHOW INDEX FROM exames;
   SHOW INDEX FROM faturas;
   SHOW INDEX FROM pagamentos;
   ```

### FASE 4.2: Execução em Desenvolvimento

1. **Executar Script em Ambiente de Desenvolvimento**
   ```bash
   mysql -u usuario -p nome_banco_dev < docs/FASE4_INDICES_BANCO_DADOS.sql
   ```

2. **Verificar Criação dos Índices**
   ```sql
   -- Verificar se os índices foram criados
   SELECT 
       TABLE_NAME,
       INDEX_NAME,
       COLUMN_NAME
   FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = 'nome_banco_dev'
       AND TABLE_NAME IN ('aulas', 'exames', 'faturas', 'pagamentos')
   ORDER BY TABLE_NAME, INDEX_NAME;
   ```

3. **Testar Funcionalidades**
   - Abrir modal de aluno
   - Carregar histórico de aluno
   - Carregar resumo de provas
   - Carregar progresso prático
   - Verificar se não há regressões

4. **Medir Performance**
   - Comparar tempos antes e depois
   - Verificar se timeouts foram resolvidos
   - Monitorar uso de CPU e memória

### FASE 4.3: Execução em Produção

1. **Escolher Horário de Baixo Tráfego**
   - Preferencialmente: madrugada ou fim de semana
   - Avisar usuários sobre possível lentidão temporária

2. **Executar Script Gradualmente (Recomendado)**
   ```sql
   -- Executar índices de prioridade ALTA primeiro
   -- Aguardar alguns minutos entre cada grupo
   
   -- Grupo 1: Aulas (prioridade ALTA)
   CREATE INDEX IF NOT EXISTS idx_aulas_aluno_tipo_status 
   ON aulas(aluno_id, tipo_aula, status);
   
   CREATE INDEX IF NOT EXISTS idx_aulas_aluno_tipo_data 
   ON aulas(aluno_id, tipo_aula, data_aula DESC);
   
   -- Aguardar 5 minutos, monitorar sistema
   
   -- Grupo 2: Pagamentos (prioridade ALTA)
   CREATE INDEX IF NOT EXISTS idx_pagamentos_fatura_data 
   ON pagamentos(fatura_id, data_pagamento DESC);
   
   -- Aguardar 5 minutos, monitorar sistema
   
   -- Grupo 3: Exames (prioridade ALTA)
   CREATE INDEX IF NOT EXISTS idx_exames_aluno_tipo_data 
   ON exames(aluno_id, tipo, data_agendada DESC, data_resultado DESC);
   
   -- Aguardar 5 minutos, monitorar sistema
   
   -- Grupo 4: Faturas e Matrículas (prioridade MÉDIA)
   -- Executar após confirmar que os anteriores funcionaram bem
   ```

3. **Monitorar Durante Execução**
   - Verificar uso de CPU e memória
   - Verificar locks no banco de dados
   - Verificar tempo de execução de queries

4. **Verificar Após Execução**
   - Testar todas as funcionalidades críticas
   - Verificar se timeouts foram resolvidos
   - Comparar tempos de resposta

### FASE 4.4: Validação e Monitoramento

1. **Executar ANALYZE TABLE**
   ```sql
   ANALYZE TABLE aulas;
   ANALYZE TABLE exames;
   ANALYZE TABLE faturas;
   ANALYZE TABLE pagamentos;
   ANALYZE TABLE matriculas;
   ANALYZE TABLE turma_matriculas;
   ```
   - Atualiza estatísticas do MySQL para uso otimizado dos índices

2. **Verificar Uso dos Índices**
   ```sql
   -- Verificar se os índices estão sendo usados
   EXPLAIN SELECT ... FROM aulas WHERE aluno_id = ? AND tipo_aula = 'pratica';
   ```

3. **Monitorar Performance**
   - Acompanhar logs de erro por 24-48 horas
   - Verificar se não há queries mais lentas
   - Coletar feedback dos usuários

## Riscos e Mitigações

### Riscos Identificados

1. **Aumento de Espaço em Disco**
   - Risco: Baixo
   - Mitigação: Verificar espaço antes de executar

2. **Lentidão Temporária Durante Criação**
   - Risco: Médio
   - Mitigação: Executar em horário de baixo tráfego, criar gradualmente

3. **Possível Impacto em INSERT/UPDATE**
   - Risco: Baixo
   - Mitigação: Monitorar após criação, remover índices não utilizados se necessário

4. **Incompatibilidade com Versão do MySQL**
   - Risco: Baixo
   - Mitigação: Testar em ambiente de desenvolvimento primeiro

## Rollback (Se Necessário)

Se houver problemas após criar os índices:

```sql
-- Remover índices criados
DROP INDEX IF EXISTS idx_aulas_aluno_tipo_status ON aulas;
DROP INDEX IF EXISTS idx_aulas_aluno_tipo_data ON aulas;
DROP INDEX IF EXISTS idx_pagamentos_fatura_data ON pagamentos;
DROP INDEX IF EXISTS idx_exames_aluno_tipo_data ON exames;
-- ... (repetir para todos os índices criados)
```

## Resultados Esperados

Após aplicar os índices:

1. **Redução de Timeouts**
   - `historico_aluno.php`: De 8+ segundos para < 2 segundos
   - `exames.php`: De 8+ segundos para < 1 segundo
   - `progresso_pratico.php`: De 8+ segundos para < 1 segundo

2. **Melhoria Geral**
   - Queries 60-90% mais rápidas
   - Menor uso de CPU no servidor
   - Melhor experiência do usuário

3. **Escalabilidade**
   - Sistema preparado para crescimento de dados
   - Performance mantida mesmo com mais registros

## Checklist Final

- [ ] Backup do banco criado
- [ ] Espaço em disco verificado
- [ ] Script testado em desenvolvimento
- [ ] Funcionalidades testadas após criação
- [ ] Horário de baixo tráfego escolhido
- [ ] Usuários avisados (se necessário)
- [ ] Script executado em produção
- [ ] Índices verificados após criação
- [ ] ANALYZE TABLE executado
- [ ] Performance monitorada
- [ ] Funcionalidades validadas
- [ ] Documentação atualizada

