<?php
/**
 * Sistema de Credenciais Automáticas - Sistema CFC
 * 
 * Este arquivo gerencia a criação automática de credenciais
 * para novos usuários (instrutores, atendentes, alunos)
 */

class CredentialManager {
    
    /**
     * Gerar senha temporária segura
     */
    public static function generateTempPassword($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Gerar senha temporária para redefinição (alias para compatibilidade)
     */
    public static function generateTemporaryPassword($length = 8) {
        return self::generateTempPassword($length);
    }
    
    /**
     * Criar credenciais automáticas para funcionário
     */
    public static function createEmployeeCredentials($dados) {
        $db = db();
        
        // Gerar senha temporária
        $tempPassword = self::generateTempPassword();
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Criar usuário na tabela usuarios
        $usuarioData = [
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha' => $hashedPassword,
            'tipo' => $dados['tipo'],
            'ativo' => true,
            'primeiro_acesso' => true,
            'senha_temporaria' => true,
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        $usuarioId = $db->insert('usuarios', $usuarioData);
        
        if ($usuarioId) {
            return [
                'success' => true,
                'usuario_id' => $usuarioId,
                'email' => $dados['email'],
                'senha_temporaria' => $tempPassword,
                'message' => 'Credenciais criadas com sucesso'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao criar credenciais'
        ];
    }
    
    /**
     * Criar credenciais automáticas para aluno
     */
    public static function createStudentCredentials($dados) {
        $db = db();
        
        // Determinar email a ser usado
        $email = $dados['email'] ?? ($dados['cpf'] . '@aluno.cfc');
        
        // Verificar se o email já existe na tabela usuarios
        $usuarioExistente = $db->fetch("SELECT id, nome, tipo FROM usuarios WHERE email = ?", [$email]);
        
        if ($usuarioExistente) {
            // Se o usuário já existe, retornar sucesso sem criar duplicado
            // Mas verificar se é do tipo 'aluno' para garantir consistência
            if ($usuarioExistente['tipo'] === 'aluno') {
                return [
                    'success' => true,
                    'usuario_id' => $usuarioExistente['id'],
                    'cpf' => $dados['cpf'],
                    'senha_temporaria' => null, // Não gerar nova senha se usuário já existe
                    'message' => 'Usuário já existe para este email. Credenciais não foram alteradas.',
                    'usuario_existente' => true
                ];
            } else {
                // Se o email existe mas é de outro tipo, retornar erro
                return [
                    'success' => false,
                    'message' => 'Este email já está cadastrado para outro tipo de usuário (' . $usuarioExistente['tipo'] . ')'
                ];
            }
        }
        
        // Se o email não existe, criar novo usuário
        // Gerar senha temporária
        $tempPassword = self::generateTempPassword();
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Criar usuário na tabela usuarios
        $usuarioData = [
            'nome' => $dados['nome'],
            'email' => $email,
            'senha' => $hashedPassword,
            'tipo' => 'aluno',
            'ativo' => true,
            'primeiro_acesso' => true,
            'senha_temporaria' => true,
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        try {
            $usuarioId = $db->insert('usuarios', $usuarioData);
            
            if ($usuarioId) {
                // Não precisamos atualizar a tabela alunos pois ela não tem campos de usuário
                // O relacionamento é feito apenas através do CPF/email
                
                return [
                    'success' => true,
                    'usuario_id' => $usuarioId,
                    'cpf' => $dados['cpf'],
                    'senha_temporaria' => $tempPassword,
                    'message' => 'Credenciais do aluno criadas com sucesso',
                    'usuario_existente' => false
                ];
            }
        } catch (Exception $e) {
            // Se der erro de duplicação (mesmo após verificação), tratar graciosamente
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'for key \'email\'') !== false) {
                // Tentar buscar o usuário que foi criado entre a verificação e a inserção
                $usuarioExistente = $db->fetch("SELECT id, nome, tipo FROM usuarios WHERE email = ?", [$email]);
                if ($usuarioExistente && $usuarioExistente['tipo'] === 'aluno') {
                    return [
                        'success' => true,
                        'usuario_id' => $usuarioExistente['id'],
                        'cpf' => $dados['cpf'],
                        'senha_temporaria' => null,
                        'message' => 'Usuário já existe para este email. Credenciais não foram alteradas.',
                        'usuario_existente' => true
                    ];
                }
            }
            
            // Se for outro tipo de erro, propagar
            throw $e;
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao criar credenciais do aluno'
        ];
    }
    
    /**
     * Enviar credenciais por email (simulado)
     */
    public static function sendCredentials($email, $senha, $tipo) {
        // Aqui você implementaria o envio real de email
        // Por enquanto, vamos apenas logar as credenciais
        
        $message = "=== CREDENCIAIS DE ACESSO ===\n";
        $message .= "Tipo: " . ucfirst($tipo) . "\n";
        $message .= "Email: " . $email . "\n";
        $message .= "Senha temporária: " . $senha . "\n";
        $message .= "IMPORTANTE: Altere sua senha no primeiro acesso!\n";
        $message .= "========================\n";
        
        error_log($message);
        
        return true;
    }
    
    /**
     * Verificar se é primeiro acesso
     */
    public static function isFirstAccess($usuarioId) {
        $db = db();
        $usuario = $db->fetch("SELECT primeiro_acesso FROM usuarios WHERE id = ?", [$usuarioId]);
        return $usuario && $usuario['primeiro_acesso'];
    }
    
    /**
     * Marcar primeiro acesso como concluído
     */
    public static function markFirstAccessCompleted($usuarioId) {
        $db = db();
        return $db->update('usuarios', [
            'primeiro_acesso' => false,
            'senha_temporaria' => false
        ], ['id' => $usuarioId]);
    }
    
    /**
     * Obter informações de credenciais para exibição
     */
    public static function getCredentialsInfo($tipo) {
        $info = [
            'admin' => [
                'title' => 'Administrador',
                'description' => 'Acesso total ao sistema',
                'login_method' => 'Email + Senha',
                'auto_credentials' => true
            ],
            'secretaria' => [
                'title' => 'Atendente CFC',
                'description' => 'Acesso completo menos configurações',
                'login_method' => 'Email + Senha',
                'auto_credentials' => true
            ],
            'instrutor' => [
                'title' => 'Instrutor',
                'description' => 'Pode editar/cancelar aulas',
                'login_method' => 'Email + Senha',
                'auto_credentials' => true
            ],
            'aluno' => [
                'title' => 'Aluno',
                'description' => 'Acesso apenas visual',
                'login_method' => 'CPF + Senha',
                'auto_credentials' => true
            ]
        ];
        
        return $info[$tipo] ?? $info['aluno'];
    }
}
