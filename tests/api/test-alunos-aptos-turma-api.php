<?php
/**
 * Testes Automatizados - API de Alunos Aptos para Turma Teórica
 * 
 * Arquivo: admin/api/alunos-aptos-turma-simples.php
 * 
 * CORREÇÃO ROBUSTA (12/12/2025): Testes para garantir que a API funciona corretamente
 * após implementação de status permitidos configuráveis e regras de CFC.
 * 
 * ESTRUTURA DE TESTE:
 * - Setup: Criar dados de teste (CFC, usuários, aluno, turma)
 * - Testes: Validar diferentes cenários
 * - Teardown: Limpar dados de teste (opcional, comentado por padrão)
 */

// Configuração de ambiente de teste
$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';

// Incluir helpers necessários
require_once $rootPath . '/admin/includes/guards_exames.php';
require_once $rootPath . '/admin/includes/FinanceiroAlunoHelper.php';

class TestAlunosAptosTurmaAPI {
    private $db;
    private $testCfcId;
    private $testAdminId;
    private $testInstrutorId;
    private $testAlunoId;
    private $testTurmaId;
    private $testVeiculoId;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Setup: Criar dados de teste
     */
    public function setup() {
        try {
            $this->db->beginTransaction();
            
            // 1. Criar CFC de teste
            $this->testCfcId = $this->db->insert('cfcs', [
                'nome' => 'CFC Teste API',
                'cnpj' => '00.000.000/0001-99',
                'ativo' => 1
            ]);
            
            // 2. Criar usuário admin de teste
            $adminHash = password_hash('teste123', PASSWORD_DEFAULT);
            $this->testAdminId = $this->db->insert('usuarios', [
                'nome' => 'Admin Teste API',
                'email' => 'admin-teste-api@teste.com',
                'senha' => $adminHash,
                'tipo' => 'admin',
                'cfc_id' => $this->testCfcId,
                'ativo' => 1
            ]);
            
            // 3. Criar aluno de teste com status 'ativo'
            $this->testAlunoId = $this->db->insert('alunos', [
                'nome' => 'Aluno Teste API',
                'cpf' => '111.222.333-44',
                'status' => 'ativo',
                'cfc_id' => $this->testCfcId
            ]);
            
            // 4. Criar matrícula ativa para o aluno
            $this->db->insert('matriculas', [
                'aluno_id' => $this->testAlunoId,
                'cfc_id' => $this->testCfcId,
                'status' => 'ativa',
                'data_inicio' => date('Y-m-d')
            ]);
            
            // 5. Criar exames médico e psicotécnico APTOS
            $this->db->insert('exames', [
                'aluno_id' => $this->testAlunoId,
                'tipo' => 'medico',
                'status' => 'concluido',
                'resultado' => 'apto',
                'data_resultado' => date('Y-m-d')
            ]);
            
            $this->db->insert('exames', [
                'aluno_id' => $this->testAlunoId,
                'tipo' => 'psicotecnico',
                'status' => 'concluido',
                'resultado' => 'apto',
                'data_resultado' => date('Y-m-d')
            ]);
            
            // 6. Criar fatura PAGA para o aluno (financeiro OK)
            $faturaId = $this->db->insert('financeiro_faturas', [
                'aluno_id' => $this->testAlunoId,
                'valor_total' => 1000.00,
                'data_vencimento' => date('Y-m-d', strtotime('+30 days')),
                'status' => 'paga'
            ]);
            
            $this->db->insert('pagamentos', [
                'fatura_id' => $faturaId,
                'valor_pago' => 1000.00,
                'data_pagamento' => date('Y-m-d')
            ]);
            
            // 7. Criar turma teórica de teste
            $this->testTurmaId = $this->db->insert('turmas_teoricas', [
                'nome' => 'Turma Teste API',
                'cfc_id' => $this->testCfcId,
                'curso_tipo' => 'AB',
                'status' => 'ativa',
                'data_inicio' => date('Y-m-d')
            ]);
            
            $this->db->commit();
            
            echo "✅ Setup concluído - CFC: {$this->testCfcId}, Aluno: {$this->testAlunoId}, Turma: {$this->testTurmaId}\n";
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("ERRO no setup: " . $e->getMessage());
        }
    }
    
    /**
     * Simular requisição HTTP para a API
     */
    private function makeRequest($turmaId, $userCfcId = null) {
        // Simular sessão
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Simular usuário logado
        if ($userCfcId !== null) {
            $_SESSION['user'] = [
                'id' => $this->testAdminId,
                'tipo' => 'admin',
                'cfc_id' => $userCfcId
            ];
        } else {
            $_SESSION['user'] = [
                'id' => $this->testAdminId,
                'tipo' => 'admin',
                'cfc_id' => $this->testCfcId
            ];
        }
        
        // Simular POST
        $_POST = ['turma_id' => $turmaId];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Capturar output
        ob_start();
        try {
            include __DIR__ . '/../../admin/api/alunos-aptos-turma-simples.php';
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $output = ob_get_clean();
        
        // Limpar sessão
        unset($_SESSION['user']);
        
        return json_decode($output, true);
    }
    
    /**
     * Teste 1: Admin de CFC - Aluno apto deve aparecer
     */
    public function testAdminCfc_AlunoApto_DeveAparecer() {
        echo "\n=== Teste 1: Admin CFC - Aluno Apto ===\n";
        
        $response = $this->makeRequest($this->testTurmaId, $this->testCfcId);
        
        if (!$response || !$response['sucesso']) {
            throw new Exception("API retornou erro: " . ($response['mensagem'] ?? 'Erro desconhecido'));
        }
        
        $alunos = $response['alunos'] ?? [];
        $encontrado = false;
        
        foreach ($alunos as $aluno) {
            if ((int)$aluno['id'] === $this->testAlunoId) {
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            throw new Exception("Aluno {$this->testAlunoId} não foi encontrado na lista de aptos");
        }
        
        echo "✅ PASS: Aluno apto apareceu na lista\n";
    }
    
    /**
     * Teste 2: Admin de CFC - Aluno concluído NÃO deve aparecer
     */
    public function testAdminCfc_AlunoConcluido_NaoDeveAparecer() {
        echo "\n=== Teste 2: Admin CFC - Aluno Concluído ===\n";
        
        // Alterar status do aluno para 'concluido'
        $this->db->update('alunos', ['status' => 'concluido'], 'id = ?', [$this->testAlunoId]);
        
        $response = $this->makeRequest($this->testTurmaId, $this->testCfcId);
        
        // Restaurar status
        $this->db->update('alunos', ['status' => 'ativo'], 'id = ?', [$this->testAlunoId]);
        
        if (!$response || !$response['sucesso']) {
            throw new Exception("API retornou erro: " . ($response['mensagem'] ?? 'Erro desconhecido'));
        }
        
        $alunos = $response['alunos'] ?? [];
        $encontrado = false;
        
        foreach ($alunos as $aluno) {
            if ((int)$aluno['id'] === $this->testAlunoId) {
                $encontrado = true;
                break;
            }
        }
        
        if ($encontrado) {
            throw new Exception("Aluno {$this->testAlunoId} com status 'concluido' apareceu na lista (não deveria)");
        }
        
        echo "✅ PASS: Aluno concluído não apareceu na lista\n";
    }
    
    /**
     * Teste 3: Admin Global - Aluno deve aparecer
     */
    public function testAdminGlobal_AlunoApto_DeveAparecer() {
        echo "\n=== Teste 3: Admin Global - Aluno Apto ===\n";
        
        // Admin global tem cfc_id = 0
        $response = $this->makeRequest($this->testTurmaId, 0);
        
        if (!$response || !$response['sucesso']) {
            throw new Exception("API retornou erro: " . ($response['mensagem'] ?? 'Erro desconhecido'));
        }
        
        $alunos = $response['alunos'] ?? [];
        $encontrado = false;
        
        foreach ($alunos as $aluno) {
            if ((int)$aluno['id'] === $this->testAlunoId) {
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            throw new Exception("Aluno {$this->testAlunoId} não foi encontrado na lista (admin global)");
        }
        
        echo "✅ PASS: Aluno apareceu na lista para admin global\n";
    }
    
    /**
     * Teste 4: Admin de outro CFC - Aluno NÃO deve aparecer
     */
    public function testAdminOutroCfc_Aluno_NaoDeveAparecer() {
        echo "\n=== Teste 4: Admin Outro CFC ===\n";
        
        // Criar outro CFC
        $outroCfcId = $this->db->insert('cfcs', [
            'nome' => 'CFC Outro Teste',
            'cnpj' => '00.000.000/0002-99',
            'ativo' => 1
        ]);
        
        try {
            // Tentar acessar turma de outro CFC (deve bloquear)
            $response = $this->makeRequest($this->testTurmaId, $outroCfcId);
            
            // Se chegou aqui, deveria ter bloqueado
            if ($response && $response['sucesso']) {
                throw new Exception("API permitiu acesso de admin de outro CFC (não deveria)");
            }
            
            echo "✅ PASS: API bloqueou acesso de admin de outro CFC\n";
            
        } finally {
            // Limpar CFC criado
            $this->db->delete('cfcs', 'id = ?', [$outroCfcId]);
        }
    }
    
    /**
     * Teste 5: Aluno sem exames OK - NÃO deve aparecer
     */
    public function testAlunoSemExamesOK_NaoDeveAparecer() {
        echo "\n=== Teste 5: Aluno Sem Exames OK ===\n";
        
        // Alterar exame médico para inapto
        $this->db->update('exames', 
            ['resultado' => 'inapto'], 
            'aluno_id = ? AND tipo = ?', 
            [$this->testAlunoId, 'medico']
        );
        
        $response = $this->makeRequest($this->testTurmaId, $this->testCfcId);
        
        // Restaurar exame
        $this->db->update('exames', 
            ['resultado' => 'apto'], 
            'aluno_id = ? AND tipo = ?', 
            [$this->testAlunoId, 'medico']
        );
        
        if (!$response || !$response['sucesso']) {
            throw new Exception("API retornou erro: " . ($response['mensagem'] ?? 'Erro desconhecido'));
        }
        
        $alunos = $response['alunos'] ?? [];
        $encontrado = false;
        
        foreach ($alunos as $aluno) {
            if ((int)$aluno['id'] === $this->testAlunoId) {
                $encontrado = true;
                break;
            }
        }
        
        if ($encontrado) {
            throw new Exception("Aluno sem exames OK apareceu na lista (não deveria)");
        }
        
        echo "✅ PASS: Aluno sem exames OK não apareceu na lista\n";
    }
    
    /**
     * Teste 6: Aluno já matriculado - NÃO deve aparecer
     */
    public function testAlunoJaMatriculado_NaoDeveAparecer() {
        echo "\n=== Teste 6: Aluno Já Matriculado ===\n";
        
        // Matricular aluno na turma
        $this->db->insert('turma_matriculas', [
            'aluno_id' => $this->testAlunoId,
            'turma_id' => $this->testTurmaId,
            'status' => 'matriculado',
            'data_matricula' => date('Y-m-d')
        ]);
        
        $response = $this->makeRequest($this->testTurmaId, $this->testCfcId);
        
        // Remover matrícula
        $this->db->delete('turma_matriculas', 
            'aluno_id = ? AND turma_id = ?', 
            [$this->testAlunoId, $this->testTurmaId]
        );
        
        if (!$response || !$response['sucesso']) {
            throw new Exception("API retornou erro: " . ($response['mensagem'] ?? 'Erro desconhecido'));
        }
        
        $alunos = $response['alunos'] ?? [];
        $encontrado = false;
        
        foreach ($alunos as $aluno) {
            if ((int)$aluno['id'] === $this->testAlunoId) {
                $encontrado = true;
                break;
            }
        }
        
        if ($encontrado) {
            throw new Exception("Aluno já matriculado apareceu na lista (não deveria)");
        }
        
        echo "✅ PASS: Aluno já matriculado não apareceu na lista\n";
    }
    
    /**
     * Teardown: Limpar dados de teste
     */
    public function teardown() {
        try {
            $this->db->beginTransaction();
            
            // Limpar na ordem correta (respeitando foreign keys)
            if ($this->testTurmaId) {
                $this->db->delete('turma_matriculas', 'turma_id = ?', [$this->testTurmaId]);
            }
            
            if ($this->testAlunoId) {
                $this->db->delete('pagamentos', 'fatura_id IN (SELECT id FROM financeiro_faturas WHERE aluno_id = ?)', [$this->testAlunoId]);
                $this->db->delete('financeiro_faturas', 'aluno_id = ?', [$this->testAlunoId]);
                $this->db->delete('exames', 'aluno_id = ?', [$this->testAlunoId]);
                $this->db->delete('matriculas', 'aluno_id = ?', [$this->testAlunoId]);
                $this->db->delete('alunos', 'id = ?', [$this->testAlunoId]);
            }
            
            if ($this->testTurmaId) {
                $this->db->delete('turmas_teoricas', 'id = ?', [$this->testTurmaId]);
            }
            
            if ($this->testAdminId) {
                $this->db->delete('usuarios', 'id = ?', [$this->testAdminId]);
            }
            
            if ($this->testCfcId) {
                $this->db->delete('cfcs', 'id = ?', [$this->testCfcId]);
            }
            
            $this->db->commit();
            
            echo "\n✅ Teardown concluído - Dados de teste removidos\n";
            
        } catch (Exception $e) {
            $this->db->rollback();
            echo "\n⚠️ ERRO no teardown: " . $e->getMessage() . "\n";
            echo "⚠️ Dados de teste podem ter ficado no banco. Limpe manualmente se necessário.\n";
        }
    }
    
    /**
     * Executar todos os testes
     */
    public function runAll() {
        echo "========================================\n";
        echo "TESTES - API Alunos Aptos Turma Teórica\n";
        echo "========================================\n";
        
        $tests = [
            'testAdminCfc_AlunoApto_DeveAparecer',
            'testAdminCfc_AlunoConcluido_NaoDeveAparecer',
            'testAdminGlobal_AlunoApto_DeveAparecer',
            'testAdminOutroCfc_Aluno_NaoDeveAparecer',
            'testAlunoSemExamesOK_NaoDeveAparecer',
            'testAlunoJaMatriculado_NaoDeveAparecer'
        ];
        
        $passed = 0;
        $failed = 0;
        
        try {
            $this->setup();
            
            foreach ($tests as $test) {
                try {
                    $this->$test();
                    $passed++;
                } catch (Exception $e) {
                    $failed++;
                    echo "❌ FAIL: {$test}\n";
                    echo "   Erro: " . $e->getMessage() . "\n";
                }
            }
            
        } finally {
            // Descomentar para limpar dados após testes
            // $this->teardown();
        }
        
        echo "\n========================================\n";
        echo "RESUMO: {$passed} passou, {$failed} falhou\n";
        echo "========================================\n";
        
        return $failed === 0;
    }
}

// Executar testes se chamado diretamente
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $tester = new TestAlunosAptosTurmaAPI();
    $success = $tester->runAll();
    exit($success ? 0 : 1);
}

