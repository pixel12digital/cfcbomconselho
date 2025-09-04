# Correção do Problema de Data de Vencimento da Habilitação

## Problema Identificado

O sistema estava salvando a data de vencimento da habilitação dos instrutores com um dia anterior ao informado. Por exemplo, quando cadastrada a data 03/12/2031, o sistema salvava 02/12/2031.

## Causa Raiz

O problema estava na função `converterDataBrasileiraParaISO` no arquivo `admin/assets/js/instrutores-page.js`. A função estava usando:

```javascript
// CÓDIGO PROBLEMÁTICO
const data = new Date(ano, mes - 1, dia);
return data.toISOString().split('T')[0];
```

O problema ocorre porque:
1. `new Date(ano, mes - 1, dia)` cria uma data no fuso horário local
2. `toISOString()` converte para UTC, que pode resultar em um dia diferente dependendo do fuso horário

## Correções Implementadas

### 1. Função `converterDataBrasileiraParaISO` Corrigida

```javascript
function converterDataBrasileiraParaISO(dataBrasileira) {
    if (!dataBrasileira || dataBrasileira.trim() === '') {
        return null;
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataBrasileira)) {
        return dataBrasileira;
    }
    
    // Verificar formato dd/mm/aaaa
    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = dataBrasileira.match(regex);
    
    if (!match) return null;
    
    const dia = parseInt(match[1]);
    const mes = parseInt(match[2]);
    const ano = parseInt(match[3]);
    
    // Validar valores
    if (dia < 1 || dia > 31) return null;
    if (mes < 1 || mes > 12) return null;
    if (ano < 1900 || ano > 2100) return null;
    
    // Verificar se a data é válida
    const data = new Date(ano, mes - 1, dia);
    if (data.getDate() !== dia || data.getMonth() !== mes - 1 || data.getFullYear() !== ano) {
        return null;
    }
    
    // Retornar no formato ISO sem conversão de fuso horário
    return `${ano}-${mes.toString().padStart(2, '0')}-${dia.toString().padStart(2, '0')}`;
}
```

### 2. Função `configurarCampoDataHibrido` Corrigida

Corrigida a conversão de data do calendário para formato brasileiro:

```javascript
// ANTES (problemático)
const data = new Date(this.value);
const dia = String(data.getDate()).padStart(2, '0');
const mes = String(data.getMonth() + 1).padStart(2, '0');
const ano = data.getFullYear();

// DEPOIS (corrigido)
const partes = this.value.split('-');
const ano = partes[0];
const mes = partes[1];
const dia = partes[2];
```

### 3. Funções de Exibição Corrigidas

Corrigidas as funções que convertem datas ISO para formato brasileiro na exibição:

```javascript
// ANTES (problemático)
const data = new Date(instrutor.validade_credencial);
const dia = String(data.getDate()).padStart(2, '0');
const mes = String(data.getMonth() + 1).padStart(2, '0');
const ano = data.getFullYear();

// DEPOIS (corrigido)
const partes = instrutor.validade_credencial.split('-');
const ano = partes[0];
const mes = partes[1];
const dia = partes[2];
```

### 4. Funções Auxiliares Adicionadas

Adicionadas funções para comparação segura de datas:

```javascript
function compararDatas(data1, data2) {
    const data1ISO = typeof data1 === 'string' ? data1 : data1.toISOString().split('T')[0];
    const data2ISO = typeof data2 === 'string' ? data2 : data2.toISOString().split('T')[0];
    return data1ISO.localeCompare(data2ISO);
}

function getDataAtual() {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}
```

### 5. Validações Atualizadas

As validações de data foram atualizadas para usar as novas funções:

```javascript
// Validação de data de nascimento
if (data && compararDatas(data, getDataAtual()) > 0) {
    console.warn('Data de nascimento não pode ser no futuro');
    this.value = '';
    return;
}

// Validação de validade da credencial
if (data && compararDatas(data, getDataAtual()) < 0) {
    console.warn('Validade da credencial deve ser no futuro');
    this.value = '';
    return;
}
```

## Arquivos Modificados

1. `admin/assets/js/instrutores-page.js` - Arquivo principal com as correções
2. `admin/assets/js/instrutores.js` - Arquivo secundário com correções adicionais
3. `teste_conversao_data.html` - Arquivo de teste para verificar as correções
4. `teste_conversao_data_final.html` - Arquivo de teste final para verificar ambas as correções

## Correções Adicionais Implementadas

### 6. Arquivo `instrutores.js` Corrigido

Adicionada a função `converterDataBrasileiraParaISO` corrigida no arquivo `instrutores.js`:

```javascript
// Função para converter data brasileira (dd/mm/aaaa) para ISO (aaaa-mm-dd) - CORRIGIDA
function converterDataBrasileiraParaISO(dataBrasileira) {
    if (!dataBrasileira || dataBrasileira.trim() === '') {
        return null;
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataBrasileira)) {
        return dataBrasileira;
    }
    
    // Verificar formato dd/mm/aaaa
    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = dataBrasileira.match(regex);
    
    if (!match) return null;
    
    const dia = parseInt(match[1]);
    const mes = parseInt(match[2]);
    const ano = parseInt(match[3]);
    
    // Validar valores
    if (dia < 1 || dia > 31) return null;
    if (mes < 1 || mes > 12) return null;
    if (ano < 1900 || ano > 2100) return null;
    
    // Verificar se a data é válida
    const data = new Date(ano, mes - 1, dia);
    if (data.getDate() !== dia || data.getMonth() !== mes - 1 || data.getFullYear() !== ano) {
        return null;
    }
    
    // Retornar no formato ISO sem conversão de fuso horário
    return `${ano}-${mes.toString().padStart(2, '0')}-${dia.toString().padStart(2, '0')}`;
}
```

### 7. Função `converterDataParaMySQL` Atualizada

A função `converterDataParaMySQL` foi atualizada para usar a nova função corrigida:

```javascript
function converterDataParaMySQL(dataString) {
    if (!dataString || dataString.trim() === '') {
        return null;
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataString)) {
        return dataString;
    }
    
    // Usar a função corrigida para conversão
    return converterDataBrasileiraParaISO(dataString);
}
```

### 8. Função `converterDataParaExibicao` Corrigida

A função `converterDataParaExibicao` foi corrigida para evitar problemas de fuso horário:

```javascript
function converterDataParaExibicao(dataString) {
    if (!dataString || dataString === '0000-00-00' || dataString.trim() === '') {
        return '';
    }
    
    try {
        // Verificar se está no formato YYYY-MM-DD
        const match = dataString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (match) {
            const [, ano, mes, dia] = match;
            const dataFormatada = `${dia}/${mes}/${ano}`;
            return dataFormatada;
        }
        
        // Fallback para outras conversões usando Date
        const data = new Date(dataString);
        if (!isNaN(data.getTime())) {
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            const dataFormatada = `${dia}/${mes}/${ano}`;
            return dataFormatada;
        } else {
            return '';
        }
    } catch (e) {
        return '';
    }
}
```

## Teste das Correções

Para testar se as correções estão funcionando:

1. Abra o arquivo `teste_conversao_data_final.html` no navegador
2. Verifique se todos os testes passam para ambos os arquivos JavaScript
3. Confirme que não há diferenças entre as funções antiga e nova para datas válidas
4. Confirme que há consistência entre os arquivos `instrutores.js` e `instrutores-page.js`

## Resultado Esperado

Após as correções, quando um instrutor for cadastrado com a data de vencimento da habilitação 03/12/2031, o sistema deve salvar exatamente essa data no banco de dados, sem a diferença de um dia.

## Observações Importantes

- As correções mantêm a compatibilidade com o formato de data brasileiro (dd/mm/aaaa)
- As validações de data continuam funcionando normalmente
- O calendário discreto continua funcionando corretamente
- Não há impacto em outras funcionalidades do sistema
