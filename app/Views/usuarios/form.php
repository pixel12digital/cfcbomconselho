<?php
$isEdit = isset($user) && !empty($user['id']);
$pageTitle = $isEdit ? 'Editar Usuário' : 'Criar Acesso';
?>

<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1><?= $pageTitle ?></h1>
            <p class="text-muted"><?= $isEdit ? 'Editar informações do usuário' : 'Criar novo acesso ao sistema' ?></p>
        </div>
        <a href="<?= base_path('usuarios') ?>" class="btn btn-outline">Voltar</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path($isEdit ? "usuarios/{$user['id']}/atualizar" : 'usuarios/criar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <?php if (!$isEdit): ?>
            <!-- Tipo de vínculo (apenas na criação) -->
            <div class="form-group">
                <label class="form-label" for="link_type">Tipo de Acesso</label>
                <select name="link_type" id="link_type" class="form-input" required>
                    <option value="none">Usuário Administrativo</option>
                    <option value="student">Vincular a Aluno Existente</option>
                    <option value="instructor">Vincular a Instrutor Existente</option>
                </select>
            </div>

            <!-- Seleção de Aluno -->
            <div class="form-group" id="student-select" style="display: none;">
                <label class="form-label" for="student_id">Aluno</label>
                <select name="link_id" id="student_id" class="form-input">
                    <option value="">Selecione um aluno...</option>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>">
                            <?= htmlspecialchars($student['full_name'] ?: $student['name']) ?> 
                            (<?= htmlspecialchars($student['cpf']) ?>)
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Nenhum aluno disponível</option>
                    <?php endif; ?>
                </select>
                <small class="form-text">
                    <?php if (empty($students)): ?>
                        <span style="color: #dc3545;">⚠️ Nenhum aluno sem acesso encontrado. Todos os alunos já possuem acesso vinculado ou não possuem email cadastrado.</span>
                    <?php else: ?>
                        Apenas alunos sem acesso vinculado aparecem aqui. (<?= count($students) ?> disponível(is))
                    <?php endif; ?>
                </small>
            </div>

            <!-- Seleção de Instrutor -->
            <div class="form-group" id="instructor-select" style="display: none;">
                <label class="form-label" for="instructor_id">Instrutor</label>
                <select name="link_id" id="instructor_id" class="form-input">
                    <option value="">Selecione um instrutor...</option>
                    <?php if (!empty($instructors)): ?>
                        <?php foreach ($instructors as $instructor): ?>
                        <option value="<?= $instructor['id'] ?>">
                            <?= htmlspecialchars($instructor['name']) ?> 
                            (<?= htmlspecialchars($instructor['cpf']) ?>)
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Nenhum instrutor disponível</option>
                    <?php endif; ?>
                </select>
                <small class="form-text">
                    <?php if (empty($instructors)): ?>
                        <span style="color: #dc3545;">⚠️ Nenhum instrutor sem acesso encontrado. Todos os instrutores já possuem acesso vinculado ou não possuem email cadastrado.</span>
                    <?php else: ?>
                        Apenas instrutores sem acesso vinculado aparecem aqui. (<?= count($instructors) ?> disponível(is))
                    <?php endif; ?>
                </small>
            </div>

            <!-- Nome (apenas para administrativo) -->
            <div class="form-group" id="nome-input">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" name="nome" id="nome" class="form-input" placeholder="Nome completo">
                <small class="form-text">Obrigatório apenas para usuários administrativos.</small>
            </div>
            <?php endif; ?>

            <!-- E-mail -->
            <div class="form-group">
                <label class="form-label" for="email">E-mail <span class="text-danger">*</span></label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="form-input" 
                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                    required
                    placeholder="usuario@exemplo.com"
                >
            </div>

            <!-- Perfil -->
            <div class="form-group">
                <label class="form-label" for="role">Perfil <span class="text-danger">*</span></label>
                <select name="role" id="role" class="form-input" required>
                    <option value="">Selecione...</option>
                    <option value="ADMIN" <?= ($user['roles'][0]['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>Administrador</option>
                    <option value="SECRETARIA" <?= ($user['roles'][0]['role'] ?? '') === 'SECRETARIA' ? 'selected' : '' ?>>Secretaria</option>
                    <option value="INSTRUTOR" <?= ($user['roles'][0]['role'] ?? '') === 'INSTRUTOR' ? 'selected' : '' ?>>Instrutor</option>
                    <option value="ALUNO" <?= ($user['roles'][0]['role'] ?? '') === 'ALUNO' ? 'selected' : '' ?>>Aluno</option>
                </select>
            </div>

            <?php if ($isEdit): ?>
            <!-- Status (apenas na edição) -->
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="ativo" <?= ($user['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= ($user['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>

            <!-- Bloco: Acesso e Segurança -->
            <div class="card" style="margin-top: var(--spacing-lg); border-left: 4px solid #007bff;">
                <div class="card-header" style="background-color: #f8f9fa;">
                    <h3 style="margin: 0; font-size: var(--font-size-lg); color: #007bff; display: flex; align-items: center; gap: 8px;">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Acesso e Segurança
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Status de Acesso -->
                    <div style="margin-bottom: var(--spacing-md); padding: var(--spacing-md); background-color: #f8f9fa; border-radius: 4px;">
                        <h4 style="margin: 0 0 var(--spacing-sm) 0; font-size: var(--font-size-md);">Status de Acesso</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-sm);">
                            <div>
                                <strong>Senha definida:</strong> 
                                <span class="badge <?= $hasPassword ? 'badge-success' : 'badge-danger' ?>">
                                    <?= $hasPassword ? 'Sim' : 'Não' ?>
                                </span>
                            </div>
                            <div>
                                <strong>Troca obrigatória:</strong> 
                                <span class="badge <?= !empty($user['must_change_password']) ? 'badge-warning' : 'badge-success' ?>">
                                    <?= !empty($user['must_change_password']) ? 'Sim' : 'Não' ?>
                                </span>
                            </div>
                            <div>
                                <strong>Link de ativação ativo:</strong> 
                                <span class="badge <?= $hasActiveToken ? 'badge-success' : 'badge-secondary' ?>">
                                    <?= $hasActiveToken ? 'Sim' : 'Não' ?>
                                </span>
                                <?php if ($hasActiveToken && $activeToken): ?>
                                    <small style="display: block; color: #666; margin-top: 4px;">
                                        Expira em: <?= date('d/m/Y H:i', strtotime($activeToken['expires_at'])) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Senha Temporária Gerada (exibir apenas uma vez) -->
                    <?php if (!empty($tempPasswordGenerated) && $tempPasswordGenerated['user_id'] == $user['id']): ?>
                    <div class="alert alert-success" style="margin-bottom: var(--spacing-md);">
                        <h4 style="margin: 0 0 var(--spacing-sm) 0; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Senha Temporária Gerada
                        </h4>
                        <p style="margin: 0 0 var(--spacing-sm) 0;">
                            <strong>E-mail:</strong> <?= htmlspecialchars($tempPasswordGenerated['user_email']) ?><br>
                            <strong>Senha temporária:</strong> 
                            <code style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 14px;" id="temp-password">
                                <?= htmlspecialchars($tempPasswordGenerated['temp_password']) ?>
                            </code>
                            <button type="button" onclick="copyToClipboard('temp-password')" class="btn btn-sm btn-outline" style="margin-left: 8px; display: inline-flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copiar
                            </button>
                        </p>
                        <small style="color: #666; display: flex; align-items: center; gap: 4px;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Esta senha será exibida apenas uma vez. Salve-a em local seguro.
                        </small>
                    </div>
                    <?php endif; ?>

                    <!-- Link de Ativação Gerado (exibir apenas uma vez) -->
                    <?php if (!empty($activationLinkGenerated) && $activationLinkGenerated['user_id'] == $user['id']): ?>
                    <div class="alert alert-info" style="margin-bottom: var(--spacing-md);">
                        <h4 style="margin: 0 0 var(--spacing-sm) 0; display: flex; align-items: center; gap: 8px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Link de Ativação Gerado
                        </h4>
                        <p style="margin: 0 0 var(--spacing-sm) 0;">
                            <strong>Link:</strong><br>
                            <code style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; word-break: break-all; display: block; margin-top: 4px;" id="activation-link">
                                <?= htmlspecialchars($activationLinkGenerated['activation_url']) ?>
                            </code>
                            <button type="button" onclick="copyToClipboard('activation-link')" class="btn btn-sm btn-outline" style="margin-top: 8px; display: inline-flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copiar Link
                            </button>
                        </p>
                        <small style="color: #666;">
                            Expira em: <?= date('d/m/Y H:i', strtotime($activationLinkGenerated['expires_at'])) ?>
                        </small>
                    </div>
                    <?php endif; ?>

                    <!-- Ações -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-sm);">
                        <!-- Gerar Senha Temporária -->
                        <form method="POST" action="<?= base_path("usuarios/{$user['id']}/gerar-senha-temporaria") ?>" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Gerar Senha Temporária
                            </button>
                        </form>

                        <!-- Gerar Link de Ativação -->
                        <form method="POST" action="<?= base_path("usuarios/{$user['id']}/gerar-link-ativacao") ?>" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-primary" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Gerar Link de Ativação
                            </button>
                        </form>

                        <!-- Enviar Link por E-mail -->
                        <?php if ($hasActiveToken): ?>
                        <form method="POST" action="<?= base_path("usuarios/{$user['id']}/enviar-link-email") ?>" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-success" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Enviar Link por E-mail
                            </button>
                        </form>
                        <?php else: ?>
                        <button type="button" class="btn btn-outline" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 6px;" disabled>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0; opacity: 0.5;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Gere um link primeiro
                        </button>
                        <?php endif; ?>
                    </div>

                    <small style="display: flex; align-items: flex-start; margin-top: var(--spacing-sm); color: #666; gap: 6px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 2px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <span><strong>Dica:</strong> Use "Gerar Senha Temporária" para testes rápidos. Use "Gerar Link de Ativação" para permitir que o usuário defina sua própria senha.</span>
                    </small>
                </div>
            </div>
            <?php else: ?>
            <!-- Enviar e-mail (apenas na criação) -->
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="send_email" value="1" checked>
                    <span>Enviar e-mail com credenciais de acesso</span>
                </label>
                <small class="form-text">Se marcado, um e-mail será enviado com a senha temporária.</small>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Salvar Alterações' : 'Criar Acesso' ?>
                </button>
                <a href="<?= base_path('usuarios') ?>" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const linkType = document.getElementById('link_type');
    const studentSelect = document.getElementById('student-select');
    const instructorSelect = document.getElementById('instructor-select');
    const nomeInput = document.getElementById('nome-input');
    const studentIdSelect = document.getElementById('student_id');
    const instructorIdSelect = document.getElementById('instructor_id');
    
    // Função para atualizar visibilidade dos campos
    function updateFieldsVisibility(value) {
        if (!value && linkType) {
            value = linkType.value;
        }
        if (!value) {
            value = 'none';
        }
        
        console.log('[USUARIOS_FORM] Atualizando visibilidade para:', value);
        
        if (studentSelect) {
            const shouldShow = value === 'student';
            studentSelect.style.display = shouldShow ? 'block' : 'none';
            console.log('[USUARIOS_FORM] Student select:', {
                display: studentSelect.style.display,
                shouldShow: shouldShow,
                optionsCount: studentIdSelect ? studentIdSelect.options.length : 0
            });
        } else {
            console.warn('[USUARIOS_FORM] student-select não encontrado!');
        }
        
        if (instructorSelect) {
            const shouldShow = value === 'instructor';
            instructorSelect.style.display = shouldShow ? 'block' : 'none';
            console.log('[USUARIOS_FORM] Instructor select:', {
                display: instructorSelect.style.display,
                shouldShow: shouldShow,
                optionsCount: instructorIdSelect ? instructorIdSelect.options.length : 0
            });
        } else {
            console.warn('[USUARIOS_FORM] instructor-select não encontrado!');
        }
        
        if (nomeInput) {
            nomeInput.style.display = value === 'none' ? 'block' : 'none';
        }
        
        // Limpar seleções quando mudar
        if (studentIdSelect && value !== 'student') {
            studentIdSelect.value = '';
        }
        if (instructorIdSelect && value !== 'instructor') {
            instructorIdSelect.value = '';
        }
    }
    
    // Inicializar estado ao carregar
    if (linkType) {
        console.log('[USUARIOS_FORM] Link type inicial:', linkType.value);
        console.log('[USUARIOS_FORM] Alunos disponíveis:', studentIdSelect ? studentIdSelect.options.length : 0);
        console.log('[USUARIOS_FORM] Instrutores disponíveis:', instructorIdSelect ? instructorIdSelect.options.length : 0);
        
        // Atualizar visibilidade inicial
        updateFieldsVisibility(linkType.value);
        
        // Adicionar listener para mudanças
        linkType.addEventListener('change', function() {
            updateFieldsVisibility(this.value);
        });
    } else {
        console.warn('[USUARIOS_FORM] Campo link_type não encontrado!');
    }
});

// Função para copiar ao clipboard
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent.trim();
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copiado para a área de transferência!');
        }).catch(function(err) {
            console.error('Erro ao copiar:', err);
            fallbackCopyTextToClipboard(text);
        });
    } else {
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            alert('Copiado para a área de transferência!');
        } else {
            alert('Erro ao copiar. Selecione o texto manualmente.');
        }
    } catch (err) {
        alert('Erro ao copiar. Selecione o texto manualmente.');
    }
    document.body.removeChild(textArea);
}
</script>
