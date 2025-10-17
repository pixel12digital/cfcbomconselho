# 🧪 Instruções para Testar o Contador Regressivo

## 🎯 **Como Testar o Sistema**

### 1. **Abrir a Página Principal**
```
http://localhost/cfc-bom-conselho/admin/?page=turmas-teoricas&acao=nova&step=1
```

### 2. **Abrir o Console do Navegador**
- Pressione `F12` ou `Ctrl+Shift+I`
- Vá para a aba "Console"

### 3. **Executar Testes no Console**

#### **Teste 1: Verificar se a função existe**
```javascript
testarContadorRegressivo()
```
**Resultado esperado:** Deve mostrar informações sobre a função e elementos encontrados.

#### **Teste 2: Forçar atualização do contador**
```javascript
forcarAtualizacaoContador()
```
**Resultado esperado:** Deve executar múltiplas atualizações do contador.

#### **Teste 3: Testar manualmente**
```javascript
// 1. Selecionar um curso
document.getElementById('curso_tipo').value = 'formacao_45h';

// 2. Executar contador regressivo
atualizarTotalHorasRegressivo();

// 3. Verificar resultado
console.log('Total de horas:', document.getElementById('total-horas-disciplinas').textContent);
```

### 4. **Teste Visual na Interface**

1. **Selecionar "Curso de formação de condutores - Permissão 45h"**
   - ✅ Total de horas deve mostrar: **45h**

2. **Adicionar disciplina "Legislação de Trânsito"**
   - ✅ Total de horas deve mostrar: **27h** (45h - 18h)

3. **Adicionar disciplina "Direção Defensiva"**
   - ✅ Total de horas deve mostrar: **11h** (27h - 16h)

4. **Adicionar mais disciplinas**
   - ✅ Total deve continuar diminuindo até chegar a **0h**

---

## 🔧 **Arquivos de Teste Criados**

### 1. **Teste Simples**
- `admin/debug-contador-regressivo.html`
- Teste isolado do contador regressivo
- Interface visual para testar

### 2. **Teste Completo**
- `admin/teste-contador-regressivo.html`
- Teste completo com todos os cenários
- Validação automática

---

## 🐛 **Se Não Estiver Funcionando**

### **Verificar no Console:**
1. Abrir Console (F12)
2. Executar: `testarContadorRegressivo()`
3. Verificar se há erros

### **Possíveis Problemas:**
1. **Função não encontrada** → Verificar se o JavaScript foi carregado
2. **Elementos não encontrados** → Verificar se os IDs estão corretos
3. **Curso não selecionado** → Selecionar um curso primeiro

### **Comandos de Debug:**
```javascript
// Verificar se elementos existem
console.log('curso_tipo:', document.getElementById('curso_tipo'));
console.log('total-horas-disciplinas:', document.getElementById('total-horas-disciplinas'));

// Verificar função
console.log('atualizarTotalHorasRegressivo:', typeof atualizarTotalHorasRegressivo);

// Executar manualmente
atualizarTotalHorasRegressivo();
```

---

## 📊 **Comportamento Esperado**

| Situação | Total Exibido | Cor |
|----------|---------------|-----|
| **Nenhum curso selecionado** | 0h | Azul |
| **Curso 45h selecionado** | 45h | Azul |
| **Curso 45h + 1 disciplina (18h)** | 27h | Azul |
| **Curso 45h + 2 disciplinas (34h)** | 11h | Amarelo |
| **Curso completo (0h restantes)** | 0h | Verde |

---

## ✅ **Sinais de Sucesso**

- ✅ Total de horas muda quando curso é selecionado
- ✅ Total diminui quando disciplina é adicionada
- ✅ Total aumenta quando disciplina é removida
- ✅ Cores mudam conforme status (azul/amarelo/verde)
- ✅ Console mostra logs de debug
- ✅ Funções globais respondem corretamente

---

## 🆘 **Se Ainda Não Funcionar**

1. **Verificar arquivo:** `admin/pages/turmas-teoricas.php`
2. **Procurar por:** `function atualizarTotalHorasRegressivo`
3. **Verificar se:** A função está sendo chamada no carregamento
4. **Executar:** `forcarAtualizacaoContador()` no console
5. **Relatar:** Erros específicos encontrados no console

**O sistema deve funcionar como contador regressivo!** 🎯
