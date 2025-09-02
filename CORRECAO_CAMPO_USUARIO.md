# Correção do Campo Usuário não Preenchido

## Problema Identificado

O campo **Usuário** no modal de edição de instrutores não estava sendo preenchido corretamente. Embora o modal abrisse e outros campos fossem preenchidos, o select de usuário permanecia vazio.

## Causa do Problema

### Problemas Identificados:

1. **Select não carregado**: O `instrutores.js` não estava chamando as funções para carregar as opções dos selects
2. **Campo não preenchido**: O campo `usuario_id` não estava sendo preenchido no formulário
3. **Timing incorreto**: Tentava preencher o campo antes das opções estarem carregadas

## Solução Implementada

### Antes da Correção:
```javascript
setTimeout(() => {
    try {
        // Apenas preenchia campos básicos
        const nomeField = document.getElementById('nome');
        const emailField = document.getElementById('email');
        // ... outros campos básicos
        
        if (nomeField) nomeField.value = instrutor.nome || '';
        if (emailField) emailField.value = instrutor.email || '';
        // ... sem preencher usuario_id
    } catch (error) {
        console.error('❌ Erro ao preencher formulário:', error);
    }
}, 100);
```

### Depois da Correção:
```javascript
setTimeout(async () => {
    try {
        // 1. PRIMEIRO: Carregar os selects
        if (typeof carregarUsuariosComRetry === 'function') {
            console.log('🔄 Carregando usuários...');
            await carregarUsuariosComRetry();
        }
        
        if (typeof carregarCFCsComRetry === 'function') {
            console.log('🔄 Carregando CFCs...');
            await carregarCFCsComRetry();
        }
        
        // 2. DEPOIS: Preencher campos básicos
        const nomeField = document.getElementById('nome');
        const emailField = document.getElementById('email');
        const telefoneField = document.getElementById('telefone');
        const credencialField = document.getElementById('credencial');
        const cfcField = document.getElementById('cfc_id');
        const ativoField = document.getElementById('ativo');
        const usuarioField = document.getElementById('usuario_id'); // NOVO
        
        if (nomeField) nomeField.value = instrutor.nome || '';
        if (emailField) emailField.value = instrutor.email || '';
        if (telefoneField) telefoneField.value = instrutor.telefone || '';
        if (credencialField) credencialField.value = instrutor.credencial || '';
        if (cfcField) cfcField.value = instrutor.cfc_id || '';
        if (ativoField) ativoField.value = instrutor.ativo ? '1' : '0';
        
        // 3. FINALMENTE: Preencher usuário com verificação
        if (usuarioField && instrutor.usuario_id) {
            const usuarioId = parseInt(instrutor.usuario_id);
            console.log(`🔍 Tentando preencher usuário ID: ${usuarioId}`);
            
            // Aguardar opções serem carregadas
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
        
        console.log('✅ Formulário preenchido com sucesso!');
    } catch (error) {
        console.error('❌ Erro ao preencher formulário:', error);
    }
}, 100);
```

## Por que Isso Resolve o Problema

### 1. **Carregamento Sequencial**
- Primeiro carrega as opções dos selects
- Depois preenche os campos básicos
- Finalmente preenche o campo usuário

### 2. **Verificação de Existência**
- Verifica se as funções de carregamento existem
- Verifica se o campo usuário existe
- Verifica se a opção do usuário existe no select

### 3. **Timing Correto**
- Aguarda 100ms para o modal abrir
- Aguarda carregamento das opções
- Aguarda mais 200ms para garantir que tudo está pronto

### 4. **Debug Detalhado**
- Logs para cada etapa do processo
- Logs das opções disponíveis se algo der errado
- Logs de sucesso quando tudo funciona

## Funções Utilizadas

### `carregarUsuariosComRetry()`
- Carrega a lista de usuários da API
- Popula o select `usuario_id`
- Retry automático se necessário

### `carregarCFCsComRetry()`
- Carrega a lista de CFCs da API
- Popula o select `cfc_id`
- Retry automático se necessário

## Resultado Esperado

Agora quando você editar um instrutor:

1. ✅ **Modal abre** sem erros
2. ✅ **Selects são carregados** (usuários e CFCs)
3. ✅ **Campos são preenchidos** corretamente
4. ✅ **Campo Usuário** mostra o usuário correto
5. ✅ **Campo CFC** mostra o CFC correto
6. ✅ **Console mostra** logs detalhados

## Logs Esperados

```
✏️ Editando instrutor ID: 23
📡 Fazendo requisição para: http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php?id=23
✅ Modal aberto com sucesso!
🔄 Carregando usuários...
📡 Carregando usuários de: /cfc-bom-conselho/admin/api/usuarios.php
✅ Usuário adicionado: Usuário teste 001
✅ 3 usuários carregados com sucesso!
🔄 Carregando CFCs...
📡 Carregando CFCs de: /cfc-bom-conselho/admin/api/cfcs.php
✅ CFC adicionado: CFC BOM CONSELHO
✅ 1 CFCs carregados com sucesso!
🔍 Tentando preencher usuário ID: 14
✅ Usuário preenchido: Usuário teste 001 (teste@teste.com.br)
✅ Formulário preenchido com sucesso!
```

## Arquivos Modificados

- `admin/assets/js/instrutores.js` - Adicionado carregamento de selects e preenchimento do campo usuário
- `CORRECAO_CAMPO_USUARIO.md` - Documentação da correção

## Teste Recomendado

1. **Acesse a página de instrutores**
2. **Clique em "Editar"** no instrutor ID 23
3. **Verifique se**:
   - ✅ Campo **Usuário** mostra "Usuário teste 001"
   - ✅ Campo **CFC** mostra "CFC BOM CONSELHO"
   - ✅ Todos os outros campos estão preenchidos
   - ✅ Console mostra os logs de carregamento
