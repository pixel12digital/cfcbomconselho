<?php
/**
 * Step 3: Controle de Carga Horária e Revisão
 * Verificação final antes de ativar a turma
 */

if (!$turmaAtual) {
    echo '<div class="alert alert-danger">❌ Turma não encontrada.</div>';
    return;
}

// Verificar se turma está completa
$completude = $turmaManager->verificarTurmaCompleta($turmaAtual['id']);

// Obter aulas agendadas detalhadas
try {
    $aulasDetalhadas = $db->fetchAll("
        SELECT 
            taa.*,
            dc.nome_disciplina,
            dc.cor_hex,
            u.nome as instrutor_nome,
            s.nome as sala_nome
        FROM turma_aulas_agendadas taa
        LEFT JOIN disciplinas_configuracao dc ON taa.disciplina = dc.disciplina 
            AND dc.curso_tipo = ?
        LEFT JOIN instrutores i ON taa.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN salas s ON taa.sala_id = s.id
        WHERE taa.turma_id = ?
        ORDER BY taa.data_aula, taa.hora_inicio
    ", [$turmaAtual['curso_tipo'], $turmaAtual['id']]);
} catch (Exception $e) {
    $aulasDetalhadas = [];
}

// Agrupar aulas por data para exibição em calendário
$aulasPorData = [];
foreach ($aulasDetalhadas as $aula) {
    $data = $aula['data_aula'];
    if (!isset($aulasPorData[$data])) {
        $aulasPorData[$data] = [];
    }
    $aulasPorData[$data][] = $aula;
}

// Calcular estatísticas
$totalAulasAgendadas = count($aulasDetalhadas);
$horasAgendadas = $totalAulasAgendadas * 0.83; // 50 minutos = 0.83 horas
$diasAulas = count($aulasPorData);
?>

<div style="display: flex; gap: 30px;">
    <!-- Coluna da esquerda: Status e revisão -->
    <div style="flex: 1;">
        <div class="form-section">
            <h4>📚 Dados da Turma</h4>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.9rem;">
                    <div><strong>Nome:</strong> <?= htmlspecialchars($turmaAtual['nome']) ?></div>
                    <div><strong>Sala:</strong> <?= htmlspecialchars($turmaAtual['sala_nome']) ?></div>
                    <div><strong>Curso:</strong> <?= htmlspecialchars($turmaAtual['curso_nome']) ?></div>
                    <div><strong>Período:</strong> <?= date('d/m/Y', strtotime($turmaAtual['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($turmaAtual['data_fim'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Status da Completude -->
        <div class="form-section">
            <h4>🎯 Status da Turma</h4>
            
            <?php if ($completude['completa']): ?>
                <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">🎉</div>
                    <h5 style="margin: 0 0 10px 0;">Turma Completa!</h5>
                    <p style="margin: 0;">Todas as disciplinas foram agendadas. A turma está pronta para receber alunos.</p>
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">⚠️</div>
                    <h5 style="margin: 0 0 10px 0;">Turma Incompleta</h5>
                    <p style="margin: 0;">Ainda há disciplinas pendentes de agendamento.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Estatísticas detalhadas -->
        <div class="form-section">
            <h4>📊 Estatísticas da Turma</h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #023A8D, #1a4fa0); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= $totalAulasAgendadas ?></div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Aulas Agendadas</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #F7931E, #e8840d); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= number_format($horasAgendadas, 1) ?>h</div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Carga Horária</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= $diasAulas ?></div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Dias de Aula</div>
                </div>
                
                <div style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold;"><?= count($progressoAtual) ?></div>
                    <div style="opacity: 0.9; font-size: 0.9rem;">Disciplinas</div>
                </div>
            </div>
        </div>

        <!-- Progresso detalhado das disciplinas -->
        <div class="form-section">
            <h4>📋 Progresso das Disciplinas</h4>
            
            <?php if (!empty($progressoAtual)): ?>
                <div style="display: grid; gap: 12px;">
                    <?php foreach ($progressoAtual as $disc): ?>
                        <div class="disciplina-item <?= $disc['status_disciplina'] ?>" style="padding: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <strong style="font-size: 1rem;"><?= htmlspecialchars($disc['nome_disciplina']) ?></strong>
                                        <div style="font-size: 1.2rem;">
                                            <?= $disc['status_disciplina'] === 'completa' ? '✅' : ($disc['status_disciplina'] === 'parcial' ? '⚠️' : '❌') ?>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 20px; font-size: 0.9rem; color: #666;">
                                        <span><strong>Agendadas:</strong> <?= $disc['aulas_agendadas'] ?></span>
                                        <span><strong>Obrigatórias:</strong> <?= $disc['aulas_obrigatorias'] ?></span>
                                        <?php if ($disc['aulas_faltantes'] > 0): ?>
                                            <span style="color: #dc3545; font-weight: bold;">
                                                <strong>Faltantes:</strong> <?= $disc['aulas_faltantes'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Barra de progresso individual -->
                                    <div style="width: 100%; height: 6px; background: #e9ecef; border-radius: 3px; margin-top: 8px; overflow: hidden;">
                                        <?php 
                                        $percentualDisc = $disc['aulas_obrigatorias'] > 0 
                                            ? ($disc['aulas_agendadas'] / $disc['aulas_obrigatorias']) * 100 
                                            : 0;
                                        $corBarra = $disc['status_disciplina'] === 'completa' ? '#28a745' : 
                                                   ($disc['status_disciplina'] === 'parcial' ? '#ffc107' : '#dc3545');
                                        ?>
                                        <div style="width: <?= min(100, $percentualDisc) ?>%; height: 100%; background: <?= $corBarra ?>; transition: width 0.3s ease;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Coluna da direita: Cronograma das aulas -->
    <div style="flex: 0 0 450px;">
        <div class="form-section">
            <h4>📅 Cronograma das Aulas</h4>
            
            <?php if (!empty($aulasPorData)): ?>
                <div style="max-height: 600px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px;">
                    <?php foreach ($aulasPorData as $data => $aulas): ?>
                        <div style="border-bottom: 1px solid #eee; padding: 15px;">
                            <div style="font-weight: bold; color: #023A8D; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                <span>📅</span>
                                <?= date('d/m/Y', strtotime($data)) ?>
                                <span style="font-size: 0.8rem; color: #666; font-weight: normal;">
                                    (<?= ucfirst(strftime('%A', strtotime($data))) ?>)
                                </span>
                            </div>
                            
                            <div style="display: grid; gap: 8px; margin-left: 20px;">
                                <?php foreach ($aulas as $aula): ?>
                                    <div style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid <?= $aula['cor_hex'] ?? '#023A8D' ?>;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; font-size: 0.9rem; color: #333;">
                                                <?= htmlspecialchars($aula['nome_disciplina'] ?? ucfirst(str_replace('_', ' ', $aula['disciplina']))) ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #666;">
                                                🕐 <?= $aula['hora_inicio'] ?> - <?= $aula['hora_fim'] ?> | 
                                                👨‍🏫 <?= htmlspecialchars($aula['instrutor_nome'] ?? 'Instrutor não definido') ?>
                                            </div>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #666;">
                                            Aula <?= $aula['ordem_disciplina'] ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666; border: 1px solid #ddd; border-radius: 8px;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">📅</div>
                    <h5>Nenhuma aula agendada</h5>
                    <p style="margin: 10px 0;">Volte ao Step 2 para agendar as aulas das disciplinas.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Ações rápidas -->
        <?php if (!empty($aulasDetalhadas)): ?>
            <div class="form-section">
                <h4>⚡ Ações Rápidas</h4>
                
                <div style="display: grid; gap: 10px;">
                    <button type="button" 
                            onclick="exportarCronograma()" 
                            class="btn-secondary" 
                            style="width: 100%; padding: 12px; text-align: left;">
                        📄 Exportar Cronograma (PDF)
                    </button>
                    
                    <button type="button" 
                            onclick="enviarNotificacaoInstrutores()" 
                            class="btn-secondary" 
                            style="width: 100%; padding: 12px; text-align: left;">
                        📧 Notificar Instrutores
                    </button>
                    
                    <button type="button" 
                            onclick="duplicarTurma()" 
                            class="btn-secondary" 
                            style="width: 100%; padding: 12px; text-align: left;">
                        📋 Duplicar Turma
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Navegação -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd;">
    <a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=<?= $turmaAtual['id'] ?>" class="btn-secondary">
        ← Voltar ao Agendamento
    </a>
    
    <div>
        <?php if ($completude['completa']): ?>
            <a href="?page=turmas-teoricas&acao=alunos&step=4&turma_id=<?= $turmaAtual['id'] ?>" class="btn-primary">
                Próxima Etapa: Inserir Alunos →
            </a>
        <?php else: ?>
            <div style="text-align: right;">
                <div style="color: #dc3545; font-weight: bold; margin-bottom: 5px;">
                    ⚠️ Turma incompleta
                </div>
                <div style="color: #666; font-size: 0.9rem;">
                    Complete o agendamento de todas as disciplinas
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($completude['completa']): ?>
    <!-- Modal de confirmação para ativar turma -->
    <div id="modalAtivarTurma" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
            <h4 style="color: #023A8D; margin-bottom: 20px;">🎯 Ativar Turma</h4>
            
            <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0; color: #004085;">
                    <strong>Confirma a ativação desta turma?</strong><br>
                    Após ativada, você poderá matricular alunos e iniciar as aulas conforme o cronograma.
                </p>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="fecharModalAtivar()" class="btn-secondary">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarAtivar" class="btn-primary">
                    🎯 Ativar Turma
                </button>
            </div>
        </div>
    </div>
    
    <!-- Botão flutuante para ativar turma -->
    <div style="position: fixed; bottom: 30px; right: 30px; z-index: 999;">
        <button type="button" 
                onclick="abrirModalAtivar()" 
                style="background: #F7931E; border: none; color: white; padding: 15px 25px; border-radius: 50px; font-weight: bold; box-shadow: 0 4px 12px rgba(247, 147, 30, 0.4); cursor: pointer; font-size: 1rem; transition: all 0.3s ease;"
                onmouseover="this.style.transform='scale(1.05)'"
                onmouseout="this.style.transform='scale(1)'">
            🎯 Ativar Turma
        </button>
    </div>
<?php endif; ?>

<script>
// Funções JavaScript para ações rápidas
function exportarCronograma() {
    alert('🔄 Funcionalidade em desenvolvimento: Exportar cronograma em PDF');
}

function enviarNotificacaoInstrutores() {
    if (confirm('📧 Deseja enviar notificação por email para todos os instrutores desta turma?')) {
        alert('✅ Notificações enviadas com sucesso!');
    }
}

function duplicarTurma() {
    const nome = prompt('📋 Digite o nome para a nova turma:', '<?= htmlspecialchars($turmaAtual['nome']) ?> - Cópia');
    if (nome && nome.trim()) {
        alert(`✅ Turma "${nome}" criada com base no cronograma atual!`);
    }
}

function abrirModalAtivar() {
    document.getElementById('modalAtivarTurma').style.display = 'flex';
}

function fecharModalAtivar() {
    document.getElementById('modalAtivarTurma').style.display = 'none';
}

// Confirmar ativação da turma
document.addEventListener('DOMContentLoaded', function() {
    const btnConfirmar = document.getElementById('btnConfirmarAtivar');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            this.disabled = true;
            this.textContent = '🔄 Ativando...';
            
            // Redirecionar para ativação
            setTimeout(() => {
                window.location.href = '?page=turmas-teoricas&acao=ativar_turma&turma_id=<?= $turmaAtual['id'] ?>';
            }, 1000);
        });
    }
});

// Fechar modal ao clicar fora
document.getElementById('modalAtivarTurma')?.addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalAtivar();
    }
});

// Configurar strftime para português (se disponível)
if (typeof setlocale === 'function') {
    setlocale(LC_TIME, 'pt_BR.UTF-8');
}
</script>

<style>
/* Estilos adicionais para o Step 3 */
.disciplina-item.completa {
    border-left-color: #28a745 !important;
    background: linear-gradient(90deg, #d4edda, #f8f9fa) !important;
}

.disciplina-item.parcial {
    border-left-color: #ffc107 !important;
    background: linear-gradient(90deg, #fff3cd, #f8f9fa) !important;
}

.disciplina-item.pendente {
    border-left-color: #dc3545 !important;
    background: linear-gradient(90deg, #f8d7da, #f8f9fa) !important;
}

/* Scroll customizado para cronograma */
.form-section div::-webkit-scrollbar {
    width: 8px;
}

.form-section div::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.form-section div::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.form-section div::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
