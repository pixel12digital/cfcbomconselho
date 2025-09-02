# Teste de Debug do Modal de Instrutores

## Objetivo

Este arquivo HTML (`teste_modal_instrutor_debug.html`) foi criado para **testar isoladamente** a função `preencherFormularioInstrutor` e identificar exatamente onde está o problema com os valores dos selects que desaparecem.

## Como Usar

1. **Abra o arquivo** `teste_modal_instrutor_debug.html` no navegador
2. **Clique em "Testar Modal"** para iniciar o teste
3. **Observe os logs** na seção "Logs de Debug" 
4. **Verifique visualmente** se os campos são preenchidos corretamente
5. **Clique em "Verificar Valores"** para ver o estado atual dos campos

## O que o Teste Faz

### 1. **Simula Dados Reais**
```javascript
const instrutor = {
    id: 23,
    nome: 'Usuário teste 001',
    cpf: '034.547.699-90',
    usuario_id: 14,
    cfc_id: 36,
    categoria_habilitacao: 'A,B,C',
    dias_semana: 'Segunda,Terça,Quarta'
};
```

### 2. **Testa Preenchimento Completo**
- ✅ Campos de texto (nome, cpf, email, etc.)
- ✅ Campos de data e horário
- ✅ **Selects de Usuário e CFC** (foco principal)
- ✅ Checkboxes de categorias e dias da semana

### 3. **Debug Detalhado**
- Logs de cada etapa do processo
- Verificação de opções disponíveis nos selects
- Monitoramento de valores após preenchimento
- Verificação após delays para detectar desaparecimento

## Logs Esperados (Sucesso)

```
[10:30:15] 🚀 Iniciando teste do modal...
[10:30:15] 📋 Dados do instrutor preparados:
[10:30:15] 🔄 Preenchendo formulário com dados:
[10:30:15] 🔍 CFC Select options: 3
[10:30:15] 🔍 Usuário Select options: 4
[10:30:15] ✅ Selects carregados, preenchendo formulário...
[10:30:15] ✅ Campo nome preenchido: Usuário teste 001
[10:30:15] 🔍 Debug - Tentando preencher usuário ID: 14
[10:30:15] 🔍 Debug - Opção encontrada: Usuário teste 001
[10:30:15] ✅ Campo usuario_id preenchido: 14
[10:30:15] 🔍 Debug - Valor após preenchimento: 14
[10:30:15] ✅ Campo cfc_id preenchido: 36
[10:30:16] 🔍 Debug - Verificação após 100ms - Valor atual: 14
[10:30:16] ✅ Formulário preenchido com sucesso!
[10:30:17] 🔍 Verificando valores atuais dos campos...
[10:30:17] ✅ usuario_id: "14"
[10:30:17] ✅ cfc_id: "36"
```

## Logs de Problema (Falha)

```
[10:30:15] ✅ Campo usuario_id preenchido: 14
[10:30:15] 🔍 Debug - Valor após preenchimento: 14
[10:30:16] 🔍 Debug - Verificação após 100ms - Valor atual: ""  ← PROBLEMA
[10:30:16] ⚠️ Valor do usuário não foi aplicado, tentando novamente...
```

## Possíveis Problemas Identificados

### 1. **Evento onchange Interferindo**
- O evento `onchange="toggleUsuarioFields()"` pode estar sendo disparado
- Solução: Remoção temporária do evento durante preenchimento

### 2. **Timing de Restauração**
- Evento restaurado muito rapidamente (200ms)
- Solução: Aumentar delay ou verificar se ainda é necessário

### 3. **Reflow Visual**
- Valores definidos mas não exibidos visualmente
- Solução: Forçar reflow com `display: none/block`

### 4. **Interferência de Outros Scripts**
- Outros eventos ou scripts podem estar interferindo
- Solução: Isolar completamente o teste

## Como Interpretar os Resultados

### ✅ **Teste Passou**
- Valores permanecem após 1 segundo
- Logs mostram valores corretos
- **Conclusão**: Problema está no sistema real, não na função

### ❌ **Teste Falhou**
- Valores desaparecem mesmo no teste isolado
- Logs mostram valores sendo perdidos
- **Conclusão**: Problema está na função `preencherFormularioInstrutor`

## Próximos Passos

1. **Execute o teste** e observe os resultados
2. **Compare com o sistema real** - se o teste passar mas o sistema falhar, o problema está em outro lugar
3. **Se o teste falhar**, as correções precisam ser aplicadas na função original
4. **Compartilhe os logs** para análise detalhada

## Arquivos Relacionados

- `teste_modal_instrutor_debug.html` - Teste isolado
- `admin/assets/js/instrutores-page.js` - Função original
- `CORRECAO_ADICIONAL_VINCULACAO.md` - Documentação das correções
