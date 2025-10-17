<?php
/**
 * Script para debug dos campos de edição
 */

echo "<h2>🔍 Debug - Campos de Edição</h2>";
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
        echo "  Sala ID: {$turma['sala_id']}\n";
        echo "  Curso: {$turma['curso_tipo']}\n";
        echo "  Data Início: {$turma['data_inicio']}\n";
        echo "  Data Fim: {$turma['data_fim']}\n";
        echo "  Modalidade: {$turma['modalidade']}\n";
        echo "  Max Alunos: {$turma['max_alunos']}\n";
        echo "  Observações: {$turma['observacoes']}\n";
        echo "  Status: {$turma['status']}\n";
        echo "  Criado em: {$turma['criado_em']}\n\n";
        
        // Verificar formato das datas
        echo "🔍 Verificando formato das datas:\n";
        echo "  Data início (raw): '{$turma['data_inicio']}'\n";
        echo "  Data fim (raw): '{$turma['data_fim']}'\n";
        
        if ($turma['data_inicio']) {
            $dataInicio = date('d/m/Y', strtotime($turma['data_inicio']));
            echo "  Data início (formatada): '$dataInicio'\n";
        }
        
        if ($turma['data_fim']) {
            $dataFim = date('d/m/Y', strtotime($turma['data_fim']));
            echo "  Data fim (formatada): '$dataFim'\n";
        }
        
    } else {
        echo "❌ Erro ao obter turma: {$resultadoTurma['mensagem']}\n";
    }
    
    // Obter disciplinas da turma
    echo "\n🔍 Obtendo disciplinas da turma 6...\n";
    $disciplinas = $turmaManager->obterDisciplinasSelecionadas(6);
    
    if (empty($disciplinas)) {
        echo "❌ Nenhuma disciplina encontrada!\n";
    } else {
        echo "✅ Encontradas " . count($disciplinas) . " disciplinas:\n";
        foreach ($disciplinas as $disc) {
            echo "  - ID: {$disc['disciplina_id']}, Nome: {$disc['nome_disciplina']}, Horas: {$disc['carga_horaria_padrao']}h\n";
        }
    }
    
    // Testar JSON encode
    echo "\n🔍 Testando JSON encode...\n";
    $turmaJson = json_encode($turma);
    echo "  Turma JSON: " . substr($turmaJson, 0, 200) . "...\n";
    
    $disciplinasJson = json_encode($disciplinas);
    echo "  Disciplinas JSON: " . substr($disciplinasJson, 0, 200) . "...\n";
    
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
