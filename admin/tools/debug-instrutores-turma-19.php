B<?php
/**
 * Script de Debug - Instrutores para Turma 19
 * 
 * Objetivo: Identificar exatamente por que o instrutor não aparece
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isDev = (getenv('ENVIRONMENT') === 'development' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
if (!$isDev && (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'admin')) {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug Instrutores Turma 19</title>
    <style>
        body { font-family: monospace; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 12px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #2196F3; color: white; }
        .ok { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
        .info-box { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug: Instrutores para Turma 19</h1>
        
        <?php
        try {
            $db = Database::getInstance();
            
            // 1. Dados da turma 19
            echo "<h2>1. Dados da Turma 19</h2>";
            $turma = $db->fetch("SELECT id, nome, cfc_id FROM turmas_teoricas WHERE id = 19");
            
            if (!$turma) {
                die("❌ Turma 19 não encontrada!");
            }
            
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>ID</td><td>{$turma['id']}</td></tr>";
            echo "<tr><td>Nome</td><td>{$turma['nome']}</td></tr>";
            echo "<tr><td>CFC ID</td><td><strong>{$turma['cfc_id']}</strong></td></tr>";
            echo "</table>";
            
            $cfcId = (int)$turma['cfc_id'];
            
            // 2. Verificar estrutura da tabela instrutores
            echo "<h2>2. Estrutura da Tabela instrutores</h2>";
            $colunas = $db->fetchAll("SHOW COLUMNS FROM instrutores");
            echo "<table>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Default</th></tr>";
            foreach ($colunas as $col) {
                if (in_array($col['Field'], ['id', 'nome', 'cfc_id', 'ativo', 'usuario_id'])) {
                    echo "<tr>";
                    echo "<td><strong>{$col['Field']}</strong></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>{$col['Default']}</td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
            
            // 3. Testar query exata usada no código
            echo "<h2>3. Query Exata Usada no Código</h2>";
            echo "<pre>";
            echo "SELECT i.id, \n";
            echo "       COALESCE(u.nome, i.nome, 'Instrutor sem nome') as nome,\n";
            echo "       i.categoria_habilitacao \n";
            echo "FROM instrutores i \n";
            echo "LEFT JOIN usuarios u ON i.usuario_id = u.id \n";
            echo "WHERE i.ativo = 1 AND i.cfc_id = {$cfcId}\n";
            echo "ORDER BY COALESCE(u.nome, i.nome, '') ASC";
            echo "</pre>";
            
            $instrutores = $db->fetchAll("
                SELECT i.id, 
                       COALESCE(u.nome, i.nome, 'Instrutor sem nome') as nome,
                       i.categoria_habilitacao,
                       i.ativo,
                       i.cfc_id,
                       u.nome as usuario_nome,
                       i.nome as instrutor_nome
                FROM instrutores i 
                LEFT JOIN usuarios u ON i.usuario_id = u.id 
                WHERE i.ativo = 1 AND i.cfc_id = ?
                ORDER BY COALESCE(u.nome, i.nome, '') ASC
            ", [$cfcId]);
            
            echo "<p><strong>Total de instrutores encontrados: " . count($instrutores) . "</strong></p>";
            
            if (empty($instrutores)) {
                echo "<div class='info-box'>";
                echo "<p class='fail'>❌ Nenhum instrutor encontrado com a query exata!</p>";
                echo "</div>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>Nome (COALESCE)</th><th>Nome Usuário</th><th>Nome Instrutor</th><th>CFC ID</th><th>Ativo</th><th>Categoria</th></tr>";
                foreach ($instrutores as $inst) {
                    $destaque = ($inst['id'] == 47) ? 'style="background-color:#fff3cd;"' : '';
                    echo "<tr {$destaque}>";
                    echo "<td>{$inst['id']}</td>";
                    echo "<td><strong>{$inst['nome']}</strong></td>";
                    echo "<td>" . ($inst['usuario_nome'] ?? 'NULL') . "</td>";
                    echo "<td>" . ($inst['instrutor_nome'] ?? 'NULL') . "</td>";
                    echo "<td>{$inst['cfc_id']}</td>";
                    echo "<td>" . ($inst['ativo'] ? 'Sim' : 'Não') . "</td>";
                    echo "<td>{$inst['categoria_habilitacao']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // 4. Verificar instrutor 47 especificamente
            echo "<h2>4. Instrutor ID 47 (Detalhado)</h2>";
            $instrutor47 = $db->fetch("
                SELECT i.*, 
                       u.id as usuario_id_tabela,
                       u.nome as usuario_nome,
                       u.ativo as usuario_ativo
                FROM instrutores i
                LEFT JOIN usuarios u ON i.usuario_id = u.id
                WHERE i.id = 47
            ");
            
            if ($instrutor47) {
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th><th>Tipo PHP</th></tr>";
                foreach ($instrutor47 as $campo => $valor) {
                    $tipo = gettype($valor);
                    $destaque = in_array($campo, ['ativo', 'cfc_id', 'usuario_id']) ? 'style="background-color:#fff3cd;"' : '';
                    echo "<tr {$destaque}>";
                    echo "<td><strong>{$campo}</strong></td>";
                    echo "<td>" . ($valor === null ? '<em>NULL</em>' : htmlspecialchars((string)$valor)) . "</td>";
                    echo "<td><small>{$tipo}</small></td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Testar condições
                echo "<h3>Teste de Condições:</h3>";
                echo "<ul>";
                $ativo = $instrutor47['ativo'];
                $cfcIdInstrutor = (int)$instrutor47['cfc_id'];
                
                echo "<li>i.ativo = 1: " . ($ativo == 1 ? '<span class="ok">✅ TRUE</span>' : '<span class="fail">❌ FALSE (valor: ' . var_export($ativo, true) . ')</span>') . "</li>";
                echo "<li>i.ativo === 1: " . ($ativo === 1 ? '<span class="ok">✅ TRUE</span>' : '<span class="fail">❌ FALSE</span>') . "</li>";
                echo "<li>i.cfc_id = {$cfcId}: " . ($cfcIdInstrutor == $cfcId ? '<span class="ok">✅ TRUE</span>' : '<span class="fail">❌ FALSE</span>') . "</li>";
                echo "<li>i.cfc_id === {$cfcId}: " . ($cfcIdInstrutor === $cfcId ? '<span class="ok">✅ TRUE</span>' : '<span class="fail">❌ FALSE</span>') . "</li>";
                echo "</ul>";
                
                // Testar query com diferentes condições
                echo "<h3>Teste de Queries Alternativas:</h3>";
                
                // Query 1: ativo = 1 (exata)
                $q1 = $db->fetchAll("SELECT i.id FROM instrutores i WHERE i.id = 47 AND i.ativo = 1");
                echo "<p>Query: <code>i.ativo = 1</code> → " . (count($q1) > 0 ? '<span class="ok">✅ Encontrado</span>' : '<span class="fail">❌ Não encontrado</span>') . "</p>";
                
                // Query 2: ativo = TRUE
                $q2 = $db->fetchAll("SELECT i.id FROM instrutores i WHERE i.id = 47 AND i.ativo = TRUE");
                echo "<p>Query: <code>i.ativo = TRUE</code> → " . (count($q2) > 0 ? '<span class="ok">✅ Encontrado</span>' : '<span class="fail">❌ Não encontrado</span>') . "</p>";
                
                // Query 3: ativo IS NOT NULL AND ativo != 0
                $q3 = $db->fetchAll("SELECT i.id FROM instrutores i WHERE i.id = 47 AND i.ativo IS NOT NULL AND i.ativo != 0");
                echo "<p>Query: <code>i.ativo IS NOT NULL AND i.ativo != 0</code> → " . (count($q3) > 0 ? '<span class="ok">✅ Encontrado</span>' : '<span class="fail">❌ Não encontrado</span>') . "</p>";
                
                // Query 4: Sem filtro de ativo
                $q4 = $db->fetchAll("SELECT i.id FROM instrutores i WHERE i.id = 47 AND i.cfc_id = ?", [$cfcId]);
                echo "<p>Query: <code>Sem filtro de ativo, apenas cfc_id = {$cfcId}</code> → " . (count($q4) > 0 ? '<span class="ok">✅ Encontrado</span>' : '<span class="fail">❌ Não encontrado</span>') . "</p>";
                
            } else {
                echo "<p class='fail'>❌ Instrutor ID 47 não encontrado!</p>";
            }
            
            // 5. Listar TODOS os instrutores do CFC 36 (sem filtro de ativo)
            echo "<h2>5. Todos os Instrutores do CFC 36 (sem filtro de ativo)</h2>";
            $todosInstrutoresCfc36 = $db->fetchAll("
                SELECT i.id, 
                       COALESCE(u.nome, i.nome) as nome,
                       i.ativo,
                       i.cfc_id,
                       CASE 
                           WHEN i.ativo = 1 THEN 'Sim'
                           WHEN i.ativo = TRUE THEN 'Sim (TRUE)'
                           WHEN i.ativo = 0 THEN 'Não'
                           WHEN i.ativo = FALSE THEN 'Não (FALSE)'
                           WHEN i.ativo IS NULL THEN 'NULL'
                           ELSE 'Outro: ' . i.ativo
                       END as ativo_descricao
                FROM instrutores i
                LEFT JOIN usuarios u ON i.usuario_id = u.id
                WHERE i.cfc_id = 36
                ORDER BY i.id
            ");
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Nome</th><th>Ativo (valor bruto)</th><th>Ativo (descrição)</th></tr>";
            foreach ($todosInstrutoresCfc36 as $inst) {
                $destaque = ($inst['id'] == 47) ? 'style="background-color:#fff3cd;"' : '';
                echo "<tr {$destaque}>";
                echo "<td>{$inst['id']}</td>";
                echo "<td><strong>{$inst['nome']}</strong></td>";
                echo "<td>" . var_export($inst['ativo'], true) . "</td>";
                echo "<td>{$inst['ativo_descricao']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<div style='background:#f8d7da;padding:15px;border-left:4px solid #dc3545;margin:20px 0;'>";
            echo "<p class='fail'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>





