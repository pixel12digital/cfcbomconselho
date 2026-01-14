<?php

/**
 * Script para inserir permissões do módulo usuarios manualmente
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== Inserindo Permissões do Módulo Usuários ===\n\n";

try {
    // Inserir permissões
    $permissions = [
        ['modulo' => 'usuarios', 'acao' => 'listar', 'descricao' => 'Listar usuários'],
        ['modulo' => 'usuarios', 'acao' => 'criar', 'descricao' => 'Criar novo usuário'],
        ['modulo' => 'usuarios', 'acao' => 'editar', 'descricao' => 'Editar usuário'],
        ['modulo' => 'usuarios', 'acao' => 'excluir', 'descricao' => 'Excluir usuário'],
        ['modulo' => 'usuarios', 'acao' => 'visualizar', 'descricao' => 'Visualizar detalhes do usuário'],
    ];
    
    $stmt = $db->prepare("
        INSERT INTO permissoes (modulo, acao, descricao) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE descricao = VALUES(descricao)
    ");
    
    foreach ($permissions as $perm) {
        $stmt->execute([$perm['modulo'], $perm['acao'], $perm['descricao']]);
        echo "✅ Permissão: {$perm['modulo']}.{$perm['acao']}\n";
    }
    
    // Associar permissões ao ADMIN
    echo "\nAssociando permissões ao ADMIN...\n";
    
    $stmt = $db->prepare("
        INSERT INTO role_permissoes (role, permissao_id)
        SELECT 'ADMIN', id FROM permissoes
        WHERE modulo = 'usuarios'
        ON DUPLICATE KEY UPDATE role = VALUES(role)
    ");
    
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "✅ {$count} permissões associadas ao ADMIN\n";
    
    echo "\n=== Concluído! ===\n";
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
