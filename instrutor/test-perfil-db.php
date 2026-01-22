<?php
/**
 * Script de teste para verificar dados do perfil no banco
 * Acesse: instrutor/test-perfil-db.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    die('Acesso negado');
}

$db = db();
$instrutorId = getCurrentInstrutorId($user['id']);

if (!$instrutorId) {
    die('Instrutor não encontrado');
}

echo "<h2>Dados do Instrutor (ID: {$instrutorId})</h2>";

// Buscar dados da tabela instrutores
$instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ?", [$instrutorId]);
echo "<h3>Tabela: instrutores</h3>";
echo "<pre>";
print_r($instrutor);
echo "</pre>";

// Buscar dados da tabela usuarios
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$user['id']]);
echo "<h3>Tabela: usuarios (usuario_id: {$user['id']})</h3>";
echo "<pre>";
print_r($usuario);
echo "</pre>";

// Buscar dados combinados (como na página de perfil)
$combinado = $db->fetch("
    SELECT u.*, i.id as instrutor_id, i.cfc_id, i.credencial, i.foto as foto_instrutor, 
           i.email as email_instrutor, i.telefone as telefone_instrutor,
           c.nome as cfc_nome
    FROM usuarios u
    LEFT JOIN instrutores i ON i.usuario_id = u.id
    LEFT JOIN cfcs c ON c.id = i.cfc_id
    WHERE u.id = ?
", [$user['id']]);

echo "<h3>Dados Combinados (como na página perfil.php)</h3>";
echo "<pre>";
print_r($combinado);
echo "</pre>";

echo "<h3>Valores que devem aparecer na página:</h3>";
echo "<ul>";
echo "<li><strong>Foto:</strong> " . ($combinado['foto_instrutor'] ?? 'NULL') . "</li>";
echo "<li><strong>Email:</strong> " . ($combinado['email_instrutor'] ?? $combinado['email'] ?? 'NULL') . "</li>";
echo "<li><strong>Telefone:</strong> " . ($combinado['telefone_instrutor'] ?? $combinado['telefone'] ?? 'NULL') . "</li>";
echo "</ul>";
