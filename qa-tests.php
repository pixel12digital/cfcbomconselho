<?php
/**
 * Script de Testes QA - Sistema de Agendamento CFC
 * Executa testes automatizados conforme checklist
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/guards/AgendamentoGuards.php';
require_once __DIR__ . '/includes/guards/AgendamentoPermissions.php';
require_once __DIR__ . '/includes/guards/AgendamentoAuditoria.php';
require_once __DIR__ . '/includes/services/SistemaNotificacoes.php';

class QATester {
    private $db;
    private $guards;
    private $permissions;
    private $auditoria;
    private $notificacoes;
    private $testes = [];
    private $resultados = [];

    public function __construct() {
        $this->db = db();
        $this->guards = new AgendamentoGuards();
        $this->permissions = new AgendamentoPermissions();
        $this->auditoria = new AgendamentoAuditoria();
        $this->notificacoes = new SistemaNotificacoes();
    }

    public function executarTodosTestes() {
        echo "<h1>🧪 RELATÓRIO DE TESTES QA - SISTEMA DE AGENDAMENTO CFC</h1>\n";
        echo "<p>Executado em: " . date('d/m/Y H:i:s') . "</p>\n";
        echo "<hr>\n";

        $this->testarPermissoes();
        $this->testarGuards();
        $this->testarConflitos();
        $this->testarAuditoria();
        $this->testarNotificacoes();
        $this->testarAPIs();
        $this->testarInterfaces();

        $this->gerarRelatorioFinal();
    }

    private function testarPermissoes() {
        echo "<h2>🔐 TESTES DE PERMISSÕES</h2>\n";
        
        // Simular usuário admin para teste
        $_SESSION['user_id'] = 1;
        $_SESSION['user_tipo'] = 'admin';
        
        // Teste 1: Admin pode criar agendamento
        $this->adicionarTeste(
            'Admin pode criar agendamento',
            $this->permissions->podeCriarAgendamento()['permitido'] === true,
            'Admin deve poder criar agendamentos'
        );

        // Simular usuário instrutor para teste
        $_SESSION['user_tipo'] = 'instrutor';
        
        // Teste 2: Instrutor não pode criar agendamento
        $this->adicionarTeste(
            'Instrutor não pode criar agendamento',
            $this->permissions->podeCriarAgendamento()['permitido'] === false,
            'Instrutor não deve poder criar agendamentos'
        );

        // Simular usuário aluno para teste
        $_SESSION['user_tipo'] = 'aluno';
        
        // Teste 3: Aluno não pode criar agendamento
        $this->adicionarTeste(
            'Aluno não pode criar agendamento',
            $this->permissions->podeCriarAgendamento()['permitido'] === false,
            'Aluno não deve poder criar agendamentos'
        );

        echo "<p>✅ Testes de permissões concluídos</p>\n";
    }

    private function testarGuards() {
        echo "<h2>🛡️ TESTES DE GUARDAS DE NEGÓCIO</h2>\n";
        
        // Dados de teste
        $dadosAula = [
            'aluno_id' => 1,
            'instrutor_id' => 1,
            'veiculo_id' => 1,
            'tipo_aula' => 'teorica',
            'data_aula' => date('Y-m-d', strtotime('+1 day')),
            'hora_inicio' => '14:00:00',
            'hora_fim' => '14:50:00',
            'disciplina' => 'Legislação',
            'observacoes' => 'Teste QA'
        ];

        // Teste 1: Validação completa
        $validacao = $this->guards->validarAgendamentoCompleto($dadosAula);
        $this->adicionarTeste(
            'Validação completa de agendamento',
            $validacao['valido'] === true,
            'Agendamento deve ser válido: ' . $validacao['motivo']
        );

        // Teste 2: Conflito de horário
        $dadosConflito = $dadosAula;
        $dadosConflito['hora_inicio'] = '14:30:00';
        $dadosConflito['hora_fim'] = '15:20:00';
        
        $validacaoConflito = $this->guards->validarAgendamentoCompleto($dadosConflito);
        $this->adicionarTeste(
            'Detecção de conflito de horário',
            $validacaoConflito['valido'] === false,
            'Deve detectar conflito: ' . $validacaoConflito['motivo']
        );

        echo "<p>✅ Testes de guardas concluídos</p>\n";
    }

    private function testarConflitos() {
        echo "<h2>⚠️ TESTES DE CONFLITOS</h2>\n";
        
        // Teste 1: Verificar se existem aulas com conflitos
        $conflitos = $this->db->fetchAll("
            SELECT a1.id as aula1, a2.id as aula2, a1.data_aula, a1.hora_inicio, a1.hora_fim
            FROM aulas a1
            JOIN aulas a2 ON a1.id != a2.id 
                AND a1.data_aula = a2.data_aula
                AND a1.status != 'cancelada' 
                AND a2.status != 'cancelada'
                AND (
                    (a1.hora_inicio < a2.hora_fim AND a1.hora_fim > a2.hora_inicio)
                    OR (a1.instrutor_id = a2.instrutor_id)
                    OR (a1.aluno_id = a2.aluno_id)
                )
            LIMIT 5
        ");

        $this->adicionarTeste(
            'Verificação de conflitos existentes',
            count($conflitos) === 0,
            'Não deve haver conflitos no banco: ' . count($conflitos) . ' encontrados'
        );

        // Teste 2: Verificar limite de aulas por instrutor/dia
        $limiteAulas = $this->db->fetchAll("
            SELECT instrutor_id, data_aula, COUNT(*) as total
            FROM aulas 
            WHERE status != 'cancelada'
            GROUP BY instrutor_id, data_aula
            HAVING COUNT(*) > 3
        ");

        $this->adicionarTeste(
            'Verificação de limite de aulas por instrutor/dia',
            count($limiteAulas) === 0,
            'Instrutores não devem ter mais de 3 aulas por dia: ' . count($limiteAulas) . ' violações'
        );

        echo "<p>✅ Testes de conflitos concluídos</p>\n";
    }

    private function testarAuditoria() {
        echo "<h2>📝 TESTES DE AUDITORIA</h2>\n";
        
        // Teste 1: Verificar se tabela de logs existe
        $tabelaLogs = $this->db->fetch("SHOW TABLES LIKE 'logs'");
        $this->adicionarTeste(
            'Tabela de logs existe',
            !empty($tabelaLogs),
            'Tabela de logs deve existir para auditoria'
        );

        // Teste 2: Verificar se há logs de agendamento
        $logsAgendamento = $this->db->fetchAll("
            SELECT * FROM logs 
            WHERE acao IN ('criar_agendamento', 'alterar_agendamento', 'cancelar_agendamento')
            ORDER BY criado_em DESC 
            LIMIT 5
        ");

        $this->adicionarTeste(
            'Logs de agendamento existem',
            count($logsAgendamento) > 0,
            'Deve haver logs de agendamento: ' . count($logsAgendamento) . ' encontrados'
        );

        // Teste 3: Verificar estrutura dos logs
        if (!empty($logsAgendamento)) {
            $log = $logsAgendamento[0];
            $camposObrigatorios = ['usuario_id', 'acao', 'tabela', 'registro_id', 'criado_em'];
            $camposPresentes = array_keys($log);
            $camposFaltando = array_diff($camposObrigatorios, $camposPresentes);

            $this->adicionarTeste(
                'Estrutura dos logs está correta',
                empty($camposFaltando),
                'Logs devem ter todos os campos obrigatórios: ' . implode(', ', $camposFaltando)
            );
        }

        echo "<p>✅ Testes de auditoria concluídos</p>\n";
    }

    private function testarNotificacoes() {
        echo "<h2>📧 TESTES DE NOTIFICAÇÕES</h2>\n";
        
        // Teste 1: Verificar se tabela de notificações existe
        $tabelaNotificacoes = $this->db->fetch("SHOW TABLES LIKE 'notificacoes'");
        $this->adicionarTeste(
            'Tabela de notificações existe',
            !empty($tabelaNotificacoes),
            'Tabela de notificações deve existir'
        );

        // Teste 2: Verificar se há notificações
        $notificacoes = $this->db->fetchAll("
            SELECT * FROM notificacoes 
            ORDER BY criado_em DESC 
            LIMIT 5
        ");

        $this->adicionarTeste(
            'Notificações existem',
            count($notificacoes) > 0,
            'Deve haver notificações: ' . count($notificacoes) . ' encontradas'
        );

        // Teste 3: Verificar estrutura das notificações
        if (!empty($notificacoes)) {
            $notificacao = $notificacoes[0];
            $camposObrigatorios = ['usuario_id', 'tipo_usuario', 'tipo_notificacao', 'titulo', 'mensagem', 'criado_em'];
            $camposPresentes = array_keys($notificacao);
            $camposFaltando = array_diff($camposObrigatorios, $camposPresentes);

            $this->adicionarTeste(
                'Estrutura das notificações está correta',
                empty($camposFaltando),
                'Notificações devem ter todos os campos obrigatórios: ' . implode(', ', $camposFaltando)
            );
        }

        echo "<p>✅ Testes de notificações concluídos</p>\n";
    }

    private function testarAPIs() {
        echo "<h2>🔌 TESTES DE APIs</h2>\n";
        
        // Teste 1: Verificar se API de agendamento existe
        $apiAgendamento = file_exists(__DIR__ . '/admin/api/agendamento.php');
        $this->adicionarTeste(
            'API de agendamento existe',
            $apiAgendamento,
            'Arquivo admin/api/agendamento.php deve existir'
        );

        // Teste 2: Verificar se API de notificações existe
        $apiNotificacoes = file_exists(__DIR__ . '/admin/api/notificacoes.php');
        $this->adicionarTeste(
            'API de notificações existe',
            $apiNotificacoes,
            'Arquivo admin/api/notificacoes.php deve existir'
        );

        // Teste 3: Verificar se API de solicitações existe
        $apiSolicitacoes = file_exists(__DIR__ . '/admin/api/solicitacoes.php');
        $this->adicionarTeste(
            'API de solicitações existe',
            $apiSolicitacoes,
            'Arquivo admin/api/solicitacoes.php deve existir'
        );

        echo "<p>✅ Testes de APIs concluídos</p>\n";
    }

    private function testarInterfaces() {
        echo "<h2>🖥️ TESTES DE INTERFACES</h2>\n";
        
        // Teste 1: Verificar se dashboard do aluno existe
        $dashboardAluno = file_exists(__DIR__ . '/aluno/dashboard.php');
        $this->adicionarTeste(
            'Dashboard do aluno existe',
            $dashboardAluno,
            'Arquivo aluno/dashboard.php deve existir'
        );

        // Teste 2: Verificar se dashboard do instrutor existe
        $dashboardInstrutor = file_exists(__DIR__ . '/instrutor/dashboard.php');
        $this->adicionarTeste(
            'Dashboard do instrutor existe',
            $dashboardInstrutor,
            'Arquivo instrutor/dashboard.php deve existir'
        );

        // Teste 3: Verificar se interface de agendamento existe
        $interfaceAgendamento = file_exists(__DIR__ . '/admin/pages/agendamento-moderno.php');
        $this->adicionarTeste(
            'Interface de agendamento existe',
            $interfaceAgendamento,
            'Arquivo admin/pages/agendamento-moderno.php deve existir'
        );

        // Teste 4: Verificar se CSS mobile-first existe
        $cssMobile = file_exists(__DIR__ . '/assets/css/mobile-first.css');
        $this->adicionarTeste(
            'CSS mobile-first existe',
            $cssMobile,
            'Arquivo assets/css/mobile-first.css deve existir'
        );

        echo "<p>✅ Testes de interfaces concluídos</p>\n";
    }

    private function adicionarTeste($nome, $passou, $detalhes) {
        $this->testes[] = [
            'nome' => $nome,
            'passou' => $passou,
            'detalhes' => $detalhes,
            'timestamp' => date('H:i:s')
        ];

        $status = $passou ? '✅ PASS' : '❌ FAIL';
        echo "<p><strong>{$status}</strong> - {$nome}</p>\n";
        if (!$passou) {
            echo "<p style='color: red; margin-left: 20px;'>{$detalhes}</p>\n";
        }
    }

    private function gerarRelatorioFinal() {
        echo "<hr>\n";
        echo "<h2>📊 RELATÓRIO FINAL</h2>\n";
        
        $totalTestes = count($this->testes);
        $testesPassaram = array_filter($this->testes, function($teste) {
            return $teste['passou'];
        });
        $testesFalharam = array_filter($this->testes, function($teste) {
            return !$teste['passou'];
        });

        $percentualSucesso = ($totalTestes > 0) ? round((count($testesPassaram) / $totalTestes) * 100, 2) : 0;

        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>\n";
        echo "<h3>Resumo dos Testes</h3>\n";
        echo "<p><strong>Total de Testes:</strong> {$totalTestes}</p>\n";
        echo "<p><strong>Testes que Passaram:</strong> " . count($testesPassaram) . "</p>\n";
        echo "<p><strong>Testes que Falharam:</strong> " . count($testesFalharam) . "</p>\n";
        echo "<p><strong>Taxa de Sucesso:</strong> {$percentualSucesso}%</p>\n";
        echo "</div>\n";

        if (!empty($testesFalharam)) {
            echo "<h3>❌ Testes que Falharam</h3>\n";
            echo "<ul>\n";
            foreach ($testesFalharam as $teste) {
                echo "<li><strong>{$teste['nome']}</strong> - {$teste['detalhes']}</li>\n";
            }
            echo "</ul>\n";
        }

        if (!empty($testesPassaram)) {
            echo "<h3>✅ Testes que Passaram</h3>\n";
            echo "<ul>\n";
            foreach ($testesPassaram as $teste) {
                echo "<li><strong>{$teste['nome']}</strong></li>\n";
            }
            echo "</ul>\n";
        }

        // Status geral
        if ($percentualSucesso >= 90) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
            echo "<h3>🎉 SISTEMA APROVADO!</h3>\n";
            echo "<p>O sistema de agendamento está funcionando corretamente com {$percentualSucesso}% de sucesso nos testes.</p>\n";
            echo "</div>\n";
        } elseif ($percentualSucesso >= 70) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
            echo "<h3>⚠️ SISTEMA COM PROBLEMAS MENORES</h3>\n";
            echo "<p>O sistema está funcionando com {$percentualSucesso}% de sucesso, mas alguns ajustes são recomendados.</p>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
            echo "<h3>🚨 SISTEMA COM PROBLEMAS CRÍTICOS</h3>\n";
            echo "<p>O sistema precisa de correções urgentes. Apenas {$percentualSucesso}% dos testes passaram.</p>\n";
            echo "</div>\n";
        }

        echo "<hr>\n";
        echo "<p><em>Relatório gerado automaticamente pelo Sistema de Testes QA - CFC Bom Conselho</em></p>\n";
    }
}

// Executar testes se chamado diretamente
if (basename($_SERVER['PHP_SELF']) === 'qa-tests.php') {
    $tester = new QATester();
    $tester->executarTodosTestes();
}
?>
