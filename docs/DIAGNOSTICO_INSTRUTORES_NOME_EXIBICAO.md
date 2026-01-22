# Diagnóstico – Nome de Instrutores no Modal de Agendamento (admin)

## Causa raiz
- A API `admin/api/instrutores-real.php` montava o nome exibido com `COALESCE(u.nome, i.nome)`, priorizando o nome do usuário.
- Para o instrutor Carlos da Silva (instrutores.id=47), o usuário vinculado tinha nome “Instrutor Teste API”. O select do modal mostrava esse nome, parecendo que “Carlos da Silva” não existia, apesar de estar ativo no CFC correto.

## Solução aplicada
- A query agora traz `i.nome AS nome_instrutor` e `u.nome AS nome_usuario` sem `COALESCE` para exibição.
- O PHP combina os campos priorizando `instrutores.nome` e usando `usuarios.nome` apenas como fallback quando o nome do instrutor está vazio.
- Ordenação ajustada para `ORDER BY COALESCE(i.nome, u.nome)` para seguir o mesmo critério de exibição.

## Impacto esperado
- Consistência entre a listagem de instrutores e o select de agendamento: “Carlos da Silva” aparece com seu nome cadastrado no módulo de instrutores.
- Nenhuma mudança na estrutura do JSON retornado; apenas o valor do campo `nome` passa a seguir a prioridade correta.

## Teste rápido recomendado
1. Abrir `admin/index.php?page=turmas-teoricas&acao=detalhes&turma_id=19` e abrir o modal “Agendar Nova Aula”.
2. Verificar que o select de Instrutor lista “Carlos da Silva” (não “Instrutor Teste API”). 
3. Agendar uma aula usando Carlos e confirmar que o agendamento salva e exibe o nome correto na agenda.

## Segunda rodada de diagnóstico (frontend x API)
- A renderização inicial do select no PHP (`admin/pages/turmas-teoricas-detalhes-inline.php`) usava `COALESCE(u.nome, i.nome)`, mantendo o nome do usuário no HTML inicial. Ajustado para `COALESCE(i.nome, u.nome, 'Instrutor sem nome')`, alinhando com a prioridade do nome do instrutor.
- Criado `admin/api/debug-instrutores-real.php` para inspecionar rapidamente a resposta da API com os filtros de CFC/ativo, validando que o id=47 vem com o nome “Carlos da Silva”.
- Nome oficial de exibição em selects de agendamento: sempre priorizar `instrutores.nome`, usar `usuarios.nome` apenas como fallback se o nome do instrutor estiver vazio.
