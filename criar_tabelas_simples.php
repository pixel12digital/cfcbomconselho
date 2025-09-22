<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🚀 CRIANDO TABELAS ESSENCIAIS - ETAPA 1.1\n";
echo "========================================\n";

$db = Database::getInstance();

// 1. Criar tabela turmas
echo "1. Criando tabela turmas...\n";
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS turmas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            instrutor_id INT NOT NULL,
            tipo_aula ENUM('teorica', 'pratica', 'mista') NOT NULL,
            categoria_cnh VARCHAR(10),
            data_inicio DATE,
            data_fim DATE,
            status ENUM('agendado', 'ativo', 'inativo', 'concluido') DEFAULT 'agendado',
            total_alunos INT DEFAULT 0,
            capacidade_maxima INT DEFAULT 30,
            frequencia_minima DECIMAL(5,2) DEFAULT 75.00,
            sala_local VARCHAR(100) NULL,
            link_online VARCHAR(255) NULL,
            observacoes TEXT,
            cfc_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (instrutor_id) REFERENCES instrutores(id),
            FOREIGN KEY (cfc_id) REFERENCES cfcs(id),
            INDEX idx_status (status),
            INDEX idx_instrutor (instrutor_id),
            INDEX idx_periodo (data_inicio, data_fim),
            INDEX idx_tipo (tipo_aula),
            INDEX idx_cfc_status (cfc_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela turmas criada\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar turmas: " . $e->getMessage() . "\n";
}

// 2. Criar tabela turma_aulas
echo "2. Criando tabela turma_aulas...\n";
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS turma_aulas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            turma_id INT NOT NULL,
            ordem INT NOT NULL,
            nome_aula VARCHAR(100) NOT NULL,
            duracao_minutos INT DEFAULT 50,
            data_aula DATE,
            tipo_conteudo VARCHAR(50),
            status ENUM('pendente', 'agendada', 'concluida', 'cancelada') DEFAULT 'pendente',
            aula_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
            FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
            INDEX idx_turma_ordem (turma_id, ordem),
            INDEX idx_data (data_aula),
            INDEX idx_status (status),
            UNIQUE KEY unique_turma_ordem (turma_id, ordem)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela turma_aulas criada\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar turma_aulas: " . $e->getMessage() . "\n";
}

// 3. Criar tabela turma_alunos
echo "3. Criando tabela turma_alunos...\n";
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS turma_alunos (
            id INT PRIMARY KEY AUTO_INCREMENT,
            turma_id INT NOT NULL,
            aluno_id INT NOT NULL,
            status ENUM('matriculado', 'ativo', 'concluido', 'desistente') DEFAULT 'matriculado',
            data_matricula DATE DEFAULT (CURRENT_DATE),
            data_conclusao DATE NULL,
            observacoes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
            FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_turma_aluno (turma_id, aluno_id),
            INDEX idx_status (status),
            INDEX idx_aluno_status (aluno_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela turma_alunos criada\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar turma_alunos: " . $e->getMessage() . "\n";
}

// 4. Criar tabela turma_presencas
echo "4. Criando tabela turma_presencas...\n";
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS turma_presencas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            turma_id INT NOT NULL,
            turma_aula_id INT NOT NULL,
            aluno_id INT NOT NULL,
            presente BOOLEAN DEFAULT FALSE,
            observacao TEXT NULL,
            registrado_por INT NOT NULL,
            registrado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
            FOREIGN KEY (turma_aula_id) REFERENCES turma_aulas(id) ON DELETE CASCADE,
            FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
            FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
            
            UNIQUE KEY unique_presenca (turma_id, turma_aula_id, aluno_id),
            INDEX idx_turma_aula (turma_id, turma_aula_id),
            INDEX idx_aluno_frequencia (aluno_id, presente),
            INDEX idx_data_registro (registrado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela turma_presencas criada\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar turma_presencas: " . $e->getMessage() . "\n";
}

// 5. Criar tabela turma_diario
echo "5. Criando tabela turma_diario...\n";
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS turma_diario (
            id INT PRIMARY KEY AUTO_INCREMENT,
            turma_aula_id INT NOT NULL,
            conteudo_ministrado TEXT NOT NULL,
            anexos JSON NULL,
            observacoes TEXT NULL,
            criado_por INT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_por INT NULL,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (turma_aula_id) REFERENCES turma_aulas(id) ON DELETE CASCADE,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id),
            FOREIGN KEY (atualizado_por) REFERENCES usuarios(id),
            
            UNIQUE KEY unique_diario_aula (turma_aula_id),
            INDEX idx_turma_aula_diario (turma_aula_id),
            INDEX idx_criado_por (criado_por),
            INDEX idx_data_criacao (criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela turma_diario criada\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar turma_diario: " . $e->getMessage() . "\n";
}

// 6. Adicionar campos em aulas_slots
echo "6. Adicionando campos em aulas_slots...\n";
try {
    $db->query("ALTER TABLE aulas_slots ADD COLUMN IF NOT EXISTS turma_id INT NULL");
    $db->query("ALTER TABLE aulas_slots ADD COLUMN IF NOT EXISTS turma_aula_id INT NULL");
    echo "✅ Campos adicionados em aulas_slots\n";
} catch (Exception $e) {
    echo "❌ Erro ao adicionar campos: " . $e->getMessage() . "\n";
}

// 7. Criar índices para aulas_slots
echo "7. Criando índices para aulas_slots...\n";
try {
    $db->query("CREATE INDEX IF NOT EXISTS idx_aulas_slots_turma ON aulas_slots(turma_id)");
    $db->query("CREATE INDEX IF NOT EXISTS idx_aulas_slots_turma_aula ON aulas_slots(turma_aula_id)");
    echo "✅ Índices criados\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar índices: " . $e->getMessage() . "\n";
}

// 8. Adicionar foreign keys para aulas_slots
echo "8. Adicionando foreign keys para aulas_slots...\n";
try {
    $db->query("ALTER TABLE aulas_slots ADD CONSTRAINT fk_aulas_slots_turma FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL");
    $db->query("ALTER TABLE aulas_slots ADD CONSTRAINT fk_aulas_slots_turma_aula FOREIGN KEY (turma_aula_id) REFERENCES turma_aulas(id) ON DELETE SET NULL");
    echo "✅ Foreign keys adicionadas\n";
} catch (Exception $e) {
    echo "❌ Erro ao adicionar foreign keys: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "🎉 ETAPA 1.1 CONCLUÍDA!\n";
echo "========================================\n";

// Verificar tabelas criadas
echo "🔍 VERIFICANDO TABELAS CRIADAS:\n";
$tables = $db->fetchAll('SHOW TABLES');
$expectedTables = ['turmas', 'turma_aulas', 'turma_alunos', 'turma_presencas', 'turma_diario'];

foreach($expectedTables as $expectedTable) {
    $found = false;
    foreach($tables as $table) {
        $tableName = $table[array_keys($table)[0]];
        if ($tableName === $expectedTable) {
            echo "✅ $expectedTable\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "❌ $expectedTable\n";
    }
}

echo "\n🎯 PRÓXIMA ETAPA: 1.2 - API de Presença\n";
?>
