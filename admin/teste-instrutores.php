<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Select de Instrutores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Teste - Select de Instrutores</h1>
        <p>Este teste verifica se todos os instrutores estão sendo carregados corretamente.</p>
        
        <?php
        require_once 'includes/config.php';
        require_once 'includes/database.php';
        
        try {
            $db = db();
            
            // Query corrigida
            $instrutores = $db->fetchAll("
                SELECT i.*, 
                       COALESCE(u.nome, i.nome) as nome,
                       COALESCE(u.email, i.email) as email,
                       COALESCE(u.telefone, i.telefone) as telefone,
                       i.categoria_habilitacao
                FROM instrutores i
                LEFT JOIN usuarios u ON i.usuario_id = u.id
                WHERE i.ativo = 1
                ORDER BY COALESCE(u.nome, i.nome)
            ");
            
            echo "<div class='alert alert-info'>";
            echo "<strong>Total de instrutores encontrados:</strong> " . count($instrutores);
            echo "</div>";
            
            echo "<h3>Select de Instrutores:</h3>";
            echo "<select class='form-select' id='instrutor_id' name='instrutor_id'>";
            echo "<option value=''>Selecione o instrutor</option>";
            
            foreach ($instrutores as $instrutor) {
                echo "<option value='" . $instrutor['id'] . "'>";
                echo htmlspecialchars($instrutor['nome']) . " - " . htmlspecialchars($instrutor['categoria_habilitacao']);
                echo "</option>";
            }
            
            echo "</select>";
            
            echo "<h3 class='mt-4'>Lista Detalhada:</h3>";
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Categoria</th><th>Ativo</th></tr></thead>";
            echo "<tbody>";
            
            foreach ($instrutores as $instrutor) {
                echo "<tr>";
                echo "<td>" . $instrutor['id'] . "</td>";
                echo "<td>" . htmlspecialchars($instrutor['nome']) . "</td>";
                echo "<td>" . htmlspecialchars($instrutor['email']) . "</td>";
                echo "<td>" . htmlspecialchars($instrutor['categoria_habilitacao']) . "</td>";
                echo "<td>" . ($instrutor['ativo'] ? 'Sim' : 'Não') . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>";
            echo "<strong>Erro:</strong> " . $e->getMessage();
            echo "</div>";
        }
        ?>
        
        <hr>
        <p><a href="index.php?page=agendamento" class="btn btn-primary">Voltar para Agendamento</a></p>
        <p><a href="debug-instrutores.php" class="btn btn-secondary">Ver Debug Completo</a></p>
    </div>
</body>
</html>
