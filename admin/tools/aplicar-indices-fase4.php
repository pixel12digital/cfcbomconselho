<?php
/**
 * Script para Aplicar Índices FASE 4
 * Sistema CFC - Bom Conselho
 * 
 * IMPORTANTE: Este script aplica índices no banco de dados.
 * Execute apenas após fazer backup e em horário de baixo tráfego.
 */

// Verificar autenticação
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar se está logado
if (!isLoggedIn()) {
    http_response_code(403);
    die('Acesso negado. Faça login para executar este script.');
}

// Obter dados do usuário atual
$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(403);
    die('Acesso negado. Não foi possível obter dados do usuário.');
}

// Verificar se é administrador (usar mesma lógica do sistema)
$isAdmin = ($currentUser['tipo'] ?? '') === 'admin';
if (!$isAdmin) {
    http_response_code(403);
    die('Acesso negado. Apenas administradores podem executar este script.<br>Seu tipo de usuário: ' . htmlspecialchars($currentUser['tipo'] ?? 'desconhecido'));
}

// Verificar se é POST (confirmação de execução)
$confirmar = $_POST['confirmar'] ?? false;
$prioridade = $_POST['prioridade'] ?? 'alta'; // alta, media, complementares, analyze
$executar = $_POST['executar'] ?? false;

// Headers
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicar Índices FASE 4 - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-box {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .log-success { color: #4ec9b0; }
        .log-error { color: #f48771; }
        .log-warning { color: #dcdcaa; }
        .log-info { color: #569cd6; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">🔧 Aplicar Índices FASE 4</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!$executar): ?>
                            <!-- Formulário de Confirmação -->
                            <div class="alert alert-warning">
                                <h5>⚠️ Atenção!</h5>
                                <ul>
                                    <li>Este script aplicará índices no banco de dados remoto</li>
                                    <li><strong>Faça backup antes de executar!</strong></li>
                                    <li>Execute em horário de baixo tráfego</li>
                                    <li>O processo pode levar alguns minutos</li>
                                </ul>
                            </div>

                            <form method="POST" id="formAplicar">
                                <input type="hidden" name="executar" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label"><strong>Selecione a Prioridade:</strong></label>
                                    <select name="prioridade" class="form-select" required>
                                        <option value="alta">🔴 Prioridade ALTA (Recomendado primeiro)</option>
                                        <option value="media">🟡 Prioridade MÉDIA (Após validar ALTA)</option>
                                        <option value="complementares">🟢 Complementares (Opcional)</option>
                                        <option value="analyze">📊 ANALYZE TABLE (Após todos os índices)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="confirmar" name="confirmar" required>
                                        <label class="form-check-label" for="confirmar">
                                            <strong>Confirmo que fiz backup do banco de dados</strong>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg">
                                    ▶️ Executar Script
                                </button>
                                <a href="../../admin/index.php" class="btn btn-secondary btn-lg">Cancelar</a>
                            </form>

                        <?php else: ?>
                            <!-- Execução do Script -->
                            <div class="log-box" id="logBox">
                                <div class="log-info">🚀 Iniciando aplicação de índices...</div>
                                <div class="log-info">📋 Prioridade selecionada: <?php echo strtoupper($prioridade); ?></div>
                                <div class="log-info">⏰ <?php echo date('Y-m-d H:i:s'); ?></div>
                                <hr style="border-color: #555;">
                                <?php
                                try {
                                    $db = Database::getInstance();
                                    $pdo = $db->getConnection();
                                    
                                    // Determinar arquivo SQL baseado na prioridade
                                    $arquivoSQL = '';
                                    switch ($prioridade) {
                                        case 'alta':
                                            $arquivoSQL = __DIR__ . '/../../docs/FASE4_INDICES_PRIORIDADE_ALTA.sql';
                                            break;
                                        case 'media':
                                            $arquivoSQL = __DIR__ . '/../../docs/FASE4_INDICES_PRIORIDADE_MEDIA.sql';
                                            break;
                                        case 'complementares':
                                            $arquivoSQL = __DIR__ . '/../../docs/FASE4_INDICES_COMPLEMENTARES.sql';
                                            break;
                                        case 'analyze':
                                            $arquivoSQL = __DIR__ . '/../../docs/FASE4_ANALYZE_TABLES.sql';
                                            break;
                                    }
                                    
                                    if (!file_exists($arquivoSQL)) {
                                        throw new Exception("Arquivo SQL não encontrado: $arquivoSQL");
                                    }
                                    
                                    echo '<div class="log-info">📄 Arquivo: ' . basename($arquivoSQL) . '</div>';
                                    echo '<div class="log-info">📖 Lendo arquivo...</div>';
                                    
                                    $sql = file_get_contents($arquivoSQL);
                                    
                                    // Remover comentários SQL (-- e /* */)
                                    $sql = preg_replace('/--.*$/m', '', $sql);
                                    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
                                    
                                    // Dividir em comandos individuais
                                    $comandos = array_filter(
                                        array_map('trim', explode(';', $sql)),
                                        function($cmd) {
                                            return !empty($cmd) && stripos($cmd, 'CREATE INDEX') !== false || stripos($cmd, 'ANALYZE TABLE') !== false;
                                        }
                                    );
                                    
                                    echo '<div class="log-info">✅ ' . count($comandos) . ' comando(s) encontrado(s)</div>';
                                    echo '<hr style="border-color: #555;">';
                                    
                                    $sucesso = 0;
                                    $erros = 0;
                                    $inicio = microtime(true);
                                    
                                    foreach ($comandos as $index => $comando) {
                                        $comando = trim($comando);
                                        if (empty($comando)) continue;
                                        
                                        echo '<div class="log-info">[ ' . ($index + 1) . ' / ' . count($comandos) . ' ] Executando...</div>';
                                        
                                        // Extrair nome do índice para log
                                        if (preg_match('/CREATE INDEX\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $comando, $matches)) {
                                            echo '<div class="log-info">   Índice: ' . $matches[1] . '</div>';
                                        } elseif (preg_match('/ANALYZE TABLE\s+(\w+)/i', $comando, $matches)) {
                                            echo '<div class="log-info">   Tabela: ' . $matches[1] . '</div>';
                                        }
                                        
                                        try {
                                            $pdo->exec($comando . ';');
                                            $sucesso++;
                                            echo '<div class="log-success">   ✅ Sucesso!</div>';
                                        } catch (PDOException $e) {
                                            $erros++;
                                            $erroMsg = $e->getMessage();
                                            
                                            // Ignorar erro se índice já existe
                                            if (strpos($erroMsg, 'Duplicate key name') !== false || 
                                                strpos($erroMsg, 'already exists') !== false) {
                                                echo '<div class="log-warning">   ⚠️ Índice já existe (ignorado)</div>';
                                                $sucesso++; // Contar como sucesso
                                                $erros--;
                                            } else {
                                                echo '<div class="log-error">   ❌ Erro: ' . htmlspecialchars($erroMsg) . '</div>';
                                            }
                                        }
                                        
                                        // Pequeno delay para não sobrecarregar
                                        usleep(100000); // 100ms
                                        
                                        // Flush output para mostrar progresso em tempo real
                                        if (ob_get_level() > 0) {
                                            ob_flush();
                                        }
                                        flush();
                                    }
                                    
                                    $tempo = round(microtime(true) - $inicio, 2);
                                    
                                    echo '<hr style="border-color: #555;">';
                                    echo '<div class="log-success"><strong>✅ Processo concluído!</strong></div>';
                                    echo '<div class="log-info">⏱️ Tempo total: ' . $tempo . ' segundos</div>';
                                    echo '<div class="log-success">✅ Sucessos: ' . $sucesso . '</div>';
                                    if ($erros > 0) {
                                        echo '<div class="log-error">❌ Erros: ' . $erros . '</div>';
                                    }
                                    
                                } catch (Exception $e) {
                                    echo '<div class="log-error">❌ ERRO FATAL: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                    echo '<div class="log-error">Arquivo: ' . htmlspecialchars($e->getFile()) . '</div>';
                                    echo '<div class="log-error">Linha: ' . $e->getLine() . '</div>';
                                }
                                ?>
                            </div>

                            <div class="mt-4">
                                <a href="aplicar-indices-fase4.php" class="btn btn-primary">🔄 Executar Outro Script</a>
                                <a href="../../admin/index.php" class="btn btn-secondary">🏠 Voltar ao Admin</a>
                                <a href="../../docs/FASE4_VERIFICAR_INDICES.sql" target="_blank" class="btn btn-info">🔍 Verificar Índices</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll para o final do log
        const logBox = document.getElementById('logBox');
        if (logBox) {
            logBox.scrollTop = logBox.scrollHeight;
        }
    </script>
</body>
</html>

