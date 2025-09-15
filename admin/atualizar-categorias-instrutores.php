<?php
// Script para atualizar categorias de habilitação dos instrutores
// Acesse: admin/atualizar-categorias-instrutores.php

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>🔧 Atualizar Categorias de Habilitação dos Instrutores</h2>";

try {
    $db = db();
    
    echo "<h3>1. Verificando instrutores sem categoria:</h3>";
    
    $instrutoresSemCategoria = $db->fetchAll("
        SELECT i.*, 
               COALESCE(u.nome, i.nome) as nome_usuario
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1 
        AND (i.categoria_habilitacao IS NULL OR i.categoria_habilitacao = '' OR i.categoria_habilitacao = 'N/A')
        AND (i.categorias_json IS NULL OR i.categorias_json = '' OR i.categorias_json = '[]')
        ORDER BY i.id
    ");
    
    if (count($instrutoresSemCategoria) > 0) {
        echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>⚠️ Instrutores encontrados sem categoria:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Credencial</th><th>Ação</th></tr>";
        
        foreach ($instrutoresSemCategoria as $instrutor) {
            echo "<tr>";
            echo "<td>" . $instrutor['id'] . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['nome_usuario']) . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['credencial']) . "</td>";
            echo "<td>";
            echo "<button onclick='atualizarCategoria(" . $instrutor['id'] . ", \"A,B\")' class='btn btn-sm btn-primary'>Definir A,B</button> ";
            echo "<button onclick='atualizarCategoria(" . $instrutor['id'] . ", \"A,E\")' class='btn btn-sm btn-success'>Definir A,E</button> ";
            echo "<button onclick='atualizarCategoria(" . $instrutor['id'] . ", \"B\")' class='btn btn-sm btn-info'>Definir B</button>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        echo "<h3>2. Atualização em lote (opcional):</h3>";
        echo "<div style='background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>Opção 1:</strong> Definir categoria padrão para todos os instrutores sem categoria</p>";
        echo "<button onclick='atualizarTodos(\"A,B\")' class='btn btn-warning'>Definir A,B para todos</button> ";
        echo "<button onclick='atualizarTodos(\"A,E\")' class='btn btn-warning'>Definir A,E para todos</button>";
        echo "</div>";
        
    } else {
        echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>✅ Todos os instrutores já possuem categoria definida!</h4>";
        echo "</div>";
    }
    
    echo "<h3>3. Verificação final:</h3>";
    $instrutoresAtualizados = $db->fetchAll("
        SELECT i.*, 
               COALESCE(u.nome, i.nome) as nome,
               CASE 
                   WHEN i.categorias_json IS NOT NULL AND i.categorias_json != '' AND i.categorias_json != '[]' THEN 
                       REPLACE(REPLACE(REPLACE(i.categorias_json, '[', ''), ']', ''), '\"', '')
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
    echo "<tr><th>Nome</th><th>Categoria</th><th>Status</th></tr>";
    
    foreach ($instrutoresAtualizados as $instrutor) {
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

?>

<script>
function atualizarCategoria(instrutorId, categoria) {
    if (confirm('Definir categoria "' + categoria + '" para o instrutor ID ' + instrutorId + '?')) {
        fetch('api/atualizar-categoria-instrutor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                instrutor_id: instrutorId,
                categoria: categoria
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert('Categoria atualizada com sucesso!');
                location.reload();
            } else {
                alert('Erro ao atualizar categoria: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar categoria');
        });
    }
}

function atualizarTodos(categoria) {
    if (confirm('Definir categoria "' + categoria + '" para TODOS os instrutores sem categoria?')) {
        fetch('api/atualizar-categoria-instrutor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                categoria: categoria,
                atualizar_todos: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert('Categorias atualizadas com sucesso!');
                location.reload();
            } else {
                alert('Erro ao atualizar categorias: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar categorias');
        });
    }
}
</script>

<hr>
<p><a href="index.php?page=agendamento">Voltar para Agendamento</a></p>
<p><a href="debug-categoria-instrutores.php">Ver Debug Detalhado</a></p>
