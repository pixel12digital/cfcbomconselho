# Correção dos Campos Não Preenchidos no Modal de Instrutores

## Problema Identificado

O modal de edição de instrutores não estava preenchendo vários campos que estavam presentes na tabela do banco de dados, incluindo:

- **CPF** (`cpf`)
- **Data de Nascimento** (`data_nascimento`)
- **Horário Início** (`horario_inicio`)
- **Horário Fim** (`horario_fim`)
- **Endereço** (`endereco`)
- **Cidade** (`cidade`)
- **UF** (`uf`)
- **Tipo de Carga** (`tipo_carga`)
- **Validade da Credencial** (`validade_credencial`)
- **Observações** (`observacoes`)
- **Categorias de Habilitação** (`categoria_habilitacao`)
- **Dias da Semana** (`dias_semana`)

## Causa do Problema

O JavaScript no `instrutores.js` estava preenchendo apenas os campos básicos (nome, email, telefone, credencial, cfc_id, ativo, usuario_id), mas não estava incluindo os campos adicionais que existem na tabela `instrutores`.

## Solução Implementada

### Antes da Correção:
```javascript
// Preencher formulário
const nomeField = document.getElementById('nome');
const emailField = document.getElementById('email');
const telefoneField = document.getElementById('telefone');
const credencialField = document.getElementById('credencial');
const cfcField = document.getElementById('cfc_id');
const ativoField = document.getElementById('ativo');
const usuarioField = document.getElementById('usuario_id');

if (nomeField) nomeField.value = instrutor.nome || '';
if (emailField) emailField.value = instrutor.email || '';
if (telefoneField) telefoneField.value = instrutor.telefone || '';
if (credencialField) credencialField.value = instrutor.credencial || '';
if (cfcField) cfcField.value = instrutor.cfc_id || '';
if (ativoField) ativoField.value = instrutor.ativo ? '1' : '0';
```

### Depois da Correção:
```javascript
// Preencher formulário
const nomeField = document.getElementById('nome');
const emailField = document.getElementById('email');
const telefoneField = document.getElementById('telefone');
const credencialField = document.getElementById('credencial');
const cfcField = document.getElementById('cfc_id');
const ativoField = document.getElementById('ativo');
const usuarioField = document.getElementById('usuario_id');

// Campos adicionais que estavam faltando
const cpfField = document.getElementById('cpf');
const cnhField = document.getElementById('cnh');
const dataNascimentoField = document.getElementById('data_nascimento');
const horarioInicioField = document.getElementById('horario_inicio');
const horarioFimField = document.getElementById('horario_fim');
const enderecoField = document.getElementById('endereco');
const cidadeField = document.getElementById('cidade');
const ufField = document.getElementById('uf');
const tipoCargaField = document.getElementById('tipo_carga');
const validadeCredencialField = document.getElementById('validade_credencial');
const observacoesField = document.getElementById('observacoes');

// Preencher campos básicos
if (nomeField) nomeField.value = instrutor.nome || '';
if (emailField) emailField.value = instrutor.email || '';
if (telefoneField) telefoneField.value = instrutor.telefone || '';
if (credencialField) credencialField.value = instrutor.credencial || '';
if (cfcField) cfcField.value = instrutor.cfc_id || '';
if (ativoField) ativoField.value = instrutor.ativo ? '1' : '0';

// Preencher campos adicionais
if (cpfField) cpfField.value = instrutor.cpf || '';
if (cnhField) cnhField.value = instrutor.cnh || '';

// Converter data de YYYY-MM-DD para DD/MM/YYYY
if (dataNascimentoField && instrutor.data_nascimento) {
    const data = new Date(instrutor.data_nascimento);
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    dataNascimentoField.value = `${dia}/${mes}/${ano}`;
}

// Converter horários de HH:MM:SS para HH:MM
if (horarioInicioField && instrutor.horario_inicio) {
    horarioInicioField.value = instrutor.horario_inicio.substring(0, 5);
}
if (horarioFimField && instrutor.horario_fim) {
    horarioFimField.value = instrutor.horario_fim.substring(0, 5);
}

// Preencher campos de endereço
if (enderecoField) enderecoField.value = instrutor.endereco || '';
if (cidadeField) cidadeField.value = instrutor.cidade || '';
if (ufField) ufField.value = instrutor.uf || '';

// Preencher campos específicos do instrutor
if (tipoCargaField) tipoCargaField.value = instrutor.tipo_carga || '';
if (validadeCredencialField && instrutor.validade_credencial) {
    const data = new Date(instrutor.validade_credencial);
    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    validadeCredencialField.value = `${dia}/${mes}/${ano}`;
}
if (observacoesField) observacoesField.value = instrutor.observacoes || '';

// Preencher categorias de habilitação (checkboxes)
if (instrutor.categoria_habilitacao) {
    let categorias = [];
    try {
        // Tentar parsear como JSON primeiro
        categorias = JSON.parse(instrutor.categoria_habilitacao);
    } catch (e) {
        // Se não for JSON, tentar split por vírgula
        categorias = instrutor.categoria_habilitacao.split(',').map(cat => cat.trim());
    }
    
    categorias.forEach(categoria => {
        const checkbox = document.querySelector(`input[name="categoria_habilitacao[]"][value="${categoria}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
}

// Preencher dias da semana (checkboxes)
if (instrutor.dias_semana) {
    let dias = [];
    try {
        // Tentar parsear como JSON primeiro
        dias = JSON.parse(instrutor.dias_semana);
    } catch (e) {
        // Se não for JSON, tentar split por vírgula
        dias = instrutor.dias_semana.split(',').map(dia => dia.trim());
    }
    
    dias.forEach(dia => {
        const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${dia}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
}
```

## Por que Isso Resolve o Problema

### 1. **Campos Completos**
- Agora todos os campos da tabela `instrutores` são preenchidos
- Inclui campos de dados pessoais, endereço, horários e especialidades

### 2. **Conversão de Formatos**
- **Datas**: Converte de `YYYY-MM-DD` para `DD/MM/YYYY`
- **Horários**: Converte de `HH:MM:SS` para `HH:MM`
- **Arrays**: Trata tanto JSON quanto strings separadas por vírgula

### 3. **Tratamento Robusto**
- Verifica se cada campo existe antes de tentar preenchê-lo
- Trata valores nulos/vazios adequadamente
- Suporta diferentes formatos de dados

### 4. **Checkboxes Inteligentes**
- Marca automaticamente as categorias de habilitação
- Marca automaticamente os dias da semana
- Funciona com dados em JSON ou string

## Campos Agora Preenchidos

### Dados Pessoais:
- ✅ **CPF** - `instrutor.cpf`
- ✅ **CNH** - `instrutor.cnh`
- ✅ **Data de Nascimento** - `instrutor.data_nascimento` (convertida)

### Horários:
- ✅ **Horário Início** - `instrutor.horario_inicio` (convertido)
- ✅ **Horário Fim** - `instrutor.horario_fim` (convertido)

### Endereço:
- ✅ **Endereço** - `instrutor.endereco`
- ✅ **Cidade** - `instrutor.cidade`
- ✅ **UF** - `instrutor.uf`

### Especialidades:
- ✅ **Tipo de Carga** - `instrutor.tipo_carga`
- ✅ **Validade da Credencial** - `instrutor.validade_credencial` (convertida)
- ✅ **Observações** - `instrutor.observacoes`

### Seleções Múltiplas:
- ✅ **Categorias de Habilitação** - `instrutor.categoria_habilitacao` (checkboxes)
- ✅ **Dias da Semana** - `instrutor.dias_semana` (checkboxes)

## Resultado Esperado

Agora quando você editar um instrutor:

1. ✅ **Todos os campos** serão preenchidos corretamente
2. ✅ **Datas** aparecerão no formato brasileiro (DD/MM/YYYY)
3. ✅ **Horários** aparecerão no formato HH:MM
4. ✅ **Checkboxes** serão marcados automaticamente
5. ✅ **Campos de endereço** serão preenchidos
6. ✅ **Especialidades** serão carregadas

## Arquivos Modificados

- `admin/assets/js/instrutores.js` - Adicionado preenchimento de todos os campos
- `CORRECAO_CAMPOS_NAO_PREENCHIDOS.md` - Documentação da correção

## Teste Recomendado

1. **Acesse a página de instrutores**
2. **Clique em "Editar"** no instrutor ID 23
3. **Verifique se todos os campos estão preenchidos**:
   - ✅ CPF: "034.547.699-90"
   - ✅ Data de Nascimento: "08/10/1981"
   - ✅ Horário Início: "08:00"
   - ✅ Horário Fim: "18:00"
   - ✅ Categorias: A, B, C marcadas
   - ✅ Dias: Segunda, Terça, Quarta marcados
   - ✅ Tipo de Carga: "granel"
   - ✅ Validade Credencial: "08/10/2027"
   - ✅ Observações: "Observações testes"
