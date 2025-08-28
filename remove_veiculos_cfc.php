<?php
// Script para remover veículos vinculados a um CFC
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🚗 Remover Veículos Vinculados ao CFC</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    
    // Buscar CFC com veículos vinculados
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = 1");
    if (!$cfc) {
        echo "❌ CFC não encontrado<br>";
        exit;
    }
    
    echo "CFC: " . htmlspecialchars($cfc['nome']) . " (ID: {$cfc['id']})<br><br>";
    
    // Buscar veículos vinculados
    $veiculos = $db->fetchAll("SELECT * FROM veiculos WHERE cfc_id = ?", [$cfc['id']]);
    
    if (empty($veiculos)) {
        echo "✅ Nenhum veículo vinculado encontrado<br>";
    } else {
        echo "🚗 Veículos vinculados encontrados: " . count($veiculos) . "<br><br>";
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Placa</th><th>Modelo</th><th>Marca</th><th>Ano</th><th>Ações</th></tr>";
        
        foreach ($veiculos as $veiculo) {
            echo "<tr>";
            echo "<td>" . $veiculo['id'] . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['placa']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['modelo'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['marca'] ?? 'N/A') . "</td>";
            echo "<td>" . ($veiculo['ano'] ?? 'N/A') . "</td>";
            echo "<td>";
            echo "<button onclick='removerVeiculo({$veiculo['id']})' style='background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;'>Remover</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br>";
        echo "<button onclick='removerTodosVeiculos()' style='background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px;'>🗑️ Remover Todos os Veículos</button>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

?>

<script>
function removerVeiculo(veiculoId) {
    if (confirm('Deseja realmente remover este veículo?')) {
        fetch('remove_veiculo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                veiculo_id: veiculoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Veículo removido com sucesso!');
                location.reload();
            } else {
                alert('Erro ao remover veículo: ' + data.error);
            }
        })
        .catch(error => {
            alert('Erro: ' + error.message);
        });
    }
}

function removerTodosVeiculos() {
    if (confirm('⚠️ ATENÇÃO: Deseja realmente remover TODOS os veículos vinculados a este CFC? Esta ação não pode ser desfeita!')) {
        fetch('remove_veiculo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cfc_id: 1,
                remover_todos: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Todos os veículos foram removidos com sucesso!');
                location.reload();
            } else {
                alert('Erro ao remover veículos: ' + data.error);
            }
        })
        .catch(error => {
            alert('Erro: ' + error.message);
        });
    }
}
</script>
