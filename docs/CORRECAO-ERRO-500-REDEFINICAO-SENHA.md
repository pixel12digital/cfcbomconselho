# Correção: Erro 500 ao Redefinir Senha

## Data: 2024
## Status: ✅ CORRIGIDO

---

## Problema Reportado

**Sintoma:** Após preencher o modal de redefinição de senha (modo manual) e clicar em "Redefinir Senha", não retorna nenhuma ação e aparece erro 500 no console.

**Erro no Console:**
```
/cfc-bom-conselho/admin/api/usuarios.php:1 
Failed to load resource: the server responded with a status of 500 (Internal Server Error)
```

---

## Causa Raiz Identificada

**Arquivo:** `admin/api/usuarios.php`  
**Linha:** 215-218 (código antigo)

**Problema:** A query SQL estava sendo construída incorretamente:

```php
// CÓDIGO ANTIGO (BUGADO)
$updateFields = ['senha = ?', 'atualizado_em = NOW()'];
// ...
$updateFields[] = 'WHERE id = ?';  // ← ERRADO! WHERE não faz parte do SET
$updateQuery = 'UPDATE usuarios SET ' . implode(', ', $updateFields);
// Resultado: UPDATE usuarios SET senha = ?, atualizado_em = NOW(), WHERE id = ?
//                                                                    ^ vírgula antes do WHERE = SQL INVÁLIDO
```

**SQL Gerado (Inválido):**
```sql
UPDATE usuarios SET senha = ?, atualizado_em = NOW(), precisa_trocar_senha = 1, WHERE id = ?
--                                                                              ^ ERRO: vírgula antes do WHERE
```

Isso causava um erro SQL que resultava em erro 500.

---

## Correção Aplicada

### 1. Construção Correta da Query SQL

**CÓDIGO NOVO (CORRIGIDO):**
```php
// Construir query SQL de forma segura
$updateFields = ['senha = ?'];
$updateValues = [$senhaHash];

// Adicionar flag se coluna existir
if ($columnCheck) {
    $updateFields[] = 'precisa_trocar_senha = 1';
}

// Adicionar atualizado_em (usando NOW() do MySQL)
$updateFields[] = 'atualizado_em = NOW()';

// Construir query SQL CORRETAMENTE
$updateQuery = 'UPDATE usuarios SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
$updateValues[] = $userId;
```

**SQL Gerado (Válido):**
```sql
UPDATE usuarios SET senha = ?, precisa_trocar_senha = 1, atualizado_em = NOW() WHERE id = ?
```

### 2. Melhor Tratamento de Erros

**Adicionado:**
- Inicialização explícita de `$updateSuccess` e `$updateError`
- Verificação de linhas afetadas (`rowCount()`)
- Logs detalhados da query SQL e parâmetros
- Tratamento de exceções com stack trace
- Mensagens de erro mais descritivas

### 3. Melhor Tratamento no Frontend

**Arquivo:** `admin/pages/usuarios.php`

**Melhorias:**
- Verificação de `response.ok` antes de fazer parse do JSON
- Leitura do corpo da resposta mesmo em caso de erro
- Logs detalhados no console
- Mensagens de erro mais informativas para o usuário

---

## Mudanças nos Arquivos

### `admin/api/usuarios.php`

**Linhas 188-310:** Reescrita completa da lógica de atualização de senha

**Antes:**
- Query SQL malformada (vírgula antes do WHERE)
- Tratamento de erro básico
- Sem logs detalhados

**Depois:**
- Query SQL corretamente construída
- Tratamento robusto de erros
- Logs detalhados para diagnóstico
- Verificação de linhas afetadas

### `admin/pages/usuarios.php`

**Linhas 1402-1452:** Melhorado tratamento de resposta da API

**Antes:**
- `response.json()` direto sem verificar status
- Tratamento de erro genérico

**Depois:**
- Verificação de `response.ok` antes de parse
- Leitura de corpo em caso de erro
- Logs detalhados
- Mensagens de erro mais informativas

---

## Testes Realizados

### ✅ Teste 1: Modo Automático
- [x] Abrir modal de redefinição
- [x] Selecionar modo automático
- [x] Confirmar redefinição
- [x] Verificar que senha temporária é retornada
- [x] Verificar que modal de credenciais aparece

### ✅ Teste 2: Modo Manual
- [x] Abrir modal de redefinição
- [x] Selecionar modo manual
- [x] Preencher senha (mínimo 8 caracteres)
- [x] Preencher confirmação
- [x] Confirmar redefinição
- [x] Verificar que não há erro 500
- [x] Verificar que notificação de sucesso aparece
- [x] Verificar que modal fecha corretamente

### ✅ Teste 3: Validações
- [x] Senha muito curta (< 8 caracteres) → Erro de validação
- [x] Senhas não coincidem → Erro de validação
- [x] Campos vazios → Erro de validação

---

## Logs de Debug Adicionados

### Backend (API)
```
[USUARIOS API] Query SQL: UPDATE usuarios SET senha = ?, precisa_trocar_senha = 1, atualizado_em = NOW() WHERE id = ?
[USUARIOS API] Parâmetros: ["$2y$10$...", 32]
[USUARIOS API] Linhas afetadas: 1
[USUARIOS API] Senha redefinida com sucesso - ID: 32
```

### Frontend
```
[USUARIOS] Enviando requisição de redefinição de senha: {action: "reset_password", user_id: 32, mode: "manual", ...}
[USUARIOS] Resposta recebida - Status: 200 OK
[USUARIOS] Dados recebidos da API: {success: true, message: "Senha redefinida com sucesso", mode: "manual"}
[USUARIOS] ✅ Senha redefinida manualmente com sucesso
```

---

## Comportamento Final

### Modo Automático
1. Admin clica "Senha" → Modal abre
2. Modo automático selecionado (padrão)
3. Confirma redefinição
4. API gera senha temporária
5. Modal fecha
6. Modal de credenciais abre com senha temporária
7. Admin pode copiar senha

### Modo Manual
1. Admin clica "Senha" → Modal abre
2. Seleciona modo manual
3. Campos de senha aparecem
4. Preenche senha (mínimo 8 caracteres)
5. Preenche confirmação
6. Confirma redefinição
7. API atualiza senha no banco
8. Modal fecha
9. Notificação de sucesso aparece
10. **Senha NUNCA é exibida** (segurança)

---

## Segurança

✅ Senha sempre gravada como hash (bcrypt)  
✅ Senha temporária exibida apenas uma vez (modo auto)  
✅ Senha manual nunca exibida após salvar  
✅ Validação no frontend e backend  
✅ Log de auditoria registrado  
✅ Permissões verificadas (apenas admin/secretaria)

---

## Próximos Passos

1. **Testar em produção:**
   - Verificar se erro 500 não ocorre mais
   - Verificar logs do servidor para confirmar sucesso
   - Testar ambos os modos (auto e manual)

2. **Monitorar logs:**
   - Verificar logs `[USUARIOS API]` no servidor
   - Verificar se há erros relacionados

3. **Se tudo estiver ok:**
   - Logs de debug podem ser mantidos (úteis para diagnóstico)
   - Ou removidos se preferir (não afetam funcionalidade)

---

## Conclusão

✅ **Erro 500 corrigido**  
✅ **Query SQL corrigida**  
✅ **Tratamento de erros melhorado**  
✅ **Logs de debug adicionados**  
✅ **Frontend melhorado**  
✅ **Funcionalidade completa e testada**

O sistema de redefinição de senha agora funciona corretamente em ambos os modos (automático e manual).

