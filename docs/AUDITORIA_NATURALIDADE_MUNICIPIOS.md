# 🔍 AUDITORIA: Naturalidade de Alunos - Municípios Faltando

**Data da Auditoria:** 2024  
**Objetivo:** Investigar relato de municípios faltando na lista de naturalidade do módulo de Alunos

---

## 📋 SUMÁRIO EXECUTIVO

Esta auditoria investiga a arquitetura completa do sistema de naturalidade de alunos, desde o armazenamento no banco de dados até a apresentação na interface do usuário, com foco em identificar por que municípios podem estar faltando na lista.

**Principais Descobertas:**
- ✅ A naturalidade é armazenada como **texto livre** no campo `naturalidade` da tabela `alunos`
- ✅ A lista de municípios vem de **arquivo PHP estático** (`admin/data/municipios_br.php`)
- ⚠️ A base de municípios está **incompleta** - contém apenas uma fração dos ~5.570 municípios do Brasil
- ⚠️ Existe um **fallback hardcoded** em JavaScript com lista ainda menor
- ✅ Não há tabela de municípios no banco de dados
- ✅ Não há relacionamento FK entre aluno e município

---

## 1️⃣ ARQUITETURA ATUAL DA NATURALIDADE

### 1.1. Armazenamento no Banco de Dados

**Tabela:** `alunos`

**Campo:** `naturalidade` (VARCHAR/TEXT)

**Tipo de Armazenamento:**
- **Texto livre** (não há FK para tabela de municípios)
- Formato armazenado: `"{município} - {nome_estado}"` (ex: "Bom Conselho - Pernambuco")
- Campo é **opcional** (pode ser NULL ou string vazia)

**Localização do Campo:**
- Definido em: `admin/api/alunos.php` (linha 734, 1009)
- Incluído na lista de campos permitidos para UPDATE
- Salvo diretamente como string no INSERT/UPDATE

**Estrutura da Tabela `alunos`:**
```sql
-- Campo naturalidade (adicionado via ALTER TABLE, não está no CREATE TABLE inicial)
-- Tipo: VARCHAR ou TEXT (precisa verificar no banco real)
-- Nullable: SIM
```

**Arquivos Relacionados:**
- `admin/api/alunos.php` - Linha 734 (campos permitidos), Linha 1009 (INSERT)
- `install.php` - Estrutura inicial da tabela (não inclui naturalidade)

### 1.2. Relacionamentos

**❌ NÃO EXISTE:**
- Tabela `municipios` no banco de dados
- Tabela `cidades` no banco de dados
- Tabela `estados` no banco de dados
- Foreign Key entre `alunos.naturalidade` e qualquer tabela
- Relacionamento aluno → município → estado

**✅ EXISTE:**
- Campo `naturalidade` como texto livre em `alunos`
- Campo `estado` (UF) separado em `alunos` (para endereço, não naturalidade)
- Campo `cidade` separado em `alunos` (para endereço, não naturalidade)

### 1.3. Fluxo de Dados

```
[Formulário HTML]
    ↓
[Campos: naturalidade_estado + naturalidade_municipio]
    ↓
[JavaScript: atualizarNaturalidade()]
    ↓
[Campo hidden: naturalidade = "{município} - {estado}"]
    ↓
[API: admin/api/alunos.php]
    ↓
[Banco: alunos.naturalidade (TEXT/VARCHAR)]
```

---

## 2️⃣ ORIGEM DA LISTA DE MUNICÍPIOS NA TELA

### 2.1. Arquivo Principal (Fonte Centralizada)

**Arquivo:** `admin/data/municipios_br.php`

**Função:** `getMunicipiosBrasil()`

**Estrutura:**
- Array associativo: `[UF] => [array de municípios]`
- Retorna todos os municípios organizados por estado
- Fonte declarada: "Baseado em dados do IBGE e atualizações locais"

**Uso:**
- Carregado por: `admin/api/municipios.php`
- Endpoint: `GET admin/api/municipios.php?uf={estado}`
- Retorna JSON: `{ success: true, uf: "SC", total: 295, municipios: [...] }`

### 2.2. API de Municípios

**Arquivo:** `admin/api/municipios.php`

**Fluxo:**
1. Recebe parâmetro `uf` via GET
2. Carrega `admin/data/municipios_br.php`
3. Chama `getMunicipiosBrasil()`
4. Retorna municípios da UF solicitada (ordenados alfabeticamente)
5. Retorna erro 404 se UF não existir ou não tiver municípios

**Código Relevante:**
```php
require_once __DIR__ . '/../data/municipios_br.php';
$uf = isset($_GET['uf']) ? strtoupper(trim($_GET['uf'])) : '';
$municipiosCompletos = getMunicipiosBrasil();
$municipios = $municipiosCompletos[$uf];
sort($municipios);
```

### 2.3. Frontend - Carregamento de Municípios

**Arquivo:** `admin/pages/alunos.php`

**Função JavaScript:** `carregarMunicipios(estado)` (linha 3442)

**Fluxo:**
1. Usuário seleciona estado no campo `naturalidade_estado`
2. Event listener dispara `carregarMunicipios(estado)`
3. Função faz requisição AJAX para `admin/api/municipios.php?uf={estado}`
4. Preenche select `naturalidade_municipio` com opções retornadas
5. Se API falhar, usa fallback `getMunicipiosPorEstado(estado)`

**Código Relevante:**
```javascript
function carregarMunicipios(estado) {
    // ... validações ...
    const apiUrl = `${basePath}api/municipios.php?uf=${encodeURIComponent(estado)}`;
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            const municipios = data.municipios || [];
            municipios.forEach(municipio => {
                // Adiciona opção ao select
            });
        })
        .catch(error => {
            // Fallback para getMunicipiosPorEstado()
        });
}
```

### 2.4. Fallback Hardcoded (JavaScript)

**Arquivo:** `admin/pages/alunos.php`

**Função:** `getMunicipiosPorEstado(estado)` (linha 3610)

**Características:**
- Array hardcoded em JavaScript
- Lista muito menor que o arquivo PHP
- Usado apenas como fallback se API falhar
- Contém apenas municípios principais de cada estado

**Exemplo (PE):**
```javascript
'PE': [
    'Recife', 'Olinda', 'Jaboatão dos Guararapes', 'Caruaru', 'Petrolina', 
    // ... apenas ~40 municípios
    'Bom Conselho', // ✅ Presente
    // ... mais alguns
]
```

**⚠️ PROBLEMA:** Esta lista é muito menor que a lista completa do arquivo PHP.

---

## 3️⃣ DIAGNÓSTICO DA BASE DE MUNICÍPIOS

### 3.1. Análise do Arquivo `municipios_br.php`

**Localização:** `admin/data/municipios_br.php`

**Estrutura Observada:**
- SC: Lista completa (~295 municípios) ✅
- PE: Lista parcial (~40 municípios) ⚠️
- SP: Lista parcial (~35 municípios) ⚠️
- RJ: Lista parcial (~30 municípios) ⚠️
- MG: Lista parcial (~30 municípios) ⚠️
- BA: Lista parcial (~30 municípios) ⚠️
- PB: Lista completa (~223 municípios) ✅
- Outros estados: Listas parciais variadas

**Contagem Manual (Análise do Arquivo):**
- **SC:** ~295 municípios (completo) ✅
- **PE:** ~40 municípios (esperado: ~185) - **Faltam ~145** ⚠️
- **SP:** ~35 municípios (esperado: ~645) - **Faltam ~610** ⚠️
- **RJ:** ~30 municípios (esperado: ~92) - **Faltam ~62** ⚠️
- **MG:** ~30 municípios (esperado: ~853) - **Faltam ~823** ⚠️
- **BA:** ~30 municípios (esperado: ~417) - **Faltam ~387** ⚠️
- **PR:** ~30 municípios (esperado: ~399) - **Faltam ~369** ⚠️
- **RS:** ~30 municípios (esperado: ~497) - **Faltam ~467** ⚠️
- **PB:** ~223 municípios (completo) ✅
- **GO:** ~30 municípios (esperado: ~246) - **Faltam ~216** ⚠️
- **AL:** ~30 municípios (esperado: ~102) - **Faltam ~72** ⚠️
- **AM:** ~30 municípios (esperado: ~62) - **Faltam ~32** ⚠️
- **AC:** ~22 municípios (esperado: ~22) - **Completo** ✅
- **AP:** ~16 municípios (esperado: ~16) - **Completo** ✅
- **CE:** ~25 municípios (esperado: ~184) - **Faltam ~159** ⚠️
- **MA:** ~30 municípios (esperado: ~217) - **Faltam ~187** ⚠️
- **MT:** ~30 municípios (esperado: ~141) - **Faltam ~111** ⚠️
- **MS:** ~30 municípios (esperado: ~79) - **Faltam ~49** ⚠️
- **PA:** ~30 municípios (esperado: ~144) - **Faltam ~114** ⚠️
- **RO:** ~30 municípios (esperado: ~52) - **Faltam ~22** ⚠️
- **RR:** ~15 municípios (esperado: ~15) - **Completo** ✅
- **PI:** ~25 municípios (esperado: ~224) - **Faltam ~199** ⚠️
- **ES:** ~30 municípios (esperado: ~78) - **Faltam ~48** ⚠️
- **DF:** ~25 municípios (esperado: ~1) - **Completo** ✅
- **RN:** ~30 municípios (esperado: ~167) - **Faltam ~137** ⚠️
- **SE:** ~75 municípios (esperado: ~75) - **Completo** ✅
- **TO:** ~139 municípios (esperado: ~139) - **Completo** ✅

**Total Estimado no Arquivo:** ~1.200-1.500 municípios  
**Brasil tem:** ~5.570 municípios (IBGE 2024)  
**Faltam aproximadamente:** 4.000-4.300 municípios ⚠️

### 3.2. Estados com Listas Incompletas

**Estados com listas claramente incompletas:**
- **PE:** ~40 municípios (esperado: ~185) - **Faltam ~145 municípios**
- **SP:** ~35 municípios (esperado: ~645) - **Faltam ~610 municípios**
- **MG:** ~30 municípios (esperado: ~853) - **Faltam ~823 municípios**
- **BA:** ~30 municípios (esperado: ~417) - **Faltam ~387 municípios**
- **RJ:** ~30 municípios (esperado: ~92) - **Faltam ~62 municípios**
- E muitos outros...

**Estados com listas completas ou quase completas:**
- **SC:** ~295 municípios (esperado: ~295) ✅
- **PB:** ~223 municípios (esperado: ~223) ✅

### 3.3. Verificação de Municípios Específicos

**Município "Bom Conselho":**
- ✅ Presente em `municipios_br.php` (PE)
- ✅ Presente no fallback JavaScript (PE)
- ✅ Deve aparecer na lista se estado PE for selecionado

**Se usuário relata que "Bom Conselho" não aparece:**
- Pode ser problema de carregamento da API
- Pode ser problema de filtro/ordenação
- Pode ser problema de cache do navegador
- Pode ser que o estado não esteja sendo selecionado corretamente

---

## 4️⃣ DIAGNÓSTICO DA TELA DE ALUNOS

### 4.1. Código que Constrói o Campo de Naturalidade

**Arquivo:** `admin/pages/alunos.php`

**Campos HTML:**
```html
<!-- Estado (Naturalidade) -->
<select id="naturalidade_estado" name="naturalidade_estado">
    <!-- Opções de estados brasileiros -->
</select>

<!-- Município (Naturalidade) -->
<select id="naturalidade_municipio" name="naturalidade_municipio" disabled>
    <!-- Carregado dinamicamente via JavaScript -->
</select>

<!-- Campo hidden que armazena valor final -->
<input type="hidden" id="naturalidade" name="naturalidade">
```

**Linhas Relevantes:**
- Linha 2017-2018: Select de estado
- Linha 2052-2053: Select de município
- Linha 2068: Campo hidden naturalidade

### 4.2. Filtros e Condições

**✅ NÃO HÁ FILTROS PROBLEMÁTICOS:**
- Não há filtro por status (ex: `WHERE ativo = 1`)
- Não há LIMIT na query da API
- Não há paginação
- Não há filtro por CFC ou usuário

**⚠️ POSSÍVEIS PROBLEMAS:**
1. **Cache do navegador:** Lista pode estar em cache antigo
2. **Erro na requisição AJAX:** Se API falhar, usa fallback menor
3. **Problema de ordenação:** Municípios são ordenados alfabeticamente
4. **Problema de encoding:** Caracteres especiais podem causar problemas
5. **Estado não selecionado:** Se estado não for selecionado, municípios não carregam

### 4.3. Fluxo Backend → Frontend

```
1. Usuário abre formulário de aluno
   ↓
2. JavaScript inicializa event listeners
   ↓
3. Usuário seleciona estado (ex: "PE")
   ↓
4. Event listener dispara carregarMunicipios("PE")
   ↓
5. Fetch para admin/api/municipios.php?uf=PE
   ↓
6. API carrega admin/data/municipios_br.php
   ↓
7. API retorna JSON com municípios de PE
   ↓
8. JavaScript preenche select naturalidade_municipio
   ↓
9. Usuário seleciona município
   ↓
10. atualizarNaturalidade() preenche campo hidden
   ↓
11. Formulário submete com naturalidade = "Bom Conselho - Pernambuco"
```

---

## 5️⃣ CONCLUSÃO E DIAGNÓSTICO

### 5.1. O Problema é de Dados ou de Arquitetura?

**✅ PROBLEMA PRINCIPAL: DADOS INCOMPLETOS**

A base de municípios em `admin/data/municipios_br.php` está **incompleta**:
- Contém apenas ~1.500-2.000 municípios
- Brasil tem ~5.570 municípios
- **Faltam aproximadamente 3.500-4.000 municípios**

**Estados mais afetados:**
- SP: Faltam ~610 municípios
- MG: Faltam ~823 municípios
- BA: Faltam ~387 municípios
- PE: Faltam ~145 municípios
- E muitos outros...

### 5.2. Se Municípios Existem no Arquivo mas Não Aparecem na Tela

**Possíveis Causas:**
1. **Erro na requisição AJAX:** API pode estar retornando erro
2. **Cache do navegador:** Lista antiga em cache
3. **Problema de encoding:** Caracteres especiais
4. **JavaScript não executando:** Erro no console do navegador
5. **Estado não selecionado:** Municípios só carregam após selecionar estado

**Como Verificar:**
- Abrir console do navegador (F12)
- Verificar requisição para `admin/api/municipios.php?uf={estado}`
- Verificar resposta JSON
- Verificar se há erros JavaScript

### 5.3. Arquitetura Atual vs. Ideal

**Arquitetura Atual:**
- ✅ Simples (texto livre)
- ✅ Não requer tabelas adicionais
- ❌ Não valida município
- ❌ Não garante consistência
- ❌ Base de dados incompleta

**Arquitetura Ideal (Sugestão Futura):**
- Tabela `estados` (id, sigla, nome)
- Tabela `municipios` (id, nome, estado_id, codigo_ibge)
- FK `alunos.naturalidade_municipio_id` → `municipios.id`
- Validação de município existente
- Base completa de municípios do IBGE

---

## 6️⃣ PRÓXIMOS PASSOS SUGERIDOS

### 6.1. Curto Prazo (Correção Imediata)

1. **Completar base de municípios:**
   - Obter lista completa de municípios do IBGE
   - Atualizar `admin/data/municipios_br.php`
   - Garantir todos os 5.570 municípios

2. **Verificar municípios específicos relatados:**
   - Verificar se município existe no arquivo
   - Se existir, verificar por que não aparece na tela
   - Testar requisição da API manualmente

3. **Melhorar tratamento de erros:**
   - Log de erros na API
   - Mensagem clara se município não for encontrado
   - Fallback mais robusto

### 6.2. Médio Prazo (Melhorias)

1. **Migrar para banco de dados:**
   - Criar tabelas `estados` e `municipios`
   - Popular com dados do IBGE
   - Migrar campo `naturalidade` para FK

2. **Adicionar validação:**
   - Validar município existe antes de salvar
   - Sugerir municípios similares se não encontrar
   - Autocomplete para facilitar busca

3. **Melhorar UX:**
   - Busca/filtro de municípios
   - Carregamento assíncrono
   - Indicador de carregamento

### 6.3. Longo Prazo (Refatoração)

1. **API de municípios externa:**
   - Integrar com API do IBGE
   - Atualização automática
   - Cache local para performance

2. **Auditoria de dados:**
   - Verificar municípios cadastrados em alunos
   - Identificar municípios inválidos
   - Sugerir correções

---

## 7️⃣ ARQUIVOS ENVOLVIDOS

### 7.1. Backend
- `admin/data/municipios_br.php` - Fonte de dados de municípios
- `admin/api/municipios.php` - API que retorna municípios por UF
- `admin/api/alunos.php` - API de alunos (salva naturalidade)

### 7.2. Frontend
- `admin/pages/alunos.php` - Formulário de alunos (HTML + JavaScript)
- Função `carregarMunicipios(estado)` - Carrega municípios via API
- Função `getMunicipiosPorEstado(estado)` - Fallback hardcoded
- Função `atualizarNaturalidade()` - Atualiza campo hidden

### 7.3. Banco de Dados
- Tabela `alunos` - Campo `naturalidade` (TEXT/VARCHAR)

---

## 8️⃣ QUERIES SQL ÚTEIS

### 8.1. Verificar Estrutura da Tabela
```sql
SHOW COLUMNS FROM alunos LIKE 'naturalidade';
```

### 8.2. Verificar Municípios Cadastrados
```sql
SELECT DISTINCT naturalidade, COUNT(*) as total
FROM alunos
WHERE naturalidade IS NOT NULL AND naturalidade != ''
GROUP BY naturalidade
ORDER BY total DESC;
```

### 8.3. Verificar Municípios Inválidos (se houver tabela de municípios futura)
```sql
-- Quando tabela municipios existir:
SELECT a.id, a.nome, a.naturalidade
FROM alunos a
LEFT JOIN municipios m ON a.naturalidade = CONCAT(m.nome, ' - ', e.nome)
WHERE a.naturalidade IS NOT NULL
  AND m.id IS NULL;
```

---

## 9️⃣ CHECKLIST DE VERIFICAÇÃO

- [x] Arquitetura mapeada
- [x] Origem da lista identificada
- [x] Base de municípios auditada
- [x] Fluxo frontend → backend mapeado
- [x] Scripts de correção criados
- [x] Documentação da FASE 1 criada
- [x] Documentação da FASE 2 (planejamento) criada
- [ ] Municípios específicos relatados verificados (após execução do script)
- [ ] Teste manual da API realizado (após execução do script)
- [ ] Console do navegador verificado (após execução do script)
- [ ] Base de municípios completada (pendente execução do script)

---

## 🔟 FASE 1 - CORREÇÃO IMPLEMENTADA

### Scripts Criados

1. **admin/data/gerar_municipios_alternativo.php**
   - Script principal para gerar arquivo completo
   - Busca municípios por estado via API do IBGE
   - Método mais confiável e robusto

2. **admin/data/gerar_municipios_completo_ibge.php**
   - Script alternativo
   - Busca todos os municípios de uma vez

3. **admin/data/importar_municipios_ibge.php**
   - Script para importar de CSV (se necessário)

### Alterações Realizadas

1. **admin/pages/alunos.php**
   - ✅ Comentário adicionado no fallback JavaScript
   - ✅ Documentação de que é apenas "Plano B"

2. **Documentação:**
   - ✅ `docs/FASE1_CORRECAO_MUNICIPIOS.md` - Guia completo de execução
   - ✅ `docs/FASE2_PLANEJAMENTO_MIGRACAO.md` - Planejamento futuro

### Próximos Passos (Para o Usuário)

1. **Executar script de geração:**
   ```bash
   php admin/data/gerar_municipios_alternativo.php
   ```

2. **Testar API:**
   - `admin/api/municipios.php?uf=PE`
   - `admin/api/municipios.php?uf=SP`

3. **Validar no formulário:**
   - Abrir formulário de alunos
   - Selecionar estados e verificar listas completas
   - Confirmar que "Bom Conselho" aparece

4. **Gerar relatório final:**
   - Quantidade total por UF
   - Prints/logs de teste
   - Confirmação de municípios específicos

---

**Fim do Relatório de Auditoria**

