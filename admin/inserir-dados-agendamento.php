<?php
/**
 * Script para inserir dados de teste específicos para o sistema de agendamento
 * Cria usuários, alunos, instrutores e veículos necessários para os testes
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Inserir Dados para Agendamento - Sistema CFC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🧪 Inserir Dados para Sistema de Agendamento</h1>";

try {
    // Verificar se já existem dados
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo IN ('admin', 'instrutor')");
    $usuariosExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM alunos");
    $alunosExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcsExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculosExistentes = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<div class='card'>
        <h3>📊 Status Atual do Banco</h3>
        <p><strong>Usuários (Admin/Instrutor):</strong> <span class='info'>{$usuariosExistentes}</span></p>
        <p><strong>Alunos:</strong> <span class='info'>{$alunosExistentes}</span></p>
        <p><strong>CFCs:</strong> <span class='info'>{$cfcsExistentes}</span></p>
        <p><strong>Veículos:</strong> <span class='info'>{$veiculosExistentes}</span></p>
    </div>";
    
    // Inserir CFC se não existir
    if ($cfcsExistentes == 0) {
        echo "<div class='card'>";
        echo "<h4>🏢 Inserindo CFC...</h4>";
        
        $sql = "INSERT INTO cfcs (nome, cnpj, endereco, telefone, email, responsavel_id, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $cfcData = [
            'CFC Bom Conselho',
            '12.345.678/0001-90',
            'Rua das Flores, 123 - Centro - Bom Conselho/PE',
            '(87) 3771-1234',
            'contato@cfcbomconselho.com',
            1, // Assumindo que o usuário admin tem ID 1
            1
        ];
        
        if ($stmt->execute($cfcData)) {
            echo "<p class='success'>✅ CFC inserido com sucesso!</p>";
            $cfcId = $db->lastInsertId();
        } else {
            echo "<p class='error'>❌ Erro ao inserir CFC</p>";
            $cfcId = 1; // Usar ID padrão
        }
        echo "</div>";
    } else {
        // Pegar o primeiro CFC existente
        $result = $db->query("SELECT id FROM cfcs LIMIT 1");
        $cfcId = $result->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    // Inserir usuário admin se não existir
    if ($usuariosExistentes == 0) {
        echo "<div class='card'>";
        echo "<h4>👤 Inserindo Usuário Administrador...</h4>";
        
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, telefone, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $adminData = [
            'Administrador CFC',
            'admin@cfc.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
            '000.000.000-00',
            '(87) 99999-9999',
            1
        ];
        
        if ($stmt->execute($adminData)) {
            echo "<p class='success'>✅ Usuário administrador inserido com sucesso!</p>";
            $adminId = $db->lastInsertId();
        } else {
            echo "<p class='error'>❌ Erro ao inserir usuário administrador</p>";
            $adminId = 1;
        }
        echo "</div>";
    } else {
        // Pegar o primeiro usuário admin existente
        $result = $db->query("SELECT id FROM usuarios WHERE tipo = 'admin' LIMIT 1");
        $adminId = $result->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    // Inserir usuário instrutor se não existir
    if ($usuariosExistentes < 2) {
        echo "<div class='card'>";
        echo "<h4>👨‍🏫 Inserindo Usuário Instrutor...</h4>";
        
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, telefone, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $instrutorData = [
            'João Silva',
            'joao.silva@cfc.com',
            password_hash('instrutor123', PASSWORD_DEFAULT),
            'instrutor',
            '111.111.111-11',
            '(87) 88888-8888',
            1
        ];
        
        if ($stmt->execute($instrutorData)) {
            echo "<p class='success'>✅ Usuário instrutor inserido com sucesso!</p>";
            $instrutorUserId = $db->lastInsertId();
        } else {
            echo "<p class='error'>❌ Erro ao inserir usuário instrutor</p>";
            $instrutorUserId = 2;
        }
        echo "</div>";
    } else {
        // Pegar o primeiro usuário instrutor existente
        $result = $db->query("SELECT id FROM usuarios WHERE tipo = 'instrutor' LIMIT 1");
        $instrutorUserId = $result->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    // Inserir instrutor se não existir
    $result = $db->query("SELECT COUNT(*) as total FROM instrutores WHERE usuario_id = $instrutorUserId");
    $instrutorExiste = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($instrutorExiste == 0) {
        echo "<div class='card'>";
        echo "<h4>🎓 Inserindo Instrutor...</h4>";
        
        $sql = "INSERT INTO instrutores (usuario_id, cfc_id, credencial, categoria_habilitacao, ativo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $instrutorData = [
            $instrutorUserId,
            $cfcId,
            'INSTR001',
            'A, B, C, D, E',
            1
        ];
        
        if ($stmt->execute($instrutorData)) {
            echo "<p class='success'>✅ Instrutor inserido com sucesso!</p>";
            $instrutorId = $db->lastInsertId();
        } else {
            echo "<p class='error'>❌ Erro ao inserir instrutor</p>";
            $instrutorId = 1;
        }
        echo "</div>";
    } else {
        // Pegar o instrutor existente
        $result = $db->query("SELECT id FROM instrutores WHERE usuario_id = $instrutorUserId LIMIT 1");
        $instrutorId = $result->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    // Inserir alunos se não existirem
    if ($alunosExistentes == 0) {
        echo "<div class='card'>";
        echo "<h4>👨‍🎓 Inserindo Alunos...</h4>";
        
        $alunos = [
            [
                'Maria Santos',
                '222.222.222-22',
                '12.345.678-9',
                '1995-05-15',
                'Rua das Palmeiras, 456 - Centro',
                '(87) 77777-7777',
                'maria.santos@email.com',
                'B'
            ],
            [
                'Pedro Oliveira',
                '333.333.333-33',
                '23.456.789-0',
                '1992-08-22',
                'Av. Principal, 789 - Bairro Novo',
                '(87) 66666-6666',
                'pedro.oliveira@email.com',
                'A'
            ],
            [
                'Ana Costa',
                '444.444.444-44',
                '34.567.890-1',
                '1998-12-10',
                'Rua do Comércio, 321 - Centro',
                '(87) 55555-5555',
                'ana.costa@email.com',
                'B'
            ]
        ];
        
        $sql = "INSERT INTO alunos (nome, cpf, rg, data_nascimento, endereco, telefone, email, cfc_id, categoria_cnh, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $alunosInseridos = 0;
        foreach ($alunos as $aluno) {
            $alunoData = array_merge($aluno, [$cfcId, 'ativo']);
            
            if ($stmt->execute($alunoData)) {
                $alunosInseridos++;
            }
        }
        
        if ($alunosInseridos > 0) {
            echo "<p class='success'>✅ {$alunosInseridos} alunos inseridos com sucesso!</p>";
        } else {
            echo "<p class='error'>❌ Erro ao inserir alunos</p>";
        }
        echo "</div>";
    }
    
    // Inserir veículos se não existirem
    if ($veiculosExistentes == 0) {
        echo "<div class='card'>";
        echo "<h4>🚗 Inserindo Veículos...</h4>";
        
        $veiculos = [
            [
                'ABC-1234',
                'Gol',
                'Volkswagen',
                2020,
                'B'
            ],
            [
                'DEF-5678',
                'Onix',
                'Chevrolet',
                2021,
                'B'
            ],
            [
                'GHI-9012',
                'CG 150',
                'Honda',
                2019,
                'A'
            ]
        ];
        
        $sql = "INSERT INTO veiculos (cfc_id, placa, modelo, marca, ano, categoria_cnh, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $veiculosInseridos = 0;
        foreach ($veiculos as $veiculo) {
            $veiculoData = array_merge([$cfcId], $veiculo, [1]);
            
            if ($stmt->execute($veiculoData)) {
                $veiculosInseridos++;
            }
        }
        
        if ($veiculosInseridos > 0) {
            echo "<p class='success'>✅ {$veiculosInseridos} veículos inseridos com sucesso!</p>";
        } else {
            echo "<p class='error'>❌ Erro ao inserir veículos</p>";
        }
        echo "</div>";
    }
    
    // Verificar dados finais
    echo "<div class='card'>";
    echo "<h4>📊 Verificação Final</h4>";
    
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo IN ('admin', 'instrutor')");
    $usuariosFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM alunos");
    $alunosFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcsFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculosFinais = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Usuários (Admin/Instrutor):</strong> <span class='success'>{$usuariosFinais}</span></p>";
    echo "<p><strong>Alunos:</strong> <span class='success'>{$alunosFinais}</span></p>";
    echo "<p><strong>CFCs:</strong> <span class='success'>{$cfcsFinais}</span></p>";
    echo "<p><strong>Veículos:</strong> <span class='success'>{$veiculosFinais}</span></p>";
    
    if ($usuariosFinais > 0 && $alunosFinais > 0 && $cfcsFinais > 0 && $veiculosFinais > 0) {
        echo "<p class='success'>🎉 Sistema pronto para testes de agendamento!</p>";
    } else {
        echo "<p class='error'>⚠️ Ainda há dados faltando</p>";
    }
    echo "</div>";
    
    // Links para testes
    echo "<div class='card'>";
    echo "<h4>🔧 Próximos Passos</h4>";
    echo "<p>Com os dados inseridos, você pode:</p>";
    echo "<a href='teste-agendamento-completo.php' class='btn'>🧪 Teste Completo do Sistema</a>";
    echo "<a href='index.php?page=agendamento' class='btn'>📅 Sistema de Agendamento</a>";
    echo "<a href='test-api-agendamento.php' class='btn'>🔌 Teste das APIs</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='card'>";
    echo "<h4 class='error'>❌ Erro no Sistema</h4>";
    echo "<p class='error'>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se o banco de dados está funcionando e se as tabelas foram criadas corretamente.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
