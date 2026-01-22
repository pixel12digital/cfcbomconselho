<?php
/**
 * Verificar e atualizar ENUM do campo resultado na tabela exames
 * 
 * Este script verifica se o ENUM inclui 'aprovado' e 'reprovado'
 * e atualiza se necessário.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

$db = db();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar/Atualizar ENUM resultado exames</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔍 Verificar/Atualizar ENUM do Campo resultado na Tabela exames</h1>

    <?php
    try {
        // 1. Verificar estrutura atual do campo resultado
        echo '<div class="section">';
        echo '<h2>1. Estrutura Atual do Campo resultado</h2>';
        
        $coluna = $db->fetch("
            SHOW COLUMNS FROM exames WHERE Field = 'resultado'
        ");
        
        if ($coluna) {
            echo '<div class="success">✅ Campo resultado encontrado</div>';
            echo '<pre>';
            echo 'Tipo: ' . htmlspecialchars($coluna['Type']) . "\n";
            echo 'Permite NULL: ' . ($coluna['Null'] === 'YES' ? 'SIM' : 'NÃO') . "\n";
            echo 'Valor padrão: ' . htmlspecialchars($coluna['Default'] ?? 'Nenhum') . "\n";
            echo '</pre>';
            
            // Extrair valores do ENUM
            preg_match("/enum\('(.+)'\)/", $coluna['Type'], $matches);
            $valoresAtuais = [];
            if ($matches && isset($matches[1])) {
                $valoresAtuais = explode("','", $matches[1]);
            }
            
            echo '<p><strong>Valores atuais do ENUM:</strong></p>';
            echo '<ul>';
            foreach ($valoresAtuais as $valor) {
                echo '<li>' . htmlspecialchars($valor) . '</li>';
            }
            echo '</ul>';
            
            // Verificar se inclui 'aprovado' e 'reprovado'
            $temAprovado = in_array('aprovado', $valoresAtuais);
            $temReprovado = in_array('reprovado', $valoresAtuais);
            
            if (!$temAprovado || !$temReprovado) {
                echo '<div class="warning">⚠️ O ENUM não inclui todos os valores necessários!</div>';
                echo '<ul>';
                if (!$temAprovado) {
                    echo '<li>❌ Falta: <strong>aprovado</strong></li>';
                }
                if (!$temReprovado) {
                    echo '<li>❌ Falta: <strong>reprovado</strong></li>';
                }
                echo '</ul>';
                
                // 2. Mostrar ação para atualizar
                echo '</div>';
                echo '<div class="section">';
                echo '<h2>2. Atualizar ENUM</h2>';
                
                if (isset($_POST['atualizar'])) {
                    // Atualizar ENUM
                    $valoresNovos = array_unique(array_merge($valoresAtuais, ['aprovado', 'reprovado']));
                    $valoresNovosStr = "'" . implode("','", $valoresNovos) . "'";
                    
                    $sql = "ALTER TABLE exames MODIFY COLUMN resultado ENUM({$valoresNovosStr}) DEFAULT 'pendente'";
                    
                    try {
                        $db->query($sql);
                        echo '<div class="success">✅ ENUM atualizado com sucesso!</div>';
                        echo '<p><strong>Novos valores do ENUM:</strong></p>';
                        echo '<ul>';
                        foreach ($valoresNovos as $valor) {
                            echo '<li>' . htmlspecialchars($valor) . '</li>';
                        }
                        echo '</ul>';
                        
                        // Recarregar estrutura
                        $coluna = $db->fetch("SHOW COLUMNS FROM exames WHERE Field = 'resultado'");
                        preg_match("/enum\('(.+)'\)/", $coluna['Type'], $matches);
                        if ($matches && isset($matches[1])) {
                            $valoresAtuais = explode("','", $matches[1]);
                        }
                    } catch (Exception $e) {
                        echo '<div class="error">❌ Erro ao atualizar ENUM: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<p>Para atualizar o ENUM, clique no botão abaixo:</p>';
                    echo '<form method="POST">';
                    echo '<button type="submit" name="atualizar">Atualizar ENUM</button>';
                    echo '</form>';
                    
                    echo '<p><strong>SQL que será executado:</strong></p>';
                    $valoresNovos = array_unique(array_merge($valoresAtuais, ['aprovado', 'reprovado']));
                    $valoresNovosStr = "'" . implode("','", $valoresNovos) . "'";
                    $sqlExemplo = "ALTER TABLE exames MODIFY COLUMN resultado ENUM({$valoresNovosStr}) DEFAULT 'pendente'";
                    echo '<pre>' . htmlspecialchars($sqlExemplo) . '</pre>';
                }
                echo '</div>'; // Fechar div da seção 2 (apenas se precisou atualizar)
            } else {
                echo '<div class="success">✅ O ENUM já inclui todos os valores necessários (aprovado e reprovado)</div>';
            }
        } else {
            echo '<div class="error">❌ Campo resultado não encontrado na tabela exames!</div>';
        }
        echo '</div>';
        
        // 3. Verificar valores atuais no banco
        echo '<div class="section">';
        echo '<h2>3. Valores Atualmente Usados no Banco</h2>';
        
        $resultados = $db->fetchAll("
            SELECT resultado, COUNT(*) as total
            FROM exames
            GROUP BY resultado
            ORDER BY resultado
        ");
        
        if (empty($resultados)) {
            echo '<div class="warning">⚠️ Nenhum exame encontrado no banco.</div>';
        } else {
            echo '<table border="1" cellpadding="8" style="width:100%; border-collapse: collapse;">';
            echo '<tr><th>Resultado</th><th>Total</th></tr>';
            foreach ($resultados as $r) {
                $valor = $r['resultado'] ? htmlspecialchars($r['resultado']) : '<em>NULL</em>';
                echo '<tr>';
                echo '<td>' . $valor . '</td>';
                echo '<td>' . $r['total'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section">';
        echo '<h2>❌ Erro</h2>';
        echo '<div class="error">' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>

    <div class="section">
        <h2>📝 Notas</h2>
        <ul>
            <li>O campo <code>resultado</code> deve incluir: <code>apto</code>, <code>inapto</code>, <code>inapto_temporario</code>, <code>pendente</code>, <code>aprovado</code>, <code>reprovado</code></li>
            <li><code>aprovado</code> e <code>reprovado</code> são usados para provas teóricas e práticas</li>
            <li><code>apto</code> e <code>inapto</code> são usados para exames médico e psicotécnico</li>
        </ul>
    </div>
</body>
</html>

