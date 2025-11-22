# 🔍 Investigação de Duplicação de Usuário - ROBERIO SANTOS MACHADO

## 📋 Análise do Código Realizada

### 1. Verificação do Banco de Dados

**⚠️ AÇÃO NECESSÁRIA:** Execute estas queries no phpMyAdmin e me envie os resultados:

#### Query 1: Buscar usuário específico
```sql
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    criado_em,
    atualizado_em
FROM usuarios
WHERE nome LIKE 'ROBERIO SANTOS MACHADO%'
ORDER BY id;
```

#### Query 2: Verificar emails duplicados
```sql
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo
FROM usuarios
WHERE email IN (
    SELECT email 
    FROM usuarios 
    GROUP BY email 
    HAVING COUNT(*) > 1
)
ORDER BY email, id;
```

**O que preciso saber:**
- ✅ Existem duas linhas distintas na tabela `usuarios` para o ROBERIO?
- ✅ Os `id` são diferentes?
- ✅ O `email` é o mesmo nos dois registros ou não?
- ✅ Há diferenças em `tipo`, `ativo`, `criado_em`?

---

### 2. Análise da API (`admin/api/usuarios.php`)

**Query usada para listar usuários (linha 79):**
```php
$usuarios = $db->fetchAll("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios ORDER BY nome");
```

**Análise:**
- ✅ Query **SIMPLES**, sem JOINs
- ✅ Não há agregações ou GROUP BY que possam causar duplicação
- ✅ A query retorna exatamente o que está no banco

**Conclusão:** Se a API retornar duplicado, é porque há **duas linhas no banco**.

**⚠️ AÇÃO NECESSÁRIA:** Adicione este log temporário na API para verificar:

```php
// admin/api/usuarios.php, linha 79, após o fetchAll:
error_log('[DEBUG USUARIOS API] Total de registros: ' . count($usuarios));
error_log('[DEBUG USUARIOS API] IDs encontrados: ' . json_encode(array_column($usuarios, 'id')));
```

---

### 3. Análise do Front-End (`admin/pages/usuarios.php`)

#### 🔴 PROBLEMA IDENTIFICADO: Dois `foreach` renderizando usuários!

**Localização 1:** Linha 484 (Tabela Desktop)
```php
<?php foreach ($usuarios as $usuario): ?>
    <tr>
        <td>
            <div class="font-weight-semibold"><?php echo htmlspecialchars($usuario['nome']); ?></div>
        </td>
        <!-- ... resto da linha ... -->
    </tr>
<?php endforeach; ?>
```

**Localização 2:** Linha 544 (Cards Mobile)
```php
<?php foreach ($usuarios as $usuario): ?>
    <div class="mobile-user-card">
        <!-- ... conteúdo do card ... -->
    </div>
<?php endforeach; ?>
```

**Análise:**
- ✅ Ambos os `foreach` iteram sobre o **mesmo array** `$usuarios`
- ✅ A tabela desktop está dentro de `.table-container` (linha 472)
- ✅ Os cards mobile estão dentro de `.mobile-user-cards` (linha 537)
- ✅ Por padrão, os cards mobile estão com `display: none` (linha 537)

**⚠️ POSSÍVEL CAUSA:** Se ambos os containers estiverem visíveis ao mesmo tempo, o usuário aparecerá duas vezes na tela.

**Verificação necessária:**
1. Abra o console do navegador (F12)
2. Execute: `document.querySelectorAll('.mobile-user-cards').forEach(el => console.log('Display:', window.getComputedStyle(el).display))`
3. Execute: `document.querySelectorAll('.table-container').forEach(el => console.log('Display:', window.getComputedStyle(el).display))`

**Se ambos estiverem visíveis:** Esse é o problema! A correção seria garantir que apenas um esteja visível por vez.

---

### 4. Verificação de Criação Automática de Usuários

**Sistema de Matrícula (`admin/includes/sistema_matricula.php`):**
- ✅ Quando um aluno é cadastrado, o sistema chama `CredentialManager::createStudentCredentials()`
- ✅ Esta função verifica se o email já existe antes de criar (linha 82)
- ✅ Se existir e for tipo 'aluno', retorna sucesso sem criar duplicado (linha 87-95)

**CredentialManager (`includes/CredentialManager.php`):**
- ✅ `createStudentCredentials()` tem proteção contra duplicação por email (linha 82-103)
- ✅ `createEmployeeCredentials()` **NÃO** verifica duplicação antes de inserir (linha 35-70)

**⚠️ POSSÍVEL CAUSA:** Se o ROBERIO foi criado manualmente como usuário tipo 'aluno' e depois foi cadastrado como aluno, pode ter havido:
1. Criação manual via interface de usuários
2. Criação automática via cadastro de aluno (se o email for diferente)

**⚠️ AÇÃO NECESSÁRIA:** Verifique se existe registro na tabela `alunos`:

```sql
SELECT 
    id,
    nome,
    cpf,
    status,
    email
FROM alunos
WHERE nome LIKE 'ROBERIO SANTOS MACHADO%'
ORDER BY id;
```

---

### 5. Verificação de JavaScript

**Análise do JavaScript (`admin/pages/usuarios.php`, linha 808+):**
- ✅ Não há fetch automático de usuários no carregamento da página
- ✅ A tabela é renderizada **diretamente no PHP** (linha 484)
- ✅ JavaScript só é usado para: criar, editar, excluir, exportar
- ✅ Não há `DOMContentLoaded` ou `window.onload` que carregue usuários

**Conclusão:** O JavaScript **NÃO** está causando duplicação na listagem inicial.

---

## 🎯 Diagnóstico Preliminar

### Cenários Possíveis (em ordem de probabilidade):

#### 1. 🔴 **Duplicação no Banco de Dados** (MAIS PROVÁVEL)
- Duas linhas distintas na tabela `usuarios`
- Pode ter sido criado manualmente e depois automaticamente
- Ou criado duas vezes manualmente

#### 2. 🟡 **Duplicação Visual no Front-End** (POSSÍVEL)
- Ambos os containers (tabela desktop + cards mobile) visíveis simultaneamente
- Mesmo usuário aparecendo duas vezes na tela, mas só uma vez no banco

#### 3. 🟢 **Duplicação na API** (IMPROVÁVEL)
- Query é simples, sem JOINs
- Se duplicar, é porque o banco tem duplicado

---

## 📝 Próximos Passos (Checklist)

Execute na seguinte ordem:

### ✅ Passo 1: Verificar Banco de Dados
- [ ] Execute Query 1 e me envie o resultado
- [ ] Execute Query 2 e me envie o resultado
- [ ] Execute Query de alunos e me envie o resultado

### ✅ Passo 2: Verificar Front-End
- [ ] Abra a página `index.php?page=usuarios`
- [ ] Abra o console do navegador (F12)
- [ ] Execute os comandos JavaScript acima para verificar visibilidade dos containers
- [ ] Me informe se ambos estão visíveis

### ✅ Passo 3: Verificar API (se necessário)
- [ ] Adicione os logs temporários na API
- [ ] Recarregue a página de usuários
- [ ] Verifique os logs do servidor
- [ ] Me informe quantos registros a API retornou

---

## 🔧 Correções Propostas (AGUARDANDO DIAGNÓSTICO)

### Se for Duplicação no Banco:
1. Identificar qual registro manter (mais recente, mais completo)
2. Verificar dependências (sessões, logs, etc.)
3. Remover o registro duplicado
4. Adicionar constraint UNIQUE no email (se não existir)

### Se for Duplicação Visual:
1. Garantir que apenas um container esteja visível por vez
2. Adicionar lógica CSS/JS para alternar entre desktop e mobile
3. Verificar media queries

### Se for Duplicação na API:
1. Revisar query (improvável, mas possível se houver JOIN escondido)
2. Adicionar DISTINCT se necessário

---

## 📊 Resumo Técnico

| Item | Status | Observação |
|------|--------|------------|
| Query do Banco | ✅ Simples | `SELECT * FROM usuarios ORDER BY nome` |
| Query da API | ✅ Simples | `SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios ORDER BY nome` |
| Renderização PHP | ⚠️ Dois foreach | Tabela desktop + Cards mobile |
| JavaScript | ✅ Não carrega lista | Só para ações (criar/editar/excluir) |
| Proteção Duplicação | ⚠️ Parcial | Alunos sim, funcionários não |

---

**⚠️ IMPORTANTE:** Não altere nada no banco até termos o diagnóstico completo. Execute as queries acima e me envie os resultados para continuarmos a investigação.

