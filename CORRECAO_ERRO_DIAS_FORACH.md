# Correção do Erro "dias.forEach is not a function" e Campo Usuário

## Problema Identificado

1. **Erro JavaScript**: `TypeError: dias.forEach is not a function` na linha 356
2. **Campo Usuário**: Não estava sendo preenchido corretamente no modal de edição

## Causa do Problema

### 1. **Erro dias.forEach**
- A variável `dias` não era um array quando tentávamos usar `forEach`
- O campo `dias_semana` pode estar vazio (`""`) ou em formato inválido
- Falta de validação adequada antes de usar métodos de array

### 2. **Campo Usuário não Preenchido**
- Timing inadequado para preencher o campo após carregar as opções
- Falta de logs detalhados para debug
- Não tentava encontrar o usuário por nome como fallback

## Solução Implementada

### 1. **Tratamento Robusto de Arrays**

#### Antes:
```javascript
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

#### Depois:
```javascript
// Preencher dias da semana (checkboxes)
if (instrutor.dias_semana) {
    let dias = [];
    try {
        // Tentar parsear como JSON primeiro
        if (typeof instrutor.dias_semana === 'string') {
            if (instrutor.dias_semana.trim() === '') {
                dias = [];
            } else {
                try {
                    dias = JSON.parse(instrutor.dias_semana);
                } catch (e) {
                    // Se não for JSON, tentar split por vírgula
                    dias = instrutor.dias_semana.split(',').map(dia => dia.trim()).filter(dia => dia !== '');
                }
            }
        } else if (Array.isArray(instrutor.dias_semana)) {
            dias = instrutor.dias_semana;
        } else {
            dias = [];
        }
    } catch (e) {
        console.warn('⚠️ Erro ao processar dias_semana:', e);
        dias = [];
    }
    
    console.log('🔍 Dias da semana processados:', dias);
    
    if (Array.isArray(dias) && dias.length > 0) {
        dias.forEach(dia => {
            const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${dia}"]`);
            if (checkbox) {
                checkbox.checked = true;
                console.log(`✅ Dia marcado: ${dia}`);
            } else {
                console.warn(`⚠️ Checkbox para dia "${dia}" não encontrado`);
            }
        });
    }
}
```

### 2. **Melhor Tratamento do Campo Usuário**

#### Antes:
```javascript
// Preencher usuário com verificação adicional
if (usuarioField && instrutor.usuario_id) {
    const usuarioId = parseInt(instrutor.usuario_id);
    console.log(`🔍 Tentando preencher usuário ID: ${usuarioId}`);
    
    // Aguardar um pouco mais para garantir que as opções foram carregadas
    setTimeout(() => {
        const usuarioOption = usuarioField.querySelector(`option[value="${usuarioId}"]`);
        if (usuarioOption) {
            usuarioField.value = usuarioId;
            console.log(`✅ Usuário preenchido: ${usuarioOption.textContent}`);
        } else {
            console.warn(`⚠️ Opção de usuário ${usuarioId} não encontrada`);
            console.log('🔍 Opções disponíveis:', Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
        }
    }, 200);
}
```

#### Depois:
```javascript
// Preencher usuário com verificação adicional
if (usuarioField && instrutor.usuario_id) {
    const usuarioId = parseInt(instrutor.usuario_id);
    console.log(`🔍 Tentando preencher usuário ID: ${usuarioId}`);
    console.log(`🔍 Campo usuário encontrado:`, usuarioField);
    console.log(`🔍 Opções disponíveis:`, Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
    
    // Aguardar um pouco mais para garantir que as opções foram carregadas
    setTimeout(() => {
        const usuarioOption = usuarioField.querySelector(`option[value="${usuarioId}"]`);
        if (usuarioOption) {
            usuarioField.value = usuarioId;
            console.log(`✅ Usuário preenchido: ${usuarioOption.textContent}`);
        } else {
            console.warn(`⚠️ Opção de usuário ${usuarioId} não encontrada`);
            console.log('🔍 Opções disponíveis:', Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
            
            // Tentar encontrar por texto também
            const options = Array.from(usuarioField.options);
            const matchingOption = options.find(opt => opt.textContent.includes(instrutor.nome));
            if (matchingOption) {
                usuarioField.value = matchingOption.value;
                console.log(`✅ Usuário preenchido por nome: ${matchingOption.textContent}`);
            }
        }
    }, 300); // Aumentei o tempo para 300ms
} else {
    console.warn('⚠️ Campo usuário não encontrado ou usuario_id não definido');
    console.log('🔍 usuarioField:', usuarioField);
    console.log('🔍 instrutor.usuario_id:', instrutor.usuario_id);
}
```

## Por que Isso Resolve o Problema

### 1. **Validação de Tipos**
- Verifica se o valor é string, array ou outro tipo
- Trata strings vazias adequadamente
- Filtra valores vazios do array

### 2. **Tratamento de Erros**
- Try-catch para capturar erros de parsing
- Logs detalhados para debug
- Fallback para arrays vazios

### 3. **Verificação de Array**
- Verifica se `dias` é realmente um array antes de usar `forEach`
- Verifica se o array tem elementos antes de processar
- Logs para cada checkbox marcado

### 4. **Campo Usuário Melhorado**
- Logs detalhados do campo e opções disponíveis
- Fallback para encontrar usuário por nome
- Tempo aumentado para garantir carregamento
- Melhor tratamento de erros

## Logs Esperados

### Sucesso:
```
🔍 Dias da semana processados: ["Segunda", "Terça", "Quarta"]
✅ Dia marcado: Segunda
✅ Dia marcado: Terça
✅ Dia marcado: Quarta
🔍 Categorias processadas: ["A", "B", "C"]
✅ Categoria marcada: A
✅ Categoria marcada: B
✅ Categoria marcada: C
🔍 Tentando preencher usuário ID: 14
🔍 Campo usuário encontrado: <select id="usuario_id">
🔍 Opções disponíveis: [{value: "", text: "Criar novo usuário"}, {value: "14", text: "Usuário teste 001"}]
✅ Usuário preenchido: Usuário teste 001
```

### Erro (com logs informativos):
```
⚠️ Erro ao processar dias_semana: SyntaxError: Unexpected token
🔍 Dias da semana processados: []
⚠️ Campo usuário não encontrado ou usuario_id não definido
🔍 usuarioField: null
🔍 instrutor.usuario_id: undefined
```

## Arquivos Modificados

- `admin/assets/js/instrutores.js` - Melhorado tratamento de arrays e campo usuário
- `CORRECAO_ERRO_DIAS_FORACH.md` - Documentação da correção

## Teste Recomendado

1. **Acesse a página de instrutores**
2. **Clique em "Editar"** no instrutor ID 23
3. **Verifique no console** se não há mais erros de `forEach`
4. **Verifique se**:
   - ✅ Campo **Usuário** mostra "Usuário teste 001"
   - ✅ **Categorias** A, B, C estão marcadas
   - ✅ **Dias** Segunda, Terça, Quarta estão marcados
   - ✅ **Console** mostra logs detalhados sem erros
