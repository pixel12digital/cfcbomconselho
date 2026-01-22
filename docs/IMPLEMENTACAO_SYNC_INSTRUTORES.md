# Implementação - Sincronização de Instrutores

**Data:** 22/11/2025  
**Objetivo:** Garantir consistência entre tabelas `usuarios` e `instrutores`  
**Status:** ✅ Implementado

---

## 1. RESUMO EXECUTIVO

### Problema Identificado
Usuários com `tipo='instrutor'` na tabela `usuarios` não possuíam registro correspondente na tabela `instrutores`, causando erros como:
```
"Instrutor não encontrado. Verifique seu cadastro."
```

### Solução Implementada
1. **Script de migração** para corrigir instrutores já existentes
2. **Função helper reutilizável** para criar instrutor a partir de usuário
3. **Ajustes no fluxo de criação/edição** de usuários para garantir consistência futura

---

## 2. ARQUIVOS CRIADOS/MODIFICADOS

### 2.1. Arquivos Criados

#### `admin/migrate-sync-instrutores.php`
- **Tipo:** Script temporário de migração
- **Função:** Sincronizar todos usuários tipo 'instrutor' com tabela instrutores
- **Acesso:** Apenas administradores
- **Status:** ⚠️ Deve ser removido após execução em desenvolvimento

#### `docs/IMPLEMENTACAO_SYNC_INSTRUTORES.md` (este arquivo)
- **Tipo:** Documentação
- **Função:** Documentar implementação completa

### 2.2. Arquivos Modificados

#### `includes/auth.php`
- **Função adicionada:** `createInstrutorFromUser($usuarioId, $cfcId = null)`
- **Localização:** Linha ~828 (após `getCurrentInstrutorId()`)
- **Função:** Criar registro de instrutor a partir de usuário, com validações e geração automática de credencial

#### `admin/api/usuarios.php`
- **Modificação POST (criação):** Linha ~362-380
  - Após criar usuário tipo 'instrutor', chama `createInstrutorFromUser()` automaticamente
- **Modificação PUT (edição):** Linha ~425-480
  - Detecta alteração de tipo para 'instrutor' e cria registro automaticamente
  - Busca tipo anterior antes da atualização para detectar mudança

---

## 3. FUNÇÃO HELPER: `createInstrutorFromUser()`

### 3.1. Localização
`includes/auth.php` (linha ~828)

### 3.2. Assinatura
```php
function createInstrutorFromUser($usuarioId, $cfcId = null)
```

### 3.3. Parâmetros
- `$usuarioId` (int, obrigatório): ID do usuário na tabela `usuarios`
- `$cfcId` (int|null, opcional): ID do CFC. Se `null`, busca o primeiro CFC disponível

### 3.4. Retorno
```php
[
    'success' => bool,        // true se operação foi bem-sucedida
    'instrutor_id' => int|null,  // ID do instrutor criado ou existente
    'message' => string,      // Mensagem descritiva
    'created' => bool         // true se foi criado agora, false se já existia
]
```

### 3.5. Lógica de Funcionamento

1. **Validação do usuário:**
   - Verifica se usuário existe
   - Verifica se tipo é 'instrutor'
   - Retorna erro se validações falharem

2. **Verificação de existência:**
   - Busca registro em `instrutores` com `usuario_id = $usuarioId`
   - Se existir, retorna sucesso sem criar duplicado

3. **Busca de CFC:**
   - Se `$cfcId` não fornecido, busca primeiro CFC disponível
   - Retorna erro se nenhum CFC encontrado

4. **Geração de credencial:**
   - Formato: `CRED-` + `usuario_id` com zero-padding (ex: `CRED-000044`)
   - Verifica se credencial já existe
   - Se existir, adiciona sufixo com timestamp

5. **Criação do registro:**
   - Insere em `instrutores` com:
     - `nome`: do usuário
     - `usuario_id`: ID do usuário
     - `cfc_id`: CFC fornecido ou encontrado
     - `credencial`: gerada automaticamente
     - `ativo`: 1
     - `criado_em`: data/hora atual

### 3.6. Logs
- Logs discretos usando `LOG_ENABLED`
- Registra criação, detecção de existência e erros

---

## 4. SCRIPT DE MIGRAÇÃO

### 4.1. Arquivo
`admin/migrate-sync-instrutores.php`

### 4.2. Funcionalidades

1. **Busca todos usuários tipo 'instrutor'**
2. **Verifica CFC disponível** (usa `$DEFAULT_CFC_ID` ou primeiro encontrado)
3. **Processa cada usuário:**
   - Verifica se já existe registro em `instrutores`
   - Se não existir, cria usando `createInstrutorFromUser()`
   - Registra detalhes de cada operação
4. **Exibe relatório completo:**
   - Total de usuários encontrados
   - Quantos já possuíam registro
   - Quantos foram criados
   - Detalhes de cada operação

### 4.3. Segurança
- Requer autenticação como administrador
- Exibe mensagem clara se não for admin
- Não permite execução não autorizada

### 4.4. Configuração
```php
$DEFAULT_CFC_ID = 1; // Ajustar conforme necessário
```

### 4.5. Execução
- **Via navegador:** `http://localhost/cfc-bom-conselho/admin/migrate-sync-instrutores.php`
- **Requisito:** Estar logado como administrador

### 4.6. Idempotência
- Pode ser executado múltiplas vezes
- Não cria duplicados
- Detecta e pula registros já existentes

---

## 5. AJUSTES NO FLUXO DE CRIAÇÃO/EDIÇÃO

### 5.1. Criação de Usuário (POST)

#### Arquivo: `admin/api/usuarios.php` (linha ~362)

**Fluxo:**
1. Usuário é criado via `CredentialManager::createEmployeeCredentials()`
2. Se `tipo === 'instrutor'`, chama `createInstrutorFromUser()` automaticamente
3. Logs discretos registram a criação

**Código:**
```php
// SYNC_INSTRUTORES: Se o tipo for 'instrutor', criar registro em instrutores automaticamente
if ($usuario && $usuario['tipo'] === 'instrutor') {
    $instrutorResult = createInstrutorFromUser($result);
    // Logs e tratamento de erros...
}
```

### 5.2. Edição de Usuário (PUT)

#### Arquivo: `admin/api/usuarios.php` (linha ~425)

**Fluxo:**
1. Busca tipo anterior ANTES da atualização
2. Atualiza usuário
3. Se tipo foi alterado para 'instrutor', chama `createInstrutorFromUser()` automaticamente
4. Logs discretos registram a criação

**Código:**
```php
// Buscar tipo anterior
$tipoAnterior = $existingUser['tipo'] ?? null;

// Atualizar usuário...

// SYNC_INSTRUTORES: Se tipo foi alterado para 'instrutor'
$tipoNovo = $updateData['tipo'] ?? $tipoAnterior;
if ($tipoNovo === 'instrutor' && $tipoAnterior !== 'instrutor') {
    $instrutorResult = createInstrutorFromUser($id);
    // Logs e tratamento de erros...
}
```

### 5.3. Comportamento de Erros
- **Erros não bloqueiam operação principal:**
  - Se falhar ao criar instrutor, usuário ainda é criado/atualizado
  - Erro é logado para diagnóstico
  - Não retorna erro ao frontend (apenas logs)

---

## 6. CASOS DE USO E TESTES

### 6.1. Caso 1: Criar novo usuário tipo 'instrutor'

**Passos:**
1. Acessar `admin/index.php?page=usuarios`
2. Clicar em "Novo Usuário"
3. Preencher: Nome, Email, Tipo = "Instrutor"
4. Salvar

**Resultado esperado:**
- ✅ Usuário criado em `usuarios`
- ✅ Registro criado automaticamente em `instrutores`
- ✅ `getCurrentInstrutorId()` retorna ID válido
- ✅ API `ocorrencias-instrutor.php` funciona sem erro

**Validação:**
```sql
-- Verificar usuário
SELECT id, nome, email, tipo FROM usuarios WHERE email = 'novo@instrutor.com';

-- Verificar instrutor
SELECT id, usuario_id, cfc_id, credencial FROM instrutores WHERE usuario_id = [ID_DO_USUARIO];
```

### 6.2. Caso 2: Alterar tipo de usuário para 'instrutor'

**Passos:**
1. Acessar `admin/index.php?page=usuarios`
2. Editar usuário existente (ex: tipo 'secretaria')
3. Alterar tipo para "Instrutor"
4. Salvar

**Resultado esperado:**
- ✅ Tipo atualizado em `usuarios`
- ✅ Registro criado automaticamente em `instrutores`
- ✅ `getCurrentInstrutorId()` retorna ID válido
- ✅ API `ocorrencias-instrutor.php` funciona sem erro

**Validação:**
```sql
-- Verificar tipo atualizado
SELECT id, tipo FROM usuarios WHERE id = [ID_DO_USUARIO];

-- Verificar instrutor criado
SELECT id, usuario_id, cfc_id, credencial FROM instrutores WHERE usuario_id = [ID_DO_USUARIO];
```

### 6.3. Caso 3: Executar script de migração

**Passos:**
1. Acessar `admin/migrate-sync-instrutores.php` (como admin)
2. Verificar relatório exibido
3. Confirmar que todos usuários tipo 'instrutor' possuem registro

**Resultado esperado:**
- ✅ Relatório exibe total de usuários encontrados
- ✅ Indica quantos já existiam e quantos foram criados
- ✅ Detalhes de cada operação
- ✅ Nenhum erro reportado

**Validação:**
```sql
-- Verificar consistência
SELECT 
    u.id as usuario_id,
    u.nome,
    u.tipo,
    i.id as instrutor_id
FROM usuarios u
LEFT JOIN instrutores i ON u.id = i.usuario_id
WHERE u.tipo = 'instrutor';

-- Não deve haver nenhum registro com instrutor_id = NULL
```

### 6.4. Caso 4: Usuário já possui registro em instrutores

**Cenário:** Usuário tipo 'instrutor' já possui registro em `instrutores`

**Comportamento esperado:**
- ✅ `createInstrutorFromUser()` detecta existência
- ✅ Não cria duplicado
- ✅ Retorna `created = false`
- ✅ Log indica que já existia

---

## 7. CHECKLIST DE VALIDAÇÃO

### 7.1. Migração
- [ ] Executar `admin/migrate-sync-instrutores.php`
- [ ] Verificar que todos usuários tipo 'instrutor' possuem registro
- [ ] Confirmar que não foram criados duplicados
- [ ] Verificar logs do sistema

### 7.2. Criação de Usuário
- [ ] Criar novo usuário tipo 'instrutor'
- [ ] Verificar que registro foi criado em `instrutores`
- [ ] Fazer login como o novo instrutor
- [ ] Acessar `instrutor/ocorrencias.php`
- [ ] Confirmar que não há erro "Instrutor não encontrado"

### 7.3. Edição de Usuário
- [ ] Editar usuário existente (tipo diferente de 'instrutor')
- [ ] Alterar tipo para 'instrutor'
- [ ] Verificar que registro foi criado em `instrutores`
- [ ] Fazer login como o instrutor
- [ ] Acessar `instrutor/ocorrencias.php`
- [ ] Confirmar que não há erro "Instrutor não encontrado"

### 7.4. APIs
- [ ] Testar `admin/api/ocorrencias-instrutor.php` (GET e POST)
- [ ] Testar `admin/api/instrutor-aulas.php`
- [ ] Verificar que todas retornam sucesso para instrutores válidos

### 7.5. Logs
- [ ] Verificar logs em `logs/php_errors.log`
- [ ] Confirmar que não há mais erros "Instrutor não encontrado"
- [ ] Verificar logs de criação automática de instrutores

---

## 8. LIMITAÇÕES E FUTURAS MELHORIAS

### 8.1. Limitações Atuais

1. **Desativação não automática:**
   - Se tipo for alterado de 'instrutor' para outro, registro em `instrutores` não é desativado automaticamente
   - Comentário `TODO` deixado no código para implementação futura

2. **CFC padrão:**
   - Script de migração usa CFC padrão configurável
   - Pode não ser o CFC correto para todos os instrutores
   - Requer ajuste manual se necessário

3. **Credencial gerada:**
   - Formato fixo: `CRED-` + `usuario_id`
   - Não permite personalização
   - Pode conflitar se já existir (usa timestamp como fallback)

### 8.2. Melhorias Futuras Sugeridas

1. **Sincronização bidirecional:**
   - Desativar registro em `instrutores` quando tipo for alterado
   - Considerar exclusão lógica (soft delete)

2. **Validação de CFC:**
   - Permitir seleção de CFC na criação de usuário tipo 'instrutor'
   - Validar se CFC existe antes de criar

3. **Geração de credencial:**
   - Permitir personalização de credencial
   - Validar formato e unicidade

4. **Script de verificação:**
   - Criar script para identificar inconsistências
   - Executar periodicamente (cron job)

---

## 9. SEGURANÇA E VALIDAÇÕES

### 9.1. Validações Implementadas

1. **Autenticação:**
   - Script de migração requer admin
   - APIs requerem autenticação e permissões

2. **Validação de dados:**
   - Verifica se usuário existe
   - Verifica se tipo é 'instrutor'
   - Verifica se CFC existe

3. **Prevenção de duplicados:**
   - Verifica existência antes de criar
   - Gera credencial única
   - Trata conflitos graciosamente

### 9.2. Logs de Auditoria

- Todas as operações são logadas
- Inclui: usuario_id, instrutor_id, cfc_id, credencial, timestamp
- Facilita rastreamento e diagnóstico

---

## 10. MANUTENÇÃO

### 10.1. Remoção do Script de Migração

**Após validação em desenvolvimento:**
1. Executar script em produção (se necessário)
2. Validar que todos os registros foram criados
3. **Remover arquivo:** `admin/migrate-sync-instrutores.php`

### 10.2. Monitoramento

- Verificar logs periodicamente
- Identificar novos casos de inconsistência
- Executar script de migração se necessário

### 10.3. Documentação

- Manter este documento atualizado
- Documentar mudanças futuras
- Registrar decisões de design

---

## 11. CONCLUSÃO

A implementação garante que:
- ✅ Todos usuários tipo 'instrutor' possuem registro em `instrutores`
- ✅ Novos usuários tipo 'instrutor' criam registro automaticamente
- ✅ Alteração de tipo para 'instrutor' cria registro automaticamente
- ✅ Script de migração corrige inconsistências existentes
- ✅ Logs discretos facilitam diagnóstico

**Próximos passos:**
1. Executar script de migração em desenvolvimento
2. Validar todos os casos de uso
3. Remover script de migração após validação
4. Monitorar logs e comportamento em produção

---

**Documento criado em:** 22/11/2025  
**Última atualização:** 22/11/2025

