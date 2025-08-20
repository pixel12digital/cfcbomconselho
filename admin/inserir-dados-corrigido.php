<?php
/**
 * Script corrigido para inserir dados essenciais para o sistema de agendamento
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Inserir Dados Corrigido - Sistema CFC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f8f9fa; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        h1 { color: #333; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📋 Inserir Dados Corrigido - Sistema de Agendamento</h1>
        <p><strong>Banco:</strong> <span class='info'>" . DB_HOST . "</span></p>
        <p><strong>Database:</strong> <span class='info'>" . DB_NAME . "</span></p>
        
        <hr>";

try {
    // Verificar dados existentes
    echo "<div class='card'>
        <h3>📊 Verificando Dados Existentes...</h3>";
    
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo IN ('admin', 'instrutor')");
    $usuariosExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM alunos");
    $alunosExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcsExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculosExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Usuários (Admin/Instrutor):</strong> <span class='info'>{$usuariosExistentes}</span></p>";
    echo "<p><strong>Alunos:</strong> <span class='info'>{$alunosExistentes}</span></p>";
    echo "<p><strong>CFCs:</strong> <span class='info'>{$cfcsExistentes}</span></p>";
    echo "<p><strong>Veículos:</strong> <span class='info'>{$veiculosExistentes}</span></p>";
    echo "</div>";
    
    // Pegar CFC existente
    $result = $db->query("SELECT id FROM cfcs LIMIT 1");
    $cfcId = $result->fetch(PDO::FETCH_ASSOC)['id'];
    echo "<div class='card'>
        <h3>🏢 CFC Existente</h3>
        <p class='info'>Usando CFC existente com ID: {$cfcId}</p>
        </div>";
    
    // Inserir alunos se não existirem
    if ($alunosExistentes == 0) {
        echo "<div class='card'>
            <h3>👨‍🎓 Inserindo Alunos...</h3>";
        
        $alunos = [
            ['Maria Santos', '222.222.222-22', '12.345.678-9', '1995-05-15', 'Rua das Palmeiras, 456 - Centro', '(87) 77777-7777', 'maria.santos@email.com', 'B'],
            ['Pedro Oliveira', '333.333.333-33', '23.456.789-0', '1992-08-22', 'Av. Principal, 789 - Bairro Novo', '(87) 66666-6666', 'pedro.oliveira@email.com', 'A']
        ];
        
        $sql = "INSERT INTO alunos (nome, cpf, rg, data_nascimento, endereco, telefone, email, cfc_id, categoria_cnh, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->query($sql);
        
        $alunosInseridos = 0;
        foreach ($alunos as $aluno) {
            $alunoData = array_merge($aluno, [$cfcId, 'ativo']);
            $stmt->execute($alunoData);
            $alunosInseridos++;
        }
        
        echo "<p class='success'>✅ {$alunosInseridos} alunos inseridos com sucesso!</p>";
        echo "</div>";
    } else {
        echo "<div class='card'>
            <h3>👨‍🎓 Alunos Existentes</h3>
            <p class='info'>Já existem {$alunosExistentes} alunos no sistema</p>
            </div>";
    }
    
    // Inserir veículos se não existirem
    if ($veiculosExistentes == 0) {
        echo "<div class='card'>
            <h3>🚗 Inserindo Veículos...</h3>";
        
        $veiculos = [
            ['ABC-1234', 'Gol', 'Volkswagen', 2020, 'B'],
            ['DEF-5678', 'Onix', 'Chevrolet', 2021, 'B']
        ];
        
        $sql = "INSERT INTO veiculos (cfc_id, placa, modelo, marca, ano, categoria_cnh, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->query($sql);
        
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
    
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo IN ('admin', 'instrutor')");
    $usuariosFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM alunos");
    $alunosFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcsFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculosFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Usuários (Admin/Instrutor):</strong> <span class='success'>{$usuariosFinais}</span></p>";
    echo "<p><strong>Alunos:</strong> <span class='success'>{$alunosFinais}</span></p>";
    echo "<p><strong>CFCs:</strong> <span class='success'>{$cfcsFinais}</span></p>";
    echo "<p><strong>Veículos:</strong> <span class='success'>{$veiculosFinais}</span></p>";
    
    if ($usuariosFinais > 0 && $alunosFinais > 0 && $cfcsFinais > 0 && $veiculosFinais > 0) {
        echo "<p class='success'>🎉 Sistema pronto para testes de agendamento!</p>";
    } else {
        echo "<p class='error'>⚠️ Ainda há dados faltando</p>";
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
