<?php
// Arquivo: app/models/Agenda.php

/**
 * Modelo para gerenciar os dados de agendas
 */
class Agenda {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Retorna todas as agendas de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $search Termo de pesquisa (opcional)
     * @return array Lista de agendas
     */
    public function getAllByUser($userId, $search = null) {
        try {
            $query = "
                SELECT a.*, 
                       (SELECT COUNT(*) FROM compromissos c WHERE c.agenda_id = a.id) as total_compromissos
                FROM agendas a
                WHERE a.user_id = :user_id
            ";
            
            // Adiciona filtro de pesquisa se especificado
            if ($search) {
                $query .= " AND (a.title LIKE :search OR a.description LIKE :search)";
            }
            
            $query .= " ORDER BY a.title";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            if ($search) {
                $searchParam = "%{$search}%";
                $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar agendas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Retorna uma agenda específica
     * 
     * @param int $id ID da agenda
     * @return array|false Dados da agenda ou false se não encontrada
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM agendas WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Erro ao buscar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se uma agenda pertence a um usuário
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @return bool Se a agenda pertence ao usuário
     */
    public function belongsToUser($agendaId, $userId) {
        try {
            $query = "SELECT COUNT(*) FROM agendas WHERE id = :agenda_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar propriedade da agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma nova agenda
     * 
     * @param array $data Dados da agenda
     * @return int|false ID da agenda criada ou false em caso de erro
     */
    public function create($data) {
        try {
            $query = "
                INSERT INTO agendas (user_id, title, description, is_public, color, created_at)
                VALUES (:user_id, :title, :description, :is_public, :color, NOW())
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $data['is_public'], PDO::PARAM_INT);
            $stmt->bindParam(':color', $data['color'], PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao criar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza uma agenda existente
     * 
     * @param int $id ID da agenda
     * @param array $data Dados a serem atualizados
     * @return bool Resultado da operação
     */
    public function update($id, $data) {
        try {
            $query = "
                UPDATE agendas
                SET title = :title,
                    description = :description,
                    is_public = :is_public,
                    color = :color,
                    updated_at = NOW()
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $data['is_public'], PDO::PARAM_INT);
            $stmt->bindParam(':color', $data['color'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao atualizar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui uma agenda
     * 
     * @param int $id ID da agenda
     * @return bool Resultado da operação
     */
    public function delete($id) {
        try {
            // Verificar se há compromissos pendentes ou aguardando aprovação
            $query = "
                SELECT COUNT(*) FROM compromissos 
                WHERE agenda_id = :agenda_id 
                AND (status = 'pendente' OR status = 'aguardando_aprovacao')
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ((int)$stmt->fetchColumn() > 0) {
                // Não pode excluir se houver compromissos pendentes
                return false;
            }
            
            // Excluir a agenda
            $query = "DELETE FROM agendas WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao excluir agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Conta os compromissos de uma agenda por status
     * 
     * @param int $agendaId ID da agenda
     * @return array Contagem de compromissos por status
     */
    public function countCompromissosByStatus($agendaId) {
        try {
            $query = "
                SELECT 
                    SUM(CASE WHEN status = 'realizado' THEN 1 ELSE 0 END) as realizados,
                    SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN status = 'aguardando_aprovacao' THEN 1 ELSE 0 END) as aguardando_aprovacao,
                    COUNT(*) as total
                FROM compromissos
                WHERE agenda_id = :agenda_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            // Se não houver compromissos, retornar zeros
            if (!$result) {
                return [
                    'realizados' => 0,
                    'cancelados' => 0,
                    'pendentes' => 0,
                    'aguardando_aprovacao' => 0,
                    'total' => 0
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log('Erro ao contar compromissos: ' . $e->getMessage());
            return [
                'realizados' => 0,
                'cancelados' => 0,
                'pendentes' => 0,
                'aguardando_aprovacao' => 0,
                'total' => 0
            ];
        }
    }
    
    /**
     * Verifica se uma agenda pode ser excluída
     * 
     * @param int $agendaId ID da agenda
     * @return bool Se a agenda pode ser excluída
     */
    public function canBeDeleted($agendaId) {
        try {
            $query = "
                SELECT COUNT(*) FROM compromissos 
                WHERE agenda_id = :agenda_id 
                AND (status = 'pendente' OR status = 'aguardando_aprovacao')
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar se agenda pode ser excluída: ' . $e->getMessage());
            return false;
        }
    }


/**
 * Obtém todas as agendas que o usuário possui acesso (próprias e compartilhadas)
 * 
 * @param int $userId ID do usuário
 * @param string $search Termo de pesquisa (opcional)
 * @return array Lista de agendas
 */
public function getAllAccessibleByUser($userId, $search = null) {
    try {
        // Verificar se o usuário existe
        if (!$userId) {
            return [];
        }
        
        // Versão mais simples que deve funcionar em todos os ambientes
        $query = "
            SELECT 
                a.*,
                u.name as owner_name,
                CASE WHEN a.user_id = :user_id THEN 1 ELSE 0 END as is_owner,
                s.can_edit
            FROM agendas a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN agenda_shares s ON a.id = s.agenda_id AND s.user_id = :user_id2
            WHERE 
                a.user_id = :user_id3
                OR s.user_id = :user_id4
                OR a.is_public = 1
        ";
        
        // Adicionar filtro de pesquisa se especificado
        if ($search) {
            $query .= " AND (a.title LIKE :search OR a.description LIKE :search)";
        }
        
        $query .= " ORDER BY CASE WHEN a.user_id = :user_id5 THEN 0 ELSE 1 END, a.title";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id2', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id3', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id4', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id5', $userId, PDO::PARAM_INT);
        
        if ($search) {
            $searchParam = "%{$search}%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Erro ao buscar agendas acessíveis: ' . $e->getMessage());
        return [];
    }
}
/**
 * Verifica se um usuário tem acesso à agenda
 * 
 * @param int $agendaId ID da agenda
 * @param int $userId ID do usuário
 * @return bool Se o usuário tem acesso
 */
public function checkUserAccess($agendaId, $userId) {
    try {
        // Verificar se o usuário é o dono da agenda
        if ($this->belongsToUser($agendaId, $userId)) {
            return true;
        }
        
        // Verificar se a agenda é pública
        $agenda = $this->getById($agendaId);
        if ($agenda && $agenda['is_public']) {
            return true;
        }
        
        // Verificar se a agenda foi compartilhada com o usuário
        $query = "SELECT COUNT(*) FROM agenda_shares WHERE agenda_id = :agenda_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('Erro ao verificar acesso do usuário à agenda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se um usuário pode editar uma agenda
 * 
 * @param int $agendaId ID da agenda
 * @param int $userId ID do usuário
 * @return bool Se o usuário pode editar
 */
public function canUserEdit($agendaId, $userId) {
    try {
        // Verificar se o usuário é o dono da agenda
        if ($this->belongsToUser($agendaId, $userId)) {
            return true;
        }
        
        // Verificar se a agenda foi compartilhada com o usuário com permissão de edição
        $query = "SELECT can_edit FROM agenda_shares WHERE agenda_id = :agenda_id AND user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return $result && $result['can_edit'];
    } catch (PDOException $e) {
        error_log('Erro ao verificar permissão de edição do usuário na agenda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza o hash público de uma agenda
 * 
 * @param int $id ID da agenda
 * @param string $hash Novo hash
 * @return bool Resultado da operação
 */
public function updatePublicHash($id, $hash) {
    try {
        $query = "UPDATE agendas SET public_hash = :hash WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erro ao atualizar hash público: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtém uma agenda pelo hash público
 * 
 * @param string $hash Hash público da agenda
 * @return array|bool Dados da agenda ou false se não encontrada
 */
public function getByPublicHash($hash) {
    try {
        $query = "SELECT * FROM agendas WHERE public_hash = :hash LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Erro ao buscar agenda por hash: ' . $e->getMessage());
        return false;
    }
}
}   