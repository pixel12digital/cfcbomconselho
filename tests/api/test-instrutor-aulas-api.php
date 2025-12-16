<?php
/**
 * Testes Automatizados - API instrutor-aulas.php
 * Tarefa 2.2 - Início e Fim de Aula Prática
 * 
 * Cenários testados:
 * - Cancelamento e transferência (já existentes)
 * - Iniciar aula (novo)
 * - Finalizar aula (novo)
 */

// Configuração de ambiente de teste
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Classe auxiliar para testes
class InstrutorAulasApiTest {
    private $baseUrl;
    private $testInstrutorId = null;
    private $testInstrutorUserId = null;
    private $testAlunoId = null;
    private $testCfcId = null;
    private $testAulaId = null;
    private $db;
    private $testResults = [];
    
    public function __construct() {
        $this->baseUrl = (defined('APP_URL') ? APP_URL : 'http://localhost') . '/admin/api/instrutor-aulas.php';
        $this->db = Database::getInstance();
    }
    
    /**
     * Setup: Criar dados de teste necessários
     */
    public function setup() {
        try {
            // Buscar ou criar CFC de teste
            $cfc = $this->db->fetch("SELECT id FROM cfcs WHERE nome LIKE '%TESTE%' OR id = 36 LIMIT 1");
            if (!$cfc) {
                $this->db->query("INSERT INTO cfcs (nome, cnpj, ativo) VALUES ('CFC Teste API', '00.000.000/0001-00', TRUE)");
                $cfc = $this->db->fetch("SELECT id FROM cfcs WHERE nome = 'CFC Teste API'");
            }
            $this->testCfcId = $cfc['id'];
            
            // Criar usuário instrutor de teste
            $hashSenha = password_hash('senha123', PASSWORD_DEFAULT);
            $this->db->query("
                INSERT INTO usuarios (nome, email, senha, tipo, ativo) 
                VALUES ('Instrutor Teste API', 'instrutor_teste_api@teste.com', ?, 'instrutor', TRUE)
                ON DUPLICATE KEY UPDATE nome = 'Instrutor Teste API'
            ", [$hashSenha]);
            
            $usuario = $this->db->fetch("SELECT id FROM usuarios WHERE email = 'instrutor_teste_api@teste.com'");
            if (!$usuario) {
                throw new Exception("Usuário de teste não foi criado");
            }
            $this->testInstrutorUserId = $usuario['id'];
            
            // Criar instrutor
            $this->db->query("
                INSERT INTO instrutores (usuario_id, cfc_id, credencial, ativo) 
                VALUES (?, ?, 'INST_TESTE_API', TRUE)
                ON DUPLICATE KEY UPDATE credencial = 'INST_TESTE_API', usuario_id = ?, cfc_id = ?
            ", [$this->testInstrutorUserId, $this->testCfcId, $this->testInstrutorUserId, $this->testCfcId]);
            
            $instrutor = $this->db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$this->testInstrutorUserId]);
            if (!$instrutor) {
                throw new Exception("Instrutor de teste não foi criado");
            }
            $this->testInstrutorId = $instrutor['id'];
            
            // Criar aluno de teste
            $this->db->query("
                INSERT INTO alunos (nome, cpf, cfc_id, status) 
                VALUES ('Aluno Teste API', '999.999.999-99', ?, 'ativo')
                ON DUPLICATE KEY UPDATE nome = 'Aluno Teste API'
            ", [$this->testCfcId]);
            
            $aluno = $this->db->fetch("SELECT id FROM alunos WHERE cpf = '999.999.999-99'");
            $this->testAlunoId = $aluno['id'];
            
            // Criar veículo de teste
            $this->db->query("
                INSERT INTO veiculos (placa, modelo, cfc_id, ativo) 
                VALUES ('ABC-9999', 'Veículo Teste', ?, TRUE)
                ON DUPLICATE KEY UPDATE modelo = 'Veículo Teste'
            ", [$this->testCfcId]);
            
            $veiculo = $this->db->fetch("SELECT id FROM veiculos WHERE placa = 'ABC-9999'");
            $testVeiculoId = $veiculo['id'];
            
            // Criar aula de teste (status agendada)
            $dataAula = date('Y-m-d', strtotime('+1 day'));
            $horaInicio = '10:00:00';
            $horaFim = '11:00:00';
            
            $this->db->query("
                INSERT INTO aulas (aluno_id, instrutor_id, cfc_id, veiculo_id, tipo_aula, data_aula, hora_inicio, hora_fim, status) 
                VALUES (?, ?, ?, ?, 'pratica', ?, ?, ?, 'agendada')
            ", [$this->testAlunoId, $this->testInstrutorId, $this->testCfcId, $testVeiculoId, $dataAula, $horaInicio, $horaFim]);
            
            $aula = $this->db->fetch("
                SELECT id FROM aulas 
                WHERE instrutor_id = ? AND data_aula = ? AND hora_inicio = ? 
                ORDER BY id DESC LIMIT 1
            ", [$this->testInstrutorId, $dataAula, $horaInicio]);
            
            $this->testAulaId = $aula['id'];
            
            return true;
        } catch (Exception $e) {
            echo "ERRO no setup: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cleanup: Limpar dados de teste
     */
    public function cleanup() {
        try {
            if ($this->testAulaId) {
                $this->db->query("DELETE FROM aulas WHERE id = ?", [$this->testAulaId]);
            }
            // Não deletar usuário/instrutor/aluno para não quebrar outros testes
        } catch (Exception $e) {
            // Ignorar erros no cleanup
        }
    }
    
    /**
     * Helper: Fazer requisição HTTP simulada
     * Simula chamada à API incluindo o arquivo diretamente
     */
    private function makeRequest($method, $data, $sessionUserId = null) {
        // A API espera JSON em php://input, mas como não podemos mockar facilmente,
        // vamos criar um arquivo temporário com o JSON e usar file_get_contents com wrapper
        
        $jsonData = json_encode($data);
        
        // Criar arquivo temporário com JSON
        $tempFile = tempnam(sys_get_temp_dir(), 'test_json_');
        file_put_contents($tempFile, $jsonData);
        
        // Wrapper temporário para file_get_contents que redireciona php://input para nosso arquivo
        // Mas isso não funciona diretamente. Vamos usar outra abordagem:
        // Simular que php://input retorna nosso JSON usando variável global
        
        // Abordagem mais simples: modificar temporariamente a superglobal $_POST
        // e ajustar o código para usar isso quando não há JSON
        // MAS a API prioriza JSON, então vamos ter que fazer diferente
        
        // Solução final pragmática: incluir o arquivo mas modificar temporariamente
        // file_get_contents usando namespace ou wrapper
        // Como isso é complexo, vamos usar uma técnica de "stream wrapper mock"
        
        // Por enquanto, vamos fazer include direto e ajustar para usar $_POST
        // quando detectarmos que estamos em teste
        
        // Preparar sessão ANTES de incluir arquivos que podem iniciar sessão
        if ($sessionUserId) {
            // Fechar qualquer sessão existente
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            
            // Iniciar nova sessão
            session_start();
            $_SESSION['user_id'] = $sessionUserId;
            $_SESSION['user_type'] = 'instrutor'; // A API usa 'tipo', mas a sessão pode usar 'user_type'
            $_SESSION['tipo'] = 'instrutor'; // Tentar ambos para compatibilidade
            $_SESSION['cfc_id'] = $this->testCfcId;
            $_SESSION['user_cfc_id'] = $this->testCfcId; // Tentar ambos
        } else {
            // Limpar sessão
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            $_SESSION = [];
        }
        
        // Preparar ambiente
        $_SERVER['REQUEST_METHOD'] = $method;
        $_GET = [];
        
        // A API lê JSON de php://input primeiro, depois usa $_POST como fallback
        // Como não podemos mockar php://input facilmente, vamos usar uma técnica:
        // Criar um arquivo temporário e modificar temporariamente file_get_contents
        // Ou usar uma abordagem mais simples: incluir o arquivo e aceitar que vai usar $_POST
        
        // Vamos usar $_POST diretamente e garantir que a API aceite
        $_POST = $data;
        
        // Também vamos simular php://input usando uma variável global
        // e criar um wrapper que intercepta file_get_contents('php://input')
        // Mas isso é complexo, então vamos tentar uma abordagem mais simples:
        
        // Modificar temporariamente file_get_contents via namespace (não funciona para built-ins)
        // Ou usar uma função wrapper
        
        // Solução pragmática: criar um arquivo wrapper temporário que simula php://input
        // Mas isso também é complexo
        
        // Vamos usar a abordagem mais simples possível: incluir diretamente
        // e aceitar que a API vai ler de $_POST quando php://input estiver vazio
        // Para isso, precisamos garantir que file_get_contents('php://input') retorne string vazia
        
        ob_start();
        
        try {
            // Incluir arquivo da API
            // A API tenta: $input = json_decode(file_get_contents('php://input'), true);
            // Se isso retornar null (porque php://input está vazio), vai usar $_POST
            include __DIR__ . '/../../admin/api/instrutor-aulas.php';
            
        } catch (Exception $e) {
            // Capturar exceções
            echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        } catch (Error $e) {
            // Capturar erros fatais também
            echo json_encode(['success' => false, 'message' => 'Erro fatal: ' . $e->getMessage()]);
        }
        
        $response = ob_get_clean();
        
        // Limpar arquivo temporário
        if (isset($tempFile) && file_exists($tempFile)) {
            @unlink($tempFile);
        }
        
        // Limpar sessão após o teste
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Decodificar JSON
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false, 
                'message' => 'Resposta não é JSON válido. Output: ' . substr($response, 0, 200),
                'raw_output' => $response
            ];
        }
        
        return $decoded ?: [];
    }
    
    /**
     * Teste: Iniciar aula - Autenticado como instrutor dono da aula, aula agendada
     */
    public function testIniciarAula_Autenticado_Agendada() {
        // Resetar aula para status agendada
        $this->db->query("UPDATE aulas SET status = 'agendada' WHERE id = ?", [$this->testAulaId]);
        
        $response = $this->makeRequest('POST', [
            'aula_id' => $this->testAulaId,
            'tipo_acao' => 'iniciar'
        ], $this->testInstrutorUserId);
        
        $this->assertTrue($response['success'] ?? false, "Deve retornar success = true");
        $this->assertEquals('em_andamento', $response['data']['status'] ?? null, "Status deve ser 'em_andamento'");
        
        // Verificar no banco
        $aula = $this->db->fetch("SELECT status FROM aulas WHERE id = ?", [$this->testAulaId]);
        $this->assertEquals('em_andamento', $aula['status'], "Status no banco deve ser 'em_andamento'");
    }
    
    /**
     * Teste: Iniciar aula - Não autenticado
     */
    public function testIniciarAula_NaoAutenticado() {
        $response = $this->makeRequest('POST', [
            'aula_id' => $this->testAulaId,
            'tipo_acao' => 'iniciar'
        ], null);
        
        $this->assertFalse($response['success'] ?? true, "Deve retornar success = false");
        $this->assertStringContainsString('não autenticado', strtolower($response['message'] ?? ''), "Mensagem deve indicar não autenticado");
    }
    
    /**
     * Teste: Iniciar aula - Aula já em andamento (erro)
     */
    public function testIniciarAula_JaEmAndamento() {
        // Colocar aula em andamento
        $this->db->query("UPDATE aulas SET status = 'em_andamento' WHERE id = ?", [$this->testAulaId]);
        
        $response = $this->makeRequest('POST', [
            'aula_id' => $this->testAulaId,
            'tipo_acao' => 'iniciar'
        ], $this->testInstrutorUserId);
        
        $this->assertFalse($response['success'] ?? true, "Deve retornar success = false");
    }
    
    /**
     * Teste: Finalizar aula - Autenticado, aula em andamento
     */
    public function testFinalizarAula_Autenticado_EmAndamento() {
        // Colocar aula em andamento primeiro
        $this->db->query("UPDATE aulas SET status = 'em_andamento' WHERE id = ?", [$this->testAulaId]);
        
        $response = $this->makeRequest('POST', [
            'aula_id' => $this->testAulaId,
            'tipo_acao' => 'finalizar'
        ], $this->testInstrutorUserId);
        
        $this->assertTrue($response['success'] ?? false, "Deve retornar success = true");
        $this->assertEquals('concluida', $response['data']['status'] ?? null, "Status deve ser 'concluida'");
        
        // Verificar no banco
        $aula = $this->db->fetch("SELECT status FROM aulas WHERE id = ?", [$this->testAulaId]);
        $this->assertEquals('concluida', $aula['status'], "Status no banco deve ser 'concluida'");
    }
    
    /**
     * Teste: Finalizar aula - Não autenticado
     */
    public function testFinalizarAula_NaoAutenticado() {
        $response = $this->makeRequest('POST', [
            'aula_id' => $this->testAulaId,
            'tipo_acao' => 'finalizar'
        ], null);
        
        $this->assertFalse($response['success'] ?? true, "Deve retornar success = false");
    }
    
    /**
     * Teste: Finalizar aula - Aula não iniciada (erro)
     */
    public function testFinalizarAula_NaoIniciada() {
        // Resetar para agendada
        $this->db->query("UPDATE aulas SET status = 'agendada' WHERE id = ?", [$this->testAulaId]);
        
        $response = $this->makeRequest('POST', [
            'aula_id' => $this->testAulaId,
            'tipo_acao' => 'finalizar'
        ], $this->testInstrutorUserId);
        
        $this->assertFalse($response['success'] ?? true, "Deve retornar success = false");
    }
    
    /**
     * Assert helpers
     */
    private function assertTrue($condition, $message) {
        if (!$condition) {
            $this->testResults[] = "❌ FALHOU: $message";
            return false;
        }
        $this->testResults[] = "✅ PASSOU: $message";
        return true;
    }
    
    private function assertFalse($condition, $message) {
        return $this->assertTrue(!$condition, $message);
    }
    
    private function assertEquals($expected, $actual, $message) {
        if ($expected !== $actual) {
            $this->testResults[] = "❌ FALHOU: $message (esperado: $expected, obtido: " . var_export($actual, true) . ")";
            return false;
        }
        $this->testResults[] = "✅ PASSOU: $message";
        return true;
    }
    
    private function assertStringContainsString($needle, $haystack, $message) {
        if (strpos($haystack, $needle) === false) {
            $this->testResults[] = "❌ FALHOU: $message";
            return false;
        }
        $this->testResults[] = "✅ PASSOU: $message";
        return true;
    }
    
    /**
     * Executar todos os testes
     */
    public function runAll() {
        echo "=== Testes da API instrutor-aulas.php (Tarefa 2.2) ===\n\n";
        
        if (!$this->setup()) {
            echo "❌ ERRO: Não foi possível fazer setup dos testes\n";
            return;
        }
        
        try {
            // Testes de iniciar aula
            echo "--- Testes de Iniciar Aula ---\n";
            $this->testIniciarAula_Autenticado_Agendada();
            $this->testIniciarAula_NaoAutenticado();
            $this->testIniciarAula_JaEmAndamento();
            
            // Testes de finalizar aula
            echo "\n--- Testes de Finalizar Aula ---\n";
            $this->testFinalizarAula_Autenticado_EmAndamento();
            $this->testFinalizarAula_NaoAutenticado();
            $this->testFinalizarAula_NaoIniciada();
            
        } finally {
            $this->cleanup();
        }
        
        // Exibir resultados
        echo "\n=== Resultados ===\n";
        foreach ($this->testResults as $result) {
            echo "$result\n";
        }
        
        $passed = count(array_filter($this->testResults, function($r) { return strpos($r, '✅') !== false; }));
        $failed = count(array_filter($this->testResults, function($r) { return strpos($r, '❌') !== false; }));
        
        echo "\nTotal: " . count($this->testResults) . " testes\n";
        echo "Passou: $passed\n";
        echo "Falhou: $failed\n";
    }
}

// Executar testes se chamado diretamente
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === '1')) {
    $test = new InstrutorAulasApiTest();
    $test->runAll();
}
?>

