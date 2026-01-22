<?php
/**
 * ExamesRulesService - Service centralizado para validações de aptidão (Exames & Provas)
 * 
 * Centraliza todas as regras que determinam se um aluno pode agendar:
 * - Prova teórica
 * - Aula prática
 * - Prova prática
 * 
 * Este service consolida lógica que estava espalhada em:
 * - includes/guards/AgendamentoGuards.php
 * - admin/includes/guards_exames.php
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2025-01-28
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

class ExamesRulesService {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Verifica se o aluno pode agendar prova teórica
     * 
     * Validações:
     * - Exame médico aprovado (apto)
     * - Exame psicotécnico aprovado (apto)
     * - Presença teórica: aluno deve estar matriculado em turma teórica válida
     * - Frequência mínima: aluno deve ter frequência >= frequência mínima da turma (ou 75% default)
     * 
     * @param int $alunoId ID do aluno
     * @return array ['ok' => bool, 'codigo' => string, 'mensagem' => string]
     */
    public function podeAgendarProvaTeorica(int $alunoId): array {
        try {
            // Verificar se aluno existe
            $aluno = $this->db->fetch("SELECT id FROM alunos WHERE id = ?", [$alunoId]);
            if (!$aluno) {
                return [
                    'ok' => false,
                    'codigo' => 'ALUNO_NAO_ENCONTRADO',
                    'mensagem' => 'Aluno não encontrado'
                ];
            }
            
            // ============================================
            // VALIDAÇÃO 1: Exames médico e psicotécnico
            // ============================================
            $exames = $this->db->fetchAll("
                SELECT tipo, status, resultado 
                FROM exames 
                WHERE aluno_id = ? AND status = 'concluido'
                AND tipo IN ('medico', 'psicotecnico')
                ORDER BY data_resultado DESC
            ", [$alunoId]);
            
            $medico = null;
            $psicotecnico = null;
            
            foreach ($exames as $exame) {
                if ($exame['tipo'] === 'medico' && !$medico) {
                    $medico = $exame;
                } elseif ($exame['tipo'] === 'psicotecnico' && !$psicotecnico) {
                    $psicotecnico = $exame;
                }
            }
            
            // Se não encontrou na tabela exames, tentar campos diretos em alunos
            if (!$medico || !$psicotecnico) {
                $alunoDetalhes = $this->db->fetch("
                    SELECT exame_medico, exame_psicologico 
                    FROM alunos WHERE id = ?
                ", [$alunoId]);
                
                if ($alunoDetalhes) {
                    if (!$medico && $alunoDetalhes['exame_medico']) {
                        $medico = [
                            'tipo' => 'medico',
                            'resultado' => $alunoDetalhes['exame_medico'],
                            'status' => 'concluido'
                        ];
                    }
                    if (!$psicotecnico && $alunoDetalhes['exame_psicologico']) {
                        $psicotecnico = [
                            'tipo' => 'psicotecnico',
                            'resultado' => $alunoDetalhes['exame_psicologico'],
                            'status' => 'concluido'
                        ];
                    }
                }
            }
            
            // Verificar se ambos estão aptos
            $medicoOK = $medico && ($medico['resultado'] === 'apto' || $medico['resultado'] === 'aprovado');
            $psicotecnicoOK = $psicotecnico && ($psicotecnico['resultado'] === 'apto' || $psicotecnico['resultado'] === 'aprovado');
            
            if (!$medicoOK || !$psicotecnicoOK) {
                $motivos = [];
                if (!$medicoOK) {
                    $statusMedico = $medico ? $medico['resultado'] : 'não realizado';
                    $motivos[] = "Exame médico: {$statusMedico}";
                }
                if (!$psicotecnicoOK) {
                    $statusPsico = $psicotecnico ? $psicotecnico['resultado'] : 'não realizado';
                    $motivos[] = "Exame psicológico: {$statusPsico}";
                }
                
                return [
                    'ok' => false,
                    'codigo' => 'EXAMES_INICIAIS_PENDENTES',
                    'mensagem' => 'Exames não aprovados: ' . implode(', ', $motivos)
                ];
            }
            
            // ============================================
            // VALIDAÇÃO 2: Presença teórica
            // ============================================
            // Buscar turmas teóricas do aluno (status válidos: matriculado, cursando, concluido)
            $turmasTeoricas = $this->db->fetchAll("
                SELECT 
                    tm.turma_id,
                    tm.frequencia_percentual,
                    tm.status as status_matricula,
                    tt.nome as turma_nome,
                    tt.status as turma_status
                FROM turma_matriculas tm
                JOIN turmas_teoricas tt ON tm.turma_id = tt.id
                WHERE tm.aluno_id = ?
                AND tm.status IN ('matriculado', 'cursando', 'concluido')
                AND tt.status IN ('ativa', 'completa', 'concluida')
                ORDER BY tm.data_matricula DESC
            ", [$alunoId]);
            
            // Se não tem nenhuma turma teórica válida
            if (empty($turmasTeoricas)) {
                return [
                    'ok' => false,
                    'codigo' => 'SEM_TURMA_TEORICA',
                    'mensagem' => 'Aluno não possui turma teórica válida para agendar a prova. É necessário estar matriculado e frequentar aulas teóricas antes de agendar a prova teórica.'
                ];
            }
            
            // Verificar frequência mínima
            // Frequência mínima padrão: 75% (pode ser configurado por turma no futuro)
            $frequenciaMinimaDefault = 75.0;
            
            $temFrequenciaSuficiente = false;
            $turmaComFrequenciaOK = null;
            $frequenciaAtual = 0;
            $frequenciaMinima = $frequenciaMinimaDefault;
            
            foreach ($turmasTeoricas as $turma) {
                $freqAtual = (float)($turma['frequencia_percentual'] ?? 0);
                
                // Se a turma tiver campo frequencia_minima, usar (futuro)
                // Por enquanto, usar default
                $freqMinima = $frequenciaMinimaDefault;
                
                if ($freqAtual >= $freqMinima) {
                    $temFrequenciaSuficiente = true;
                    $turmaComFrequenciaOK = $turma;
                    $frequenciaAtual = $freqAtual;
                    $frequenciaMinima = $freqMinima;
                    break; // Uma turma com frequência OK é suficiente
                } else {
                    // Guardar a maior frequência encontrada para mensagem de erro
                    if ($freqAtual > $frequenciaAtual) {
                        $frequenciaAtual = $freqAtual;
                        $frequenciaMinima = $freqMinima;
                    }
                }
            }
            
            if (!$temFrequenciaSuficiente) {
                return [
                    'ok' => false,
                    'codigo' => 'FREQUENCIA_INSUFICIENTE',
                    'mensagem' => "Frequência teórica abaixo do mínimo exigido. Frequência atual: " . number_format($frequenciaAtual, 1) . "%. Mínimo exigido: " . number_format($frequenciaMinima, 1) . "%. É necessário frequentar mais aulas teóricas antes de agendar a prova."
                ];
            }
            
            // Todas as validações passaram
            return [
                'ok' => true,
                'codigo' => 'EXAMES_E_PRESENCA_OK',
                'mensagem' => 'Exames aprovados e frequência teórica suficiente - pode agendar prova teórica'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar aptidão para prova teórica (aluno_id={$alunoId}): " . $e->getMessage());
            return [
                'ok' => false,
                'codigo' => 'ERRO_VERIFICACAO',
                'mensagem' => 'Erro ao verificar exames iniciais e presença teórica'
            ];
        }
    }
    
    /**
     * Verifica se o aluno pode agendar aula prática
     * 
     * Validações:
     * - Prova teórica aprovada
     * 
     * Nota: Outras validações (limite de aulas/dia, conflitos) devem ser feitas separadamente
     * 
     * @param int $alunoId ID do aluno
     * @return array ['ok' => bool, 'codigo' => string, 'mensagem' => string]
     */
    public function podeAgendarAulaPratica(int $alunoId): array {
        try {
            // Verificar se aluno existe
            $aluno = $this->db->fetch("SELECT id FROM alunos WHERE id = ?", [$alunoId]);
            if (!$aluno) {
                return [
                    'ok' => false,
                    'codigo' => 'ALUNO_NAO_ENCONTRADO',
                    'mensagem' => 'Aluno não encontrado'
                ];
            }
            
            // Buscar prova teórica (primeiro tenta concluída, depois verifica se há agendada)
            // Prioridade: tabela exames (tipo='teorico'), fallback para campo direto em alunos
            $provaTeorica = $this->db->fetch("
                SELECT tipo, status, resultado, data_resultado, data_agendada
                FROM exames 
                WHERE aluno_id = ? 
                AND tipo = 'teorico' 
                AND status = 'concluido'
                ORDER BY data_resultado DESC
                LIMIT 1
            ", [$alunoId]);
            
            // Se não encontrou prova concluída, verificar se há prova agendada
            if (!$provaTeorica) {
                $provaAgendada = $this->db->fetch("
                    SELECT tipo, status, resultado, data_agendada
                    FROM exames 
                    WHERE aluno_id = ? 
                    AND tipo = 'teorico' 
                    AND status = 'agendado'
                    ORDER BY data_agendada DESC
                    LIMIT 1
                ", [$alunoId]);
                
                if ($provaAgendada) {
                    // Prova está agendada mas não concluída
                    $dataFormatada = $provaAgendada['data_agendada'] ? 
                        date('d/m/Y', strtotime($provaAgendada['data_agendada'])) : 
                        'data não informada';
                    
                    return [
                        'ok' => false,
                        'codigo' => 'PROVA_TEORICA_AGENDADA_NAO_CONCLUIDA',
                        'mensagem' => "Prova teórica agendada para {$dataFormatada}, mas ainda não foi concluída. É necessário concluir e aprovar a prova teórica antes de agendar aulas práticas."
                    ];
                }
                
                // Se não encontrou na tabela exames, tentar campo direto em alunos
                $alunoDetalhes = $this->db->fetch("
                    SELECT resultado_prova_teorica, data_prova_teorica 
                    FROM alunos WHERE id = ?
                ", [$alunoId]);
                
                if ($alunoDetalhes && $alunoDetalhes['resultado_prova_teorica']) {
                    $provaTeorica = [
                        'tipo' => 'teorico',
                        'resultado' => $alunoDetalhes['resultado_prova_teorica'],
                        'status' => 'concluido',
                        'data_resultado' => $alunoDetalhes['data_prova_teorica']
                    ];
                }
            }
            
            // Se ainda não encontrou nenhuma prova teórica
            if (!$provaTeorica) {
                return [
                    'ok' => false,
                    'codigo' => 'PROVA_TEORICA_NAO_REALIZADA',
                    'mensagem' => 'Prova teórica não realizada. É necessário agendar e aprovar a prova teórica antes de agendar aulas práticas.'
                ];
            }
            
            // Verificar se prova teórica foi aprovada
            // Aceita tanto 'aprovado' quanto 'apto' como válidos
            $provaAprovada = $provaTeorica && 
                           ($provaTeorica['resultado'] === 'aprovado' || 
                            $provaTeorica['resultado'] === 'apto');
            
            if (!$provaAprovada) {
                $statusProva = $provaTeorica ? $provaTeorica['resultado'] : 'não realizada';
                
                // Mensagem específica baseada no resultado
                $mensagemErro = "Prova teórica concluída, mas resultado: '{$statusProva}'. É necessário que a prova teórica esteja aprovada para agendar aulas práticas.";
                
                return [
                    'ok' => false,
                    'codigo' => 'PROVA_TEORICA_NAO_APROVADA',
                    'mensagem' => $mensagemErro
                ];
            }
            
            return [
                'ok' => true,
                'codigo' => 'PROVA_TEORICA_APROVADA',
                'mensagem' => 'Prova teórica aprovada - pode agendar aulas práticas'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar prova teórica para aula prática (aluno_id={$alunoId}): " . $e->getMessage());
            return [
                'ok' => false,
                'codigo' => 'ERRO_VERIFICACAO',
                'mensagem' => 'Erro ao verificar prova teórica'
            ];
        }
    }
    
    /**
     * Verifica se o aluno pode agendar prova prática
     * 
     * Validações:
     * - Prova teórica aprovada
     * 
     * Nota: Outras validações (carga horária prática, etc.) devem ser feitas separadamente
     * 
     * @param int $alunoId ID do aluno
     * @return array ['ok' => bool, 'codigo' => string, 'mensagem' => string]
     */
    public function podeAgendarProvaPratica(int $alunoId): array {
        try {
            // Verificar se aluno existe
            $aluno = $this->db->fetch("SELECT id FROM alunos WHERE id = ?", [$alunoId]);
            if (!$aluno) {
                return [
                    'ok' => false,
                    'codigo' => 'ALUNO_NAO_ENCONTRADO',
                    'mensagem' => 'Aluno não encontrado'
                ];
            }
            
            // Usar a mesma lógica de verificação de prova teórica
            // Para agendar prova prática, o aluno deve ter prova teórica aprovada
            $resultadoProvaTeorica = $this->podeAgendarAulaPratica($alunoId);
            
            if (!$resultadoProvaTeorica['ok']) {
                // Adaptar mensagem para contexto de prova prática
                $mensagem = str_replace('aulas práticas', 'prova prática', $resultadoProvaTeorica['mensagem']);
                return [
                    'ok' => false,
                    'codigo' => $resultadoProvaTeorica['codigo'],
                    'mensagem' => $mensagem
                ];
            }
            
            return [
                'ok' => true,
                'codigo' => 'PROVA_TEORICA_APROVADA',
                'mensagem' => 'Prova teórica aprovada - pode agendar prova prática'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar aptidão para prova prática (aluno_id={$alunoId}): " . $e->getMessage());
            return [
                'ok' => false,
                'codigo' => 'ERRO_VERIFICACAO',
                'mensagem' => 'Erro ao verificar prova teórica'
            ];
        }
    }
}

