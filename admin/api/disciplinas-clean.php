<?php
/**
 * API para Gerenciamento de Disciplinas
 * CRUD completo para disciplinas do sistema
 */

// Configurações diretas do banco (copiadas do config.php)
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');

// Headers primeiro
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Desabilitar exibição de erros para evitar interferência no JSON
ini_set('display_errors', 0);
error_reporting(0);

// Limpar qualquer saída anterior
while (ob_get_level()) {
    ob_end_clean();
}

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

// Iniciar buffer de saída
ob_start();

try {
    // Conectar ao banco
    $db = conectarBanco();
    
    if (!$db) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }
    
    // Verificar e criar tabela se necessário
    verificarECriarTabelaDisciplinas($db);
    
    // Obter ação
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? 'listar';
    
    // Processar ação
    switch ($acao) {
        case 'listar':
            listarDisciplinas($db);
            break;
            
        case 'criar':
            criarDisciplina($db);
            break;
            
        case 'editar':
            editarDisciplina($db);
            break;
            
        case 'excluir':
            excluirDisciplina($db);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    // Limpar qualquer saída anterior
    ob_clean();
    
    // Retornar erro em JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'debug' => [
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // Garantir que apenas JSON seja enviado
    ob_end_flush();
}

/**
 * Verificar e criar tabela de disciplinas se não existir
 */
function verificarECriarTabelaDisciplinas($db) {
    try {
        // Verificar se a tabela existe
        $stmt = $db->query("SHOW TABLES LIKE 'disciplinas'");
        $tabelaExiste = $stmt->fetch();
        
        if (!$tabelaExiste) {
            // Criar tabela disciplinas
            $sql = "
                CREATE TABLE disciplinas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    codigo VARCHAR(50) NOT NULL UNIQUE,
                    nome VARCHAR(100) NOT NULL,
                    descricao TEXT,
                    carga_horaria_padrao INT DEFAULT 1,
                    cor_hex VARCHAR(7) DEFAULT '#007bff',
                    icone VARCHAR(50) DEFAULT 'book',
                    ativa BOOLEAN DEFAULT TRUE,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    INDEX idx_codigo (codigo),
                    INDEX idx_ativa (ativa)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            
            $db->exec($sql);
            
            // Inserir disciplinas padrão
            $disciplinasPadrao = [
                ['codigo' => 'legislacao_transito', 'nome' => 'Legislação de Trânsito', 'descricao' => 'Estudo das leis e normas de trânsito', 'carga_horaria_padrao' => 18, 'cor_hex' => '#dc3545', 'icone' => 'gavel'],
                ['codigo' => 'direcao_defensiva', 'nome' => 'Direção Defensiva', 'descricao' => 'Técnicas de direção segura', 'carga_horaria_padrao' => 16, 'cor_hex' => '#28a745', 'icone' => 'shield-alt'],
                ['codigo' => 'primeiros_socorros', 'nome' => 'Primeiros Socorros', 'descricao' => 'Noções básicas de primeiros socorros', 'carga_horaria_padrao' => 4, 'cor_hex' => '#ffc107', 'icone' => 'first-aid'],
                ['codigo' => 'meio_ambiente_cidadania', 'nome' => 'Meio Ambiente e Cidadania', 'descricao' => 'Educação ambiental e cidadania', 'carga_horaria_padrao' => 4, 'cor_hex' => '#17a2b8', 'icone' => 'leaf'],
                ['codigo' => 'mecanica_basica', 'nome' => 'Mecânica Básica', 'descricao' => 'Conhecimentos básicos de mecânica', 'carga_horaria_padrao' => 3, 'cor_hex' => '#6c757d', 'icone' => 'wrench']
            ];
            
            foreach ($disciplinasPadrao as $disciplina) {
                $sql = "INSERT INTO disciplinas (codigo, nome, descricao, carga_horaria_padrao, cor_hex, icone) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $disciplina['codigo'],
                    $disciplina['nome'],
                    $disciplina['descricao'],
                    $disciplina['carga_horaria_padrao'],
                    $disciplina['cor_hex'],
                    $disciplina['icone']
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao criar tabela disciplinas: " . $e->getMessage());
        throw new Exception("Erro ao inicializar sistema de disciplinas");
    }
}

/**
 * Listar disciplinas
 */
function listarDisciplinas($db) {
    try {
        $stmt = $db->query("SELECT * FROM disciplinas ORDER BY nome ASC");
        $disciplinas = $stmt->fetchAll();
        
        $html = gerarHtmlDisciplinas($disciplinas);
        
        echo json_encode([
            'sucesso' => true,
            'disciplinas' => $disciplinas,
            'html' => $html,
            'total' => count($disciplinas),
            'debug' => [
                'total_disciplinas' => count($disciplinas),
                'sql_executado' => 'SELECT * FROM disciplinas ORDER BY nome ASC'
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Erro ao listar disciplinas: " . $e->getMessage());
    }
}

/**
 * Criar nova disciplina
 */
function criarDisciplina($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    try {
        $dados = [
            'codigo' => $_POST['codigo'] ?? '',
            'nome' => $_POST['nome'] ?? '',
            'descricao' => $_POST['descricao'] ?? '',
            'carga_horaria_padrao' => (int)($_POST['carga_horaria_padrao'] ?? 1),
            'cor_hex' => $_POST['cor_hex'] ?? '#007bff',
            'icone' => $_POST['icone'] ?? 'book',
            'ativa' => 1
        ];
        
        // Validar dados obrigatórios
        if (empty($dados['codigo']) || empty($dados['nome'])) {
            throw new Exception('Código e nome são obrigatórios');
        }
        
        // Verificar se código já existe
        $stmt = $db->prepare("SELECT id FROM disciplinas WHERE codigo = ?");
        $stmt->execute([$dados['codigo']]);
        $existente = $stmt->fetch();
        if ($existente) {
            throw new Exception('Já existe uma disciplina com este código');
        }
        
        // Inserir disciplina
        $sql = "INSERT INTO disciplinas (codigo, nome, descricao, carga_horaria_padrao, cor_hex, icone, ativa) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'],
            $dados['carga_horaria_padrao'],
            $dados['cor_hex'],
            $dados['icone'],
            $dados['ativa']
        ]);
        
        $id = $db->lastInsertId();
        
        // Buscar disciplina criada
        $stmt = $db->prepare("SELECT * FROM disciplinas WHERE id = ?");
        $stmt->execute([$id]);
        $disciplina = $stmt->fetch();
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Disciplina criada com sucesso!',
            'disciplina' => $disciplina,
            'debug' => [
                'disciplina_id' => $id,
                'dados_inseridos' => $dados
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Erro ao criar disciplina: " . $e->getMessage());
    }
}

/**
 * Editar disciplina
 */
function editarDisciplina($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    try {
        $id = $_POST['id'] ?? '';
        $dados = [
            'codigo' => $_POST['codigo'] ?? '',
            'nome' => $_POST['nome'] ?? '',
            'descricao' => $_POST['descricao'] ?? '',
            'carga_horaria_padrao' => (int)($_POST['carga_horaria_padrao'] ?? 1),
            'cor_hex' => $_POST['cor_hex'] ?? '#007bff',
            'icone' => $_POST['icone'] ?? 'book'
        ];
        
        // Validar dados obrigatórios
        if (empty($id) || empty($dados['codigo']) || empty($dados['nome'])) {
            throw new Exception('ID, código e nome são obrigatórios');
        }
        
        // Verificar se disciplina existe
        $stmt = $db->prepare("SELECT * FROM disciplinas WHERE id = ?");
        $stmt->execute([$id]);
        $disciplina = $stmt->fetch();
        if (!$disciplina) {
            throw new Exception('Disciplina não encontrada');
        }
        
        // Verificar se código já existe em outra disciplina
        $stmt = $db->prepare("SELECT id FROM disciplinas WHERE codigo = ? AND id != ?");
        $stmt->execute([$dados['codigo'], $id]);
        $existente = $stmt->fetch();
        if ($existente) {
            throw new Exception('Já existe outra disciplina com este código');
        }
        
        // Atualizar disciplina
        $sql = "UPDATE disciplinas SET codigo = ?, nome = ?, descricao = ?, carga_horaria_padrao = ?, cor_hex = ?, icone = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'],
            $dados['carga_horaria_padrao'],
            $dados['cor_hex'],
            $dados['icone'],
            $id
        ]);
        
        // Buscar disciplina atualizada
        $stmt = $db->prepare("SELECT * FROM disciplinas WHERE id = ?");
        $stmt->execute([$id]);
        $disciplinaAtualizada = $stmt->fetch();
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Disciplina atualizada com sucesso!',
            'disciplina' => $disciplinaAtualizada,
            'debug' => [
                'disciplina_id' => $id,
                'dados_atualizados' => $dados
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Erro ao editar disciplina: " . $e->getMessage());
    }
}

/**
 * Excluir disciplina
 */
function excluirDisciplina($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    try {
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            throw new Exception('ID da disciplina é obrigatório');
        }
        
        // Verificar se disciplina existe
        $stmt = $db->prepare("SELECT * FROM disciplinas WHERE id = ?");
        $stmt->execute([$id]);
        $disciplina = $stmt->fetch();
        if (!$disciplina) {
            throw new Exception('Disciplina não encontrada');
        }
        
        // Verificar se disciplina está sendo usada em turmas
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM disciplinas_configuracao WHERE disciplina = ?");
            $stmt->execute([$disciplina['codigo']]);
            $emUso = $stmt->fetch();
            
            if ($emUso && $emUso['total'] > 0) {
                throw new Exception('Esta disciplina está sendo usada em ' . $emUso['total'] . ' configuração(ões) de curso e não pode ser excluída');
            }
        } catch (Exception $e) {
            // Se a tabela disciplinas_configuracao não existir, continuar
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                throw $e;
            }
        }
        
        // Excluir disciplina
        $stmt = $db->prepare("DELETE FROM disciplinas WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Disciplina excluída com sucesso!',
            'debug' => [
                'disciplina_id' => $id,
                'disciplina_excluida' => $disciplina
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        throw new Exception("Erro ao excluir disciplina: " . $e->getMessage());
    }
}

/**
 * Gerar HTML das disciplinas com layout otimizado
 */
function gerarHtmlDisciplinas($disciplinas) {
    if (empty($disciplinas)) {
        return '
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="fas fa-book-open" style="font-size: 3rem; color: #6c757d;"></i>
            </div>
            <h5 class="text-muted">Nenhuma disciplina cadastrada</h5>
            <p class="text-muted">Clique em "Nova Disciplina" para começar.</p>
        </div>';
    }
    
    $html = '';
    foreach ($disciplinas as $disciplina) {
        $statusClass = $disciplina['ativa'] ? 'ativo' : 'inativo';
        $statusText = $disciplina['ativa'] ? 'ATIVO' : 'INATIVO';
        $statusColor = $disciplina['ativa'] ? '#28a745' : '#dc3545';
        
        $html .= '
        <div class="col-lg-4 col-md-6 col-12 mb-3">
            <div class="card h-100 shadow-sm border-0 disciplina-card" style="transition: all 0.3s ease; cursor: pointer;" onclick="visualizarDisciplina(' . $disciplina['id'] . ')">
                <div class="card-header border-0 p-3" style="background: linear-gradient(135deg, ' . $disciplina['cor_hex'] . ' 0%, ' . $disciplina['cor_hex'] . 'dd 100%); color: white; position: relative;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold">
                                <i class="fas fa-' . $disciplina['icone'] . ' me-2"></i>
                                ' . htmlspecialchars($disciplina['nome']) . '
                            </h6>
                            <small class="opacity-75">' . htmlspecialchars($disciplina['codigo']) . '</small>
                        </div>
                        <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(255,255,255,0.25); color: white; font-size: 0.7rem;">
                            ' . $statusText . '
                        </span>
                    </div>
                    <div class="position-absolute top-0 end-0 me-3 mt-2">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light rounded-circle p-1" style="width: 28px; height: 28px; padding: 0;" data-bs-toggle="dropdown" onclick="event.stopPropagation();">
                                <i class="fas fa-ellipsis-v" style="font-size: 0.8rem;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="event.stopPropagation(); editarDisciplina(' . $disciplina['id'] . ');"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="event.stopPropagation(); excluirDisciplina(' . $disciplina['id'] . ');"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-clock me-2" style="color: ' . $disciplina['cor_hex'] . ';"></i>
                                <span class="fw-semibold">' . $disciplina['carga_horaria_padrao'] . ' aulas</span>
                            </div>
                        </div>
                    </div>
                    <div class="disciplina-descricao">
                        <small class="text-muted lh-sm">' . nl2br(htmlspecialchars($disciplina['descricao'])) . '</small>
                    </div>
                </div>
                <div class="card-footer border-0 bg-transparent p-3 pt-0">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); editarDisciplina(' . $disciplina['id'] . ');">
                            <i class="fas fa-edit me-1"></i> Editar Disciplina
                        </button>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    return $html;
}
?>
