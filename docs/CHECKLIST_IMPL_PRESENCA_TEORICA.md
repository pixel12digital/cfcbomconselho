# ✅ CHECKLIST DE IMPLEMENTAÇÃO: PRESENÇA TEÓRICA
## Sistema CFC Bom Conselho - Itens por Prioridade

**Data:** 24/11/2025  
**Objetivo:** Checklist organizado por prioridade para completar o fluxo de presença teórica

---

## 🔴 PRIORIDADE ALTA
### Itens obrigatórios para colocar em produção

### 1. **Área do Aluno - Visualização de Presenças**
- [ ] Criar página `aluno/presencas-teoricas.php` ou adicionar bloco no dashboard
- [ ] Exibir lista de turmas teóricas do aluno
- [ ] Exibir frequência percentual por turma
- [ ] Exibir tabela de aulas com status de presença (Presente/Ausente/Não registrado)
- [ ] Exibir justificativas (se houver)
- [ ] Adicionar filtro por período (último mês, último trimestre, etc.)
- [ ] Permitir que aluno acesse seu próprio histórico (validação de segurança)

**Arquivos a criar/modificar:**
- `aluno/presencas-teoricas.php` (novo)
- `aluno/dashboard.php` (adicionar bloco ou link)
- `admin/api/turma-frequencia.php` (ajustar permissões para aluno)

**Critério de aceite:**
- Aluno consegue ver suas presenças teóricas diretamente
- Aluno consegue ver frequência percentual
- Aluno consegue ver histórico de presenças/faltas

---

### 2. **Painel Instrutor - Acesso às Turmas Teóricas**
- [ ] Adicionar seção "Minhas Turmas Teóricas" no dashboard (`instrutor/dashboard.php`)
- [ ] Listar turmas teóricas do instrutor (status: ativa, completa, cursando)
- [ ] Exibir informações básicas (nome, período, número de alunos)
- [ ] Adicionar link direto para chamada de cada turma
- [ ] Adicionar link para próxima aula teórica do dia (se houver)
- [ ] Adicionar contador de presenças pendentes (se houver)

**Arquivos a modificar:**
- `instrutor/dashboard.php` (adicionar seção de turmas teóricas)

**Critério de aceite:**
- Instrutor vê suas turmas teóricas no dashboard
- Instrutor consegue acessar chamada diretamente do dashboard
- Instrutor vê próxima aula teórica do dia

---

### 3. **Painel Instrutor - Lista de Aulas Teóricas**
- [ ] Adicionar seção "Aulas Teóricas" em `instrutor/aulas.php`
- [ ] Listar aulas teóricas do instrutor (futuras e passadas)
- [ ] Exibir informações básicas (data, horário, disciplina, turma, sala)
- [ ] Adicionar link para chamada de cada aula
- [ ] Adicionar filtros (período, status, turma)

**Arquivos a modificar:**
- `instrutor/aulas.php` (adicionar seção de aulas teóricas)

**Critério de aceite:**
- Instrutor vê suas aulas teóricas na lista de aulas
- Instrutor consegue acessar chamada diretamente da lista

---

### 4. **Segurança - Acesso do Aluno ao Histórico**
- [ ] Criar endpoint ou ajustar `historico-aluno.php` para permitir acesso do aluno
- [ ] Validar que aluno só pode ver seu próprio histórico
- [ ] Adicionar validação de segurança (verificar `aluno_id` do usuário logado)
- [ ] Criar rota específica para aluno: `aluno/historico.php` ou similar

**Arquivos a criar/modificar:**
- `aluno/historico.php` (novo) ou ajustar `admin/pages/historico-aluno.php`
- `includes/auth.php` (adicionar função `getCurrentAlunoId()` se necessário)

**Critério de aceite:**
- Aluno consegue acessar seu próprio histórico
- Aluno não consegue acessar histórico de outros alunos
- Validação de segurança implementada

---

## 🟡 PRIORIDADE MÉDIA
### Itens importantes de UX/relatórios

### 5. **Interface de Chamada - Melhorias de UX**
- [ ] Adicionar botão "Marcar todos presentes" na chamada
- [ ] Adicionar botão "Marcar todos ausentes" na chamada
- [ ] Adicionar busca rápida de aluno por nome/CPF na chamada
- [ ] Adicionar filtro por status (todos, presentes, ausentes, sem registro)
- [ ] Adicionar contador visual de presenças (ex: "15/20 presentes")

**Arquivos a modificar:**
- `admin/pages/turma-chamada.php` (adicionar botões e filtros)

**Critério de aceite:**
- Instrutor/Admin consegue marcar todos de uma vez
- Busca e filtros funcionam corretamente
- Interface mais intuitiva

---

### 6. **Relatórios de Frequência - Admin/Secretaria**
- [ ] Criar página `admin/pages/relatorio-frequencia.php`
- [ ] Exibir relatório consolidado de frequência por turma
- [ ] Exibir lista de alunos com frequência abaixo do mínimo
- [ ] Adicionar filtros (turma, período, status)
- [ ] Exibir estatísticas gerais (frequência média, aprovados, reprovados)

**Arquivos a criar:**
- `admin/pages/relatorio-frequencia.php` (novo)
- Adicionar item no menu do admin

**Critério de aceite:**
- Admin/Secretaria consegue ver relatório consolidado
- Relatório mostra alunos em risco (frequência abaixo do mínimo)
- Filtros funcionam corretamente

---

### 7. **Histórico de Alterações de Presença**
- [ ] Criar tabela `turma_presencas_log` ou usar tabela `logs` existente
- [ ] Registrar todas as alterações de presença (quem alterou, quando, o que mudou)
- [ ] Exibir histórico de alterações na interface de chamada
- [ ] Adicionar tooltip ou modal com histórico de alterações

**Arquivos a criar/modificar:**
- `admin/migrations/XXX-create-turma-presencas-log.sql` (novo, se necessário)
- `admin/api/turma-presencas.php` (adicionar log de alterações)
- `admin/pages/turma-chamada.php` (exibir histórico)

**Critério de aceite:**
- Todas as alterações são registradas
- Admin/Secretaria consegue ver histórico de alterações
- Histórico mostra quem alterou, quando e o que mudou

---

### 8. **Filtros e Busca - Lista de Alunos da Turma**
- [ ] Adicionar filtro "Frequência abaixo do mínimo" na lista de alunos da turma
- [ ] Adicionar busca rápida por nome/CPF na lista de alunos
- [ ] Adicionar ordenação por frequência (maior/menor)
- [ ] Adicionar badge visual para alunos com frequência abaixo do mínimo

**Arquivos a modificar:**
- `admin/pages/turmas-teoricas-detalhes-inline.php` (adicionar filtros)

**Critério de aceite:**
- Secretaria consegue filtrar alunos em risco rapidamente
- Busca funciona corretamente
- Ordenação funciona corretamente

---

## 🟢 PRIORIDADE BAIXA
### Melhorias futuras, refinamentos

### 9. **Exportação de Relatórios**
- [ ] Adicionar exportação PDF de lista de presença
- [ ] Adicionar exportação Excel de lista de presença
- [ ] Adicionar exportação PDF de relatório de frequência
- [ ] Adicionar exportação Excel de relatório de frequência

**Arquivos a criar/modificar:**
- `admin/api/exportar-presencas.php` (novo)
- `admin/api/exportar-frequencia.php` (novo)
- Adicionar botões de exportação nas páginas

**Critério de aceite:**
- Exportação PDF funciona corretamente
- Exportação Excel funciona corretamente
- Arquivos exportados têm formatação adequada

---

### 10. **Notificações Automáticas**
- [ ] Notificar aluno quando frequência estiver abaixo do mínimo
- [ ] Notificar aluno quando atingir frequência mínima
- [ ] Notificar instrutor quando há aula teórica agendada para hoje
- [ ] Notificar instrutor quando há presenças pendentes

**Arquivos a criar/modificar:**
- `includes/services/SistemaNotificacoes.php` (adicionar tipos de notificação)
- `admin/includes/TurmaTeoricaManager.php` (adicionar lógica de notificações)

**Critério de aceite:**
- Notificações são enviadas corretamente
- Notificações aparecem no dashboard
- Usuários são notificados nos momentos corretos

---

### 11. **Dashboard de Frequência Geral - Admin**
- [ ] Criar página `admin/pages/dashboard-frequencia.php`
- [ ] Exibir frequência média geral (todas as turmas)
- [ ] Exibir gráfico de frequência por período
- [ ] Exibir lista de alunos com frequência abaixo do mínimo (todas as turmas)
- [ ] Exibir estatísticas gerais (total de alunos, aprovados, reprovados)

**Arquivos a criar:**
- `admin/pages/dashboard-frequencia.php` (novo)
- Adicionar item no menu do admin

**Critério de aceite:**
- Dashboard mostra visão consolidada
- Gráficos são exibidos corretamente
- Estatísticas são calculadas corretamente

---

### 12. **Melhorias de Performance**
- [ ] Otimizar queries de frequência (adicionar índices se necessário)
- [ ] Implementar cache de frequência percentual (se necessário)
- [ ] Otimizar queries de listagem de presenças
- [ ] Adicionar paginação na lista de presenças (se houver muitas)

**Arquivos a modificar:**
- `admin/api/turma-frequencia.php` (otimizar queries)
- `admin/api/turma-presencas.php` (otimizar queries)
- `admin/migrations/XXX-add-indexes-presencas.sql` (novo, se necessário)

**Critério de aceite:**
- Queries executam rapidamente
- Sistema suporta grande volume de dados
- Performance é adequada

---

### 13. **Validações Adicionais**
- [ ] Adicionar limite temporal para edição (ex: não permitir editar presenças de mais de 30 dias)
- [ ] Adicionar validação de horário (ex: não permitir marcar presença antes do horário da aula)
- [ ] Adicionar validação de data (ex: não permitir marcar presença de aula futura)
- [ ] Adicionar confirmação antes de excluir presença

**Arquivos a modificar:**
- `admin/api/turma-presencas.php` (adicionar validações)

**Critério de aceite:**
- Validações funcionam corretamente
- Mensagens de erro são claras
- Regras de negócio são aplicadas

---

## 📊 RESUMO DE PRIORIDADES

### **Prioridade Alta (4 itens):**
1. Área do Aluno - Visualização de Presenças
2. Painel Instrutor - Acesso às Turmas Teóricas
3. Painel Instrutor - Lista de Aulas Teóricas
4. Segurança - Acesso do Aluno ao Histórico

### **Prioridade Média (4 itens):**
5. Interface de Chamada - Melhorias de UX
6. Relatórios de Frequência - Admin/Secretaria
7. Histórico de Alterações de Presença
8. Filtros e Busca - Lista de Alunos da Turma

### **Prioridade Baixa (5 itens):**
9. Exportação de Relatórios
10. Notificações Automáticas
11. Dashboard de Frequência Geral - Admin
12. Melhorias de Performance
13. Validações Adicionais

---

## 🎯 ORDEM SUGERIDA DE IMPLEMENTAÇÃO

### **Fase 1 - Base (Prioridade Alta):**
1. Área do Aluno - Visualização de Presenças
2. Painel Instrutor - Acesso às Turmas Teóricas
3. Segurança - Acesso do Aluno ao Histórico

### **Fase 2 - Completar Instrutor (Prioridade Alta):**
4. Painel Instrutor - Lista de Aulas Teóricas

### **Fase 3 - Melhorias de UX (Prioridade Média):**
5. Interface de Chamada - Melhorias de UX
6. Filtros e Busca - Lista de Alunos da Turma

### **Fase 4 - Relatórios (Prioridade Média):**
7. Relatórios de Frequência - Admin/Secretaria
8. Histórico de Alterações de Presença

### **Fase 5 - Refinamentos (Prioridade Baixa):**
9. Exportação de Relatórios
10. Notificações Automáticas
11. Dashboard de Frequência Geral - Admin
12. Melhorias de Performance
13. Validações Adicionais

---

## ✅ CHECKLIST DE VALIDAÇÃO FINAL

Antes de considerar o sistema completo, validar:

- [ ] Aluno consegue ver suas presenças teóricas
- [ ] Instrutor consegue acessar suas turmas teóricas facilmente
- [ ] Instrutor consegue fazer chamada de suas turmas teóricas
- [ ] Admin/Secretaria consegue ver relatórios consolidados
- [ ] Todas as validações de segurança estão implementadas
- [ ] Performance é adequada para o volume de dados
- [ ] Interface é intuitiva para todos os perfis
- [ ] Documentação está atualizada

---

**Fim do Checklist**

