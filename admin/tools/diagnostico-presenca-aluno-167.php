<?php
/**
 * Diagnóstico de Presença - Aluno 167, Turma 19
 * Script para verificar se a presença está sendo salva e recuperada corretamente
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

header('Content-Type: text/html; charset=UTF-8');

$db = Database::getInstance();
$alunoId = 167;
$turmaId = 19;

echo "<h1>Diagnóstico de Presença - Aluno 167, Turma 19</h1>";

// 1. Verificar presenças na tabela turma_presencas
echo "<h2>1. Presenças na tabela turma_presencas</h2>";
$presencas = $db->fetchAll("
    SELECT 
        tp.*,
        taa.nome_aula,
        taa.data_aula,
        taa.status as aula_status
    FROM turma_presencas tp
    JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
    WHERE tp.aluno_id = ? AND tp.turma_id = ?
    ORDER BY taa.data_aula
", [$alunoId, $turmaId]);

echo "<p><strong>Total de presenças encontradas:</strong> " . count($presencas) . "</p>";

if (count($presencas) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Turma Aula ID</th><th>Presente</th><th>Tipo</th><th>Nome Aula</th><th>Data</th><th>Status Aula</th></tr>";
    foreach ($presencas as $p) {
        $tipoPresente = gettype($p['presente']);
        echo "<tr>";
        echo "<td>" . htmlspecialchars($p['id']) . "</td>";
        echo "<td>" . htmlspecialchars($p['turma_aula_id']) . "</td>";
        echo "<td>" . htmlspecialchars($p['presente']) . "</td>";
        echo "<td>" . $tipoPresente . "</td>";
        echo "<td>" . htmlspecialchars($p['nome_aula']) . "</td>";
        echo "<td>" . htmlspecialchars($p['data_aula']) . "</td>";
        echo "<td>" . htmlspecialchars($p['aula_status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>NENHUMA PRESENÇA ENCONTRADA!</strong></p>";
}

// 2. Verificar aulas agendadas da turma
echo "<h2>2. Aulas agendadas da turma</h2>";
$aulas = $db->fetchAll("
    SELECT 
        id,
        nome_aula,
        data_aula,
        status,
        ordem_global
    FROM turma_aulas_agendadas
    WHERE turma_id = ?
    AND status IN ('agendada', 'realizada')
    ORDER BY ordem_global
", [$turmaId]);

echo "<p><strong>Total de aulas válidas:</strong> " . count($aulas) . "</p>";

if (count($aulas) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nome Aula</th><th>Data</th><th>Status</th><th>Ordem</th></tr>";
    foreach ($aulas as $aula) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($aula['id']) . "</td>";
        echo "<td>" . htmlspecialchars($aula['nome_aula']) . "</td>";
        echo "<td>" . htmlspecialchars($aula['data_aula']) . "</td>";
        echo "<td>" . htmlspecialchars($aula['status']) . "</td>";
        echo "<td>" . htmlspecialchars($aula['ordem_global']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Testar a query exata do historico-aluno.php
echo "<h2>3. Teste da query do historico-aluno.php</h2>";
$queryTest = $db->fetchAll("
    SELECT 
        taa.id as aula_id,
        taa.nome_aula,
        taa.disciplina,
        taa.data_aula,
        taa.hora_inicio,
        taa.hora_fim,
        taa.status as aula_status,
        taa.ordem_global,
        tp.presente,
        tp.registrado_em,
        tp.id as presenca_id,
        tp.turma_id as presenca_turma_id,
        tp.turma_aula_id as presenca_turma_aula_id
    FROM turma_aulas_agendadas taa
    LEFT JOIN turma_presencas tp ON (
        tp.turma_aula_id = taa.id 
        AND tp.turma_id = taa.turma_id
        AND tp.aluno_id = ?
    )
    WHERE taa.turma_id = ?
    AND taa.status IN ('agendada', 'realizada')
    ORDER BY taa.ordem_global ASC
", [$alunoId, $turmaId]);

echo "<p><strong>Total de resultados da query:</strong> " . count($queryTest) . "</p>";

if (count($queryTest) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Aula ID</th><th>Nome Aula</th><th>Data</th><th>Presente (raw)</th><th>Presente (tipo)</th><th>Presente (null?)</th><th>Presente (empty?)</th><th>Presença ID</th><th>Turma ID Presença</th><th>Turma Aula ID Presença</th></tr>";
    foreach ($queryTest as $row) {
        $presenteRaw = $row['presente'];
        $presenteTipo = gettype($presenteRaw);
        $presenteIsNull = ($presenteRaw === null) ? 'SIM' : 'NÃO';
        $presenteIsEmpty = ($presenteRaw === '' || $presenteRaw === null) ? 'SIM' : 'NÃO';
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['aula_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nome_aula']) . "</td>";
        echo "<td>" . htmlspecialchars($row['data_aula']) . "</td>";
        echo "<td>" . var_export($presenteRaw, true) . "</td>";
        echo "<td>" . $presenteTipo . "</td>";
        echo "<td>" . $presenteIsNull . "</td>";
        echo "<td>" . $presenteIsEmpty . "</td>";
        echo "<td>" . ($row['presenca_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['presenca_turma_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['presenca_turma_aula_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Verificar se há presença para aula específica (227)
echo "<h2>4. Verificar presença para aula_id = 227</h2>";
$aula227 = $db->fetch("
    SELECT 
        tp.*,
        taa.nome_aula,
        taa.data_aula
    FROM turma_presencas tp
    JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
    WHERE tp.aluno_id = ? 
    AND tp.turma_id = ?
    AND tp.turma_aula_id = 227
", [$alunoId, $turmaId]);

if ($aula227) {
    echo "<p style='color: green;'><strong>PRESENÇA ENCONTRADA PARA AULA 227:</strong></p>";
    echo "<pre>" . print_r($aula227, true) . "</pre>";
} else {
    echo "<p style='color: red;'><strong>NENHUMA PRESENÇA ENCONTRADA PARA AULA 227!</strong></p>";
}

// 5. Verificar estrutura da tabela turma_presencas
echo "<h2>5. Estrutura da tabela turma_presencas</h2>";
$columns = $db->fetchAll("SHOW COLUMNS FROM turma_presencas");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
    echo "</tr>";
}
echo "</table>";

?>





