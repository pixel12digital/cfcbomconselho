# Análise: Relação entre `tipo` e `exame_id` no Lançamento de Resultado

## Contexto

Quando o usuário clica em "Lançar Resultado" no histórico do aluno, a URL gerada é:

```
index.php?page=exames&tipo=medico&exame_id=40&origem=historico
```

ou

```
index.php?page=exames&tipo=psicotecnico&exame_id=40&origem=historico
```

## Por que o `tipo` está na URL?

### 1. Filtro da Lista de Exames

**Arquivo:** `admin/pages/exames.php` (linha ~118-128)

```php
$exames = $db->fetchAll("
    SELECT e.*, a.nome as aluno_nome, a.cpf as aluno_cpf,
           c.nome as cfc_nome
    FROM exames e
    JOIN alunos a ON e.aluno_id = a.id
    JOIN cfcs c ON a.cfc_id = c.id
    WHERE e.tipo = ?  -- ⚠️ FILTRA POR TIPO
      AND e.data_agendada BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                              AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY e.data_agendada DESC, e.hora_agendada DESC, e.id DESC
", [$tipo]);
```

**Conclusão:** A página de exames **sempre** filtra a lista por tipo. Se não houver `tipo` na URL, a página não sabe qual lista mostrar.

### 2. Normalização do Tipo

**Arquivo:** `admin/pages/exames.php` (linha ~23-29)

```php
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$tiposValidos = ['medico', 'psicotecnico', 'teorico', 'pratico'];
// Normalizar: se vazio ou inválido, usar padrão 'medico'
if (!in_array($tipo, $tiposValidos)) {
    $tipo = 'medico';
}
```

**Conclusão:** Se o `tipo` não for fornecido ou for inválido, a página usa `'medico'` como padrão.

### 3. Abertura Automática do Modal de Resultado

**Arquivo:** `admin/pages/exames.php` (linha ~2145-2150)

```javascript
if (urlParamsOrigem === 'historico') {
    if (urlParamsAlunoId) {
        // Abrir modal de agendamento
        // ...
    } else if (urlParamsExameId) {
        // Abrir modal de resultado
        setTimeout(function() {
            abrirModalResultado(urlParamsExameId);
        }, 300);
    }
}
```

**Função `abrirModalResultado(exameId)`:**
```javascript
function abrirModalResultado(exameId) {
    const modal = new bootstrap.Modal(document.getElementById('modalResultadoExame'));
    modal.show();
    
    // Definir ID do exame
    document.getElementById('exame_id_resultado').value = exameId;
    
    // Limpar formulário
    document.getElementById('formResultadoExame').reset();
    document.getElementById('exame_id_resultado').value = exameId;
    document.querySelector('input[name="data_resultado"]').value = new Date().toISOString().split('T')[0];
}
```

**Observação:** A função `abrirModalResultado()` **não carrega dados do exame** do banco. Ela apenas:
- Abre o modal
- Define o `exame_id` no campo hidden
- Limpa o formulário
- Define a data de hoje como padrão

### 4. Comparação com `editarExame()`

**Arquivo:** `admin/pages/exames.php` (linha ~2747-2797)

```javascript
function editarExame(exameId) {
    // Buscar dados do exame
    fetch(`api/exames_simple.php?id=${exameId}&t=${Date.now()}`, {
        cache: 'no-cache'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.exame) {
            const exame = data.exame;
            
            // Preencher formulário com dados do exame
            document.getElementById('aluno_id').value = exame.aluno_id;
            document.getElementById('tipo_exame').value = exame.tipo;  // ⚠️ TIPO VEM DO BANCO
            document.getElementById('data_agendada').value = exame.data_agendada;
            // ... outros campos
        }
    });
}
```

**Diferença:** `editarExame()` **busca dados do exame** via API e preenche o formulário, incluindo o `tipo`. Já `abrirModalResultado()` **não busca dados**, apenas abre o modal vazio.

## Resposta à Pergunta: O `tipo` é Necessário?

### ✅ SIM, o `tipo` é necessário na URL porque:

1. **A página filtra a lista por tipo:** Sem `tipo`, a query SQL não funciona corretamente
2. **A página precisa saber qual "aba" mostrar:** Médico, Psicotécnico, Teórico ou Prático
3. **O histórico já conhece o tipo:** Quando cria o link, já sabe se é médico ou psicotécnico

### ❌ NÃO, o `tipo` não é necessário para o modal de resultado porque:

1. **O `exame_id` já identifica o exame:** A API pode buscar o exame pelo ID e retornar o tipo
2. **O modal não usa o tipo:** A função `abrirModalResultado()` não precisa do tipo para funcionar
3. **A API já retorna o tipo:** Se necessário, pode-se buscar o exame via API e obter o tipo

## Possível Melhoria Futura

**Cenário hipotético:** Se quisermos remover o `tipo` da URL quando há `exame_id`:

1. **Buscar o exame via API** quando `exame_id` estiver presente
2. **Usar o tipo retornado** para filtrar a lista
3. **Atualizar a URL** para incluir o tipo correto

**Mas isso adiciona complexidade desnecessária**, pois:
- O histórico já conhece o tipo
- Não há problema em passar o tipo na URL
- A solução atual é simples e funcional

## Conclusão

**O `tipo` na URL é necessário e correto** porque:
- A página de exames sempre filtra por tipo
- O histórico já conhece o tipo ao criar o link
- Não há problema em passar ambos (`tipo` e `exame_id`) na URL
- A solução atual é simples e funcional

**Não há necessidade de alteração** neste momento.

