# RESUMO - CORREÇÃO DE EDIÇÃO DO ALUNO

## Data: 2025-11-19

## Problema Identificado

Ao editar um aluno, vários campos que foram preenchidos na criação não retornavam preenchidos no modal de edição:

1. **Órgão Emissor (RG)** - `rg_orgao_emissor`
2. **UF do RG** - `rg_uf`
3. **Data de Emissão do RG** - `rg_data_emissao`
4. **Estado Civil** - `estado_civil`
5. **Profissão** - `profissao`
6. **Escolaridade** - `escolaridade`
7. **Estado (Naturalidade)** - `naturalidade_estado` (já funcionava parcialmente)
8. **Município (Naturalidade)** - `naturalidade_municipio` (já funcionava parcialmente)
9. **Nacionalidade** - `nacionalidade` (já funcionava)
10. **Telefone Secundário** - `telefone_secundario`
11. **Contato de Emergência – Nome** - `contato_emergencia_nome`
12. **Contato de Emergência – Telefone** - `contato_emergencia_telefone`
13. **Seleção da LGPD** - `lgpd_consentimento`
14. **Data/Hora do Consentimento LGPD** - `lgpd_consentimento_em`

## Causas Identificadas

### 1. Campos não estavam sendo salvos no POST
- **Arquivo**: `admin/api/alunos.php` (linha ~485)
- **Problema**: O array `$alunoData` não incluía os campos adicionais
- **Solução**: Adicionados todos os campos faltantes no array `$alunoData`

### 2. Campos não estavam sendo enviados do frontend
- **Arquivo**: `admin/pages/alunos.php` (função `saveAlunoDados`, linha ~6696)
- **Problema**: O `FormData` não incluía os campos adicionais
- **Solução**: Adicionados todos os campos faltantes no `dadosFormData`

### 3. Campos não estavam sendo preenchidos na edição
- **Arquivo**: `admin/pages/alunos.php` (função `preencherFormularioAluno`, linha ~4314)
- **Problema**: O array `campos` não incluía os campos adicionais
- **Solução**: Adicionados todos os campos faltantes no array `campos` e tratamento especial para selects e checkbox LGPD

### 4. Campos não existiam na tabela do banco
- **Arquivo**: `admin/api/alunos.php` (linha ~227)
- **Problema**: Os campos não existiam na tabela `alunos`
- **Solução**: Adicionada verificação e criação automática dos campos faltantes (similar ao que já era feito para `renach` e `foto`)

### 5. PUT não incluía campos vazios
- **Arquivo**: `admin/api/alunos.php` (linha ~723)
- **Problema**: O `array_filter` removia campos vazios, impedindo limpeza de campos
- **Solução**: Substituído por lista explícita de campos permitidos

## Arquivos Alterados

### 1. `admin/api/alunos.php`

#### Alterações no POST (criação/edição):
- **Linha ~485**: Adicionados campos no array `$alunoData`:
  - `rg_orgao_emissor`
  - `rg_uf`
  - `rg_data_emissao`
  - `estado_civil`
  - `profissao`
  - `escolaridade`
  - `telefone_secundario`
  - `contato_emergencia_nome`
  - `contato_emergencia_telefone`
  - `lgpd_consentimento`
  - `lgpd_consentimento_em`

#### Verificação e criação automática de campos:
- **Linha ~227**: Adicionada verificação e criação automática dos campos faltantes na tabela `alunos`:
  - `rg_orgao_emissor` (VARCHAR(10))
  - `rg_uf` (CHAR(2))
  - `rg_data_emissao` (DATE)
  - `estado_civil` (VARCHAR(50))
  - `profissao` (VARCHAR(100))
  - `escolaridade` (VARCHAR(50))
  - `telefone_secundario` (VARCHAR(20))
  - `contato_emergencia_nome` (VARCHAR(100))
  - `contato_emergencia_telefone` (VARCHAR(20))
  - `lgpd_consentimento` (TINYINT(1))
  - `lgpd_consentimento_em` (DATETIME)

#### Alterações no PUT (atualização):
- **Linha ~723**: Substituído `array_filter` por lista explícita de campos permitidos
- **Linha ~746**: Adicionado processamento especial para LGPD:
  - Se `lgpd_consentimento = 1` e não houver data, define data atual
  - Se `lgpd_consentimento = 0`, limpa a data

### 2. `admin/pages/alunos.php`

#### Função `saveAlunoDados` (linha ~6696):
- Adicionados campos no `dadosFormData`:
  - `rg_orgao_emissor`
  - `rg_uf`
  - `rg_data_emissao`
  - `estado_civil`
  - `profissao`
  - `escolaridade`
  - `telefone_secundario`
  - `contato_emergencia_nome`
  - `contato_emergencia_telefone`
  - `lgpd_consentimento`
  - `lgpd_consentimento_em`

#### Função `preencherFormularioAluno` (linha ~4314):
- **Array `campos`**: Adicionados todos os campos faltantes
- **Tratamento de selects**: Adicionado tratamento especial para elementos `SELECT` (estado_civil, escolaridade, rg_uf)
- **Tratamento de LGPD**: Adicionado tratamento especial para checkbox e campo de data/hora:
  - Checkbox `lgpd_consentimento` é marcado/desmarcado conforme valor salvo
  - Campo `lgpd_consentimento_em` é formatado para exibição (dd/mm/aaaa hh:mm)

## Campos Adicionados na Tabela `alunos`

Os seguintes campos foram adicionados automaticamente (se não existirem):

1. `rg_orgao_emissor` - VARCHAR(10) DEFAULT ''
2. `rg_uf` - CHAR(2) DEFAULT ''
3. `rg_data_emissao` - DATE NULL
4. `estado_civil` - VARCHAR(50) DEFAULT ''
5. `profissao` - VARCHAR(100) DEFAULT ''
6. `escolaridade` - VARCHAR(50) DEFAULT ''
7. `telefone_secundario` - VARCHAR(20) DEFAULT ''
8. `contato_emergencia_nome` - VARCHAR(100) DEFAULT ''
9. `contato_emergencia_telefone` - VARCHAR(20) DEFAULT ''
10. `lgpd_consentimento` - TINYINT(1) DEFAULT 0
11. `lgpd_consentimento_em` - DATETIME NULL

## Regras de Negócio Implementadas

### LGPD
- Se o checkbox `lgpd_consentimento` for marcado e não houver data, a data atual é definida automaticamente
- Se o checkbox for desmarcado, a data é limpa (null)
- A data/hora do consentimento é exibida no formato brasileiro (dd/mm/aaaa hh:mm) no campo readonly

### Data de Emissão do RG
- Campo do tipo `date` (input HTML5)
- Formato salvo no banco: YYYY-MM-DD
- Formato exibido: YYYY-MM-DD (formato nativo do input date)

### Selects (Estado Civil, Escolaridade, UF do RG)
- Valores são definidos diretamente no `value` do select
- Se o valor não existir nas opções, um aviso é logado no console (mas não bloqueia)

## Testes Recomendados

### 1. Criar novo aluno com todos os campos
- Preencher todos os campos listados acima
- Salvar
- Verificar no banco se todos os campos foram salvos

### 2. Editar aluno criado
- Abrir modal de edição
- Verificar se todos os campos aparecem preenchidos corretamente:
  - ✅ Órgão Emissor
  - ✅ UF do RG
  - ✅ Data Emissão RG
  - ✅ Estado Civil (select)
  - ✅ Profissão
  - ✅ Escolaridade (select)
  - ✅ Estado (Naturalidade) (select)
  - ✅ Município (Naturalidade) (select)
  - ✅ Nacionalidade
  - ✅ Telefone Secundário
  - ✅ Contato de Emergência – Nome
  - ✅ Contato de Emergência – Telefone
  - ✅ Checkbox LGPD
  - ✅ Data/Hora do Consentimento LGPD

### 3. Editar e salvar novamente
- Alterar alguns campos
- Salvar
- Verificar se as alterações foram salvas corretamente

### 4. Testar LGPD
- Marcar checkbox LGPD em novo aluno → verificar se data é preenchida automaticamente
- Desmarcar checkbox LGPD → verificar se data é limpa
- Editar aluno com LGPD já marcado → verificar se checkbox e data aparecem corretamente

## Observações Importantes

1. **Campos criados automaticamente**: Os campos são criados automaticamente na primeira execução da API, similar ao que já era feito com `renach` e `foto`

2. **Compatibilidade**: As alterações são retrocompatíveis - alunos existentes sem esses campos terão valores vazios/null, mas não causarão erros

3. **LGPD**: A data do consentimento só é alterada quando o checkbox é marcado/desmarcado. Edições normais do aluno não alteram a data do consentimento

4. **Selects**: Os valores dos selects devem corresponder exatamente aos valores das opções (`value`). Se houver divergência, um aviso será logado no console

## Status

✅ **Concluído**

Todos os campos foram:
- ✅ Adicionados ao salvamento (POST)
- ✅ Adicionados ao envio do frontend (`saveAlunoDados`)
- ✅ Adicionados ao preenchimento do formulário (`preencherFormularioAluno`)
- ✅ Adicionados à atualização (PUT)
- ✅ Criados automaticamente na tabela (se não existirem)
- ✅ Tratados corretamente (selects, checkbox, data)

