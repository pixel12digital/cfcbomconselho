# Correção do Parcelamento Mensal de Faturas

## Problema Identificado

Ao parcelar uma fatura mensal, quando a Data de Vencimento era definida para hoje (ex: 20/11/2025):
- ✅ **Entrada:** 20/11/2025 (correto)
- ❌ **1ª parcela:** 20/11/2025 (INCORRETO - deveria ser 20/12/2025)
- ✅ **2ª parcela:** 20/12/2025 (correto)
- ✅ **3ª parcela:** 20/01/2026 (correto)

**Causa:** A função `calcularVencimentosParcelas()` estava calculando a primeira parcela (i=0) na mesma data base, mesmo quando havia entrada.

## Comportamento Desejado

### Parcelamento Mensal COM Entrada:
- **Entrada:** Data escolhida no campo de vencimento (ex: 20/11/2025)
- **1ª parcela:** Data + 1 mês (20/12/2025)
- **2ª parcela:** Data + 2 meses (20/01/2026)
- **3ª parcela:** Data + 3 meses (20/02/2026)
- E assim sucessivamente

### Parcelamento Mensal SEM Entrada:
- **1ª parcela:** Data escolhida (20/11/2025)
- **2ª parcela:** Data + 1 mês (20/12/2025)
- **3ª parcela:** Data + 2 meses (20/01/2026)
- E assim sucessivamente

### Outras Modalidades:
- **Por dias, quinzenal, etc.:** Mantém comportamento original (não alterado)

## Alterações Realizadas

### 1. Função Utilitária `addMonthsKeepingDay()`

**Arquivo:** `admin/pages/financeiro-faturas.php` (linhas ~2023-2036)

**NOVO CÓDIGO ADICIONADO:**
```javascript
/**
 * Adiciona meses a uma data mantendo o dia do mês
 * Se o mês resultante não tiver o mesmo dia (ex.: 31 → fevereiro), ajusta para o último dia do mês
 * @param {Date} date - Data base
 * @param {number} months - Número de meses a adicionar
 * @returns {Date} Nova data com meses adicionados
 */
function addMonthsKeepingDay(date, months) {
    const d = new Date(date.getTime());
    const originalDay = d.getDate();
    d.setMonth(d.getMonth() + months);
    // Se o mês novo não tiver o mesmo dia (ex.: 31 → fevereiro), ajustar para o último dia do mês
    if (d.getDate() < originalDay) {
        d.setDate(0); // último dia do mês anterior
    }
    return d;
}
```

**Justificativa:** Função utilitária para adicionar meses mantendo o dia do mês, tratando casos especiais (ex: 31 de janeiro → 28/29 de fevereiro).

### 2. Ajuste na Função `calcularVencimentosParcelas()`

**Arquivo:** `admin/pages/financeiro-faturas.php` (linhas ~2038-2085)

**ANTES:**
```javascript
function calcularVencimentosParcelas(opcoes) {
    const {
        dataPrimeiraParcela,
        quantidadeParcelas,
        frequencia,
        intervaloDias
    } = opcoes;
    
    // ...
    
    for (let i = 0; i < quantidadeParcelas; i++) {
        let d = new Date(base);
        
        if (frequencia === 'monthly') {
            // Avança i meses mantendo o dia
            const targetMonth = d.getMonth() + i;
            // ... (primeira parcela i=0 ficava na mesma data base)
        }
        // ...
    }
}
```

**DEPOIS:**
```javascript
function calcularVencimentosParcelas(opcoes) {
    const {
        dataPrimeiraParcela,
        quantidadeParcelas,
        frequencia,
        intervaloDias,
        temEntrada  // NOVO: indica se há entrada
    } = opcoes;
    
    // Log para debug
    console.log('[DEBUG PARCELAS] Data base:', formatDateLocal(base), 'Frequência:', frequencia, 'Tem entrada:', temEntrada);
    
    for (let i = 0; i < quantidadeParcelas; i++) {
        let d;
        
        if (frequencia === 'monthly') {
            // REGRA: Para parcelamento mensal com entrada, a 1ª parcela deve ser data + 1 mês
            // Para parcelamento mensal sem entrada, a 1ª parcela é a data base
            if (temEntrada) {
                // Com entrada: 1ª parcela = data base + 1 mês, 2ª = data base + 2 meses, etc.
                d = addMonthsKeepingDay(base, i + 1);
            } else {
                // Sem entrada: 1ª parcela = data base, 2ª = data base + 1 mês, etc.
                d = addMonthsKeepingDay(base, i);
            }
        } else if (frequencia === 'days') {
            // Para frequência por dias, manter lógica original
            d = new Date(base);
            const dias = intervaloDias || 30;
            d.setDate(d.getDate() + i * dias);
        }
        
        // Log para debug
        console.log(`[DEBUG PARCELAS] Parcela ${i + 1}:`, formatDateLocal(d));
        
        vencimentos.push(d);
    }
}
```

**Mudanças:**
- ✅ Adicionado parâmetro `temEntrada` nas opções
- ✅ Lógica ajustada: com entrada, primeira parcela = `base + 1 mês` (não `base`)
- ✅ Uso da função `addMonthsKeepingDay()` para cálculo correto
- ✅ Logs de debug adicionados

### 3. Ajuste na Chamada de `calcularVencimentosParcelas()`

**Arquivo:** `admin/pages/financeiro-faturas.php` (linhas ~2227-2236)

**ANTES:**
```javascript
const vencimentos = calcularVencimentosParcelas({
    dataPrimeiraParcela: dataBase,
    quantidadeParcelas: numParcelasValido,
    frequencia: frequencia,
    intervaloDias: intervaloDias
});
```

**DEPOIS:**
```javascript
// REGRA DE NEGÓCIO:
// - Para parcelamento mensal COM entrada: Entrada = data_vencimento, 1ª parcela = data_vencimento + 1 mês
// - Para parcelamento mensal SEM entrada: 1ª parcela = data_vencimento, 2ª = data_vencimento + 1 mês
// - Para outras frequências (dias, quinzenal, etc.): manter comportamento original
const temEntrada = entradaValida > 0.009;

// Log para debug
console.log('[DEBUG PARCELAS] Iniciando cálculo:', {
    dataBase: formatDateLocal(dataBase),
    frequencia: frequencia,
    temEntrada: temEntrada,
    numParcelas: numParcelasValido
});

const vencimentos = calcularVencimentosParcelas({
    dataPrimeiraParcela: dataBase,
    quantidadeParcelas: numParcelasValido,
    frequencia: frequencia,
    intervaloDias: intervaloDias,
    temEntrada: temEntrada  // NOVO: passar informação se há entrada
});
```

**Mudanças:**
- ✅ Calculado `temEntrada` antes de chamar a função
- ✅ Passado `temEntrada` como parâmetro
- ✅ Logs de debug adicionados

## Garantia de Consistência

✅ **Tabela "Resumo das Parcelas":** Usa as datas calculadas por `calcularVencimentosParcelas()`

✅ **Payload da API:** As datas são coletadas diretamente dos inputs da tabela (linhas 2551-2579), garantindo que as mesmas datas calculadas sejam enviadas

✅ **Mesma lógica em ambos os lugares:** Não há divergência entre o que é exibido e o que é salvo

## Arquivos Modificados

1. **`admin/pages/financeiro-faturas.php`**
   - Adicionada função `addMonthsKeepingDay()` (linhas ~2023-2036)
   - Ajustada função `calcularVencimentosParcelas()` (linhas ~2038-2085)
   - Ajustada chamada em `calcularParcelas()` (linhas ~2227-2236)
   - Adicionados logs de debug

## Validação Esperada

### Teste 1 - Parcelamento Mensal COM Entrada

**Cenário:**
- Data de vencimento: 20/11/2025
- Entrada: R$ 500,00
- Mais 6 parcelas mensais

**Resultado Esperado:**
- ✅ Entrada: 20/11/2025
- ✅ 1ª parcela: 20/12/2025
- ✅ 2ª parcela: 20/01/2026
- ✅ 3ª parcela: 20/02/2026
- ✅ 4ª parcela: 20/03/2026
- ✅ 5ª parcela: 20/04/2026
- ✅ 6ª parcela: 20/05/2026

### Teste 2 - Parcelamento Mensal SEM Entrada

**Cenário:**
- Data de vencimento: 20/11/2025
- Entrada: R$ 0,00
- 6 parcelas mensais

**Resultado Esperado:**
- ✅ 1ª parcela: 20/11/2025
- ✅ 2ª parcela: 20/12/2025
- ✅ 3ª parcela: 20/01/2026
- ✅ 4ª parcela: 20/02/2026
- ✅ 5ª parcela: 20/03/2026
- ✅ 6ª parcela: 20/04/2026

### Teste 3 - Outras Frequências (Não Alteradas)

**Cenário:**
- Data de vencimento: 20/11/2025
- Frequência: Por dias (30 dias)
- 6 parcelas

**Resultado Esperado:**
- ✅ Comportamento original mantido (não alterado)

## Logs de Debug

Os logs aparecerão no console do navegador (DevTools) quando o parcelamento for calculado:

```
[DEBUG PARCELAS] Iniciando cálculo: {
    dataBase: "2025-11-20",
    frequencia: "monthly",
    temEntrada: true,
    numParcelas: 6
}
[DEBUG PARCELAS] Data base: 2025-11-20 Frequência: monthly Tem entrada: true
[DEBUG PARCELAS] Parcela 1: 2025-12-20
[DEBUG PARCELAS] Parcela 2: 2026-01-20
[DEBUG PARCELAS] Parcela 3: 2026-02-20
[DEBUG PARCELAS] Parcela 4: 2026-03-20
[DEBUG PARCELAS] Parcela 5: 2026-04-20
[DEBUG PARCELAS] Parcela 6: 2026-05-20
```

## Observações Técnicas

- **Função `addMonthsKeepingDay()`:** Trata corretamente casos especiais como 31 de janeiro → 28/29 de fevereiro
- **Parâmetro `temEntrada`:** Permite diferenciar entre parcelamento com e sem entrada
- **Logs temporários:** Podem ser removidos após validação completa
- **Impacto mínimo:** Apenas o parcelamento mensal foi ajustado, outras frequências mantêm comportamento original
- **Consistência garantida:** Mesma lógica usada na tabela e no payload da API

