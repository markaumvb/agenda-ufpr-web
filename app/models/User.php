<?php

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function findByEmail($email) {
        try {
            $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Erro ao buscar usuário por e-mail: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateLastLogin($id) {
    try {
        // Atualizar a data do último login
        $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erro ao atualizar data de último login: ' . $e->getMessage());
        return false;
    }
}


public function isRegistrationComplete($id) {
    try {
        $query = "SELECT first_access FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        // Se first_access for 0, o registro está completo
        return $result && $result['first_access'] == 0;
    } catch (PDOException $e) {
        error_log('Erro ao verificar registro do usuário: ' . $e->getMessage());
        return false;
    }
}

    public function create($data) {
        try {
            $query = "INSERT INTO users (username, name, email, created_at) 
                      VALUES (:username, :name, :email, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao criar usuário: ' . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $query = "UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id";
            
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

    public function getById($id) {
        try {
            $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Erro ao buscar usuário por ID: ' . $e->getMessage());
            return false;
        }
    }

public function findByUsername($username) {
    try {
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Erro ao buscar usuário por nome de usuário: ' . $e->getMessage());
        return false;
    }
}
}