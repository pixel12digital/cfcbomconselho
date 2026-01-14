<?php
$isInstrutor = ($isInstrutor ?? false) || ($_SESSION['current_role'] ?? '') === 'INSTRUTOR';
$isAdmin = !$isAluno && !$isInstrutor;
?>
<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1><?= ($isAluno || $isInstrutor) ? 'Minha Agenda' : 'Agenda' ?></h1>
            <p class="text-muted"><?= ($isAluno || $isInstrutor) ? 'Suas aulas agendadas' : 'Agendamento e controle de aulas' ?></p>
        </div>
        <?php if ($isAdmin): ?>
        <a href="<?= base_path('agenda/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Aula
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros e Controles -->
<div class="card" style="margin-bottom: var(--spacing-md);">
    <div class="card-body">
        <form method="GET" action="<?= base_path('agenda') ?>" id="filtersForm">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                <!-- View (apenas para admin/secretaria) -->
                <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label class="form-label">Visualização</label>
                    <select name="view" class="form-input" onchange="this.form.submit()">
                        <option value="list" <?= $viewType === 'list' ? 'selected' : '' ?>>Lista</option>
                        <option value="week" <?= $viewType === 'week' ? 'selected' : '' ?>>Semanal</option>
                        <option value="day" <?= $viewType === 'day' ? 'selected' : '' ?>>Diária</option>
                    </select>
                </div>
                
                <!-- Data -->
                <div class="form-group">
                    <label class="form-label">Data</label>
                    <input type="date" name="date" class="form-input" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
                </div>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- Instrutor (apenas administrativo) -->
                <div class="form-group">
                    <label class="form-label">Instrutor</label>
                    <select name="instructor_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach ($instructors as $instructor): ?>
                        <option value="<?= $instructor['id'] ?>" <?= ($filters['instructor_id'] ?? '') == $instructor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($instructor['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Veículo (apenas administrativo) -->
                <div class="form-group">
                    <label class="form-label">Veículo</label>
                    <select name="vehicle_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= $vehicle['id'] ?>" <?= ($filters['vehicle_id'] ?? '') == $vehicle['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vehicle['plate']) ?> - <?= htmlspecialchars($vehicle['model'] ?? '') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- Status -->
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="agendada" <?= ($filters['status'] ?? '') === 'agendada' ? 'selected' : '' ?>>Agendada</option>
                        <option value="em_andamento" <?= ($filters['status'] ?? '') === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="concluida" <?= ($filters['status'] ?? '') === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                        <option value="cancelada" <?= ($filters['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($isAdmin): ?>
            <!-- Filtro para exibir canceladas (apenas administrativo) -->
            <div style="margin-top: var(--spacing-sm); padding-top: var(--spacing-sm); border-top: 1px solid var(--color-border, #e0e0e0);">
                <label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                    <input type="checkbox" name="show_canceled" value="1" 
                           <?= ($showCanceled ?? false) ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <span>Exibir aulas canceladas</span>
                </label>
            </div>
            <?php endif; ?>
            
            <?php if ($isAdmin): ?>
            <!-- Navegação de Data -->
            <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-md); align-items: center; justify-content: center;">
                <button type="button" class="btn btn-outline" onclick="navigateDate(-1)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <?= $viewType === 'week' ? 'Semana Anterior' : 'Dia Anterior' ?>
                </button>
                <button type="button" class="btn btn-outline" onclick="navigateDate(0)">
                    Hoje
                </button>
                <button type="button" class="btn btn-outline" onclick="navigateDate(1)">
                    <?= $viewType === 'week' ? 'Próxima Semana' : 'Próximo Dia' ?>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Calendário / Lista -->
<div class="card">
    <div class="card-body">
        <?php if ($viewType === 'list'): ?>
            <!-- Abas para INSTRUTOR -->
            <?php if ($isInstrutor): ?>
            <?php 
            $currentTab = $tab ?? 'proximas';
            ?>
            <div class="instructor-tabs" style="display: flex; gap: var(--spacing-xs); margin-bottom: var(--spacing-md); border-bottom: 2px solid var(--color-border, #e0e0e0); overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <a href="<?= base_path('agenda?view=list&tab=proximas') ?>" 
                   class="instructor-tab <?= $currentTab === 'proximas' ? 'active' : '' ?>"
                   style="padding: var(--spacing-sm) var(--spacing-md); text-decoration: none; color: <?= $currentTab === 'proximas' ? 'var(--color-primary, #3b82f6)' : 'var(--color-text-muted, #666)' ?>; border-bottom: 2px solid <?= $currentTab === 'proximas' ? 'var(--color-primary, #3b82f6)' : 'transparent' ?>; margin-bottom: -2px; font-weight: <?= $currentTab === 'proximas' ? '600' : '400' ?>; transition: all 0.2s; white-space: nowrap; flex-shrink: 0;">
                    Próximas
                </a>
                <a href="<?= base_path('agenda?view=list&tab=historico') ?>" 
                   class="instructor-tab <?= $currentTab === 'historico' ? 'active' : '' ?>"
                   style="padding: var(--spacing-sm) var(--spacing-md); text-decoration: none; color: <?= $currentTab === 'historico' ? 'var(--color-primary, #3b82f6)' : 'var(--color-text-muted, #666)' ?>; border-bottom: 2px solid <?= $currentTab === 'historico' ? 'var(--color-primary, #3b82f6)' : 'transparent' ?>; margin-bottom: -2px; font-weight: <?= $currentTab === 'historico' ? '600' : '400' ?>; transition: all 0.2s; white-space: nowrap; flex-shrink: 0;">
                    Histórico
                </a>
                <a href="<?= base_path('agenda?view=list&tab=todas') ?>" 
                   class="instructor-tab <?= $currentTab === 'todas' ? 'active' : '' ?>"
                   style="padding: var(--spacing-sm) var(--spacing-md); text-decoration: none; color: <?= $currentTab === 'todas' ? 'var(--color-primary, #3b82f6)' : 'var(--color-text-muted, #666)' ?>; border-bottom: 2px solid <?= $currentTab === 'todas' ? 'var(--color-primary, #3b82f6)' : 'transparent' ?>; margin-bottom: -2px; font-weight: <?= $currentTab === 'todas' ? '600' : '400' ?>; transition: all 0.2s; white-space: nowrap; flex-shrink: 0;">
                    Todas
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Visualização Lista -->
            <?php
            $currentTab = $tab ?? 'proximas';
            // Ordenar aulas por data/hora (próximas primeiro, ou desc para histórico)
            if ($currentTab === 'historico' || $currentTab === 'todas') {
                usort($lessons, function($a, $b) {
                    $dateA = strtotime($a['scheduled_date'] . ' ' . $a['scheduled_time']);
                    $dateB = strtotime($b['scheduled_date'] . ' ' . $b['scheduled_time']);
                    return $dateB - $dateA; // Desc para histórico
                });
            } else {
                usort($lessons, function($a, $b) {
                    $dateA = strtotime($a['scheduled_date'] . ' ' . $a['scheduled_time']);
                    $dateB = strtotime($b['scheduled_date'] . ' ' . $b['scheduled_time']);
                    return $dateA - $dateB; // Asc para próximas
                });
            }
            
            $statusConfig = [
                'agendada' => ['label' => 'Agendada', 'color' => '#3b82f6', 'bg' => '#dbeafe'],
                'em_andamento' => ['label' => 'Em Andamento', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
                'concluida' => ['label' => 'Concluída', 'color' => '#10b981', 'bg' => '#d1fae5'],
                'cancelada' => ['label' => 'Cancelada', 'color' => '#ef4444', 'bg' => '#fee2e2'],
                'no_show' => ['label' => 'Não Compareceu', 'color' => '#6b7280', 'bg' => '#f3f4f6']
            ];
            ?>
            
            <?php if (empty($lessons)): ?>
                <div style="text-align: center; padding: 40px 20px;">
                    <p class="text-muted">
                        <?php if ($isInstrutor): ?>
                            <?php if ($currentTab === 'historico'): ?>
                                Nenhuma aula no histórico.
                            <?php else: ?>
                                Você não possui aulas agendadas.
                            <?php endif; ?>
                        <?php else: ?>
                            Você não possui aulas agendadas.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                    <?php foreach ($lessons as $lesson): ?>
                        <?php
                        $lessonDate = new \DateTime($lesson['scheduled_date'] . ' ' . $lesson['scheduled_time']);
                        $endTime = clone $lessonDate;
                        $endTime->modify("+{$lesson['duration_minutes']} minutes");
                        
                        $status = $statusConfig[$lesson['status']] ?? ['label' => $lesson['status'], 'color' => '#666', 'bg' => '#f3f4f6'];
                        $isPast = $lessonDate < new \DateTime();
                        ?>
                        <div style="padding: var(--spacing-md); border: 1px solid var(--color-border, #e0e0e0); border-radius: var(--radius-md, 8px); background: <?= $isPast && $lesson['status'] !== 'agendada' ? '#f9fafb' : 'white' ?>; transition: all 0.2s;">
                            <div style="display: grid; gap: var(--spacing-xs);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--spacing-sm); flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 200px;">
                                        <div style="font-weight: 600; font-size: 1rem; margin-bottom: var(--spacing-xs);">
                                            <?= $lessonDate->format('d/m/Y') ?> às <?= $lessonDate->format('H:i') ?>
                                        </div>
                                        <div style="color: var(--color-text-muted, #666); font-size: 0.875rem; margin-bottom: var(--spacing-xs);">
                                            <?php if ($isInstrutor): ?>
                                                Aluno: <?= htmlspecialchars($lesson['student_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($lesson['instructor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($isInstrutor && !empty($lesson['vehicle_plate'])): ?>
                                        <div style="color: var(--color-text-muted, #666); font-size: 0.875rem;">
                                            Veículo: <?= htmlspecialchars($lesson['vehicle_plate']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: <?= $status['bg'] ?>; color: <?= $status['color'] ?>;">
                                            <?= $status['label'] ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="color: var(--color-text-muted, #666); font-size: 0.875rem; margin-top: var(--spacing-xs);">
                                    Aula Prática • <?= $lessonDate->format('H:i') ?> – <?= $endTime->format('H:i') ?>
                                </div>
                                <?php if ($isInstrutor): ?>
                                <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-sm); flex-wrap: wrap;">
                                    <a href="<?= base_path("agenda/{$lesson['id']}") ?>" class="btn btn-sm btn-outline" style="flex: 1; min-width: 120px; text-align: center;">
                                        Ver Detalhes
                                    </a>
                                    <?php 
                                    // Ações apenas para aulas futuras (não históricas)
                                    $isHistorical = in_array($lesson['status'], ['concluida', 'cancelada', 'no_show']);
                                    $isFuture = $currentTab === 'proximas' || (!$isHistorical && $currentTab === 'todas');
                                    if ($isFuture && !$isHistorical):
                                    ?>
                                        <?php if ($lesson['status'] === 'agendada'): ?>
                                        <a href="<?= base_path("agenda/{$lesson['id']}/iniciar") ?>" class="btn btn-sm btn-warning" style="flex: 1; min-width: 120px; text-align: center;">
                                            Iniciar Aula
                                        </a>
                                        <?php elseif ($lesson['status'] === 'em_andamento'): ?>
                                        <a href="<?= base_path("agenda/{$lesson['id']}/concluir") ?>" class="btn btn-sm btn-success" style="flex: 1; min-width: 120px; text-align: center;">
                                            Concluir Aula
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($viewType === 'week'): ?>
            <!-- Visualização Semanal -->
            <?php
            // Nomes dos dias em português (PT-BR)
            $dayNames = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];
            
            // Semana inicia no domingo (0 = domingo)
            $dateObj = new \DateTime($startDate);
            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $days[] = clone $dateObj;
                $dateObj->modify('+1 day');
            }
            
            // Configurações da grade
            $startHour = 7; // 7:00
            $endHour = 22; // 22:00
            $totalMinutes = ($endHour - $startHour) * 60; // Total de minutos do dia (900 min = 15 horas)
            $pixelsPerMinute = 2; // 2px por minuto (120px por hora) - ideal para leitura
            $dayColumnHeight = $totalMinutes * $pixelsPerMinute;
            $hourHeight = 60 * $pixelsPerMinute; // Altura de cada hora
            ?>
            <div class="calendar-week">
                <div class="calendar-week-header">
                    <div class="calendar-hour-col"></div>
                    <?php foreach ($days as $index => $day): ?>
                    <div class="calendar-day-header">
                        <div class="calendar-day-name"><?= $dayNames[$index] ?></div>
                        <div class="calendar-day-number"><?= $day->format('d/m') ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="calendar-week-body">
                    <!-- Coluna de horas -->
                    <div class="calendar-hours-col">
                        <?php for ($h = $startHour; $h <= $endHour; $h++): ?>
                        <div class="calendar-hour-mark" style="height: <?= $hourHeight ?>px;">
                            <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Colunas dos dias -->
                    <?php foreach ($days as $day): ?>
                    <div class="calendar-day-column" style="height: <?= $dayColumnHeight ?>px;">
                        <?php
                        $dayStr = $day->format('Y-m-d');
                        $dayLessons = array_filter($lessons, function($lesson) use ($dayStr) {
                            return $lesson['scheduled_date'] === $dayStr;
                        });
                        
                        foreach ($dayLessons as $lesson):
                            // Calcular posição e altura baseado em minutos
                            $lessonTime = strtotime($lesson['scheduled_time']);
                            $lessonHour = (int)date('H', $lessonTime);
                            $lessonMinute = (int)date('i', $lessonTime);
                            
                            // Minutos desde o início do dia (7:00)
                            $minutesFromStart = (($lessonHour - $startHour) * 60) + $lessonMinute;
                            
                            // Altura proporcional à duração
                            $durationMinutes = (int)$lesson['duration_minutes'];
                            $height = $durationMinutes * $pixelsPerMinute;
                            
                            // Posição top
                            $top = $minutesFromStart * $pixelsPerMinute;
                            
                            // Calcular horário de término
                            $startDateTime = new \DateTime($lesson['scheduled_date'] . ' ' . $lesson['scheduled_time']);
                            $endDateTime = clone $startDateTime;
                            $endDateTime->modify("+{$durationMinutes} minutes");
                            $startTime = $startDateTime->format('H:i');
                            $endTime = $endDateTime->format('H:i');
                            
                            $statusClass = [
                                'agendada' => 'lesson-scheduled',
                                'em_andamento' => 'lesson-in-progress',
                                'concluida' => 'lesson-completed',
                                'cancelada' => 'lesson-cancelled',
                                'no_show' => 'lesson-no-show'
                            ][$lesson['status']] ?? 'lesson-scheduled';
                        ?>
                        <a href="<?= base_path("agenda/{$lesson['id']}") ?>" 
                           class="lesson-card <?= $statusClass ?>" 
                           style="position: absolute; top: <?= $top ?>px; height: <?= $height ?>px; width: calc(100% - 8px); left: 4px; margin-bottom: 2px;"
                           title="<?= htmlspecialchars($lesson['student_name'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($lesson['instructor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="lesson-card-time"><?= $startTime ?> – <?= $endTime ?></div>
                            <div class="lesson-card-title"><?= htmlspecialchars($lesson['student_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lesson-card-instructor"><?= htmlspecialchars($lesson['instructor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($lesson['vehicle_plate'] ?? ''): ?>
                            <div class="lesson-card-vehicle"><?= htmlspecialchars($lesson['vehicle_plate'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($lesson['status'] === 'cancelada'): ?>
                            <div class="lesson-card-status">Cancelada</div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Visualização Diária -->
            <div class="calendar-day">
                <div class="calendar-day-header">
                    <?php
                    $dateObj = new \DateTime($date);
                    $dayNames = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
                    $dayName = $dayNames[(int)$dateObj->format('w')];
                    ?>
                    <h2><?= date('d/m/Y', strtotime($date)) ?> - <?= $dayName ?></h2>
                </div>
                
                <div class="calendar-day-body">
                    <?php
                    $startHour = 7;
                    $endHour = 22;
                    $totalMinutes = ($endHour - $startHour) * 60;
                    $pixelsPerMinute = 2; // 2px por minuto (120px por hora) - ideal para leitura
                    $dayColumnHeight = $totalMinutes * $pixelsPerMinute;
                    
                    $dayLessons = array_filter($lessons, function($lesson) use ($date) {
                        return $lesson['scheduled_date'] === $date;
                    });
                    ?>
                    <div class="calendar-day-timeline" style="position: relative; height: <?= $dayColumnHeight ?>px;">
                        <!-- Marcações de hora -->
                        <?php for ($h = $startHour; $h <= $endHour; $h++): ?>
                        <div class="calendar-day-hour-mark" style="position: absolute; top: <?= (($h - $startHour) * 60) * $pixelsPerMinute ?>px; width: 100%; border-top: 1px solid var(--color-border, #e0e0e0);">
                            <div class="calendar-day-hour-label" style="position: absolute; left: 0; top: -12px; padding: 0 var(--spacing-sm); background: white;">
                                <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00
                            </div>
                        </div>
                        <?php endfor; ?>
                        
                        <!-- Aulas -->
                        <?php foreach ($dayLessons as $lesson):
                            $lessonTime = strtotime($lesson['scheduled_time']);
                            $lessonHour = (int)date('H', $lessonTime);
                            $lessonMinute = (int)date('i', $lessonTime);
                            
                            $minutesFromStart = (($lessonHour - $startHour) * 60) + $lessonMinute;
                            $durationMinutes = (int)$lesson['duration_minutes'];
                            $height = $durationMinutes * $pixelsPerMinute;
                            $top = $minutesFromStart * $pixelsPerMinute;
                            
                            // Calcular horário de término
                            $startDateTime = new \DateTime($lesson['scheduled_date'] . ' ' . $lesson['scheduled_time']);
                            $endDateTime = clone $startDateTime;
                            $endDateTime->modify("+{$durationMinutes} minutes");
                            $startTime = $startDateTime->format('H:i');
                            $endTime = $endDateTime->format('H:i');
                            
                            $statusClass = [
                                'agendada' => 'lesson-scheduled',
                                'em_andamento' => 'lesson-in-progress',
                                'concluida' => 'lesson-completed',
                                'cancelada' => 'lesson-cancelled',
                                'no_show' => 'lesson-no-show'
                            ][$lesson['status']] ?? 'lesson-scheduled';
                        ?>
                        <a href="<?= base_path("agenda/{$lesson['id']}") ?>" 
                           class="lesson-card <?= $statusClass ?>" 
                           style="position: absolute; top: <?= $top ?>px; height: <?= $height ?>px; width: calc(100% - 200px); left: 100px; margin-bottom: 2px;">
                            <div class="lesson-card-time"><?= $startTime ?> – <?= $endTime ?></div>
                            <div class="lesson-card-title"><?= htmlspecialchars($lesson['student_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lesson-card-instructor"><?= htmlspecialchars($lesson['instructor_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($lesson['vehicle_plate'] ?? ''): ?>
                            <div class="lesson-card-vehicle"><?= htmlspecialchars($lesson['vehicle_plate'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if ($lesson['status'] === 'cancelada'): ?>
                            <div class="lesson-card-status">Cancelada</div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.calendar-week {
    overflow-x: auto;
}

.calendar-week-header {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    border-bottom: 2px solid var(--color-border, #e0e0e0);
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
}

.calendar-hour-col {
    padding: var(--spacing-sm);
    font-weight: 600;
    text-align: center;
    border-right: 1px solid var(--color-border, #e0e0e0);
}

.calendar-day-header {
    padding: var(--spacing-sm);
    text-align: center;
    border-right: 1px solid var(--color-border, #e0e0e0);
}

.calendar-day-name {
    font-size: 0.875rem;
    color: var(--color-text-muted, #666);
    text-transform: uppercase;
    font-weight: 600;
}

.calendar-day-number {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: var(--spacing-xs);
}

.calendar-week-body {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    position: relative;
}

.calendar-hours-col {
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--color-border, #e0e0e0);
}

.calendar-hour-mark {
    padding: var(--spacing-xs);
    font-size: 0.75rem;
    font-weight: 600;
    text-align: center;
    color: var(--color-text-muted, #666);
    border-bottom: 1px solid var(--color-border, #e0e0e0);
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-day-column {
    position: relative;
    border-right: 1px solid var(--color-border, #e0e0e0);
    border-bottom: 1px solid var(--color-border, #e0e0e0);
}

.calendar-day {
    max-width: 1000px;
    margin: 0 auto;
}

.calendar-day-header h2 {
    margin-bottom: var(--spacing-md);
    text-align: center;
}

.calendar-day-body {
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--radius-md, 8px);
    overflow: hidden;
    background: white;
}

.calendar-day-timeline {
    position: relative;
    padding-left: 80px;
}

.calendar-day-hour-mark {
    position: absolute;
    width: 100%;
}

.calendar-day-hour-label {
    position: absolute;
    left: 0;
    top: -12px;
    padding: 0 var(--spacing-sm);
    background: white;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--color-text-muted, #666);
}

.lesson-card {
    display: block;
    padding: 4px 6px;
    border-radius: var(--radius-sm, 4px);
    text-decoration: none;
    color: inherit;
    border-left: 3px solid;
    background: var(--color-bg-secondary, #f5f5f5);
    transition: all 0.2s;
    font-size: 0.75rem;
    overflow: hidden;
    box-sizing: border-box;
    z-index: 5;
}

.lesson-card:hover {
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transform: translateX(2px);
}

.lesson-scheduled {
    border-left-color: #3b82f6;
    background: #eff6ff;
}

.lesson-in-progress {
    border-left-color: #f59e0b;
    background: #fffbeb;
}

.lesson-completed {
    border-left-color: #10b981;
    background: #f0fdf4;
}

.lesson-cancelled {
    border-left-color: #ef4444;
    background: #fef2f2;
    opacity: 0.7;
}

.lesson-no-show {
    border-left-color: #6b7280;
    background: #f9fafb;
    opacity: 0.7;
}

.lesson-card-time {
    font-weight: 600;
    font-size: 0.7rem;
    color: var(--color-text-muted, #666);
    margin-bottom: 2px;
}

.lesson-card-title {
    font-weight: 600;
    margin: 2px 0;
    font-size: 0.8rem;
    line-height: 1.2;
}

.lesson-card-instructor,
.lesson-card-vehicle {
    font-size: 0.7rem;
    color: var(--color-text-muted, #666);
    margin-top: 2px;
    line-height: 1.2;
}

.lesson-card-status {
    font-size: 0.65rem;
    color: #ef4444;
    font-weight: 600;
    margin-top: 2px;
}

/* Estilos para abas do instrutor (desktop e mobile) */
.instructor-tabs {
    position: relative;
}

.instructor-tab {
    cursor: pointer;
    user-select: none;
}

.instructor-tab:hover {
    color: var(--color-primary, #3b82f6) !important;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-sm, 4px) var(--radius-sm, 4px) 0 0;
}

.instructor-tab.active {
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-sm, 4px) var(--radius-sm, 4px) 0 0;
}

/* Estilos mobile para abas do instrutor */
@media (max-width: 768px) {
    .instructor-tabs {
        gap: 0 !important;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .instructor-tabs::-webkit-scrollbar {
        display: none;
    }
    
    .instructor-tab {
        padding: var(--spacing-sm) var(--spacing-md) !important;
        min-width: auto;
        flex: 1;
        text-align: center;
        font-size: 0.875rem;
    }
}
</style>

<script>
function navigateDate(direction) {
    const dateInput = document.querySelector('input[name="date"]');
    const currentDate = new Date(dateInput.value);
    const viewType = '<?= $viewType ?>';
    
    if (direction === 0) {
        // Hoje
        currentDate.setTime(Date.now());
    } else {
        // Navegar
        const days = viewType === 'week' ? 7 : 1;
        currentDate.setDate(currentDate.getDate() + (direction * days));
    }
    
    dateInput.value = currentDate.toISOString().split('T')[0];
    document.getElementById('filtersForm').submit();
}
</script>
