# FASE 2 - Planejamento: Migração para Banco de Dados

**Data:** 2024  
**Status:** Planejamento (NÃO IMPLEMENTADO)  
**Objetivo:** Documentar proposta de migração futura para banco de dados

---

## 📋 Visão Geral

Esta fase documenta a proposta de migração da base de municípios de arquivo PHP estático para banco de dados relacional, melhorando:
- Validação de dados
- Consistência
- Performance
- Manutenibilidade

**⚠️ IMPORTANTE:** Esta fase NÃO será implementada agora. Apenas documentada para referência futura.

---

## 🗄️ Proposta de Estrutura de Banco de Dados

### Tabela: `estados`

```sql
CREATE TABLE estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sigla CHAR(2) NOT NULL UNIQUE,
    nome VARCHAR(50) NOT NULL,
    codigo_ibge INT NOT NULL UNIQUE,
    regiao ENUM('Norte', 'Nordeste', 'Centro-Oeste', 'Sudeste', 'Sul') NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_sigla (sigla),
    INDEX idx_codigo_ibge (codigo_ibge)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `municipios`

```sql
CREATE TABLE municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    estado_id INT NOT NULL,
    codigo_ibge INT NOT NULL UNIQUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_estado (estado_id),
    INDEX idx_nome (nome),
    INDEX idx_codigo_ibge (codigo_ibge),
    UNIQUE KEY unique_municipio_estado (nome, estado_id),
    
    FOREIGN KEY (estado_id) REFERENCES estados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Alteração na Tabela: `alunos`

```sql
-- Adicionar campo para FK (mantendo campo antigo temporariamente)
ALTER TABLE alunos 
ADD COLUMN naturalidade_municipio_id INT NULL AFTER naturalidade,
ADD INDEX idx_naturalidade_municipio (naturalidade_municipio_id),
ADD FOREIGN KEY (naturalidade_municipio_id) REFERENCES municipios(id) ON DELETE SET NULL;
```

---

## 🔄 Estratégia de Migração de Dados

### Passo 1: Popular Tabelas `estados` e `municipios`

```sql
-- Inserir estados
INSERT INTO estados (sigla, nome, codigo_ibge, regiao) VALUES
('AC', 'Acre', 12, 'Norte'),
('AL', 'Alagoas', 27, 'Nordeste'),
-- ... todos os estados
('TO', 'Tocantins', 17, 'Norte');

-- Inserir municípios (via script PHP que lê municipios_br.php ou API do IBGE)
-- Script migrará todos os ~5.570 municípios
```

### Passo 2: Migrar Dados Existentes em `alunos.naturalidade`

**Estratégia de Matching:**

```php
// Pseudocódigo da migração
foreach ($alunos as $aluno) {
    if (empty($aluno['naturalidade'])) {
        continue;
    }
    
    // Padrão: "Município - Estado"
    // Ex: "Bom Conselho - Pernambuco"
    $partes = explode(' - ', $aluno['naturalidade']);
    
    if (count($partes) === 2) {
        $nomeMunicipio = trim($partes[0]);
        $nomeEstado = trim($partes[1]);
        
        // Buscar estado por nome
        $estado = buscarEstadoPorNome($nomeEstado);
        
        if ($estado) {
            // Buscar município por nome e estado
            $municipio = buscarMunicipioPorNomeEEstado($nomeMunicipio, $estado['id']);
            
            if ($municipio) {
                // Atualizar aluno com FK
                atualizarAlunoNaturalidade($aluno['id'], $municipio['id']);
            } else {
                // Log de município não encontrado para revisão manual
                logarMunicipioNaoEncontrado($aluno['id'], $nomeMunicipio, $nomeEstado);
            }
        }
    }
}
```

**Tratamento de Casos Especiais:**
- Municípios com nomes ligeiramente diferentes
- Estados com nomes diferentes (ex: "Pernambuco" vs "PE")
- Dados inválidos ou mal formatados
- Revisão manual de casos não encontrados

### Passo 3: Validação e Limpeza

```sql
-- Verificar quantos alunos foram migrados
SELECT 
    COUNT(*) as total,
    COUNT(naturalidade_municipio_id) as migrados,
    COUNT(*) - COUNT(naturalidade_municipio_id) as pendentes
FROM alunos
WHERE naturalidade IS NOT NULL AND naturalidade != '';

-- Listar alunos não migrados para revisão
SELECT id, nome, naturalidade
FROM alunos
WHERE naturalidade IS NOT NULL 
  AND naturalidade != ''
  AND naturalidade_municipio_id IS NULL;
```

---

## 🔧 Ajustes na API

### Nova API: `admin/api/municipios.php`

```php
<?php
// Nova versão que lê do banco de dados

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/database.php';

$db = db();
$uf = isset($_GET['uf']) ? strtoupper(trim($_GET['uf'])) : '';

if (empty($uf)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parâmetro UF é obrigatório'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Buscar municípios do banco
$municipios = $db->fetchAll("
    SELECT m.nome
    FROM municipios m
    INNER JOIN estados e ON m.estado_id = e.id
    WHERE e.sigla = ?
    ORDER BY m.nome ASC
", [$uf]);

$nomesMunicipios = array_column($municipios, 'nome');

echo json_encode([
    'success' => true,
    'uf' => $uf,
    'total' => count($nomesMunicipios),
    'municipios' => $nomesMunicipios
], JSON_UNESCAPED_UNICODE);
```

---

## 📝 Alterações no Frontend

### Campo de Naturalidade

**Antes (texto livre):**
```html
<input type="hidden" id="naturalidade" name="naturalidade">
```

**Depois (FK):**
```html
<input type="hidden" id="naturalidade_municipio_id" name="naturalidade_municipio_id">
```

**JavaScript:**
```javascript
// Ao selecionar município, salvar o ID em vez do texto
function atualizarNaturalidade() {
    const municipioSelect = document.getElementById('naturalidade_municipio');
    const municipioId = municipioSelect.value; // Agora será o ID
    const naturalidadeInput = document.getElementById('naturalidade_municipio_id');
    naturalidadeInput.value = municipioId;
}
```

---

## ✅ Benefícios da Migração

1. **Validação:** Garante que apenas municípios válidos sejam cadastrados
2. **Consistência:** Evita erros de digitação e variações de nome
3. **Performance:** Consultas indexadas mais rápidas
4. **Manutenibilidade:** Atualizações centralizadas no banco
5. **Relatórios:** Facilita consultas e análises por região
6. **Integridade:** Foreign keys garantem dados consistentes

---

## ⚠️ Riscos e Considerações

### Riscos

1. **Migração de dados:** Alguns registros podem não ser migrados automaticamente
2. **Downtime:** Migração pode exigir manutenção
3. **Compatibilidade:** Código legado pode depender do formato antigo
4. **Performance inicial:** Primeira carga pode ser lenta

### Mitigações

1. **Backup completo** antes da migração
2. **Migração em etapas** (estados por vez)
3. **Manter campo antigo** temporariamente para compatibilidade
4. **Script de rollback** caso necessário
5. **Testes extensivos** em ambiente de desenvolvimento

---

## 📅 Cronograma Sugerido (Futuro)

1. **Fase 2.1:** Criar tabelas e popular com dados do IBGE
2. **Fase 2.2:** Desenvolver script de migração de dados existentes
3. **Fase 2.3:** Atualizar API para ler do banco
4. **Fase 2.4:** Atualizar frontend para usar FK
5. **Fase 2.5:** Migrar dados existentes
6. **Fase 2.6:** Validação e testes
7. **Fase 2.7:** Remover campo antigo (após período de transição)

---

## 🔗 Referências

- [IBGE - Estrutura Territorial](https://www.ibge.gov.br/explica/codigos-dos-municipios.php)
- [API IBGE - Municípios](https://servicodados.ibge.gov.br/api/v1/localidades/municipios)
- Documentação da FASE 1: `docs/FASE1_CORRECAO_MUNICIPIOS.md`

---

**Fim da Documentação da FASE 2 (Planejamento)**

