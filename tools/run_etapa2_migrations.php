<?php

/**
 * Script para executar migrations da Etapa 2
 * Gerenciamento de Acessos e Credenciais
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== Executando Migrations da Etapa 2 ===\n\n";

$migrations = [
    '017_add_user_id_to_students.sql' => 'Adicionar user_id em students',
    '018_create_password_reset_tokens.sql' => 'Criar tabela de tokens de recuperação',
    '019_create_smtp_settings.sql' => 'Criar tabela de configurações SMTP',
];

$seeds = [
    '006_seed_usuarios_permissions.sql' => 'Inserir permissões do módulo de usuários',
];

// Executar migrations
foreach ($migrations as $file => $description) {
    echo "Executando: {$description}...\n";
    $filePath = __DIR__ . '/../database/migrations/' . $file;
    
    if (!file_exists($filePath)) {
        echo "  ❌ Arquivo não encontrado: {$file}\n";
        continue;
    }
    
    $sql = file_get_contents($filePath);
    
    try {
        // Remover comentários e executar SQL completo
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Executar cada statement separadamente (dividir por ;)
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && strlen($statement) > 5) {
                try {
                    $db->exec($statement);
                } catch (\PDOException $e) {
                    // Ignorar erros de "já existe" ou "duplicado"
                    if (strpos($e->getMessage(), 'Duplicate') === false && 
                        strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), 'Duplicate column') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        echo "  ✅ {$description} - OK\n";
    } catch (\Exception $e) {
        // Verificar se é erro de coluna já existe (pode ignorar)
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false ||
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "  ⚠️  {$description} - Já existe (ignorado)\n";
        } else {
            echo "  ❌ Erro: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== Executando Seeds ===\n\n";

// Executar seeds
foreach ($seeds as $file => $description) {
    echo "Executando: {$description}...\n";
    $filePath = __DIR__ . '/../database/seeds/' . $file;
    
    if (!file_exists($filePath)) {
        echo "  ❌ Arquivo não encontrado: {$file}\n";
        continue;
    }
    
    $sql = file_get_contents($filePath);
    
    try {
        // Executar cada statement separadamente
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                $db->exec($statement);
            }
        }
        
        echo "  ✅ {$description} - OK\n";
    } catch (\Exception $e) {
        // Verificar se é erro de duplicata (pode ignorar)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "  ⚠️  {$description} - Já existe (ignorado)\n";
        } else {
            echo "  ❌ Erro: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== Concluído! ===\n";
echo "\nPróximos passos:\n";
echo "1. Acesse /configuracoes/smtp como ADMIN para configurar SMTP\n";
echo "2. Acesse /usuarios como ADMIN para criar acessos\n";
echo "3. Teste os fluxos de alteração e recuperação de senha\n";
