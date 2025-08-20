<?php
/**
 * CRIAR VEÍCULOS DE TESTE
 * Script para inserir veículos necessários para o TESTE #9
 */

// Configuração de exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Criar Veículos de Teste</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo ".data-table th, .data-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }";
echo ".data-table th { background-color: #e9ecef; font-weight: bold; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🚗 Criar Veículos de Teste</h1>";
echo "<p>Script para inserir veículos necessários para o TESTE #9</p>";
echo "</div>";

try {
    // Incluir arquivos necessários
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    
    // Conectar ao banco
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Conexão estabelecida com sucesso!</div>";
    
    // Verificar CFCs disponíveis
    $stmt = $pdo->query("SELECT id, nome FROM cfcs LIMIT 1");
    $cfc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cfc) {
        echo "<div class='error'>❌ Nenhum CFC encontrado!</div>";
        exit;
    }
    
    echo "<div class='info'>ℹ️ CFC selecionado: " . htmlspecialchars($cfc['nome']) . " (ID: " . $cfc['id'] . ")</div>";
    
    // Verificar se já existem veículos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($count['total'] > 0) {
        echo "<div class='info'>ℹ️ Já existem " . $count['total'] . " veículos na tabela.</div>";
        
        // Mostrar veículos existentes
        $stmt = $pdo->query("SELECT * FROM veiculos");
        $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Veículos Existentes:</h3>";
        echo "<table class='data-table'>";
        echo "<thead><tr><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Ano</th><th>Status</th></tr></thead><tbody>";
        foreach ($veiculos as $veiculo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($veiculo['id']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['placa']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['marca']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['modelo']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['ano']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['status']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
    } else {
        echo "<div class='info'>ℹ️ Nenhum veículo encontrado. Criando veículos de teste...</div>";
        
        // Array de veículos de teste
        $veiculosTeste = [
            [
                'placa' => 'ABC-1234',
                'marca' => 'Fiat',
                'modelo' => 'Palio',
                'ano' => '2020',
                'cor' => 'Branco',
                'chassi' => '9BWZZZ377VT004251',
                'renavam' => '12345678901',
                'status' => 'ativo'
            ],
            [
                'placa' => 'DEF-5678',
                'marca' => 'Volkswagen',
                'modelo' => 'Gol',
                'ano' => '2019',
                'cor' => 'Prata',
                'chassi' => '9BWZZZ377VT004252',
                'renavam' => '12345678902',
                'status' => 'ativo'
            ],
            [
                'placa' => 'GHI-9012',
                'marca' => 'Chevrolet',
                'modelo' => 'Onix',
                'ano' => '2021',
                'cor' => 'Preto',
                'chassi' => '9BWZZZ377VT004253',
                'renavam' => '12345678903',
                'status' => 'ativo'
            ]
        ];
        
        // Inserir veículos
        $stmt = $pdo->prepare("INSERT INTO veiculos (cfc_id, placa, marca, modelo, ano, cor, chassi, renavam, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $veiculosCriados = 0;
        foreach ($veiculosTeste as $veiculo) {
            try {
                $result = $stmt->execute([
                    $cfc['id'],
                    $veiculo['placa'],
                    $veiculo['marca'],
                    $veiculo['modelo'],
                    $veiculo['ano'],
                    $veiculo['cor'],
                    $veiculo['chassi'],
                    $veiculo['renavam'],
                    $veiculo['status']
                ]);
                
                if ($result) {
                    $veiculosCriados++;
                    echo "<div class='success'>✅ Veículo " . htmlspecialchars($veiculo['placa']) . " criado com sucesso!</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro ao criar veículo " . htmlspecialchars($veiculo['placa']) . ": " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<div class='success'>🎉 Total de veículos criados: " . $veiculosCriados . "</div>";
        
        // Verificar veículos criados
        $stmt = $pdo->query("SELECT * FROM veiculos ORDER BY id DESC");
        $veiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Veículos Criados:</h3>";
        echo "<table class='data-table'>";
        echo "<thead><tr><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Ano</th><th>Status</th></tr></thead><tbody>";
        foreach ($veiculos as $veiculo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($veiculo['id']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['placa']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['marca']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['modelo']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['ano']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['status']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
    
    echo "<div class='success'>✅ Script executado com sucesso!</div>";
    echo "<p><strong>🔄 Próximo passo:</strong> Execute o TESTE #9 novamente!</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
