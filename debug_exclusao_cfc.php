<?php
// Script de debug para verificar registros vinculados ao CFC
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Usar o novo sistema de caminhos
require_once __DIR__ . '/includes/paths.php';
require_once INCLUDES_PATH . '/config.php';
require_once INCLUDES_PATH . '/database.php';

try {
    $db = Database::getInstance();
    
    // ID do CFC que está sendo tentado excluir
    $cfc_id = 30;
    
    echo "<h2>Debug de Exclusão do CFC ID: {$cfc_id}</h2>";
    echo "<p><strong>Caminho base do projeto:</strong> " . PROJECT_BASE_PATH . "</p>";
    
    // Verificar se o CFC existe
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$cfc_id]);
    if (!$cfc) {
        echo "<p style='color: red;'>CFC não encontrado!</p>";
        exit;
    }
    
    echo "<h3>Informações do CFC:</h3>";
    echo "<pre>" . print_r($cfc, true) . "</pre>";
    
    // Verificar registros vinculados
    echo "<h3>Verificando registros vinculados:</h3>";
    
    // Instrutores
    $instrutores = $db->fetchAll("SELECT * FROM instrutores WHERE cfc_id = ?", [$cfc_id]);
    echo "<h4>Instrutores ({$db->count('instrutores', 'cfc_id = ?', [$cfc_id])}):</h4>";
    if (!empty($instrutores)) {
        echo "<pre>" . print_r($instrutores, true) . "</pre>";
    } else {
        echo "<p>Nenhum instrutor vinculado</p>";
    }
    
    // Alunos
    $alunos = $db->fetchAll("SELECT * FROM alunos WHERE cfc_id = ?", [$cfc_id]);
    echo "<h4>Alunos ({$db->count('alunos', 'cfc_id = ?', [$cfc_id])}):</h4>";
    if (!empty($alunos)) {
        echo "<pre>" . print_r($alunos, true) . "</pre>";
    } else {
        echo "<p>Nenhum aluno vinculado</p>";
    }
    
    // Veículos
    $veiculos = $db->fetchAll("SELECT * FROM veiculos WHERE cfc_id = ?", [$cfc_id]);
    echo "<h4>Veículos ({$db->count('veiculos', 'cfc_id = ?', [$cfc_id])}):</h4>";
    if (!empty($veiculos)) {
        echo "<pre>" . print_r($veiculos, true) . "</pre>";
    } else {
        echo "<p>Nenhum veículo vinculado</p>";
    }
    
    // Aulas
    $aulas = $db->fetchAll("SELECT * FROM aulas WHERE cfc_id = ?", [$cfc_id]);
    echo "<h4>Aulas ({$db->count('aulas', 'cfc_id = ?', [$cfc_id])}):</h4>";
    if (!empty($aulas)) {
        echo "<pre>" . print_r($aulas, true) . "</pre>";
    } else {
        echo "<p>Nenhuma aula vinculada</p>";
    }
    
    // Verificar se há outras tabelas que possam ter referência ao CFC
    echo "<h3>Verificando outras possíveis referências:</h3>";
    
    // Listar todas as tabelas do banco
    $tables = $db->fetchAll("SHOW TABLES");
    echo "<h4>Tabelas no banco:</h4>";
    echo "<ul>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<li>{$tableName}</li>";
    }
    echo "</ul>";
    
    // Verificar se há outras tabelas com cfc_id
    echo "<h4>Verificando outras tabelas com cfc_id:</h4>";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        
        // Verificar se a tabela tem coluna cfc_id
        $columns = $db->fetchAll("SHOW COLUMNS FROM {$tableName}");
        $hasCfcId = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'cfc_id') {
                $hasCfcId = true;
                break;
            }
        }
        
        if ($hasCfcId) {
            $count = $db->count($tableName, 'cfc_id = ?', [$cfc_id]);
            if ($count > 0) {
                echo "<p><strong>{$tableName}:</strong> {$count} registro(s)</p>";
                $records = $db->fetchAll("SELECT * FROM {$tableName} WHERE cfc_id = ?", [$cfc_id]);
                echo "<pre>" . print_r($records, true) . "</pre>";
            }
        }
    }
    
    // Testar a função count diretamente
    echo "<h3>Testando função count diretamente:</h3>";
    try {
        $instr_count = $db->count('instrutores', 'cfc_id = ?', [$cfc_id]);
        echo "<p>Count instrutores: {$instr_count}</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro ao contar instrutores: " . $e->getMessage() . "</p>";
    }
    
    try {
        $alunos_count = $db->count('alunos', 'cfc_id = ?', [$cfc_id]);
        echo "<p>Count alunos: {$alunos_count}</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro ao contar alunos: " . $e->getMessage() . "</p>";
    }
    
    try {
        $veiculos_count = $db->count('veiculos', 'cfc_id = ?', [$cfc_id]);
        echo "<p>Count veículos: {$veiculos_count}</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro ao contar veículos: " . $e->getMessage() . "</p>";
    }
    
    try {
        $aulas_count = $db->count('aulas', 'cfc_id = ?', [$cfc_id]);
        echo "<p>Count aulas: {$aulas_count}</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro ao contar aulas: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>
