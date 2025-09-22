# 📋 INVENTÁRIO COMPLETO DO SISTEMA CFC

## PARTE A — Inventário do Módulo Alunos

### A.1 Telas & Navegação atual

**Rotas reais:**
- **Lista:** `/admin/pages/alunos.php` (GET)
- **Criar:** Modal integrado na mesma página (`abrirModalAluno('criar')`)
- **Editar:** Modal integrado (`abrirModalAluno('editar', id)`)
- **Visualizar:** Modal integrado (`abrirModalAluno('visualizar', id)`)
- **Histórico:** `/admin/pages/historico-aluno.php?id={id}` (GET)

**Menu:** "Cadastros" → "Alunos" (apenas admin)

**Ações rápidas existentes:**
- Editar (ícone lápis)
- Histórico (ícone histórico)
- Visualizar (ícone olho)
- Excluir (ícone lixeira)

### A.2 Estrutura de dados do Formulário de Aluno

| Campo | Rótulo | Tipo | Obrigatório | Validações | Máscara | Tabela.coluna |
|-------|--------|------|-------------|------------|---------|---------------|
| nome | Nome Completo | text | Sim | min 2 chars | - | alunos.nome |
| cpf | CPF | text | Sim | CPF válido | 000.000.000-00 | alunos.cpf |
| rg | RG | text | Não | - | 00.000.000-0 | alunos.rg |
| data_nascimento | Data de Nascimento | date | Não | data válida | DD/MM/AAAA | alunos.data_nascimento |
| telefone | Telefone | text | Não | - | (00) 00000-0000 | alunos.telefone |
| telefone2 | Telefone 2 | text | Não | - | (00) 00000-0000 | alunos.telefone2 |
| email | E-mail | email | Não | email válido | - | alunos.email |
| endereco | Endereço | text | Não | - | - | alunos.endereco |
| numero | Número | text | Não | - | - | alunos.numero |
| bairro | Bairro | text | Não | - | - | alunos.bairro |
| cidade | Cidade | text | Não | - | - | alunos.cidade |
| estado | Estado | select | Não | - | - | alunos.estado |
| cep | CEP | text | Não | - | 00000-000 | alunos.cep |
| naturalidade | Naturalidade | text | Não | - | - | alunos.naturalidade |
| nacionalidade | Nacionalidade | text | Não | - | - | alunos.nacionalidade |
| estado_civil | Estado Civil | select | Não | - | - | alunos.estado_civil |
| profissao | Profissão | text | Não | - | - | alunos.profissao |
| escolaridade | Escolaridade | select | Não | - | - | alunos.escolaridade |
| categoria_cnh | Categoria CNH | select | Sim | - | - | alunos.categoria_cnh |
| tipo_servico | Tipo de Serviço | select | Não | - | - | alunos.tipo_servico |
| cfc_id | CFC | select | Sim | - | - | alunos.cfc_id |
| status | Status | select | Sim | - | - | alunos.status |
| observacoes | Observações | textarea | Não | - | - | alunos.observacoes |

**Campos LGPD/consentimentos:** Não implementados

**Campos de contato de emergência:**
- contato_emergencia (text)
- telefone_emergencia (text)

### A.3 "Tipo de serviço" / Matrículas dentro de Alunos

**Onde é adicionado:** Campo direto no formulário de aluno (select)

**Tabelas/colunas usadas:**
- `alunos.tipo_servico` (ENUM: 'primeira_habilitacao', 'adicao', 'mudanca', 'renovacao')

**Status possíveis:**
- ativo
- inativo  
- concluido

**Regras de negócio:** Não há validação de múltiplas matrículas ativas da mesma categoria

### A.4 Histórico do Aluno

**Blocos existentes:**
1. **Dados pessoais** - Informações básicas do aluno
2. **Presenças teóricas** - Aulas teóricas e frequência
3. **Aulas práticas** - Aulas práticas agendadas
4. **Cobranças/boletos** - Status de pagamento (não implementado)
5. **Ocorrências** - Observações e incidentes
6. **Documentos** - Arquivos anexados (não implementado)
7. **Timeline com auditoria** - Log de alterações

**Origem:** API `/admin/api/historico.php?tipo=aluno&id={id}`

### A.5 APIs do módulo Alunos

**GET /admin/api/alunos.php**
- **Query:** `?id={id}` (opcional)
- **Response:** 
```json
{
  "success": true,
  "aluno": {
    "id": 1,
    "nome": "João Silva",
    "cpf": "123.456.789-00",
    "cfc_nome": "CFC Exemplo"
  }
}
```
- **Erros:** 404 (aluno não encontrado), 500 (erro interno)

**POST /admin/api/alunos.php**
- **Payload:** Dados do formulário
- **Response:**
```json
{
  "success": true,
  "message": "Aluno criado com sucesso",
  "aluno_id": 123
}
```

**PUT /admin/api/alunos.php**
- **Query:** `?id={id}`
- **Payload:** Dados atualizados
- **Response:** Similar ao POST

**DELETE /admin/api/alunos.php**
- **Query:** `?id={id}`
- **Response:**
```json
{
  "success": true,
  "message": "Aluno excluído com sucesso"
}
```

### A.6 Permissões

| Perfil | Ver | Criar | Editar | Excluir | Exportar | Histórico |
|--------|-----|-------|--------|---------|----------|-----------|
| admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| secretaria | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| instrutor | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| aluno | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Bloqueios na UI:** Campos desabilitados para instrutores
**Bloqueios na API:** Verificação de permissão `canManageStudents()`

### A.7 Integrações ligadas ao aluno

**Cobrança:** Não implementada (Asaas não configurado)

**Notificações:** 
- E-mail: Configurado mas não implementado
- WhatsApp: Não implementado
- SMS: Não implementado

### A.8 Relatórios/Exportações relacionadas a Alunos

**Relatórios existentes:**
- Lista de alunos (CSV) - Botão "Exportar" na interface
- Relatório de matrículas - `/admin/pages/relatorio-matriculas.php`

**Filtros aceitos:** Status, categoria CNH, CFC, período

### A.9 Pontos de dor / limitações

- Campos LGPD não implementados
- Sistema de documentos não funcional
- Integração financeira ausente
- Validação de CPF duplicado pode falhar
- Histórico limitado a dados básicos

### A.10 Legados que não podemos quebrar

- Campo `operacoes` (JSON) para controle de fases
- Relacionamento obrigatório com CFC
- Estrutura de categorias CNH fixa

---

## PARTE B — Inventário Geral do Sistema

### B.1 Mapa do Menu (visível hoje)

**Dashboard**
- `/admin/index.php` (admin, instrutor, secretaria)

**Cadastros** (apenas admin)
- Usuários: `/admin/index.php?page=usuarios&action=list`
- CFCs: `/admin/index.php?page=cfcs&action=list`
- Alunos: `/admin/pages/alunos.php`
- Instrutores: `/admin/pages/instrutores.php`
- Veículos: `/admin/pages/veiculos.php`

**Operacional**
- Agendamento: `/admin/pages/agendamento.php`

**Turmas Teóricas**
- Nova Turma: `/admin/pages/turmas.php?action=create`
- Lista de Turmas: `/admin/pages/turmas.php`
- Dashboard: `/admin/pages/turma-dashboard.php`
- Calendário: `/admin/pages/turma-calendario.php`
- Matrículas: `/admin/pages/turma-matriculas.php`
- Relatórios: `/admin/pages/turma-relatorios.php`
- Configurações: `/admin/pages/turma-configuracoes.php`
- Templates: `/admin/pages/turma-templates.php`
- Gerador de Grade: `/admin/pages/turma-grade-generator.php`

**Relatórios** (apenas admin)
- Relatório de Frequência: `/admin/pages/relatorio-frequencia.php`
- Relatório de Presenças: `/admin/pages/relatorio-presencas.php`
- Relatório de Matrículas: `/admin/pages/relatorio-matriculas.php`
- Relatório de ATA: `/admin/pages/relatorio-ata.php`

### B.2 Páginas/Telas por Módulo

**Dashboard**
- URL: `/admin/index.php`
- Propósito: Visão geral do sistema
- Ações: Visualizar estatísticas, acessar módulos

**Alunos**
- URL: `/admin/pages/alunos.php`
- Propósito: Gestão completa de alunos
- Ações: Criar, editar, excluir, visualizar, histórico, exportar

**Turmas Teóricas**
- URL: `/admin/pages/turmas.php`
- Propósito: Gestão de turmas e aulas teóricas
- Ações: Criar turma, chamada, diário, relatórios

**Agendamento**
- URL: `/admin/pages/agendamento.php`
- Propósito: Agendamento de aulas práticas
- Ações: Agendar, cancelar, reagendar aulas

**Instrutores**
- URL: `/admin/pages/instrutores.php`
- Propósito: Gestão de instrutores
- Ações: Criar, editar, excluir, visualizar

**Veículos**
- URL: `/admin/pages/veiculos.php`
- Propósito: Gestão da frota
- Ações: Criar, editar, excluir, manutenção

**CFCs**
- URL: `/admin/pages/cfcs.php`
- Propósito: Gestão de CFCs
- Ações: Criar, editar, excluir, visualizar

**Usuários**
- URL: `/admin/pages/usuarios.php`
- Propósito: Gestão de usuários do sistema
- Ações: Criar, editar, excluir, visualizar

### B.3 Fluxos principais do CFC já cobertos

**Pré-cadastro/Prospecção:** Não implementado

**Cadastro do Aluno e Matrícula/Serviço:**
- Telas: `/admin/pages/alunos.php`
- APIs: `/admin/api/alunos.php`
- Regras: Categoria obrigatória, CFC obrigatório

**Financeiro:** Não implementado (planos, cobranças, parcelamento)

**Agenda/Agendamentos:**
- Telas: `/admin/pages/agendamento.php`
- APIs: `/admin/api/agendamento.php`
- Regras: 50min por aula, max 3 aulas/instrutor/dia, intervalos obrigatórios

**Treinamento Teórico:**
- Telas: `/admin/pages/turmas.php`, `/admin/pages/turma-chamada.php`
- APIs: `/admin/api/turmas.php`, `/admin/api/turma-presencas.php`
- Regras: Frequência mínima 90%

**Treinamento Prático:**
- Telas: `/admin/pages/agendamento.php`
- APIs: `/admin/api/agendamento.php`
- Regras: Conflitos de horário, disponibilidade de veículos

**Exames/Provas:** Não implementado

**Conclusão/Certificação:** Não implementado

### B.4 APIs por Módulo (catalogação)

**Alunos**
- `/admin/api/alunos.php` - CRUD completo
- `/admin/api/historico.php?tipo=aluno` - Histórico

**Instrutores**
- `/admin/api/instrutores.php` - CRUD completo
- `/admin/api/historico.php?tipo=instrutor` - Histórico

**CFCs**
- `/admin/api/cfcs.php` - CRUD completo

**Veículos**
- `/admin/api/veiculos.php` - CRUD completo
- `/admin/api/manutencao.php` - Manutenções

**Usuários**
- `/admin/api/usuarios.php` - CRUD completo

**Agendamento**
- `/admin/api/agendamento.php` - CRUD de aulas
- `/admin/api/verificar-disponibilidade.php` - Verificação de conflitos

**Turmas**
- `/admin/api/turmas.php` - CRUD de turmas
- `/admin/api/turma-presencas.php` - Controle de presenças
- `/admin/api/turma-frequencia.php` - Cálculo de frequência
- `/admin/api/turma-diario.php` - Diário de classe
- `/admin/api/turma-relatorios.php` - Relatórios

**Configurações**
- `/admin/api/configuracoes.php` - Configurações de categorias

### B.5 Banco de Dados (visão de alto nível)

**Tabelas centrais:**

**usuarios** - Usuários do sistema
- Chaves: id (PK), email (UNIQUE)
- Relacionamentos: cfcs.responsavel_id, instrutores.usuario_id

**cfcs** - Centros de Formação de Condutores
- Chaves: id (PK), cnpj (UNIQUE)
- Relacionamentos: alunos.cfc_id, instrutores.cfc_id, veiculos.cfc_id

**alunos** - Alunos matriculados
- Chaves: id (PK), cpf (UNIQUE)
- Relacionamentos: aulas.aluno_id

**instrutores** - Instrutores credenciados
- Chaves: id (PK), credencial (UNIQUE)
- Relacionamentos: aulas.instrutor_id

**veiculos** - Frota de veículos
- Chaves: id (PK), placa (UNIQUE)
- Relacionamentos: aulas.veiculo_id

**aulas** - Aulas agendadas
- Chaves: id (PK)
- Relacionamentos: aluno_id, instrutor_id, veiculo_id, cfc_id

**turmas** - Turmas teóricas
- Chaves: id (PK)
- Relacionamentos: turma_alunos.turma_id

**sessoes** - Sessões de usuários
- Chaves: id (PK), token (UNIQUE)
- Relacionamentos: usuario_id

**logs** - Logs de auditoria
- Chaves: id (PK)
- Relacionamentos: usuario_id

**Campos de auditoria:** criado_em, atualizado_em em todas as tabelas principais

**Features de integridade:** Foreign Keys com RESTRICT, Unique Keys para campos únicos

### B.6 Permissões (matriz geral)

| Perfil | Dashboard | Alunos | Instrutores | Veículos | CFCs | Usuários | Turmas | Agendamento | Relatórios |
|--------|-----------|--------|-------------|----------|------|----------|--------|-------------|------------|
| admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| secretaria | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| instrutor | ✅ | 👁️ | ❌ | ❌ | ❌ | ❌ | 👁️ | 👁️ | ❌ |
| aluno | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

**Legenda:** ✅ = Total, 👁️ = Apenas visualização, ❌ = Sem acesso

### B.7 Relatórios/Exportações

**Relatórios funcionais:**
- Relatório de Frequência (`/admin/pages/relatorio-frequencia.php`)
- Relatório de Presenças (`/admin/pages/relatorio-presencas.php`)
- Relatório de Matrículas (`/admin/pages/relatorio-matriculas.php`)
- Relatório de ATA (`/admin/pages/relatorio-ata.php`)

**Exportações disponíveis:**
- CSV de alunos, instrutores, veículos, CFCs, usuários
- PDF de relatórios específicos

**Filtros:** Período, status, categoria, CFC

### B.8 Integrações externas

**Asaas:** Configurado mas não implementado
- Endpoints: Não utilizados
- Configs: `ASAAS_API_KEY`, `ASAAS_ENVIRONMENT`

**E-mail:** Configurado mas não implementado
- SMTP: Hostinger configurado
- Templates: Não implementados

**WhatsApp/SMS:** Não implementado

**APIs externas disponíveis:**
- ViaCEP: `VIA_CEP_API` configurado
- IBGE: `IBGE_API` configurado
- DETRAN: `DETRAN_API` não configurado

### B.9 Notificações

**Eventos configurados mas não implementados:**
- Criação de aluno
- Agendamento de aula
- Cancelamento de aula
- Vencimento de documentos

**Canais:** E-mail (configurado), WhatsApp (não implementado), SMS (não implementado)

### B.10 Configurações & Feature Flags

**Arquivo:** `/includes/config.php`

**Flags principais:**
- `DEBUG_MODE` - Modo debug
- `NOTIFICATIONS_ENABLED` - Notificações
- `REPORTS_ENABLED` - Relatórios
- `AUDIT_ENABLED` - Auditoria
- `MAINTENANCE_MODE` - Modo manutenção

**Configurações de ambiente:**
- `ENVIRONMENT` - local/production
- `APP_URL` - URL base
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` - Banco de dados

### B.11 Erros/Pendências Conhecidas

**Problemas identificados:**
- Campo `operacoes` (JSON) pode causar problemas de performance
- Validação de CPF duplicado pode falhar em casos específicos
- Sistema de documentos não funcional
- Integração financeira ausente
- Histórico limitado
- Campos LGPD não implementados

**Warnings conhecidos:**
- `strtotime(null)` em conversões de data
- Queries pesadas em relatórios com muitos dados
- Validações ausentes em alguns formulários

### B.12 Legados/Redundâncias

**Redundâncias identificadas:**
- Múltiplas versões de histórico de aluno (`historico-aluno.php`, `historico-aluno-novo.php`, `historico-aluno-melhorado.php`)
- Campos duplicados entre `usuarios` e `instrutores`
- Sistema de turmas antigo vs novo

**Dependências que impedem remoção:**
- Campo `operacoes` usado para controle de fases
- Relacionamentos obrigatórios com CFC
- Estrutura de categorias CNH fixa

### B.13 Ambientes

**Ambiente atual:** `localhost/cfc-bom-conselho`

**Variáveis críticas:**
- `ENVIRONMENT` = 'local'
- `DB_HOST` = 'localhost'
- `DB_NAME` = 'cfc_sistema'
- `APP_URL` = 'http://localhost/cfc-bom-conselho'

**Diferenças dev vs produção:**
- Debug mode ativo em local
- Logs mais verbosos em local
- Backup automático apenas em produção
- Rate limiting apenas em produção

---

## RESUMO EXECUTIVO

### ✅ **Funcionalidades Completas**
- Sistema de autenticação e permissões
- CRUD completo para todos os módulos principais
- Sistema de agendamento com regras de negócio
- Relatórios básicos funcionais
- Dashboard com estatísticas

### ⚠️ **Funcionalidades Parciais**
- Histórico de alunos (básico)
- Sistema de turmas (estrutura pronta, funcionalidades limitadas)
- Relatórios (básicos funcionais)

### ❌ **Funcionalidades Ausentes**
- Sistema financeiro completo
- Integração com Asaas
- Sistema de documentos
- Notificações automáticas
- Exames e certificação
- Campos LGPD
- Sistema de prospecção

### 🔧 **Principais Limitações**
- Integração financeira não implementada
- Sistema de documentos não funcional
- Notificações configuradas mas não implementadas
- Histórico limitado
- Campos LGPD ausentes

### 🎯 **Recomendações para Consolidação**
1. Implementar sistema financeiro básico
2. Ativar integração com Asaas
3. Implementar sistema de documentos
4. Ativar notificações por e-mail
5. Expandir histórico de alunos
6. Adicionar campos LGPD obrigatórios
7. Consolidar versões de histórico
8. Implementar sistema de prospecção
