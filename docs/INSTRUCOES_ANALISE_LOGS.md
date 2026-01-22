# 📊 Instruções para Análise de Logs de Conexões

**Status Atual:** Logs ainda não foram gerados (sistema precisa ser usado primeiro)

---

## 🔍 Como Gerar Logs para Análise

### **Passo 1: Usar o Sistema**
1. Acesse o painel admin: `https://cfcbomconselho.com.br/admin/`
2. Navegue por diferentes páginas por 10-15 minutos
3. Faça ações típicas:
   - Abrir modais
   - Criar/editar registros
   - Fazer requisições AJAX
   - Deixar páginas abertas (para detectar polling)

### **Passo 2: Verificar se Logs Foram Criados**
O arquivo deve estar em: `storage/logs/db_connections.jsonl`

---

## 📋 Métodos de Análise

### **Método 1: Script de Análise Automática (Recomendado)**

**Via Navegador:**
1. Acesse: `https://cfcbomconselho.com.br/admin/tools/analisar_logs_conexoes.php`
2. O script mostrará análise completa automaticamente

**Via CLI (se tiver acesso SSH):**
```bash
php admin/tools/analisar_logs_conexoes.php
```

### **Método 2: Relatório Visual**

1. Acesse: `https://cfcbomconselho.com.br/admin/tools/db_connections_report.php`
2. Visualize gráficos e tabelas interativas

### **Método 3: Análise Manual (JSONL)**

Se tiver acesso ao arquivo:
```bash
# Ver últimas 100 linhas
tail -n 100 storage/logs/db_connections.jsonl

# Contar total
wc -l storage/logs/db_connections.jsonl

# Filtrar por URI específica
grep "/admin/api/notificacoes.php" storage/logs/db_connections.jsonl | wc -l
```

---

## 📊 O Que o Script de Análise Mostra

O script `analisar_logs_conexoes.php` gera automaticamente:

1. **Período Coberto** - Timestamp inicial/final e duração
2. **Top 20 URIs** - Por número de conexões, com % e conexões/minuto
3. **Top 10 IPs** - IPs que mais geram conexões
4. **Top 10 User-Agents** - Navegadores/dispositivos
5. **Timeline** - Conexões por minuto (últimos 30 min)
6. **Reconexões** - URIs com mais reconexões (instabilidade)
7. **Duplas Conexões** - Requests com múltiplas conexões
8. **Padrões Detectados:**
   - **Polling** - Frequência > 1/min com intervalo estimado
   - **Explosões** - Picos > 2x a média
9. **Conclusão** - Top 3 culpados com evidências numéricas

---

## 🎯 Formato de Saída Esperado

Quando os logs estiverem disponíveis, o script mostrará algo como:

```
📊 ANÁLISE DE LOGS DE CONEXÕES
================================================================================

📅 PERÍODO COBERTO:
   Início: 2025-01-16 14:00:00
   Fim:    2025-01-16 14:30:00
   Duração: 30.0 minutos
   Total de entradas: 1250

🔝 TOP 20 REQUEST_URI POR CONEXÕES:
--------------------------------------------------------------------------------
 1. /admin/api/notificacoes.php                    |   450 conexões ( 36.0%) | ~15.00/min
 2. /admin/index.php                               |   320 conexões ( 25.6%) | ~10.67/min
 3. /admin/api/salas-clean.php?action=listar       |   180 conexões ( 14.4%) | ~ 6.00/min
...

🎯 CONCLUSÃO - TOP 3 CULPADOS PROVÁVEIS:
================================================================================

1. /admin/api/notificacoes.php
   📊 450 conexões em 30.0 minutos (~15.00 conexões/minuto)
   🌐 IP mais comum: 177.xxx.xxx.xxx (450 conexões)
   📱 UA mais comum: Mozilla/5.0 (Android; Mobile)...
   ⚠️ PADRÃO: Polling detectado (~4s de intervalo)
```

---

## ⚠️ Status Atual

**Arquivo de log:** `storage/logs/db_connections.jsonl`  
**Status:** ❌ Não encontrado (logs ainda não foram gerados)

**Próximo passo:** Usar o sistema por alguns minutos para gerar logs, depois executar a análise.

---

**Última verificação:** 2025-01-16



