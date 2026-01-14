<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Enviar Comunicado</h1>
            <p class="text-muted">Crie e envie notificações para alunos e instrutores</p>
        </div>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-body">
        <form method="POST" action="<?= base_path('comunicados') ?>" id="broadcastForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <!-- Título -->
            <div class="form-group">
                <label class="form-label" for="title">Título *</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    class="form-input" 
                    required
                    placeholder="Ex: Aviso importante"
                    maxlength="255"
                >
            </div>

            <!-- Mensagem -->
            <div class="form-group">
                <label class="form-label" for="body">Mensagem *</label>
                <textarea 
                    id="body" 
                    name="body" 
                    class="form-input" 
                    rows="6"
                    required
                    placeholder="Digite a mensagem do comunicado..."
                ></textarea>
            </div>

            <!-- Público-alvo -->
            <div class="form-group">
                <label class="form-label" for="target">Público-alvo *</label>
                <select id="target" name="target" class="form-input" required>
                    <option value="">Selecione o público...</option>
                    <option value="ALL_STUDENTS">Todos os alunos</option>
                    <option value="ALL_INSTRUCTORS">Todos os instrutores</option>
                    <option value="ALL_USERS">Todos (alunos + instrutores)</option>
                    <option value="ONE_STUDENT">Um aluno específico</option>
                    <option value="ONE_INSTRUCTOR">Um instrutor específico</option>
                </select>
            </div>

            <!-- Destinatário específico (aluno) -->
            <div class="form-group" id="student-select-group" style="display: none;">
                <label class="form-label" for="target_id_student">Selecione o aluno *</label>
                <select id="target_id_student" class="form-input">
                    <option value="">Selecione um aluno...</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>">
                            <?= htmlspecialchars($student['full_name'] ?? $student['name']) ?> 
                            (<?= htmlspecialchars($student['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Destinatário específico (instrutor) -->
            <div class="form-group" id="instructor-select-group" style="display: none;">
                <label class="form-label" for="target_id_instructor">Selecione o instrutor *</label>
                <select id="target_id_instructor" class="form-input">
                    <option value="">Selecione um instrutor...</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?= $instructor['id'] ?>">
                            <?= htmlspecialchars($instructor['name']) ?> 
                            (<?= htmlspecialchars($instructor['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Campo hidden para garantir que apenas um target_id seja enviado -->
            <input type="hidden" id="final_target_id" name="target_id" value="">

            <!-- Link opcional -->
            <div class="form-group">
                <label class="form-label" for="link">Link (opcional)</label>
                <input 
                    type="text" 
                    id="link" 
                    name="link" 
                    class="form-input" 
                    placeholder="Ex: /agenda, /financeiro"
                >
                <small class="form-text">Rota interna do sistema para onde a notificação deve redirecionar</small>
            </div>

            <!-- Botões -->
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                <button type="button" class="btn btn-outline" onclick="showPreview()">
                    Visualizar
                </button>
                <button type="submit" class="btn btn-primary">
                    Enviar Comunicado
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Preview -->
<div id="previewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 500px; margin: 20px; max-height: 90vh; overflow-y: auto;">
        <div class="card-body">
            <h3 style="margin-top: 0;">Preview do Comunicado</h3>
            <div id="previewContent" style="margin-bottom: var(--spacing-md);">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closePreview()">
                    Fechar
                </button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">
                    Confirmar e Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar selects de destinatário específico
document.getElementById('target').addEventListener('change', function() {
    const target = this.value;
    const studentGroup = document.getElementById('student-select-group');
    const instructorGroup = document.getElementById('instructor-select-group');
    const studentSelect = document.getElementById('target_id_student');
    const instructorSelect = document.getElementById('target_id_instructor');
    const finalTargetId = document.getElementById('final_target_id');

    // Ocultar todos e limpar valores
    studentGroup.style.display = 'none';
    instructorGroup.style.display = 'none';
    studentSelect.removeAttribute('required');
    instructorSelect.removeAttribute('required');
    studentSelect.value = '';
    instructorSelect.value = '';
    finalTargetId.value = '';

    // Mostrar o apropriado
    if (target === 'ONE_STUDENT') {
        studentGroup.style.display = 'block';
        studentSelect.setAttribute('required', 'required');
    } else if (target === 'ONE_INSTRUCTOR') {
        instructorGroup.style.display = 'block';
        instructorSelect.setAttribute('required', 'required');
    }
});

// Sincronizar valores dos selects com o campo hidden antes do submit
document.getElementById('broadcastForm').addEventListener('submit', function(e) {
    const target = document.getElementById('target').value;
    const finalTargetId = document.getElementById('final_target_id');
    
    if (target === 'ONE_STUDENT') {
        finalTargetId.value = document.getElementById('target_id_student').value;
    } else if (target === 'ONE_INSTRUCTOR') {
        finalTargetId.value = document.getElementById('target_id_instructor').value;
    } else {
        finalTargetId.value = '';
    }
});

// Função para mostrar preview
function showPreview() {
    const title = document.getElementById('title').value.trim();
    const body = document.getElementById('body').value.trim();
    const target = document.getElementById('target').value;
    const link = document.getElementById('link').value.trim();

    if (!title || !body || !target) {
        alert('Preencha todos os campos obrigatórios antes de visualizar.');
        return;
    }

    // Determinar público-alvo
    let targetText = '';
    switch(target) {
        case 'ALL_STUDENTS':
            targetText = 'Todos os alunos';
            break;
        case 'ALL_INSTRUCTORS':
            targetText = 'Todos os instrutores';
            break;
        case 'ALL_USERS':
            targetText = 'Todos os usuários (alunos + instrutores)';
            break;
        case 'ONE_STUDENT':
            const studentSelect = document.getElementById('target_id_student');
            const selectedStudent = studentSelect.options[studentSelect.selectedIndex];
            targetText = 'Aluno: ' + selectedStudent.text;
            break;
        case 'ONE_INSTRUCTOR':
            const instructorSelect = document.getElementById('target_id_instructor');
            const selectedInstructor = instructorSelect.options[instructorSelect.selectedIndex];
            targetText = 'Instrutor: ' + selectedInstructor.text;
            break;
    }

    // Montar preview
    const previewContent = `
        <div style="margin-bottom: var(--spacing-md);">
            <strong>Público-alvo:</strong> ${targetText}
        </div>
        <div style="margin-bottom: var(--spacing-md);">
            <strong>Título:</strong><br>
            ${escapeHtml(title)}
        </div>
        <div style="margin-bottom: var(--spacing-md);">
            <strong>Mensagem:</strong><br>
            <div style="white-space: pre-wrap; background: var(--color-bg-light, #f5f5f5); padding: var(--spacing-md); border-radius: 4px; margin-top: var(--spacing-xs);">
                ${escapeHtml(body)}
            </div>
        </div>
        ${link ? `<div style="margin-bottom: var(--spacing-md);"><strong>Link:</strong> ${escapeHtml(link)}</div>` : ''}
    `;

    document.getElementById('previewContent').innerHTML = previewContent;
    document.getElementById('previewModal').style.display = 'flex';
}

function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
}

function submitForm() {
    document.getElementById('broadcastForm').submit();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fechar modal ao clicar fora
document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePreview();
    }
});
</script>

<style>
#previewModal .card {
    background: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    #previewModal .card {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
}
</style>
