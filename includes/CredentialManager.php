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
        
        // Gerar senha temporária
        $tempPassword = self::generateTempPassword();
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Criar usuário na tabela usuarios
        $usuarioData = [
            'nome' => $dados['nome'],
            'email' => $dados['email'] ?? $dados['cpf'] . '@aluno.cfc',
            'senha' => $hashedPassword,
            'tipo' => 'aluno',
            'ativo' => true,
            'primeiro_acesso' => true,
            'senha_temporaria' => true,
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        $usuarioId = $db->insert('usuarios', $usuarioData);
        
        if ($usuarioId) {
            // Não precisamos atualizar a tabela alunos pois ela não tem campos de usuário
            // O relacionamento é feito apenas através do CPF/email
            
            return [
                'success' => true,
                'usuario_id' => $usuarioId,
                'cpf' => $dados['cpf'],
                'senha_temporaria' => $tempPassword,
                'message' => 'Credenciais do aluno criadas com sucesso'
            ];
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
