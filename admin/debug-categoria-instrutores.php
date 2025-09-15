<?php
// Script para investigar por que aparece N/A na categoria_habilitacao
// Acesse: admin/debug-categoria-instrutores.php

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>🔍 Debug - Categoria de Habilitação dos Instrutores</h2>";

try {
    $db = db();
    
    echo "<h3>1. Verificação da estrutura da tabela instrutores:</h3>";
    $estrutura = $db->fetchAll("DESCRIBE instrutores");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($estrutura as $campo) {
        echo "<tr>";
        echo "<td>" . $campo['Field'] . "</td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . ($campo['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $campo['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Dados reais dos instrutores:</h3>";
    $instrutores = $db->fetchAll("
        SELECT i.*, 
               COALESCE(u.nome, i.nome) as nome_usuario,
               COALESCE(u.email, i.email) as email_usuario,
               COALESCE(u.telefone, i.telefone) as telefone_usuario
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY i.id
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Credencial</th><th>Categoria Original</th><th>Categoria JSON</th><th>Usuario ID</th><th>CFC ID</th><th>Ativo</th></tr>";
    
    foreach ($instrutores as $instrutor) {
        echo "<tr>";
        echo "<td>" . $instrutor['id'] . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['nome_usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['credencial']) . "</td>";
        echo "<td style='background-color: " . (empty($instrutor['categoria_habilitacao']) ? '#ffcccc' : '#ccffcc') . ";'>";
        echo htmlspecialchars($instrutor['categoria_habilitacao'] ?? 'NULL');
        echo "</td>";
        echo "<td style='background-color: " . (empty($instrutor['categorias_json']) ? '#ffcccc' : '#ccffcc') . ";'>";
        echo htmlspecialchars($instrutor['categorias_json'] ?? 'NULL');
        echo "</td>";
        echo "<td>" . $instrutor['usuario_id'] . "</td>";
        echo "<td>" . $instrutor['cfc_id'] . "</td>";
        echo "<td>" . ($instrutor['ativo'] ? 'Sim' : 'Não') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3. Análise dos dados:</h3>";
    
    $categoriaVazia = 0;
    $categoriaJsonVazia = 0;
    $totalInstrutores = count($instrutores);
    
    foreach ($instrutores as $instrutor) {
        if (empty($instrutor['categoria_habilitacao'])) {
            $categoriaVazia++;
        }
        if (empty($instrutor['categorias_json'])) {
            $categoriaJsonVazia++;
        }
    }
    
    echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>📊 Estatísticas:</h4>";
    echo "<ul>";
    echo "<li><strong>Total de instrutores ativos:</strong> " . $totalInstrutores . "</li>";
    echo "<li><strong>Categoria_habilitacao vazia:</strong> " . $categoriaVazia . " (" . round(($categoriaVazia/$totalInstrutores)*100, 2) . "%)</li>";
    echo "<li><strong>Categorias_json vazia:</strong> " . $categoriaJsonVazia . " (" . round(($categoriaJsonVazia/$totalInstrutores)*100, 2) . "%)</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>4. Query atual vs Query melhorada:</h3>";
    
    echo "<h4>Query Atual (que está causando N/A):</h4>";
    echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 5px;'>";
    echo "SELECT i.*, 
       COALESCE(u.nome, i.nome) as nome,
       COALESCE(u.email, i.email) as email,
       COALESCE(u.telefone, i.telefone) as telefone,
       i.categoria_habilitacao
FROM instrutores i
LEFT JOIN usuarios u ON i.usuario_id = u.id
WHERE i.ativo = 1
ORDER BY COALESCE(u.nome, i.nome)";
    echo "</pre>";
    
    echo "<h4>Query Melhorada (sugestão):</h4>";
    echo "<pre style='background-color: #e8f5e8; padding: 10px; border-radius: 5px;'>";
    echo "SELECT i.*, 
       COALESCE(u.nome, i.nome) as nome,
       COALESCE(u.email, i.email) as email,
       COALESCE(u.telefone, i.telefone) as telefone,
       CASE 
           WHEN i.categorias_json IS NOT NULL AND i.categorias_json != '' THEN 
               REPLACE(REPLACE(i.categorias_json, '[', ''), ']', '')
           WHEN i.categoria_habilitacao IS NOT NULL AND i.categoria_habilitacao != '' THEN 
               i.categoria_habilitacao
           ELSE 'Sem categoria'
       END as categoria_habilitacao
FROM instrutores i
LEFT JOIN usuarios u ON i.usuario_id = u.id
WHERE i.ativo = 1
ORDER BY COALESCE(u.nome, i.nome)";
    echo "</pre>";
    
    echo "<h3>5. Teste da query melhorada:</h3>";
    $instrutoresMelhorados = $db->fetchAll("
        SELECT i.*, 
               COALESCE(u.nome, i.nome) as nome,
               COALESCE(u.email, i.email) as email,
               COALESCE(u.telefone, i.telefone) as telefone,
               CASE 
                   WHEN i.categorias_json IS NOT NULL AND i.categorias_json != '' THEN 
                       REPLACE(REPLACE(i.categorias_json, '[', ''), ']', '')
                   WHEN i.categoria_habilitacao IS NOT NULL AND i.categoria_habilitacao != '' THEN 
                       i.categoria_habilitacao
                   ELSE 'Sem categoria'
               END as categoria_habilitacao
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY COALESCE(u.nome, i.nome)
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Nome</th><th>Categoria Melhorada</th><th>Status</th></tr>";
    
    foreach ($instrutoresMelhorados as $instrutor) {
        $status = '';
        if ($instrutor['categoria_habilitacao'] === 'Sem categoria') {
            $status = '<span style="color: red;">⚠️ Precisa definir categoria</span>';
        } else {
            $status = '<span style="color: green;">✅ OK</span>';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($instrutor['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['categoria_habilitacao']) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=agendamento'>Voltar para Agendamento</a></p>";
echo "<p><a href='teste-instrutores.php'>Testar Select de Instrutores</a></p>";
?>
