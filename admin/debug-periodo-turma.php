<?php
/**
 * Script para debug do Período da Turma
 */

echo "<h2>🔍 Debug - Período da Turma</h2>";
echo "<pre>";

try {
    // Incluir configuração
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/includes/TurmaTeoricaManager.php';
    
    // Conectar ao banco
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 30
        ]
    );
    
    echo "✅ Conectado ao banco!\n\n";
    
    // Criar instância do TurmaTeoricaManager
    $turmaManager = new TurmaTeoricaManager($pdo);
    
    // Obter dados da turma 6
    echo "🔍 Obtendo dados da turma 6...\n";
    $resultadoTurma = $turmaManager->obterTurma(6);
    
    if ($resultadoTurma['sucesso']) {
        $turma = $resultadoTurma['dados'];
        echo "✅ Turma encontrada:\n";
        echo "  ID: {$turma['id']}\n";
        echo "  Nome: {$turma['nome']}\n";
        echo "  Data Início (raw): '{$turma['data_inicio']}'\n";
        echo "  Data Fim (raw): '{$turma['data_fim']}'\n";
        
        // Testar conversão de datas
        echo "\n🔍 Testando conversão de datas:\n";
        
        if ($turma['data_inicio']) {
            $dataInicio = new DateTime($turma['data_inicio']);
            $dataInicioFormatada = $dataInicio->format('d/m/Y');
            echo "  Data início (DateTime): {$dataInicioFormatada}\n";
            
            // Testar com JavaScript Date
            $dataInicioJS = date('d/m/Y', strtotime($turma['data_inicio']));
            echo "  Data início (date): {$dataInicioJS}\n";
        }
        
        if ($turma['data_fim']) {
            $dataFim = new DateTime($turma['data_fim']);
            $dataFimFormatada = $dataFim->format('d/m/Y');
            echo "  Data fim (DateTime): {$dataFimFormatada}\n";
            
            // Testar com JavaScript Date
            $dataFimJS = date('d/m/Y', strtotime($turma['data_fim']));
            echo "  Data fim (date): {$dataFimJS}\n";
        }
        
        // Testar JSON encode
        echo "\n🔍 Testando JSON encode:\n";
        $turmaJson = json_encode($turma, JSON_UNESCAPED_UNICODE);
        echo "  Turma JSON: " . substr($turmaJson, 0, 300) . "...\n";
        
        // Verificar se há problemas com as datas
        echo "\n🔍 Verificando problemas com datas:\n";
        if (empty($turma['data_inicio'])) {
            echo "  ❌ data_inicio está vazia!\n";
        } else {
            echo "  ✅ data_inicio tem valor: {$turma['data_inicio']}\n";
        }
        
        if (empty($turma['data_fim'])) {
            echo "  ❌ data_fim está vazia!\n";
        } else {
            echo "  ✅ data_fim tem valor: {$turma['data_fim']}\n";
        }
        
    } else {
        echo "❌ Erro ao obter turma: {$resultadoTurma['mensagem']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Botões de teste
echo '<br>';
echo '<a href="?page=turmas-teoricas&acao=editar&step=1&turma_id=6" style="background: #F7931E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">✏️ Testar Edição</a>';
echo '<a href="?page=turmas-teoricas&acao=detalhes&turma_id=6" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔍 Ver Detalhes</a>';
?>
