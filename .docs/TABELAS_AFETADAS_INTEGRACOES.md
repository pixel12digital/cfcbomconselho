# Tabelas Afetadas nas Integrações - Validação de Arquitetura

## 🔍 Análise: Integrações sem criar "segundo progresso" paralelo

---

## 1️⃣ Integração na Matrícula

### Tabelas que serão MODIFICADAS:

#### `enrollments` (MODIFICAR - adicionar campos)
**Ação:** Adicionar 2 campos opcionais
```sql
ALTER TABLE enrollments 
ADD COLUMN theory_course_id INT(11) NULL COMMENT 'Template de curso teórico (opcional)',
ADD COLUMN theory_class_id INT(11) NULL COMMENT 'Turma teórica (opcional)',
ADD KEY theory_course_id (theory_course_id),
ADD KEY theory_class_id (theory_class_id),
ADD CONSTRAINT enrollments_ibfk_theory_course FOREIGN KEY (theory_course_id) REFERENCES theory_courses(id),
ADD CONSTRAINT enrollments_ibfk_theory_class FOREIGN KEY (theory_class_id) REFERENCES theory_classes(id);
```

**Justificativa:** 
- ✅ Não quebra estrutura existente (campos opcionais)
- ✅ Permite rastreabilidade: matrícula pode apontar para curso/turma
- ✅ Não cria "segundo progresso" - só adiciona vínculo

---

#### `theory_enrollments` (CRIAR/MODIFICAR)
**Ação:** Upsert idempotente
- **Se `theory_class_id` informado:** Criar registro em `theory_enrollments`
- **Idempotência:** UNIQUE KEY `class_student` já existe (evita duplicidade)
- **Estratégia:** `INSERT ... ON DUPLICATE KEY UPDATE` ou verificação antes

**Tabela já existe (criada na migration 025):**
- `theory_enrollments.class_id` → `theory_classes.id`
- `theory_enrollments.student_id` → `students.id`
- `theory_enrollments.enrollment_id` → `enrollments.id` (opcional)

**Justificativa:**
- ✅ Não cria progresso paralelo - `theory_enrollments` é apenas vínculo aluno-turma
- ✅ `enrollment_id` opcional permite rastreabilidade reversa
- ✅ Progresso continua em `student_steps` (não mexe aqui)

---

### Tabelas que serão LIDAS (consultas):

#### `theory_courses` (LEITURA)
- Validar se curso existe e está ativo

#### `theory_classes` (LEITURA)
- Validar se turma existe e está ativa
- Verificar se aluno já está matriculado (via `theory_enrollments`)

#### `theory_enrollments` (LEITURA)
- Verificar duplicidade antes de criar

---

### Fluxo de Matrícula Atual (não alterar):
```php
1. Criar enrollment (enrollments)
2. Buscar steps ativos (steps)
3. Criar student_steps para cada step (student_steps)
4. Registrar histórico (student_history)
5. Registrar auditoria (auditoria)
```

### Fluxo de Matrícula Novo (adicionar depois do passo 1):
```php
6. Se theory_class_id informado:
   - Validar turma existe e está ativa
   - Criar/upsert theory_enrollment (idempotente)
```

**✅ Não altera steps/student_steps** - integração é apenas vínculo adicional.

---

## 2️⃣ Integração no Progresso (steps/student_steps)

### Tabelas que serão MODIFICADAS:

#### `steps` (MODIFICAR - adicionar 1 registro)
**Ação:** INSERT novo step
```sql
INSERT INTO steps (code, name, description, `order`, is_active) 
VALUES ('CURSO_TEORICO', 'Curso Teórico', 'Curso teórico concluído', 4, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
```
**Order:** 4 (antes de PROVA_TEORICA que é 5)

**Justificativa:**
- ✅ Reutiliza tabela existente `steps` (não cria paralelo)
- ✅ Adiciona apenas 1 registro ao catálogo
- ✅ Será criado automaticamente em `student_steps` pelo fluxo normal

---

#### `student_steps` (MODIFICAR - UPDATE automático)
**Ação:** UPDATE status baseado em `theory_attendance`

**Quando atualizar:**
- Ao marcar presença em sessão (`TheoryAttendanceController::salvar()`)
- Ao criar/cancelar sessão (verificar se todas concluídas)
- Método: `updateTheoryStepStatus($enrollmentId, $classId)`

**Lógica de conclusão:**
```php
1. Buscar todas sessões da turma (theory_sessions WHERE class_id = X AND status = 'done')
2. Para cada sessão, verificar presença do aluno:
   - Se TODAS as sessões têm presença 'present' OU 'justified' → concluir step
   - Senão → manter 'pendente'
3. UPDATE student_steps SET status = 'concluida' WHERE enrollment_id = X AND step_id = CURSO_TEORICO
```

**Justificativa:**
- ✅ **Reutiliza `student_steps` existente** (não cria paralelo)
- ✅ Apenas atualiza status do step 'CURSO_TEORICO'
- ✅ Integração transparente: aluno vê "Curso Teórico" na timeline normal

---

### Tabelas que serão LIDAS (consultas):

#### `theory_sessions` (LEITURA)
- Buscar sessões da turma (`WHERE class_id = ? AND status = 'done'`)

#### `theory_attendance` (LEITURA)
- Buscar presenças do aluno nas sessões
- Verificar se todas são `present` ou `justified`

#### `theory_enrollments` (LEITURA)
- Buscar turmas do aluno para verificar progresso

#### `steps` (LEITURA)
- Buscar step 'CURSO_TEORICO' para obter `step_id`

#### `student_steps` (LEITURA)
- Buscar `student_step` correspondente para atualizar

---

### Fluxo de Criação de Student Steps (não alterar):
```php
// Já existe em AlunosController::criarMatricula()
foreach ($steps as $step) {
    $studentStepModel->create([
        'enrollment_id' => $enrollmentId,
        'step_id' => $step['id'],
        'status' => ($step['code'] === 'MATRICULA') ? 'concluida' : 'pendente'
    ]);
}
```

**✅ O step 'CURSO_TEORICO' será criado automaticamente** porque `Step::findAllActive()` retorna todos os steps ativos, incluindo o novo.

**✅ Não precisa alterar o fluxo de criação** - funciona automaticamente.

---

### Fluxo de Atualização do Status (novo - adicionar):

**Trigger:** Ao marcar presença em sessão
```php
TheoryAttendanceController::salvar() {
    // ... salvar presenças ...
    
    // Verificar conclusão do curso teórico
    $this->checkTheoryCourseCompletion($sessionId);
}

checkTheoryCourseCompletion($sessionId) {
    // 1. Buscar sessão e turma
    // 2. Buscar todas sessões da turma (status = 'done')
    // 3. Buscar theory_enrollments da turma
    // 4. Para cada enrollment:
    //    - Verificar se aluno tem presença 'present' ou 'justified' em TODAS as sessões
    //    - Se sim: UPDATE student_steps SET status = 'concluida' WHERE enrollment_id = X AND step_id = CURSO_TEORICO
}
```

**✅ Atualiza apenas o status** - não cria nova tabela de progresso.

---

## 📊 Resumo: Tabelas Afetadas

### ✅ Modificações (escrever dados)
1. **`enrollments`** - Adicionar 2 campos opcionais (FK)
2. **`theory_enrollments`** - Criar registro (upsert idempotente)
3. **`steps`** - Adicionar 1 registro (INSERT)
4. **`student_steps`** - UPDATE status (baseado em attendance)

### ✅ Leituras (consultas)
1. **`theory_courses`** - Validar curso
2. **`theory_classes`** - Validar turma
3. **`theory_sessions`** - Buscar sessões da turma
4. **`theory_attendance`** - Verificar presenças
5. **`steps`** - Buscar step CURSO_TEORICO

### ✅ Não serão tocadas (garantia)
- ❌ Não cria nova tabela de "progresso teórico"
- ❌ Não cria estrutura paralela a `student_steps`
- ❌ Não duplica lógica de progresso

---

## ✅ Validação de Consistência

### 1. Progresso permanece em `student_steps`
- ✅ Step 'CURSO_TEORICO' entra no catálogo normal
- ✅ Criado automaticamente em `student_steps` no fluxo normal
- ✅ Atualização de status segue mesmo padrão das outras etapas

### 2. Matrícula mantém estrutura existente
- ✅ Campos opcionais não quebram queries existentes
- ✅ `theory_enrollments` é apenas vínculo adicional
- ✅ Não interfere no fluxo de criação de `student_steps`

### 3. Integração transparente
- ✅ Aluno vê "Curso Teórico" na timeline normal
- ✅ Progresso calculado automaticamente via attendance
- ✅ Não precisa de "segunda view" de progresso

---

## 🎯 Conclusão

**✅ Arquitetura consistente:**
- Reutiliza `steps` e `student_steps` existentes
- Não cria progresso paralelo
- Integração transparente no fluxo normal
- Apenas adiciona vínculos opcionais em `enrollments`

**✅ Sem breaking changes:**
- Campos opcionais em `enrollments`
- Step novo no catálogo (comportamento normal)
- UPDATE em `student_steps` (mesmo padrão das outras etapas)

**✅ Idempotência garantida:**
- UNIQUE KEY em `theory_enrollments` (class_student)
- ON DUPLICATE KEY UPDATE ou verificação antes
- Transações nas operações críticas
