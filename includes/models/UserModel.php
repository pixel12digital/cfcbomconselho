<?php
/**
 * UserModel - Modelo de Usuário
 * Classe para gerenciar operações de usuários no banco de dados
 */

class UserModel {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Buscar usuário por email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar usuário por ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Listar todos os usuários
     */
    public function findAll() {
        $stmt = $this->db->query("SELECT * FROM usuarios ORDER BY nome");
        return $stmt->fetchAll();
    }
    
    /**
     * Criar novo usuário
     */
    public function create($dados) {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nome, email, senha, tipo, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $dados['nome'],
            $dados['email'],
            password_hash($dados['senha'], PASSWORD_DEFAULT),
            $dados['tipo'],
            $dados['status'] ?? 'ativo'
        ]);
    }
    
    /**
     * Atualizar usuário
     */
    public function update($id, $dados) {
        $stmt = $this->db->prepare("
            UPDATE usuarios 
            SET nome = ?, email = ?, tipo = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $dados['nome'],
            $dados['email'],
            $dados['tipo'],
            $dados['status'],
            $id
        ]);
    }
    
    /**
     * Excluir usuário
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Verificar credenciais de login
     */
    public function authenticate($email, $senha) {
        $usuario = $this->findByEmail($email);
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            return $usuario;
        }
        
        return false;
    }
}
?>
