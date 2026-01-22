# TESTES – Regressão de Login e Permissões

## Objetivo

Este checklist valida os **fluxos de autenticação e controle de acesso** do sistema, garantindo que login, logout, permissões e multi-tenant funcionam corretamente em todos os perfis de usuário.

## Cenários Principais (rascunho)

- [ ] Cenário 1 – Login como Admin Master (cfc_id = 0)
- [ ] Cenário 2 – Login como Admin Secretaria (cfc_id > 0)
- [ ] Cenário 3 – Login como Instrutor
- [ ] Cenário 4 – Login como Aluno (se implementado)
- [ ] Cenário 5 – Tentativa de login com credenciais inválidas
- [ ] Cenário 6 – Bloqueio após múltiplas tentativas de login inválidas
- [ ] Cenário 7 – Logout e limpeza de sessão
- [ ] Cenário 8 – Controle de acesso por CFC (multi-tenant)
- [ ] Cenário 9 – Admin Global pode acessar dados de qualquer CFC
- [ ] Cenário 10 – Usuário de CFC específico só acessa dados do seu CFC
- [ ] Cenário 11 – Timeout de sessão após período de inatividade
- [ ] Cenário 12 – Redirecionamento correto após login (admin/instrutor/aluno)

## Modelo de Caso de Teste

Para cada cenário, quando formos detalhar:

- **Cenário:** `<nome>`
- **Pré-condições:** `<o que precisa existir - ex.: usuários cadastrados, etc.>`
- **Passos:**
  1. `<passo 1>`
  2. `<passo 2>`
  3. `<passo 3>`
- **Resultado esperado:** `<comportamento esperado após os passos>`
- **Status:** [ ] Pendente / [x] Aprovado / [ ] Falhou

---

**⚠️ NOTA:** Este arquivo está em modo rascunho. Casos de teste serão detalhados conforme necessário nas próximas fases, especialmente antes de cada deploy.

**Referência:** `docs/PLANO_IMPL_PRODUCAO_CFC.md` - Fluxos Críticos (Acesso administrativo)

