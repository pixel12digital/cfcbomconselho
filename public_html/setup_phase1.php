<?php
/**
 * Script de Setup Fase 1 - Execute via navegador
 * Acesse: http://localhost/cfc-v.1/public_html/setup_phase1.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Config/Database.php';

use App\Config\Database;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Fase 1 - CFC Sistema</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .status.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .status.info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
        .table-list {
            list-style: none;
            padding: 0;
        }
        .table-list li {
            padding: 8px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 3px;
        }
        .table-list li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Setup Fase 1</h1>
        <p class="subtitle">Configuração automática do banco de dados</p>

        <?php
        $action = $_GET['action'] ?? 'check';
        $db = Database::getInstance()->getConnection();

        if ($action === 'execute') {
            echo '<div class="status info"><strong>Executando migrations e seeds...</strong></div>';
            
            try {
                // Ler migration
                $migrationFile = ROOT_PATH . '/database/migrations/002_create_phase1_tables.sql';
                if (!file_exists($migrationFile)) {
                    throw new Exception("Arquivo de migration não encontrado!");
                }
                
                $migrationSQL = file_get_contents($migrationFile);
                
                // Executar SET statements
                $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
                $db->exec("SET time_zone = '+00:00'");
                
                // Executar CREATE TABLE statements
                $statements = explode(';', $migrationSQL);
                $tablesCreated = [];
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement) || preg_match('/^--/', $statement) || preg_match('/^SET\s+/i', $statement)) {
                        continue;
                    }
                    
                    if (preg_match('/^CREATE\s+TABLE/i', $statement)) {
                        $db->exec($statement . ';');
                        if (preg_match('/`(\w+)`/', $statement, $matches)) {
                            $tablesCreated[] = $matches[1];
                        }
                    }
                }
                
                $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo '<div class="status success">';
                echo '<strong>✓ Migration executada com sucesso!</strong><br>';
                echo 'Tabelas criadas: ' . implode(', ', $tablesCreated);
                echo '</div>';
                
                // Executar seed
                $seedFile = ROOT_PATH . '/database/seeds/002_seed_phase1_data.sql';
                if (!file_exists($seedFile)) {
                    throw new Exception("Arquivo de seed não encontrado!");
                }
                
                $seedSQL = file_get_contents($seedFile);
                $statements = explode(';', $seedSQL);
                $inserted = 0;
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement) || preg_match('/^--/', $statement)) {
                        continue;
                    }
                    
                    if (preg_match('/^INSERT\s+INTO/i', $statement)) {
                        try {
                            $db->exec($statement . ';');
                            $inserted++;
                        } catch (PDOException $e) {
                            // Ignorar duplicatas
                            if (strpos($e->getMessage(), 'Duplicate') === false) {
                                echo '<div class="status error">Aviso: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                        }
                    }
                }
                
                echo '<div class="status success">';
                echo '<strong>✓ Seed executado com sucesso!</strong><br>';
                echo "Dados inseridos: {$inserted} operações";
                echo '</div>';
                
                // Verificar tabelas
                echo '<div class="status info">';
                echo '<strong>Verificando tabelas criadas:</strong><ul class="table-list">';
                $requiredTables = ['services', 'students', 'enrollments', 'steps', 'student_steps'];
                $allOk = true;
                
                foreach ($requiredTables as $table) {
                    try {
                        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
                        if ($stmt->rowCount() > 0) {
                            echo "<li>{$table}</li>";
                        } else {
                            echo "<li style='color: #dc3545;'>✗ {$table} - NÃO ENCONTRADA</li>";
                            $allOk = false;
                        }
                    } catch (PDOException $e) {
                        echo "<li style='color: #dc3545;'>✗ {$table} - ERRO</li>";
                        $allOk = false;
                    }
                }
                
                echo '</ul></div>';
                
                if ($allOk) {
                    echo '<div class="status success">';
                    echo '<strong>✅ FASE 1 CONFIGURADA COM SUCESSO!</strong><br><br>';
                    echo 'Todas as tabelas foram criadas e os dados iniciais foram inseridos.<br>';
                    echo 'Você pode agora acessar o sistema normalmente.';
                    echo '</div>';
                    echo '<a href="' . str_replace('/public_html', '', $_SERVER['PHP_SELF']) . '/../alunos" class="btn">Acessar Sistema</a>';
                } else {
                    echo '<div class="status error">';
                    echo '<strong>⚠️ Algumas tabelas não foram criadas. Verifique os erros acima.</strong>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="status error">';
                echo '<strong>❌ ERRO:</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            
        } else {
            // Verificar status atual
            echo '<div class="status info">';
            echo '<strong>Verificando status atual do banco de dados...</strong>';
            echo '</div>';
            
            $requiredTables = ['services', 'students', 'enrollments', 'steps', 'student_steps'];
            $missingTables = [];
            $existingTables = [];
            
            foreach ($requiredTables as $table) {
                try {
                    $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
                    if ($stmt->rowCount() > 0) {
                        $existingTables[] = $table;
                    } else {
                        $missingTables[] = $table;
                    }
                } catch (PDOException $e) {
                    $missingTables[] = $table;
                }
            }
            
            if (empty($missingTables)) {
                echo '<div class="status success">';
                echo '<strong>✅ Todas as tabelas já existem!</strong><br>';
                echo 'A Fase 1 já está configurada.';
                echo '</div>';
                echo '<a href="' . str_replace('/public_html', '', $_SERVER['PHP_SELF']) . '/../alunos" class="btn">Acessar Sistema</a>';
            } else {
                echo '<div class="status error">';
                echo '<strong>⚠️ Tabelas faltando:</strong><ul class="table-list">';
                foreach ($missingTables as $table) {
                    echo "<li style='color: #dc3545;'>✗ {$table}</li>";
                }
                echo '</ul></div>';
                
                if (!empty($existingTables)) {
                    echo '<div class="status info">';
                    echo '<strong>Tabelas existentes:</strong><ul class="table-list">';
                    foreach ($existingTables as $table) {
                        echo "<li>{$table}</li>";
                    }
                    echo '</ul></div>';
                }
                
                echo '<div class="status info">';
                echo '<strong>Próximo passo:</strong><br>';
                echo 'Clique no botão abaixo para criar as tabelas faltantes e inserir os dados iniciais.';
                echo '</div>';
                echo '<a href="?action=execute" class="btn">Executar Setup</a>';
            }
        }
        ?>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
        
        <p style="color: #666; font-size: 14px;">
            <strong>Nota:</strong> Este script executa as migrations e seeds da Fase 1 automaticamente.<br>
            Se preferir executar manualmente, use o arquivo <code>EXECUTAR_FASE1.sql</code> no phpMyAdmin.
        </p>
    </div>
</body>
</html>
