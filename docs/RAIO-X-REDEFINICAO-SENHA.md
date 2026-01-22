# Raio-X: Sistema de Redefinição de Senha

## Data: 2024
## Objetivo: Documentar estado atual e implementar fluxo completo

---

## 0. Estado Atual do Sistema

### 0.1. Armazenamento de Senhas

**Campo no Banco de Dados:**
- Tabela: `usuarios`
- Campo: `senha` (VARCHAR(255))
- Algoritmo de Hash: `password_hash($senha, PASSWORD_DEFAULT)` (bcrypt)
- Verificação: `password_verify($senha, $hash)`

**Localização no Código:**
- `includes/auth.php` (linha 43): `password_verify($senha, $usuario['senha'])`
- `admin/api/usuarios.php` (linha 121): `password_hash($novaSenhaTemporaria, PASSWORD_DEFAULT)`
- `includes/CredentialManager.php` (linha 66): `password_hash($tempPassword, PASSWORD_DEFAULT)`

### 0.2. Flags de Troca de Senha

**Campos Identificados no Código:**
- `primeiro_acesso` (BOOLEAN) - Usado em `CredentialManager.php` (linhas 75, 179, 251, 261)
- `senha_temporaria` (BOOLEAN) - Usado em `CredentialManager.php` (linhas 76, 180, 262)
- `senha_alterada_em` (DATETIME) - Mencionado em `AuthService.php` (linhas 100, 137, 141)

**Status:**
- ⚠️ **NECESSÁRIO VERIFICAR**: Se essas colunas existem no banco de dados
- ⚠️ **NECESSÁRIO CRIAR**: Coluna `precisa_trocar_senha` se não existir (padronização)

### 0.3. Fluxo de Redefinição Existente

**Backend (API):**
- Arquivo: `admin/api/usuarios.php`
- Endpoint: `POST` com `action=reset_password`
- Localização: Linhas 96-154
- Funcionalidade Atual:
  - ✅ Gera senha temporária via `CredentialManager::generateTemporaryPassword()`
  - ✅ Faz hash e salva no banco
  - ✅ Envia email (simulado via `CredentialManager::sendCredentials()`)
  - ✅ Retorna senha temporária na resposta
  - ❌ **NÃO marca flag de troca obrigatória**
  - ❌ **NÃO suporta modo manual**
  - ❌ **NÃO faz log de auditoria**

**Frontend (Modal):**
- Arquivo: `admin/pages/usuarios.php`
- Modal: `#resetPasswordModal` (linha 545)
- Função: `showResetPasswordModal()` (linha 1114)
- Funcionalidade Atual:
  - ✅ Abre modal ao clicar no botão "Senha"
  - ✅ Mostra informações do usuário
  - ✅ Requer confirmação via checkbox
  - ✅ Chama API de redefinição
  - ✅ Mostra modal de credenciais após sucesso
  - ❌ **NÃO oferece opção de modo manual**
  - ❌ **NÃO valida senha manual**

### 0.4. Helpers e Serviços

**CredentialManager (`includes/CredentialManager.php`):**
- ✅ `generateTempPassword($length = 8)` - Gera senha aleatória
- ✅ `generateTemporaryPassword($length = 8)` - Alias para compatibilidade
- ✅ `sendCredentials($email, $senha, $tipo)` - Envia email (simulado)
- ✅ `isFirstAccess($usuarioId)` - Verifica primeiro acesso
- ✅ `markFirstAccessCompleted($usuarioId)` - Marca primeiro acesso como concluído

**Sistema de Email:**
- ⚠️ **SIMULADO**: `CredentialManager::sendCredentials()` apenas faz log
- ⚠️ **NECESSÁRIO**: Implementar envio real de email (futuro)

**Sistema de Log/Auditoria:**
- ⚠️ **BÁSICO**: Apenas `error_log()` usado
- ⚠️ **NECESSÁRIO**: Sistema estruturado de auditoria

### 0.5. Permissões

**Verificação de Permissão:**
- Arquivo: `admin/api/usuarios.php` (linha 42)
- Função: `canManageUsers()` (definida em `includes/auth.php`)
- Regra: Apenas `admin` e `secretaria` podem gerenciar usuários
- Status: ✅ **FUNCIONANDO**

---

## 1. O Que Precisa Ser Implementado

### 1.1. Banco de Dados

**Verificar/Criar Coluna:**
```sql
-- Verificar se existe
SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha';

-- Se não existir, criar:
ALTER TABLE usuarios
  ADD COLUMN precisa_trocar_senha TINYINT(1) NOT NULL DEFAULT 0 AFTER senha;
```

**Verificar Colunas Existentes:**
```sql
-- Verificar campos relacionados
SHOW COLUMNS FROM usuarios;
```

### 1.2. Backend (API)

**Melhorias Necessárias:**
1. ✅ Adicionar suporte a modo manual (`mode: 'auto' | 'manual'`)
2. ✅ Validar senha manual (mínimo 8 caracteres, confirmação)
3. ✅ Marcar `precisa_trocar_senha = 1` após reset
4. ✅ Adicionar log de auditoria
5. ✅ Melhorar retorno da API (incluir `mode` e `temp_password` apenas se `mode === 'auto'`)

### 1.3. Frontend (Modal)

**Melhorias Necessárias:**
1. ✅ Adicionar opção de modo (radio buttons: automático vs manual)
2. ✅ Mostrar campos de senha manual quando modo manual selecionado
3. ✅ Validar senha manual no frontend
4. ✅ Melhorar UX (loading states, mensagens de erro)
5. ✅ Garantir que senha temporária só aparece uma vez

### 1.4. Fluxo de Login

**Verificação de Flag:**
- ⚠️ **NECESSÁRIO**: Verificar `precisa_trocar_senha` no login
- ⚠️ **NECESSÁRIO**: Redirecionar para tela de alteração obrigatória se flag = 1

---

## 2. Decisões de Implementação

### 2.1. Padronização de Flag

**Decisão:** Usar `precisa_trocar_senha` como flag principal
- Se `primeiro_acesso` e `senha_temporaria` existirem, manter para compatibilidade
- `precisa_trocar_senha` será o flag principal para reset de senha pelo admin

### 2.2. Geração de Senha Temporária

**Decisão:** Usar `CredentialManager::generateTempPassword(10)` (10 caracteres)
- Mantém compatibilidade com código existente
- 10 caracteres é um bom equilíbrio entre segurança e usabilidade

### 2.3. Log de Auditoria

**Decisão:** Usar sistema de log centralizado (se existir) ou `error_log()` com formato estruturado
- Formato: `[PASSWORD_RESET] admin_id=X, user_id=Y, mode=auto|manual, timestamp=Z, ip=W`

### 2.4. Email

**Decisão:** Manter simulado por enquanto, mas deixar comentários claros onde plugar o envio real
- Quando sistema de email estiver configurado, substituir `CredentialManager::sendCredentials()`

---

## 3. Checklist de Implementação

- [ ] Verificar/criar coluna `precisa_trocar_senha` no banco
- [ ] Atualizar API para suportar modo manual
- [ ] Adicionar marcação de flag após reset
- [ ] Adicionar log de auditoria
- [ ] Atualizar modal frontend com opções de modo
- [ ] Adicionar validação de senha manual
- [ ] Melhorar UX do modal
- [ ] Testar fluxo completo
- [ ] Documentar mudanças

---

## 4. Arquivos a Modificar

1. `admin/api/usuarios.php` - Melhorar endpoint de reset
2. `admin/pages/usuarios.php` - Melhorar modal frontend
3. `includes/CredentialManager.php` - Adicionar helper se necessário
4. `docs/scripts/migration-precisa-trocar-senha.sql` - Script de migração (criar)

---

## 5. Notas Finais

- **NÃO quebrar funcionalidade existente**: O fluxo atual funciona, apenas precisa ser melhorado
- **Reaproveitar código**: Usar `CredentialManager` e funções existentes
- **Segurança**: Nunca expor senha atual, sempre hash, senha temporária só uma vez
- **Compatibilidade**: Manter compatibilidade com campos existentes (`primeiro_acesso`, `senha_temporaria`)

