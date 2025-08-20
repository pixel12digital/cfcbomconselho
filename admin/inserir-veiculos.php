<?php
/**
 * Script para inserir veículos usando PDO diretamente
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../includes/config.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Inserir Veículos - Sistema CFC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f8f9fa; }
        h1 { color: #333; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚗 Inserir Veículos - Sistema de Agendamento</h1>
        
        <hr>";

try {
    // Conectar com PDO
    echo "<div class='card'>
        <h3>🔌 Conectando com PDO...</h3>";
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p class='success'>✅ Conexão PDO estabelecida com sucesso!</p>";
    echo "</div>";
    
    // Verificar dados existentes
    echo "<div class='card'>
        <h3>📊 Verificando Dados Existentes...</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculosExistentes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcsExistentes = $stmt->fetch()['total'];
    
    echo "<p><strong>Veículos existentes:</strong> <span class='info'>{$veiculosExistentes}</span></p>";
    echo "<p><strong>CFCs existentes:</strong> <span class='info'>{$cfcsExistentes}</span></p>";
    echo "</div>";
    
    // Pegar CFC existente
    $stmt = $pdo->query("SELECT id FROM cfcs LIMIT 1");
    $cfcId = $stmt->fetch()['id'];
    echo "<div class='card'>
        <h3>🏢 CFC Existente</h3>
        <p class='info'>Usando CFC existente com ID: {$cfcId}</p>
        </div>";
    
    // Inserir veículos se não existirem
    if ($veiculosExistentes == 0) {
        echo "<div class='card'>
            <h3>🚗 Inserindo Veículos...</h3>";
        
        $veiculos = [
            ['ABC-1234', 'Gol', 'Volkswagen', 2020, 'B'],
            ['DEF-5678', 'Onix', 'Chevrolet', 2021, 'B'],
            ['GHI-9012', 'CG 150', 'Honda', 2019, 'A']
        ];
        
        $sql = "INSERT INTO veiculos (cfc_id, placa, modelo, marca, ano, categoria_cnh, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        $veiculosInseridos = 0;
        foreach ($veiculos as $veiculo) {
            $veiculoData = array_merge([$cfcId], $veiculo, [1]);
            $stmt->execute($veiculoData);
            $veiculosInseridos++;
        }
        
        echo "<p class='success'>✅ {$veiculosInseridos} veículos inseridos com sucesso!</p>";
        echo "</div>";
    } else {
        echo "<div class='card'>
            <h3>🚗 Veículos Existentes</h3>
            <p class='info'>Já existem {$veiculosExistentes} veículos no sistema</p>
            </div>";
    }
    
    // Verificação final
    echo "<div class='card'>
        <h3>📊 Verificação Final</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculosFinais = $stmt->fetch()['total'];
    
    echo "<p><strong>Veículos:</strong> <span class='success'>{$veiculosFinais}</span></p>";
    
    if ($veiculosFinais > 0) {
        echo "<p class='success'>🎉 Veículos inseridos com sucesso!</p>";
    } else {
        echo "<p class='error'>⚠️ Ainda não há veículos</p>";
    }
    echo "</div>";
    
    // Links para próximos passos
    echo "<div class='card'>
        <h3>🔧 Próximos Passos</h3>
        <p>Agora você pode:</p>
        <a href='teste-agendamento-completo.php' class='btn'>🧪 Teste Completo do Sistema</a>
        <a href='index.php?page=agendamento' class='btn'>📅 Sistema de Agendamento</a>
        </div>";
    
} catch (Exception $e) {
    echo "<div class='card'>
        <h3 class='error'>❌ Erro no Sistema</h3>
        <p class='error'>Erro: " . $e->getMessage() . "</p>
        <p>Verifique se o banco de dados está funcionando.</p>
        </div>";
}

echo "</div></body></html>";
?>
