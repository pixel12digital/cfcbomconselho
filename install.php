<?php
// =====================================================
// SCRIPT DE INSTALAÇÃO - SISTEMA CFC
// =====================================================

require_once 'includes/config.php';
require_once 'includes/database.php';

// Verificar se já foi instalado
if (file_exists('installed.lock')) {
    die('Sistema já foi instalado. Remova o arquivo installed.lock para reinstalar.');
}

echo "<h1>Instalação do Sistema CFC</h1>";
echo "<p>Configurando banco de dados...</p>";

try {
    $db = db();
    
    // Criar tabelas se não existirem
    $tables = [
        'usuarios' => "
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                tipo ENUM('admin', 'instrutor', 'secretaria') NOT NULL DEFAULT 'secretaria',
                cpf VARCHAR(14) UNIQUE,
                telefone VARCHAR(20),
                ativo BOOLEAN DEFAULT TRUE,
                ultimo_login DATETIME,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ",
        'cfcs' => "
            CREATE TABLE IF NOT EXISTS cfcs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(200) NOT NULL,
                cnpj VARCHAR(18) UNIQUE NOT NULL,
                razao_social VARCHAR(200),
                endereco TEXT,
                bairro VARCHAR(100),
                cidade VARCHAR(100),
                uf CHAR(2),
                cep VARCHAR(10),
                telefone VARCHAR(20),
                email VARCHAR(100),
                responsavel_id INT,
                ativo BOOLEAN DEFAULT TRUE,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
            )
        ",
        'alunos' => "
            CREATE TABLE IF NOT EXISTS alunos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                cpf VARCHAR(14) UNIQUE NOT NULL,
                rg VARCHAR(20),
                data_nascimento DATE,
                endereco TEXT,
                telefone VARCHAR(20),
                email VARCHAR(100),
                cfc_id INT NOT NULL,
                categoria_cnh ENUM('A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE') NOT NULL,
                status ENUM('ativo', 'inativo', 'concluido') DEFAULT 'ativo',
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
            )
        ",
        'instrutores' => "
            CREATE TABLE IF NOT EXISTS instrutores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                cfc_id INT NOT NULL,
                credencial VARCHAR(50) UNIQUE NOT NULL,
                categoria_habilitacao VARCHAR(100),
                ativo BOOLEAN DEFAULT TRUE,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
                FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
            )
        ",
        'aulas' => "
            CREATE TABLE IF NOT EXISTS aulas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aluno_id INT NOT NULL,
                instrutor_id INT NOT NULL,
                cfc_id INT NOT NULL,
                tipo_aula ENUM('teorica', 'pratica') NOT NULL,
                data_aula DATE NOT NULL,
                hora_inicio TIME NOT NULL,
                hora_fim TIME NOT NULL,
                status ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'agendada',
                observacoes TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (aluno_id) REFERENCES alunos(id),
                FOREIGN KEY (instrutor_id) REFERENCES instrutores(id),
                FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
            )
        ",
        'veiculos' => "
            CREATE TABLE IF NOT EXISTS veiculos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cfc_id INT NOT NULL,
                placa VARCHAR(10) UNIQUE NOT NULL,
                modelo VARCHAR(100),
                marca VARCHAR(100),
                ano INT,
                categoria_cnh VARCHAR(10),
                ativo BOOLEAN DEFAULT TRUE,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
            )
        ",
        'sessoes' => "
            CREATE TABLE IF NOT EXISTS sessoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                expira_em TIMESTAMP NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )
        ",
        'logs' => "
            CREATE TABLE IF NOT EXISTS logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT,
                acao VARCHAR(100) NOT NULL,
                tabela_afetada VARCHAR(50),
                registro_id INT,
                dados_anteriores TEXT,
                dados_novos TEXT,
                ip_address VARCHAR(45),
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )
        ",
        'exames' => "
            CREATE TABLE IF NOT EXISTS exames (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aluno_id INT NOT NULL,
                -- NOTA: A tabela exames agora também é usada para provas teóricas e práticas
                -- tipo: 'medico', 'psicotecnico' (exames pré-requisitos)
                --       'teorico', 'pratico' (provas de direção do DETRAN)
                tipo ENUM('medico', 'psicotecnico', 'teorico', 'pratico') NOT NULL,
                status ENUM('agendado', 'concluido', 'cancelado') DEFAULT 'agendado',
                -- NOTA: resultado inclui valores para exames ('apto', 'inapto', etc.) 
                --       e para provas ('aprovado', 'reprovado')
                resultado ENUM('apto', 'inapto', 'inapto_temporario', 'pendente', 'aprovado', 'reprovado') DEFAULT 'pendente',
                clinica_nome VARCHAR(200),
                protocolo VARCHAR(100),
                data_agendada DATE NOT NULL,
                data_resultado DATE,
                observacoes TEXT,
                anexos TEXT,
                criado_por INT,
                atualizado_por INT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (aluno_id) REFERENCES alunos(id),
                FOREIGN KEY (criado_por) REFERENCES usuarios(id),
                FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
            )
        ",
        // =====================================================
        // TABELAS ADICIONADAS NA FASE 1 (2025-01-27)
        // Para alinhar install.php com a estrutura real usada pelo sistema
        // =====================================================
        
        // Tabela matriculas – adicionada na Fase 1 para alinhar com migrations
        'matriculas' => "
            CREATE TABLE IF NOT EXISTS matriculas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aluno_id INT NOT NULL,
                categoria_cnh ENUM('A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE') NOT NULL,
                tipo_servico VARCHAR(100) NOT NULL,
                status ENUM('ativa', 'concluida', 'trancada', 'cancelada') DEFAULT 'ativa',
                data_inicio DATE NOT NULL,
                data_fim DATE DEFAULT NULL,
                valor_total DECIMAL(10, 2) DEFAULT NULL,
                forma_pagamento VARCHAR(50) DEFAULT NULL,
                observacoes TEXT DEFAULT NULL,
                renach VARCHAR(50) DEFAULT NULL,
                processo_numero VARCHAR(100) DEFAULT NULL,
                processo_numero_detran VARCHAR(100) DEFAULT NULL,
                processo_situacao VARCHAR(100) DEFAULT NULL,
                status_financeiro ENUM('regular', 'inadimplente', 'quitado') DEFAULT 'regular',
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_aluno (aluno_id),
                INDEX idx_status (status),
                INDEX idx_categoria_tipo (categoria_cnh, tipo_servico),
                INDEX idx_status_financeiro (status_financeiro),
                FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
            )
        ",
        // Tabela financeiro_faturas – adicionada na Fase 1 para alinhar com migrations
        'financeiro_faturas' => "
            CREATE TABLE IF NOT EXISTS financeiro_faturas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aluno_id INT NOT NULL,
                matricula_id INT DEFAULT NULL,
                titulo VARCHAR(200) NOT NULL,
                descricao TEXT DEFAULT NULL,
                valor DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                valor_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                data_vencimento DATE NOT NULL,
                vencimento DATE DEFAULT NULL,
                status ENUM('aberta', 'paga', 'vencida', 'parcial', 'cancelada') DEFAULT 'aberta',
                forma_pagamento ENUM('avista', 'boleto', 'pix', 'cartao', 'transferencia', 'dinheiro') DEFAULT 'avista',
                parcelas INT DEFAULT 1,
                observacoes TEXT DEFAULT NULL,
                reteste BOOLEAN DEFAULT FALSE,
                criado_por INT DEFAULT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_aluno (aluno_id),
                INDEX idx_matricula (matricula_id),
                INDEX idx_status (status),
                INDEX idx_vencimento (data_vencimento),
                INDEX idx_status_vencimento (status, data_vencimento),
                FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE SET NULL,
                FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
            )
        ",
        // Tabela pagamentos – adicionada na Fase 1 para alinhar com migrations
        'pagamentos' => "
            CREATE TABLE IF NOT EXISTS pagamentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fatura_id INT NOT NULL,
                data_pagamento DATE NOT NULL,
                valor_pago DECIMAL(10, 2) NOT NULL,
                metodo ENUM('pix', 'boleto', 'cartao', 'dinheiro', 'transferencia', 'outros') DEFAULT 'pix',
                comprovante_url VARCHAR(500) DEFAULT NULL,
                obs TEXT DEFAULT NULL,
                criado_por INT DEFAULT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_fatura (fatura_id),
                INDEX idx_data_pagamento (data_pagamento),
                FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
            )
        ",
        // Tabela financeiro_pagamentos (despesas) – adicionada na Fase 1 para alinhar com migrations
        'financeiro_pagamentos' => "
            CREATE TABLE IF NOT EXISTS financeiro_pagamentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fornecedor VARCHAR(200) NOT NULL,
                descricao TEXT DEFAULT NULL,
                categoria ENUM('combustivel', 'manutencao', 'salarios', 'aluguel', 'energia', 'agua', 'telefone', 'internet', 'outros') DEFAULT 'outros',
                valor DECIMAL(10, 2) NOT NULL,
                status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
                vencimento DATE NOT NULL,
                data_pagamento DATE DEFAULT NULL,
                forma_pagamento ENUM('pix', 'boleto', 'cartao', 'dinheiro', 'transferencia') DEFAULT 'pix',
                comprovante_url VARCHAR(500) DEFAULT NULL,
                observacoes TEXT DEFAULT NULL,
                criado_por INT DEFAULT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_vencimento (vencimento),
                INDEX idx_categoria (categoria),
                INDEX idx_status_vencimento (status, vencimento),
                FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
            )
        ",
        // Tabela financeiro_configuracoes – adicionada na Fase 2 para configurações do módulo financeiro
        'financeiro_configuracoes' => "
            CREATE TABLE IF NOT EXISTS financeiro_configuracoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chave VARCHAR(100) NOT NULL UNIQUE,
                valor VARCHAR(255) NOT NULL,
                descricao VARCHAR(255) DEFAULT NULL,
                tipo ENUM('texto', 'numero', 'booleano', 'data') DEFAULT 'texto',
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_chave (chave)
            )
        "
    ];
    
    // Criar tabelas
    foreach ($tables as $tableName => $sql) {
        try {
            $db->query($sql);
            echo "<p>✅ Tabela <strong>$tableName</strong> criada com sucesso</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Erro ao criar tabela <strong>$tableName</strong>: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar se usuário admin já existe
    $adminExists = $db->fetch("SELECT id FROM usuarios WHERE email = 'admin@cfc.com'");
    
    if (!$adminExists) {
        // Inserir usuário administrador padrão
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo, cpf, telefone, ativo) VALUES 
                ('Administrador', 'admin@cfc.com', :senha, 'admin', '000.000.000-00', '(11) 99999-9999', TRUE)";
        
        $db->query($sql, ['senha' => $adminPassword]);
        echo "<p>✅ Usuário administrador criado com sucesso</p>";
    } else {
        echo "<p>ℹ️ Usuário administrador já existe</p>";
    }
    
    // Verificar se CFC padrão já existe
    $cfcExists = $db->fetch("SELECT id FROM cfcs WHERE cnpj = '00.000.000/0000-00'");
    
    if (!$cfcExists) {
        // Inserir CFC padrão
        $sql = "INSERT INTO cfcs (nome, cnpj, endereco, telefone, email) VALUES 
                ('CFC Exemplo', '00.000.000/0000-00', 'Rua Exemplo, 123 - Centro', '(11) 3333-3333', 'contato@cfcexemplo.com')";
        
        $db->query($sql);
        echo "<p>✅ CFC padrão criado com sucesso</p>";
    } else {
        echo "<p>ℹ️ CFC padrão já existe</p>";
    }
    
    // Inserir configuração padrão de financeiro (Fase 2)
    try {
        $configExists = $db->fetch("SELECT id FROM financeiro_configuracoes WHERE chave = 'dias_inadimplencia'");
        if (!$configExists) {
            $sql = "INSERT INTO financeiro_configuracoes (chave, valor, descricao, tipo) VALUES 
                    ('dias_inadimplencia', '30', 'Número de dias após vencimento para considerar inadimplente', 'numero')";
            $db->query($sql);
            echo "<p>✅ Configuração financeira padrão criada com sucesso</p>";
        } else {
            echo "<p>ℹ️ Configuração financeira padrão já existe</p>";
        }
    } catch (Exception $e) {
        // Ignorar se tabela não existir ainda (será criada na próxima execução)
        echo "<p>⚠️ Não foi possível inserir configuração financeira (tabela pode não existir ainda): " . $e->getMessage() . "</p>";
    }
    
    // Criar índices para performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email)",
        "CREATE INDEX IF NOT EXISTS idx_alunos_cpf ON alunos(cpf)",
        "CREATE INDEX IF NOT EXISTS idx_alunos_cfc ON alunos(cfc_id)",
        "CREATE INDEX IF NOT EXISTS idx_aulas_data ON aulas(data_aula)",
        "CREATE INDEX IF NOT EXISTS idx_aulas_status ON aulas(status)",
        "CREATE INDEX IF NOT EXISTS idx_sessoes_token ON sessoes(token)",
        "CREATE INDEX IF NOT EXISTS idx_sessoes_expira ON sessoes(expira_em)",
        // Índices adicionados na Fase 1
        "CREATE INDEX IF NOT EXISTS idx_matriculas_aluno ON matriculas(aluno_id)",
        "CREATE INDEX IF NOT EXISTS idx_matriculas_status ON matriculas(status)",
        "CREATE INDEX IF NOT EXISTS idx_financeiro_faturas_aluno ON financeiro_faturas(aluno_id)",
        "CREATE INDEX IF NOT EXISTS idx_financeiro_faturas_status ON financeiro_faturas(status)",
        "CREATE INDEX IF NOT EXISTS idx_pagamentos_fatura ON pagamentos(fatura_id)",
        "CREATE INDEX IF NOT EXISTS idx_financeiro_pagamentos_status ON financeiro_pagamentos(status)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $db->query($index);
        } catch (Exception $e) {
            // Ignorar erros de índices duplicados
        }
    }
    
    echo "<p>✅ Índices de performance criados</p>";
    
    // Criar arquivo de lock
    file_put_contents('installed.lock', date('Y-m-d H:i:s'));
    
    echo "<hr>";
    echo "<h2>🎉 Instalação Concluída com Sucesso!</h2>";
    echo "<p><strong>Credenciais de Acesso:</strong></p>";
    echo "<ul>";
    echo "<li><strong>URL:</strong> <a href='index.php'>" . APP_URL . "/index.php</a></li>";
    echo "<li><strong>Email:</strong> admin@cfc.com</li>";
    echo "<li><strong>Senha:</strong> admin123</li>";
    echo "</ul>";
    
    echo "<p><strong>Próximos Passos:</strong></p>";
    echo "<ol>";
    echo "<li>Faça login com as credenciais acima</li>";
    echo "<li>Altere a senha padrão do administrador</li>";
    echo "<li>Configure os dados do seu CFC</li>";
    echo "<li>Comece a usar o sistema!</li>";
    echo "</ol>";
    
    echo "<p><a href='index.php' class='btn btn-primary'>Ir para o Login</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro na Instalação</h2>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Verifique as configurações do banco de dados em <code>includes/config.php</code></p>";
}
?>
