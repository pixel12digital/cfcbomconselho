<?php
// Verificar se a página está sendo acessada através do sistema de roteamento
if (!defined('ADMIN_ROUTING') && !isset($aluno)) {
    // Se não estiver sendo acessada via roteamento, redirecionar
    header('Location: ../index.php?page=agendar-aula&aluno_id=' . ($_GET['aluno_id'] ?? ''));
    exit;
}

// Verificação de variáveis (sem debug visual)

// Verificar se os dados necessários estão disponíveis
if (!isset($aluno) || !isset($instrutores) || !isset($veiculos)) {
    echo '<div class="alert alert-danger">Erro: Dados não carregados. <a href="?page=alunos">Voltar para Alunos</a></div>';
    return;
}
?>

<style>
    /* CSS específico para a página de agendamento */
    .schedule-container {
        max-width: 100%;
        padding: 0;
    }
    
    .student-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
    }
    
    .form-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .existing-lessons {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 15px;
        border-left: 4px solid #007bff;
    }
    
    .lesson-card {
        background: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        border-left: 3px solid #28a745;
    }
    
         .time-slot {
         background: #e9ecef;
         padding: 8px 12px;
         border-radius: 20px;
         font-size: 0.9em;
         color: #495057;
     }
     
     /* Radio buttons personalizados para melhor visibilidade */
     .custom-radio {
         margin-bottom: 0;
     }
     
     .custom-radio .form-check-input {
         width: 20px;
         height: 20px;
         margin-top: 0;
         border: 3px solid #dee2e6;
         background-color: white;
         cursor: pointer;
         transition: all 0.3s ease;
     }
     
     .custom-radio .form-check-input:checked {
         background-color: #007bff;
         border-color: #007bff;
         box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
         transform: scale(1.1);
     }
     
     .custom-radio .form-check-input:focus {
         box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
         border-color: #007bff;
     }
     
           .custom-radio .form-check-label {
          cursor: pointer;
          padding: 8px 0;
          margin-left: 8px;
      }
      
      .radio-text {
          display: flex;
          flex-direction: column;
          gap: 2px;
      }
      
      .radio-text strong {
          color: #495057;
          font-size: 14px;
          line-height: 1.2;
      }
      
      .radio-text small {
          color: #6c757d;
          font-size: 12px;
          line-height: 1.2;
      }
      
      .custom-radio .form-check-input:checked + .form-check-label .radio-text strong {
          color: #007bff;
          font-weight: 600;
      }
      
      /* Hover effects */
      .custom-radio:hover .form-check-input:not(:checked) {
          border-color: #adb5bd;
          transform: scale(1.05);
      }
</style>

<!-- Cabeçalho da Página -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-calendar me-2"></i>Agendar Aula - <?php echo htmlspecialchars($aluno['nome']); ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?page=alunos" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Alunos
        </a>
    </div>
</div>

        <!-- Informações do Aluno -->
        <div class="student-info">
            <div class="row">
                <div class="col-md-8">
                    <h4><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                    <p class="mb-1"><strong>CPF:</strong> <?php echo htmlspecialchars($aluno['cpf']); ?></p>
                    <p class="mb-1"><strong>CFC:</strong> <?php echo htmlspecialchars($cfc ? $cfc['nome'] : 'N/A'); ?></p>
                    <p class="mb-0"><strong>Status:</strong> 
                        <span class="badge bg-<?php echo $aluno['status'] === 'ativo' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($aluno['status']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50">
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulário de Agendamento -->
        <div class="form-section">
            <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>Nova Aula</h5>
            
                         <!-- Seleção de Tipo de Agendamento -->
             <div class="mb-4">
                 <label class="form-label fw-bold">Tipo de Agendamento:</label>
                 <div class="d-flex gap-3">
                     <div class="form-check custom-radio">
                         <input class="form-check-input" type="radio" name="tipo_agendamento" id="aula_unica" value="unica" checked>
                         <label class="form-check-label" for="aula_unica">
                             <div class="radio-text">
                                 <strong>1 Aula</strong>
                                 <small>50 minutos</small>
                             </div>
                         </label>
                     </div>
                     <div class="form-check custom-radio">
                         <input class="form-check-input" type="radio" name="tipo_agendamento" id="duas_aulas" value="duas">
                         <label class="form-check-label" for="duas_aulas">
                             <div class="radio-text">
                                 <strong>2 Aulas</strong>
                                 <small>1h 40min</small>
                             </div>
                         </label>
                     </div>
                     <div class="form-check custom-radio">
                         <input class="form-check-input" type="radio" name="tipo_agendamento" id="tres_aulas" value="tres">
                         <label class="form-check-label" for="tres_aulas">
                             <div class="radio-text">
                                 <strong>3 Aulas</strong>
                                 <small>2h 30min</small>
                             </div>
                         </label>
                     </div>
                 </div>
                 
                 <!-- Opções para 3 aulas -->
                 <div id="opcoesTresAulas" class="mt-3" style="display: none;">
                     <label class="form-label fw-bold">Posição do Intervalo:</label>
                     <div class="d-flex gap-3">
                         <div class="form-check custom-radio">
                             <input class="form-check-input" type="radio" name="posicao_intervalo" id="intervalo_depois" value="depois" checked>
                             <label class="form-check-label" for="intervalo_depois">
                                 <div class="radio-text">
                                     <strong>2 consecutivas + intervalo + 1 aula</strong>
                                     <small>Primeiro bloco, depois intervalo</small>
                                 </div>
                             </label>
                         </div>
                         <div class="form-check custom-radio">
                             <input class="form-check-input" type="radio" name="posicao_intervalo" id="intervalo_antes" value="antes">
                             <label class="form-check-label" for="intervalo_antes">
                                 <div class="radio-text">
                                     <strong>1 aula + intervalo + 2 consecutivas</strong>
                                     <small>Primeira aula, depois intervalo</small>
                                 </div>
                             </label>
                         </div>
                     </div>
                 </div>
                                 <small class="form-text text-muted">
                     <i class="fas fa-info-circle me-1"></i>
                     <strong>2 aulas:</strong> Consecutivas (1h 40min) | <strong>3 aulas:</strong> Escolha a posição do intervalo de 30min (2h 30min total)
                 </small>
            </div>
            
            <form id="formAgendamento" method="POST" action="../api/agendamento.php">
                                 <input type="hidden" name="aluno_id" value="<?php echo $_GET['aluno_id']; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="data_aula" class="form-label">Data da Aula *</label>
                        <input type="date" class="form-control" id="data_aula" name="data_aula" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="hora_inicio" class="form-label">Hora de Início *</label>
                        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                    </div>
                </div>
                
                <!-- Horários Calculados Automaticamente -->
                <div id="horariosCalculados" class="mb-3" style="display: none;">
                    <label class="form-label fw-bold">Horários Calculados:</label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-primary">1ª Aula</h6>
                                    <div id="hora1" class="fw-bold">--:--</div>
                                    <small class="text-muted">50 min</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4" id="coluna2" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-success">2ª Aula</h6>
                                    <div id="hora2" class="fw-bold">--:--</div>
                                    <small class="text-muted">50 min</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4" id="coluna3" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-warning">3ª Aula</h6>
                                    <div id="hora3" class="fw-bold">--:--</div>
                                    <small class="text-muted">50 min</small>
                                </div>
                            </div>
                        </div>
                    </div>
                                         <div id="intervaloInfo" class="mt-2 text-center" style="display: none;">
                         <span class="badge bg-info">
                             <i class="fas fa-clock me-1"></i>Intervalo de 30 minutos entre blocos de aulas
                         </span>
                     </div>
                </div>
                
                <div class="row">
                                         <div class="col-md-6 mb-3">
                         <label for="duracao" class="form-label">Duração da Aula *</label>
                         <div class="form-control-plaintext bg-light border rounded p-2">
                             <i class="fas fa-clock me-2 text-primary"></i>
                             <strong>50 minutos</strong>
                             <small class="text-muted ms-2">(duração fixa)</small>
                         </div>
                         <input type="hidden" id="duracao" name="duracao" value="50">
                     </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="tipo_aula" class="form-label">Tipo de Aula *</label>
                        <select class="form-select" id="tipo_aula" name="tipo_aula" required>
                            <option value="">Selecione...</option>
                            <option value="teorica">Teórica</option>
                            <option value="pratica">Prática</option>
                            <option value="simulador">Simulador</option>
                            <option value="avaliacao">Avaliação</option>
                        </select>
                    </div>
                </div>
                
                <!-- Campo Disciplina - Visível apenas para aulas teóricas -->
                <div id="campo_disciplina" class="row" style="display: none;">
                    <div class="col-md-6 mb-3">
                        <label for="disciplina" class="form-label">Disciplina *</label>
                        <select class="form-select" id="disciplina" name="disciplina">
                            <option value="">Selecione a disciplina...</option>
                            <option value="legislacao_transito">Legislação de Trânsito</option>
                            <option value="direcao_defensiva">Direção Defensiva</option>
                            <option value="primeiros_socorros">Primeiros Socorros</option>
                            <option value="meio_ambiente">Meio Ambiente e Cidadania</option>
                            <option value="mecanica_basica">Mecânica Básica</option>
                            <option value="sinalizacao">Sinalização de Trânsito</option>
                            <option value="etica_profissional">Ética Profissional</option>
                        </select>
                        <small class="form-text text-muted">Disciplina específica da aula teórica</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="instrutor_id" class="form-label">Instrutor *</label>
                        <select class="form-select" id="instrutor_id" name="instrutor_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($instrutores as $instrutor): ?>
                                <option value="<?php echo $instrutor['id']; ?>">
                                    <?php echo htmlspecialchars($instrutor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="veiculo_id" class="form-label">Veículo</label>
                        <select class="form-select" id="veiculo_id" name="veiculo_id">
                            <option value="">Selecione...</option>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <option value="<?php echo $veiculo['id']; ?>">
                                    <?php echo htmlspecialchars($veiculo['placa'] . ' - ' . $veiculo['modelo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Opcional para aulas teóricas</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                              placeholder="Observações sobre a aula..."></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Agendar Aula
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                </div>
            </form>
        </div>

        <!-- Aulas Existentes -->
        <?php if (isset($aulas_existentes) && !empty($aulas_existentes)): ?>
        <div class="existing-lessons">
            <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>Aulas Agendadas</h5>
            
            <?php foreach ($aulas_existentes as $aula): ?>
                <div class="lesson-card">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="time-slot">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="time-slot">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo $aula['hora_inicio']; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <strong><?php echo htmlspecialchars($aula['instrutor_nome'] ?? 'N/A'); ?></strong>
                            <br><small class="text-muted">Instrutor</small>
                        </div>
                        <div class="col-md-3">
                            <?php if ($aula['veiculo_placa']): ?>
                                <strong><?php echo htmlspecialchars($aula['veiculo_placa']); ?></strong>
                                <br><small class="text-muted">Veículo</small>
                            <?php else: ?>
                                <span class="badge bg-info">Teórica</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validação do formulário
        const form = document.getElementById('formAgendamento');
        const dataAula = document.getElementById('data_aula');
        const horaInicio = document.getElementById('hora_inicio');
        const duracao = document.getElementById('duracao');
        const tipoAula = document.getElementById('tipo_aula');
        const instrutor = document.getElementById('instrutor_id');
        const veiculo = document.getElementById('veiculo_id');
        
        // Definir data mínima como hoje
        const hoje = new Date().toISOString().split('T')[0];
        dataAula.min = hoje;
        
        // Validação em tempo real
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validarFormulario()) {
                // Enviar formulário
                enviarAgendamento();
            }
        });
        
        // Debug: verificar carregamento dos campos
        console.log('Veículos carregados:', veiculo.options.length);
        console.log('Estado inicial - Tipo de aula:', tipoAula.value);
        
        // Inicializar estado dos campos baseado no tipo de aula selecionado
        const campoDisciplina = document.getElementById('campo_disciplina');
        const disciplina = document.getElementById('disciplina');
        
        if (tipoAula.value === 'teorica') {
            // Aula teórica: mostrar disciplina, ocultar veículo
            campoDisciplina.style.display = 'block';
            disciplina.required = true;
            disciplina.disabled = false;
            
            veiculo.required = false;
            veiculo.disabled = true;
            veiculo.value = '';
            console.log('Inicialização: aula teórica - disciplina habilitada, veículo desabilitado');
        } else if (tipoAula.value !== '') {
            // Aula prática: ocultar disciplina, mostrar veículo
            campoDisciplina.style.display = 'none';
            disciplina.required = false;
            disciplina.disabled = true;
            disciplina.value = '';
            
            veiculo.required = true;
            veiculo.disabled = false;
            console.log('Inicialização: aula prática - disciplina desabilitada, veículo habilitado');
        }
        
        // Validação do tipo de aula vs veículo e disciplina
        tipoAula.addEventListener('change', function() {
            console.log('Tipo de aula alterado para:', this.value);
            const campoDisciplina = document.getElementById('campo_disciplina');
            const disciplina = document.getElementById('disciplina');
            
            if (this.value === 'teorica') {
                // Aula teórica: mostrar disciplina, ocultar veículo
                campoDisciplina.style.display = 'block';
                disciplina.required = true;
                disciplina.disabled = false;
                
                veiculo.required = false;
                veiculo.disabled = true;
                veiculo.value = '';
                console.log('Aula teórica: disciplina habilitada, veículo desabilitado');
            } else {
                // Aula prática: ocultar disciplina, mostrar veículo
                campoDisciplina.style.display = 'none';
                disciplina.required = false;
                disciplina.disabled = true;
                disciplina.value = '';
                
                veiculo.required = true;
                veiculo.disabled = false;
                console.log('Aula prática: disciplina desabilitada, veículo habilitado');
            }
        });
        
                 // Duração fixa de 50 minutos (campo hidden)
        
        // Elementos para horários calculados
        const horariosCalculados = document.getElementById('horariosCalculados');
        const coluna2 = document.getElementById('coluna2');
        const coluna3 = document.getElementById('coluna3');
        const intervaloInfo = document.getElementById('intervaloInfo');
        const hora1 = document.getElementById('hora1');
        const hora2 = document.getElementById('hora2');
        const hora3 = document.getElementById('hora3');
        
                 // Event listeners para tipo de agendamento
         document.querySelectorAll('input[name="tipo_agendamento"]').forEach(radio => {
             radio.addEventListener('change', function() {
                 // Mostrar/ocultar opções de intervalo para 3 aulas
                 const opcoesTresAulas = document.getElementById('opcoesTresAulas');
                 if (this.value === 'tres') {
                     opcoesTresAulas.style.display = 'block';
                 } else {
                     opcoesTresAulas.style.display = 'none';
                 }
                 calcularHorarios();
             });
         });
         
         // Event listeners para posição do intervalo
         document.querySelectorAll('input[name="posicao_intervalo"]').forEach(radio => {
             radio.addEventListener('change', calcularHorarios);
         });
        
        // Event listeners para data e hora
        dataAula.addEventListener('change', calcularHorarios);
        horaInicio.addEventListener('change', calcularHorarios);
        
        // Calcular horários automaticamente
        function calcularHorarios() {
            const tipoAgendamento = document.querySelector('input[name="tipo_agendamento"]:checked').value;
            const data = dataAula.value;
            const horaInicio = document.getElementById('hora_inicio').value;
            
            if (!data || !horaInicio) {
                horariosCalculados.style.display = 'none';
                return;
            }
            
            // Converter hora de início para minutos
            const [horas, minutos] = horaInicio.split(':').map(Number);
            let inicioMinutos = horas * 60 + minutos;
            
            // Calcular horários baseados no tipo
            switch (tipoAgendamento) {
                case 'unica':
                    // 1 aula: 50 minutos
                    const fim1 = inicioMinutos + 50;
                    hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor(fim1/60).toString().padStart(2,'0')}:${(fim1%60).toString().padStart(2,'0')}`;
                    
                    coluna2.style.display = 'none';
                    coluna3.style.display = 'none';
                    intervaloInfo.style.display = 'none';
                    horariosCalculados.style.display = 'block';
                    break;
                    
                case 'duas':
                    // 2 aulas consecutivas: 50 + 50 = 100 minutos
                    const fim2 = inicioMinutos + 100;
                    hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')}`;
                    hora2.textContent = `${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')} - ${Math.floor(fim2/60).toString().padStart(2,'0')}:${(fim2%60).toString().padStart(2,'0')}`;
                    
                    coluna2.style.display = 'block';
                    coluna3.style.display = 'none';
                    intervaloInfo.style.display = 'none';
                    horariosCalculados.style.display = 'block';
                    break;
                    
                case 'tres':
                    // 3 aulas com intervalo de 30min = 180 minutos total
                    const fim3 = inicioMinutos + 180;
                    const posicaoIntervalo = document.querySelector('input[name="posicao_intervalo"]:checked').value;
                    
                    if (posicaoIntervalo === 'depois') {
                        // 2 consecutivas + 30min intervalo + 1 aula
                        hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')}`;
                        hora2.textContent = `${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+100)/60).toString().padStart(2,'0')}:${((inicioMinutos+100)%60).toString().padStart(2,'0')}`;
                        hora3.textContent = `${Math.floor((inicioMinutos+130)/60).toString().padStart(2,'0')}:${((inicioMinutos+130)%60).toString().padStart(2,'0')} - ${Math.floor(fim3/60).toString().padStart(2,'0')}:${(fim3%60).toString().padStart(2,'0')}`;
                    } else {
                        // 1 aula + 30min intervalo + 2 consecutivas
                        hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')}`;
                        hora2.textContent = `${Math.floor((inicioMinutos+80)/60).toString().padStart(2,'0')}:${((inicioMinutos+80)%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+130)/60).toString().padStart(2,'0')}:${((inicioMinutos+130)%60).toString().padStart(2,'0')}`;
                        hora3.textContent = `${Math.floor((inicioMinutos+130)/60).toString().padStart(2,'0')}:${((inicioMinutos+130)%60).toString().padStart(2,'0')} - ${Math.floor(fim3/60).toString().padStart(2,'0')}:${(fim3%60).toString().padStart(2,'0')}`;
                    }
                    
                    coluna2.style.display = 'block';
                    coluna3.style.display = 'block';
                    intervaloInfo.style.display = 'block';
                    horariosCalculados.style.display = 'block';
                    break;
            }
        }
        
        function validarFormulario() {
            let valido = true;
            
            // Limpar mensagens de erro anteriores
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validar campos obrigatórios
            if (!dataAula.value) {
                dataAula.classList.add('is-invalid');
                valido = false;
            }
            
            if (!horaInicio.value) {
                horaInicio.classList.add('is-invalid');
                valido = false;
            }
            
            if (!duracao.value) {
                duracao.classList.add('is-invalid');
                valido = false;
            }
            
            if (!tipoAula.value) {
                tipoAula.classList.add('is-invalid');
                valido = false;
            }
            
            if (!instrutor.value) {
                instrutor.classList.add('is-invalid');
                valido = false;
            }
            
            // Validar disciplina para aulas teóricas
            if (tipoAula.value === 'teorica' && !disciplina.value) {
                disciplina.classList.add('is-invalid');
                valido = false;
            }
            
            // Validar veículo para aulas práticas
            if (tipoAula.value !== 'teorica' && !veiculo.value) {
                veiculo.classList.add('is-invalid');
                valido = false;
            }
            
            return valido;
        }
        
        // Verificar disponibilidade em tempo real
        horaInicio.addEventListener('change', function() {
            if (horaInicio.value && dataAula.value && instrutor.value) {
                verificarDisponibilidade();
            }
        });
        
        dataAula.addEventListener('change', function() {
            if (horaInicio.value && dataAula.value && instrutor.value) {
                verificarDisponibilidade();
            }
        });
        
        instrutor.addEventListener('change', function() {
            if (horaInicio.value && dataAula.value && instrutor.value) {
                verificarDisponibilidade();
            }
        });
        
        function verificarDisponibilidade() {
            const formData = new FormData();
            const tipoAgendamento = document.querySelector('input[name="tipo_agendamento"]:checked').value;
            
            formData.append('data_aula', dataAula.value);
            formData.append('hora_inicio', horaInicio.value);
            formData.append('duracao', '50');
            formData.append('instrutor_id', instrutor.value);
            formData.append('tipo_aula', tipoAula.value);
                         formData.append('tipo_agendamento', tipoAgendamento);
             
             // Adicionar posição do intervalo se for 3 aulas
             if (tipoAgendamento === 'tres') {
                 const posicaoIntervalo = document.querySelector('input[name="posicao_intervalo"]:checked').value;
                 formData.append('posicao_intervalo', posicaoIntervalo);
             }
             
             if (veiculo.value) formData.append('veiculo_id', veiculo.value);
            
            fetch('../api/verificar-disponibilidade.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    if (data.disponivel) {
                        // Limpar mensagens de erro
                        horaInicio.classList.remove('is-invalid');
                        horaInicio.classList.add('is-valid');
                        
                        // Mostrar mensagem de sucesso
                        mostrarMensagemDisponibilidade('Horário disponível!', 'success');
                    } else {
                        // Marcar como inválido
                        horaInicio.classList.remove('is-valid');
                        horaInicio.classList.add('is-invalid');
                        
                        // Mostrar mensagem de erro
                        mostrarMensagemDisponibilidade(data.mensagem, 'danger');
                        
                        // Mostrar horários alternativos se disponíveis
                        if (data.horarios_alternativos && data.horarios_alternativos.length > 0) {
                            mostrarHorariosAlternativos(data.horarios_alternativos);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao verificar disponibilidade:', error);
            });
        }
        
        function mostrarMensagemDisponibilidade(mensagem, tipo) {
            // Remover mensagem anterior
            const msgAnterior = document.querySelector('.msg-disponibilidade');
            if (msgAnterior) msgAnterior.remove();
            
            // Criar nova mensagem
            const msgDiv = document.createElement('div');
            msgDiv.className = `alert alert-${tipo} msg-disponibilidade mt-2`;
            msgDiv.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : 'exclamation-triangle'} me-2"></i>${mensagem}`;
            
            // Inserir após o campo de hora
            horaInicio.parentNode.appendChild(msgDiv);
        }
        
        function mostrarHorariosAlternativos(horarios) {
            // Remover sugestões anteriores
            const sugestoesAnteriores = document.querySelector('.horarios-alternativos');
            if (sugestoesAnteriores) sugestoesAnteriores.remove();
            
            // Criar div de sugestões
            const sugestoesDiv = document.createElement('div');
            sugestoesDiv.className = 'horarios-alternativos mt-3';
            sugestoesDiv.innerHTML = `
                <h6 class="text-muted">Horários alternativos disponíveis:</h6>
                <div class="d-flex flex-wrap gap-2">
                    ${horarios.map(h => `
                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                onclick="selecionarHorarioAlternativo('${h.hora_inicio}')">
                            ${h.hora_inicio} (${h.total_aulas} aula${h.total_aulas > 1 ? 's' : ''})
                        </button>
                    `).join('')}
                </div>
            `;
            
            // Inserir após a mensagem de disponibilidade
            const msgDisponibilidade = document.querySelector('.msg-disponibilidade');
            if (msgDisponibilidade) {
                msgDisponibilidade.parentNode.appendChild(sugestoesDiv);
            }
        }
        
        function selecionarHorarioAlternativo(hora) {
            horaInicio.value = hora;
            verificarDisponibilidade();
        }
        
        function enviarAgendamento() {
            const formData = new FormData(form);
            
            // Adicionar tipo de agendamento
            const tipoAgendamento = document.querySelector('input[name="tipo_agendamento"]:checked').value;
            formData.append('tipo_agendamento', tipoAgendamento);
            
            // Adicionar disciplina se for aula teórica
            if (tipoAula.value === 'teorica' && disciplina.value) {
                formData.append('disciplina', disciplina.value);
            }
            
            // Adicionar posição do intervalo se for 3 aulas
            if (tipoAgendamento === 'tres') {
                const posicaoIntervalo = document.querySelector('input[name="posicao_intervalo"]:checked').value;
                formData.append('posicao_intervalo', posicaoIntervalo);
            }
            
            // Mostrar loading
            const btnSubmit = form.querySelector('button[type="submit"]');
            const textoOriginal = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Agendando...';
            btnSubmit.disabled = true;
            
            fetch('../api/agendamento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    // Sucesso
                    mostrarMensagemSucesso('Aula agendada com sucesso!', data.dados);
                    
                    // Redirecionar após 3 segundos
                    setTimeout(() => {
                        window.location.href = '?page=alunos';
                    }, 3000);
                } else {
                    // Erro
                    mostrarMensagemErro('Erro ao agendar aula: ' + data.mensagem);
                    
                    // Reativar botão
                    btnSubmit.innerHTML = textoOriginal;
                    btnSubmit.disabled = false;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarMensagemErro('Erro ao agendar aula. Tente novamente.');
                
                // Reativar botão
                btnSubmit.innerHTML = textoOriginal;
                btnSubmit.disabled = false;
            });
        }
        
        function mostrarMensagemSucesso(mensagem, dados) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <h5><i class="fas fa-check-circle me-2"></i>${mensagem}</h5>
                <hr>
                <p><strong>Aluno:</strong> ${dados.aluno}</p>
                <p><strong>Instrutor:</strong> ${dados.instrutor}</p>
                <p><strong>Data:</strong> ${dados.data}</p>
                <p><strong>Total de Aulas:</strong> ${dados.total_aulas}</p>
                <p><strong>Tipo:</strong> ${dados.tipo}</p>
                ${dados.aulas_criadas ? `
                    <hr>
                    <h6><i class="fas fa-clock me-2"></i>Horários das Aulas:</h6>
                    ${dados.aulas_criadas.map((aula, index) => `
                        <p class="mb-1"><strong>${index + 1}ª Aula:</strong> ${aula.hora_inicio} - ${aula.hora_fim}</p>
                    `).join('')}
                ` : ''}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            form.parentNode.insertBefore(alertDiv, form.nextSibling);
        }
        
        function mostrarMensagemErro(mensagem) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Erro</h5>
                <p>${mensagem}</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            form.parentNode.insertBefore(alertDiv, form.nextSibling);
        }
    });
</script>
