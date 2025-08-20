<?php
/**
 * Script para inserir dados básicos de teste
 * Execute este script antes de rodar os testes automatizados
 */

require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>🔧 INSERINDO DADOS DE TESTE</h1>";
    echo "<p>Este script insere dados básicos para os testes funcionarem corretamente.</p>";
    echo "<hr>";
    
    // 1. Inserir usuário admin se não existir
    $usuarioExiste = $db->fetch("SELECT id FROM usuarios WHERE email = ?", ['admin@cfc.com']);
    
    if (!$usuarioExiste) {
        $senhaHash = password_hash('password', PASSWORD_DEFAULT);
        $db->query("
            INSERT INTO usuarios (nome, email, senha, tipo, ativo, criado_em) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ", ['Administrador', 'admin@cfc.com', $senhaHash, 'admin', 1]);
        echo "✅ Usuário admin criado<br>";
    } else {
        echo "✅ Usuário admin já existe<br>";
    }
    
    // 2. Inserir CFC de teste se não existir
    $cfcExiste = $db->fetch("SELECT id FROM cfcs WHERE cnpj = ?", ['12.345.678/0001-90']);
    
    if (!$cfcExiste) {
        $db->query("
            INSERT INTO cfcs (nome, cnpj, endereco, telefone, email, ativo, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            'CFC Bom Conselho',
            '12.345.678/0001-90',
            'Rua Principal, 123',
            '(11) 99999-9999',
            'contato@cfcbomconselho.com',
            1
        ]);
        $cfcId = $db->getConnection()->lastInsertId();
        echo "✅ CFC de teste criado (ID: $cfcId)<br>";
    } else {
        $cfcId = $cfcExiste['id'];
        echo "✅ CFC de teste já existe (ID: $cfcId)<br>";
    }
    
    // 3. Inserir aluno de teste se não existir
    $alunoExiste = $db->fetch("SELECT id FROM alunos WHERE cpf = ?", ['123.456.789-00']);
    
    if (!$alunoExiste) {
        $db->query("
            INSERT INTO alunos (nome, cpf, email, telefone, categoria_cnh, endereco, cfc_id, status, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            'Maria Santos',
            '123.456.789-00',
            'maria@teste.com',
            '(11) 88888-8888',
            'B',
            'Rua do Aluno, 456',
            $cfcId,
            'ativo'
        ]);
        $alunoId = $db->getConnection()->lastInsertId();
        echo "✅ Aluno de teste criado (ID: $alunoId)<br>";
    } else {
        $alunoId = $alunoExiste['id'];
        echo "✅ Aluno de teste já existe (ID: $alunoId)<br>";
    }
    
    // 4. Inserir instrutor de teste se não existir
    $instrutorExiste = $db->fetch("SELECT id FROM instrutores WHERE credencial = ?", ['CRED-12345']);
    
    if (!$instrutorExiste) {
        // Primeiro criar usuário para o instrutor
        $senhaInstrutor = password_hash('instrutor123', PASSWORD_DEFAULT);
        $db->query("
            INSERT INTO usuarios (nome, email, senha, tipo, ativo, criado_em) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ", ['Carlos Instrutor', 'carlos@teste.com', $senhaInstrutor, 'instrutor', 1]);
        
        $usuarioInstrutorId = $db->getConnection()->lastInsertId();
        
        $db->query("
            INSERT INTO instrutores (usuario_id, cfc_id, credencial, categoria_habilitacao, ativo, criado_em) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ", [
            $usuarioInstrutorId,
            $cfcId,
            'CRED-12345',
            'A,B,C',
            1
        ]);
        $instrutorId = $db->getConnection()->lastInsertId();
        echo "✅ Instrutor de teste criado (ID: $instrutorId)<br>";
    } else {
        $instrutorId = $instrutorExiste['id'];
        echo "✅ Instrutor de teste já existe (ID: $instrutorId)<br>";
    }
    
    // 5. Inserir veículo de teste se não existir
    $veiculoExiste = $db->fetch("SELECT id FROM veiculos WHERE placa = ?", ['ABC-1234']);
    
    if (!$veiculoExiste) {
        $db->query("
            INSERT INTO veiculos (marca, modelo, ano, placa, categoria_cnh, cfc_id, ativo, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            'Fiat',
            'Palio',
            '2020',
            'ABC-1234',
            'B',
            $cfcId,
            1
        ]);
        $veiculoId = $db->getConnection()->lastInsertId();
        echo "✅ Veículo de teste criado (ID: $veiculoId)<br>";
    } else {
        $veiculoId = $veiculoExiste['id'];
        echo "✅ Veículo de teste já existe (ID: $veiculoId)<br>";
    }
    
    echo "<hr>";
    echo "<h2>✅ DADOS DE TESTE INSERIDOS COM SUCESSO!</h2>";
    echo "<p>Agora você pode executar os testes automatizados.</p>";
    echo "<p><a href='executar-todos-testes.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Executar Testes</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO AO INSERIR DADOS DE TESTE</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se o banco de dados está configurado corretamente.</p>";
}
?>
