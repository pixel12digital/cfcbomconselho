# FASE 4: GUIA RÁPIDO DE EXECUÇÃO

## 📋 Resumo

Este guia fornece instruções passo a passo para aplicar os índices no banco de dados seguindo o plano de aplicação.

## 📁 Scripts Disponíveis

| Script | Descrição | Quando Usar |
|--------|-----------|-------------|
| `FASE4_INDICES_PRIORIDADE_ALTA.sql` | Índices críticos (maior impacto) | **PRIMEIRO** - Execute antes de tudo |
| `FASE4_INDICES_PRIORIDADE_MEDIA.sql` | Índices importantes | **SEGUNDO** - Após validar os de ALTA |
| `FASE4_INDICES_COMPLEMENTARES.sql` | Otimizações adicionais | **TERCEIRO** - Após validar os anteriores |
| `FASE4_VERIFICAR_INDICES.sql` | Verificar se índices foram criados | Após cada execução |
| `FASE4_ANALYZE_TABLES.sql` | Atualizar estatísticas do MySQL | Após criar todos os índices |
| `FASE4_ROLLBACK.sql` | Remover índices (se necessário) | Apenas em caso de problemas |

## 🚀 Execução Passo a Passo

### PASSO 1: Preparação (OBRIGATÓRIO)

1. **Fazer Backup do Banco**
   ```bash
   mysqldump -u usuario -p nome_banco > backup_antes_indices_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Verificar Espaço em Disco**
   - Verifique se há espaço suficiente (estimativa: 10-20% do tamanho atual)

3. **Escolher Horário**
   - Preferencialmente: madrugada ou fim de semana
   - Avisar usuários sobre possível lentidão temporária

### PASSO 2: Executar Índices de Prioridade ALTA

1. **Executar Script**
   ```bash
   mysql -u usuario -p nome_banco < docs/FASE4_INDICES_PRIORIDADE_ALTA.sql
   ```
   ⏱️ **Tempo estimado:** 2-5 minutos (dependendo do tamanho das tabelas)

2. **Verificar Criação**
   ```bash
   mysql -u usuario -p nome_banco < docs/FASE4_VERIFICAR_INDICES.sql
   ```

3. **Testar Funcionalidades**
   - ✅ Abrir modal de aluno
   - ✅ Carregar histórico de aluno
   - ✅ Carregar resumo de provas
   - ✅ Carregar progresso prático

4. **Aguardar e Monitorar**
   - ⏳ Aguardar 5-10 minutos
   - 📊 Monitorar uso de CPU e memória
   - 🔍 Verificar logs de erro

### PASSO 3: Executar Índices de Prioridade MÉDIA

**⚠️ Execute APENAS se os índices de ALTA funcionaram bem**

1. **Executar Script**
   ```bash
   mysql -u usuario -p nome_banco < docs/FASE4_INDICES_PRIORIDADE_MEDIA.sql
   ```
   ⏱️ **Tempo estimado:** 1-3 minutos

2. **Verificar Criação**
   ```bash
   mysql -u usuario -p nome_banco < docs/FASE4_VERIFICAR_INDICES.sql
   ```

3. **Testar Novamente**
   - ✅ Repetir testes do PASSO 2

4. **Aguardar e Monitorar**
   - ⏳ Aguardar 5-10 minutos
   - 📊 Monitorar sistema

### PASSO 4: Executar Índices Complementares (Opcional)

**⚠️ Execute APENAS se todos os anteriores funcionaram bem**

1. **Executar Script**
   ```bash
   mysql -u usuario -p nome_banco < docs/FASE4_INDICES_COMPLEMENTARES.sql
   ```
   ⏱️ **Tempo estimado:** 1-2 minutos

2. **Verificar Criação**
   ```bash
   mysql -u usuario -p nome_banco < docs/FASE4_VERIFICAR_INDICES.sql
   ```

### PASSO 5: Atualizar Estatísticas (OBRIGATÓRIO)

**Execute APÓS criar todos os índices**

1. **Executar ANALYZE TABLE**
   ```bash
   mysql -u usuario -p nome_banco < docs/FASE4_ANALYZE_TABLES.sql
   ```
   ⏱️ **Tempo estimado:** 5-15 minutos (dependendo do tamanho das tabelas)

   ⚠️ **IMPORTANTE:** Este passo pode levar alguns minutos. Execute em horário de baixo tráfego.

## ✅ Checklist de Validação

Após executar os scripts, verifique:

- [ ] Todos os índices foram criados (usar `FASE4_VERIFICAR_INDICES.sql`)
- [ ] Funcionalidades críticas funcionam normalmente
- [ ] Não há erros nos logs do servidor
- [ ] Tempo de resposta melhorou significativamente
- [ ] Não há queries mais lentas após criação dos índices
- [ ] ANALYZE TABLE foi executado

## 🔄 Rollback (Se Necessário)

Se houver problemas graves após criar os índices:

```bash
mysql -u usuario -p nome_banco < docs/FASE4_ROLLBACK.sql
```

⚠️ **Use apenas em caso de problemas graves!**

## 📊 Resultados Esperados

Após aplicar os índices:

| Endpoint | Antes | Depois | Melhoria |
|----------|-------|--------|----------|
| `historico_aluno.php` | 8+ segundos (timeout) | < 2 segundos | ✅ 75-80% |
| `exames.php` | 8+ segundos (timeout) | < 1 segundo | ✅ 85-90% |
| `progresso_pratico.php` | 8+ segundos (timeout) | < 1 segundo | ✅ 85-90% |

## 🆘 Troubleshooting

### Problema: Script demora muito para executar
**Solução:** Normal para tabelas grandes. Aguarde a conclusão. Se demorar mais de 30 minutos, considere executar em horário de menor tráfego.

### Problema: Erro "Duplicate key name"
**Solução:** O índice já existe. Isso é normal se executar o script novamente. Use `CREATE INDEX IF NOT EXISTS` (já incluído nos scripts).

### Problema: Erro de permissão
**Solução:** Verifique se o usuário tem permissão `CREATE INDEX` no banco de dados.

### Problema: Performance piorou após criar índices
**Solução:** Execute `ANALYZE TABLE` para atualizar estatísticas. Se persistir, verifique se os índices estão sendo usados com `EXPLAIN`.

## 📞 Suporte

Se encontrar problemas:
1. Verifique os logs do MySQL
2. Execute `FASE4_VERIFICAR_INDICES.sql` para verificar índices criados
3. Use `EXPLAIN` nas queries problemáticas para verificar uso de índices
4. Considere fazer rollback se necessário

## 📝 Notas Finais

- ✅ Execute sempre em ambiente de desenvolvimento primeiro
- ✅ Faça backup antes de executar em produção
- ✅ Execute em horário de baixo tráfego
- ✅ Monitore o sistema durante e após a execução
- ✅ Teste todas as funcionalidades após criar os índices

---

**Última atualização:** 2025-01-27  
**Versão:** 1.0

