# ✅ EXECUÇÃO E VALIDAÇÃO: FASE 1

**Data:** 2024  
**Status:** Aguardando execução e validação

---

## 🎯 OBJETIVO

Este documento guia a execução e validação completa da FASE 1, garantindo que a base de municípios está 100% completa e funcionando.

---

## 📋 CHECKLIST DE EXECUÇÃO

### Passo 1: Executar Script Oficial

**Opção A - Via Painel Web (Recomendado):**
1. Acesse: `admin/tools/atualizar_municipios.php`
2. Visualize estatísticas atuais
3. Clique em "Atualizar via API do IBGE"
4. Aguarde processamento (2-5 minutos)
5. Verifique resultado na tela

**Opção B - Via CLI:**
```bash
cd c:\xampp\htdocs\cfc-bom-conselho
php admin/data/gerar_municipios_alternativo.php
```

**Resultado Esperado:**
```
╔════════════════════════════════════════════════════════════════╗
║  GERADOR OFICIAL DE MUNICÍPIOS DO BRASIL (IBGE)                ║
╚════════════════════════════════════════════════════════════════╝

FASE 1: Buscando municípios por estado via API do IBGE...
------------------------------------------------------------
  [AC] Buscando... ✓ 22 municípios
  [AL] Buscando... ✓ 102 municípios
  ...
  [PE] Buscando... ✓ 185 municípios
  [SP] Buscando... ✓ 645 municípios
  [MG] Buscando... ✓ 853 municípios
  ...
------------------------------------------------------------

FASE 2: Validações finais...
------------------------------------------------------------

RESUMO:
  Total de estados processados: 27 / 27
  Total de municípios: 5570
  Erros encontrados: 0
  Avisos: 0

TABELA DE MUNICÍPIOS POR UF:
------------------------------------------------------------
  UF    | Encontrado   | Esperado     | Status
------------------------------------------------------------
  AC    | 22           | 22           | ✓ OK
  AL    | 102          | 102          | ✓ OK
  ...
  PE    | 185          | 185          | ✓ OK
  SP    | 645          | 645          | ✓ OK
  MG    | 853          | 853          | ✓ OK
  ...
------------------------------------------------------------

FASE 3: Gerando arquivo...
------------------------------------------------------------
  ✓ Backup criado: municipios_br.php.backup
  ✓ Arquivo gerado: municipios_br.php
  ✓ Total de municípios: 5570
  ✓ Total de estados: 27

╔════════════════════════════════════════════════════════════════╗
║  ✓ CONCLUÍDO COM SUCESSO                                       ║
╚════════════════════════════════════════════════════════════════╝
```

### Passo 2: Validar Arquivo Gerado

**Verificações:**
- [ ] Arquivo `admin/data/municipios_br.php` existe
- [ ] Arquivo é legível (não corrompido)
- [ ] Função `getMunicipiosBrasil()` existe
- [ ] Backup `municipios_br.php.backup` foi criado

**Teste rápido:**
```php
<?php
require_once 'admin/data/municipios_br.php';
$municipios = getMunicipiosBrasil();
echo "Total: " . array_sum(array_map('count', $municipios)) . "\n";
echo "PE: " . count($municipios['PE'] ?? []) . "\n";
echo "SP: " . count($municipios['SP'] ?? []) . "\n";
```

### Passo 3: Testar API

**Teste 1: Pernambuco (PE)**
```
URL: admin/api/municipios.php?uf=PE
```

**Resposta esperada:**
```json
{
  "success": true,
  "uf": "PE",
  "total": 185,
  "municipios": [
    "Abreu e Lima",
    "Afogados da Ingazeira",
    ...
    "Bom Conselho",  ← DEVE APARECER
    ...
    "Vitória de Santo Antão"
  ]
}
```

**Validações:**
- [ ] `success: true`
- [ ] `uf: "PE"`
- [ ] `total: 185` (ou próximo)
- [ ] "Bom Conselho" está na lista
- [ ] Lista ordenada alfabeticamente

**Teste 2: São Paulo (SP)**
```
URL: admin/api/municipios.php?uf=SP
```

**Validações:**
- [ ] `total: 645` (ou próximo)
- [ ] Lista longa e completa

**Teste 3: Minas Gerais (MG)**
```
URL: admin/api/municipios.php?uf=MG
```

**Validações:**
- [ ] `total: 853` (ou próximo)
- [ ] Lista muito longa e completa

### Passo 4: Validar no Formulário de Alunos

1. **Abrir formulário:**
   - Acesse módulo de Alunos
   - Clique em "Novo Aluno" ou edite um existente

2. **Testar estados:**
   - Selecione "PE" no campo "Estado (Naturalidade)"
   - Aguarde carregamento
   - Verifique lista de municípios

3. **Validações:**
   - [ ] Lista carrega automaticamente
   - [ ] Lista é longa (não cortada)
   - [ ] "Bom Conselho" aparece na lista
   - [ ] Pode selecionar "Bom Conselho"
   - [ ] Campo "Naturalidade" é preenchido corretamente

4. **Testar outros estados:**
   - [ ] SP: Lista muito longa (~645 municípios)
   - [ ] MG: Lista muito longa (~853 municípios)
   - [ ] BA: Lista longa (~417 municípios)
   - [ ] RS: Lista longa (~497 municípios)

### Passo 5: Verificar Console do Navegador

1. Abra DevTools (F12)
2. Vá para aba "Console"
3. Selecione um estado no formulário
4. Verifique:

**Sem erros:**
- [ ] Nenhum erro JavaScript (vermelho)
- [ ] Nenhum warning crítico
- [ ] Mensagens de log mostram sucesso:
  - `✅ X municípios carregados para PE (via API)`

**Se houver erros:**
- [ ] Verificar requisição AJAX na aba "Network"
- [ ] Verificar se `api/municipios.php` retorna 200
- [ ] Verificar resposta JSON

### Passo 6: Validar Municípios Específicos

**Municípios para validar (especialmente os relatados pelo usuário):**

- [ ] **PE - Bom Conselho:** Aparece na lista
- [ ] Outros municípios relatados (se houver)

**Como validar:**
1. Abrir formulário de alunos
2. Selecionar estado correspondente
3. Procurar município na lista (Ctrl+F)
4. Confirmar que aparece

---

## 📊 RESULTADO ESPERADO

### Tabela Final de Validação

Após execução bem-sucedida, você deve ter:

| UF | Encontrado | Esperado | Status | Validação API | Validação Formulário |
|----|------------|----------|--------|---------------|---------------------|
| PE | 185 | 185 | ✓ OK | ✅ | ✅ |
| SP | 645 | 645 | ✓ OK | ✅ | ✅ |
| MG | 853 | 853 | ✓ OK | ✅ | ✅ |
| BA | 417 | 417 | ✓ OK | ✅ | ✅ |
| ... | ... | ... | ... | ... | ... |
| **TOTAL** | **~5.570** | **~5.570** | **✓ OK** | **✅** | **✅** |

### Evidências a Coletar

1. **Screenshot do terminal** após execução do script
2. **Resposta JSON da API** (PE, SP, MG)
3. **Screenshot do formulário** mostrando lista completa
4. **Screenshot do console** (sem erros)
5. **Confirmação explícita:**
   - "Bom Conselho" aparece em PE
   - Outros municípios relatados aparecem

---

## 🐛 TROUBLESHOOTING

### Problema: Script não executa

**Verificar:**
- PHP instalado: `php -v`
- Extensão cURL habilitada: `php -m | grep curl`
- Permissões de escrita em `admin/data/`
- Conexão com internet (para API IBGE)

### Problema: Script retorna erros

**Se algum estado falhar:**
- Verificar conexão com internet
- Verificar se API do IBGE está acessível
- Tentar novamente (pode ser instabilidade temporária)

**Se quantidade estiver baixa:**
- Verificar se todos os estados foram processados
- Comparar com valores esperados
- Script não gravará se houver erros críticos

### Problema: API retorna erro 404

**Verificar:**
- Arquivo `municipios_br.php` existe
- Função `getMunicipiosBrasil()` existe
- Arquivo não está corrompido
- Permissões de leitura

### Problema: Municípios não aparecem no formulário

**Verificar:**
1. Console do navegador (F12)
2. Requisição AJAX na aba "Network"
3. Resposta da API
4. Cache do navegador (limpar)
5. Erros JavaScript

---

## ✅ CRITÉRIOS DE SUCESSO

A FASE 1 será considerada **100% concluída** quando:

1. ✅ Script executado sem erros críticos
2. ✅ Arquivo `municipios_br.php` gerado com ~5.570 municípios
3. ✅ Todos os estados com status "✓ OK" na validação
4. ✅ API retorna municípios corretamente (PE, SP, MG)
5. ✅ "Bom Conselho" aparece na lista de PE
6. ✅ Formulário de alunos carrega listas completas
7. ✅ Console do navegador sem erros
8. ✅ Municípios específicos relatados aparecem

---

## 📝 REGISTRO DE EXECUÇÃO

**Data de Execução:** _______________

**Método Utilizado:**
- [ ] Painel Web
- [ ] CLI

**Resultado do Script:**
- Total de municípios: _______
- Total de estados: _______
- Erros: _______
- Avisos: _______

**Validação da API:**
- PE: _______ municípios (esperado: 185)
- SP: _______ municípios (esperado: 645)
- MG: _______ municípios (esperado: 853)

**Validação do Formulário:**
- [ ] Listas carregam corretamente
- [ ] "Bom Conselho" aparece em PE
- [ ] Sem erros no console

**Observações:**
_________________________________________________
_________________________________________________

---

**Fim do Guia de Execução e Validação**

