<?php
// API para atualizar categorias de habilitação dos instrutores
// admin/api/atualizar-categoria-instrutor.php

header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dados não fornecidos');
    }
    
    $db = db();
    
    if (isset($input['atualizar_todos']) && $input['atualizar_todos']) {
        // Atualizar todos os instrutores sem categoria
        $categoria = $input['categoria'] ?? 'A,B';
        
        $resultado = $db->execute("
            UPDATE instrutores 
            SET categoria_habilitacao = ? 
            WHERE ativo = 1 
            AND (categoria_habilitacao IS NULL OR categoria_habilitacao = '' OR categoria_habilitacao = 'N/A')
            AND (categorias_json IS NULL OR categorias_json = '' OR categorias_json = '[]')
        ", [$categoria]);
        
        $linhasAfetadas = $db->rowCount();
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => "Categoria '$categoria' definida para $linhasAfetadas instrutores",
            'linhas_afetadas' => $linhasAfetadas
        ]);
        
    } else {
        // Atualizar instrutor específico
        $instrutorId = $input['instrutor_id'] ?? null;
        $categoria = $input['categoria'] ?? null;
        
        if (!$instrutorId || !$categoria) {
            throw new Exception('ID do instrutor e categoria são obrigatórios');
        }
        
        $resultado = $db->execute("
            UPDATE instrutores 
            SET categoria_habilitacao = ? 
            WHERE id = ? AND ativo = 1
        ", [$categoria, $instrutorId]);
        
        if ($resultado) {
            echo json_encode([
                'sucesso' => true,
                'mensagem' => "Categoria '$categoria' definida para o instrutor ID $instrutorId"
            ]);
        } else {
            throw new Exception('Erro ao atualizar categoria do instrutor');
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}
?>
