# CONTEXTO CFC BOM CONSELHO

## Objetivo
- Sistema web para gestao completa de Centros de Formacao de Condutores, cobrindo matricula, aulas teoricas e praticas, exames, financeiro e acompanhamento via dashboards.
- Publicos principais: administracao/secretaria (operacao completa), instrutor (agenda e presencas), aluno (acompanhamento de progresso e financeiro).

## Modulos e areas
- admin/: area administrativa. pages/ = telas; api/ = endpoints REST; includes/ = helpers, services e guards; assets/ = css/js; migrations/ = scripts SQL; tools/ e jobs/ = diagnosticos e tarefas de cron; uploads/ e logs/ por perfil.
- aluno/: portal/PWA do aluno (dashboard, aulas, financeiro, historico).
- instrutor/: portal/PWA do instrutor (agenda e registro de aulas/praticas, presencas teoricas planejadas).
- includes/ na raiz: configuracao global (config.php), wrapper de banco (database.php) e utilitarios compartilhados.
- assets/ raiz: estaticos globais (css, js, img); pwa/ e uploads/ para manifest e arquivos enviados; backups/ e logs/ para rotinas do sistema.
- docs/: referencias funcionais e auditorias (ONBOARDING_DEV_CFC.md, RAIO-X-PROJETO-CFC-COMPLETO.md, CHECKLIST-TESTES-FUNCIONAIS-CFC.md).

## Fluxos criticos (nao quebrar)
- Autenticacao e permissoes: login em index.php e admin/login.php; perfis admin master (cfc_id=0), admin secretaria (cfc_id>0), instrutor, aluno; guards em includes/ e admin/includes/ controlam acesso e recaptcha opcional.
- Cadastro de aluno e matricula: admin/pages/alunos.php com admin/api/alunos.php e admin/api/matriculas.php; campos de operacoes e status precisam manter integridade com matriculas e financeiro informativo.
- Turma teorica e presenca: admin/pages/turmas-teoricas* + TurmaTeoricaManager + admin/api/turma-presencas.php; elegibilidade exige exames medico/psico ok, sem faturas vencidas e mesmo CFC; salvar presenca recalcula frequencia.
- Aula pratica: agendamento via admin/index.php?page=agendamento; validar conflito de instrutor e veiculo, limite de 3 aulas por dia e intervalo minimo de 30min; status agendada -> concluida; integracao futura com PWA instrutor para km inicial/final.
- Exames e status: admin/api/exames.php + admin/includes/guards_exames.php; bloqueia turma teorica e aulas praticas quando exames nao ok.
- Financeiro: faturas e pagamentos (admin/pages/financeiro-* e admin/api/financeiro-*.php); job admin/jobs/marcar_faturas_vencidas.php marca vencidas; FinanceiroAlunoHelper bloqueia matricula em turma e agendamento pratico se houver fatura vencida/inadimplencia.
- Dashboards aluno/instrutor: consomem APIs de aulas, presencas e financeiro; nao quebrar contratos usados nas PWAs.
- Logs/backups/uploads: manter escrita em logs/, backups/ e uploads/ pois scripts e jobs dependem dessas pastas.

## Padroes de pastas
- Raiz: index.php/login.php/logout.php, includes/config.php e database.php, assets/ globais, uploads/, backups/, logs/.
- admin/: index.php roteia por query (?page, ?action); pages/ = UIs, api/ = endpoints, includes/ = regras e helpers, migrations/ = SQL numeradas, tools/ e jobs/ = manutencao/cron, uploads/ e logs/ especificos.
- aluno/ e instrutor/: telas mobile-first/PWA; compartilham includes/ global.
- docs/: usar como fonte antes de alterar fluxos (ONBOARDING_DEV_CFC.md, RAIO-X-PROJETO-CFC-COMPLETO.md, CHECKLIST-TESTES-FUNCIONAIS-CFC.md, FASE4_GUIA_RAPIDO_EXECUCAO.md).
- Config local: opcional criar config_local.php na raiz para sobrescrever credenciais em dev sem commitar.

## Como rodar e testar local
1) Requisitos: PHP 8+, MySQL 5.7+, Apache ou Nginx; extensoes PDO, PDO_MySQL, JSON, curl, openssl, session, mbstring.
2) Clonar/baixar em htdocs (ex: C:\xampp\htdocs\cfc-bom-conselho).
3) Criar BD `cfc_bom_conselho` utf8mb4 e definir credenciais em includes/config.php ou config_local.php.
4) Instalar: acessar http://localhost/cfc-bom-conselho/install.php ou rodar migrations em ordem: 001-create-turmas-teoricas-structure.sql, 004-create-matriculas-structure.sql, 005-create-financeiro-faturas-structure.sql, 006-create-pagamentos-structure.sql, 007-create-financeiro-pagamentos-structure.sql, 008-create-financeiro-configuracoes-structure.sql.
5) Seeds minimos: inserir CFC id 36 e usuario admin (gerar hash em admin/gerar-hash-senha.php ou scripts de criacao em admin/). Ajustar APP_URL/recaptcha se usado.
6) Permissoes de escrita: garantir logs/, backups/ e uploads/ com acesso de escrita (icacls no Windows ou chmod 755 no Linux/Mac).
7) Acesso: login raiz em index.php; admin em admin/index.php; instrutor em instrutor/dashboard.php; aluno em aluno/dashboard.php.
8) Rotinas de apoio: se usar financeiro real, agendar cron para admin/jobs/marcar_faturas_vencidas.php; manter backups regulares.
9) Testes manuais basicos apos mudancas: login/sessao; criar/editar aluno e matricula; matricular em turma teorica e registrar presenca; agendar aula pratica validando limites e conflitos; registrar exames e verificar bloqueios; criar fatura, registrar pagamento e rodar job de vencidas; revisar dashboards de aluno e instrutor. Roteiros detalhados em docs/CHECKLIST-TESTES-FUNCIONAIS-CFC.md e docs/FASE4_GUIA_RAPIDO_EXECUCAO.md.

## Referencias rapidas
- docs/ONBOARDING_DEV_CFC.md (guia principal de contexto e fluxos)
- docs/RAIO-X-PROJETO-CFC-COMPLETO.md e RAIO-X-* (detalhes de fluxos especificos)
- admin/pages/_PLANO-SISTEMA-CFC.md (planejamento funcional)
