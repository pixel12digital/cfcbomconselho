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
            
            // Buscar exames médico e psicotécnico concluídos
            // Prioridade: tabela exames, fallback para campos diretos em alunos
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
                    // Usar campos diretos como fallback
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
            // Exames OK = ambos aptos (inapto_temporario é considerado não apto)
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
            
            return [
                'ok' => true,
                'codigo' => 'EXAMES_OK',
                'mensagem' => 'Exames aprovados - pode agendar prova teórica'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar aptidão para prova teórica (aluno_id={$alunoId}): " . $e->getMessage());
            return [
                'ok' => false,
                'codigo' => 'ERRO_VERIFICACAO',
                'mensagem' => 'Erro ao verificar exames iniciais'
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
            
            // Buscar prova teórica aprovada
            // Prioridade: tabela exames (tipo='teorico'), fallback para campo direto em alunos
            $provaTeorica = $this->db->fetch("
                SELECT tipo, status, resultado, data_resultado
                FROM exames 
                WHERE aluno_id = ? 
                AND tipo = 'teorico' 
                AND status = 'concluido'
                ORDER BY data_resultado DESC
                LIMIT 1
            ", [$alunoId]);
            
            // Se não encontrou na tabela exames, tentar campo direto em alunos
            if (!$provaTeorica) {
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
            
            // Verificar se prova teórica foi aprovada
            // Aceita tanto 'aprovado' quanto 'apto' como válidos
            $provaAprovada = $provaTeorica && 
                           ($provaTeorica['resultado'] === 'aprovado' || 
                            $provaTeorica['resultado'] === 'apto');
            
            if (!$provaAprovada) {
                $statusProva = $provaTeorica ? $provaTeorica['resultado'] : 'não realizada';
                
                // Mensagem de erro: manter compatibilidade com formato atual
                return [
                    'ok' => false,
                    'codigo' => 'PROVA_TEORICA_NAO_APROVADA',
                    'mensagem' => "Prova teórica não aprovada: {$statusProva}"
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

