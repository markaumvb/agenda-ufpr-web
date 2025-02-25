<?php
// Arquivo: agenda_ufpr/app/models/User.php

/**
 * Modelo para gerenciar os dados de usuários
 */
class User {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Busca um usuário pelo nome de usuário
     * 
     * @param string $username Nome de usuário
     * @return array|false Dados do usuário ou false se não encontrado
     */
    public function findByUsername($username) {
        try {
            $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['username' => $username]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Erro ao buscar usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria um novo usuário
     * 
     * @param array $data Dados do usuário
     * @return bool Resultado da operação
     */
    public function create($data) {
        try {
            $query = "INSERT INTO users (username, name, email, created_at) 
                      VALUES (:username, :name, :email, NOW())";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                'username' => $data['username'],
                'name' => $data['name'],
                'email' => $data['email']
            ]);
        } catch (PDOException $e) {
            error_log('Erro ao criar usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza os dados de um usuário
     * 
     * @param int $id ID do usuário
     * @param array $data Dados a serem atualizados
     * @return bool Resultado da operação
     */
    public function update($id, $data) {
        try {
            $query = "UPDATE users SET 
                        name = :name,
                        email = :email,
                        updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'id' => $id
            ]);
        } catch (PDOException $e) {
            error_log('Erro ao atualizar usuário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca o usuário como já tendo feito o primeiro acesso
     * 
     * @param int $id ID do usuário
     * @return bool Resultado da operação
     */
    public function markFirstAccessComplete($id) {
        try {
            $query = "UPDATE users SET first_access = 0 WHERE id = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log('Erro ao atualizar status de primeiro acesso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uma lista de todos os usuários
     * 
     * @return array Lista de usuários
     */
    public function getAll() {
        try {
            $query = "SELECT id, username, name, email, created_at FROM users ORDER BY name";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao obter lista de usuários: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se o usuário existe pelo ID
     * 
     * @param int $id ID do usuário
     * @return bool Se o usuário existe
     */
    public function exists($id) {
        try {
            $query = "SELECT COUNT(*) FROM users WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar existência de usuário: ' . $e->getMessage());
            return false;
        }
    }
}