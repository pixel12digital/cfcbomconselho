<?php
require_once 'includes/config.php';

echo "=== TESTE CRIAR ANA SOUZA (PDO) ===\n\n";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Conexão PDO estabelecida\n";
    
    // Verificar se Ana já existe
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE cpf = '12345678910'");
    $stmt->execute();
    $ana = $stmt->fetch();
    
    if ($ana) {
        echo "❌ Ana Souza já existe (ID: {$ana['id']})\n";
        // Remover registros relacionados
        $pdo->prepare("DELETE FROM exames WHERE aluno_id = ?")->execute([$ana['id']]);
        $pdo->prepare("DELETE FROM matriculas WHERE aluno_id = ?")->execute([$ana['id']]);
        $pdo->prepare("DELETE FROM faturas WHERE aluno_id = ?")->execute([$ana['id']]);
        $pdo->prepare("DELETE FROM alunos WHERE id = ?")->execute([$ana['id']]);
        echo "✅ Registros removidos\n";
    }
    
    // Criar Ana Souza
    $sql = "INSERT INTO alunos (
        nome, cpf, rg, data_nascimento, telefone, email, 
        endereco, cidade, estado, cep, categoria_cnh, 
        status, observacoes, cfc_id, lgpd_consentido, 
        lgpd_consentido_em, criado_em, atualizado_em
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', 'Aluna de teste E2E', ?, 1, NOW(), NOW(), NOW())";
    
    $params = [
        'Ana Souza',
        '12345678910',
        '1234567',
        '1995-03-15',
        '11999887766',
        'ana.souza@email.com',
        'Rua das Flores, 123',
        'São Paulo',
        'SP',
        '01234567',
        'AB',
        36
    ];
    
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute($params);
    
    if ($resultado) {
        $alunoId = $pdo->lastInsertId();
        echo "✅ Ana Souza criada com sucesso (ID: $alunoId)\n";
        
        // Criar fatura
        $sqlFatura = "INSERT INTO faturas (
            aluno_id, numero, descricao, valor, valor_liquido,
            status, vencimento, criado_por, criado_em, atualizado_em
        ) VALUES (?, ?, 'Taxa de Matrícula/Inscrição', 500.00, 500.00, 'aberta', DATE_ADD(NOW(), INTERVAL 30 DAY), 18, NOW(), NOW())";
        
        $numeroFatura = 'MAT-' . date('Y') . '-' . str_pad($alunoId, 4, '0', STR_PAD_LEFT);
        
        $stmtFatura = $pdo->prepare($sqlFatura);
        $resultadoFatura = $stmtFatura->execute([$alunoId, $numeroFatura]);
        
        if ($resultadoFatura) {
            $faturaId = $pdo->lastInsertId();
            echo "✅ Fatura criada (ID: $faturaId) - R$ 500,00 em aberto\n";
        } else {
            echo "❌ Erro ao criar fatura\n";
        }
        
        echo "\n=== RESULTADO ETAPA 1 ===\n";
        echo "✅ PASS - Aluna criada, fatura gerada\n";
        echo "📋 IDs gerados:\n";
        echo "   - Aluno ID: $alunoId\n";
        echo "   - Fatura ID: $faturaId\n";
        echo "   - CFC ID: 36\n";
        
    } else {
        echo "❌ Erro ao criar Ana Souza\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro PDO: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
}

echo "\n=== FIM ===\n";
?>
