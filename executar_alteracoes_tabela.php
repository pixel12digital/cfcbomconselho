<?php
// Script para executar alterações na tabela instrutores
echo "<h1>Adicionando Campos Faltantes na Tabela Instrutores</h1>";

try {
    // Incluir arquivos necessários
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    echo "✅ Arquivos incluídos com sucesso<br>";
    
    // Conectar ao banco
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida<br>";
    
    // Verificar estrutura atual
    echo "<h2>Estrutura Atual da Tabela:</h2>";
    $colunas = $db->fetchAll("DESCRIBE instrutores");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    $camposExistentes = [];
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
        
        $camposExistentes[] = $coluna['Field'];
    }
    echo "</table>";
    
    // Verificar quais campos precisam ser adicionados
    echo "<h2>Verificando Campos Faltantes:</h2>";
    
    $camposNecessarios = [
        'tipo_carga' => 'VARCHAR(100)',
        'validade_credencial' => 'DATE',
        'observacoes' => 'TEXT'
    ];
    
    $camposParaAdicionar = [];
    foreach ($camposNecessarios as $campo => $tipo) {
        if (!in_array($campo, $camposExistentes)) {
            $camposParaAdicionar[$campo] = $tipo;
            echo "❌ Campo <strong>$campo</strong> não existe - será adicionado<br>";
        } else {
            echo "✅ Campo <strong>$campo</strong> já existe<br>";
        }
    }
    
    if (empty($camposParaAdicionar)) {
        echo "<p style='color: green;'>🎉 Todos os campos necessários já existem!</p>";
        echo "<p><a href='admin/index.php?page=instrutores'>Clique aqui para testar o cadastro</a></p>";
        exit;
    }
    
    // Adicionar campos faltantes
    echo "<h2>Adicionando Campos Faltantes:</h2>";
    
    foreach ($camposParaAdicionar as $campo => $tipo) {
        try {
            $sql = "ALTER TABLE instrutores ADD COLUMN $campo $tipo NULL";
            
            if ($campo === 'tipo_carga') {
                $sql .= " COMMENT 'Tipo de carga que o instrutor pode transportar' AFTER categoria_habilitacao";
            } elseif ($campo === 'validade_credencial') {
                $sql .= " COMMENT 'Data de validade da credencial do instrutor' AFTER tipo_carga";
            } elseif ($campo === 'observacoes') {
                $sql .= " COMMENT 'Observações e notas sobre o instrutor' AFTER validade_credencial";
            }
            
            echo "Executando: <code>$sql</code><br>";
            
            $result = $db->query($sql);
            
            if ($result) {
                echo "✅ Campo <strong>$campo</strong> adicionado com sucesso!<br>";
            } else {
                echo "❌ Erro ao adicionar campo <strong>$campo</strong><br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Erro ao adicionar campo <strong>$campo</strong>: " . $e->getMessage() . "<br>";
        }
    }
    
    // Verificar estrutura final
    echo "<h2>Estrutura Final da Tabela:</h2>";
    $colunasFinais = $db->fetchAll("DESCRIBE instrutores");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunasFinais as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>🎉 Resumo:</h2>";
    echo "<ul>";
    echo "<li>✅ Conexão com banco: OK</li>";
    echo "<li>✅ Campos adicionados: " . count($camposParaAdicionar) . "</li>";
    echo "<li>✅ Estrutura da tabela atualizada</li>";
    echo "</ul>";
    
    echo "<h3>Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>Teste o cadastro de instrutores no sistema</li>";
    echo "<li>Verifique se todos os campos são salvos corretamente</li>";
    echo "<li>Teste a edição de instrutores existentes</li>";
    echo "</ol>";
    
    echo "<p><a href='admin/index.php?page=instrutores' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Testar Cadastro de Instrutores</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se os arquivos de configuração estão corretos.</p>";
}
?>
