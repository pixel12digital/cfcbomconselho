<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "Verificando tabelas existentes...\n";
$tables = $db->fetchAll('SHOW TABLES');
foreach($tables as $table) {
    echo "- " . $table[array_keys($table)[0]] . "\n";
}

echo "\nVerificando se turma_diario existe...\n";
$exists = $db->fetch("SHOW TABLES LIKE 'turma_diario'");

if (!$exists) {
    echo "Criando tabela turma_diario...\n";
    
    $sql = "
        CREATE TABLE turma_diario (
            id INT PRIMARY KEY AUTO_INCREMENT,
            turma_id INT NOT NULL,
            turma_aula_id INT NOT NULL,
            conteudo_ministrado TEXT NOT NULL,
            anexos JSON NULL,
            observacoes TEXT NULL,
            registrado_por INT NOT NULL,
            atualizado_por INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (turma_id) REFERENCES turmas(id),
            FOREIGN KEY (turma_aula_id) REFERENCES turma_aulas(id),
            FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
            FOREIGN KEY (atualizado_por) REFERENCES usuarios(id),
            
            UNIQUE KEY unique_diario_aula (turma_id, turma_aula_id)
        )
    ";
    
    try {
        $db->query($sql);
        echo "✅ Tabela turma_diario criada com sucesso!\n";
    } catch (Exception $e) {
        echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ Tabela turma_diario já existe!\n";
}
?>
