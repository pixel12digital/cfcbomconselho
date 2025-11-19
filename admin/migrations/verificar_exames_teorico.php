<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

$db = Database::getInstance();

echo "🔍 Verificando exames teóricos...\n\n";

// Verificar todos os exames teóricos
$exames = $db->fetchAll("
    SELECT id, aluno_id, tipo, data_agendada, 
           CURDATE() as hoje,
           DATE_SUB(CURDATE(), INTERVAL 30 DAY) as inicio_janela,
           DATE_ADD(CURDATE(), INTERVAL 30 DAY) as fim_janela
    FROM exames 
    WHERE tipo = 'teorico'
    ORDER BY id DESC
");

echo "📊 Total de exames teóricos encontrados: " . count($exames) . "\n\n";

if (count($exames) > 0) {
    echo "ID | Tipo | Data Agendada | Hoje | Início Janela | Fim Janela | Dentro?\n";
    echo str_repeat('-', 80) . "\n";
    
    foreach ($exames as $exame) {
        $dataAgendada = $exame['data_agendada'];
        $inicioJanela = $exame['inicio_janela'];
        $fimJanela = $exame['fim_janela'];
        
        $dentro = ($dataAgendada >= $inicioJanela && $dataAgendada <= $fimJanela) ? 'SIM ✅' : 'NÃO ❌';
        
        echo sprintf("%-3d | %-6s | %-13s | %-5s | %-13s | %-11s | %s\n", 
            $exame['id'], 
            $exame['tipo'], 
            $dataAgendada,
            $exame['hoje'],
            $inicioJanela,
            $fimJanela,
            $dentro
        );
    }
    
    echo "\n";
    
    // Testar a query usada na página
    $examesNaJanela = $db->fetchAll("
        SELECT e.*, a.nome as aluno_nome, a.cpf as aluno_cpf,
               c.nome as cfc_nome
        FROM exames e
        JOIN alunos a ON e.aluno_id = a.id
        JOIN cfcs c ON a.cfc_id = c.id
        WHERE e.tipo = 'teorico'
          AND e.data_agendada BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                  AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY e.data_agendada DESC, e.hora_agendada DESC, e.id DESC
    ");
    
    echo "📋 Exames teóricos dentro da janela de 60 dias (query da página): " . count($examesNaJanela) . "\n";
    
    if (count($examesNaJanela) > 0) {
        echo "✅ Os exames DEVEM aparecer na listagem!\n";
        foreach ($examesNaJanela as $exame) {
            echo "  - ID {$exame['id']}: {$exame['aluno_nome']} - Data: {$exame['data_agendada']}\n";
        }
    } else {
        echo "⚠️ Nenhum exame dentro da janela. Verifique as datas.\n";
    }
} else {
    echo "⚠️ Nenhum exame teórico encontrado no banco de dados.\n";
}

?>

