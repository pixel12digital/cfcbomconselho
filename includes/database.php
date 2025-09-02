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
    
    private function __construct() {
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
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_TIMEOUT => DB_TIMEOUT,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_LOCAL_INFILE => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (LOG_ENABLED && LOG_LEVEL === 'DEBUG') {
                error_log('Conexão com banco de dados estabelecida com sucesso');
            }
            
        } catch (PDOException $e) {
            $this->logError('Erro na conexão com banco de dados: ' . $e->getMessage());
            throw new Exception('Erro na conexão com banco de dados');
        }
    }
    
    private function reconnect() {
        try {
            // Fechar conexão atual se existir
            $this->connection = null;
            
            // Aguardar um pouco antes de reconectar
            usleep(100000); // 100ms
            
            // Reconectar
            $this->connect();
            
            if (LOG_ENABLED) {
                error_log('Reconexão com banco de dados estabelecida com sucesso');
            }
            
        } catch (Exception $e) {
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
                throw new Exception('Erro na preparação da query');
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
                        throw new Exception('Erro na preparação da query após reconexão');
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
                    throw new Exception('Erro na execução da query após reconexão');
                }
            }
            
            $this->logError('Erro na execução da query: ' . $e->getMessage());
            $this->logError('SQL: ' . $sql);
            $this->logError('Parâmetros: ' . json_encode($params));
            throw new Exception('Erro na execução da query');
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
        $this->connection = null;
        self::$instance = null;
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
            $whereCounter = 0;
            foreach ($whereParams as $param) {
                $whereParamName = "where_{$whereCounter}";
                $whereSql = str_replace('?', ":{$whereParamName}", $whereSql);
                $whereParamsNamed[$whereParamName] = $param;
                $whereCounter++;
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
