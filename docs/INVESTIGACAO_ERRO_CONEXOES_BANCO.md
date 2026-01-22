# 🔍 Investigação: Erro de Limite de Conexões do Banco de Dados

**Data:** 2025-01-16  
**Erro:** `SQLSTATE[HY000] [1226] User 'u502697186_cfcbomconselho' has exceeded the 'max_connections_per_hour' resource (current value: 500)`  
**Localização:** `admin/index.php` linha 15 → `includes/database.php` linha 78

---

## 📊 Análise Completa

### ✅ **1. PWA NÃO está causando o problema**

#### **APIs chamadas pelo PWA:**
- ✅ `pwa/install-footer.js` linha 950: `fetch(manifestUrl)` - **Apenas busca manifest.json (arquivo estático, sem banco)**
- ✅ `pwa/sw.js`: `fetch()` para cache de recursos estáticos - **Não acessa banco**

**Conclusão:** O PWA **não faz nenhuma chamada de API que acesse o banco de dados**. Ele apenas:
- Busca manifest.json (arquivo estático)
- Cacheia recursos via Service Worker (arquivos estáticos)
- Não há polling ou requisições repetidas

---

### ❌ **2. Problema está no Painel Administrativo**

#### **Fluxo de Conexões em `admin/index.php`:**

```php
// admin/index.php linha 13-15
require_once '../includes/config.php';      // Não cria conexão
require_once '../includes/database.php';     // Define classe Database
require_once '../includes/auth.php';         // Linha 1155: $auth = new Auth()
```

**Problema identificado:**

1. **`includes/auth.php` linha 1155:**
   ```php
   $auth = new Auth();
   ```
   - O construtor de `Auth` chama `db()` (linha 1155)
   - `db()` chama `Database::getInstance()` (linha 718)
   - **Cria conexão #1**

2. **`admin/index.php` linha 36:**
   ```php
   $db = Database::getInstance();
   ```
   - Mesmo usando Singleton, se a instância foi resetada ou se há múltiplas requisições simultâneas, pode criar conexão adicional
   - **Potencial conexão #2**

3. **Cada API chamada via AJAX:**
   - `admin/api/salas-clean.php` → `Database::getInstance()` → **Nova conexão por requisição**
   - `admin/api/disciplinas-automaticas.php` → `Database::getInstance()` → **Nova conexão por requisição**
   - `admin/api/instrutor-aulas.php` → `Database::getInstance()` → **Nova conexão por requisição**
   - E assim por diante...

#### **Problema do Singleton:**
O Singleton funciona **por requisição PHP**, não globalmente:
- Cada requisição HTTP = novo processo PHP = nova instância Singleton
- Se houver 500 requisições em 1 hora, serão 500 conexões

---

## 🔍 Causas Prováveis

### **Causa 1: Múltiplas Requisições Simultâneas**
- Usuários acessando o admin simultaneamente
- Cada requisição cria uma conexão
- 500 conexões/hora = ~8 conexões/minuto = limite atingido rapidamente

### **Causa 2: Polling ou Auto-refresh**
- Verificar se há `setInterval` ou `setTimeout` fazendo requisições periódicas
- **Encontrado:** `admin/pages/turmas-teoricas.php` linha 2010-2013:
  ```javascript
  setTimeout(function() {
      atualizarTotalHorasRegressivo();
  }, 1000);
  ```
  - Se essa função fizer requisições AJAX, pode estar gerando muitas conexões

### **Causa 3: APIs sendo chamadas em loop**
- Verificar se há funções JavaScript que fazem polling
- **Encontrado:** `admin/index.php` linha 3537 e `admin/pages/turmas-teoricas.php` linha 5349:
  ```javascript
  fetch(getBasePath() + '/admin/api/salas-clean.php?action=listar')
  ```
  - Se essas funções forem chamadas repetidamente, geram muitas conexões

### **Causa 4: Conexões não sendo fechadas adequadamente**
- O PHP fecha conexões automaticamente ao final do script
- **MAS:** Se houver erros ou exceções, a conexão pode ficar "pendurada" até timeout
- Timeout padrão do MySQL: 8 horas (muito longo)

### **Causa 5: Reconexões automáticas**
- `includes/database.php` linha 83-102: método `reconnect()`
- Se houver falhas de conexão, o sistema tenta reconectar
- Cada reconexão conta como nova conexão

---

## 📋 APIs que Acessam o Banco (Identificadas)

### **APIs chamadas pelo Admin:**
1. `admin/api/salas-clean.php` → `Database::getInstance()`
2. `admin/api/disciplinas-automaticas.php` → `Database::getInstance()`
3. `admin/api/instrutor-aulas.php` → `Database::getInstance()`
4. `admin/api/notificacoes.php` → `Database::getInstance()`
5. `admin/api/solicitacoes.php` → `Database::getInstance()`
6. `admin/api/cfcs.php` → `Database::getInstance()`
7. E muitas outras...

### **APIs chamadas pelo Aluno/Instrutor:**
1. `admin/api/notificacoes.php` (chamado de `aluno/notificacoes.php` e `instrutor/notificacoes.php`)
2. `admin/api/solicitacoes.php` (chamado de `aluno/dashboard.php`)
3. `admin/api/instrutor-aulas.php` (chamado de `instrutor/dashboard.php`)

**Todas essas APIs criam uma nova conexão via `Database::getInstance()`**

---

## 🔧 Soluções Recomendadas

### **Solução 1: Aguardar Reset do Contador (Imediato)**
- O contador de conexões por hora reseta automaticamente
- Aguardar 1 hora para o limite resetar
- **Temporário:** Não resolve o problema de raiz

### **Solução 2: Aumentar Limite na Hostinger (Curto Prazo)**
- Contatar suporte da Hostinger
- Solicitar aumento de `max_connections_per_hour` de 500 para 2000 ou mais
- **Custo:** Pode haver custo adicional dependendo do plano

### **Solução 3: Implementar Connection Pooling (Médio Prazo)**
- Usar `PDO::ATTR_PERSISTENT => true` (já está `false` na linha 36)
- **Problema:** Persistent connections podem causar problemas em shared hosting
- **Alternativa:** Implementar pool de conexões reutilizáveis

### **Solução 4: Otimizar Código para Reutilizar Conexões (Médio Prazo)**
- Garantir que `Database::getInstance()` realmente reutiliza a mesma instância
- Verificar se não há múltiplas chamadas desnecessárias
- Adicionar logging para rastrear criação de conexões

### **Solução 5: Reduzir Polling/Auto-refresh (Médio Prazo)**
- Remover ou aumentar intervalo de `setTimeout`/`setInterval`
- Usar WebSockets ou Server-Sent Events em vez de polling
- Implementar debounce em funções que fazem requisições AJAX

### **Solução 6: Implementar Cache de Resultados (Longo Prazo)**
- Cachear resultados de APIs que não mudam frequentemente
- Reduzir número de requisições ao banco
- Usar Redis ou Memcached (se disponível)

### **Solução 7: Fechar Conexões Explicitamente (Boa Prática)**
- Adicionar `register_shutdown_function()` para garantir fechamento
- Fechar conexões após uso em APIs
- **Nota:** PHP fecha automaticamente, mas explícito é melhor

---

## 🎯 Ações Imediatas Recomendadas

### **1. Verificar se há Polling Ativo**
```javascript
// Procurar no console do navegador:
// - setInterval
// - setTimeout em loop
// - Requisições AJAX repetidas
```

### **2. Adicionar Logging de Conexões**
```php
// Em includes/database.php, adicionar:
private function connect() {
    error_log('[DB] Nova conexão criada: ' . date('Y-m-d H:i:s'));
    // ... resto do código
}
```

### **3. Monitorar Requisições**
- Verificar logs do servidor (se disponível)
- Contar quantas requisições estão sendo feitas por minuto
- Identificar picos de tráfego

### **4. Verificar se há Erros que Impedem Fechamento**
- Verificar logs de erro do PHP
- Verificar se há exceções não tratadas que impedem fechamento de conexões

---

## 📊 Estatísticas Estimadas

### **Cenário 1: Uso Normal**
- 10 usuários simultâneos
- Cada usuário faz 5 requisições/minuto
- **Total:** 50 requisições/minuto = 3000 requisições/hora
- **Conexões:** 3000 conexões/hora ❌ **EXCEDE LIMITE**

### **Cenário 2: Com Polling**
- 5 usuários com auto-refresh a cada 5 segundos
- Cada refresh = 3 requisições AJAX
- **Total:** 5 × 12 × 3 = 180 requisições/minuto = 10.800 requisições/hora
- **Conexões:** 10.800 conexões/hora ❌ **MUITO ACIMA DO LIMITE**

### **Cenário 3: Uso Otimizado**
- 10 usuários simultâneos
- Cache de resultados
- Sem polling desnecessário
- **Total:** ~100 requisições/hora
- **Conexões:** ~100 conexões/hora ✅ **DENTRO DO LIMITE**

---

## 🔍 Próximos Passos de Investigação

1. **Adicionar logging de conexões** para rastrear quando são criadas
2. **Verificar logs do servidor** para identificar picos de tráfego
3. **Analisar código JavaScript** para encontrar polling/auto-refresh
4. **Monitorar por 24 horas** para identificar padrões
5. **Implementar soluções progressivas** conforme identificado

---

## 📝 Conclusão

### **PWA:**
- ✅ **NÃO está causando o problema**
- Apenas busca arquivos estáticos (manifest.json)
- Não faz requisições que acessam banco de dados

### **Painel Administrativo:**
- ❌ **É a causa provável do problema**
- Múltiplas APIs criando conexões
- Possível polling/auto-refresh gerando muitas requisições
- Cada requisição = nova conexão (mesmo com Singleton)

### **Solução Recomendada:**
1. **Imediato:** Aguardar reset do contador (1 hora)
2. **Curto Prazo:** Solicitar aumento de limite na Hostinger
3. **Médio Prazo:** Reduzir polling e otimizar código
4. **Longo Prazo:** Implementar cache e connection pooling

---

**Última atualização:** 2025-01-16  
**Status:** Aguardando implementação de logging para diagnóstico preciso
