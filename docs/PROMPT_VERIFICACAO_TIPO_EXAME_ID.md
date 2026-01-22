# Prompt: Verificação da Necessidade do Parâmetro `tipo` na URL de Lançamento de Resultado

## Contexto

No histórico do aluno, quando o usuário clica em "Lançar Resultado" de um exame médico ou psicotécnico, a URL gerada é:

```
index.php?page=exames&tipo=medico&exame_id=40&origem=historico
```

ou

```
index.php?page=exames&tipo=psicotecnico&exame_id=40&origem=historico
```

## Objetivo da Investigação

Entender se o parâmetro `tipo` na URL é realmente necessário quando já temos o `exame_id`, ou se pode ser obtido do banco de dados através do `exame_id`.

## Pontos a Verificar

### 1. Por que o `tipo` está na URL?

**Verificar:**
- A página `admin/pages/exames.php` filtra a lista de exames por tipo?
- A query SQL usa `WHERE e.tipo = ?`?
- O que acontece se o `tipo` não for fornecido na URL?

**Arquivo:** `admin/pages/exames.php` (linha ~118-128)

### 2. A função `abrirModalResultado()` precisa do `tipo`?

**Verificar:**
- A função `abrirModalResultado(exameId)` busca dados do exame do banco?
- Ela usa o `tipo` para alguma coisa?
- O modal de resultado precisa do tipo para funcionar?

**Arquivo:** `admin/pages/exames.php` (linha ~2115-2126)

### 3. A API pode retornar o tipo do exame?

**Verificar:**
- A API `admin/api/exames_simple.php` retorna o tipo quando buscamos por ID?
- Existe endpoint GET que retorna dados do exame incluindo o tipo?

**Arquivo:** `admin/api/exames_simple.php` (linha ~55-70)

### 4. Comparação com outras funções

**Verificar:**
- A função `editarExame(exameId)` busca dados do exame via API?
- Ela obtém o tipo do banco ou da URL?
- Como ela funciona sem o tipo na URL?

**Arquivo:** `admin/pages/exames.php` (linha ~2747-2797)

## Questões Específicas

1. **Se removermos o `tipo` da URL quando há `exame_id`, a página ainda funciona?**
   - A lista de exames será exibida corretamente?
   - O modal de resultado abrirá corretamente?

2. **Se o `tipo` não for fornecido, qual é o comportamento padrão?**
   - A página usa `'medico'` como padrão?
   - Isso pode causar problemas se o exame for psicotécnico?

3. **O histórico do aluno já conhece o tipo ao criar o link?**
   - O código PHP no histórico já sabe se é médico ou psicotécnico?
   - Há algum problema em passar o tipo na URL?

## Resultado Esperado

Após a investigação, determinar:

1. ✅ **O `tipo` é necessário na URL?** (SIM/NÃO)
2. ✅ **Por que é necessário?** (Lista de razões técnicas)
3. ✅ **Pode ser obtido do banco via `exame_id`?** (SIM/NÃO)
4. ✅ **Vale a pena fazer essa mudança?** (SIM/NÃO - considerando complexidade vs benefício)

## Observações

- Não alterar código ainda, apenas investigar
- Documentar todas as descobertas
- Fornecer evidências (trechos de código, linhas, etc.)

