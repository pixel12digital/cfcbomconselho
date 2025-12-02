<?php
/**
 * Página para Aplicar Índices FASE 4
 * Sistema CFC - Bom Conselho
 * 
 * Acessível via: index.php?page=aplicar-indices
 */

// Verificar se estamos sendo incluídos pelo sistema de roteamento do admin
if (!defined('ADMIN_ROUTING')) {
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    require_once '../../includes/auth.php';
    
    // Verificar se usuário está logado
    if (!isLoggedIn()) {
        header('Location: ../../index.php');
        exit;
    }
}

// Verificar se é administrador
$currentUser = getCurrentUser();
if (!$currentUser || ($currentUser['tipo'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Acesso negado. Apenas administradores podem executar este script.</div>';
    return;
}

// Verificar se é POST (confirmação de execução)
$confirmar = $_POST['confirmar'] ?? false;
$prioridade = $_POST['prioridade'] ?? 'alta';
$executar = $_POST['executar'] ?? false;

// Determinar caminho raiz
$rootPath = dirname(__DIR__, 2);

?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">🔧 Aplicar Índices FASE 4</h3>
                </div>
                <div class="card-body">
                    <?php if (!$executar): ?>
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
                            <a href="index.php" class="btn btn-secondary btn-lg">Cancelar</a>
                        </form>

                    <?php else: ?>
                        <div class="log-box" id="logBox" style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 500px; overflow-y: auto;">
                            <div class="log-info" style="color: #569cd6;">🚀 Iniciando aplicação de índices...</div>
                            <div class="log-info" style="color: #569cd6;">📋 Prioridade: <?php echo strtoupper($prioridade); ?></div>
                            <div class="log-info" style="color: #569cd6;">⏰ <?php echo date('Y-m-d H:i:s'); ?></div>
                            <hr style="border-color: #555;">
                            <?php
                            try {
                                $db = Database::getInstance();
                                $pdo = $db->getConnection();
                                
                                // Configurar PDO para usar queries bufferizadas (necessário para ANALYZE TABLE)
                                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                                
                                // Determinar arquivo SQL
                                $arquivoSQL = '';
                                $docsPath = $rootPath . '/docs/';
                                switch ($prioridade) {
                                    case 'alta':
                                        $arquivoSQL = $docsPath . 'FASE4_INDICES_PRIORIDADE_ALTA.sql';
                                        break;
                                    case 'media':
                                        $arquivoSQL = $docsPath . 'FASE4_INDICES_PRIORIDADE_MEDIA.sql';
                                        break;
                                    case 'complementares':
                                        $arquivoSQL = $docsPath . 'FASE4_INDICES_COMPLEMENTARES.sql';
                                        break;
                                    case 'analyze':
                                        $arquivoSQL = $docsPath . 'FASE4_ANALYZE_TABLES.sql';
                                        break;
                                }
                                
                                if (!file_exists($arquivoSQL)) {
                                    throw new Exception("Arquivo SQL não encontrado: $arquivoSQL");
                                }
                                
                                echo '<div class="log-info" style="color: #569cd6;">📄 Arquivo: ' . basename($arquivoSQL) . '</div>';
                                
                                $sql = file_get_contents($arquivoSQL);
                                $sql = preg_replace('/--.*$/m', '', $sql);
                                $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
                                
                                $comandos = array_filter(
                                    array_map('trim', explode(';', $sql)),
                                    function($cmd) {
                                        return !empty($cmd) && (stripos($cmd, 'CREATE INDEX') !== false || stripos($cmd, 'ANALYZE TABLE') !== false);
                                    }
                                );
                                
                                echo '<div class="log-info" style="color: #569cd6;">✅ ' . count($comandos) . ' comando(s) encontrado(s)</div>';
                                echo '<hr style="border-color: #555;">';
                                
                                $sucesso = 0;
                                $erros = 0;
                                $inicio = microtime(true);
                                
                                foreach ($comandos as $index => $comando) {
                                    $comando = trim($comando);
                                    if (empty($comando)) continue;
                                    
                                    echo '<div class="log-info" style="color: #569cd6;">[ ' . ($index + 1) . ' / ' . count($comandos) . ' ] Executando...</div>';
                                    
                                    if (preg_match('/CREATE INDEX\s+(?:IF NOT EXISTS\s+)?(\w+)/i', $comando, $matches)) {
                                        echo '<div class="log-info" style="color: #569cd6;">   Índice: ' . $matches[1] . '</div>';
                                    } elseif (preg_match('/ANALYZE TABLE\s+(\w+)/i', $comando, $matches)) {
                                        echo '<div class="log-info" style="color: #569cd6;">   Tabela: ' . $matches[1] . '</div>';
                                    }
                                    
                                    try {
                                        // Para ANALYZE TABLE, garantir que não há queries pendentes
                                        if (stripos($comando, 'ANALYZE TABLE') !== false) {
                                            // Fechar qualquer statement pendente
                                            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                                            // Executar com statement preparado para garantir isolamento
                                            $stmt = $pdo->prepare($comando);
                                            $stmt->execute();
                                            $stmt->closeCursor();
                                        } else {
                                            // Para CREATE INDEX, usar exec normalmente
                                            $pdo->exec($comando . ';');
                                        }
                                        $sucesso++;
                                        echo '<div class="log-success" style="color: #4ec9b0;">   ✅ Sucesso!</div>';
                                    } catch (PDOException $e) {
                                        $erroMsg = $e->getMessage();
                                        
                                        if (strpos($erroMsg, 'Duplicate key name') !== false || 
                                            strpos($erroMsg, 'already exists') !== false) {
                                            echo '<div class="log-warning" style="color: #dcdcaa;">   ⚠️ Índice já existe (ignorado)</div>';
                                            $sucesso++;
                                        } else {
                                            $erros++;
                                            echo '<div class="log-error" style="color: #f48771;">   ❌ Erro: ' . htmlspecialchars($erroMsg) . '</div>';
                                        }
                                    }
                                    
                                    // Delay maior para ANALYZE TABLE para garantir processamento completo
                                    $delay = stripos($comando, 'ANALYZE TABLE') !== false ? 200000 : 100000;
                                    usleep($delay);
                                    if (ob_get_level() > 0) ob_flush();
                                    flush();
                                }
                                
                                $tempo = round(microtime(true) - $inicio, 2);
                                
                                echo '<hr style="border-color: #555;">';
                                echo '<div class="log-success" style="color: #4ec9b0;"><strong>✅ Processo concluído!</strong></div>';
                                echo '<div class="log-info" style="color: #569cd6;">⏱️ Tempo: ' . $tempo . ' segundos</div>';
                                echo '<div class="log-success" style="color: #4ec9b0;">✅ Sucessos: ' . $sucesso . '</div>';
                                if ($erros > 0) {
                                    echo '<div class="log-error" style="color: #f48771;">❌ Erros: ' . $erros . '</div>';
                                }
                                
                            } catch (Exception $e) {
                                echo '<div class="log-error" style="color: #f48771;">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>

                        <div class="mt-4">
                            <a href="index.php?page=aplicar-indices" class="btn btn-primary">🔄 Executar Outro Script</a>
                            <a href="index.php" class="btn btn-secondary">🏠 Voltar ao Dashboard</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const logBox = document.getElementById('logBox');
    if (logBox) {
        logBox.scrollTop = logBox.scrollHeight;
    }
</script>

