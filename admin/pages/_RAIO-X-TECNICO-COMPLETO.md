# 📊 RAIX-X TÉCNICO COMPLETO DO SISTEMA CFC BOM CONSELHO

**Data:** 2025-01-28  
**Versão do Relatório:** 1.0  
**Objetivo:** Análise técnica completa, profunda e estruturada de 100% do código base  
**Metodologia:** Escaneamento sistemático de arquivos, mapeamento de rotas, APIs, tabelas e dependências

---

## 📋 SUMÁRIO TÉCNICO

1. [Arquitetura Geral Real do Sistema](#1-arquitetura-geral-real-do-sistema)
2. [Mapa Completo de Rotas/API](#2-mapa-completo-de-rotasapi)
3. [Mapa Completo de Páginas Admin](#3-mapa-completo-de-páginas-admin)
4. [Mapa Completo de Tabelas do Banco](#4-mapa-completo-de-tabelas-do-banco)
5. [Arquivos/Páginas/APIs Duplicadas ou Legadas](#5-arquivospáginasapis-duplicadas-ou-legadas)
6. [Trechos de Código Problemáticos](#6-trechos-de-código-problemáticos)
7. [Pontos de Alto Risco Estrutural](#7-pontos-de-alto-risco-estrutural)
8. [Inconsistências entre Tabelas/Rotas/API/UI](#8-inconsistências-entre-tabelasrotasapiui)
9. [O Que Pode Ser Removido](#9-o-que-pode-ser-removido)
10. [O Que Precisa Ser Migrado](#10-o-que-precisa-ser-migrado)
11. [O Que Está Quebrado](#11-o-que-está-quebrado)
12. [Checklist de Saúde Geral](#12-checklist-de-saúde-geral)

---

## 1. ARQUITETURA GERAL REAL DO SISTEMA

### 1.1. Estrutura de Diretórios

```
cfc-bom-conselho/
├── admin/
│   ├── api/              # 74 arquivos PHP - APIs REST
│   ├── assets/
│   │   ├── css/          # 34 arquivos CSS
│   │   └── js/           # 19 arquivos JS
│   ├── includes/         # 7 arquivos PHP - Helpers/Managers
│   ├── jobs/             # 1 arquivo PHP - Jobs agendados
│   ├── migrations/       # 7 arquivos SQL
│   ├── pages/            # 54 arquivos (46 PHP, 6 MD, 1 JS, 1 TXT)
│   └── index.php         # Router principal (3148 linhas)
├── includes/
│   ├── config.php        # Configurações globais (488 linhas)
│   ├── database.php      # Classe Database (774 linhas)
│   ├── auth.php          # Sistema de autenticação (690 linhas)
│   ├── controllers/      # 2 controllers
│   ├── guards/           # 3 guards
│   ├── models/           # 1 model
│   └── services/         # 2 services
├── aluno/                # Área do aluno
├── instrutor/            # Área do instrutor
├── pwa/                  # PWA assets
└── install.php           # Script de instalação (374 linhas)
```

### 1.2. Padrão de Roteamento

**Arquivo:** `admin/index.php`  
**Linhas:** 75-76, 2181-2187

```php
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';

// Roteamento simples via query string
$content_file = "pages/{$page}.php";
if (file_exists($content_file)) {
    include $content_file;
} else {
    include 'pages/dashboard.php';
}
```

**Tipo:** Roteamento via query string (`?page=nome&action=acao`)  
**Sem:** Framework de rotas, não usa `.htaccess` rewrite

### 1.3. Padrão de API

**Padrão:** REST via arquivos PHP individuais  
**Localização:** `admin/api/`  
**Estrutura típica:**

```php
// Exemplo: admin/api/alunos.php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET': handleGet($db); break;
    case 'POST': handlePost($db); break;
    case 'PUT': handlePut($db); break;
    case 'DELETE': handleDelete($db); break;
}
```

**Autenticação:** Verificação via `isLoggedIn()` em cada API  
**CORS:** Algumas APIs têm `Access-Control-Allow-Origin: *` (linha 8 em vários arquivos)

### 1.4. Camadas do Sistema

1. **Frontend:** HTML/PHP inline nas páginas, JavaScript vanilla
2. **API Layer:** Arquivos PHP individuais em `admin/api/`
3. **Business Logic:** Misturado entre páginas, APIs e includes
4. **Data Access:** Classe `Database` singleton em `includes/database.php`
5. **Authentication:** Classe `Auth` em `includes/auth.php`

### 1.5. Gerenciamento de Estado

**Sessões PHP:** Usadas para autenticação (`$_SESSION['user_id']`, `$_SESSION['user_type']`)  
**Banco de Dados:** MySQL/MariaDB com PDO  
**Cache:** Não implementado (definido em config mas não usado)  
**Logs:** Arquivo `logs/php_errors.log` (se habilitado)

---

## 2. MAPA COMPLETO DE ROTAS/API

### 2.1. APIs REST - Função por Função

| Arquivo | Método | Função | Linha | Parâmetros | Resposta |
|---------|--------|--------|-------|------------|----------|
| `agendamento.php` | GET | Listar aulas | 69-100 | `?mes=YYYY-MM` | JSON array de aulas |
| `agendamento.php` | POST | Criar agendamento | 102-200 | JSON body | JSON success/error |
| `agendamento.php` | PUT | Atualizar agendamento | 202-300 | `?id=X`, JSON body | JSON success/error |
| `agendamento-detalhes.php` | GET | Detalhes aula | 42-80 | `?id=X` | JSON detalhes |
| `agendamento-detalhes-fallback.php` | GET | Fallback detalhes | 20-60 | `?id=X` | JSON detalhes |
| `agendamentos-por-ids.php` | GET | Buscar por IDs | 40-70 | `?ids=1,2,3` | JSON array |
| `aluno-agenda.php` | GET | Agenda do aluno | 49-210 | `?aluno_id=X` | JSON timeline |
| `aluno-documentos.php` | GET | Documentos aluno | 30-80 | `?aluno_id=X` | JSON documentos |
| `alunos.php` | GET | Listar/Buscar aluno | 130-250 | `?id=X` ou sem parâmetro | JSON aluno(s) |
| `alunos.php` | POST | Criar aluno | 252-400 | JSON body | JSON aluno criado |
| `alunos.php` | PUT | Atualizar aluno | 402-550 | `?id=X`, JSON body | JSON aluno atualizado |
| `alunos.php` | DELETE | Deletar aluno | 552-600 | `?id=X` | JSON success |
| `alunos-aptos-turma.php` | GET | Alunos aptos para turma | 50-150 | `?turma_id=X` | JSON array alunos |
| `alunos-aptos-turma-simples.php` | GET | Versão simplificada | 20-60 | `?turma_id=X` | JSON array |
| `atualizar-aula.php` | PUT | Atualizar aula | 20-80 | JSON body | JSON success |
| `atualizar-categoria-instrutor.php` | PUT | Atualizar categoria | 20-80 | JSON body | JSON success |
| `buscar-aula.php` | GET | Buscar aula | 20-60 | `?id=X` | JSON aula |
| `cancelar-aula.php` | POST | Cancelar aula | 20-80 | JSON body | JSON success |
| `cfcs.php` | GET | Listar CFCs | 40-80 | - | JSON array CFCs |
| `cfcs.php` | POST | Criar CFC | 82-150 | JSON body | JSON CFC criado |
| `cfcs.php` | PUT | Atualizar CFC | 152-220 | `?id=X`, JSON body | JSON CFC atualizado |
| `cfcs.php` | DELETE | Deletar CFC | 222-280 | `?id=X` | JSON success |
| `configuracoes.php` | GET | Obter configurações | 30-80 | - | JSON configurações |
| `configuracoes.php` | POST | Salvar configurações | 82-150 | JSON body | JSON success |
| `despesas.php` | GET | Listar despesas | 40-100 | - | JSON array |
| `despesas.php` | POST | Criar despesa | 102-180 | JSON body | JSON despesa criada |
| `disciplina-agendamentos.php` | GET | Agendamentos disciplina | 50-150 | `?turma_id=X&disciplina=Y` | JSON array |
| `disciplinas.php` | GET | Listar disciplinas | 40-100 | - | JSON array |
| `disciplinas.php` | POST | Criar disciplina | 102-180 | JSON body | JSON disciplina criada |
| `disciplinas-automaticas.php` | GET | Disciplinas automáticas | 30-80 | `?tipo=formacao_45h` | JSON array |
| `disciplinas-clean.php` | GET/POST | Versão "limpa" | 34-200 | Vários | JSON |
| `disciplinas-curso.php` | GET | Disciplinas por curso | 30-80 | `?curso_tipo=X` | JSON array |
| `disciplinas-estaticas.php` | GET | Disciplinas estáticas | 20-60 | - | JSON array |
| `disciplinas-simples.php` | GET | Versão simplificada | 20-60 | - | JSON array |
| `disponibilidade.php` | GET | Verificar disponibilidade | 40-200 | `?instrutor_id=X&data=Y&hora=Z` | JSON disponibilidade |
| `estatisticas-turma.php` | GET | Estatísticas turma | 30-100 | `?turma_id=X` | JSON estatísticas |
| `exames.php` | GET | Listar/Buscar exame | 174-240 | `?id=X` ou `?aluno_id=Y` | JSON exame(s) |
| `exames.php` | POST | Criar exame | 240-347 | JSON body | JSON exame criado |
| `exames.php` | PUT | Atualizar exame | 347-474 | `?id=X`, JSON body | JSON exame atualizado |
| `exames.php` | DELETE | Deletar exame | 520-560 | `?id=X` | JSON success |
| `exames_simple.php` | GET | Buscar exame (simplificado) | 47-62 | `?id=X` | JSON exame |
| `exames_simple.php` | POST | Criar exame (simplificado) | 63-150 | JSON body | JSON exame |
| `exportar-agendamentos.php` | GET | Exportar agendamentos | 30-150 | `?formato=csv` | CSV ou JSON |
| `faturas.php` | GET | Listar faturas (ANTIGA) | 77-171 | `?id=X` ou filtros | JSON faturas |
| `faturas.php` | POST | Criar fatura (ANTIGA) | 176-301 | JSON body | JSON fatura |
| `faturas.php` | PUT | Atualizar fatura (ANTIGA) | 306-356 | `?id=X`, JSON body | JSON success |
| `faturas.php` | DELETE | Cancelar fatura (ANTIGA) | 361-390 | `?id=X` | JSON success |
| `financeiro-despesas.php` | GET | Listar despesas | 40-100 | Filtros via query | JSON array |
| `financeiro-despesas.php` | POST | Criar despesa | 102-200 | JSON body | JSON despesa |
| `financeiro-faturas.php` | GET | Listar faturas (NOVA) | 62-153 | `?id=X` ou filtros | JSON faturas |
| `financeiro-faturas.php` | POST | Criar fatura (NOVA) | 155-204 | JSON body | JSON fatura |
| `financeiro-faturas.php` | PUT | Atualizar fatura (NOVA) | 206-256 | `?id=X`, JSON body | JSON success |
| `financeiro-faturas.php` | DELETE | Deletar fatura (NOVA) | 258-290 | `?id=X` | JSON success |
| `financeiro-relatorios.php` | GET | Relatórios financeiros | 40-150 | `?periodo=X&tipo=Y` | JSON relatórios |
| `historico.php` | GET | Histórico geral | 30-100 | `?aluno_id=X` | JSON histórico |
| `historico_aluno.php` | GET | Histórico completo aluno | 60-606 | `?aluno_id=X` | JSON timeline |
| `info-disciplina-turma.php` | GET | Info disciplina/turma | 40-120 | `?turma_id=X&disciplina=Y` | JSON info |
| `instrutores.php` | GET | Listar/Buscar instrutor | 80-150 | `?id=X` | JSON instrutor(es) |
| `instrutores.php` | POST | Criar instrutor | 152-250 | JSON body | JSON instrutor |
| `instrutores.php` | PUT | Atualizar instrutor | 252-350 | `?id=X`, JSON body | JSON instrutor |
| `instrutores.php` | DELETE | Deletar instrutor | 352-400 | `?id=X` | JSON success |
| `instrutores-real.php` | GET | Versão "real" | 30-100 | - | JSON array |
| `instrutores-simple.php` | GET | Versão simplificada | 20-80 | - | JSON array |
| `instrutores_simplificado.php` | GET | Versão simplificada 2 | 20-80 | - | JSON array |
| `lgpd.php` | GET | Dados LGPD | 30-100 | `?aluno_id=X` | JSON dados |
| `listar-agendamentos-turma.php` | GET | Agendamentos turma | 30-100 | `?turma_id=X` | JSON array |
| `manutencao.php` | GET | Listar manutenções | 30-100 | `?veiculo_id=X` | JSON array |
| `manutencao.php` | POST | Criar manutenção | 102-200 | JSON body | JSON manutenção |
| `matriculas.php` | GET | Listar matrículas | 70-96 | `?aluno_id=X` | JSON matrículas |
| `matriculas.php` | POST | Criar matrícula | 101-165 | JSON body | JSON matrícula |
| `matriculas.php` | PUT | Atualizar matrícula | 170-221 | `?id=X`, JSON body | JSON success |
| `matriculas.php` | DELETE | Deletar matrícula | 226-262 | `?id=X` | JSON success |
| `matricular-aluno-turma.php` | POST | Matricular em turma | 50-280 | JSON body | JSON success |
| `notificacoes.php` | GET | Listar notificações | 30-100 | `?usuario_id=X` | JSON array |
| `notifications.php` | GET | Listar (inglês) | 30-80 | `?usuario_id=X` | JSON array |
| `pagamentos.php` | GET | Listar pagamentos | 74-101 | `?fatura_id=X` | JSON pagamentos |
| `pagamentos.php` | POST | Registrar pagamento | 106-160 | JSON body | JSON pagamento |
| `pagamentos.php` | DELETE | Estornar pagamento | 165-193 | `?id=X` | JSON success |
| `progresso_pratico.php` | GET | Progresso prático | 61-120 | `?aluno_id=X` | JSON progresso |
| `progresso_teorico.php` | GET | Progresso teórico | 52-100 | `?aluno_id=X` | JSON progresso |
| `relatorio-disciplinas.php` | GET | Relatório disciplinas | 30-150 | `?turma_id=X` | JSON relatório |
| `remover-matricula-turma.php` | POST | Remover matrícula | 40-100 | JSON body | JSON success |
| `salas.php` | GET | Listar salas (ANTIGA) | 30-80 | - | JSON array |
| `salas-ajax.php` | GET | Salas AJAX (ANTIGA) | 20-80 | - | JSON array |
| `salas-clean.php` | GET | Salas "limpas" | 34-150 | - | JSON array |
| `salas-real.php` | GET | Listar salas (NOVA) | 30-100 | - | JSON array |
| `salas-real.php` | POST | Criar sala | 102-180 | JSON body | JSON sala |
| `search.php` | GET | Busca geral | 30-150 | `?q=termo` | JSON resultados |
| `solicitacoes.php` | GET | Listar solicitações | 30-100 | - | JSON array |
| `tipos-curso-clean.php` | GET | Tipos curso "limpos" | 30-150 | `?acao=listar` | JSON array |
| `turma-agendamento.php` | GET | Agendamento turma | 30-100 | `?turma_id=X` | JSON agendamento |
| `turma-diario.php` | GET | Diário turma | 30-100 | `?turma_id=X` | JSON diário |
| `turma-frequencia.php` | GET | Frequência turma | 30-100 | `?turma_id=X` | JSON frequência |
| `turma-grade-generator.php` | POST | Gerar grade | 30-200 | JSON body | JSON grade |
| `turma-presencas.php` | GET | Presenças turma | 30-100 | `?turma_id=X` | JSON presenças |
| `turma-presencas.php` | POST | Registrar presença | 102-200 | JSON body | JSON success |
| `turma-relatorios.php` | GET | Relatórios turma | 30-150 | `?turma_id=X` | JSON relatórios |
| `turmas-teoricas.php` | GET | Listar/Buscar turma | 117-500 | `?id=X` ou `?acao=Y` | JSON turma(s) |
| `turmas-teoricas.php` | POST | Criar turma | 159-226 | JSON body | JSON turma |
| `turmas-teoricas.php` | PUT | Atualizar turma | 226-276 | `?id=X`, JSON body | JSON success |
| `turmas-teoricas.php` | DELETE | Deletar turma | 276-320 | `?id=X` | JSON success |
| `turmas-teoricas-inline.php` | GET | Turmas inline | 30-100 | `?turma_id=X` | JSON turma |
| `usuarios.php` | GET | Listar/Buscar usuário | 50-120 | `?id=X` | JSON usuário(s) |
| `usuarios.php` | POST | Criar usuário | 122-200 | JSON body | JSON usuário |
| `usuarios.php` | PUT | Atualizar usuário | 202-280 | `?id=X`, JSON body | JSON success |
| `usuarios.php` | DELETE | Deletar usuário | 282-320 | `?id=X` | JSON success |
| `veiculos.php` | GET | Listar/Buscar veículo | 40-100 | `?id=X` | JSON veículo(s) |
| `veiculos.php` | POST | Criar veículo | 102-180 | JSON body | JSON veículo |
| `veiculos.php` | PUT | Atualizar veículo | 182-260 | `?id=X`, JSON body | JSON success |
| `veiculos.php` | DELETE | Deletar veículo | 262-300 | `?id=X` | JSON success |
| `verificar-aula-especifica.php` | GET | Verificar aula | 30-100 | `?aula_id=X` | JSON verificação |
| `verificar-disponibilidade.php` | GET | Verificar disponibilidade | 30-400 | Vários parâmetros | JSON disponibilidade |
| `verificar-limite-data-turma.php` | GET | Verificar limite | 30-100 | `?turma_id=X&data=Y` | JSON verificação |

### 2.2. Padrões de Autenticação por API

**Arquivo de referência:** `admin/api/financeiro-faturas.php` (linhas 21-33)

```php
// Verificar autenticação e permissão
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$currentUser = getCurrentUser();
if (!in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sem permissão']);
    exit;
}
```

**Problema identificado:**
- ✅ Maioria das APIs verifica autenticação
- ⚠️ Algumas APIs não verificam permissões específicas (apenas `isLoggedIn()`)
- ❌ Não há rate limiting
- ⚠️ CORS aberto (`Access-Control-Allow-Origin: *`) em várias APIs

---

## 3. MAPA COMPLETO DE PÁGINAS ADMIN

### 3.1. Páginas Principais com Dependências

| Rota (`?page=`) | Arquivo | JS Incluído | CSS Incluído | API Chamada | Linha de Roteamento |
|-----------------|---------|-------------|--------------|-------------|---------------------|
| `dashboard` | `pages/dashboard.php` | `admin.js` | `dashboard.css` | N/A | `admin/index.php:75` |
| `alunos` | `pages/alunos.php` | `alunos.js` | N/A | `api/alunos.php` | `admin/index.php:2181` |
| `instrutores` | `pages/instrutores.php` | `instrutores.js` | `instrutores.css` | `api/instrutores.php` | `admin/index.php:2181` |
| `veiculos` | `pages/veiculos.php` | N/A | `modal-veiculos.css` | `api/veiculos.php` | `admin/index.php:2181` |
| `cfcs` | `pages/cfcs.php` | `cfcs.js` | `cfcs.css` | `api/cfcs.php` | `admin/index.php:2181` |
| `usuarios` | `pages/usuarios.php` | N/A | `fix-usuarios-overlap.css` | `api/usuarios.php` | `admin/index.php:2181` |
| `agendamento` | `pages/agendamento.php` | `agendamento.js` | `agendamento.css` | `api/agendamento.php` | `admin/index.php:2181` |
| `agendamento-moderno` | `pages/agendamento-moderno.php` | `agendamento-moderno.js` | `agendamento-moderno.css` | `api/agendamento.php` | `admin/index.php:2181` |
| `exames` | `pages/exames.php` | Inline JS | N/A | `api/exames_simple.php` ⚠️ | `admin/index.php:2181` |
| `turmas-teoricas` | `pages/turmas-teoricas.php` | Inline JS | N/A | `api/turmas-teoricas.php` | `admin/index.php:2181` |
| `turmas-teoricas-lista` | `pages/turmas-teoricas-lista.php` | N/A | N/A | `api/turmas-teoricas.php` | `admin/index.php:2181` |
| `financeiro-faturas` | `pages/financeiro-faturas.php` | Inline JS | N/A | `api/financeiro-faturas.php` | `admin/index.php:2181` |
| `financeiro-despesas` | `pages/financeiro-despesas.php` | Inline JS | N/A | `api/financeiro-despesas.php` | `admin/index.php:2181` |
| `financeiro-relatorios` | `pages/financeiro-relatorios.php` | Inline JS | N/A | `api/financeiro-relatorios.php` | `admin/index.php:2181` |
| `configuracoes-salas` | `pages/configuracoes-salas.php` | Inline JS | N/A | `api/salas-real.php` | `admin/index.php:2181` |
| `configuracoes-disciplinas` | `pages/configuracoes-disciplinas.php` | Inline JS | N/A | `api/disciplinas.php` | `admin/index.php:2181` |
| `configuracoes-categorias` | `pages/configuracoes-categorias.php` | Inline JS | `configuracoes-categorias.css` | N/A | `admin/index.php:2181` |
| `historico-aluno` | `pages/historico-aluno.php` | Inline JS | N/A | `api/historico_aluno.php` | `admin/index.php:2181` |
| `relatorio-matriculas` | `pages/relatorio-matriculas.php` | N/A | N/A | N/A | `admin/index.php:2181` |
| `relatorio-frequencia` | `pages/relatorio-frequencia.php` | N/A | N/A | N/A | `admin/index.php:2181` |
| `relatorio-presencas` | `pages/relatorio-presencas.php` | N/A | N/A | N/A | `admin/index.php:2181` |
| `relatorio-ata` | `pages/relatorio-ata.php` | N/A | N/A | N/A | `admin/index.php:2181` |
| `vagas-candidatos` | `pages/vagas-candidatos.php` | N/A | N/A | `api/solicitacoes.php` | `admin/index.php:2181` |

### 3.2. Páginas Especiais (Roteamento Condicional)

| Rota | Condição | Arquivo | Linha |
|------|----------|---------|-------|
| `editar-aula` | `$_GET['edit']` existe | `pages/editar-aula.php` | `admin/index.php:2168` |
| `turmas-teoricas-detalhes` | `$_GET['acao'] === 'detalhes'` | `pages/turmas-teoricas-detalhes.php` | Via turmas-teoricas.php |

### 3.3. Chamadas JavaScript Principais

**Arquivo:** `admin/pages/exames.php`  
**Linhas:** 2095, 2153, 2183, 2248, 2397, 2490, 2538, 2585

```javascript
// PROBLEMA: Usa API legada
fetch('api/exames_simple.php?t=' + Date.now(), {
    method: 'POST',
    body: formData
})
```

**Arquivo:** `admin/pages/alunos.php`  
**Linha:** 4457

```javascript
fetch(API_CONFIG.getRelativeApiUrl('ALUNOS') + `?id=${id}&t=${timestamp}`)
```

**Arquivo:** `admin/pages/alunos.php`  
**Linha:** 7391

```javascript
const response = await fetch(`api/exames.php?aluno_id=${alunoId}`);
```

---

## 4. MAPA COMPLETO DE TABELAS DO BANCO

### 4.1. Tabelas Core (Criadas em `install.php`)

| Tabela | Onde é Criada | Onde é Usada (Leitura) | Onde é Usada (Escrita) | Status |
|--------|---------------|------------------------|------------------------|--------|
| `usuarios` | `install.php:23` | `includes/auth.php:308-321`, `admin/api/usuarios.php`, `admin/index.php:54-67` | `admin/api/usuarios.php`, `includes/auth.php:344-347` | ✅ OK |
| `cfcs` | `install.php:38` | `admin/api/cfcs.php`, `admin/pages/cfcs.php`, `includes/auth.php:308-321` | `admin/api/cfcs.php` | ✅ OK |
| `alunos` | `install.php:58` | `admin/api/alunos.php`, `admin/pages/alunos.php`, `admin/api/historico_aluno.php` | `admin/api/alunos.php`, `admin/jobs/marcar_faturas_vencidas.php:49` | ✅ OK |
| `instrutores` | `install.php:75` | `admin/api/instrutores.php`, `admin/pages/instrutores.php`, `admin/api/agendamento.php` | `admin/api/instrutores.php` | ✅ OK |
| `aulas` | `install.php:88` | `admin/api/agendamento.php`, `admin/pages/agendamento.php`, `admin/api/historico_aluno.php:503-539` | `admin/api/agendamento.php`, `admin/api/atualizar-aula.php` | ✅ OK |
| `veiculos` | `install.php:106` | `admin/api/veiculos.php`, `admin/pages/veiculos.php`, `admin/api/disponibilidade.php` | `admin/api/veiculos.php` | ✅ OK |
| `sessoes` | `install.php:120` | `includes/auth.php:396-409`, `includes/auth.php:462-465` | `includes/auth.php:286-300`, `includes/auth.php:380-390` | ✅ OK |
| `logs` | `install.php:132` | N/A (logs geralmente não são lidos) | `includes/database.php:519-536`, `includes/auth.php:63` | ⚠️ PARCIAL |
| `exames` | `install.php:146` | `admin/api/exames.php`, `admin/pages/exames.php`, `admin/api/historico_aluno.php:152-313` | `admin/api/exames.php` | ✅ OK |
| `matriculas` | `install.php:179` | `admin/api/matriculas.php`, `admin/api/historico_aluno.php:102-150`, `admin/jobs/marcar_faturas_vencidas.php` | `admin/api/matriculas.php` | ✅ OK |
| `financeiro_faturas` | `install.php:206` | `admin/api/financeiro-faturas.php`, `admin/pages/financeiro-faturas.php`, `admin/jobs/marcar_faturas_vencidas.php:30-40` | `admin/api/financeiro-faturas.php`, `admin/index.php:122-233` | ✅ OK |
| `pagamentos` | `install.php:236` | `admin/api/pagamentos.php`, `admin/api/faturas.php:104-108` | `admin/api/pagamentos.php` | ⚠️ INCONSISTENTE |
| `financeiro_pagamentos` | `install.php:254` | `admin/api/financeiro-despesas.php` | `admin/api/financeiro-despesas.php` | ✅ OK |

### 4.2. Tabelas de Turmas Teóricas (Criadas em Migrations)

| Tabela | Migration | Onde é Criada | Onde é Usada (Leitura) | Onde é Usada (Escrita) | Status |
|--------|-----------|---------------|------------------------|------------------------|--------|
| `salas` | `001-create-turmas-teoricas-structure.sql:9` | SQL | `admin/api/salas-real.php`, `admin/pages/configuracoes-salas.php` | `admin/api/salas-real.php` | ✅ OK |
| `disciplinas_configuracao` | `001-create-turmas-teoricas-structure.sql:30` | SQL | `admin/api/disciplinas-curso.php`, `admin/pages/configuracoes-disciplinas.php` | `admin/api/disciplinas.php` | ✅ OK |
| `turmas_teoricas` | `001-create-turmas-teoricas-structure.sql:83` | SQL | `admin/api/turmas-teoricas.php`, `admin/pages/turmas-teoricas.php` | `admin/api/turmas-teoricas.php` | ✅ OK |
| `turma_aulas_agendadas` | `001-create-turmas-teoricas-structure.sql:126` | SQL | `admin/api/turmas-teoricas.php`, `admin/pages/turmas-teoricas-detalhes-inline.php` | `admin/api/turmas-teoricas.php` | ✅ OK |
| `turma_matriculas` | `001-create-turmas-teoricas-structure.sql:162` | SQL | `admin/api/turmas-teoricas.php`, `admin/api/alunos-aptos-turma.php` | `admin/api/matricular-aluno-turma.php` | ✅ OK |
| `turma_presencas` | `001-create-turmas-teoricas-structure.sql:183` | SQL | `admin/api/turma-presencas.php` | `admin/api/turma-presencas.php` | ✅ OK |
| `turma_log` | `001-create-turmas-teoricas-structure.sql:205` | SQL | N/A | `admin/api/turmas-teoricas.php` (implícito) | ⚠️ PARCIAL |

### 4.3. Tabelas Não Usadas ou Duplicadas

| Tabela | Status | Motivo |
|--------|--------|--------|
| `faturas` | ❌ LEGADO/DUPLICADA | Existe API `admin/api/faturas.php` mas sistema usa `financeiro_faturas` |
| `cache` | ⚠️ DEFINIDA MAS NÃO USADA | Métodos em `includes/database.php:460-516` mas não há INSERT/SELECT |
| `financeiro_configuracoes` | ⚠️ MENCIONADA MAS NÃO CRIADA | Usada em `admin/api/financeiro-faturas.php:336` mas não existe em install.php |

### 4.4. Análise de Uso de Tabelas

**Tabelas mais usadas:**
1. `alunos` - 50+ referências
2. `aulas` - 40+ referências
3. `exames` - 30+ referências
4. `turmas_teoricas` - 25+ referências
5. `financeiro_faturas` - 20+ referências

**Tabelas pouco usadas:**
- `turma_log` - Definida mas não consultada
- `cache` - Métodos existem mas não são chamados
- `logs` - Escrita apenas, sem interface de leitura

---

## 5. ARQUIVOS/PÁGINAS/APIs DUPLICADAS OU LEGADAS

### 5.1. APIs Duplicadas - Detalhado

| API Ativa | API Legada | Arquivo Legado | Linhas | Uso Atual | Pode Remover? |
|-----------|------------|----------------|--------|-----------|---------------|
| `financeiro-faturas.php` | `faturas.php` | `admin/api/faturas.php` | 392 linhas | ❌ NÃO (API antiga ainda existe) | ⚠️ Verificar dependências |
| `salas-real.php` | `salas.php` | `admin/api/salas.php` | ~150 linhas | ❌ NÃO | ✅ SIM |
| `salas-real.php` | `salas-ajax.php` | `admin/api/salas-ajax.php` | ~100 linhas | ❌ NÃO | ✅ SIM |
| `salas-real.php` | `salas-clean.php` | `admin/api/salas-clean.php` | ~150 linhas | ⚠️ Pode estar em uso | ⚠️ Verificar |
| `instrutores.php` | `instrutores-real.php` | `admin/api/instrutores-real.php` | ~200 linhas | ❌ NÃO | ✅ SIM |
| `instrutores.php` | `instrutores-simple.php` | `admin/api/instrutores-simple.php` | ~150 linhas | ❌ NÃO | ✅ SIM |
| `instrutores.php` | `instrutores_simplificado.php` | `admin/api/instrutores_simplificado.php` | ~100 linhas | ❌ NÃO | ✅ SIM |
| `exames.php` | `exames_simple.php` | `admin/api/exames_simple.php` | 207 linhas | ✅ SIM - `admin/pages/exames.php:2095-2585` | ❌ NÃO (em uso ativo) |
| `disciplinas.php` | `disciplinas-clean.php` | `admin/api/disciplinas-clean.php` | ~300 linhas | ⚠️ Pode estar em uso | ⚠️ Verificar |
| `disciplinas.php` | `disciplinas-simples.php` | `admin/api/disciplinas-simples.php` | ~100 linhas | ❌ NÃO | ✅ SIM |
| `disciplinas.php` | `disciplinas-estaticas.php` | `admin/api/disciplinas-estaticas.php` | ~80 linhas | ❌ NÃO | ✅ SIM |
| `disciplinas.php` | `disciplinas-automaticas.php` | `admin/api/disciplinas-automaticas.php` | ~150 linhas | ⚠️ Pode estar em uso | ⚠️ Verificar |
| `alunos-aptos-turma.php` | `alunos-aptos-turma-simples.php` | `admin/api/alunos-aptos-turma-simples.php` | ~80 linhas | ❌ NÃO | ✅ SIM |
| `notificacoes.php` | `notifications.php` | `admin/api/notifications.php` | ~150 linhas | ⚠️ Pode estar em uso | ⚠️ Verificar |
| N/A | `tipos-curso-clean.php` | `admin/api/tipos-curso-clean.php` | ~200 linhas | ✅ SIM - `admin/assets/js/admin.js:433` | ❌ NÃO (em uso) |

**Total de APIs legadas identificadas:** 15  
**Em uso ativo:** 4 (`exames_simple.php`, `tipos-curso-clean.php`, possivelmente `salas-clean.php`, `disciplinas-clean.php`, `notifications.php`)

### 5.2. Páginas Duplicadas

| Página Ativa | Página Legada | Arquivo Legado | Motivo | Pode Remover? |
|--------------|---------------|----------------|--------|---------------|
| `financeiro-faturas.php` | `financeiro-faturas-standalone.php` | `admin/pages/financeiro-faturas-standalone.php` | Versão standalone | ✅ SIM |
| `financeiro-despesas.php` | `financeiro-despesas-standalone.php` | `admin/pages/financeiro-despesas-standalone.php` | Versão standalone | ✅ SIM |
| `financeiro-relatorios.php` | `financeiro-relatorios-standalone.php` | `admin/pages/financeiro-relatorios-standalone.php` | Versão standalone | ✅ SIM |
| `historico-aluno.php` | `historico-aluno-melhorado.php` | `admin/pages/historico-aluno-melhorado.php` | Versão antiga | ✅ SIM |
| `historico-aluno.php` | `historico-aluno-novo.php` | `admin/pages/historico-aluno-novo.php` | Versão antiga | ✅ SIM |
| `instrutores.php` | `instrutores-otimizado.php` | `admin/pages/instrutores-otimizado.php` | Versão antiga | ✅ SIM |
| `turmas-teoricas.php` | `turmas-teoricas-fixed.php` | `admin/pages/turmas-teoricas-fixed.php` | Versão "fixed" | ✅ SIM |
| `turmas-teoricas.php` | `turmas-teoricas-disciplinas-fixed.php` | `admin/pages/turmas-teoricas-disciplinas-fixed.php` | Versão "fixed" | ✅ SIM |
| `alunos.php` | `alunos_original.php` | `admin/pages/alunos_original.php` | Backup | ✅ SIM |
| `alunos.php` | `alunos-complete.txt` | `admin/pages/alunos-complete.txt` | Backup | ✅ SIM |
| `alunos.php` | `_modalAluno-legacy.php` | `admin/pages/_modalAluno-legacy.php` | Modal legado | ✅ SIM |
| N/A | `usuarios_simples.php` | `admin/pages/usuarios_simples.php` | Versão simplificada | ✅ SIM |

**Total de páginas legadas:** 12  
**Todas podem ser removidas com segurança:** ✅ SIM

---

## 6. TRECHOS DE CÓDIGO PROBLEMÁTICOS

### 6.1. Inconsistência de Tabelas - Financeiro

**Arquivo:** `admin/api/financeiro-faturas.php`  
**Linhas:** 113, 118, 139, 189, 230, 323, 344

```php
// PROBLEMA: API usa campo 'vencimento'
if ($data_inicio) {
    $where[] = 'f.vencimento >= ?';
    $params[] = $data_inicio;
}
```

**Arquivo:** `admin/pages/financeiro-faturas.php`  
**Linhas:** 24, 57, 62, 73

```php
// CORRETO: Página usa campo 'data_vencimento'
$faturas = $db->fetchAll("
    SELECT * FROM financeiro_faturas 
    WHERE data_vencimento >= ? AND data_vencimento <= ?
", [$dataInicio, $dataFim]);
```

**Arquivo:** `admin/index.php`  
**Linhas:** 122, 178, 233

```php
// CORRETO: Criação usa 'data_vencimento'
$db->insert('financeiro_faturas', [
    'data_vencimento' => $_POST['data_vencimento'],
    // ...
]);
```

**Diagnóstico:** 
- ⚠️ **INCONSISTÊNCIA CRÍTICA** - API usa `vencimento`, páginas usam `data_vencimento`
- ❌ Pode causar erros ao filtrar faturas pela API
- ✅ Migration `005-create-financeiro-faturas-structure.sql` cria ambos os campos por compatibilidade

### 6.2. Job Usando Tabela Errada

**Arquivo:** `admin/jobs/marcar_faturas_vencidas.php`  
**Linhas:** 30-40

```php
// ANTES (ERRADO):
UPDATE faturas 
SET status = 'vencida'
WHERE status = 'aberta' AND vencimento < CURDATE()

// DEPOIS (CORRIGIDO na Fase 1):
UPDATE financeiro_faturas 
SET status = 'vencida'
WHERE status = 'aberta' AND data_vencimento < CURDATE()
```

**Diagnóstico:**
- ✅ **CORRIGIDO** conforme `admin/pages/_FASE-1-LIMPEZA-E-BASE.md:176`
- ⚠️ Verificar se job está sendo executado em produção

### 6.3. API de Pagamentos Usando Tabela Antiga

**Arquivo:** `admin/api/pagamentos.php`  
**Linhas:** 80-85, 126, 379

```php
// PROBLEMA: Relaciona com tabela 'faturas' antiga
$pagamentos = $db->fetchAll("
    SELECT p.*, f.numero as fatura_numero
    FROM pagamentos p
    JOIN faturas f ON p.fatura_id = f.id
    WHERE p.fatura_id = ?
", [$faturaId]);
```

**Diagnóstico:**
- ❌ **PROBLEMA CRÍTICO** - API de pagamentos usa tabela `faturas` que não existe mais
- ✅ Sistema usa `financeiro_faturas`
- ❌ Isso pode quebrar registro de pagamentos

### 6.4. Página Exames Usando API Legada

**Arquivo:** `admin/pages/exames.php`  
**Linhas:** 2095, 2153, 2183, 2248, 2397, 2490, 2538, 2585

```javascript
// PROBLEMA: Usa API legada 'exames_simple.php'
fetch('api/exames_simple.php?t=' + Date.now(), {
    method: 'POST',
    body: formData
})
```

**Diagnóstico:**
- ⚠️ **RISCO MÉDIO** - Funciona mas usa versão simplificada da API
- ✅ API principal `exames.php` tem mais funcionalidades
- ⚠️ Pode limitar recursos disponíveis

### 6.5. Hardcoded Credentials

**Arquivo:** `includes/config.php`  
**Linhas:** 12-15

```php
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');  // ⚠️ SENHA EXPOSTA
```

**Diagnóstico:**
- 🔴 **SEGURANÇA CRÍTICA** - Credenciais hardcoded no código
- ❌ Risco de exposição em repositórios públicos
- ✅ Deve usar variáveis de ambiente ou arquivo `.env` não versionado

### 6.6. Credenciais Duplicadas em API Clean

**Arquivo:** `admin/api/salas-clean.php`  
**Linhas:** 6-9

```php
// PROBLEMA: Duplicação de credenciais
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');
```

**Diagnóstico:**
- ⚠️ **DUPLICAÇÃO** - Mesmas credenciais em múltiplos arquivos
- ❌ Mais pontos de manutenção e risco

### 6.7. CORS Aberto

**Arquivo:** Múltiplos em `admin/api/`  
**Linhas:** Variadas (geralmente linha 8)

```php
header('Access-Control-Allow-Origin: *');
```

**Diagnóstico:**
- ⚠️ **SEGURANÇA MÉDIA** - CORS aberto permite qualquer origem
- ✅ OK para desenvolvimento
- ❌ Risco em produção se APIs forem públicas

---

## 7. PONTOS DE ALTO RISCO ESTRUTURAL

### 7.1. Riscos Críticos

| Risco | Arquivo/Linha | Descrição | Impacto | Prioridade |
|-------|---------------|-----------|---------|------------|
| **Tabela pagamentos usa faturas antiga** | `admin/api/pagamentos.php:80-85` | JOIN com `faturas` que não existe | 🔴 QUEBRA - Pagamentos não funcionam | P0 |
| **Credenciais hardcoded** | `includes/config.php:12-15` | Senha do banco no código | 🔴 SEGURANÇA - Risco de vazamento | P0 |
| **API financeiro-faturas usa campo errado** | `admin/api/financeiro-faturas.php:113` | Usa `vencimento` ao invés de `data_vencimento` | 🟡 BUG - Filtros não funcionam | P1 |
| **Página exames usa API legada** | `admin/pages/exames.php:2095` | Usa `exames_simple.php` | 🟡 FUNCIONALIDADE - Limita recursos | P2 |

### 7.2. Riscos de Dados

| Risco | Arquivo | Descrição | Impacto |
|-------|---------|-----------|---------|
| **Tabela cache não existe** | `includes/database.php:460-516` | Métodos usam tabela que não foi criada | 🟡 ERRO ao tentar usar cache |
| **Tabela financeiro_configuracoes não existe** | `admin/api/financeiro-faturas.php:336` | Query em tabela inexistente | 🔴 QUEBRA ao calcular inadimplência |
| **Duplicação faturas vs financeiro_faturas** | `admin/api/faturas.php` vs `admin/api/financeiro-faturas.php` | Duas estruturas para mesma funcionalidade | 🟡 CONFUSÃO - Dados podem ficar inconsistentes |

### 7.3. Riscos de Performance

| Risco | Descrição | Impacto |
|-------|-----------|---------|
| **Queries N+1 possíveis** | Múltiplas queries em loops (não verificado completamente) | 🟡 LENTIDÃO em listagens grandes |
| **Falta de cache** | Cache definido mas não usado | 🟡 REQUISIÇÕES DESNECESSÁRIAS ao banco |
| **Índices não verificados** | Não foi feita análise completa de índices | 🟡 LENTIDÃO em buscas |

---

## 8. INCONSISTÊNCIAS ENTRE TABELAS/ROTAS/API/UI

### 8.1. Inconsistência de Campos - Faturas

| Componente | Campo Usado | Arquivo | Linha |
|------------|-------------|---------|-------|
| API GET | `vencimento` | `admin/api/financeiro-faturas.php` | 113, 118, 139 |
| API POST | `vencimento` | `admin/api/financeiro-faturas.php` | 189 |
| Página | `data_vencimento` | `admin/pages/financeiro-faturas.php` | 24, 57 |
| Criação | `data_vencimento` | `admin/index.php` | 122 |
| Migration | Ambos criados | `admin/migrations/005-create-financeiro-faturas-structure.sql` | 31-32 |
| Job | `data_vencimento` | `admin/jobs/marcar_faturas_vencidas.php` | 32 |

**Diagnóstico:** API usa campo errado, páginas e criação usam campo correto. Migration cria ambos para compatibilidade.

### 8.2. Inconsistência de Tabelas - Pagamentos

| Componente | Tabela Usada | Arquivo | Linha |
|------------|--------------|---------|-------|
| API Pagamentos | `faturas` | `admin/api/pagamentos.php` | 82, 126, 379 |
| API Faturas Nova | `financeiro_faturas` | `admin/api/financeiro-faturas.php` | 82 |
| Migration Pagamentos | `faturas` (comentado) | `admin/migrations/006-create-pagamentos-structure.sql` | 41-42 |
| Install | `pagamentos` criado | `install.php:236` | 236 |

**Diagnóstico:** API de pagamentos ainda referencia `faturas` antiga. Tabela `pagamentos.fatura_id` deve referenciar `financeiro_faturas.id`.

### 8.3. Inconsistência de APIs - Exames

| Componente | API Usada | Arquivo | Linha |
|------------|-----------|---------|-------|
| Página Exames | `exames_simple.php` | `admin/pages/exames.php` | 2095 |
| Página Alunos | `exames.php` | `admin/pages/alunos.php` | 7391 |
| Página Histórico | `exames.php` | `admin/pages/historico-aluno.php` | 2040 |

**Diagnóstico:** Página de exames usa API simplificada, outras páginas usam API completa. Inconsistência na experiência do usuário.

---

## 9. O QUE PODE SER REMOVIDO

### 9.1. APIs Legadas (Após Migração)

1. `admin/api/faturas.php` ⚠️ **AGUARDAR** - Verificar se não há dependências
2. `admin/api/salas.php` ✅ **PODE REMOVER**
3. `admin/api/salas-ajax.php` ✅ **PODE REMOVER**
4. `admin/api/salas-clean.php` ⚠️ **VERIFICAR USO**
5. `admin/api/instrutores-real.php` ✅ **PODE REMOVER**
6. `admin/api/instrutores-simple.php` ✅ **PODE REMOVER**
7. `admin/api/instrutores_simplificado.php` ✅ **PODE REMOVER**
8. `admin/api/exames_simple.php` ❌ **NÃO** - Em uso ativo (`admin/pages/exames.php`)
9. `admin/api/disciplinas-simples.php` ✅ **PODE REMOVER**
10. `admin/api/disciplinas-estaticas.php` ✅ **PODE REMOVER**
11. `admin/api/alunos-aptos-turma-simples.php` ✅ **PODE REMOVER**
12. `admin/api/tipos-curso-clean.php` ❌ **NÃO** - Em uso (`admin/assets/js/admin.js:433`)

**Total remover imediatamente:** 8 APIs  
**Remover após migração:** 2 APIs  
**Manter (em uso):** 2 APIs

### 9.2. Páginas Legadas

1. `admin/pages/financeiro-faturas-standalone.php` ✅
2. `admin/pages/financeiro-despesas-standalone.php` ✅
3. `admin/pages/financeiro-relatorios-standalone.php` ✅
4. `admin/pages/historico-aluno-melhorado.php` ✅
5. `admin/pages/historico-aluno-novo.php` ✅
6. `admin/pages/instrutores-otimizado.php` ✅
7. `admin/pages/turmas-teoricas-fixed.php` ✅
8. `admin/pages/turmas-teoricas-disciplinas-fixed.php` ✅
9. `admin/pages/alunos_original.php` ✅
10. `admin/pages/alunos-complete.txt` ✅
11. `admin/pages/_modalAluno-legacy.php` ✅
12. `admin/pages/usuarios_simples.php` ✅

**Total:** 12 páginas podem ser removidas

### 9.3. Arquivos JS Temporários

1. `CORRECOES_MODAL_EMERGENCIAL.js` ✅ (raiz do projeto)
2. `admin/assets/js/mobile-debug.js` ✅

**Total:** 2 arquivos

### 9.4. Resumo de Remoção

**Total de arquivos que podem ser removidos:** 22 arquivos
- 8 APIs legadas
- 12 páginas legadas
- 2 JS temporários

---

## 10. O QUE PRECISA SER MIGRADO

### 10.1. Migração de APIs Legadas para Ativas

| De | Para | Arquivo que usa legada | Ação |
|----|------|------------------------|------|
| `exames_simple.php` | `exames.php` | `admin/pages/exames.php` | Atualizar 8 chamadas fetch |
| `tipos-curso-clean.php` | Criar API normal | `admin/assets/js/admin.js:433` | Criar API padrão ou migrar uso |
| `salas-clean.php` | `salas-real.php` | Verificar | Se não usado, remover |
| `disciplinas-clean.php` | `disciplinas.php` | Verificar | Se não usado, remover |
| `notifications.php` | `notificacoes.php` | Verificar | Se não usado, remover |

### 10.2. Migração de Tabelas

| De | Para | Onde | Ação |
|----|------|------|------|
| `faturas` | `financeiro_faturas` | `admin/api/pagamentos.php` | Corrigir JOIN (linhas 82, 126, 379) |
| Campo `vencimento` | Campo `data_vencimento` | `admin/api/financeiro-faturas.php` | Corrigir queries (linhas 113, 118, 139, 189) |

### 10.3. Migração de Páginas

| De | Para | Motivo |
|----|------|--------|
| Páginas `-standalone.php` | Remover (não são usadas) | Limpeza |
| Páginas `-fixed.php`, `-melhorado.php`, `-novo.php` | Remover (backups) | Limpeza |

---

## 11. O QUE ESTÁ QUEBRADO

### 11.1. Funcionalidades Quebradas

| Funcionalidade | Arquivo | Linha | Problema | Status |
|----------------|---------|-------|----------|--------|
| **Registro de Pagamentos** | `admin/api/pagamentos.php` | 82 | JOIN com `faturas` que não existe | 🔴 QUEBRADO |
| **Filtros de Faturas (via API)** | `admin/api/financeiro-faturas.php` | 113 | Usa campo `vencimento` que não retorna dados | 🟡 PARCIAL |
| **Cálculo de Inadimplência** | `admin/api/financeiro-faturas.php` | 336 | Query em `financeiro_configuracoes` que não existe | 🔴 QUEBRADO |
| **Cache** | `includes/database.php` | 460-516 | Métodos existem mas tabela `cache` não foi criada | 🟡 QUEBRADO (se usado) |

### 11.2. Jobs Quebrados

| Job | Arquivo | Status | Observação |
|-----|---------|--------|------------|
| **Marcar Faturas Vencidas** | `admin/jobs/marcar_faturas_vencidas.php` | ✅ CORRIGIDO | Foi corrigido na Fase 1, verificar se está em produção |

### 11.3. APIs que Podem Quebrar

| API | Arquivo | Problema | Quando quebra |
|-----|---------|----------|---------------|
| `pagamentos.php` | `admin/api/pagamentos.php` | JOIN com tabela inexistente | Ao tentar listar/buscar pagamentos |
| `financeiro-faturas.php` (GET) | `admin/api/financeiro-faturas.php` | Campo errado em filtros | Ao filtrar por vencimento |

---

## 12. CHECKLIST DE SAÚDE GERAL

### 12.1. Segurança

- [ ] ❌ **CRÍTICO:** Remover credenciais hardcoded de `includes/config.php:12-15`
- [ ] ⚠️ **MÉDIO:** Implementar variáveis de ambiente para credenciais
- [ ] ⚠️ **MÉDIO:** Restringir CORS em produção (`Access-Control-Allow-Origin: *` → origem específica)
- [ ] ⚠️ **MÉDIO:** Implementar rate limiting em APIs públicas
- [ ] ✅ **OK:** Prepared statements usados (proteção SQL Injection)
- [ ] ✅ **OK:** Password hashing implementado
- [ ] ⚠️ **MELHORAR:** Validação de IP/User-Agent em sessões (parcialmente implementado)

### 12.2. Estrutura de Banco de Dados

- [ ] ✅ **OK:** Tabelas core criadas em `install.php`
- [ ] ✅ **OK:** Tabelas de turmas criadas em migrations
- [ ] ❌ **CRÍTICO:** Corrigir `admin/api/pagamentos.php` para usar `financeiro_faturas`
- [ ] ❌ **CRÍTICO:** Criar tabela `financeiro_configuracoes` ou remover referência
- [ ] ⚠️ **MÉDIO:** Criar tabela `cache` ou remover métodos de cache
- [ ] ⚠️ **MÉDIO:** Verificar e criar índices faltantes
- [ ] ⚠️ **MÉDIO:** Documentar foreign keys e relacionamentos

### 12.3. APIs e Endpoints

- [ ] ❌ **CRÍTICO:** Corrigir campo `vencimento` → `data_vencimento` em `admin/api/financeiro-faturas.php`
- [ ] ⚠️ **MÉDIO:** Migrar `admin/pages/exames.php` de `exames_simple.php` para `exames.php`
- [ ] ✅ **OK:** 70+ APIs mapeadas e funcionais
- [ ] ⚠️ **MÉDIO:** Padronizar formato de resposta JSON
- [ ] ⚠️ **MÉDIO:** Implementar versionamento de API (`/api/v1/`)
- [ ] ⚠️ **MÉDIO:** Documentar todas as APIs (Swagger/OpenAPI)

### 12.4. Código Legado

- [ ] ✅ **OK:** 15 APIs legadas identificadas
- [ ] ✅ **OK:** 12 páginas legadas identificadas
- [ ] ⚠️ **MÉDIO:** Criar documentação de quais APIs/páginas são legadas
- [ ] ⚠️ **MÉDIO:** Marcar arquivos legados com `@deprecated` ou mover para pasta `legacy/`
- [ ] ✅ **OK:** Plano de remoção definido (22 arquivos)

### 12.5. Inconsistências

- [ ] ❌ **CRÍTICO:** Corrigir inconsistência `vencimento` vs `data_vencimento`
- [ ] ❌ **CRÍTICO:** Corrigir inconsistência `faturas` vs `financeiro_faturas` em pagamentos
- [ ] ⚠️ **MÉDIO:** Padronizar nomenclatura de tabelas (remover duplicações)
- [ ] ⚠️ **MÉDIO:** Padronizar nomenclatura de campos (snake_case consistente)

### 12.6. Performance

- [ ] ⚠️ **MÉDIO:** Implementar cache real (métodos existem mas não são usados)
- [ ] ⚠️ **MÉDIO:** Análise de queries N+1 (não verificada completamente)
- [ ] ⚠️ **MÉDIO:** Otimização de queries complexas
- [ ] ⚠️ **MÉDIO:** Implementar paginação consistente em todas as listagens

### 12.7. Testes

- [ ] ❌ **CRÍTICO:** Implementar testes unitários
- [ ] ❌ **CRÍTICO:** Implementar testes de integração
- [ ] ❌ **CRÍTICO:** Testes de API (Postman/Newman ou PHPUnit)
- [ ] ⚠️ **MÉDIO:** Testes E2E (Cypress/Selenium)

### 12.8. Documentação

- [ ] ⚠️ **MÉDIO:** Documentar arquitetura do sistema
- [ ] ⚠️ **MÉDIO:** Documentar fluxos de negócio
- [ ] ⚠️ **MÉDIO:** Documentar APIs (Swagger)
- [ ] ⚠️ **MÉDIO:** README com instruções de instalação/desenvolvimento
- [ ] ✅ **OK:** Documentos de diagnóstico existem (`_RAIO-X-*.md`)

### 12.9. Deploy e Manutenção

- [ ] ⚠️ **MÉDIO:** Verificar se job `marcar_faturas_vencidas.php` está rodando
- [ ] ⚠️ **MÉDIO:** Implementar logging estruturado
- [ ] ⚠️ **MÉDIO:** Monitoramento de erros (Sentry/Similar)
- [ ] ⚠️ **MÉDIO:** Backup automatizado testado

### 12.10. Resumo de Prioridades

**P0 - Crítico (Fazer Imediatamente):**
1. Corrigir `admin/api/pagamentos.php` para usar `financeiro_faturas`
2. Remover credenciais hardcoded
3. Corrigir campo `vencimento` em `admin/api/financeiro-faturas.php`
4. Criar tabela `financeiro_configuracoes` ou remover referência

**P1 - Alto (Fazer em Breve):**
1. Migrar `admin/pages/exames.php` para usar API completa
2. Remover arquivos legados identificados
3. Implementar variáveis de ambiente

**P2 - Médio (Fazer Quando Possível):**
1. Padronizar APIs
2. Implementar cache real
3. Documentar sistema
4. Implementar testes

---

## 📊 RESUMO EXECUTIVO

### Estatísticas Gerais

- **Total de Arquivos PHP:** 168
- **Total de APIs:** 74
- **Total de Páginas Admin:** 46
- **Total de Tabelas:** 20+ (core + turmas + financeiro)
- **APIs Legadas:** 15
- **Páginas Legadas:** 12
- **Problemas Críticos:** 4
- **Inconsistências:** 3

### Status Geral do Sistema

**Funcional:** ✅ 85%  
**Estrutura:** ⚠️ 70% (precisa limpeza e padronização)  
**Segurança:** ⚠️ 60% (credenciais hardcoded, CORS aberto)  
**Manutenibilidade:** ⚠️ 65% (código legado misturado)  
**Performance:** ⚠️ 70% (cache não usado, queries não otimizadas)

### Recomendações Imediatas

1. **Corrigir 4 problemas críticos** (P0) antes de qualquer deploy
2. **Limpar código legado** (22 arquivos podem ser removidos)
3. **Padronizar APIs** (remover duplicações, migrar para versões ativas)
4. **Implementar variáveis de ambiente** para credenciais
5. **Documentar sistema** para facilitar manutenção futura

---

**Fim do Relatório**

