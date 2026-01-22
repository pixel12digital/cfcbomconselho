# Inventário Completo - Erro "Instrutor não encontrado" na API de Ocorrências

**Data:** 22/11/2025  
**Arquivo afetado:** `admin/api/ocorrencias-instrutor.php`  
**Página afetada:** `instrutor/ocorrencias.php`  
**Usuário afetado:** Carlos da Silva (usuario_id=44)

---

## 1. RESUMO EXECUTIVO

### Problema Identificado
A API `admin/api/ocorrencias-instrutor.php` retorna erro **404** com mensagem:
```
"Instrutor não encontrado. Verifique seu cadastro."
```

### Causa Raiz
O usuário com `id=44` existe na tabela `usuarios` com `tipo='instrutor'`, mas **não existe registro correspondente** na tabela `instrutores` com `usuario_id=44`.

### Status do Arquivo
✅ **Arquivo existe e está acessível**  
✅ **Autenticação funcionando**  
❌ **Registro faltante no banco de dados**

---

## 2. ANÁLISE DOS LOGS

### 2.1. Logs do PHP (`logs/php_errors.log`)

#### Padrão de Erro Repetido (29 ocorrências)
```
[22-Nov-2025 15:22:20 America/Sao_Paulo] [OCORRENCIAS_API] Verificando autenticação - session_id=4va093o6270ovrl4icj0pd27fh, user_id=44, session_status=2
[22-Nov-2025 15:22:20 America/Sao_Paulo] Query executada: {"timestamp":"2025-11-22 15:22:20","sql":"SELECT u.id, u.nome, u.email, u.tipo, u.cpf, u.telefone, u.ultimo_login, \r\n                       c.id as cfc_id, c.nome as cfc_nome, c.cnpj as cfc_cnpj\r\n                FROM usuarios u \r\n                LEFT JOIN cfcs c ON u.id = c.responsavel_id \r\n                WHERE u.id = :id LIMIT 1","params":{"id":44},"time":"0.0334s"}
[22-Nov-2025 15:22:20 America/Sao_Paulo] Query executada: {"timestamp":"2025-11-22 15:22:20","sql":"SELECT id FROM instrutores WHERE usuario_id = ?","params":[44],"time":"0.0316s"}
[22-Nov-2025 15:22:20 America/Sao_Paulo] [OCORRENCIAS_API] Instrutor não encontrado - usuario_id=44, tipo=instrutor, email=carlosteste@teste.com.br, timestamp=2025-11-22 15:22:20, ip=::1
```

#### Análise dos Logs
- **Autenticação:** ✅ Funcionando (session_id válido, user_id=44)
- **Query de usuário:** ✅ Retorna dados (nome, email, tipo='instrutor')
- **Query de instrutor:** ❌ Retorna vazio (nenhum registro com usuario_id=44)
- **Frequência:** 29 ocorrências entre 14:37:30 e 15:32:58

### 2.2. Logs do Apache (`C:\xampp\apache\logs\error.log`)

#### Erros Encontrados
- **404.php não encontrado:** Múltiplas ocorrências (problema secundário, não relacionado)
- **Nenhum erro 404 para `ocorrencias-instrutor.php`:** ✅ Arquivo está sendo encontrado

#### Conclusão
O arquivo `admin/api/ocorrencias-instrutor.php` **existe e está acessível**. O problema não é de roteamento ou arquivo faltante.

---

## 3. ANÁLISE DO CÓDIGO

### 3.1. Fluxo de Autenticação e Validação

#### Arquivo: `admin/api/ocorrencias-instrutor.php` (linhas 50-105)

```php
// 1. Verificar autenticação
$user = getCurrentUser();
if (!$user) {
    returnJsonError('Usuário não autenticado', 401);
}

// 2. Validar tipo de usuário
if ($user['tipo'] !== 'instrutor') {
    returnJsonError('Acesso negado. Apenas instrutores podem usar esta API.', 403);
}

// 3. Buscar instrutor_id
$instrutorId = getCurrentInstrutorId($user['id']);
if (!$instrutorId) {
    returnJsonError('Instrutor não encontrado. Verifique seu cadastro.', 404);
}
```

#### Arquivo: `includes/auth.php` (linhas 810-827)

```php
function getCurrentInstrutorId($userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        if (!$user) {
            return null;
        }
        $userId = $user['id'];
    }
    
    $db = db();
    $instrutor = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$userId]);
    
    if (!$instrutor) {
        return null;  // ← RETORNA NULL QUANDO NÃO ENCONTRA
    }
    
    return $instrutor['id'];
}
```

### 3.2. Query Executada

```sql
SELECT id FROM instrutores WHERE usuario_id = 44
```

**Resultado:** Nenhuma linha retornada (registro não existe)

---

## 4. ESTRUTURA DO BANCO DE DADOS

### 4.1. Tabela `usuarios`
- **ID:** 44
- **Nome:** Carlos da Silva
- **Email:** carlosteste@teste.com.br
- **Tipo:** `instrutor`
- **Status:** Ativo

### 4.2. Tabela `instrutores` (estrutura mínima)

```sql
CREATE TABLE IF NOT EXISTS instrutores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,           -- ← DEVE SER 44
    cfc_id INT NOT NULL,                -- ← OBRIGATÓRIO
    credencial VARCHAR(50) UNIQUE NOT NULL,  -- ← OBRIGATÓRIO
    nome VARCHAR(255),                  -- ← OPCIONAL (pode vir de usuarios)
    categoria_habilitacao VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
)
```

### 4.3. Campos Obrigatórios para Inserção
1. `usuario_id` (INT NOT NULL) - **44**
2. `cfc_id` (INT NOT NULL) - **Precisa buscar primeiro CFC disponível**
3. `credencial` (VARCHAR(50) UNIQUE NOT NULL) - **Gerar automaticamente**

### 4.4. Verificação no Banco
```sql
-- Verificar se usuário existe
SELECT id, nome, email, tipo FROM usuarios WHERE id = 44;
-- Resultado: ✅ Existe (Carlos da Silva, carlosteste@teste.com.br, instrutor)

-- Verificar se instrutor existe
SELECT id, nome, usuario_id, cfc_id, credencial FROM instrutores WHERE usuario_id = 44;
-- Resultado: ❌ Não existe (nenhum registro)
```

---

## 5. SOLUÇÃO PROPOSTA

### 5.1. Script SQL (`docs/scripts/criar-instrutor-carlos.sql`)

```sql
-- Verificar se já existe
SELECT id, nome, usuario_id FROM instrutores WHERE usuario_id = 44;

-- Inserir registro de instrutor
INSERT INTO instrutores (
    nome,
    usuario_id,
    cfc_id,
    credencial,
    ativo,
    criado_em
) VALUES (
    'Carlos da Silva',  -- Nome do instrutor
    44,                 -- usuario_id
    1,                  -- cfc_id (ajustar conforme necessário)
    'CRED-000044',      -- Credencial gerada
    1,                  -- ativo
    NOW()               -- criado_em
);

-- Verificar se foi criado
SELECT id, nome, usuario_id, cfc_id, credencial, ativo FROM instrutores WHERE usuario_id = 44;
```

### 5.2. Script PHP (`admin/criar-instrutor-carlos.php`)

**Funcionalidades:**
1. ✅ Verifica se usuário existe (ID 44)
2. ✅ Verifica se já existe registro em `instrutores`
3. ✅ Busca primeiro CFC disponível
4. ✅ Gera credencial única automaticamente
5. ✅ Cria registro em `instrutores`
6. ✅ Exibe relatório detalhado

**Execução:**
- **Via navegador:** `http://localhost/cfc-bom-conselho/admin/criar-instrutor-carlos.php`
- **Via CLI:** `php admin/criar-instrutor-carlos.php` (se PHP estiver no PATH)

**Problema reportado:** Não funciona via navegador (possível problema de autenticação ou permissões)

---

## 6. ALTERNATIVAS DE EXECUÇÃO

### 6.1. Via phpMyAdmin (Recomendado)
1. Acessar phpMyAdmin
2. Selecionar banco de dados `u502697186_cfcbomconselho`
3. Abrir aba "SQL"
4. Executar script SQL de `docs/scripts/criar-instrutor-carlos.sql`
5. Ajustar `cfc_id` se necessário (verificar primeiro CFC disponível)

### 6.2. Via CLI (se PHP estiver no PATH)
```bash
cd C:\xampp\htdocs\cfc-bom-conselho
php admin/criar-instrutor-carlos.php
```

### 6.3. SQL Direto (via phpMyAdmin ou cliente MySQL)

**Passo 1: Verificar CFC disponível**
```sql
SELECT id, nome FROM cfcs ORDER BY id LIMIT 1;
```

**Passo 2: Verificar se credencial já existe**
```sql
SELECT id FROM instrutores WHERE credencial = 'CRED-000044';
```

**Passo 3: Inserir registro**
```sql
INSERT INTO instrutores (
    nome,
    usuario_id,
    cfc_id,
    credencial,
    ativo,
    criado_em
) VALUES (
    'Carlos da Silva',
    44,
    1,  -- Substituir pelo cfc_id real
    'CRED-000044',
    1,
    NOW()
);
```

**Passo 4: Verificar criação**
```sql
SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, c.nome as cfc_nome 
FROM instrutores i 
LEFT JOIN usuarios u ON i.usuario_id = u.id 
LEFT JOIN cfcs c ON i.cfc_id = c.id 
WHERE i.usuario_id = 44;
```

---

## 7. VALIDAÇÃO PÓS-CORREÇÃO

### 7.1. Testes Necessários

1. **Verificar registro criado:**
   ```sql
   SELECT * FROM instrutores WHERE usuario_id = 44;
   ```

2. **Testar função `getCurrentInstrutorId()`:**
   - Fazer login como Carlos da Silva (usuario_id=44)
   - A função deve retornar o `id` do instrutor (não null)

3. **Testar API `ocorrencias-instrutor.php`:**
   - Acessar `instrutor/ocorrencias.php`
   - Tentar registrar uma ocorrência
   - Verificar se não retorna mais erro 404

4. **Verificar logs:**
   - Não deve mais aparecer `[OCORRENCIAS_API] Instrutor não encontrado`
   - Deve aparecer logs de sucesso ao registrar ocorrências

### 7.2. Checklist de Validação

- [ ] Registro criado em `instrutores` com `usuario_id=44`
- [ ] `getCurrentInstrutorId(44)` retorna um ID válido
- [ ] API `ocorrencias-instrutor.php` não retorna mais erro 404
- [ ] Página `instrutor/ocorrencias.php` carrega sem erros
- [ ] É possível registrar ocorrências
- [ ] Logs não mostram mais "Instrutor não encontrado"

---

## 8. ARQUIVOS ENVOLVIDOS

### 8.1. Arquivos Afetados pelo Erro
- `admin/api/ocorrencias-instrutor.php` (linha 91-104)
- `instrutor/ocorrencias.php` (chamadas à API)
- `includes/auth.php` (função `getCurrentInstrutorId`, linha 810-827)

### 8.2. Arquivos de Solução
- `docs/scripts/criar-instrutor-carlos.sql` (script SQL)
- `admin/criar-instrutor-carlos.php` (script PHP temporário)

### 8.3. Arquivos de Log
- `logs/php_errors.log` (29 ocorrências do erro)
- `C:\xampp\apache\logs\error.log` (sem erros relacionados)

---

## 9. IMPACTO E PRIORIDADE

### 9.1. Impacto
- **Alto:** Bloqueia completamente o uso da funcionalidade de ocorrências para o instrutor
- **Escopo:** Apenas o usuário Carlos da Silva (usuario_id=44) é afetado
- **Severidade:** Crítica para o usuário afetado, baixa para o sistema geral

### 9.2. Prioridade
- **Alta:** Deve ser corrigido imediatamente para permitir uso da funcionalidade
- **Complexidade:** Baixa (apenas inserção de registro no banco)
- **Tempo estimado:** 5-10 minutos (execução do SQL)

---

## 10. LIÇÕES APRENDIDAS

### 10.1. Problemas Identificados
1. **Inconsistência de dados:** Usuário existe em `usuarios` mas não em `instrutores`
2. **Falta de validação:** Sistema não valida se registro em `instrutores` existe ao criar usuário tipo `instrutor`
3. **Mensagem de erro:** Poderia ser mais clara indicando que é necessário criar registro em `instrutores`

### 10.2. Recomendações Futuras
1. **Validação no cadastro:** Ao criar usuário tipo `instrutor`, criar automaticamente registro em `instrutores`
2. **Script de sincronização:** Criar script para identificar e corrigir inconsistências similares
3. **Melhorar mensagem de erro:** Indicar claramente que é necessário criar registro em `instrutores`

---

## 11. CONCLUSÃO

O problema é **claramente identificado** e a **solução é simples**: criar o registro faltante na tabela `instrutores` para o `usuario_id=44`.

**Próximo passo:** Executar o script SQL via phpMyAdmin ou cliente MySQL para criar o registro.

---

**Documento criado em:** 22/11/2025  
**Última atualização:** 22/11/2025

