# Script de População de Dados Mínimos para Homologação

**Objetivo:** Documentar como popular dados mínimos de teste no ambiente de homologação.

**⚠️ ATENÇÃO:** Este documento é apenas uma referência. A execução real dos scripts deve ser feita manualmente pelo desenvolvedor responsável.

---

## Dados Mínimos Necessários

Para validar os fluxos críticos em homologação, é necessário ter pelo menos:

1. **1 CFC** - CFC Bom Conselho (ID 36 - canônico)
2. **1 usuário admin** - Para acessar o painel administrativo
3. **1 instrutor** - Para testar o painel do instrutor
4. **1 aluno** - Com matrícula ativa
5. **1 turma teórica** - Com algumas aulas agendadas
6. **Alguns agendamentos de aula prática** - Para testar agenda

---

## Script SQL para Popular Dados Mínimos

**⚠️ IMPORTANTE:** Ajuste os valores conforme seu ambiente de homolog antes de executar.

```sql
-- =====================================================
-- POPULAR DADOS MÍNIMOS PARA HOMOLOGAÇÃO
-- =====================================================

-- 1. Criar CFC (se não existir)
INSERT INTO cfcs (id, nome, cnpj, razao_social, ativo, criado_em)
VALUES (36, 'CFC Bom Conselho', '00.000.000/0001-00', 'CFC Bom Conselho LTDA', 1, NOW())
ON DUPLICATE KEY UPDATE nome = 'CFC Bom Conselho';

-- 2. Criar usuário admin (senha: admin123 - usar password_hash() no PHP)
-- NOTA: Execute via PHP para gerar hash correto da senha
-- Exemplo: $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);

INSERT INTO usuarios (nome, email, senha, tipo, cfc_id, ativo, criado_em)
VALUES ('Admin Homolog', 'admin@homolog.cfc', '$2y$10$...', 'admin', 0, 1, NOW())
ON DUPLICATE KEY UPDATE nome = 'Admin Homolog';

-- 3. Criar instrutor
-- Primeiro criar usuário do instrutor
INSERT INTO usuarios (nome, email, senha, tipo, cfc_id, ativo, criado_em)
VALUES ('Instrutor Teste', 'instrutor@homolog.cfc', '$2y$10$...', 'instrutor', 36, 1, NOW())
ON DUPLICATE KEY UPDATE nome = 'Instrutor Teste';

-- Depois criar registro em instrutores (assumindo que usuario_id do instrutor acima é X)
-- Ajustar usuario_id conforme resultado do INSERT acima
INSERT INTO instrutores (usuario_id, cfc_id, credencial, categoria_habilitacao, ativo, criado_em)
VALUES (LAST_INSERT_ID(), 36, 'INST001', 'B,C,D', 1, NOW())
ON DUPLICATE KEY UPDATE credencial = 'INST001';

-- 4. Criar aluno
INSERT INTO alunos (nome, cpf, cfc_id, categoria_cnh, status, criado_em)
VALUES ('Aluno Teste', '123.456.789-00', 36, 'B', 'ativo', NOW())
ON DUPLICATE KEY UPDATE nome = 'Aluno Teste';

-- 5. Criar matrícula do aluno
-- Ajustar aluno_id conforme resultado do INSERT acima
INSERT INTO matriculas (aluno_id, categoria_cnh, tipo_servico, status, data_inicio, status_financeiro, criado_em)
VALUES (LAST_INSERT_ID(), 'B', 'primeira_habilitacao', 'ativa', CURDATE(), 'regular', NOW())
ON DUPLICATE KEY UPDATE status = 'ativa';

-- 6. Criar turma teórica (requer salas existentes)
-- Primeiro verificar se há salas, se não, criar uma
INSERT INTO salas (nome, capacidade, cfc_id, ativa, criado_em)
VALUES ('Sala 1', 30, 36, 1, NOW())
ON DUPLICATE KEY UPDATE nome = 'Sala 1';

-- Criar turma teórica
INSERT INTO turmas_teoricas (nome, sala_id, curso_tipo, modalidade, data_inicio, data_fim, status, cfc_id, criado_por, max_alunos, criado_em)
VALUES ('Turma Teste Homolog', LAST_INSERT_ID(), 'formacao_45h', 'presencial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'ativa', 36, 1, 30, NOW())
ON DUPLICATE KEY UPDATE nome = 'Turma Teste Homolog';

-- 7. Matricular aluno na turma
-- Ajustar turma_id conforme resultado do INSERT acima
INSERT INTO turma_matriculas (turma_id, aluno_id, status, data_matricula)
VALUES (LAST_INSERT_ID(), (SELECT id FROM alunos WHERE cpf = '123.456.789-00' LIMIT 1), 'matriculado', NOW())
ON DUPLICATE KEY UPDATE status = 'matriculado';

-- 8. Criar algumas aulas práticas agendadas
-- Ajustar instrutor_id e aluno_id conforme necessário
INSERT INTO aulas (aluno_id, instrutor_id, cfc_id, tipo_aula, data_aula, hora_inicio, hora_fim, status, criado_em)
VALUES 
    ((SELECT id FROM alunos WHERE cpf = '123.456.789-00' LIMIT 1), 
     (SELECT id FROM instrutores WHERE credencial = 'INST001' LIMIT 1), 
     36, 'pratica', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '08:50:00', 'agendada', NOW()),
    ((SELECT id FROM alunos WHERE cpf = '123.456.789-00' LIMIT 1), 
     (SELECT id FROM instrutores WHERE credencial = 'INST001' LIMIT 1), 
     36, 'pratica', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', '09:50:00', 'agendada', NOW());
```

---

## Como Executar

### Opção 1: Via PHP (Recomendado para criar usuários com hash de senha)

1. Criar script temporário `tools/seed_homolog.php`:
   ```php
   <?php
   require_once '../includes/config.php';
   require_once '../includes/database.php';
   
   // Gerar hash de senha
   $senha_admin = password_hash('admin123', PASSWORD_DEFAULT);
   $senha_instrutor = password_hash('instrutor123', PASSWORD_DEFAULT);
   
   // Executar inserts...
   // (adaptar SQL acima para PHP)
   ```

2. Executar via navegador ou linha de comando.

### Opção 2: Via MySQL diretamente

1. Conectar ao banco de homolog:
   ```bash
   mysql -u usuario_homolog -p nome_banco_homolog
   ```

2. Executar os comandos SQL acima (ajustando valores conforme necessário).

3. Para criar usuários, gerar hash de senha via PHP primeiro.

---

## Credenciais de Teste Sugeridas

**Admin:**
- Email: `admin@homolog.cfc`
- Senha: `admin123` (alterar após primeiro acesso)

**Instrutor:**
- Email: `instrutor@homolog.cfc`
- Senha: `instrutor123` (alterar após primeiro acesso)

**⚠️ IMPORTANTE:** 
- Estas credenciais são apenas para homologação.
- Nunca usar estas senhas em produção.
- Alterar senhas após primeiro acesso.

---

## Próximos Passos

Após popular os dados mínimos:

1. Verificar login admin funciona
2. Verificar login instrutor funciona
3. Executar validação dos fluxos críticos (Tarefa 0.4)
4. Documentar resultados

---

**Documento criado na Fase 0 - Tarefa 0.3**  
**Última atualização:** 2025-12-12

