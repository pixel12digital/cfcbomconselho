<?php
/**
 * Step 4: Sistema de Inserção de Alunos
 * Matrícula de alunos com validação de exames
 */

if (!$turmaAtual) {
    echo '<div class="alert alert-danger">❌ Turma não encontrada.</div>';
    return;
}

// Verificar se turma está pronta para receber alunos
if ($turmaAtual['status'] !== 'completa' && $turmaAtual['status'] !== 'ativa') {
    echo '<div class="alert alert-danger">❌ A turma deve estar completa antes de matricular alunos.</div>';
    return;
}

// Processar matrícula de aluno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['acao'] === 'matricular_aluno') {
    $alunoId = $_POST['aluno_id'] ?? null;
    
    if ($alunoId) {
        $resultado = $turmaManager->matricularAluno($turmaAtual['id'], $alunoId);
        
        if ($resultado['sucesso']) {
            $sucesso = $resultado['mensagem'];
            // Recarregar dados da turma
            $turmaAtual = $turmaManager->obterTurma($turmaAtual['id']);
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}

// Buscar alunos matriculados
try {
    $alunosMatriculados = $db->fetchAll("
        SELECT 
            tm.*,
            a.nome as aluno_nome,
            a.cpf,
            a.email,
            a.telefone,
            a.categoria_cnh,
            a.exame_medico,
            a.exame_psicologico
        FROM turma_matriculas tm
        LEFT JOIN alunos a ON tm.aluno_id = a.id
        WHERE tm.turma_id = ?
        ORDER BY tm.data_matricula DESC
    ", [$turmaAtual['id']]);
} catch (Exception $e) {
    $alunosMatriculados = [];
}

// Buscar alunos elegíveis (com exames aprovados e não matriculados)
try {
    $alunosElegiveis = $db->fetchAll("
        SELECT 
            a.*,
            CASE 
                WHEN (a.exame_medico IN ('apto', 'aprovado') AND a.exame_psicologico IN ('apto', 'aprovado')) 
                THEN 'elegivel'
                ELSE 'pendente'
            END as status_elegibilidade
        FROM alunos a
        WHERE a.status = 'ativo' 
        AND a.cfc_id = ?
        AND a.id NOT IN (
            SELECT tm.aluno_id 
            FROM turma_matriculas tm 
            WHERE tm.turma_id = ?
        )
        ORDER BY 
            CASE WHEN (a.exame_medico IN ('apto', 'aprovado') AND a.exame_psicologico IN ('apto', 'aprovado')) 
                 THEN 1 ELSE 2 END,
            a.nome
    ", [$turmaAtual['cfc_id'], $turmaAtual['id']]);
} catch (Exception $e) {
    $alunosElegiveis = [];
}

// Separar elegíveis dos pendentes
$elegiveisAprovados = array_filter($alunosElegiveis, fn($a) => $a['status_elegibilidade'] === 'elegivel');
$elegiveisComPendencias = array_filter($alunosElegiveis, fn($a) => $a['status_elegibilidade'] === 'pendente');

// Calcular estatísticas
$vagasDisponiveis = $turmaAtual['max_alunos'] - $turmaAtual['alunos_matriculados'];
$percentualOcupacao = $turmaAtual['max_alunos'] > 0 ? round(($turmaAtual['alunos_matriculados'] / $turmaAtual['max_alunos']) * 100, 1) : 0;
?>

<div style="display: flex; gap: 30px;">
    <!-- Coluna da esquerda: Alunos elegíveis -->
    <div style="flex: 1;">
        <div class="form-section">
            <h4>📚 Dados da Turma</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.9rem;">
                    <div><strong>Nome:</strong> <?= htmlspecialchars($turmaAtual['nome']) ?></div>
                    <div><strong>Sala:</strong> <?= htmlspecialchars($turmaAtual['sala_nome']) ?></div>
                    <div><strong>Curso:</strong> <?= htmlspecialchars($turmaAtual['curso_nome']) ?></div>
                    <div><strong>Vagas:</strong> <?= $turmaAtual['alunos_matriculados'] ?>/<?= $turmaAtual['max_alunos'] ?> (<?= $vagasDisponiveis ?> disponíveis)</div>
                </div>
            </div>
        </div>

        <!-- Status de vagas -->
        <div class="form-section">
            <h4>👥 Controle de Vagas</h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #023A8D, #1a4fa0); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= $turmaAtual['alunos_matriculados'] ?></div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Matriculados</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= $vagasDisponiveis ?></div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Vagas Livres</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #F7931E, #e8840d); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= count($elegiveisAprovados) ?></div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Elegíveis</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= $percentualOcupacao ?>%</div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Ocupação</div>
                </div>
            </div>
            
            <!-- Barra de ocupação visual -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 600;">Ocupação da Turma</span>
                    <span style="font-size: 0.9rem; color: #666;"><?= $turmaAtual['alunos_matriculados'] ?> de <?= $turmaAtual['max_alunos'] ?> vagas</span>
                </div>
                <div style="width: 100%; height: 12px; background: #e9ecef; border-radius: 6px; overflow: hidden;">
                    <div style="width: <?= $percentualOcupacao ?>%; height: 100%; background: linear-gradient(90deg, #023A8D, #F7931E); transition: width 0.3s ease;"></div>
                </div>
            </div>
        </div>

        <!-- Alunos elegíveis -->
        <div class="form-section">
            <h4>✅ Alunos Elegíveis (Exames Aprovados)</h4>
            
            <?php if ($vagasDisponiveis <= 0): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; text-align: center;">
                    <strong>🚫 Turma Lotada</strong><br>
                    Não há vagas disponíveis para novas matrículas.
                </div>
            <?php elseif (empty($elegiveisAprovados)): ?>
                <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 10px;">🎓</div>
                    <strong>Nenhum aluno elegível no momento</strong><br>
                    <small>Todos os alunos com exames aprovados já estão matriculados ou não há alunos com exames completos.</small>
                </div>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px;">
                    <?php foreach ($elegiveisAprovados as $aluno): ?>
                        <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <div style="font-weight: bold; color: #333; margin-bottom: 5px;">
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </div>
                                <div style="font-size: 0.9rem; color: #666; display: flex; gap: 15px; flex-wrap: wrap;">
                                    <span>📱 <?= htmlspecialchars($aluno['telefone'] ?? 'Não informado') ?></span>
                                    <span>📧 <?= htmlspecialchars($aluno['email'] ?? 'Não informado') ?></span>
                                    <span>🆔 CNH <?= htmlspecialchars($aluno['categoria_cnh']) ?></span>
                                </div>
                                <div style="font-size: 0.8rem; color: #28a745; margin-top: 5px;">
                                    ✅ Exame médico: <?= ucfirst($aluno['exame_medico']) ?> | 
                                    ✅ Exame psicológico: <?= ucfirst($aluno['exame_psicologico']) ?>
                                </div>
                            </div>
                            
                            <form method="POST" style="margin-left: 15px;">
                                <input type="hidden" name="acao" value="matricular_aluno">
                                <input type="hidden" name="aluno_id" value="<?= $aluno['id'] ?>">
                                <button type="submit" 
                                        class="btn-primary" 
                                        style="padding: 8px 16px; font-size: 0.9rem;"
                                        onclick="return confirm('Confirma a matrícula de <?= htmlspecialchars($aluno['nome']) ?> nesta turma?')">
                                    ➕ Matricular
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Alunos com pendências -->
        <?php if (!empty($elegiveisComPendencias)): ?>
            <div class="form-section">
                <h4>⚠️ Alunos com Pendências nos Exames</h4>
                
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px;">
                    <?php foreach ($elegiveisComPendencias as $aluno): ?>
                        <div style="padding: 15px; border-bottom: 1px solid #eee; opacity: 0.7;">
                            <div style="font-weight: bold; color: #333; margin-bottom: 5px;">
                                <?= htmlspecialchars($aluno['nome']) ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 5px;">
                                📱 <?= htmlspecialchars($aluno['telefone'] ?? 'Não informado') ?> | 
                                🆔 CNH <?= htmlspecialchars($aluno['categoria_cnh']) ?>
                            </div>
                            <div style="font-size: 0.8rem; color: #dc3545;">
                                <?php if ($aluno['exame_medico'] !== 'apto' && $aluno['exame_medico'] !== 'aprovado'): ?>
                                    ❌ Exame médico: <?= $aluno['exame_medico'] ?: 'Não realizado' ?>
                                <?php endif; ?>
                                <?php if ($aluno['exame_psicologico'] !== 'apto' && $aluno['exame_psicologico'] !== 'aprovado'): ?>
                                    | ❌ Exame psicológico: <?= $aluno['exame_psicologico'] ?: 'Não realizado' ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 0.9rem;">
                    <strong>💡 Dica:</strong> Estes alunos só poderão ser matriculados após aprovação nos exames médico e psicológico.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Coluna da direita: Alunos matriculados -->
    <div style="flex: 0 0 400px;">
        <div class="form-section">
            <h4>🎓 Alunos Matriculados</h4>
            
            <?php if (empty($alunosMatriculados)): ?>
                <div style="text-align: center; padding: 40px; color: #666; border: 1px solid #ddd; border-radius: 8px;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">👥</div>
                    <h5>Nenhum aluno matriculado</h5>
                    <p style="margin: 10px 0;">Comece matriculando os primeiros alunos na turma.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 600px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px;">
                    <?php foreach ($alunosMatriculados as $matricula): ?>
                        <div style="padding: 15px; border-bottom: 1px solid #eee;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                <div style="font-weight: bold; color: #333;">
                                    <?= htmlspecialchars($matricula['aluno_nome']) ?>
                                </div>
                                <span style="background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: bold;">
                                    <?= ucfirst($matricula['status']) ?>
                                </span>
                            </div>
                            
                            <div style="font-size: 0.8rem; color: #666; margin-bottom: 8px;">
                                <div>📱 <?= htmlspecialchars($matricula['telefone'] ?? 'Não informado') ?></div>
                                <div>📧 <?= htmlspecialchars($matricula['email'] ?? 'Não informado') ?></div>
                                <div>🆔 <?= htmlspecialchars($matricula['cpf']) ?> | CNH <?= htmlspecialchars($matricula['categoria_cnh']) ?></div>
                            </div>
                            
                            <div style="font-size: 0.7rem; color: #999; border-top: 1px solid #eee; padding-top: 8px;">
                                📅 Matriculado em: <?= date('d/m/Y H:i', strtotime($matricula['data_matricula'])) ?>
                            </div>
                            
                            <!-- Ações -->
                            <div style="margin-top: 10px; text-align: right;">
                                <button type="button" 
                                        onclick="visualizarAluno(<?= $matricula['aluno_id'] ?>)" 
                                        class="btn-secondary" 
                                        style="padding: 4px 8px; font-size: 0.8rem; margin-right: 5px;">
                                    👁️ Ver
                                </button>
                                <button type="button" 
                                        onclick="removerAluno(<?= $matricula['aluno_id'] ?>, '<?= htmlspecialchars($matricula['aluno_nome']) ?>')" 
                                        class="btn-secondary" 
                                        style="padding: 4px 8px; font-size: 0.8rem; background: #dc3545; border-color: #dc3545;">
                                    🗑️ Remover
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Ações da turma -->
        <?php if ($turmaAtual['status'] === 'completa' && $turmaAtual['alunos_matriculados'] > 0): ?>
            <div class="form-section">
                <h4>🎯 Ações da Turma</h4>
                
                <div style="display: grid; gap: 10px;">
                    <button type="button" 
                            onclick="ativarTurma()" 
                            class="btn-primary" 
                            style="width: 100%; padding: 12px; text-align: left;">
                        🚀 Ativar Turma e Iniciar Aulas
                    </button>
                    
                    <button type="button" 
                            onclick="gerarListaPresenca()" 
                            class="btn-secondary" 
                            style="width: 100%; padding: 12px; text-align: left;">
                        📋 Gerar Lista de Presença
                    </button>
                    
                    <button type="button" 
                            onclick="exportarListaAlunos()" 
                            class="btn-secondary" 
                            style="width: 100%; padding: 12px; text-align: left;">
                        📄 Exportar Lista de Alunos
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Navegação -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
    <a href="?page=turmas-teoricas&acao=revisar&step=3&turma_id=<?= $turmaAtual['id'] ?>" class="btn-secondary">
        ← Voltar à Revisão
    </a>
    
    <div>
        <?php if ($turmaAtual['status'] === 'completa' && $turmaAtual['alunos_matriculados'] > 0): ?>
            <button type="button" onclick="ativarTurma()" class="btn-primary">
                🎯 Finalizar e Ativar Turma
            </button>
        <?php else: ?>
            <div style="text-align: right; color: #666; font-size: 0.9rem;">
                <?php if ($turmaAtual['alunos_matriculados'] === 0): ?>
                    Matricule pelo menos um aluno para ativar a turma
                <?php else: ?>
                    Turma pronta para ser ativada
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Funções JavaScript para ações dos alunos
function visualizarAluno(alunoId) {
    // Implementar modal ou página de detalhes do aluno
    window.open(`?page=alunos&acao=detalhes&aluno_id=${alunoId}`, '_blank');
}

function removerAluno(alunoId, nomeAluno) {
    if (confirm(`⚠️ Confirma a remoção de "${nomeAluno}" desta turma?\n\nEsta ação não pode ser desfeita.`)) {
        // Implementar remoção via AJAX ou form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="acao" value="remover_aluno">
            <input type="hidden" name="aluno_id" value="${alunoId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function ativarTurma() {
    if (confirm('🎯 Confirma a ativação desta turma?\n\nApós ativada, as aulas poderão ser realizadas conforme o cronograma.')) {
        window.location.href = `?page=turmas-teoricas&acao=ativar_turma&turma_id=<?= $turmaAtual['id'] ?>`;
    }
}

function gerarListaPresenca() {
    window.open(`?page=turma-presencas&turma_id=<?= $turmaAtual['id'] ?>&acao=gerar_lista`, '_blank');
}

function exportarListaAlunos() {
    window.open(`?page=turmas-teoricas&acao=exportar_alunos&turma_id=<?= $turmaAtual['id'] ?>`, '_blank');
}

// Atualizar página a cada 30 segundos para mostrar novas matrículas
setInterval(() => {
    // Verificar se há novas matrículas via AJAX (implementação futura)
}, 30000);
</script>
