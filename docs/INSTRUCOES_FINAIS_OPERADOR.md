# 🎯 INSTRUÇÕES FINAIS PARA O OPERADOR

**FASE 1 - Correção de Municípios: PRONTA PARA EXECUÇÃO**

---

## 📋 RESUMO RÁPIDO

**Problema:** Municípios faltando na lista de naturalidade  
**Solução:** Base completa de ~5.570 municípios do Brasil  
**Status:** ✅ Implementação completa e robusta

---

## 🚀 COMO EXECUTAR (ESCOLHA UMA OPÇÃO)

### OPÇÃO 1: Via Painel Web (MAIS FÁCIL)

1. Acesse no navegador:
   ```
   http://localhost/cfc-bom-conselho/admin/tools/atualizar_municipios.php
   ```

2. Visualize as estatísticas atuais

3. Escolha uma opção:
   - **"Atualizar via API do IBGE"** (se servidor tem internet)
   - **"Atualizar via CSV Local"** (se servidor não tem internet)

4. Aguarde processamento (2-5 minutos)

5. Verifique a tabela de validação na tela

### OPÇÃO 2: Via Terminal/CLI

#### Se servidor TEM internet:

```bash
cd c:\xampp\htdocs\cfc-bom-conselho
php admin/data/gerar_municipios_alternativo.php
```

#### Se servidor NÃO TEM internet:

1. Baixe CSV do IBGE: https://www.ibge.gov.br/explica/codigos-dos-municipios.php
2. Salve em: `admin/data/fontes/municipios_ibge.csv`
3. Execute:
```bash
php admin/data/importar_municipios_ibge.php
```

---

## ✅ O QUE ESPERAR APÓS EXECUÇÃO

### Tabela de Validação (Exemplo)

```
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

Total de municípios: 5570
Total de estados: 27
```

### Validações Automáticas

O script **NÃO gravará o arquivo** se:
- ❌ Algum estado falhar na busca
- ❌ Algum estado tiver quantidade muito abaixo do esperado
- ❌ Total estiver muito baixo (< 95% do esperado)

**Se tudo estiver OK:**
- ✅ Arquivo `municipios_br.php` será gerado
- ✅ Backup `municipios_br.php.backup` será criado
- ✅ Mensagem "✓ CONCLUÍDO COM SUCESSO" será exibida

---

## 🧪 TESTES RÁPIDOS

### Teste 1: API

No navegador, acesse:
```
http://localhost/cfc-bom-conselho/admin/api/municipios.php?uf=PE
```

**Deve retornar:**
- `success: true`
- `total: 185` (ou próximo)
- "Bom Conselho" na lista de municípios

### Teste 2: Formulário

1. Abra formulário de criar/editar aluno
2. Selecione estado "PE"
3. Verifique se "Bom Conselho" aparece na lista

---

## 📊 RESULTADO ESPERADO

Após execução bem-sucedida:

| Item | Esperado | Status |
|------|----------|--------|
| Total de municípios | ~5.570 | ✅ |
| Estados processados | 27/27 | ✅ |
| PE - Municípios | 185 | ✅ |
| SP - Municípios | 645 | ✅ |
| MG - Municípios | 853 | ✅ |
| "Bom Conselho" em PE | Aparece | ✅ |

---

## 📁 ONDE ESTÃO OS ARQUIVOS

### Scripts de Geração

- **Oficial (API IBGE):** `admin/data/gerar_municipios_alternativo.php`
- **Plano B (CSV):** `admin/data/importar_municipios_ibge.php`

### Arquivo Gerado

- **Fonte oficial:** `admin/data/municipios_br.php`
- **Backup:** `admin/data/municipios_br.php.backup`

### Painel Web

- **URL:** `admin/tools/atualizar_municipios.php`

### Documentação

- **Guia completo:** `docs/FASE1_CORRECAO_MUNICIPIOS.md`
- **Resumo executivo:** `docs/RESUMO_EXECUTIVO_FASE1.md`
- **Guia de validação:** `docs/EXECUCAO_VALIDACAO_FASE1.md`

---

## ⚠️ IMPORTANTE

1. **O script NÃO gravará arquivo incompleto**
   - Se houver erros, o arquivo anterior será mantido
   - Revise os erros e execute novamente

2. **Sempre use o script oficial**
   - `gerar_municipios_alternativo.php` é o único oficial
   - Outros scripts são auxiliares

3. **Valide após execução**
   - Teste a API
   - Teste o formulário
   - Confirme municípios específicos

---

## 🆘 PRECISA DE AJUDA?

Consulte:
- `docs/FASE1_CORRECAO_MUNICIPIOS.md` - Guia completo
- `docs/EXECUCAO_VALIDACAO_FASE1.md` - Troubleshooting

---

**Pronto para executar! 🚀**

