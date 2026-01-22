# Análise: Perfil do CFC - Estrutura Atual e Proposta

## 📋 Resumo Executivo

**Descoberta Principal**: A tabela `cfcs` **JÁ POSSUI** campos para perfil institucional (`endereco`, `telefone`, `email`), mas eles **NÃO estão sendo utilizados** na interface atual.

**Recomendação**: **Opção A (mais simples)** - Habilitar os campos existentes sem criar novas estruturas.

---

## 1️⃣ Fluxo Atual (Nome e Logo)

### Rotas Identificadas

| Rota | Método | Controller | Método | Descrição |
|------|--------|------------|--------|-----------|
| `/configuracoes/cfc` | GET | `ConfiguracoesController` | `cfc()` | Exibe página de configurações |
| `/configuracoes/cfc/salvar` | POST | `ConfiguracoesController` | `salvarCfc()` | Salva nome e CNPJ |
| `/configuracoes/cfc/logo/upload` | POST | `ConfiguracoesController` | `uploadLogo()` | Faz upload do logo |
| `/configuracoes/cfc/logo/remover` | POST | `ConfiguracoesController` | `removerLogo()` | Remove logo |
| `/configuracoes/cfc/logo` | GET | `ConfiguracoesController` | `logo()` | Serve o logo (protegido) |

**Arquivo de Rotas**: `app/routes/web.php` (linhas 129-133)

### Onde os Dados são Salvos

#### Logo (`logo_path`)
- **Tabela**: `cfcs`
- **Coluna**: `logo_path` (VARCHAR 255)
- **Localização física**: `storage/uploads/cfcs/{cfc_id}/logo.{ext}`
- **Migration**: `034_add_logo_path_to_cfcs.sql`
- **Controller**: `ConfiguracoesController::uploadLogo()` (linha ~609)
- **Model**: `Cfc::getCurrentLogo()` (linha 32)

#### Nome do CFC
- **Tabela**: `cfcs`
- **Coluna**: `nome` (VARCHAR 255, NOT NULL)
- **Controller**: `ConfiguracoesController::salvarCfc()` (linha 1132)
- **Model**: `Cfc::getCurrentName()` (linha 23)

#### CNPJ
- **Tabela**: `cfcs`
- **Coluna**: `cnpj` (VARCHAR 18, NULL)
- **Controller**: `ConfiguracoesController::salvarCfc()` (linha 1133)
- **Exibição condicional**: Só aparece na view se não estiver vazio (linha 297 do `cfc.php`)

---

## 2️⃣ Estrutura Atual da Tabela `cfcs`

### Schema Completo (Migration 001 + 034)

```sql
CREATE TABLE `cfcs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,                    -- ✅ USADO
  `cnpj` varchar(18) DEFAULT NULL,                 -- ✅ USADO (condicional)
  `endereco` text DEFAULT NULL,                    -- ❌ NÃO USADO (mas existe!)
  `telefone` varchar(20) DEFAULT NULL,              -- ❌ NÃO USADO (mas existe!)
  `email` varchar(255) DEFAULT NULL,               -- ❌ NÃO USADO (mas existe!)
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `logo_path` varchar(255) DEFAULT NULL,           -- ✅ USADO (migration 034)
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Status dos Campos

| Campo | Tipo | Status | Observação |
|-------|------|--------|------------|
| `id` | INT | ✅ | Chave primária |
| `nome` | VARCHAR(255) | ✅ **USADO** | Salvo e exibido |
| `cnpj` | VARCHAR(18) | ✅ **USADO** | Salvo e exibido (condicional) |
| `endereco` | TEXT | ❌ **NÃO USADO** | Existe no banco, mas não é editado/exibido |
| `telefone` | VARCHAR(20) | ❌ **NÃO USADO** | Existe no banco, mas não é editado/exibido |
| `email` | VARCHAR(255) | ❌ **NÃO USADO** | Existe no banco, mas não é editado/exibido |
| `status` | ENUM | ✅ | Usado internamente |
| `logo_path` | VARCHAR(255) | ✅ **USADO** | Upload e exibição funcionando |
| `created_at` | TIMESTAMP | ✅ | Automático |
| `updated_at` | TIMESTAMP | ✅ | Automático |

---

## 3️⃣ Arquivos Envolvidos

### Controller
- **Arquivo**: `app/Controllers/ConfiguracoesController.php`
- **Métodos relevantes**:
  - `cfc()` (linha 554) - Exibe a página
  - `salvarCfc()` (linha 1113) - Salva nome e CNPJ apenas
  - `uploadLogo()` (linha ~609) - Upload do logo
  - `removerLogo()` (linha ~1086) - Remove logo

### Model
- **Arquivo**: `app/Models/Cfc.php`
- **Métodos**:
  - `getCurrent()` - Busca CFC atual (da sessão)
  - `getCurrentName()` - Retorna nome do CFC
  - `getCurrentLogo()` - Retorna caminho do logo
  - `hasLogo()` - Verifica se tem logo

### View
- **Arquivo**: `app/Views/configuracoes/cfc.php`
- **Seções**:
  - Upload de logo (linhas 10-270)
  - Informações do CFC (linhas 274-318) - **Só exibe nome e CNPJ**

### Rotas
- **Arquivo**: `app/routes/web.php` (linhas 129-133)

---

## 4️⃣ Proposta de Implementação

### ✅ Recomendação: Opção A (Habilitar Campos Existentes)

**Por quê?**
- ✅ Campos já existem no banco (sem migration necessária)
- ✅ Zero impacto no fluxo atual
- ✅ Implementação simples e incremental
- ✅ Compatibilidade total mantida

### 📝 Implementação Proposta

#### 4.1 Atualizar Controller (`ConfiguracoesController::salvarCfc()`)

**Arquivo**: `app/Controllers/ConfiguracoesController.php` (linha 1113)

**Mudanças**:
- Aceitar campos: `endereco`, `telefone`, `email`
- Validar formatos básicos
- Salvar no banco usando o mesmo método `update()`

#### 4.2 Atualizar View (`app/Views/configuracoes/cfc.php`)

**Arquivo**: `app/Views/configuracoes/cfc.php` (linha 274)

**Mudanças**:
- Adicionar campos de formulário para:
  - `endereco` (textarea)
  - `telefone` (input com máscara)
  - `email` (input type="email")
- Exibir valores existentes (se houver)
- Manter layout atual (card "Informações do CFC")

#### 4.3 Model (Opcional - Melhorias)

**Arquivo**: `app/Models/Cfc.php`

**Mudanças opcionais**:
- Adicionar métodos getters específicos (se necessário)
- Não é obrigatório, pois `getCurrent()` já retorna todos os campos

---

## 5️⃣ Campos Adicionais Recomendados (Futuro)

Se no futuro precisar de mais campos, considere:

### Opção B (JSON - Flexível)
```sql
ALTER TABLE `cfcs` 
ADD COLUMN `profile_json` TEXT DEFAULT NULL 
COMMENT 'Dados adicionais do perfil em JSON' 
AFTER `logo_path`;
```

**Exemplo de conteúdo**:
```json
{
  "legal_name": "Razão Social LTDA",
  "trade_name": "Nome Fantasia",
  "whatsapp": "(11) 99999-9999",
  "address": {
    "line": "Rua Exemplo, 123",
    "city": "São Paulo",
    "state": "SP",
    "zip": "01234-567"
  }
}
```

**Por enquanto**: **NÃO é necessário** - os campos existentes (`endereco`, `telefone`, `email`) são suficientes para um perfil básico.

---

## 6️⃣ Checklist de Implementação

### Fase 1: Controller (Mínimo Impacto)
- [ ] Atualizar `salvarCfc()` para aceitar `endereco`, `telefone`, `email`
- [ ] Adicionar validações básicas
- [ ] Manter compatibilidade com dados antigos (NULL)

### Fase 2: View (Incremental)
- [ ] Adicionar campos no formulário "Informações do CFC"
- [ ] Preencher valores existentes (se houver)
- [ ] Adicionar máscaras/validações no frontend (opcional)

### Fase 3: Testes
- [ ] Testar salvamento de campos novos
- [ ] Testar exibição de valores existentes
- [ ] Verificar que logo/nome continuam funcionando
- [ ] Testar com valores NULL (compatibilidade)

---

## 7️⃣ Respostas às Perguntas

### Qual controller/arquivo atende `/configuracoes/cfc`?
**Resposta**: `app/Controllers/ConfiguracoesController.php` → método `cfc()` (linha 554)

### Qual tabela e colunas salvam nome e logo?
**Resposta**: 
- **Tabela**: `cfcs`
- **Nome**: coluna `nome` (VARCHAR 255)
- **Logo**: coluna `logo_path` (VARCHAR 255)
- **CNPJ**: coluna `cnpj` (VARCHAR 18) - também salvo

### Se já existe local para "dados do CFC"?
**Resposta**: ✅ **SIM!** A tabela `cfcs` já possui:
- `endereco` (TEXT) - **não usado**
- `telefone` (VARCHAR 20) - **não usado**
- `email` (VARCHAR 255) - **não usado**

### Recomendação: manter como está ou implementar perfil enxuto?
**Resposta**: **Implementar perfil enxuto usando campos existentes (Opção A)**

**Justificativa**:
- Zero impacto (sem migration)
- Campos já existem e estão prontos
- Implementação simples (só atualizar controller e view)
- Compatibilidade total mantida

---

## 8️⃣ Próximos Passos

1. **Implementar** atualização do controller para salvar `endereco`, `telefone`, `email`
2. **Implementar** atualização da view para exibir/editar esses campos
3. **Testar** que tudo continua funcionando (logo, nome, CNPJ)
4. **Opcional**: Adicionar validações mais robustas (formato de email, telefone, etc.)

---

## 📌 Notas Importantes

- ✅ **Não quebrar fluxo atual**: Logo e nome continuam funcionando normalmente
- ✅ **Sem migration necessária**: Campos já existem
- ✅ **Incremental**: Pode ser implementado gradualmente
- ✅ **Compatível**: Funciona com dados existentes (NULL é aceito)

---

**Data da Análise**: 2024
**Arquivos Analisados**: 
- `app/Controllers/ConfiguracoesController.php`
- `app/Models/Cfc.php`
- `app/Views/configuracoes/cfc.php`
- `app/routes/web.php`
- `database/migrations/001_create_base_tables.sql`
- `database/migrations/034_add_logo_path_to_cfcs.sql`
