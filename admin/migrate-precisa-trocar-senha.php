<?php
/**
 * Script de Migração: Adicionar coluna precisa_trocar_senha
 * 
 * Este script verifica e cria a coluna precisa_trocar_senha na tabela usuarios
 * se ela ainda não existir.
 * 
 * IMPORTANTE: Execute este script apenas uma vez. Pode ser removido após uso.
 */

// Verificar se estamos sendo acessados via web (não via CLI)
if (php_sapi_name() !== 'cli') {
    // Requer autenticação se executado via web
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/auth.php';
    
    // Verificar se usuário está logado e é admin
    if (!isLoggedIn()) {
        die('Acesso negado. Faça login primeiro.');
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser || $currentUser['tipo'] !== 'admin') {
        die('Acesso negado. Apenas administradores podem executar este script.');
    }
} else {
    // Execução via CLI
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/database.php';
}

// Configurar headers para HTML se executado via web
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração: precisa_trocar_senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .migration-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .status-box {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .status-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .status-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .status-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <h1 class="mb-4">
            <i class="fas fa-database"></i> Migração: precisa_trocar_senha
        </h1>
        
        <?php
        $db = Database::getInstance();
        $messages = [];
        $errors = [];
        $success = false;
        
        try {
            // PASSO 1: Verificar se a coluna já existe
            $messages[] = ['type' => 'info', 'text' => 'Verificando se a coluna precisa_trocar_senha já existe...'];
            
            $checkColumn = $db->fetch("
                SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME = 'precisa_trocar_senha'
            ");
            
            if ($checkColumn) {
                $messages[] = [
                    'type' => 'success',
                    'text' => '✅ A coluna precisa_trocar_senha já existe na tabela usuarios.'
                ];
                $messages[] = [
                    'type' => 'info',
                    'text' => 'Detalhes da coluna:'
                ];
                $messages[] = [
                    'type' => 'info',
                    'text' => sprintf(
                        'Tipo: %s | Nullable: %s | Default: %s',
                        $checkColumn['DATA_TYPE'],
                        $checkColumn['IS_NULLABLE'],
                        $checkColumn['COLUMN_DEFAULT'] ?? 'NULL'
                    )
                ];
                $success = true;
            } else {
                // PASSO 2: Coluna não existe, vamos criá-la
                $messages[] = [
                    'type' => 'warning',
                    'text' => '⚠️ A coluna precisa_trocar_senha não existe. Criando...'
                ];
                
                // Verificar estrutura da tabela para determinar posição
                $tableStructure = $db->fetchAll("
                    SELECT COLUMN_NAME, ORDINAL_POSITION
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'usuarios'
                    ORDER BY ORDINAL_POSITION
                ");
                
                $afterColumn = 'senha'; // Coluna padrão para posicionar
                $senhaFound = false;
                foreach ($tableStructure as $col) {
                    if ($col['COLUMN_NAME'] === 'senha') {
                        $senhaFound = true;
                        break;
                    }
                }
                
                if (!$senhaFound) {
                    $afterColumn = 'tipo'; // Fallback se senha não existir
                }
                
                // Criar a coluna
                $sql = "
                    ALTER TABLE usuarios
                    ADD COLUMN precisa_trocar_senha TINYINT(1) NOT NULL DEFAULT 0 
                    COMMENT 'Flag que indica se o usuário precisa trocar a senha no próximo login (1 = sim, 0 = não)' 
                    AFTER {$afterColumn}
                ";
                
                $messages[] = [
                    'type' => 'info',
                    'text' => 'Executando SQL:'
                ];
                $messages[] = [
                    'type' => 'info',
                    'text' => '<pre>' . htmlspecialchars($sql) . '</pre>'
                ];
                
                $result = $db->query($sql);
                
                if ($result) {
                    $messages[] = [
                        'type' => 'success',
                        'text' => '✅ Coluna precisa_trocar_senha criada com sucesso!'
                    ];
                    $success = true;
                    
                    // Verificar novamente para confirmar
                    $verifyColumn = $db->fetch("
                        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = 'usuarios'
                          AND COLUMN_NAME = 'precisa_trocar_senha'
                    ");
                    
                    if ($verifyColumn) {
                        $messages[] = [
                            'type' => 'success',
                            'text' => '✅ Verificação: Coluna confirmada no banco de dados.'
                        ];
                    }
                } else {
                    $errors[] = 'Erro ao executar ALTER TABLE. Verifique as permissões do banco de dados.';
                }
            }
            
            // PASSO 3: Verificar colunas relacionadas (opcional)
            $messages[] = [
                'type' => 'info',
                'text' => '<hr><strong>Verificando colunas relacionadas:</strong>'
            ];
            
            $relatedColumns = ['primeiro_acesso', 'senha_temporaria', 'senha_alterada_em'];
            foreach ($relatedColumns as $colName) {
                $colCheck = $db->fetch("
                    SELECT COLUMN_NAME, DATA_TYPE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'usuarios'
                      AND COLUMN_NAME = ?
                ", [$colName]);
                
                if ($colCheck) {
                    $messages[] = [
                        'type' => 'info',
                        'text' => sprintf('✅ Coluna %s existe (Tipo: %s)', $colName, $colCheck['DATA_TYPE'])
                    ];
                } else {
                    $messages[] = [
                        'type' => 'info',
                        'text' => sprintf('ℹ️ Coluna %s não existe (opcional, não é necessária)', $colName)
                    ];
                }
            }
            
            // PASSO 4: Listar todas as colunas relacionadas a senha
            $messages[] = [
                'type' => 'info',
                'text' => '<hr><strong>Colunas relacionadas a senha na tabela usuarios:</strong>'
            ];
            
            $passwordColumns = $db->fetchAll("
                SELECT 
                    COLUMN_NAME, 
                    DATA_TYPE, 
                    IS_NULLABLE, 
                    COLUMN_DEFAULT,
                    COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'usuarios'
                  AND COLUMN_NAME IN ('senha', 'precisa_trocar_senha', 'primeiro_acesso', 'senha_temporaria', 'senha_alterada_em')
                ORDER BY ORDINAL_POSITION
            ");
            
            if (!empty($passwordColumns)) {
                $messages[] = [
                    'type' => 'info',
                    'text' => '<table class="table table-sm table-bordered mt-2">
                        <thead>
                            <tr>
                                <th>Coluna</th>
                                <th>Tipo</th>
                                <th>Nullable</th>
                                <th>Default</th>
                                <th>Comentário</th>
                            </tr>
                        </thead>
                        <tbody>'
                ];
                
                foreach ($passwordColumns as $col) {
                    $messages[] = [
                        'type' => 'info',
                        'text' => sprintf(
                            '<tr>
                                <td><strong>%s</strong></td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                            </tr>',
                            htmlspecialchars($col['COLUMN_NAME']),
                            htmlspecialchars($col['DATA_TYPE']),
                            htmlspecialchars($col['IS_NULLABLE']),
                            htmlspecialchars($col['COLUMN_DEFAULT'] ?? 'NULL'),
                            htmlspecialchars($col['COLUMN_COMMENT'] ?? '')
                        )
                    ];
                }
                
                $messages[] = [
                    'type' => 'info',
                    'text' => '</tbody></table>'
                ];
            }
            
        } catch (Exception $e) {
            $errors[] = 'Erro durante a migração: ' . $e->getMessage();
            if (LOG_ENABLED) {
                error_log('[MIGRATION] Erro: ' . $e->getMessage());
            }
        }
        
        // Exibir mensagens
        foreach ($messages as $msg) {
            $class = 'status-' . $msg['type'];
            echo '<div class="status-box ' . $class . '">';
            echo $msg['text'];
            echo '</div>';
        }
        
        // Exibir erros
        if (!empty($errors)) {
            echo '<div class="status-box status-error">';
            echo '<strong>❌ Erros encontrados:</strong><ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }
        
        // Mensagem final
        if ($success && empty($errors)) {
            echo '<div class="status-box status-success mt-4">';
            echo '<strong>✅ Migração concluída com sucesso!</strong><br>';
            echo 'A coluna precisa_trocar_senha está pronta para uso.';
            echo '</div>';
            
            echo '<div class="mt-4">';
            echo '<a href="index.php?page=usuarios" class="btn btn-primary">';
            echo '<i class="fas fa-arrow-left"></i> Voltar para Gerenciar Usuários';
            echo '</a>';
            echo ' <a href="index.php" class="btn btn-secondary">';
            echo '<i class="fas fa-home"></i> Ir para Dashboard';
            echo '</a>';
            echo '</div>';
        } elseif (!empty($errors)) {
            echo '<div class="status-box status-error mt-4">';
            echo '<strong>❌ Migração falhou!</strong><br>';
            echo 'Verifique os erros acima e tente novamente.';
            echo '</div>';
        }
        ?>
        
        <hr class="my-4">
        
        <div class="alert alert-info">
            <strong><i class="fas fa-info-circle"></i> Informações:</strong>
            <ul class="mb-0 mt-2">
                <li>Este script pode ser executado múltiplas vezes com segurança (verifica se a coluna já existe)</li>
                <li>Após confirmar que a migração foi bem-sucedida, você pode remover este arquivo</li>
                <li>A coluna <code>precisa_trocar_senha</code> será usada pelo sistema de redefinição de senha</li>
            </ul>
        </div>
    </div>
</body>
</html>

