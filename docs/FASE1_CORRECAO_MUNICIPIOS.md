# FASE 1 - Correção Imediata: Base Completa de Municípios

**Data:** 2024  
**Status:** Em execução  
**Objetivo:** Completar base de municípios sem alterar arquitetura

---

## 📋 Resumo

Esta fase corrige o problema de municípios faltando na lista de naturalidade, completando a base de dados em `admin/data/municipios_br.php` com todos os ~5.570 municípios do Brasil, mantendo a arquitetura atual (arquivo PHP + API).

---

## 🔧 Scripts e Ferramentas Criados

### 1. Script Oficial (PRINCIPAL)
**Arquivo:** `admin/data/gerar_municipios_alternativo.php`

**Status:** ✅ FLUXO OFICIAL DE GERAÇÃO

**Descrição:**  
Script principal e oficial para gerar `municipios_br.php` completo. Busca municípios por estado usando a API oficial do IBGE.

**Características:**
- ✅ Validações robustas (HTTP status, JSON, quantidade mínima)
- ✅ Compara com valores esperados por UF
- ✅ NÃO grava arquivo se houver erros críticos
- ✅ Cria backup automático antes de sobrescrever
- ✅ Exibe tabela completa UF | Encontrado | Esperado | Status
- ✅ Avisos claros se algum estado estiver abaixo do esperado

**Como usar:**
```bash
cd c:\xampp\htdocs\cfc-bom-conselho
php admin/data/gerar_municipios_alternativo.php
```

**O que faz:**
1. Busca municípios de cada estado via API do IBGE
2. Valida cada resposta (HTTP, JSON, quantidade)
3. Compara com valores esperados mínimos
4. Organiza por UF e ordena alfabeticamente
5. Gera `admin/data/municipios_br.php` completo (apenas se tudo estiver OK)

### 2. Script de Importação CSV (PLANO B)
**Arquivo:** `admin/data/importar_municipios_ibge.php`

**Status:** ✅ PLANO B (quando servidor não tem internet)

**Descrição:**  
Importa municípios de um arquivo CSV local quando a API do IBGE não está disponível.

**Requisitos:**
- Arquivo CSV em: `admin/data/fontes/municipios_ibge.csv`
- Estrutura: `nome_municipio,uf` ou `codigo_ibge,nome_municipio,uf`

**Como usar:**
```bash
php admin/data/importar_municipios_ibge.php
```

### 3. Painel Web (FACILITADOR)
**Arquivo:** `admin/tools/atualizar_municipios.php`

**Status:** ✅ INTERFACE WEB PARA OPERADOR

**Descrição:**  
Painel web que facilita a atualização sem precisar usar linha de comando.

**Funcionalidades:**
- Visualizar estatísticas atuais
- Atualizar via API do IBGE (botão)
- Atualizar via CSV local (botão)
- Ver tabela completa UF | Encontrado | Esperado | Status

**Acesso:**
- URL: `admin/tools/atualizar_municipios.php`
- Requer: Autenticação de administrador

### 4. Scripts Auxiliares
**Arquivos:**
- `admin/data/gerar_municipios_completo_ibge.php` - Método alternativo (não recomendado)
- `admin/data/importar_municipios_ibge.php` - Já documentado acima

---

## ✅ Passos para Execução

### OPÇÃO 1: Via Painel Web (RECOMENDADO)

1. Acesse: `admin/tools/atualizar_municipios.php`
2. Visualize estatísticas atuais
3. Escolha uma opção:
   - **Atualizar via API do IBGE** (se servidor tem internet)
   - **Atualizar via CSV Local** (se servidor não tem internet)
4. Aguarde processamento
5. Verifique resultado na tela

### OPÇÃO 2: Via CLI (Terminal)

#### Método A: API do IBGE (servidor com internet)

1. Abra o terminal/PowerShell
2. Navegue até o diretório do projeto:
   ```bash
   cd c:\xampp\htdocs\cfc-bom-conselho
   ```
3. Execute o script oficial:
   ```bash
   php admin/data/gerar_municipios_alternativo.php
   ```

#### Método B: CSV Local (servidor sem internet)

1. Baixe o CSV do IBGE com todos os municípios
2. Salve em: `admin/data/fontes/municipios_ibge.csv`
3. Execute:
   ```bash
   php admin/data/importar_municipios_ibge.php
   ```

### Passo 2: Verificar Resultado

O script exibirá:
- ✅ Tabela com UF | Encontrado | Esperado | Status
- ✅ Total de municípios (~5.570)
- ✅ Avisos ou erros (se houver)

**IMPORTANTE:** O script NÃO gravará o arquivo se houver erros críticos ou se algum estado estiver muito abaixo do esperado.

O arquivo gerado será:
- `admin/data/municipios_br.php` (novo)
- `admin/data/municipios_br.php.backup` (backup do anterior)

### Passo 3: Testar API

Teste a API diretamente no navegador:

```
http://localhost/cfc-bom-conselho/admin/api/municipios.php?uf=PE
http://localhost/cfc-bom-conselho/admin/api/municipios.php?uf=SP
http://localhost/cfc-bom-conselho/admin/api/municipios.php?uf=MG
```

**Resposta esperada:**
```json
{
  "success": true,
  "uf": "PE",
  "total": 185,
  "municipios": ["Abreu e Lima", "Afogados da Ingazeira", ..., "Bom Conselho", ...]
}
```

### Passo 4: Validar Municípios Específicos

Verifique se os municípios relatados aparecem:

**Pernambuco (PE):**
- ✅ Bom Conselho
- Verifique outros municípios relatados

**São Paulo (SP):**
- Deve retornar ~645 municípios

**Minas Gerais (MG):**
- Deve retornar ~853 municípios

### Passo 5: Testar no Formulário de Alunos

1. Acesse o módulo de Alunos
2. Abra o formulário de criar/editar aluno
3. Selecione um estado (ex: PE)
4. Verifique se a lista de municípios está completa
5. Procure por "Bom Conselho" na lista
6. Teste com outros estados críticos (SP, MG, BA)

---

## 🔍 Validações

### Checklist de Validação

- [ ] Script executado com sucesso
- [ ] Arquivo `municipios_br.php` gerado/atualizado
- [ ] API retorna municípios corretamente
- [ ] PE retorna ~185 municípios
- [ ] SP retorna ~645 municípios
- [ ] MG retorna ~853 municípios
- [ ] "Bom Conselho" aparece na lista de PE
- [ ] Formulário de alunos carrega municípios corretamente
- [ ] Não há erros no console do navegador
- [ ] Fallback JavaScript funciona (se API falhar)

### Municípios para Validar

**Pernambuco:**
- ✅ Bom Conselho
- Outros municípios relatados pelo usuário

**Outros estados:**
- Validar municípios específicos relatados

---

## 📊 Estatísticas Esperadas

Após a correção, o arquivo deve conter:

| UF | Municípios Esperados |
|----|---------------------|
| AC | 22 |
| AL | 102 |
| AP | 16 |
| AM | 62 |
| BA | 417 |
| CE | 184 |
| DF | 1 |
| ES | 78 |
| GO | 246 |
| MA | 217 |
| MT | 142 |
| MS | 79 |
| MG | 853 |
| PA | 144 |
| PB | 223 |
| PR | 399 |
| PE | 185 |
| PI | 224 |
| RJ | 92 |
| RN | 167 |
| RS | 497 |
| RO | 52 |
| RR | 15 |
| SC | 295 |
| SP | 645 |
| SE | 75 |
| TO | 139 |

**Total:** ~5.570 municípios

---

## 🐛 Troubleshooting

### Problema: Script não executa

**Solução:**
- Verifique se PHP está instalado: `php -v`
- Verifique se está no diretório correto
- Verifique permissões de escrita no diretório `admin/data/`

### Problema: API retorna erro 404

**Solução:**
- Verifique se o arquivo `municipios_br.php` foi gerado
- Verifique se a função `getMunicipiosBrasil()` existe
- Verifique logs de erro do PHP

### Problema: Municípios não aparecem na tela

**Solução:**
1. Abra o console do navegador (F12)
2. Verifique se há erros JavaScript
3. Verifique a requisição AJAX para `api/municipios.php`
4. Verifique se a resposta JSON está correta
5. Limpe o cache do navegador

### Problema: Script demora muito

**Solução:**
- O script faz requisições para cada estado
- Pode levar alguns minutos
- Aguarde a conclusão

---

## 📝 Alterações Realizadas

### Arquivos Modificados

1. **admin/data/municipios_br.php**
   - ✅ Atualizado com base completa de municípios (via script)

2. **admin/pages/alunos.php**
   - ✅ Comentário adicionado no fallback JavaScript
   - ✅ Documentação de que é apenas "Plano B"

### Arquivos Criados

1. **admin/data/gerar_municipios_alternativo.php**
   - Script para gerar arquivo completo

2. **admin/data/gerar_municipios_completo_ibge.php**
   - Script alternativo

3. **admin/data/importar_municipios_ibge.php**
   - Script para importar de CSV (se necessário)

4. **docs/FASE1_CORRECAO_MUNICIPIOS.md**
   - Esta documentação

---

## ✅ Resultado Final Esperado

Após a execução bem-sucedida:

1. ✅ Arquivo `municipios_br.php` com ~5.570 municípios
2. ✅ API retorna todos os municípios por estado
3. ✅ Formulário de alunos exibe lista completa
4. ✅ Municípios relatados (ex: Bom Conselho) aparecem
5. ✅ Fallback JavaScript documentado como "Plano B"
6. ✅ Sem erros no console do navegador

---

## 🔄 Próximos Passos (FASE 2)

Após concluir a FASE 1, a FASE 2 planejará:
- Migração para banco de dados
- Tabelas `estados` e `municipios`
- FK em `alunos.naturalidade_municipio_id`
- Migração de dados existentes

**Nota:** FASE 2 não será implementada agora, apenas documentada.

---

**Fim da Documentação da FASE 1**

