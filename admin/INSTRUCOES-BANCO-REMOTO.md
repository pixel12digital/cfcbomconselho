# 🌐 Instruções para Banco Remoto

## ✅ **Scripts Atualizados para Banco Remoto**

Todos os scripts foram atualizados para usar as configurações do banco remoto do `config.php`:

### 📡 **Configurações do Banco Remoto:**
- **Host:** `auth-db803.hstgr.io`
- **Database:** `u502697186_cfcbomconselho`
- **User:** `u502697186_cfcbomconselho`
- **Password:** `Los@ngo#081081`

---

## 🚀 **PASSO A PASSO PARA CORRIGIR OS SELECTS**

### **PASSO 1: Executar Migração**
Acesse no navegador:
```
http://localhost/cfc-bom-conselho/admin/migracao-remoto.php
```

**Este script irá:**
- ✅ Conectar ao banco remoto
- ✅ Verificar se a tabela `turmas_disciplinas` existe
- ✅ Criar a tabela se necessário
- ✅ Mostrar estrutura e dados
- ✅ Listar turmas existentes

### **PASSO 2: Criar Turma com Disciplinas**
1. Acesse: `http://localhost/cfc-bom-conselho/admin/?page=turmas-teoricas&acao=nova&step=1`
2. Preencha:
   - Nome da turma: `Teste Disciplinas 2024`
   - Selecione curso: `Formação - 45h`
3. Adicione algumas disciplinas
4. Clique em "Criar Turma"
5. **Anote o ID da turma criada**

### **PASSO 3: Testar Etapa 2**
Acesse (substitua `X` pelo ID da turma criada):
```
http://localhost/cfc-bom-conselho/admin/?page=turmas-teoricas&acao=agendar&step=2&turma_id=X
```

**Resultado esperado:**
- ✅ Select "Disciplina" deve mostrar as disciplinas adicionadas
- ✅ Select "Instrutor" deve carregar normalmente
- ✅ Console deve estar limpo

---

## 🔧 **Scripts Disponíveis**

### 1. **migracao-remoto.php** ⭐ **RECOMENDADO**
- Script mais simples e direto
- Conecta ao banco remoto automaticamente
- Mostra informações úteis

### 2. **teste-conexao-migracao.php**
- Teste detalhado de conexão
- Verifica estrutura da tabela
- Mostra dados existentes

### 3. **popular-disciplinas-teste.php**
- Adiciona disciplinas de teste
- Cria turma se necessário
- Útil para testes

### 4. **executar-migracao-disciplinas.php**
- Script original (pode ter problemas de autenticação)

---

## 🐛 **Se Houver Problemas**

### **Erro de Conexão:**
```
❌ ERRO de conexão: SQLSTATE[HY000] [2002] Connection refused
```

**Possíveis causas:**
1. 🔥 Firewall bloqueando conexão
2. 🌐 Problemas de conectividade
3. ⏰ Timeout de conexão
4. 🔑 Credenciais incorretas

**Soluções:**
1. Verificar se está conectado à internet
2. Tentar novamente em alguns minutos
3. Verificar se o banco está online

### **Erro de Permissão:**
```
❌ ERRO: Access denied for user
```

**Solução:**
- Verificar se as credenciais no `config.php` estão corretas
- Verificar se o usuário tem permissão para criar tabelas

### **Tabela Não Cria:**
```
❌ Tabela não foi criada
```

**Solução:**
- Verificar se o usuário tem permissão `CREATE TABLE`
- Verificar se não há conflitos de nomes

---

## 📋 **Checklist de Validação**

### ✅ **Migração Executada:**
- [ ] Script executado sem erros
- [ ] Tabela `turmas_disciplinas` criada
- [ ] Estrutura da tabela correta
- [ ] Conexão com banco remoto funcionando

### ✅ **Turma Criada:**
- [ ] Turma criada na etapa 1
- [ ] Disciplinas adicionadas
- [ ] Turma salva com sucesso
- [ ] ID da turma anotado

### ✅ **Etapa 2 Funcionando:**
- [ ] Select "Disciplina" populado
- [ ] Select "Instrutor" populado
- [ ] Console sem erros
- [ ] Interface carregando completamente

---

## 🎯 **Fluxo de Teste Completo**

1. **Execute:** `migracao-remoto.php` ✅
2. **Crie turma:** Etapa 1 com disciplinas ✅
3. **Teste:** Etapa 2 com turma criada ✅
4. **Verifique:** Selects populados ✅
5. **Confirme:** Sem erros no console ✅

---

## 📞 **Suporte**

Se encontrar problemas:
1. ✅ Execute primeiro `migracao-remoto.php`
2. ✅ Verifique se a tabela foi criada
3. ✅ Teste com uma turma nova
4. ✅ Verifique console do navegador (F12)

**Me informe o resultado de cada passo!** 🚀

---

**Última Atualização:** Outubro 2024
**Ambiente:** Banco Remoto (Hostinger)
**Status:** ✅ Pronto para Teste
