<?php
// =====================================================
// CONEXÃO COM BANCO DE DADOS MYSQL
// =====================================================

require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    private $lastQuery;
    private $queryCount = 0;
    private $queryTime = 0;
    private static $requestId = null;
    private static $requestStartTime = null;
    
    private function __construct() {
        // Gerar request_id único se não existir
        if (self::$requestId === null) {
            self::$requestId = uniqid('req_', true);
            self::$requestStartTime = microtime(true);
        }
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // Configurações específicas para conexão remota
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            // Configurar timezone do MySQL para UTC para evitar problemas de expiração
            $initCommands = "SET NAMES " . DB_CHARSET . "; SET time_zone = '+00:00';";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => $initCommands,
                PDO::ATTR_PERSISTENT => false, // Desabilitado para conexão remota
                PDO::ATTR_TIMEOUT => 30, // Timeout maior para conexão remota
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_LOCAL_INFILE => false
            ];
            
            if (LOG_ENABLED && LOG_LEVEL === 'DEBUG') {
                error_log('Tentando conectar: ' . $dsn . ' | Usuário: ' . DB_USER);
            }
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (LOG_ENABLED) {
                error_log('✅ Conexão com banco de dados estabelecida com sucesso');
                error_log('📋 DSN: ' . $dsn);
                error_log('👤 Usuário: ' . DB_USER);
            }
            
        } catch (PDOException $e) {
            $errorInfo = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'dsn' => $dsn,
                'user' => DB_USER,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->logError('❌ Erro na conexão com banco de dados:');
            $this->logError('📋 Detalhes: ' . json_encode($errorInfo, JSON_PRETTY_PRINT));
            
            // Melhor tratamento de erros específicos
            if ($e->getCode() == 2002) {
                throw new Exception('🏠 Host ' . DB_HOST . ' não encontrado. Verifique se o acesso remoto está liberado na Hostinger.');
            } elseif ($e->getCode() == 1045) {
                throw new Exception('🔐 Credenciais inválidas (usuário/senha). Verifique nas configurações da Hostinger.');
            } elseif ($e->getCode() == 1049) {
                throw new Exception('📁 Banco de dados "' . DB_NAME . '" não existe. Crie o banco na Hostinger.');
            } elseif ($e->getCode() == 2006) {
                throw new Exception('🔌 Conexão perdida com o servidor MySQL. Tente novamente.');
            } else {
                throw new Exception('🚫 Erro desconhecido: ' . $e->getMessage() . ' (Código: ' . $e->getCode() . ')');
            }
        }
    }
    
    private function reconnect() {
        try {
            // Fechar conexão atual se existir
            $this->connection = null;
            
            // Aguardar um pouco antes de reconectar
            usleep(100000); // 100ms
            
            // Log de tentativa de reconexão
            $this->logConnection('reconnect', null);
            
            // Reconectar
            $this->connect();
            
            if (LOG_ENABLED) {
                error_log('Reconexão com banco de dados estabelecida com sucesso');
            }
            
        } catch (Exception $e) {
            $this->logConnection('reconnect_error', $e->getMessage());
            $this->logError('Erro na reconexão com banco de dados: ' . $e->getMessage());
            throw new Exception('Erro na reconexão com banco de dados');
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        
        try {
            $this->lastQuery = $sql;
            $this->statement = $this->connection->prepare($sql);
            
            if (!$this->statement) {
                $errorInfo = $this->connection->errorInfo();
                $pdoMessage = $errorInfo && !empty($errorInfo[2]) ? $errorInfo[2] : 'Falha na preparação';
                throw new Exception('Erro na preparação da query: ' . $pdoMessage . ' | SQL: ' . $sql);
            }
            
            $this->statement->execute($params);
            $this->queryCount++;
            
            $endTime = microtime(true);
            $this->queryTime += ($endTime - $startTime);
            
            if (LOG_ENABLED && LOG_LEVEL === 'DEBUG') {
                $this->logQuery($sql, $params, ($endTime - $startTime));
            }
            
            return $this->statement;
            
        } catch (PDOException $e) {
            // Verificar se é erro de conexão perdida
            if ($e->getCode() == 2006 || strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
                $this->logError('Conexão perdida, tentando reconectar...');
                $this->reconnect();
                
                // Tentar executar a query novamente
                try {
                    $this->statement = $this->connection->prepare($sql);
                    if (!$this->statement) {
                        $errorInfo = $this->connection->errorInfo();
                        $pdoMessage = $errorInfo && !empty($errorInfo[2]) ? $errorInfo[2] : 'Falha na preparação';
                        throw new Exception('Erro na preparação da query após reconexão: ' . $pdoMessage . ' | SQL: ' . $sql);
                    }
                    
                    $this->statement->execute($params);
                    $this->queryCount++;
                    
                    $endTime = microtime(true);
                    $this->queryTime += ($endTime - $startTime);
                    
                    if (LOG_ENABLED && LOG_LEVEL === 'DEBUG') {
                        $this->logQuery($sql, $params, ($endTime - $startTime));
                    }
                    
                    return $this->statement;
                    
                } catch (PDOException $e2) {
                    $this->logError('Erro na execução da query após reconexão: ' . $e2->getMessage());
                    $this->logError('SQL: ' . $sql);
                    $this->logError('Parâmetros: ' . json_encode($params));
                    
                    // Obter errorInfo do PDO para detalhes completos
                    $errorInfo = $this->statement ? $this->statement->errorInfo() : null;
                    $pdoMessage = $e2->getMessage();
                    if ($errorInfo && !empty($errorInfo[2])) {
                        $pdoMessage = $errorInfo[2];
                    }
                    
                    // Montar mensagem detalhada
                    $msg = 'Erro na execução da query após reconexão';
                    if ($pdoMessage) {
                        $msg .= ': ' . $pdoMessage;
                    }
                    $msg .= ' | SQL: ' . $sql;
                    $msg .= ' | PARAMS: ' . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    
                    throw new Exception($msg);
                }
            }
            
            $this->logError('Erro na execução da query: ' . $e->getMessage());
            $this->logError('SQL: ' . $sql);
            $this->logError('Parâmetros: ' . json_encode($params));
            
            // Obter errorInfo do PDO para detalhes completos
            $errorInfo = $this->statement ? $this->statement->errorInfo() : null;
            $pdoMessage = $e->getMessage();
            if ($errorInfo && !empty($errorInfo[2])) {
                $pdoMessage = $errorInfo[2];
            }
            
            // Montar mensagem detalhada
            $msg = 'Erro na execução da query';
            if ($pdoMessage) {
                $msg .= ': ' . $pdoMessage;
            }
            $msg .= ' | SQL: ' . $sql;
            $msg .= ' | PARAMS: ' . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            throw new Exception($msg);
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchColumn($sql, $params = [], $column = 0) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
    
    public function quote($value) {
        return $this->connection->quote($value);
    }
    
    public function getQueryCount() {
        return $this->queryCount;
    }
    
    public function getQueryTime() {
        return $this->queryTime;
    }
    
    public function getLastQuery() {
        return $this->lastQuery;
    }
    
    public function close() {
        $this->logConnection('close', null);
        $this->connection = null;
        self::$instance = null;
    }
    
    /**
     * Log de conexões ao banco de dados
     * Formato JSON Lines para facilitar análise
     */
    private function logConnection($event, $error = null) {
        try {
            // Criar diretório de logs se não existir
            $logDir = __DIR__ . '/../storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
                // Criar .htaccess para proteger logs
                $htaccess = $logDir . '/.htaccess';
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, "Deny from all\n");
                }
            }
            
            $logFile = $logDir . '/db_connections.jsonl';
            
            // Rotação de log se passar de 10MB
            if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
                $backupFile = $logDir . '/db_connections_' . date('Ymd_His') . '.jsonl';
                @rename($logFile, $backupFile);
                
                // Manter apenas últimos 10 arquivos
                $files = glob($logDir . '/db_connections_*.jsonl');
                if (count($files) > 10) {
                    usort($files, function($a, $b) {
                        return filemtime($a) - filemtime($b);
                    });
                    foreach (array_slice($files, 0, count($files) - 10) as $oldFile) {
                        @unlink($oldFile);
                    }
                }
            }
            
            // Coletar informações do request
            $requestTime = self::$requestStartTime ? round((microtime(true) - self::$requestStartTime) * 1000, 2) : 0;
            
            // Obter usuário logado (se disponível, sem quebrar se não existir)
            $userId = null;
            $userEmail = null;
            try {
                if (session_status() === PHP_SESSION_ACTIVE || @session_start()) {
                    $userId = $_SESSION['user_id'] ?? null;
                    $userEmail = $_SESSION['user_email'] ?? null;
                }
            } catch (Exception $e) {
                // Ignorar erros de sessão
            }
            
            // Resumir User-Agent (primeiros 100 chars)
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $userAgentShort = strlen($userAgent) > 100 ? substr($userAgent, 0, 100) . '...' : $userAgent;
            
            // Montar log entry
            $logEntry = [
                'timestamp' => date('c'), // ISO 8601
                'request_id' => self::$requestId,
                'event' => $event, // connect, reconnect, pdo_exception, close
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'http_referer' => $_SERVER['HTTP_REFERER'] ?? null,
                'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $userAgentShort,
                'user_id' => $userId,
                'user_email' => $userEmail ? substr($userEmail, 0, 50) : null, // Limitar tamanho
                'request_time_ms' => $requestTime,
                'error' => $error
            ];
            
            // Escrever log (append mode)
            $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // Não quebrar o sistema se logging falhar
            // Logar em error_log como fallback
            @error_log('[DB Log Error] ' . $e->getMessage());
        }
    }
    
    private function logQuery($sql, $params, $time) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'sql' => $sql,
            'params' => $params,
            'time' => number_format($time, 4) . 's'
        ];
        
        error_log('Query executada: ' . json_encode($log));
    }
    
    private function logError($message) {
        if (LOG_ENABLED) {
            error_log('[DATABASE ERROR] ' . $message);
        }
    }
    
    // Métodos de conveniência para operações comuns
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $newParams = [];
        $counter = 0;
        
        foreach (array_keys($data) as $field) {
            $paramName = "set_{$counter}";
            $setParts[] = "{$field} = :{$paramName}";
            $newParams[$paramName] = $data[$field];
            $counter++;
        }
        
        // Converter whereParams para parâmetros nomeados se necessário
        $whereParamsNamed = [];
        $whereSql = $where;
        
        if (!empty($whereParams)) {
            // Verificar se WHERE já tem parâmetros nomeados (ex: "id = :id")
            $hasNamedParams = preg_match('/:\w+/', $where);
            
            if ($hasNamedParams) {
                // WHERE já tem parâmetros nomeados, usar diretamente
                $whereParamsNamed = $whereParams;
            } else {
                // WHERE usa placeholders ?, converter para nomeados
                $whereCounter = 0;
                foreach ($whereParams as $param) {
                    $whereParamName = "where_{$whereCounter}";
                    $whereSql = str_replace('?', ":{$whereParamName}", $whereSql);
                    $whereParamsNamed[$whereParamName] = $param;
                    $whereCounter++;
                }
            }
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$whereSql}";
        $params = array_merge($newParams, $whereParamsNamed);
        
        return $this->query($sql, $params);
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }
    
    public function findById($table, $id, $fields = '*') {
        $sql = "SELECT {$fields} FROM {$table} WHERE id = :id LIMIT 1";
        return $this->fetch($sql, ['id' => $id]);
    }
    
    public function findAll($table, $fields = '*', $orderBy = null, $limit = null) {
        $sql = "SELECT {$fields} FROM {$table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->fetchAll($sql);
    }
    
    public function findWhere($table, $where, $params = [], $fields = '*', $orderBy = null, $limit = null) {
        $sql = "SELECT {$fields} FROM {$table} WHERE {$where}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->fetchAll($sql, $params);
    }
    
    public function count($table, $where = null, $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        return $this->fetchColumn($sql, $params);
    }
    
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    // Métodos para paginação
    public function paginate($table, $page = 1, $perPage = ITEMS_PER_PAGE, $where = null, $params = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        // Contar total de registros
        $total = $this->count($table, $where, $params);
        
        // Buscar registros da página
        $sql = "SELECT * FROM {$table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    // Métodos para backup
    public function backup($tables = null) {
        if (!BACKUP_ENABLED) {
            return false;
        }
        
        try {
            if ($tables === null) {
                $tables = $this->getTables();
            }
            
            $backup = '';
            
            foreach ($tables as $table) {
                $backup .= $this->backupTable($table);
            }
            
            $backupDir = __DIR__ . '/../backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . $filename;
            
            file_put_contents($filepath, $backup);
            
            if (LOG_ENABLED) {
                error_log('Backup criado com sucesso: ' . $filepath);
            }
            
            return $filepath;
            
        } catch (Exception $e) {
            $this->logError('Erro ao criar backup: ' . $e->getMessage());
            return false;
        }
    }
    
    private function backupTable($table) {
        $backup = "\n-- Estrutura da tabela {$table}\n";
        $backup .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $createTable = $this->fetch("SHOW CREATE TABLE {$table}");
        $backup .= $createTable['Create Table'] . ";\n\n";
        
        $backup .= "-- Dados da tabela {$table}\n";
        $rows = $this->fetchAll("SELECT * FROM {$table}");
        
        if (!empty($rows)) {
            $fields = array_keys($rows[0]);
            $backup .= "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = $this->quote($value);
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            $backup .= implode(",\n", $values) . ";\n";
        }
        
        return $backup;
    }
    
    private function getTables() {
        $tables = [];
        $result = $this->fetchAll("SHOW TABLES");
        
        foreach ($result as $row) {
            $tables[] = array_values($row)[0];
        }
        
        return $tables;
    }
    
    // Métodos para cache
    public function getCache($key) {
        if (!CACHE_ENABLED) {
            return false;
        }
        
        $cacheKey = DB_CACHE_PREFIX . $key;
        $sql = "SELECT dados, expira_em FROM cache WHERE chave = :chave AND expira_em > NOW()";
        $result = $this->fetch($sql, ['chave' => $cacheKey]);
        
        if ($result) {
            return json_decode($result['dados'], true);
        }
        
        return false;
    }
    
    public function setCache($key, $data, $duration = null) {
        if (!CACHE_ENABLED) {
            return false;
        }
        
        if ($duration === null) {
            $duration = DB_CACHE_DURATION;
        }
        
        $cacheKey = DB_CACHE_PREFIX . $key;
        $expiraEm = date('Y-m-d H:i:s', time() + $duration);
        
        $sql = "INSERT INTO cache (chave, dados, expira_em) VALUES (:chave, :dados, :expira_em) 
                ON DUPLICATE KEY UPDATE dados = :dados, expira_em = :expira_em";
        
        return $this->query($sql, [
            'chave' => $cacheKey,
            'dados' => json_encode($data),
            'expira_em' => $expiraEm
        ]);
    }
    
    public function deleteCache($key) {
        if (!CACHE_ENABLED) {
            return false;
        }
        
        $cacheKey = DB_CACHE_PREFIX . $key;
        $sql = "DELETE FROM cache WHERE chave = :chave";
        
        return $this->query($sql, ['chave' => $cacheKey]);
    }
    
    public function clearCache() {
        if (!CACHE_ENABLED) {
            return false;
        }
        
        $sql = "DELETE FROM cache WHERE expira_em <= NOW()";
        return $this->query($sql);
    }
    
    // Métodos para logs
    public function log($usuarioId, $acao, $tabelaAfetada = null, $registroId = null, $dadosAnteriores = null, $dadosNovos = null) {
        if (!AUDIT_ENABLED) {
            return false;
        }
        
        $sql = "INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, ip_address) 
                VALUES (:usuario_id, :acao, :tabela_afetada, :registro_id, :dados_anteriores, :dados_novos, :ip_address)";
        
        return $this->query($sql, [
            'usuario_id' => $usuarioId,
            'acao' => $acao,
            'tabela_afetada' => $tabelaAfetada,
            'registro_id' => $registroId,
            'dados_anteriores' => $dadosAnteriores ? json_encode($dadosAnteriores) : null,
            'dados_novos' => $dadosNovos ? json_encode($dadosNovos) : null,
            'ip_address' => $this->getClientIP()
        ]);
    }
    
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Métodos para validação
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function validateCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    public function validateCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }
        
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        
        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
    
    // Métodos para sanitização
    public function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
        } else {
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    public function escape($string) {
        return $this->connection->quote($string);
    }
    
    // Métodos para estatísticas
    public function getStats() {
        return [
            'total_queries' => $this->queryCount,
            'total_time' => number_format($this->queryTime, 4) . 's',
            'average_time' => $this->queryCount > 0 ? number_format($this->queryTime / $this->queryCount, 4) . 's' : '0s',
            'connection_status' => $this->connection ? 'Connected' : 'Disconnected'
        ];
    }
    
    // Método para limpeza de logs antigos
    public function cleanupLogs() {
        if (!AUDIT_ENABLED) {
            return false;
        }
        
        $retentionDays = AUDIT_RETENTION_DAYS;
        $sql = "DELETE FROM logs WHERE criado_em < DATE_SUB(NOW(), INTERVAL {$retentionDays} DAY)";
        
        return $this->query($sql);
    }
    
    // Método para otimização de tabelas
    public function optimizeTables() {
        $tables = $this->getTables();
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $sql = "OPTIMIZE TABLE {$table}";
                $this->query($sql);
                $results[$table] = 'success';
            } catch (Exception $e) {
                $results[$table] = 'error: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    public function getLastError() {
        if ($this->statement) {
            $errorInfo = $this->statement->errorInfo();
            return $errorInfo[2] ?? 'Erro desconhecido';
        }
        return 'Nenhum erro disponível';
    }
}

// Função global para obter instância do banco
function db() {
    return Database::getInstance();
}

// Função global para executar queries
function query($sql, $params = []) {
    return db()->query($sql, $params);
}

// Função global para buscar um registro
function fetch($sql, $params = []) {
    return db()->fetch($sql, $params);
}

// Função global para buscar todos os registros
function fetchAll($sql, $params = []) {
    return db()->fetchAll($sql, $params);
}

// Função global para contar registros
function dbCount($sql, $params = []) {
    return db()->count($sql, $params);
}

// Função global para inserir
function insert($table, $data) {
    return db()->insert($table, $data);
}

// Função global para atualizar
function update($table, $data, $where, $whereParams = []) {
    return db()->update($table, $data, $where, $whereParams);
}

// Função global para deletar
function delete($table, $where, $params = []) {
    return db()->delete($table, $where, $params);
}

// Função global para buscar por ID
function findById($table, $id, $fields = '*') {
    return db()->findById($table, $id, $fields);
}

// Função global para buscar todos
function findAll($table, $fields = '*', $orderBy = null, $limit = null) {
    return db()->findAll($table, $fields, $orderBy, $limit);
}

// Função global para buscar com condição
function findWhere($table, $where, $params = [], $fields = '*', $orderBy = null, $limit = null) {
    return db()->findWhere($table, $where, $params, $fields, $orderBy, $limit);
}

// Função global para verificar existência
function exists($table, $where, $params = []) {
    return db()->exists($table, $where, $params);
}

// Função global para paginação
function paginate($table, $page = 1, $perPage = ITEMS_PER_PAGE, $where = null, $params = [], $orderBy = null) {
    return db()->paginate($table, $page, $perPage, $where, $params, $orderBy);
}

// Função global para log
function dbLog($usuarioId, $acao, $tabelaAfetada = null, $registroId = null, $dadosAnteriores = null, $dadosNovos = null) {
    return db()->log($usuarioId, $acao, $tabelaAfetada, $registroId, $dadosAnteriores, $dadosNovos);
}

// Função global para cache
function getCache($key) {
    return db()->getCache($key);
}

function setCache($key, $data, $duration = null) {
    return db()->setCache($key, $data, $duration);
}

function deleteCache($key) {
    return db()->deleteCache($key);
}

// Função global para backup
function backup($tables = null) {
    return db()->backup($tables);
}

// Função global para estatísticas
function getDbStats() {
    return db()->getStats();
}

?>
