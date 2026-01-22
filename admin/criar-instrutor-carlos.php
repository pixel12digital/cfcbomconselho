<?php
/**
 * Script temporário para criar registro de instrutor para Carlos da Silva (usuario_id=44)
 * 
 * Problema: Usuário existe na tabela usuarios com tipo='instrutor', mas não existe registro em instrutores
 * 
 * Executar via navegador: http://localhost/cfc-bom-conselho/admin/criar-instrutor-carlos.php
 * OU via CLI: php admin/criar-instrutor-carlos.php
 */

// Permitir execução via CLI sem autenticação
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Requer autenticação se executado via navegador
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    
    $user = getCurrentUser();
    if (!$user || !canManageUsers()) {
        die('Acesso negado. Apenas administradores e secretárias podem executar este script.');
    }
} else {
    // CLI: apenas incluir config e database
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/database.php';
}

header('Content-Type: text/html; charset=utf-8');

$db = db();
$usuarioId = 44;

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Criar Instrutor - Carlos da Silva</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Criar Instrutor - Carlos da Silva</h1>";

try {
    // 1. Verificar se o usuário existe
    echo "<h2>1. Verificando usuário...</h2>";
    $usuario = $db->fetch("SELECT id, nome, email, tipo FROM usuarios WHERE id = ?", [$usuarioId]);
    
    if (!$usuario) {
        echo "<div class='error'>❌ Usuário com ID $usuarioId não encontrado na tabela usuarios.</div>";
        exit;
    }
    
    echo "<div class='success'>✅ Usuário encontrado:</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th></tr>";
    echo "<tr><td>{$usuario['id']}</td><td>{$usuario['nome']}</td><td>{$usuario['email']}</td><td>{$usuario['tipo']}</td></tr>";
    echo "</table>";
    
    if ($usuario['tipo'] !== 'instrutor') {
        echo "<div class='warning'>⚠️ Atenção: O tipo do usuário é '{$usuario['tipo']}', não 'instrutor'.</div>";
    }
    
    // 2. Verificar se já existe registro em instrutores
    echo "<h2>2. Verificando registro em instrutores...</h2>";
    $instrutorExistente = $db->fetch("SELECT id, nome, usuario_id, cfc_id, credencial, ativo FROM instrutores WHERE usuario_id = ?", [$usuarioId]);
    
    if ($instrutorExistente) {
        echo "<div class='warning'>⚠️ Já existe um registro de instrutor para este usuário:</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nome</th><th>Usuario ID</th><th>CFC ID</th><th>Credencial</th><th>Ativo</th></tr>";
        echo "<tr><td>{$instrutorExistente['id']}</td><td>{$instrutorExistente['nome']}</td><td>{$instrutorExistente['usuario_id']}</td><td>{$instrutorExistente['cfc_id']}</td><td>{$instrutorExistente['credencial']}</td><td>" . ($instrutorExistente['ativo'] ? 'Sim' : 'Não') . "</td></tr>";
        echo "</table>";
        echo "<div class='info'>ℹ️ Nenhuma ação necessária. O registro já existe.</div>";
        exit;
    }
    
    echo "<div class='info'>ℹ️ Nenhum registro encontrado. Será criado um novo registro.</div>";
    
    // 3. Buscar primeiro CFC disponível
    echo "<h2>3. Buscando CFC disponível...</h2>";
    $cfc = $db->fetch("SELECT id, nome FROM cfcs ORDER BY id LIMIT 1");
    
    if (!$cfc) {
        echo "<div class='error'>❌ Nenhum CFC encontrado no banco de dados. É necessário criar um CFC primeiro.</div>";
        exit;
    }
    
    echo "<div class='success'>✅ CFC encontrado:</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Nome</th></tr>";
    echo "<tr><td>{$cfc['id']}</td><td>{$cfc['nome']}</td></tr>";
    echo "</table>";
    
    // 4. Gerar credencial única
    $credencial = 'CRED-' . str_pad($usuarioId, 6, '0', STR_PAD_LEFT);
    
    // Verificar se credencial já existe
    $credencialExistente = $db->fetch("SELECT id FROM instrutores WHERE credencial = ?", [$credencial]);
    if ($credencialExistente) {
        // Se existir, adicionar sufixo
        $credencial = 'CRED-' . str_pad($usuarioId, 6, '0', STR_PAD_LEFT) . '-' . time();
    }
    
    // 5. Criar registro de instrutor
    echo "<h2>4. Criando registro de instrutor...</h2>";
    
    $instrutorData = [
        'nome' => $usuario['nome'] ?? 'Carlos da Silva',
        'usuario_id' => $usuarioId,
        'cfc_id' => $cfc['id'],
        'credencial' => $credencial,
        'ativo' => 1,
        'criado_em' => date('Y-m-d H:i:s')
    ];
    
    echo "<div class='info'>ℹ️ Dados que serão inseridos:</div>";
    echo "<pre>" . json_encode($instrutorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    $instrutorId = $db->insert('instrutores', $instrutorData);
    
    if (!$instrutorId) {
        $error = $db->getLastError();
        echo "<div class='error'>❌ Erro ao criar instrutor: " . htmlspecialchars($error) . "</div>";
        exit;
    }
    
    echo "<div class='success'>✅ Instrutor criado com sucesso! ID: $instrutorId</div>";
    
    // 6. Verificar registro criado
    echo "<h2>5. Verificando registro criado...</h2>";
    $instrutorCriado = $db->fetch("
        SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, c.nome as cfc_nome 
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        LEFT JOIN cfcs c ON i.cfc_id = c.id 
        WHERE i.id = ?
    ", [$instrutorId]);
    
    if ($instrutorCriado) {
        echo "<div class='success'>✅ Registro confirmado:</div>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$instrutorCriado['id']}</td></tr>";
        echo "<tr><td>Nome</td><td>{$instrutorCriado['nome']}</td></tr>";
        echo "<tr><td>Usuario ID</td><td>{$instrutorCriado['usuario_id']}</td></tr>";
        echo "<tr><td>CFC</td><td>{$instrutorCriado['cfc_nome']} (ID: {$instrutorCriado['cfc_id']})</td></tr>";
        echo "<tr><td>Credencial</td><td>{$instrutorCriado['credencial']}</td></tr>";
        echo "<tr><td>Ativo</td><td>" . ($instrutorCriado['ativo'] ? 'Sim' : 'Não') . "</td></tr>";
        echo "<tr><td>Criado em</td><td>{$instrutorCriado['criado_em']}</td></tr>";
        echo "</table>";
        
        echo "<div class='success'><strong>✅ Processo concluído com sucesso!</strong></div>";
        echo "<div class='info'>ℹ️ Agora você pode testar a página <a href='../instrutor/ocorrencias.php'>instrutor/ocorrencias.php</a></div>";
    } else {
        echo "<div class='error'>❌ Erro: Registro não foi encontrado após a criação.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "    </div>
</body>
</html>";

