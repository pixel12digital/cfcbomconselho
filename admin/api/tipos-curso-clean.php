<?php
// API completamente limpa para tipos de curso
// Sem dependências que possam gerar HTML

// Configurações diretas do banco (copiadas do config.php)
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');

// Headers primeiro
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Desabilitar qualquer output de erro
ini_set('display_errors', 0);
error_reporting(0);

// Função para conectar ao banco diretamente
function conectarBanco() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// Função para listar tipos de curso
function listarTiposCurso($pdo) {
    try {
        // Verificar se tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'tipos_curso'");
        if ($stmt->rowCount() == 0) {
            // Criar tabela
            $pdo->exec("
                CREATE TABLE tipos_curso (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    codigo VARCHAR(50) NOT NULL UNIQUE,
                    nome VARCHAR(200) NOT NULL,
                    descricao TEXT,
                    carga_horaria_total INT NOT NULL DEFAULT 0,
                    ativo BOOLEAN DEFAULT TRUE,
                    cfc_id INT NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_cfc_ativo (cfc_id, ativo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Inserir tipos padrão
            $tiposPadrao = [
                ['formacao_45h', 'Curso de Formação de Condutores - Permissão 45h', 'Curso completo para obtenção da primeira habilitação', 45],
                ['formacao_acc_20h', 'Curso de Formação de Condutores - ACC 20h', 'Curso para Adição de Categoria ou Mudança de Categoria', 20],
                ['reciclagem_infrator', 'Curso de Reciclagem para Condutor Infrator', 'Reciclagem obrigatória para condutores infratores', 30],
                ['atualizacao', 'Curso de Atualização', 'Atualização de conhecimentos para condutores', 15]
            ];
            
            foreach ($tiposPadrao as $tipo) {
                $pdo->prepare("INSERT INTO tipos_curso (codigo, nome, descricao, carga_horaria_total, ativo, cfc_id) VALUES (?, ?, ?, ?, 1, ?)")
                    ->execute([$tipo[0], $tipo[1], $tipo[2], $tipo[3], 1]);
            }
        }
        
        // Buscar tipos de curso
        $stmt = $pdo->prepare("SELECT * FROM tipos_curso WHERE cfc_id = ? ORDER BY nome ASC");
        $stmt->execute([1]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        return [];
    }
}

// Função para gerar HTML dos tipos de curso
function gerarHtmlTiposCurso($tipos) {
    if (empty($tipos)) {
        return '<div class="text-center py-3">
            <i class="fas fa-graduation-cap fa-2x text-muted mb-2"></i>
            <p class="text-muted">Nenhum tipo de curso cadastrado</p>
        </div>';
    }
    
    $html = '';
    foreach ($tipos as $tipo) {
        $statusBadge = $tipo['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
        
        $html .= '<div class="col-12 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>' . htmlspecialchars($tipo['nome']) . '</h6>
                    ' . $statusBadge . '
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-2">
                                <strong><i class="fas fa-tag me-1"></i>Código:</strong> ' . htmlspecialchars($tipo['codigo']) . '
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <strong><i class="fas fa-clock me-1"></i>Carga Horária:</strong> ' . $tipo['carga_horaria_total'] . ' horas
                            </div>
                        </div>
                        <div class="col-md-6">
                            ' . ($tipo['descricao'] ? '<div class="mb-2"><strong><i class="fas fa-info-circle me-1"></i>Descrição:</strong> ' . htmlspecialchars($tipo['descricao']) . '</div>' : '') . '
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editarTipoCurso(' . $tipo['id'] . ', \'' . htmlspecialchars($tipo['codigo']) . '\', \'' . htmlspecialchars($tipo['nome']) . '\', \'' . htmlspecialchars($tipo['descricao']) . '\', ' . $tipo['carga_horaria_total'] . ', ' . $tipo['ativo'] . ')">
                            <i class="fas fa-edit me-1"></i>Editar
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="excluirTipoCurso(' . $tipo['id'] . ', \'' . htmlspecialchars($tipo['nome']) . '\')">
                            <i class="fas fa-trash me-1"></i>Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    return '<div class="row">' . $html . '</div>';
}

// Processar requisição
try {
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? 'listar';
    
    if ($acao === 'listar') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $tipos = listarTiposCurso($pdo);
        $html = gerarHtmlTiposCurso($tipos);
        
        echo json_encode([
            'sucesso' => true,
            'tipos' => $tipos,
            'html' => $html
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($acao === 'editar') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $codigo = trim($_POST['codigo'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $carga_horaria_total = (int)($_POST['carga_horaria_total'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 1);
        
        if ($id <= 0) {
            throw new Exception('ID do tipo de curso inválido');
        }
        
        if (empty($codigo)) {
            throw new Exception('Código do curso é obrigatório');
        }
        
        if (empty($nome)) {
            throw new Exception('Nome do curso é obrigatório');
        }
        
        if ($carga_horaria_total <= 0) {
            throw new Exception('Carga horária deve ser maior que zero');
        }
        
        // Verificar se tipo existe
        $stmt = $pdo->prepare("SELECT id FROM tipos_curso WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$id, 1]);
        if ($stmt->rowCount() == 0) {
            throw new Exception('Tipo de curso não encontrado');
        }
        
        // Verificar se código já existe em outro tipo
        $stmt = $pdo->prepare("SELECT id FROM tipos_curso WHERE codigo = ? AND id != ? AND cfc_id = ?");
        $stmt->execute([$codigo, $id, 1]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Já existe um tipo de curso com este código');
        }
        
        // Atualizar tipo
        $stmt = $pdo->prepare("UPDATE tipos_curso SET codigo = ?, nome = ?, descricao = ?, carga_horaria_total = ?, ativo = ? WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$codigo, $nome, $descricao, $carga_horaria_total, $ativo, $id, 1]);
        
        // Processar disciplinas selecionadas
        if (isset($_POST['disciplinas'])) {
            $disciplinasJson = $_POST['disciplinas'];
            $disciplinasSelecionadas = json_decode($disciplinasJson, true);
            
            if (is_array($disciplinasSelecionadas)) {
                // Primeiro, remover todas as disciplinas configuradas para este curso
                try {
                    $stmt = $pdo->prepare("DELETE FROM disciplinas_configuracao WHERE curso_tipo = ?");
                    $stmt->execute([$codigo]);
                } catch (PDOException $e) {
                    // Se a tabela não existir, criar ela
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS disciplinas_configuracao (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            curso_tipo VARCHAR(50) NOT NULL,
                            disciplina VARCHAR(50) NOT NULL,
                            nome_disciplina VARCHAR(100) NOT NULL,
                            aulas_obrigatorias INT NOT NULL,
                            ordem INT NOT NULL DEFAULT 1,
                            cor_hex VARCHAR(7) DEFAULT '#007bff',
                            icone VARCHAR(50) DEFAULT 'book',
                            ativa BOOLEAN DEFAULT TRUE,
                            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            
                            UNIQUE KEY unique_curso_disciplina (curso_tipo, disciplina),
                            INDEX idx_curso_ordem (curso_tipo, ordem)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                }
                
                // Mapear IDs das disciplinas para códigos
                $mapeamentoDisciplinas = [
                    1 => ['codigo' => 'legislacao_transito', 'nome' => 'Legislação de Trânsito', 'aulas' => 18],
                    2 => ['codigo' => 'direcao_defensiva', 'nome' => 'Direção Defensiva', 'aulas' => 16],
                    3 => ['codigo' => 'primeiros_socorros', 'nome' => 'Primeiros Socorros', 'aulas' => 4],
                    4 => ['codigo' => 'meio_ambiente_cidadania', 'nome' => 'Meio Ambiente e Cidadania', 'aulas' => 4],
                    5 => ['codigo' => 'mecanica_basica', 'nome' => 'Mecânica Básica', 'aulas' => 3]
                ];
                
                // Inserir disciplinas selecionadas
                $ordem = 1;
                foreach ($disciplinasSelecionadas as $disciplinaId) {
                    if (isset($mapeamentoDisciplinas[$disciplinaId])) {
                        $disc = $mapeamentoDisciplinas[$disciplinaId];
                        $stmt = $pdo->prepare("
                            INSERT INTO disciplinas_configuracao 
                            (curso_tipo, disciplina, nome_disciplina, aulas_obrigatorias, ordem, ativa) 
                            VALUES (?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([
                            $codigo, 
                            $disc['codigo'], 
                            $disc['nome'], 
                            $disc['aulas'], 
                            $ordem++
                        ]);
                    }
                }
            }
        }
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Tipo de curso atualizado com sucesso!'
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($acao === 'excluir') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception('ID do tipo de curso inválido');
        }
        
        // Verificar se tipo existe
        $stmt = $pdo->prepare("SELECT id FROM tipos_curso WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$id, 1]);
        if ($stmt->rowCount() == 0) {
            throw new Exception('Tipo de curso não encontrado');
        }
        
        // Verificar se tipo está sendo usada em turmas
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM turmas_teoricas WHERE curso_tipo = (SELECT codigo FROM tipos_curso WHERE id = ?)");
            $stmt->execute([$id]);
            $resultado = $stmt->fetch();
            
            if ($resultado['total'] > 0) {
                throw new Exception('Não é possível excluir este tipo de curso pois ele está sendo usado em turmas teóricas');
            }
        } catch (PDOException $e) {
            // Se a tabela turmas_teoricas não existir, ignorar a verificação
            error_log("DEBUG - Tabela turmas_teoricas não existe ou erro: " . $e->getMessage());
        }
        
        // Excluir tipo
        $stmt = $pdo->prepare("DELETE FROM tipos_curso WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$id, 1]);
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Tipo de curso excluído com sucesso!'
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($acao === 'criar') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $codigo = trim($_POST['codigo'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $carga_horaria_total = (int)($_POST['carga_horaria_total'] ?? 0);
        $ativo = 1;
        
        if (empty($codigo)) {
            throw new Exception('Código do curso é obrigatório');
        }
        
        if (empty($nome)) {
            throw new Exception('Nome do curso é obrigatório');
        }
        
        if ($carga_horaria_total <= 0) {
            throw new Exception('Carga horária deve ser maior que zero');
        }
        
        // Verificar se já existe tipo com o mesmo código
        $stmt = $pdo->prepare("SELECT id FROM tipos_curso WHERE codigo = ? AND cfc_id = ?");
        $stmt->execute([$codigo, 1]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Já existe um tipo de curso com este código');
        }
        
        // Inserir novo tipo
        $stmt = $pdo->prepare("INSERT INTO tipos_curso (codigo, nome, descricao, carga_horaria_total, ativo, cfc_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $nome, $descricao, $carga_horaria_total, $ativo, 1]);
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Tipo de curso criado com sucesso!',
            'tipo' => [
                'id' => $pdo->lastInsertId(),
                'codigo' => $codigo,
                'nome' => $nome
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    // Log do erro para debug
    error_log("DEBUG - Erro na API tipos de curso: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'debug' => [
            'acao' => $acao ?? 'não definida',
            'post_data' => $_POST,
            'get_data' => $_GET
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
