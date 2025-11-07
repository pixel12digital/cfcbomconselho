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

$stats = [
    'total' => count($turmas),
    'ativas' => count(array_filter($turmas, fn($t) => $t['status'] === 'ativa')),
    'criando' => count(array_filter($turmas, fn($t) => in_array($t['status'], ['criando', 'agendando']))),
    'concluidas' => count(array_filter($turmas, fn($t) => $t['status'] === 'finalizada'))
];
?>

<!-- Cabeçalho com estatísticas -->
<div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 26px; flex-wrap: wrap;">
    <h1 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-layer-group" style="font-size: 1.4rem; color: #64748b;"></i>
        Turmas Teóricas
    </h1>
    <a href="?page=turmas-teoricas&acao=nova&step=1"
       style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px; border: 1px solid rgba(2, 58, 141, 0.35); color: var(--primary-dark); background: rgba(2, 58, 141, 0.08); font-weight: 600; text-decoration: none;">
        <i class="fas fa-plus" style="font-size: 0.95rem; color: inherit;"></i>
        Nova turma
    </a>
</div>

<?php
$cardsResumo = [
    ['label' => 'Total de turmas', 'valor' => $stats['total']],
    ['label' => 'Turmas ativas', 'valor' => $stats['ativas']],
    ['label' => 'Em criação', 'valor' => $stats['criando']],
    ['label' => 'Concluídas', 'valor' => $stats['concluidas']],
];
?>
<!-- Cards de estatísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <?php foreach ($cardsResumo as $card): ?>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 4px;">
            <span style="font-size: 1.75rem; font-weight: 700; color: var(--primary-dark); letter-spacing: -0.02em; line-height: 1;">
                <?= $card['valor'] ?>
            </span>
            <span style="font-size: 0.78rem; font-weight: 600; color: #4b5563; text-transform: none;">
                <?= htmlspecialchars($card['label']) ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<div style="position: sticky; top: 68px; z-index: 20; margin-bottom: 18px;">
    <form method="GET" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <input type="hidden" name="page" value="turmas-teoricas">

        <div style="flex: 1 1 260px; display: flex; align-items: center; gap: 8px; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 10px;">
            <i class="fas fa-search" style="color: #64748b;"></i>
            <input type="text"
                   name="busca"
                   value="<?= htmlspecialchars($filtros['busca']) ?>"
                   placeholder="Buscar por turma, curso ou sala"
                   style="border: none; outline: none; flex: 1; font-size: 0.95rem; background: transparent;"
                   onkeydown="if(event.key==='Enter'){ this.form.submit(); }">
        </div>

        <label style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #475569;">
            <span>Status</span>
            <select name="status" style="min-width: 140px; padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #ffffff; font-size: 0.9rem;">
                <option value="">Todos</option>
                <option value="criando" <?= $filtros['status'] === 'criando' ? 'selected' : '' ?>>Criando</option>
                <option value="agendando" <?= $filtros['status'] === 'agendando' ? 'selected' : '' ?>>Agendando</option>
                <option value="completa" <?= $filtros['status'] === 'completa' ? 'selected' : '' ?>>Completa</option>
                <option value="ativa" <?= $filtros['status'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
                <option value="finalizada" <?= $filtros['status'] === 'finalizada' ? 'selected' : '' ?>>Concluída</option>
            </select>
        </label>

        <label style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; color: #475569;">
            <span>Curso</span>
            <select name="curso_tipo" style="min-width: 160px; padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #ffffff; font-size: 0.9rem;">
                <option value="">Todos</option>
                <?php foreach ($cursosDisponiveis as $key => $nome): ?>
                    <option value="<?= $key ?>" <?= $filtros['curso_tipo'] === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nome) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div style="display: flex; gap: 6px; align-items: center;">
            <button type="submit" style="padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(2, 58, 141, 0.35); background: rgba(2, 58, 141, 0.08); color: var(--primary-dark); font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-search" style="font-size: 0.85rem;"></i>
                Filtrar
            </button>
            <a href="?page=turmas-teoricas" style="padding: 6px 10px; border-radius: 8px; border: 1px solid #cbd5f5; background: #ffffff; color: #475569; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <i class="fas fa-undo" style="font-size: 0.85rem;"></i>
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
            <?php
                $statusBadgeText = $statusLabels[$turma['status']] ?? ucfirst($turma['status']);
                $turmaNome = htmlspecialchars($turma['nome']);
                $turmaNomeJs = htmlspecialchars(addslashes($turma['nome']));
                $cursoNome = htmlspecialchars($turma['curso_nome'] ?? 'Curso não especificado');
                $progresso = $turmaManager->obterProgressoDisciplinas($turma['id']);

                if (!empty($progresso)) {
                    usort($progresso, function ($a, $b) {
                        $weight = function ($disc) {
                            if (($disc['aulas_faltantes'] ?? 0) > 0) {
                                return 0;
                            }
                            if (($disc['status_disciplina'] ?? '') === 'parcial') {
                                return 1;
                            }
                            return 2;
                        };
                        $wA = $weight($a);
                        $wB = $weight($b);
                        if ($wA === $wB) {
                            return ($b['aulas_faltantes'] ?? 0) <=> ($a['aulas_faltantes'] ?? 0);
                        }
                        return $wA <=> $wB;
                    });
                }
            ?>
            <div class="turma-card">
                <div class="turma-card__header">
                    <div class="turma-card__title-group">
                        <div class="turma-card__title-row">
                            <a href="?page=turmas-teoricas&acao=detalhes&turma_id=<?= $turma['id'] ?>"
                               class="turma-card__title"
                               title="<?= $turmaNome ?>">
                                <?= $turmaNome ?>
                            </a>
                            <span class="status-badge status-<?= $turma['status'] ?>">
                                <?= htmlspecialchars($statusBadgeText) ?>
                            </span>
                        </div>
                        <div class="turma-card__subtitle"><?= $cursoNome ?></div>
                    </div>
                    <div class="turma-card__header-actions">
                        <div class="turma-card__menu">
                            <button type="button" class="turma-card__menu-trigger" aria-haspopup="true" aria-expanded="false" aria-label="Abrir menu de ações da turma <?= $turmaNome ?>" onclick="toggleTurmaCardMenu(this)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="turma-card__menu-dropdown">
                                <a href="?page=turmas-teoricas&acao=detalhes&turma_id=<?= $turma['id'] ?>" onclick="closeTurmaCardMenusImmediate()">
                                    <i class="fas fa-external-link-alt"></i>
                                    Gerenciar turma
                                </a>
                                <?php if ($isAdmin): ?>
                                    <button type="button" onclick="excluirTurmaCompleta(this, <?= $turma['id'] ?>, '<?= $turmaNomeJs ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                        Excluir turma
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="turma-meta">
                    <span class="turma-meta__item">
                        <i class="fas fa-door-open"></i>
                        <?= htmlspecialchars($turma['sala_nome'] ?? 'Sala não definida') ?>
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-calendar"></i>
                        <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-users"></i>
                        <?= $turma['alunos_matriculados'] ?>/<?= $turma['max_alunos'] ?> alunos
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-clock"></i>
                        <?= number_format((float)($turma['horas_agendadas'] ?? 0), 0, ',', '.') ?> h agendadas
                    </span>
                    <span class="turma-meta__item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <?= $turma['modalidade'] === 'online' ? 'Online' : 'Presencial' ?>
                    </span>
                </div>

                <?php if (!empty($progresso)): ?>
                    <div class="progresso-disciplinas">
                        <div class="progresso-disciplinas__header">Progresso das disciplinas</div>
                        <div class="progresso-disciplinas__grid">
                            <?php foreach ($progresso as $disc): ?>
                                <?php
                                    $obrigatorias = (int)($disc['aulas_obrigatorias'] ?? 0);
                                    $agendadas = (int)($disc['aulas_agendadas'] ?? 0);
                                    $percentual = $obrigatorias > 0 ? round(($agendadas / max(1, $obrigatorias)) * 100) : 0;

                                    $badgeLabel = 'Em andamento';
                                    $badgeClass = 'state-progress';

                                    if ($percentual >= 100 && $obrigatorias > 0) {
                                        $badgeLabel = 'Concluída';
                                        $badgeClass = 'state-complete';
                                        $percentual = 100;
                                    } elseif ($agendadas <= 0) {
                                        $badgeLabel = 'Pendente';
                                        $badgeClass = 'state-pending';
                                        $percentual = 0;
                                    } else {
                                        $percentual = min(99, max(1, $percentual));
                                    }

                                    $badgeAria = sprintf('Estado: %s — %d de %d aulas', $badgeLabel, $agendadas, $obrigatorias);
                                    $disciplinaIdAttr = isset($disc['disciplina_id']) ? (int)$disc['disciplina_id'] : 'null';
                                ?>
                                <div class="disciplina-pill">
                                    <span class="disciplina-pill__title" title="<?= htmlspecialchars($disc['nome_disciplina']) ?>"><?= htmlspecialchars($disc['nome_disciplina']) ?></span>
                                    <a href="?page=turmas-teoricas&acao=detalhes&turma_id=<?= (int)$turma['id'] ?>#calendario"
                                       class="disciplina-pill__badge <?= $badgeClass ?>"
                                       aria-label="<?= htmlspecialchars($badgeAria) ?>"
                                       onclick="abrirCalendarioDisciplina(event, <?= (int)$turma['id'] ?>, <?= $disciplinaIdAttr ?>)">
                                        <span><?= $badgeLabel ?></span>
                                    </a>
                                    <div class="disciplina-pill__metric"><?= $agendadas ?>/<?= $obrigatorias ?> aulas</div>
                                    <div class="disciplina-pill__metric"><?= $percentual ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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

.turma-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.turma-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.turma-card__title-group {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.turma-card__title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.turma-card__title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    display: inline-block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.turma-card__title:hover {
    text-decoration: underline;
}

.turma-card__subtitle {
    font-size: 0.88rem;
    color: #526079;
}

.turma-card__header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.turma-card__menu {
    position: relative;
}

.turma-card__menu-trigger {
    border: 1px solid #cbd5f5;
    background: #ffffff;
    border-radius: 8px;
    padding: 6px 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.turma-card__menu-trigger:hover {
    background: #eef2ff;
    color: #1d4ed8;
}

.turma-card__menu-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
    min-width: 190px;
    padding: 8px 0;
    display: none;
    flex-direction: column;
    z-index: 30;
}

.turma-card__menu.open .turma-card__menu-dropdown {
    display: flex;
}

.turma-card__menu-dropdown a,
.turma-card__menu-dropdown button {
    padding: 8px 16px;
    font-size: 0.9rem;
    color: #0f172a;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.turma-card__menu-dropdown a:hover,
.turma-card__menu-dropdown button:hover {
    background: #f1f5f9;
}

.turma-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.9rem;
    color: #475569;
}

.turma-meta__item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.turma-meta__item i {
    color: #475569;
    font-size: 1.2rem;
}

.progresso-disciplinas {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.progresso-disciplinas__header {
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
    text-transform: none;
}

.progresso-disciplinas__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 10px;
}

.disciplina-pill {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 14px;
    min-height: 112px;
}

.disciplina-pill__title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
}

.disciplina-pill__badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: none;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid transparent;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
}

.disciplina-pill__badge-icon {
    font-size: 0.85rem;
    line-height: 1;
}

.disciplina-pill__metric {
    font-size: 0.8rem;
    color: #475569;
}

.disciplina-pill__metric + .disciplina-pill__metric {
    margin-top: -2px;
}

.disciplina-pill__badge.state-pending {
    background: #f1f3f5;
    color: #495057;
    border-color: #e0e3e7;
}

.disciplina-pill__badge.state-progress {
    background: #fff4e5;
    color: #8a6100;
    border-color: #fcd9a4;
}

.disciplina-pill__badge.state-complete {
    background: #e6f4ea;
    color: #1e7d3c;
    border-color: #c9efd5;
}

@media (max-width: 768px) {
    .turma-card__header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .turma-card__header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .progresso-disciplinas__grid {
        grid-template-columns: 1fr;
    }

    .turma-meta {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
// Toolbar helpers
function toggleTurmaCardMenu(button) {
    const menu = button.closest('.turma-card__menu');
    const willOpen = !menu.classList.contains('open');

    document.querySelectorAll('.turma-card__menu.open').forEach(openMenu => {
        if (openMenu !== menu) {
            openMenu.classList.remove('open');
            const trigger = openMenu.querySelector('.turma-card__menu-trigger');
            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        }
    });

    if (willOpen) {
        menu.classList.add('open');
        button.setAttribute('aria-expanded', 'true');
        document.addEventListener('click', closeTurmaCardMenus, true);
    } else {
        menu.classList.remove('open');
        button.setAttribute('aria-expanded', 'false');
        document.removeEventListener('click', closeTurmaCardMenus, true);
    }
}

function closeTurmaCardMenus(event) {
    if (event && event.target.closest('.turma-card__menu')) {
        return;
    }
    document.querySelectorAll('.turma-card__menu.open').forEach(menu => {
        menu.classList.remove('open');
        const trigger = menu.querySelector('.turma-card__menu-trigger');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
    });
    document.removeEventListener('click', closeTurmaCardMenus, true);
}

function closeTurmaCardMenusImmediate() {
    closeTurmaCardMenus();
}

function abrirCalendarioDisciplina(event, turmaId, disciplinaId) {
    if (event) {
        event.preventDefault();
    }
    let url = `?page=turmas-teoricas&acao=detalhes&turma_id=${turmaId}&tab=calendario`;
    if (disciplinaId !== null && disciplinaId !== undefined) {
        url += `&disciplina_id=${disciplinaId}`;
    }
    window.location.href = url + '#calendario';
}

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
function excluirTurmaCompleta(button, turmaId, nomeTurma) {
    closeTurmaCardMenusImmediate();

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

    if (!confirm('ÚLTIMA CONFIRMAÇÃO!\n\nEsta ação não pode ser desfeita. Deseja realmente excluir esta turma?')) {
        return;
    }

    const triggerButton = button;
    const textoOriginal = triggerButton.innerHTML;
    triggerButton.disabled = true;
    triggerButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';

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
            alert(data.mensagem);
            location.reload();
        } else {
            triggerButton.disabled = false;
            triggerButton.innerHTML = textoOriginal;
            alert('Erro: ' + data.mensagem);
        }
    })
    .catch(error => {
        triggerButton.disabled = false;
        triggerButton.innerHTML = textoOriginal;
        console.error('Erro ao excluir turma:', error);
        alert('Erro ao excluir turma. Verifique sua conexão e tente novamente.');
    });
}
</script>
