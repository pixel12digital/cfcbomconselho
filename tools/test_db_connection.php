<?php
/**
 * Script de teste de conexão com banco de dados remoto
 * 
 * Uso: php tools/test_db_connection.php
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;

echo "=== TESTE DE CONEXÃO COM BANCO DE DADOS ===\n\n";

// Carregar variáveis de ambiente
Env::load();

// Exibir configuração (sem senha completa)
echo "Configuração do Banco:\n";
echo "  - Host: " . ($_ENV['DB_HOST'] ?? 'não definido') . "\n";
echo "  - Porta: " . ($_ENV['DB_PORT'] ?? '3306') . "\n";
echo "  - Database: " . ($_ENV['DB_NAME'] ?? 'não definido') . "\n";
echo "  - Usuário: " . ($_ENV['DB_USER'] ?? 'não definido') . "\n";
echo "  - Senha: " . (!empty($_ENV['DB_PASS']) ? '***definida***' : 'não definida') . "\n";
echo "\n";

try {
    echo "Tentando conectar...\n";
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "✅ Conexão estabelecida com sucesso!\n\n";
    
    // Testar query simples
    echo "Testando query...\n";
    $stmt = $conn->query("SELECT VERSION() as version, DATABASE() as database, USER() as user, NOW() as server_time");
    $result = $stmt->fetch();
    
    echo "Informações do Banco:\n";
    echo "  - Versão MySQL/MariaDB: " . ($result['version'] ?? 'N/A') . "\n";
    echo "  - Database atual: " . ($result['database'] ?? 'N/A') . "\n";
    echo "  - Usuário: " . ($result['user'] ?? 'N/A') . "\n";
    echo "  - Data/Hora do servidor: " . ($result['server_time'] ?? 'N/A') . "\n";
    echo "\n";
    
    // Verificar tabelas principais
    echo "Verificando tabelas principais...\n";
    $tables = ['enrollments', 'students', 'services', 'cfcs'];
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM `{$table}`");
            $count = $stmt->fetch()['count'];
            echo "  ✅ {$table}: {$count} registros\n";
        } catch (\Exception $e) {
            echo "  ❌ {$table}: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // Verificar matrícula específica (ID 2 do erro anterior)
    echo "Verificando matrícula ID 2...\n";
    try {
        $stmt = $conn->prepare("
            SELECT e.*, 
                   s.name as student_name,
                   s.full_name as student_full_name,
                   sv.name as service_name
            FROM enrollments e
            LEFT JOIN students s ON s.id = e.student_id
            LEFT JOIN services sv ON sv.id = e.service_id
            WHERE e.id = ?
        ");
        $stmt->execute([2]);
        $enrollment = $stmt->fetch();
        
        if ($enrollment) {
            echo "  ✅ Matrícula encontrada:\n";
            echo "     - ID: {$enrollment['id']}\n";
            echo "     - Aluno: {$enrollment['student_name']}\n";
            echo "     - Serviço: {$enrollment['service_name']}\n";
            echo "     - Valor Final: R$ " . number_format($enrollment['final_price'] ?? 0, 2, ',', '.') . "\n";
            echo "     - Saldo Devedor: R$ " . number_format($enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?? 0, 2, ',', '.') . "\n";
            echo "     - Parcelas: " . ($enrollment['installments'] ?? 1) . "x\n";
            echo "     - Forma de Pagamento: " . ($enrollment['payment_method'] ?? 'N/A') . "\n";
            echo "     - Status Cobrança: " . ($enrollment['billing_status'] ?? 'N/A') . "\n";
            echo "     - Data 1ª Parcela: " . ($enrollment['first_due_date'] ?? 'N/A') . "\n";
        } else {
            echo "  ⚠️ Matrícula ID 2 não encontrada\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Erro ao buscar matrícula: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    echo "=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    
} catch (\PDOException $e) {
    echo "❌ ERRO na conexão:\n";
    echo "  - Código: " . $e->getCode() . "\n";
    echo "  - Mensagem: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Possíveis causas:\n";
    echo "  1. Host/porta incorretos\n";
    echo "  2. Credenciais inválidas\n";
    echo "  3. Firewall bloqueando conexão\n";
    echo "  4. Banco de dados não existe\n";
    echo "  5. Usuário sem permissão\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ ERRO:\n";
    echo "  - Mensagem: " . $e->getMessage() . "\n";
    echo "  - Arquivo: " . $e->getFile() . "\n";
    echo "  - Linha: " . $e->getLine() . "\n";
    exit(1);
}
