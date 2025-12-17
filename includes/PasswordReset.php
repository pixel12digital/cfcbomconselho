<?php
/**
 * Sistema de Recuperação de Senha - Sistema CFC
 * 
 * Gerencia tokens de recuperação de senha com segurança:
 * - Tokens com hash SHA256 (nunca armazenados em texto puro)
 * - Expiração de 30 minutos
 * - Uso único (one-time)
 * - Rate limiting (1 solicitação a cada 5 minutos por login+ip)
 * - Proteção anti-enumeração (não revela se usuário existe)
 */

class PasswordReset {
    
    /**
     * Solicitar recuperação de senha
     * 
     * @param string $login Email ou CPF do usuário
     * @param string $type Tipo do usuário (admin/secretaria/instrutor/aluno)
     * @param string $ip Endereço IP do solicitante
     * @return array ['success' => bool, 'message' => string, 'token' => string|null]
     */
    public static function requestReset($login, $type, $ip) {
        try {
            $db = db();
            
            // Rate limiting: verificar última solicitação nos últimos 5 minutos
            $rateLimitResult = self::checkRateLimit($login, $ip, $db);
            if (!$rateLimitResult['allowed']) {
                // Retornar sucesso mesmo assim (proteção anti-enumeração)
                // Mas não gerar token se já solicitou recentemente
                return [
                    'success' => true,
                    'message' => 'Se o dado informado existir em nossa base, você receberá instruções para redefinir sua senha.',
                    'token' => null,
                    'rate_limited' => true
                ];
            }
            
            // Buscar usuário (sem revelar se existe ou não)
            $usuario = self::findUserByLogin($login, $type, $db);
            
            if (!$usuario) {
                // Proteção anti-enumeração: retornar mesma mensagem
                // Não revelar que usuário não existe
                return [
                    'success' => true,
                    'message' => 'Se o dado informado existir em nossa base, você receberá instruções para redefinir sua senha.',
                    'token' => null,
                    'user_not_found' => true
                ];
            }
            
            // Gerar token único (32 bytes, hex)
            $token = bin2hex(random_bytes(32)); // 64 caracteres hex
            $tokenHash = hash('sha256', $token); // Hash SHA256 para armazenar
            
            // Expiração: 30 minutos
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 60));
            
            // Invalidar tokens anteriores do mesmo login (não usados)
            try {
                $db->query(
                    "UPDATE password_resets SET used_at = NOW() WHERE login = :login AND used_at IS NULL",
                    ['login' => $login]
                );
            } catch (Exception $e) {
                // Log mas continuar (pode ser primeira solicitação)
                if (LOG_ENABLED) {
                    error_log('[PASSWORD_RESET] Aviso ao invalidar tokens anteriores: ' . $e->getMessage());
                }
            }
            
            // Salvar token no banco
            $resetData = [
                'login' => $login,
                'token_hash' => $tokenHash,
                'type' => $type,
                'ip' => $ip,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $resetId = $db->insert('password_resets', $resetData);
            
            if ($resetId) {
                // Log de auditoria (sem dados sensíveis)
                if (LOG_ENABLED) {
                    $auditLog = sprintf(
                        '[PASSWORD_RESET_REQUEST] login=%s, type=%s, ip=%s, reset_id=%d, timestamp=%s',
                        $login,
                        $type,
                        $ip,
                        $resetId,
                        date('Y-m-d H:i:s')
                    );
                    error_log($auditLog);
                }
                
                return [
                    'success' => true,
                    'message' => 'Se o dado informado existir em nossa base, você receberá instruções para redefinir sua senha.',
                    'token' => $token, // Retornar token apenas para montar link no email
                    'user_id' => $usuario['id'],
                    'user_email' => $usuario['email'] ?? null
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao processar solicitação. Tente novamente mais tarde.'
            ];
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[PASSWORD_RESET] Erro ao solicitar reset: ' . $e->getMessage());
            }
            
            // Em caso de erro, retornar mensagem neutra
            return [
                'success' => true, // Retornar sucesso mesmo em erro (anti-enumeração)
                'message' => 'Se o dado informado existir em nossa base, você receberá instruções para redefinir sua senha.',
                'token' => null
            ];
        }
    }
    
    /**
     * Validar token de recuperação
     * 
     * @param string $token Token em texto puro
     * @return array ['valid' => bool, 'reset_id' => int|null, 'login' => string|null, 'type' => string|null]
     */
    public static function validateToken($token) {
        try {
            $db = db();
            
            // Hash do token para buscar no banco
            $tokenHash = hash('sha256', $token);
            
            // Buscar token (não expirado e não usado)
            $reset = $db->fetch(
                "SELECT id, login, type, expires_at, used_at FROM password_resets 
                 WHERE token_hash = :token_hash AND expires_at > NOW() AND used_at IS NULL 
                 LIMIT 1",
                ['token_hash' => $tokenHash]
            );
            
            if (!$reset) {
                return [
                    'valid' => false,
                    'reset_id' => null,
                    'login' => null,
                    'type' => null,
                    'reason' => 'Token inválido, expirado ou já utilizado'
                ];
            }
            
            return [
                'valid' => true,
                'reset_id' => $reset['id'],
                'login' => $reset['login'],
                'type' => $reset['type']
            ];
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[PASSWORD_RESET] Erro ao validar token: ' . $e->getMessage());
            }
            
            return [
                'valid' => false,
                'reset_id' => null,
                'login' => null,
                'type' => null,
                'reason' => 'Erro ao validar token'
            ];
        }
    }
    
    /**
     * Consumir token e definir nova senha
     * 
     * @param string $token Token em texto puro
     * @param string $newPassword Nova senha (em texto puro, será hasheada)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function consumeTokenAndSetPassword($token, $newPassword) {
        try {
            $db = db();
            
            // Validar token primeiro
            $validation = self::validateToken($token);
            
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Link inválido ou expirado. Solicite uma nova recuperação de senha.'
                ];
            }
            
            // Validar força da senha
            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'A senha deve ter no mínimo 8 caracteres.'
                ];
            }
            
            // Buscar usuário
            $login = $validation['login'];
            $usuario = self::findUserByLogin($login, $validation['type'], $db);
            
            if (!$usuario) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado.'
                ];
            }
            
            // Hash da nova senha
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Atualizar senha na tabela usuarios
            try {
                $db->update('usuarios', ['senha' => $passwordHash], 'id = :id', ['id' => $usuario['id']]);
                $updateSuccess = true;
            } catch (Exception $e) {
                $updateSuccess = false;
                if (LOG_ENABLED) {
                    error_log('[PASSWORD_RESET] Erro ao atualizar senha: ' . $e->getMessage());
                }
            }
            
            // Se for aluno, também atualizar na tabela alunos (sincronização)
            if ($usuario['tipo'] === 'aluno' && !empty($usuario['cpf'])) {
                try {
                    $alunoNaTabelaAlunos = $db->fetch(
                        "SELECT id FROM alunos WHERE cpf = :cpf",
                        ['cpf' => $usuario['cpf']]
                    );
                    
                    if ($alunoNaTabelaAlunos) {
                        $db->update('alunos', ['senha' => $passwordHash], 'cpf = :cpf', ['cpf' => $usuario['cpf']]);
                    }
                } catch (Exception $e) {
                    // Não falhar a operação principal se houver erro na sincronização
                    if (LOG_ENABLED) {
                        error_log('[PASSWORD_RESET] Erro ao sincronizar senha na tabela alunos: ' . $e->getMessage());
                    }
                }
            }
            
            if (!$updateSuccess) {
                return [
                    'success' => false,
                    'message' => 'Erro ao atualizar senha. Tente novamente.'
                ];
            }
            
            // Marcar token como usado
            try {
                $db->update('password_resets', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $validation['reset_id']]);
            } catch (Exception $e) {
                // Log mas não falhar operação
                if (LOG_ENABLED) {
                    error_log('[PASSWORD_RESET] Erro ao marcar token como usado: ' . $e->getMessage());
                }
            }
            
            // Invalidar todos os outros tokens não usados do mesmo login
            try {
                $db->query(
                    "UPDATE password_resets SET used_at = NOW() 
                     WHERE login = :login AND used_at IS NULL AND id != :id",
                    ['login' => $login, 'id' => $validation['reset_id']]
                );
            } catch (Exception $e) {
                // Log mas não falhar operação
                if (LOG_ENABLED) {
                    error_log('[PASSWORD_RESET] Erro ao invalidar outros tokens: ' . $e->getMessage());
                }
            }
            
            // Log de auditoria
            if (LOG_ENABLED) {
                $auditLog = sprintf(
                    '[PASSWORD_RESET_COMPLETE] login=%s, type=%s, reset_id=%d, user_id=%d, timestamp=%s',
                    $login,
                    $validation['type'],
                    $validation['reset_id'],
                    $usuario['id'],
                    date('Y-m-d H:i:s')
                );
                error_log($auditLog);
            }
            
            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso. Você pode fazer login agora.'
            ];
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[PASSWORD_RESET] Erro ao consumir token: ' . $e->getMessage());
            }
            
            return [
                'success' => false,
                'message' => 'Erro ao atualizar senha. Tente novamente.'
            ];
        }
    }
    
    /**
     * Verificar rate limiting (1 solicitação a cada 5 minutos por login+ip)
     * 
     * @param string $login Email ou CPF
     * @param string $ip Endereço IP
     * @param object $db Instância do banco
     * @return array ['allowed' => bool]
     */
    private static function checkRateLimit($login, $ip, $db) {
        try {
            // Verificar se há solicitação nos últimos 5 minutos
            $recentRequest = $db->fetch(
                "SELECT id, created_at FROM password_resets 
                 WHERE login = :login AND ip = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 ORDER BY created_at DESC LIMIT 1",
                ['login' => $login, 'ip' => $ip]
            );
            
            if ($recentRequest) {
                if (LOG_ENABLED) {
                    error_log(sprintf(
                        '[PASSWORD_RESET] Rate limit atingido para login=%s, ip=%s (última solicitação: %s)',
                        $login,
                        $ip,
                        $recentRequest['created_at']
                    ));
                }
                
                return ['allowed' => false];
            }
            
            return ['allowed' => true];
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[PASSWORD_RESET] Erro ao verificar rate limit: ' . $e->getMessage());
            }
            
            // Em caso de erro, permitir (melhor UX)
            return ['allowed' => true];
        }
    }
    
    /**
     * Buscar usuário por login (email ou CPF) sem revelar se existe
     * 
     * @param string $login Email ou CPF
     * @param string $type Tipo do usuário
     * @param object $db Instância do banco
     * @return array|null Dados do usuário ou null
     */
    private static function findUserByLogin($login, $type, $db) {
        try {
            // Para aluno: buscar por CPF
            if ($type === 'aluno') {
                // Limpar CPF (remover pontos e traços)
                $cpfLimpo = preg_replace('/[^0-9]/', '', $login);
                
                // Buscar primeiro na tabela usuarios (por CPF limpo ou email)
                $usuario = $db->fetch(
                    "SELECT id, email, cpf, tipo FROM usuarios WHERE (cpf = :cpf OR email = :email) AND tipo = 'aluno' AND ativo = 1 LIMIT 1",
                    ['cpf' => $cpfLimpo, 'email' => $login]
                );
                
                // Se encontrou mas não tem CPF, tentar buscar CPF na tabela alunos pelo email
                if ($usuario && empty($usuario['cpf']) && !empty($usuario['email'])) {
                    $alunoComCPF = $db->fetch(
                        "SELECT cpf FROM alunos WHERE email = :email LIMIT 1",
                        ['email' => $usuario['email']]
                    );
                    if ($alunoComCPF && !empty($alunoComCPF['cpf'])) {
                        $usuario['cpf'] = $alunoComCPF['cpf'];
                    }
                }
                
                return $usuario;
            }
            
            // Para funcionários: buscar por email
            $usuario = $db->fetch(
                "SELECT id, email, tipo FROM usuarios WHERE email = :email AND tipo = :type AND ativo = 1 LIMIT 1",
                ['email' => $login, 'type' => $type]
            );
            
            return $usuario;
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[PASSWORD_RESET] Erro ao buscar usuário: ' . $e->getMessage());
            }
            
            return null;
        }
    }
}
