<?php
/**
 * SCRIPT TEMPORÁRIO DE MIGRAÇÃO – REMOVER APÓS EXECUÇÃO
 * 
 * SYNC_INSTRUTORES - Sincronização de usuários tipo 'instrutor' com tabela instrutores
 * 
 * Objetivo: Garantir que, para todo usuarios.tipo = 'instrutor', exista um registro correspondente em instrutores.usuario_id
 * 
 * Executar via navegador: http://localhost/cfc-bom-conselho/admin/migrate-sync-instrutores.php
 * 
 * IMPORTANTE: Este script deve ser removido após validação em desenvolvimento
 */

// Configuração: CFC ID padrão (ajustar conforme necessário)
// Se não houver CFC disponível, o script tentará buscar o primeiro CFC do banco
$DEFAULT_CFC_ID = 1; // ATENÇÃO: Ajustar este valor conforme o CFC padrão do sistema

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Permitir execução via CLI sem autenticação
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Verificar autenticação e permissão (apenas admin) - apenas via navegador
    $user = getCurrentUser();
    if (!$user || !isAdmin()) {
        header('Content-Type: text/html; charset=utf-8');
        die('
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Acesso Negado</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
                    .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; border: 1px solid #f5c6cb; }
                </style>
            </head>
            <body>
                <div class="error">
                    <h2>❌ Acesso Negado</h2>
                    <p>Apenas administradores podem executar este script de migração.</p>
                    <p>Usuário atual: ' . htmlspecialchars($user['email'] ?? 'Não logado') . ' (Tipo: ' . htmlspecialchars($user['tipo'] ?? 'N/A') . ')</p>
                </div>
            </body>
            </html>
        ');
    }
} else {
    // CLI: apenas logar que está executando via CLI
    if (LOG_ENABLED) {
        error_log('[SYNC_INSTRUTORES_MIGRATION] Executando via CLI - autenticação não requerida');
    }
}

// Headers apenas se não for CLI
if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
}

$db = db();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Migração - Sincronização de Instrutores</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #0c5460; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; border: 1px solid #dee2e6; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .summary { background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .summary h3 { margin-top: 0; color: #004085; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Migração - Sincronização de Instrutores</h1>
        <div class='warning'>
            <strong>⚠️ SCRIPT TEMPORÁRIO</strong><br>
            Este script deve ser removido após a execução bem-sucedida em desenvolvimento.
        </div>";

try {
    // 1. Buscar todos usuários tipo 'instrutor'
    echo "<h2>1. Buscando usuários tipo 'instrutor'...</h2>";
    $usuariosInstrutores = $db->fetchAll("SELECT id, nome, email, tipo, ativo FROM usuarios WHERE tipo = 'instrutor' ORDER BY id");
    
    if (empty($usuariosInstrutores)) {
        echo "<div class='info'>ℹ️ Nenhum usuário do tipo 'instrutor' encontrado no banco de dados.</div>";
        echo "    </div></body></html>";
        exit;
    }
    
    echo "<div class='success'>✅ Encontrados " . count($usuariosInstrutores) . " usuário(s) do tipo 'instrutor'</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Ativo</th></tr>";
    foreach ($usuariosInstrutores as $usuario) {
        echo "<tr>";
        echo "<td>{$usuario['id']}</td>";
        echo "<td>" . htmlspecialchars($usuario['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
        echo "<td>" . ($usuario['ativo'] ? 'Sim' : 'Não') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Verificar CFC disponível
    echo "<h2>2. Verificando CFC disponível...</h2>";
    $cfc = $db->fetch("SELECT id, nome FROM cfcs ORDER BY id LIMIT 1");
    
    if (!$cfc) {
        echo "<div class='error'>❌ Nenhum CFC encontrado no banco de dados. É necessário criar um CFC primeiro.</div>";
        echo "    </div></body></html>";
        exit;
    }
    
    $cfcId = $DEFAULT_CFC_ID;
    // Verificar se o CFC padrão existe, senão usar o primeiro encontrado
    $cfcPadrao = $db->fetch("SELECT id, nome FROM cfcs WHERE id = ?", [$cfcId]);
    if (!$cfcPadrao) {
        $cfcId = $cfc['id'];
        echo "<div class='warning'>⚠️ CFC padrão (ID: $DEFAULT_CFC_ID) não encontrado. Usando primeiro CFC disponível (ID: {$cfc['id']}, Nome: {$cfc['nome']})</div>";
    } else {
        echo "<div class='success'>✅ Usando CFC padrão: ID {$cfcPadrao['id']}, Nome: {$cfcPadrao['nome']}</div>";
    }
    
    // 3. Processar cada usuário
    echo "<h2>3. Processando usuários...</h2>";
    
    $totalUsuarios = count($usuariosInstrutores);
    $jaExistentes = 0;
    $criados = 0;
    $erros = 0;
    $detalhes = [];
    
    foreach ($usuariosInstrutores as $usuario) {
        $usuarioId = $usuario['id'];
        
        // Verificar se já existe registro em instrutores
        $instrutorExistente = $db->fetch("SELECT id, nome, cfc_id, credencial, ativo FROM instrutores WHERE usuario_id = ?", [$usuarioId]);
        
        if ($instrutorExistente) {
            $jaExistentes++;
            $detalhes[] = [
                'usuario_id' => $usuarioId,
                'usuario_nome' => $usuario['nome'],
                'status' => 'já_existia',
                'instrutor_id' => $instrutorExistente['id'],
                'mensagem' => "Instrutor já existe (ID: {$instrutorExistente['id']})"
            ];
            
            if (LOG_ENABLED) {
                error_log("[SYNC_INSTRUTORES_MIGRATION] [OK] usuario_id={$usuarioId} já possui instrutor_id={$instrutorExistente['id']}");
            }
        } else {
            // Criar registro usando a função helper
            $result = createInstrutorFromUser($usuarioId, $cfcId);
            
            if ($result['success'] && $result['created']) {
                $criados++;
                $detalhes[] = [
                    'usuario_id' => $usuarioId,
                    'usuario_nome' => $usuario['nome'],
                    'status' => 'criado',
                    'instrutor_id' => $result['instrutor_id'],
                    'mensagem' => "Instrutor criado com sucesso (ID: {$result['instrutor_id']})"
                ];
            } else {
                $erros++;
                $detalhes[] = [
                    'usuario_id' => $usuarioId,
                    'usuario_nome' => $usuario['nome'],
                    'status' => 'erro',
                    'instrutor_id' => null,
                    'mensagem' => $result['message'] ?? 'Erro desconhecido'
                ];
            }
        }
    }
    
    // 4. Exibir detalhes
    echo "<h2>4. Detalhes do processamento...</h2>";
    echo "<table>";
    echo "<tr><th>Usuario ID</th><th>Nome</th><th>Status</th><th>Instrutor ID</th><th>Mensagem</th></tr>";
    foreach ($detalhes as $detalhe) {
        $statusClass = '';
        $statusIcon = '';
        switch ($detalhe['status']) {
            case 'já_existia':
                $statusClass = 'info';
                $statusIcon = 'ℹ️';
                break;
            case 'criado':
                $statusClass = 'success';
                $statusIcon = '✅';
                break;
            case 'erro':
                $statusClass = 'error';
                $statusIcon = '❌';
                break;
        }
        
        echo "<tr class='{$statusClass}'>";
        echo "<td>{$detalhe['usuario_id']}</td>";
        echo "<td>" . htmlspecialchars($detalhe['usuario_nome']) . "</td>";
        echo "<td>{$statusIcon} " . ucfirst(str_replace('_', ' ', $detalhe['status'])) . "</td>";
        echo "<td>" . ($detalhe['instrutor_id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($detalhe['mensagem']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Resumo final
    echo "<div class='summary'>";
    echo "<h3>📊 Resumo Final</h3>";
    echo "<ul>";
    echo "<li><strong>Total de usuários instrutor encontrados:</strong> {$totalUsuarios}</li>";
    echo "<li><strong>Já possuíam registro em instrutores:</strong> {$jaExistentes}</li>";
    echo "<li><strong>Criados agora:</strong> {$criados}</li>";
    if ($erros > 0) {
        echo "<li><strong style='color: #dc3545;'>Erros:</strong> {$erros}</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    if ($criados > 0) {
        echo "<div class='success'>";
        echo "<strong>✅ Migração concluída com sucesso!</strong><br>";
        echo "Foram criados {$criados} registro(s) na tabela instrutores.";
        echo "</div>";
    } else if ($erros === 0) {
        echo "<div class='info'>";
        echo "<strong>ℹ️ Nenhuma ação necessária.</strong><br>";
        echo "Todos os usuários tipo 'instrutor' já possuem registro correspondente em instrutores.";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Migração concluída com erros.</strong><br>";
        echo "Verifique os detalhes acima e os logs do sistema.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Erro durante a migração:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "    </div>
</body>
</html>";

