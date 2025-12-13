# Diagnóstico – Aula teórica não aparece no painel do instrutor (PWA)

## Resumo do problema
- Aula teórica agendada no admin (Turma 19 – “Turma A – Formação CNH AB”) para 12/12/2025 18:30 com instrutor id=47 (Carlos da Silva) aparece no calendário do admin, mas no painel do instrutor (PWA) o “Aulas hoje” e “Próximas aulas” mostram 0.

## Fluxo de dados mapeado
- Onde a aula teórica é salva: tabela `turma_aulas_agendadas` (`id`, `turma_id`, `instrutor_id`, `data_aula`, `hora_inicio`, `hora_fim`, `status`, `disciplina`, `nome_aula`, `sala_id`, `observacoes`, etc.). A consulta abaixo confirma o registro de teste:  
  ```sql
  SELECT id, turma_id, instrutor_id, data_aula, hora_inicio, hora_fim, status, disciplina, nome_aula
  FROM turma_aulas_agendadas
  WHERE turma_id = 19 AND instrutor_id = 47 AND data_aula = '2025-12-12';
  -- Retorna id=227, status='agendada', hora_inicio='18:30:00'
  ```
- Telas/APIs do painel do instrutor:
  - `instrutor/dashboard.php` (desktop): busca aulas práticas (`aulas`) e teóricas (`turma_aulas_agendadas`) usando `instrutor_id` da tabela `instrutores` (obtido via `usuarios.id -> instrutores.usuario_id`). Filtros: data = hoje ou próximos 7 dias; status != 'cancelada'. Deve enxergar a aula teórica 227.
  - `instrutor/aulas.php`: lista completa com filtros; também junta `aulas` + `turma_aulas_agendadas` pelo `instrutor_id` da tabela `instrutores`.
  - **`instrutor/dashboard-mobile.php` (PWA/mobile)**: usa apenas a tabela `aulas` e filtra por `a.instrutor_id = $user['id']` (usa `usuarios.id` como se fosse `instrutores.id`). Não consulta `turma_aulas_agendadas`. Com o usuário 45 (Instrutor Teste API) e instrutor 47 (Carlos da Silva), nenhum registro aparece.
  - APIs auxiliares: `admin/api/instrutor-aulas.php` (usada para cancelar/transferir/iniciar/finalizar aulas práticas) trabalha somente com a tabela `aulas` e obtém o `instrutor_id` via `getCurrentInstrutorId`. Não entrega listagem para o dashboard.

## Causa raiz
- O painel PWA/mobile (`instrutor/dashboard-mobile.php`) consulta apenas aulas práticas (`aulas`) e ainda usa `usuarios.id` como se fosse `aulas.instrutor_id`. Isso exclui:
  - Todas as aulas teóricas (tabela `turma_aulas_agendadas` não é lida).
  - Todas as aulas práticas do instrutor 47, porque o filtro usa `instrutor_id = 45` (id do usuário), não `47` (id da tabela `instrutores`).
- O dashboard desktop (`instrutor/dashboard.php`) já inclui teóricas e usa o `instrutor_id` correto; o problema específico reportado no PWA decorre do dashboard-mobile.

## Impacto para a Tarefa 2.2 (início/fim de aula prática)
- A Tarefa 2.2 permanece íntegra para aulas práticas: a API `admin/api/instrutor-aulas.php` usa `getCurrentInstrutorId` e opera na tabela `aulas`. O erro mapeado aqui é na exibição do PWA/mobile e não altera a lógica de início/fim de aulas práticas.

## Sugestões de próximos passos (alto nível, sem implementar agora)
- Opção A: unificar o PWA/mobile para usar a mesma lógica do `instrutor/dashboard.php` (buscar `instrutor_id` via tabela `instrutores` e combinar `aulas` + `turma_aulas_agendadas` para hoje e próximos 7 dias).
- Opção B: manter o PWA só para práticas, mas então corrigir o filtro para usar `instrutores.id` (via `getCurrentInstrutorId`) e documentar explicitamente que teóricas só aparecem no admin (menos recomendado se a intenção é mostrar teóricas no PWA).
- Opção C: criar um endpoint dedicado que devolva aulas práticas e teóricas para o instrutor (usando `instrutor_id` correto) e consumir no PWA/mobile, centralizando a regra de negócio.

## Correção aplicada – 2025-12-12
- Dashboard mobile (`instrutor/dashboard-mobile.php`) agora usa `getCurrentInstrutorId` (instrutores.id) em vez de `usuarios.id`.
- Passou a considerar aulas práticas (`aulas`) e teóricas (`turma_aulas_agendadas`) para hoje e próximos 7 dias, unificando as listas.
- A Tarefa 2.2 (API de aulas práticas em `admin/api/instrutor-aulas.php`) permanece inalterada.

## Correção final implementada – 2025-12-12
- Confirmação de arquivo ativo: painel PWA usa `instrutor/dashboard-mobile.php` (marcado com comentário DEBUG).
- Filtro de instrutor: obtido via `getCurrentInstrutorId($user['id'])`, garantindo uso de `instrutores.id`.
- Listagem: combina aulas práticas (`aulas`) e teóricas (`turma_aulas_agendadas`) para hoje e próximos 7 dias; ordena e limita próximas a 10.
- Script de apoio: `instrutor/debug_aulas_carlos.php` mostra quantas práticas/teóricas o instrutor logado tem no dia e o ID resolvido.
- Dashboard desktop (`instrutor/dashboard.php`), listagem completa (`instrutor/aulas.php`) e API da Tarefa 2.2 (`admin/api/instrutor-aulas.php`) permaneceram intocados.

## Checklist de testes manuais
- Logar como instrutor Carlos da Silva no PWA/mobile.
- Verificar se a aula teórica de 12/12/2025 18:30 aparece em “Aulas de Hoje” e em “Próximas Aulas” (quando aplicável).
- Criar uma aula prática de teste para o mesmo dia em outro horário e confirmar que a contagem de “Aulas de Hoje” soma prática + teórica e ambas aparecem na lista.
- Remover/zerar todas as aulas do dia e confirmar que a mensagem de “Nenhuma aula hoje/agendada” volta a aparecer.

### Pontos adicionais encontrados
- `instrutor/debug_aulas_carlos.php` pode ser usado em homolog para validar rapidamente se o ID do instrutor está correto e se práticas/teóricas do dia estão sendo vistas pelo dashboard.
- Diagnóstico banco LOCAL – usuário 44:
  - `usuarios.id = 44` (nome/email do Carlos) não possui instrutor vinculado.
  - Existe instrutor “Carlos da Silva” em `instrutores.id = 47` vinculado a `usuario_id = 45` (CFC 36, ativo, credencial INST_TESTE_API).
  - Portanto, para o usuário 44 o `getCurrentInstrutorId` retorna null; necessário vincular corretamente ou usar usuário 45 para o painel.
- Correção de vínculo aplicada (DB compartilhado): `instrutores.id = 47` agora está com `usuario_id = 44` (arquivo SQL: `docs/CORRECAO_VINCULO_INSTRUTOR_CARLOS_LOCAL.sql`).
- 404 em “Chamada/Diário” do dashboard:
  - Causa: links apontavam para `/admin/index.php?...` sem respeitar o caminho relativo quando `BASE_PATH` não estava definido; em alguns ambientes resultava em 404.
  - Ajuste: `baseAdmin` agora remove o sufixo `/instrutor` do caminho atual (ou BASE_PATH) e monta `/admin/index.php` (ex.: `.../cfc-bom-conselho/admin/index.php`), evitando prefixo `/instrutor/admin/...`. JS de `fazer-chamada`/`fazer-diario` usa `href`/`data-url` (sem montar `chamada.php/diario.php`).
  - Rotas usadas: para aulas teóricas e práticas que têm `turma_id`/`aula_id`, links vão para `admin/index.php?page=turma-chamada|turma-diario&turma_id={id}&aula_id={id}&origem=instrutor`.
- Ajustes recentes:
  - `getCurrentInstrutorId` agora busca por `usuario_id` ativo e loga WARN quando não encontra.
  - `instrutor/dashboard.php` inicializa `$proximaAula`, busca instrutor_id via helper, e protege contra instrutor_id null (zera listas, evita warnings e loga WARN).
