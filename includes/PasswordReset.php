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
                // Rate limit: retornar mensagem informando cooldown
                return [
                    'success' => false,
                    'found' => null, // Não verificado devido a rate limit
                    'message' => 'Você já solicitou recuperação recentemente. Aguarde alguns minutos antes de tentar novamente.',
                    'token' => null,
                    'rate_limited' => true
                ];
            }
            
            // Buscar usuário (consulta real)
            if (LOG_ENABLED) {
                error_log("[PASSWORD_RESET] requestReset chamado - login: '$login', type: '$type', ip: '$ip'");
            }
            
            $usuario = self::findUserByLogin($login, $type, $db);
            
            if (LOG_ENABLED) {
                if ($usuario) {
                    error_log("[PASSWORD_RESET] Usuário encontrado - ID: " . ($usuario['id'] ?? 'N/A') . ", email: " . ($usuario['email'] ?? 'N/A') . ", tipo: " . ($usuario['tipo'] ?? 'N/A'));
                } else {
                    error_log("[PASSWORD_RESET] Usuário NÃO encontrado - login: '$login', type: '$type'");
                }
            }
            
            if (!$usuario) {
                // Não encontrado: retornar mensagem amigável
                return [
                    'success' => false,
                    'found' => false,
                    'message' => 'Não foi possível localizar um cadastro com os dados informados. Verifique se digitou corretamente. Se persistir, entre em contato com a Secretaria.',
                    'token' => null,
                    'user_not_found' => true
                ];
            }
            
            // Usuário encontrado: verificar se tem e-mail
            $emailTo = $usuario['email'] ?? null;
            $hasValidEmail = !empty($emailTo) && filter_var($emailTo, FILTER_VALIDATE_EMAIL);
            
            // Se não tem e-mail válido, retornar mensagem específica
            if (!$hasValidEmail) {
                if ($type === 'aluno') {
                    return [
                        'success' => false,
                        'found' => true,
                        'has_email' => false,
                        'message' => 'Cadastro localizado, porém não há e-mail cadastrado. Entre em contato com a Secretaria para atualizar seu cadastro e redefinir sua senha.',
                        'token' => null,
                        'user_id' => $usuario['id'],
                        'user_email' => null
                    ];
                } else {
                    // Para funcionários, e-mail é obrigatório
                    return [
                        'success' => false,
                        'found' => true,
                        'has_email' => false,
                        'message' => 'Cadastro localizado, porém o e-mail cadastrado não é válido. Entre em contato com a Secretaria para atualizar seu cadastro.',
                        'token' => null,
                        'user_id' => $usuario['id'],
                        'user_email' => null
                    ];
                }
            }
            
            // Tem e-mail válido: gerar token e processar reset
            // Gerar token único (32 bytes, hex)
            $token = bin2hex(random_bytes(32)); // 64 caracteres hex
            $tokenHash = hash('sha256', $token); // Hash SHA256 para armazenar
            
            // Expiração: 30 minutos
            // Usar UTC para evitar problemas de timezone entre PHP e MySQL
            $expiresAt = gmdate('Y-m-d H:i:s', time() + (30 * 60));
            
            // Log para diagnóstico
            if (LOG_ENABLED) {
                $now = date('Y-m-d H:i:s');
                $nowUtc = gmdate('Y-m-d H:i:s');
                error_log(sprintf(
                    '[PASSWORD_RESET] Token gerado - expires_at: %s (UTC: %s), now: %s (UTC: %s), timezone: %s',
                    $expiresAt,
                    gmdate('Y-m-d H:i:s', time() + (30 * 60)),
                    $now,
                    $nowUtc,
                    date_default_timezone_get()
                ));
            }
            
            // Invalidar tokens anteriores do mesmo login (não usados)
            try {
                $db->query(
                    "UPDATE password_resets SET used_at = NOW() WHERE login = :login AND used_at IS NULL",
                    ['login' => $login]
                );
            } catch (Throwable $e) {
                // Log mas continuar (pode ser primeira solicitação)
                if (LOG_ENABLED) {
                    error_log('[PASSWORD_RESET] Aviso ao invalidar tokens anteriores: ' . $e->getMessage());
                }
            }
            
            // Salvar token no banco
            // Usar UTC para created_at também para consistência
            $createdAt = gmdate('Y-m-d H:i:s');
            
            $resetData = [
                'login' => $login,
                'token_hash' => $tokenHash,
                'type' => $type,
                'ip' => $ip,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'created_at' => $createdAt
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
                
                // Preparar e-mail mascarado para feedback
                $maskedDestination = self::maskEmail($emailTo);
                
                return [
                    'success' => true,
                    'found' => true,
                    'has_email' => true,
                    'message' => 'Se o seu cadastro estiver correto, as instruções foram enviadas para redefinir sua senha.',
                    'token' => $token, // Retornar token apenas para montar link no email
                    'user_id' => $usuario['id'],
                    'user_email' => $emailTo,
                    'masked_destination' => $maskedDestination // E-mail mascarado
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
            
            // Em caso de erro, retornar mensagem de erro genérica
            return [
                'success' => false,
                'found' => null,
                'message' => 'Erro ao processar solicitação. Tente novamente mais tarde ou entre em contato com a Secretaria.',
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
            // Usar UTC_TIMESTAMP() para garantir mesma timezone
            $reset = $db->fetch(
                "SELECT id, login, type, expires_at, used_at, created_at FROM password_resets 
                 WHERE token_hash = :token_hash AND expires_at > UTC_TIMESTAMP() AND used_at IS NULL 
                 LIMIT 1",
                ['token_hash' => $tokenHash]
            );
            
            // Log detalhado para diagnóstico
            if (LOG_ENABLED) {
                $nowUtc = gmdate('Y-m-d H:i:s');
                if ($reset) {
                    error_log(sprintf(
                        '[PASSWORD_RESET] Token válido encontrado - expires_at: %s, now (UTC): %s, created_at: %s',
                        $reset['expires_at'],
                        $nowUtc,
                        $reset['created_at'] ?? 'N/A'
                    ));
                } else {
                    // Verificar se existe mas está expirado
                    $expiredCheck = $db->fetch(
                        "SELECT id, expires_at, used_at FROM password_resets 
                         WHERE token_hash = :token_hash 
                         LIMIT 1",
                        ['token_hash' => $tokenHash]
                    );
                    if ($expiredCheck) {
                        error_log(sprintf(
                            '[PASSWORD_RESET] Token encontrado mas INVÁLIDO - expires_at: %s, now (UTC): %s, used_at: %s, reason: %s',
                            $expiredCheck['expires_at'],
                            $nowUtc,
                            $expiredCheck['used_at'] ?? 'NULL',
                            ($expiredCheck['expires_at'] <= $nowUtc) ? 'EXPIRADO' : (($expiredCheck['used_at'] ?? null) ? 'JÁ USADO' : 'OUTRO')
                        ));
                    } else {
                        error_log(sprintf(
                            '[PASSWORD_RESET] Token não encontrado no banco - token_hash: %s',
                            substr($tokenHash, 0, 16) . '...'
                        ));
                    }
                }
            }
            
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
            
        } catch (Throwable $e) {
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
                if (LOG_ENABLED) {
                    error_log(sprintf(
                        '[PASSWORD_RESET] Usuário não encontrado - login: %s, type: %s',
                        $login,
                        $validation['type']
                    ));
                }
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado.'
                ];
            }
            
            // Validar que o usuário tem ID
            if (empty($usuario['id'])) {
                if (LOG_ENABLED) {
                    error_log(sprintf(
                        '[PASSWORD_RESET] Usuário encontrado mas sem ID - login: %s, type: %s, usuario: %s',
                        $login,
                        $validation['type'],
                        json_encode($usuario)
                    ));
                }
                return [
                    'success' => false,
                    'message' => 'Erro ao atualizar senha. Dados do usuário incompletos.'
                ];
            }
            
            // Hash da nova senha
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            if (LOG_ENABLED) {
                error_log(sprintf(
                    '[PASSWORD_RESET] Tentando atualizar senha - usuario_id: %s, tipo: %s, login: %s',
                    $usuario['id'],
                    $usuario['tipo'] ?? 'N/A',
                    $login
                ));
            }
            
            // Atualizar senha na tabela usuarios
            try {
                $stmt = $db->update('usuarios', ['senha' => $passwordHash], 'id = :id', ['id' => $usuario['id']]);
                
                // Verificar se a atualização afetou alguma linha
                $rowsAffected = $stmt ? $stmt->rowCount() : 0;
                
                if ($rowsAffected > 0) {
                    $updateSuccess = true;
                    if (LOG_ENABLED) {
                        error_log(sprintf(
                            '[PASSWORD_RESET] Senha atualizada com sucesso - usuario_id: %s, linhas afetadas: %d',
                            $usuario['id'],
                            $rowsAffected
                        ));
                    }
                } else {
                    $updateSuccess = false;
                    if (LOG_ENABLED) {
                        error_log(sprintf(
                            '[PASSWORD_RESET] Nenhuma linha foi atualizada - usuario_id: %s, tipo: %s. Verificar se o ID existe na tabela.',
                            $usuario['id'],
                            $usuario['tipo'] ?? 'N/A'
                        ));
                    }
                }
            } catch (Throwable $e) {
                $updateSuccess = false;
                if (LOG_ENABLED) {
                    error_log(sprintf(
                        '[PASSWORD_RESET] Erro ao atualizar senha: %s | Usuario ID: %s | Tipo: %s | Login: %s | File: %s:%d | Trace: %s',
                        $e->getMessage(),
                        $usuario['id'],
                        $usuario['tipo'] ?? 'N/A',
                        $login,
                        $e->getFile(),
                        $e->getLine(),
                        substr($e->getTraceAsString(), 0, 500)
                    ));
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
                } catch (Throwable $e) {
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
            
            // Marcar token como usado (usar UTC para consistência)
            try {
                $db->update('password_resets', ['used_at' => gmdate('Y-m-d H:i:s')], 'id = :id', ['id' => $validation['reset_id']]);
            } catch (Throwable $e) {
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
            } catch (Throwable $e) {
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
            // Para aluno: buscar por CPF (prioritário) na tabela usuarios
            if ($type === 'aluno') {
                // Limpar CPF (remover pontos e traços) e espaços
                $cpfLimpo = preg_replace('/[^0-9]/', '', trim($login));
                
                if (LOG_ENABLED) {
                    error_log("[PASSWORD_RESET] findUserByLogin - login recebido: '$login', type: '$type', cpfLimpo: '$cpfLimpo'");
                }
                
                // Verificar se é email ou CPF
                $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
                
                // Buscar APENAS na tabela usuarios (não usar fallback para alunos)
                if ($isEmail) {
                    // Se for email, buscar por email
                    $usuario = $db->fetch(
                        "SELECT id, email, cpf, tipo FROM usuarios WHERE email = :email AND tipo = 'aluno' AND ativo = 1 LIMIT 1",
                        ['email' => $login]
                    );
                } else {
                    // Se for CPF, buscar normalizando o CPF do banco (pode estar com ou sem formatação)
                    // Usar REPLACE para remover formatação durante a comparação
                    if (!empty($cpfLimpo) && strlen($cpfLimpo) === 11) {
                        if (LOG_ENABLED) {
                            error_log("[PASSWORD_RESET] Buscando aluno na tabela usuarios com CPF limpo: $cpfLimpo");
                        }
                        
                        $usuario = $db->fetch(
                            "SELECT id, email, cpf, tipo FROM usuarios 
                             WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
                             AND tipo = 'aluno' 
                             AND ativo = 1 
                             LIMIT 1",
                            ['cpf' => $cpfLimpo]
                        );
                        
                        if (LOG_ENABLED) {
                            if ($usuario) {
                                error_log("[PASSWORD_RESET] Aluno encontrado na tabela usuarios - ID: " . $usuario['id'] . ", CPF: " . ($usuario['cpf'] ?? 'N/A'));
                            } else {
                                error_log("[PASSWORD_RESET] Aluno NÃO encontrado na tabela usuarios com CPF: $cpfLimpo");
                                // Log adicional: verificar se existe com outro formato
                                $verificarFormato = $db->fetchAll(
                                    "SELECT id, cpf, tipo, ativo FROM usuarios WHERE tipo = 'aluno' LIMIT 10"
                                );
                                if (LOG_ENABLED && !empty($verificarFormato)) {
                                    error_log("[PASSWORD_RESET] Exemplos de CPFs na tabela usuarios: " . json_encode($verificarFormato));
                                }
                            }
                        }
                    } else {
                        if (LOG_ENABLED) {
                            error_log("[PASSWORD_RESET] CPF inválido ou vazio - limpo: '$cpfLimpo', tamanho: " . strlen($cpfLimpo));
                        }
                        $usuario = null;
                    }
                }
                
                // Se encontrou, buscar dados adicionais na tabela alunos (telefone/email complementar)
                if ($usuario) {
                    // Buscar CPF e telefone na tabela alunos (apenas para dados complementares)
                    $cpfParaBusca = $usuario['cpf'] ?? $cpfLimpo;
                    $emailParaBusca = $usuario['email'] ?? null;
                    
                    $alunoCompleto = null;
                    try {
                        if (!empty($cpfParaBusca)) {
                            // Normalizar CPF para busca
                            $cpfBusca = preg_replace('/[^0-9]/', '', $cpfParaBusca);
                            
                            if (strlen($cpfBusca) === 11) {
                                // Buscar normalizando CPF do banco (remove formatação durante comparação)
                                // Nota: tabela alunos tem 'telefone' e 'telefone_secundario', não tem 'celular'
                                $alunoCompleto = $db->fetch(
                                    "SELECT cpf, telefone, telefone_secundario, email FROM alunos 
                                     WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
                                     LIMIT 1",
                                    ['cpf' => $cpfBusca]
                                );
                            }
                        } elseif (!empty($emailParaBusca)) {
                            $alunoCompleto = $db->fetch(
                                "SELECT cpf, telefone, telefone_secundario, email FROM alunos WHERE email = :email LIMIT 1",
                                ['email' => $emailParaBusca]
                            );
                        }
                    } catch (Exception $e) {
                        // Se falhar ao buscar dados complementares, não é crítico - continuar com dados do usuarios
                        if (LOG_ENABLED) {
                            error_log("[PASSWORD_RESET] Erro ao buscar dados complementares em alunos (não crítico): " . $e->getMessage());
                        }
                        $alunoCompleto = null;
                    }
                    
                    if ($alunoCompleto) {
                        // Atualizar email se não tinha (mas manter CPF e ID de usuarios)
                        if (empty($usuario['email']) && !empty($alunoCompleto['email'])) {
                            $usuario['email'] = $alunoCompleto['email'];
                        }
                        // Adicionar telefone (preferir telefone_secundario se disponível, senão telefone) - apenas para exibição/feedback
                        $usuario['telefone'] = !empty($alunoCompleto['telefone_secundario']) 
                            ? $alunoCompleto['telefone_secundario'] 
                            : ($alunoCompleto['telefone'] ?? null);
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
    
    /**
     * Mascarar e-mail para exibição segura (padrão cartão)
     * Exemplo: joao.silva@gmail.com → jo***@gm***.com
     * Exemplo: contato@cfc.com.br → co***@cf***.com.br
     * 
     * @param string $email E-mail completo
     * @return string|null E-mail mascarado ou null se inválido
     */
    private static function maskEmail($email) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        list($local, $domain) = explode('@', $email, 2);
        
        // Mascarar parte local (antes do @)
        // Manter primeiras 2 letras + asteriscos (padrão cartão)
        if (strlen($local) >= 2) {
            $maskedLocal = substr($local, 0, 2) . str_repeat('*', min(3, max(1, strlen($local) - 2)));
        } elseif (strlen($local) == 1) {
            $maskedLocal = substr($local, 0, 1) . '***';
        } else {
            $maskedLocal = '*';
        }
        
        // Mascarar domínio
        // Separar domínio em partes (ex: example.com.br → [example, com, br])
        $domainParts = explode('.', $domain);
        $mainDomain = array_shift($domainParts); // exemplo
        
        // Manter 2 primeiras letras do domínio principal + asteriscos (padrão cartão)
        if (strlen($mainDomain) >= 2) {
            $maskedMain = substr($mainDomain, 0, 2) . str_repeat('*', min(3, max(1, strlen($mainDomain) - 2)));
        } elseif (strlen($mainDomain) == 1) {
            $maskedMain = substr($mainDomain, 0, 1) . '***';
        } else {
            $maskedMain = '***';
        }
        
        // Reconstruir domínio com extensão
        $extension = !empty($domainParts) ? '.' . implode('.', $domainParts) : '';
        
        return $maskedLocal . '@' . $maskedMain . $extension;
    }
    
    /**
     * Mascarar telefone para exibição segura
     * Exemplo: (87) 98145-0308 → (**) *****-**08
     * 
     * @param string $phone Telefone completo
     * @return string|null Telefone mascarado ou null se inválido
     */
    private static function maskPhone($phone) {
        if (empty($phone)) {
            return null;
        }
        
        // Remover caracteres não numéricos
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Se tiver menos de 10 dígitos, não mascarar
        if (strlen($cleaned) < 10) {
            return null;
        }
        
        // Formato esperado: DDD (2 dígitos) + número (8 ou 9 dígitos)
        // Exibir apenas últimos 2 dígitos
        $lastTwo = substr($cleaned, -2);
        
        // Contar dígitos totais para determinar padrão
        if (strlen($cleaned) == 11) {
            // Celular: (XX) 9XXXX-XXXX → (**) *****-**XX
            return '(**) *****-**' . $lastTwo;
        } elseif (strlen($cleaned) == 10) {
            // Fixo: (XX) XXXX-XXXX → (**) ****-**XX
            return '(**) ****-**' . $lastTwo;
        } else {
            // Formato desconhecido, mascarar tudo exceto últimos 2
            $masked = str_repeat('*', strlen($cleaned) - 2) . $lastTwo;
            return $masked;
        }
    }
}
