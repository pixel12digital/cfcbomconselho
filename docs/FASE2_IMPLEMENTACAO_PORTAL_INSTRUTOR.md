# Fase 2 - Implementação Portal do Instrutor

## Data: 2024
## Status: ✅ CONCLUÍDA

---

## Resumo

Implementação da Fase 2 do plano de melhorias do portal do instrutor, focando em funcionalidades essenciais: ocorrências, contato com secretaria e central de avisos.

---

## 1. Página de Ocorrências

### ✅ Arquivo Criado: `instrutor/ocorrencias.php`

**Funcionalidades Implementadas:**

1. **Formulário de Registro:**
   - ✅ Tipo da ocorrência (select fixo):
     - Atraso do Aluno
     - Problema com Veículo
     - Infraestrutura
     - Comportamento do Aluno
     - Outro
   - ✅ Data da ocorrência (default = hoje)
   - ✅ Aula relacionada (opcional) - select das aulas recentes/futuras do instrutor
   - ✅ Descrição detalhada (textarea, mínimo 10 caracteres)

2. **Listagem de Ocorrências:**
   - ✅ Exibe todas as ocorrências registradas pelo instrutor
   - ✅ Colunas: Data, Tipo, Aula (se houver), Resumo da descrição
   - ✅ Ordenação: mais recente para mais antiga
   - ✅ Status visual (Aberta, Em Análise, Resolvida, Arquivada)
   - ✅ Exibe resolução se houver (preenchida pela secretaria/admin)

3. **Validações:**
   - ✅ Tipo obrigatório e válido
   - ✅ Data obrigatória e formato válido
   - ✅ Descrição obrigatória (mínimo 10 caracteres)
   - ✅ Validação de propriedade da aula (se fornecida)

4. **Layout:**
   - ✅ Grid de 2 colunas (formulário + listagem)
   - ✅ Cards com sombras e bordas arredondadas
   - ✅ Badges coloridos para tipo e status
   - ✅ Responsivo (mobile-first)

**Tabela Utilizada:**
- `ocorrencias_instrutor` (criada via script de migração)

---

## 2. Script de Migração - Ocorrências

### ✅ Arquivo Criado: `docs/scripts/migration_ocorrencias_instrutor.sql`

**Estrutura da Tabela:**

```sql
CREATE TABLE IF NOT EXISTS ocorrencias_instrutor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrutor_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('atraso_aluno', 'problema_veiculo', 'infraestrutura', 'comportamento_aluno', 'outro'),
    data_ocorrencia DATE NOT NULL,
    aula_id INT NULL,
    descricao TEXT NOT NULL,
    status ENUM('aberta', 'em_analise', 'resolvida', 'arquivada') DEFAULT 'aberta',
    resolucao TEXT NULL,
    resolvido_por INT NULL,
    resolvido_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ...
)
```

**Características:**
- ✅ Foreign keys para `instrutores`, `usuarios`, `aulas`
- ✅ Índices para performance
- ✅ Campos para resolução (preenchidos pela secretaria/admin)

---

## 3. Página de Contato com Secretaria

### ✅ Arquivo Criado: `instrutor/contato.php`

**Funcionalidades Implementadas:**

1. **Informações de Contato (Fixas):**
   - ✅ WhatsApp com link `https://wa.me/55{numero}`
   - ✅ E-mail com link `mailto:`
   - ✅ Telefone com link `tel:`
   - ✅ Horário de atendimento
   - ✅ Endereço
   - ✅ Ícones coloridos para cada tipo de contato

2. **Formulário de Mensagem:**
   - ✅ Assunto (obrigatório, mínimo 5 caracteres)
   - ✅ Aula relacionada (opcional) - select das aulas recentes/futuras
   - ✅ Mensagem (obrigatória, mínimo 10 caracteres)

3. **Persistência:**
   - ✅ Salva em tabela `contatos_instrutor`
   - ✅ Status inicial: 'aberto'
   - ✅ Não envia e-mail real (apenas salva no banco)
   - ✅ Validação de propriedade da aula (se fornecida)

4. **Layout:**
   - ✅ Grid de 2 colunas (informações + formulário)
   - ✅ Cards com sombras
   - ✅ Links clicáveis para WhatsApp, E-mail e Telefone
   - ✅ Responsivo (mobile-first)

**Tabela Utilizada:**
- `contatos_instrutor` (criada via script de migração)

**Fonte de Dados de Contato:**
- Extraído de `index.php` (linhas 4732-4740)
- WhatsApp: (87) 98145-0308
- E-mail: contato@cfcbomconselho.com.br
- Horário: Segunda a Sexta, 8h às 18h

---

## 4. Script de Migração - Contatos

### ✅ Arquivo Criado: `docs/scripts/migration_contatos_instrutor.sql`

**Estrutura da Tabela:**

```sql
CREATE TABLE IF NOT EXISTS contatos_instrutor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrutor_id INT NOT NULL,
    usuario_id INT NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    aula_id INT NULL,
    status ENUM('aberto', 'em_atendimento', 'respondido', 'fechado') DEFAULT 'aberto',
    resposta TEXT NULL,
    respondido_por INT NULL,
    respondido_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ...
)
```

**Características:**
- ✅ Foreign keys para `instrutores`, `usuarios`, `aulas`
- ✅ Índices para performance
- ✅ Campos para resposta (preenchidos pela secretaria/admin)

---

## 5. Central de Avisos / Notificações

### ✅ Arquivo Criado: `instrutor/notificacoes.php`

**Funcionalidades Implementadas:**

1. **Listagem de Notificações:**
   - ✅ Lista todas as notificações do instrutor (até 100 mais recentes)
   - ✅ Reutiliza a mesma fonte de dados do dashboard
   - ✅ Query: `SELECT * FROM notificacoes WHERE usuario_id = ? AND tipo_usuario = 'instrutor'`
   - ✅ Ordenação: mais recente para mais antiga

2. **Estatísticas:**
   - ✅ Total de notificações
   - ✅ Não lidas
   - ✅ Lidas

3. **Funcionalidades:**
   - ✅ **Visualizar detalhes**: Ao clicar na notificação, expande para mostrar mensagem completa e dados adicionais
   - ✅ **Marcar como lida**: Botão individual por notificação
   - ✅ **Marcar todas como lidas**: Botão de ação em massa
   - ✅ **Filtrar não lidas**: Mostra apenas notificações não lidas
   - ✅ **Mostrar todas**: Remove filtro

4. **Integração com API:**
   - ✅ Usa `admin/api/notificacoes.php` (POST para marcar como lida)
   - ✅ Usa `admin/api/notificacoes.php` (PUT para marcar todas como lidas)
   - ✅ Valida propriedade da notificação (API já faz isso)
   - ✅ Recarrega página após marcar para atualizar contadores

5. **Layout:**
   - ✅ Cards com destaque visual para não lidas (borda azul, fundo claro)
   - ✅ Badge de "não lida" (bolinha azul)
   - ✅ Expansão de detalhes ao clicar
   - ✅ Botões de ação por notificação
   - ✅ Responsivo (mobile-first)

**Observação:**
- A API atual (`admin/api/notificacoes.php`) não suporta marcar como "não lida"
- Apenas notificações não lidas têm botão de ação (marcar como lida)
- Notificações já lidas não têm botão (API não suporta desmarcar)

---

## 6. Arquivos Criados/Modificados

### Novos Arquivos:
1. ✅ `instrutor/ocorrencias.php` - Página de ocorrências
2. ✅ `instrutor/contato.php` - Página de contato com secretaria
3. ✅ `instrutor/notificacoes.php` - Central de avisos
4. ✅ `docs/scripts/migration_ocorrencias_instrutor.sql` - Script de migração para ocorrências
5. ✅ `docs/scripts/migration_contatos_instrutor.sql` - Script de migração para contatos
6. ✅ `docs/FASE2_IMPLEMENTACAO_PORTAL_INSTRUTOR.md` - Esta documentação

### Arquivos Modificados:
- Nenhum arquivo existente foi modificado (apenas novos arquivos criados)

---

## 7. Tabelas do Banco de Dados

### Novas Tabelas:

1. **`ocorrencias_instrutor`**
   - Registro de ocorrências reportadas por instrutores
   - Campos: tipo, data_ocorrencia, aula_id, descricao, status, resolucao
   - Foreign keys: instrutores, usuarios, aulas

2. **`contatos_instrutor`**
   - Mensagens de contato enviadas por instrutores para secretaria
   - Campos: assunto, mensagem, aula_id, status, resposta
   - Foreign keys: instrutores, usuarios, aulas

### Tabelas Reutilizadas:

1. **`notificacoes`**
   - Já existente no sistema
   - Campos: usuario_id, tipo_usuario, titulo, mensagem, lida, dados
   - Filtrada por `usuario_id` e `tipo_usuario = 'instrutor'`

---

## 8. Segurança Implementada

### ✅ Validações de Segurança:

1. **Todas as Páginas:**
   - ✅ Verificação de autenticação (`tipo === 'instrutor'`)
   - ✅ Verificação de `precisa_trocar_senha`
   - ✅ Busca `instrutor_id` da tabela `instrutores`

2. **Ocorrências:**
   - ✅ Validação de propriedade da aula (se fornecida)
   - ✅ Validação de tipo (enum fixo)
   - ✅ Validação de descrição (mínimo 10 caracteres)

3. **Contato:**
   - ✅ Validação de propriedade da aula (se fornecida)
   - ✅ Validação de assunto (mínimo 5 caracteres)
   - ✅ Validação de mensagem (mínimo 10 caracteres)

4. **Notificações:**
   - ✅ API valida propriedade da notificação
   - ✅ Filtro sempre por `usuario_id` e `tipo_usuario = 'instrutor'`

---

## 9. Integração com APIs Existentes

### ✅ APIs Reutilizadas:

1. **`admin/api/notificacoes.php`**
   - **GET**: Listar notificações (não usado diretamente, query direta no PHP)
   - **POST**: Marcar notificação como lida
   - **PUT**: Marcar todas as notificações como lidas
   - ✅ **Funcional para instrutores**: API valida propriedade

### ⚠️ Limitações Conhecidas:

1. **API de Notificações:**
   - Não suporta marcar como "não lida" (apenas marcar como lida)
   - Solução atual: Apenas notificações não lidas têm botão de ação

---

## 10. Layout e UX

### ✅ Padrões Mantidos:

1. **Cores:**
   - Azul primário: `#2563eb`
   - Verde sucesso: `#10b981`
   - Amarelo aviso: `#f59e0b`
   - Vermelho erro: `#ef4444`
   - Cinza neutro: `#64748b`

2. **Tipografia:**
   - Títulos: `font-size: 20px`, `font-weight: 600`
   - Subtítulos: `font-size: 14px`, `color: #64748b`
   - Corpo: `font-size: 14px`, `color: #1e293b`

3. **Componentes:**
   - Cards com `border-radius: 8px`, `box-shadow: 0 2px 4px rgba(0,0,0,0.1)`
   - Botões com `padding: 12px`, `border-radius: 6px`
   - Inputs com `padding: 10px`, `border: 1px solid #ddd`

4. **Responsividade:**
   - Grid adaptativo (`grid-template-columns: repeat(auto-fit, minmax(...))`)
   - Mobile-first (usa `mobile-first.css`)
   - Breakpoints via CSS Grid

---

## 11. Comentários no Código

### ✅ Padrão de Comentários:

Todas as alterações foram marcadas com:
```php
// FASE 2 - [Descrição da alteração]
// Arquivo: [arquivo] (linha ~[número])
```

**Exemplos:**
- `// FASE 2 - Verificação de autenticação (padrão do portal)`
- `// FASE 2 - Processar cadastro de ocorrência`
- `// FASE 2 - Buscar todas as notificações do instrutor`
- `// FASE 2 - Funções JavaScript para gerenciar notificações`

---

## 12. Checklist de Testes Recomendados

### ✅ Testes de Funcionalidade:

- [ ] **Ocorrências:**
  - [ ] Registrar ocorrência sem aula relacionada
  - [ ] Registrar ocorrência com aula relacionada
  - [ ] Validar que instrutor não pode selecionar aula de outro instrutor
  - [ ] Verificar listagem de ocorrências (ordenação correta)
  - [ ] Verificar que ocorrências aparecem apenas para o instrutor que registrou

- [ ] **Contato:**
  - [ ] Enviar mensagem sem aula relacionada
  - [ ] Enviar mensagem com aula relacionada
  - [ ] Validar que instrutor não pode selecionar aula de outro instrutor
  - [ ] Verificar links de WhatsApp, E-mail e Telefone (abrem corretamente)
  - [ ] Verificar que mensagens são salvas no banco

- [ ] **Notificações:**
  - [ ] Verificar listagem completa de notificações
  - [ ] Marcar notificação individual como lida
  - [ ] Marcar todas as notificações como lidas
  - [ ] Filtrar apenas não lidas
  - [ ] Expandir detalhes da notificação
  - [ ] Verificar que contador do dashboard atualiza após marcar como lida

### ✅ Testes de Segurança:

- [ ] Verificar que páginas redirecionam se não autenticado
- [ ] Verificar que páginas redirecionam se `precisa_trocar_senha = 1`
- [ ] Verificar que instrutor não pode acessar ocorrências de outro instrutor
- [ ] Verificar que instrutor não pode acessar contatos de outro instrutor
- [ ] Verificar que instrutor não pode acessar notificações de outro instrutor

### ✅ Testes de UX:

- [ ] Verificar layout responsivo em mobile
- [ ] Verificar feedback de sucesso/erro nas páginas
- [ ] Verificar validação frontend dos formulários
- [ ] Verificar que formulários não permitem reenvio acidental (redirect após POST)

---

## 13. Próximos Passos (Fase 3 - Opcional)

Conforme inventário original, ainda faltam:

1. **`instrutor/chamada.php`**
   - Chamada para aulas teóricas
   - Marcar presença/falta dos alunos

2. **`instrutor/diario.php`**
   - Diário de aula
   - Registrar conteúdo e observações

---

## 14. Observações Técnicas

### Compatibilidade:

- ✅ Mantém compatibilidade com código existente
- ✅ Não altera fluxo de login
- ✅ Não altera páginas da Fase 1
- ✅ Reutiliza estilos e componentes existentes

### Performance:

- ✅ Queries otimizadas com JOINs apropriados
- ✅ Filtros aplicados no banco
- ✅ Limite de resultados quando necessário (notificações: 100)

### Manutenibilidade:

- ✅ Código comentado indicando alterações da Fase 2
- ✅ Estrutura clara e organizada
- ✅ Separação de responsabilidades (PHP vs JavaScript)

### Criação Automática de Tabelas:

- ✅ As páginas tentam criar tabelas automaticamente se não existirem
- ✅ Scripts de migração disponíveis em `docs/scripts/`
- ✅ Recomendado executar scripts manualmente em produção

---

## 15. Resumo Executivo

### ✅ O que foi implementado:

1. **Página de Ocorrências** (`instrutor/ocorrencias.php`)
   - Formulário completo de registro
   - Listagem com status e resolução
   - Tabela `ocorrencias_instrutor` criada

2. **Página de Contato** (`instrutor/contato.php`)
   - Informações de contato da secretaria
   - Formulário de mensagem
   - Tabela `contatos_instrutor` criada

3. **Central de Avisos** (`instrutor/notificacoes.php`)
   - Listagem completa de notificações
   - Ações: marcar como lida, marcar todas, filtrar
   - Integração com API existente

### ✅ Scripts de Migração:

1. `docs/scripts/migration_ocorrencias_instrutor.sql`
2. `docs/scripts/migration_contatos_instrutor.sql`

### ✅ Documentação:

1. `docs/FASE2_IMPLEMENTACAO_PORTAL_INSTRUTOR.md` (esta documentação)

---

## 16. Correção – Vínculo de Instrutor nas Ocorrências

### Data: 2024
### Status: ✅ CORRIGIDO

---

### Problema Identificado

Ao tentar registrar uma ocorrência na página `instrutor/ocorrencias.php`, aparecia o erro:
> "Erro ao registrar ocorrência: Instrutor não encontrado. Verifique seu cadastro."

### Causa Raiz

A página `instrutor/ocorrencias.php` estava usando uma query diferente da Fase 1 para buscar o `instrutor_id`:
- **Fase 1 (funcionando)**: `SELECT id FROM instrutores WHERE usuario_id = ?`
- **Fase 2 (com problema)**: Query com LEFT JOIN que retornava `null` quando não havia registro na tabela `instrutores`, mas criava um array vazio em vez de retornar erro

### O que foi Corrigido

1. **Função Centralizada Criada:**
   - ✅ Criada função `getCurrentInstrutorId($userId)` em `includes/auth.php`
   - ✅ Usa o mesmo padrão da Fase 1: `SELECT id FROM instrutores WHERE usuario_id = ?`
   - ✅ Retorna `null` se não encontrar (não cria array vazio)

2. **API Criada:**
   - ✅ Criado `admin/api/ocorrencias-instrutor.php`
   - ✅ Usa `getCurrentInstrutorId()` para obter o instrutor_id
   - ✅ Validações de segurança:
     - Verifica autenticação
     - Verifica tipo de usuário = 'instrutor'
     - Valida que aula pertence ao instrutor (se fornecida)
     - Não aceita `instrutor_id` via POST (sempre obtém do login)

3. **Página Atualizada:**
   - ✅ `instrutor/ocorrencias.php` agora usa `getCurrentInstrutorId()`
   - ✅ Formulário envia via AJAX para a API (não mais POST direto)
   - ✅ Feedback visual melhorado (mensagens de sucesso/erro dinâmicas)
   - ✅ Listagem atualizada após registro bem-sucedido

4. **Alinhamento com Fase 1:**
   - ✅ `admin/api/instrutor-aulas.php` atualizado para usar `getCurrentInstrutorId()`
   - ✅ Mesma lógica de obtenção do instrutor_id em ambas as APIs

### Validações de Segurança Aplicadas

1. **Autenticação:**
   - ✅ Verifica `getCurrentUser()` - usuário deve estar autenticado

2. **Tipo de Usuário:**
   - ✅ Verifica `$user['tipo'] === 'instrutor'` - apenas instrutores podem usar

3. **Propriedade do Instrutor:**
   - ✅ `instrutor_id` sempre obtido do login (nunca via POST)
   - ✅ Query: `SELECT id FROM instrutores WHERE usuario_id = ?`

4. **Propriedade da Aula (se fornecida):**
   - ✅ Valida que `aulas.instrutor_id = instrutor_id_do_login`
   - ✅ Query: `SELECT id FROM aulas WHERE id = ? AND instrutor_id = ?`
   - ✅ Bloqueia tentativas de vincular ocorrência a aula de outro instrutor

5. **Logs de Auditoria:**
   - ✅ Log detalhado quando instrutor não é encontrado
   - ✅ Log de tentativas de acesso não autorizado
   - ✅ Log de sucesso ao registrar ocorrência

### Arquivos Modificados

1. ✅ `includes/auth.php` - Adicionada função `getCurrentInstrutorId()`
2. ✅ `admin/api/instrutor-aulas.php` - Atualizado para usar função centralizada
3. ✅ `admin/api/ocorrencias-instrutor.php` - **NOVO** - API para ocorrências
4. ✅ `instrutor/ocorrencias.php` - Atualizado para usar função centralizada e API

### Testes Recomendados

- [ ] Instrutor real logado registra ocorrência sem aula → deve salvar
- [ ] Instrutor real logado registra ocorrência com aula sua → deve salvar
- [ ] Tentativa de forçar `aula_id` de outro instrutor via DevTools → deve bloquear
- [ ] Instrutor sem vínculo na tabela `instrutores` → deve mostrar erro com log detalhado
- [ ] Mensagem de sucesso aparece após registro bem-sucedido
- [ ] Listagem é atualizada após registro (sem F5)

---

**Fim da Fase 2**

