<?php
/**
 * Serviço de Configurações SMTP
 * 
 * Gerencia configurações SMTP do banco de dados com criptografia de senha
 */

class SMTPConfigService {
    
    /**
     * Obter configurações SMTP ativas do banco
     * 
     * @return array|null Configurações descriptografadas ou null se não configurado
     */
    public static function getConfig() {
        try {
            $db = db();
            
            $config = $db->fetch(
                "SELECT id, host, port, user, pass_encrypted, encryption_mode, 
                        from_name, from_email, enabled, last_test_at, last_test_status, last_test_message
                 FROM smtp_settings 
                 WHERE enabled = 1 
                 ORDER BY updated_at DESC 
                 LIMIT 1"
            );
            
            if (!$config) {
                return null;
            }
            
            // Descriptografar senha
            $decryptedPass = self::decryptPassword($config['pass_encrypted']);
            $config['pass'] = $decryptedPass;
            unset($config['pass_encrypted']); // Não expor hash
            
            // Verificar se a descriptografia funcionou
            if (empty($config['pass'])) {
                if (LOG_ENABLED) {
                    error_log('[SMTP_CONFIG] AVISO: Senha descriptografada está vazia - possível erro na descriptografia ou senha não foi criptografada corretamente');
                }
                // Retornar null para forçar fallback ou indicar configuração inválida
                return null;
            }
            
            return $config;
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[SMTP_CONFIG] Erro ao obter configurações: ' . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Salvar configurações SMTP
     * 
     * @param array $data Dados da configuração
     * @param int $userId ID do usuário que está salvando
     * @return array ['success' => bool, 'message' => string]
     */
    public static function saveConfig($data, $userId = null) {
        try {
            $db = db();
            
            // Validações
            $errors = self::validateConfig($data);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'errors' => $errors
                ];
            }
            
            // Criptografar senha (se fornecida nova)
            $passEncrypted = null;
            if (!empty($data['pass'])) {
                // Nova senha fornecida
                $passEncrypted = self::encryptPassword($data['pass']);
            } else {
                // Manter senha atual se não fornecida
                $existing = $db->fetch("SELECT pass_encrypted FROM smtp_settings WHERE enabled = 1 ORDER BY updated_at DESC LIMIT 1");
                if ($existing) {
                    $passEncrypted = $existing['pass_encrypted'];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Senha SMTP é obrigatória na primeira configuração'
                    ];
                }
            }
            
            // Desabilitar outras configurações (mantém apenas uma ativa)
            // Usar WHERE 1=1 para desabilitar todas, pois vamos habilitar apenas a nova
            $db->update('smtp_settings', ['enabled' => 0], '1=1');
            
            // Salvar nova configuração
            $configData = [
                'host' => trim($data['host']),
                'port' => (int)$data['port'],
                'user' => trim($data['user']),
                'pass_encrypted' => $passEncrypted,
                'encryption_mode' => $data['encryption_mode'] ?? 'tls',
                'from_name' => !empty($data['from_name']) ? trim($data['from_name']) : null,
                'from_email' => !empty($data['from_email']) ? trim($data['from_email']) : null,
                'enabled' => 1,
                'updated_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->insert('smtp_settings', $configData);
            
            if (LOG_ENABLED) {
                error_log(sprintf(
                    '[SMTP_CONFIG] Configurações SMTP atualizadas - Host: %s, User: %s, Updated by: %d',
                    $configData['host'],
                    $configData['user'],
                    $userId ?? 0
                ));
            }
            
            return [
                'success' => true,
                'message' => 'Configurações SMTP salvas com sucesso!'
            ];
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[SMTP_CONFIG] Erro ao salvar configurações: ' . $e->getMessage());
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Testar configuração SMTP
     * 
     * @param string $testEmail E-mail para enviar teste
     * @param int $userId ID do usuário que está testando
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testConfig($testEmail, $userId = null) {
        try {
            $config = self::getConfig();
            
            if (!$config) {
                return [
                    'success' => false,
                    'message' => 'Configurações SMTP não encontradas. Configure primeiro.'
                ];
            }
            
            // Validar e-mail
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'E-mail de teste inválido.'
                ];
            }
            
            // Tentar enviar e-mail de teste
            require_once __DIR__ . '/Mailer.php';
            
            $testToken = bin2hex(random_bytes(16)); // Token fake apenas para teste
            $result = Mailer::sendPasswordResetEmail($testEmail, $testToken, 'admin');
            
            // Atualizar status do teste
            $db = db();
            $updateData = [
                'last_test_at' => date('Y-m-d H:i:s'),
                'last_test_status' => $result['success'] ? 'ok' : 'error',
                'last_test_message' => substr($result['message'] ?? 'Teste realizado', 0, 500)
            ];
            
            // Atualizar última configuração ativa
            $db->update('smtp_settings', $updateData, 'enabled = 1');
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'E-mail de teste enviado com sucesso para ' . $testEmail . '. Verifique sua caixa de entrada.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Erro ao enviar e-mail de teste: ' . ($result['message'] ?? 'Erro desconhecido')
                ];
            }
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[SMTP_CONFIG] Erro ao testar configurações: ' . $e->getMessage());
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao testar configurações: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar se SMTP está configurado e ativo
     * 
     * @return bool
     */
    public static function isConfigured() {
        $config = self::getConfig();
        return $config !== null && !empty($config['host']) && !empty($config['user']) && !empty($config['pass']);
    }
    
    /**
     * Obter status da configuração (para exibição no painel)
     * 
     * @return array
     */
    public static function getStatus() {
        $config = self::getConfig();
        
        if (!$config) {
            return [
                'configured' => false,
                'status' => 'incompleto',
                'message' => 'SMTP não configurado'
            ];
        }
        
        $status = [
            'configured' => true,
            'status' => 'configurado',
            'message' => 'SMTP configurado',
            'host' => $config['host'],
            'user' => $config['user'],
            'last_test_at' => $config['last_test_at'],
            'last_test_status' => $config['last_test_status'],
            'last_test_message' => $config['last_test_message']
        ];
        
        if ($config['last_test_status'] === 'error') {
            $status['status'] = 'error';
            $status['message'] = 'SMTP configurado mas teste falhou';
        }
        
        return $status;
    }
    
    /**
     * Validar dados de configuração
     * 
     * @param array $data
     * @return array Lista de erros (vazia se válido)
     */
    private static function validateConfig($data) {
        $errors = [];
        
        // Host obrigatório
        if (empty($data['host'])) {
            $errors[] = 'Host SMTP é obrigatório';
        }
        
        // Porta obrigatória e válida
        if (empty($data['port']) || !is_numeric($data['port']) || $data['port'] < 1 || $data['port'] > 65535) {
            $errors[] = 'Porta SMTP deve ser um número entre 1 e 65535';
        }
        
        // User obrigatório e e-mail válido
        if (empty($data['user'])) {
            $errors[] = 'Usuário/e-mail SMTP é obrigatório';
        } elseif (!filter_var($data['user'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Usuário deve ser um e-mail válido';
        }
        
        // Senha obrigatória apenas se não houver configuração existente
        $existing = db()->fetch("SELECT id FROM smtp_settings WHERE enabled = 1 ORDER BY updated_at DESC LIMIT 1");
        if (empty($data['pass']) && !$existing) {
            $errors[] = 'Senha SMTP é obrigatória na primeira configuração';
        }
        
        // From email (se fornecido) deve ser válido
        if (!empty($data['from_email']) && !filter_var($data['from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail "from" deve ser válido';
        }
        
        return $errors;
    }
    
    /**
     * Criptografar senha SMTP
     * 
     * @param string $password
     * @return string Senha criptografada
     */
    private static function encryptPassword($password) {
        // Usar chave do config.php ou gerar uma específica
        $key = defined('JWT_SECRET') ? JWT_SECRET : 'smtp_secret_key_' . (defined('DB_HOST') ? DB_HOST : 'default');
        
        // Usar openssl para criptografia AES-256-CBC
        $cipher = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($password, $cipher, $key, 0, $iv);
        
        // Concatenar IV com dados criptografados e codificar em base64
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Descriptografar senha SMTP
     * 
     * @param string $encryptedPassword
     * @return string Senha descriptografada
     */
    private static function decryptPassword($encryptedPassword) {
        try {
            $key = defined('JWT_SECRET') ? JWT_SECRET : 'smtp_secret_key_' . (defined('DB_HOST') ? DB_HOST : 'default');
            
            $data = base64_decode($encryptedPassword);
            $cipher = 'AES-256-CBC';
            $ivLength = openssl_cipher_iv_length($cipher);
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[SMTP_CONFIG] Erro ao descriptografar senha: ' . $e->getMessage());
            }
            return '';
        }
    }
}
