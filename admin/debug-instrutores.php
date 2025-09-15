<?php
// Script de debug para verificar carregamento de instrutores
// Acesse: admin/debug-instrutores.php

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Debug - Carregamento de Instrutores</h2>";

try {
    $db = db();
    
    echo "<h3>1. Query usada na página de agendamento:</h3>";
    $query_agendamento = "
        SELECT i.*, u.nome, u.email, u.telefone
        FROM instrutores i
        JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY u.nome
    ";
    
    echo "<pre>" . htmlspecialchars($query_agendamento) . "</pre>";
    
    $instrutores_agendamento = $db->fetchAll($query_agendamento);
    echo "<p><strong>Resultado:</strong> " . count($instrutores_agendamento) . " instrutores encontrados</p>";
    
    echo "<h3>2. Lista de instrutores encontrados:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Ativo</th><th>CFC ID</th><th>Credencial</th><th>Categoria</th></tr>";
    
    foreach ($instrutores_agendamento as $instrutor) {
        echo "<tr>";
        echo "<td>" . $instrutor['id'] . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['email']) . "</td>";
        echo "<td>" . ($instrutor['ativo'] ? 'Sim' : 'Não') . "</td>";
        echo "<td>" . $instrutor['cfc_id'] . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['credencial']) . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['categoria_habilitacao']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3. Query alternativa (sem JOIN com usuarios):</h3>";
    $query_alternativa = "
        SELECT i.*, 
               COALESCE(u.nome, i.nome) as nome,
               COALESCE(u.email, i.email) as email,
               COALESCE(u.telefone, i.telefone) as telefone
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY COALESCE(u.nome, i.nome)
    ";
    
    echo "<pre>" . htmlspecialchars($query_alternativa) . "</pre>";
    
    $instrutores_alternativa = $db->fetchAll($query_alternativa);
    echo "<p><strong>Resultado:</strong> " . count($instrutores_alternativa) . " instrutores encontrados</p>";
    
    echo "<h3>4. Verificação de dados na tabela instrutores:</h3>";
    $todos_instrutores = $db->fetchAll("SELECT * FROM instrutores ORDER BY id");
    echo "<p><strong>Total de instrutores na tabela:</strong> " . count($todos_instrutores) . "</p>";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Usuario ID</th><th>CFC ID</th><th>Credencial</th><th>Categoria</th><th>Ativo</th><th>Nome (usuarios)</th></tr>";
    
    foreach ($todos_instrutores as $instrutor) {
        // Buscar nome do usuário
        $usuario = $db->fetch("SELECT nome FROM usuarios WHERE id = ?", [$instrutor['usuario_id']]);
        $nome_usuario = $usuario ? $usuario['nome'] : 'N/A';
        
        echo "<tr>";
        echo "<td>" . $instrutor['id'] . "</td>";
        echo "<td>" . $instrutor['usuario_id'] . "</td>";
        echo "<td>" . $instrutor['cfc_id'] . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['credencial']) . "</td>";
        echo "<td>" . htmlspecialchars($instrutor['categoria_habilitacao']) . "</td>";
        echo "<td>" . ($instrutor['ativo'] ? 'Sim' : 'Não') . "</td>";
        echo "<td>" . htmlspecialchars($nome_usuario) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>5. Verificação de usuários:</h3>";
    $usuarios = $db->fetchAll("SELECT * FROM usuarios ORDER BY id");
    echo "<p><strong>Total de usuários:</strong> " . count($usuarios) . "</p>";
    
    echo "<h3>6. Instrutores sem usuário correspondente:</h3>";
    $instrutores_sem_usuario = $db->fetchAll("
        SELECT i.* 
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        WHERE u.id IS NULL
    ");
    
    if (count($instrutores_sem_usuario) > 0) {
        echo "<p><strong>Problema encontrado:</strong> " . count($instrutores_sem_usuario) . " instrutores sem usuário correspondente</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Usuario ID</th><th>Credencial</th><th>Categoria</th><th>Ativo</th></tr>";
        
        foreach ($instrutores_sem_usuario as $instrutor) {
            echo "<tr>";
            echo "<td>" . $instrutor['id'] . "</td>";
            echo "<td>" . $instrutor['usuario_id'] . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['credencial']) . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['categoria_habilitacao']) . "</td>";
            echo "<td>" . ($instrutor['ativo'] ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>✅ Todos os instrutores têm usuário correspondente</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php?page=agendamento'>Voltar para Agendamento</a></p>";
?>
