# Investigação: Ocorrências de Instrutor no Painel Admin/Secretaria

**Data:** 22/11/2025  
**Objetivo:** Verificar se já existe implementação para visualizar e gerenciar ocorrências registradas por instrutores no painel admin/secretaria.

---

## 📋 Resumo Executivo

**Status:** ❌ **NÃO IMPLEMENTADO**

Não existe nenhuma interface no painel admin/secretaria para visualizar, gerenciar ou resolver ocorrências registradas pelos instrutores. A funcionalidade está **parcialmente implementada** apenas no lado do instrutor.

---

## 🔍 O que foi encontrado

### ✅ **Implementado (Lado do Instrutor)**

1. **Tabela de Banco de Dados: `ocorrencias_instrutor`**
   - Localização: `docs/scripts/migration_ocorrencias_instrutor.sql`
   - Campos relevantes para admin/secretaria:
     - `status` (ENUM: 'aberta', 'em_analise', 'resolvida', 'arquivada')
     - `resolucao` (TEXT) - Campo para preencher a resolução
     - `resolvido_por` (INT) - ID do usuário que resolveu
     - `resolvido_em` (DATETIME) - Data/hora da resolução
   - **Conclusão:** A estrutura da tabela já prevê que admin/secretaria podem resolver ocorrências.

2. **API do Instrutor: `admin/api/ocorrencias-instrutor.php`**
   - **POST:** Permite que instrutor registre ocorrências
   - **GET:** Permite que instrutor liste suas próprias ocorrências
   - **Validações:** Verifica se `aula_id` pertence ao instrutor logado
   - **Conclusão:** API funcional apenas para o instrutor visualizar/registrar suas próprias ocorrências.

3. **Página do Instrutor: `instrutor/ocorrencias.php`**
   - Formulário para registrar novas ocorrências
   - Lista de ocorrências registradas pelo instrutor
   - **Conclusão:** Interface completa para o instrutor.

---

### ❌ **NÃO Implementado (Lado do Admin/Secretaria)**

1. **Página de Gerenciamento de Ocorrências**
   - ❌ Não existe `admin/pages/ocorrencias.php` ou similar
   - ❌ Não há interface para visualizar todas as ocorrências
   - ❌ Não há interface para filtrar por instrutor, status, tipo, data
   - ❌ Não há interface para resolver ocorrências (preencher `resolucao`, `resolvido_por`, `resolvido_em`)

2. **Item no Menu Lateral**
   - ❌ Não existe item "Ocorrências" ou "Ocorrências de Instrutores" no menu do admin
   - ❌ Não há link para acessar ocorrências em nenhum lugar do painel

3. **API para Admin/Secretaria**
   - ❌ Não existe endpoint para admin/secretaria listar todas as ocorrências
   - ❌ Não existe endpoint para admin/secretaria atualizar status/resolução
   - ❌ A API atual (`admin/api/ocorrencias-instrutor.php`) é restrita apenas a instrutores

4. **Dashboard/Notificações**
   - ❌ Não há widget no dashboard mostrando ocorrências pendentes
   - ❌ Não há notificações quando uma nova ocorrência é registrada

---

## 📊 Estrutura da Tabela `ocorrencias_instrutor`

```sql
CREATE TABLE ocorrencias_instrutor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrutor_id INT NOT NULL,              -- ID do instrutor
    usuario_id INT NOT NULL,                -- ID do usuário que registrou
    tipo ENUM(
        'atraso_aluno',
        'problema_veiculo',
        'infraestrutura',
        'comportamento_aluno',
        'outro'
    ) NOT NULL DEFAULT 'outro',
    data_ocorrencia DATE NOT NULL,
    aula_id INT NULL,                       -- Aula relacionada (opcional)
    descricao TEXT NOT NULL,
    status ENUM('aberta', 'em_analise', 'resolvida', 'arquivada') DEFAULT 'aberta',
    resolucao TEXT NULL,                    -- ⚠️ Campo para admin/secretaria preencher
    resolvido_por INT NULL,                 -- ⚠️ ID do admin/secretaria que resolveu
    resolvido_em DATETIME NULL,             -- ⚠️ Data/hora da resolução
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Observação:** Os campos `resolucao`, `resolvido_por` e `resolvido_em` indicam que a funcionalidade de resolução foi planejada, mas não implementada.

---

## 🔎 Arquivos Verificados

### ✅ Arquivos que existem:
- `docs/scripts/migration_ocorrencias_instrutor.sql` - Script de migração
- `admin/api/ocorrencias-instrutor.php` - API (apenas para instrutor)
- `instrutor/ocorrencias.php` - Página do instrutor

### ❌ Arquivos que NÃO existem:
- `admin/pages/ocorrencias.php` - Página de gerenciamento
- `admin/pages/ocorrencias-instrutor.php` - Página alternativa
- `admin/api/ocorrencias-admin.php` - API para admin/secretaria
- Qualquer referência no menu (`admin/index.php`)

---

## 📝 Recomendações para Implementação Futura

### 1. **Criar Página de Gerenciamento**
   - **Arquivo:** `admin/pages/ocorrencias.php`
   - **Funcionalidades:**
     - Listar todas as ocorrências (com paginação)
     - Filtros: status, tipo, instrutor, data
     - Visualizar detalhes da ocorrência
     - Resolver ocorrência (preencher resolução)
     - Alterar status (aberta → em_analise → resolvida → arquivada)

### 2. **Criar/Expandir API**
   - **Opção A:** Expandir `admin/api/ocorrencias-instrutor.php` para aceitar requisições de admin/secretaria
   - **Opção B:** Criar `admin/api/ocorrencias-admin.php` específica para admin/secretaria
   - **Métodos necessários:**
     - `GET` - Listar todas as ocorrências (com filtros)
     - `GET /{id}` - Obter detalhes de uma ocorrência
     - `PUT /{id}` - Atualizar status/resolução
     - `PATCH /{id}/resolver` - Resolver ocorrência (preencher resolução)

### 3. **Adicionar ao Menu**
   - Adicionar item "Ocorrências" no menu lateral do admin
   - Badge mostrando quantidade de ocorrências "abertas"
   - Visível apenas para `admin` e `secretaria`

### 4. **Dashboard Widget (Opcional)**
   - Card no dashboard mostrando:
     - Total de ocorrências abertas
     - Ocorrências em análise
     - Link para página de gerenciamento

### 5. **Notificações (Opcional)**
   - Notificar admin/secretaria quando nova ocorrência é registrada
   - Usar sistema de notificações existente (`admin/api/notificacoes.php`)

---

## ✅ Conclusão

**Status Atual:** A funcionalidade de ocorrências está **50% implementada**:
- ✅ Instrutor pode registrar e visualizar suas ocorrências
- ❌ Admin/Secretaria não podem visualizar, gerenciar ou resolver ocorrências

**Próximos Passos:** Implementar interface de gerenciamento no painel admin/secretaria conforme recomendações acima.

---

**Arquivo criado em:** 22/11/2025  
**Última atualização:** 22/11/2025

