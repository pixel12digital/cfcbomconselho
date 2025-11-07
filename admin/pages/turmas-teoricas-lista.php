<?php
/**
 * Lista de Turmas Teóricas
 * Componente para exibir turmas existentes
 */

// Buscar turmas existentes
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'status' => $_GET['status'] ?? '',
    'curso_tipo' => $_GET['curso_tipo'] ?? '',
    'cfc_id' => $isAdmin ? null : ($user['cfc_id'] ?? 1)
];

$resultado = $turmaManager->listarTurmas($filtros);
$turmas = $resultado['sucesso'] ? $resultado['dados'] : [];

$statusLabels = [
    'criando' => 'Agendando',
    'agendando' => 'Agendando',
    'completa' => 'Agendado',
    'ativa' => 'Em andamento',
    'finalizada' => 'Concluída'
];

// Estatísticas rápidas
$stats = [
    'total' => count($turmas),
    'ativas' => count(array_filter($turmas, fn($t) => $t['status'] === 'ativa')),
    'criando' => count(array_filter($turmas, fn($t) => in_array($t['status'], ['criando', 'agendando']))),
    'concluidas' => count(array_filter($turmas, fn($t) => $t['status'] === 'finalizada'))
];
?>

<!-- Cabeçalho com estatísticas -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h3 style="margin: 0; color: var(--primary-dark); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-layer-group icon icon-24"></i>
            Turmas Teóricas
        </h3>
        <p style="margin: 5px 0 0 0; color: var(--gray-600);">
            Gerencie turmas teóricas com controle completo de disciplinas e carga horária
        </p>
    </div>
    <a href="?page=turmas-teoricas&acao=nova&step=1" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
        <i class="fas fa-plus icon icon-20"></i>
        Nova Turma Teórica
    </a>
</div>

<!-- Cards de estatísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: linear-gradient(135deg, #023A8D, #1a4fa0); color: white; padding: 20px; border-radius: 8px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $stats['total'] ?></div>
        <div style="opacity: 0.9;">Total de Turmas</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 20px; border-radius: 8px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $stats['ativas'] ?></div>
        <div style="opacity: 0.9;">Turmas Ativas</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #F7931E, #e8840d); color: white; padding: 20px; border-radius: 8px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $stats['criando'] ?></div>
        <div style="opacity: 0.9;">Em Criação</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white; padding: 20px; border-radius: 8px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $stats['concluidas'] ?></div>
        <div style="opacity: 0.9;">Concluídas</div>
    </div>
</div>

<!-- Filtros -->
<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
        <input type="hidden" name="page" value="turmas-teoricas">
        
        <div style="flex: 1; min-width: 200px;">
            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px; font-weight: 600; color: var(--gray-600);">
                <i class="fas fa-search icon icon-20"></i>
                Buscar
            </label>
            <input type="text" 
                   name="busca" 
                   value="<?= htmlspecialchars($filtros['busca']) ?>"
                   placeholder="Nome da turma, curso, sala..."
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="min-width: 150px;">
            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px; font-weight: 600; color: var(--gray-600);">
                <i class="fas fa-stream icon icon-20"></i>
                Status
            </label>
            <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Todos</option>
                <option value="criando" <?= $filtros['status'] === 'criando' ? 'selected' : '' ?>>Criando</option>
                <option value="agendando" <?= $filtros['status'] === 'agendando' ? 'selected' : '' ?>>Agendando</option>
                <option value="completa" <?= $filtros['status'] === 'completa' ? 'selected' : '' ?>>Completa</option>
                <option value="ativa" <?= $filtros['status'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
                <option value="finalizada" <?= $filtros['status'] === 'finalizada' ? 'selected' : '' ?>>Concluída</option>
            </select>
        </div>
        
        <div style="min-width: 150px;">
            <label style="display: flex; align-items: center; gap: 6px; margin-bottom: 5px; font-weight: 600; color: var(--gray-600);">
                <i class="fas fa-graduation-cap icon icon-20"></i>
                Curso
            </label>
            <select name="curso_tipo" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Todos</option>
                <?php foreach ($cursosDisponiveis as $key => $nome): ?>
                    <option value="<?= $key ?>" <?= $filtros['curso_tipo'] === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nome) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <button type="submit" class="btn-primary" style="padding: 8px 20px; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-search icon icon-20"></i>
                Filtrar
            </button>
            <a href="?page=turmas-teoricas" class="btn-secondary" style="padding: 8px 15px; margin-left: 5px; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-undo icon icon-20"></i>
                Limpar
            </a>
        </div>
    </form>
</div>

<!-- Lista de turmas -->
<?php if (empty($turmas)): ?>
    <div style="text-align: center; padding: 60px 20px; color: var(--gray-600);">
        <div style="font-size: 4rem; margin-bottom: 20px;">
            <i class="fas fa-layer-group icon icon-64"></i>
        </div>
        <h4>Nenhuma turma teórica encontrada</h4>
        <p>Comece criando uma nova turma teórica para organizar suas aulas por disciplinas.</p>
        <a href="?page=turmas-teoricas&acao=nova&step=1" class="btn-primary" style="margin-top: 20px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-plus icon icon-20"></i>
            Criar Primeira Turma
        </a>
    </div>
<?php else: ?>
    <div style="display: grid; gap: 20px;">
        <?php foreach ($turmas as $turma): ?>
            <?php $statusBadgeText = $statusLabels[$turma['status']] ?? ucfirst($turma['status']); ?>
            <div class="turma-card">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; gap: 10px;">
                    <div style="flex: 1; min-width: 0;">
                        <h5 style="margin: 0 0 5px 0; font-size: 1.2rem;">
                            <a href="?page=turmas-teoricas&acao=detalhes&turma_id=<?= $turma['id'] ?>"
                               class="turma-title-link">
                                <?= htmlspecialchars($turma['nome']) ?>
                            </a>
                        </h5>
                        <div style="color: #666; font-size: 0.9rem;">
                            <?= htmlspecialchars($turma['curso_nome'] ?? 'Curso não especificado') ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?= $turma['status'] ?>">
                        <?= htmlspecialchars($statusBadgeText) ?>
                    </span>
                </div>
                
                <div class="turma-meta">
                    <span class="turma-meta__item">
                        <i class="fas fa-door-open icon icon-20 icon-muted"></i>
                        <?= htmlspecialchars($turma['sala_nome'] ?? 'Sala não definida') ?>
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-calendar icon icon-20 icon-muted"></i>
                        <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-user-friends icon icon-20 icon-muted"></i>
                        <?= $turma['alunos_matriculados'] ?>/<?= $turma['max_alunos'] ?> alunos
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-clock icon icon-20 icon-muted"></i>
                        <?= $turma['horas_agendadas'] ?? 0 ?>h agendadas
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-chalkboard-teacher icon icon-20 icon-muted"></i>
                        <?= $turma['modalidade'] === 'online' ? 'Online' : 'Presencial' ?>
                    </span>
                </div>
                
                <!-- Progresso visual das disciplinas -->
                <?php 
                $progresso = $turmaManager->obterProgressoDisciplinas($turma['id']);
                if (!empty($progresso)): 
                ?>
                    <div class="progresso-disciplinas">
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 8px;">
                            <strong>Progresso das Disciplinas:</strong>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                            <?php foreach ($progresso as $disc): ?>
                                <div class="disciplina-item <?= $disc['status_disciplina'] ?>">
                                    <div style="flex: 1;">
                                        <strong><?= htmlspecialchars($disc['nome_disciplina']) ?></strong>
                                        <div style="font-size: 0.8rem; opacity: 0.8;">
                                            <?= $disc['aulas_agendadas'] ?>/<?= $disc['aulas_obrigatorias'] ?> aulas
                                            <?php if ($disc['aulas_faltantes'] > 0): ?>
                                                (faltam <?= $disc['aulas_faltantes'] ?>)
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($disc['status_disciplina'] === 'completa'): ?>
                                            <i class="fas fa-check-circle icon icon-20 icon-success"></i>
                                        <?php elseif ($disc['status_disciplina'] === 'parcial'): ?>
                                            <i class="fas fa-exclamation-circle icon icon-20 icon-warning"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle icon icon-20 icon-danger"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Ações -->
                <div class="turma-actions">
                    <a href="?page=turmas-teoricas&acao=detalhes&turma_id=<?= $turma['id'] ?>"
                       class="btn-primary"
                       style="padding: 8px 16px; font-size: 0.9rem;"
                       title="Abrir a página para editar informações, alunos e calendário da turma.">
                        Gerenciar Turma
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Paginação (se necessário) -->
    <?php if (count($turmas) >= 10): ?>
        <div style="text-align: center; margin-top: 30px; color: #666;">
            <p>Mostrando <?= count($turmas) ?> turma(s)</p>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
.icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: inherit;
    line-height: 1;
}

.icon-20 { font-size: 20px; }
.icon-24 { font-size: 24px; }
.icon-64 { font-size: 64px; }

.icon-muted { color: var(--gray-500); }
.icon-success { color: var(--success-color); }
.icon-warning { color: var(--warning-color); }
.icon-danger { color: var(--danger-color); }

.btn-danger {
    background: #dc3545;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.turma-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: flex-start;
}

.turma-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: var(--gray-700);
}

.turma-meta__item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.turma-title-link {
    color: inherit;
    text-decoration: none;
}

.turma-title-link:hover {
    text-decoration: underline;
}
</style>

<script>
// Função global para detectar o path base automaticamente
function getBasePath() {
    return window.location.pathname.includes('/cfc-bom-conselho/') ? '/cfc-bom-conselho' : '';
}

// Função para excluir turma (versão antiga - mantida para compatibilidade)
function excluirTurma(turmaId, nomeTurma) {
    if (confirm(`Tem certeza que deseja excluir a turma "${nomeTurma}"?\n\nEsta ação não pode ser desfeita.`)) {
        // Fazer requisição para excluir
        const formData = new FormData();
        formData.append('acao', 'excluir');
        formData.append('turma_id', turmaId);
        
        fetch(getBasePath() + '/admin/api/turmas-teoricas.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Status da resposta:', response.status);
            console.log('Headers da resposta:', response.headers);
            
            // Verificar se a resposta é JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido. Content-Type: ' + contentType);
            }
            
            return response.text().then(text => {
                console.log('Resposta bruta:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.error('Texto recebido:', text);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            console.log('Dados processados:', data);
            if (data.sucesso) {
                alert('Turma excluída com sucesso.');
                location.reload();
            } else {
                alert('Erro ao excluir turma: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro completo:', error);
            alert('Erro ao excluir turma: ' + error.message);
        });
    }
}

/**
 * Excluir turma completamente (apenas para administradores)
 * Exclui a turma e todos os dados relacionados (agendamentos, alunos, etc.)
 */
function excluirTurmaCompleta(turmaId, nomeTurma) {
    // Confirmação com detalhes
    const mensagem = `ATENÇÃO: Esta ação é IRREVERSÍVEL!\n\n` +
                     `Você está prestes a excluir COMPLETAMENTE a turma:\n` +
                     `"${nomeTurma}"\n\n` +
                     `Isso irá excluir:\n` +
                     `• A turma em si\n` +
                     `• Todas as aulas agendadas\n` +
                     `• Todas as matrículas de alunos\n` +
                     `• Todos os registros relacionados\n\n` +
                     `Tem certeza que deseja continuar?`;
    
    if (!confirm(mensagem)) {
        return;
    }
    
    // Segunda confirmação para garantir
    if (!confirm('ÚLTIMA CONFIRMAÇÃO!\n\nEsta ação não pode ser desfeita. Deseja realmente excluir esta turma?')) {
        return;
    }
    
    // Mostrar loading
    const btnExcluir = event.target.closest('button');
    const textoOriginal = btnExcluir.innerHTML;
    btnExcluir.disabled = true;
    btnExcluir.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';
    
    // Fazer requisição para API
    fetch(getBasePath() + '/admin/api/turmas-teoricas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            acao: 'excluir',
            turma_id: turmaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Sucesso - mostrar mensagem e recarregar página
            alert(data.mensagem);
            location.reload();
        } else {
            // Erro - restaurar botão e mostrar mensagem
            btnExcluir.disabled = false;
            btnExcluir.innerHTML = textoOriginal;
            alert('Erro: ' + data.mensagem);
        }
    })
    .catch(error => {
        // Erro de rede - restaurar botão e mostrar mensagem
        btnExcluir.disabled = false;
        btnExcluir.innerHTML = textoOriginal;
        console.error('Erro ao excluir turma:', error);
        alert('Erro ao excluir turma. Verifique sua conexão e tente novamente.');
    });
}
</script>
