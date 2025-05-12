<?php
// Arquivo: app/models/AgendaShare.php

/**
 * Modelo para gerenciar o compartilhamento de agendas
 */
class AgendaShare {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Compartilha uma agenda com um usuário
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário com quem compartilhar
     * @param bool $canEdit Se o usuário pode editar a agenda
     * @return bool Resultado da operação
     */
    public function shareAgenda($agendaId, $userId, $canEdit = false) {
        try {
            // Verificar se já existe um compartilhamento para esta agenda e usuário
            $checkQuery = "SELECT COUNT(*) FROM agenda_shares WHERE agenda_id = :agenda_id AND user_id = :user_id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $checkStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            $exists = (int)$checkStmt->fetchColumn() > 0;
            
            if ($exists) {
                // Atualizar o compartilhamento existente
                $query = "UPDATE agenda_shares SET can_edit = :can_edit WHERE agenda_id = :agenda_id AND user_id = :user_id";
            } else {
                // Criar um novo compartilhamento
                $query = "INSERT INTO agenda_shares (agenda_id, user_id, can_edit, created_at) VALUES (:agenda_id, :user_id, :can_edit, NOW())";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':can_edit', $canEdit, PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao compartilhar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove o compartilhamento de uma agenda com um usuário
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @return bool Resultado da operação
     */
    public function removeShare($agendaId, $userId) {
        try {
            $query = "DELETE FROM agenda_shares WHERE agenda_id = :agenda_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao remover compartilhamento da agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todos os compartilhamentos de uma agenda
     * 
     * @param int $agendaId ID da agenda
     * @return array Lista de compartilhamentos
     */
    public function getSharesByAgenda($agendaId) {
        try {
            $query = "
                SELECT s.*, u.username, u.name, u.email 
                FROM agenda_shares s
                JOIN users u ON s.user_id = u.id
                WHERE s.agenda_id = :agenda_id
                ORDER BY u.name
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao obter compartilhamentos da agenda: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todas as agendas compartilhadas com um usuário
     * 
     * @param int $userId ID do usuário
     * @return array Lista de agendas compartilhadas
     */
    public function getSharedAgendasByUser($userId) {
        try {
            $query = "
                SELECT a.*, s.can_edit, u.name as owner_name 
                FROM agenda_shares s
                JOIN agendas a ON s.agenda_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE s.user_id = :user_id
                ORDER BY a.title
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao obter agendas compartilhadas com o usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se um usuário tem acesso a uma agenda
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @return bool|array False se não tiver acesso, ou array com detalhes do compartilhamento
     */
    public function checkAccess($agendaId, $userId) {
        try {
            $query = "SELECT * FROM agenda_shares WHERE agenda_id = :agenda_id AND user_id = :user_id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            return $result ? $result : false;
        } catch (PDOException $e) {
            error_log('Erro ao verificar acesso à agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se um usuário pode editar uma agenda compartilhada
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @return bool Se o usuário pode editar
     */
    public function canEdit($agendaId, $userId) {
        $access = $this->checkAccess($agendaId, $userId);
        
        if (!$access) {
            return false;
        }
        
        return (bool)$access['can_edit'];
    }

    public function deleteAllFromAgenda($agendaId) {
        $sql = "DELETE FROM agenda_shares WHERE agenda_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$agendaId]);
    }

    public function getSharedWithUser($userId, $activeOnly = true, $page = 1, $perPage = 10, $search = null) {
    try {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT a.*, 
               s.can_edit,
               u.name as owner_name,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'pendente') as pendentes,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'realizado') as realizados,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'cancelado') as cancelados,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'aguardando_aprovacao') as aguardando_aprovacao
                FROM agenda_shares s
                INNER JOIN agendas a ON s.agenda_id = a.id
                INNER JOIN users u ON a.user_id = u.id
                WHERE s.user_id = :user_id";
        
        if ($activeOnly) {
            $sql .= " AND a.is_active = 1";
        }
        
        // Adicionar filtro de busca
        if ($search) {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search OR u.name LIKE :search)";
        }
        
        $sql .= " ORDER BY a.title
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($search) {
            $searchParam = "%{$search}%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $agendas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['is_owner'] = false;
            $row['compromissos'] = [
                'pendentes' => (int)$row['pendentes'],
                'realizados' => (int)$row['realizados'],
                'cancelados' => (int)$row['cancelados'],
                'aguardando_aprovacao' => (int)$row['aguardando_aprovacao']
            ];
            $agendas[] = $row;
        }
        
        return $agendas;
    } catch (PDOException $e) {
        error_log('Erro ao buscar agendas compartilhadas com o usuário: ' . $e->getMessage());
        return [];
    }
}


public function countSharedWithUser($userId, $activeOnly = true, $search = null) {
    $sql = "SELECT COUNT(*) as total 
            FROM agenda_shares s
            INNER JOIN agendas a ON s.agenda_id = a.id
            INNER JOIN users u ON a.user_id = u.id
            WHERE s.user_id = :user_id";
    
    if ($activeOnly) {
        $sql .= " AND a.is_active = 1";
    }
    
    if ($search) {
        $sql .= " AND (a.title LIKE :search OR a.description LIKE :search OR u.name LIKE :search)";
    }
    
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    
    if ($search) {
        $searchParam = "%{$search}%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row['total'];
}

public function getAgendasSharedByUser($userId, $search = null) {
    try {
        // Modificada para buscar agendas onde o usuário é o dono E que foram compartilhadas com outros
        $sql = "SELECT DISTINCT a.*, 
               u.name as owner_name,
               1 as can_edit,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'pendente') as pendentes,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'realizado') as realizados,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'cancelado') as cancelados,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'aguardando_aprovacao') as aguardando_aprovacao
                FROM agendas a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN agenda_shares s ON a.id = s.agenda_id
                WHERE a.user_id = :userId 
                AND EXISTS (SELECT 1 FROM agenda_shares WHERE agenda_id = a.id)";
        
        if ($search) {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search OR u.name LIKE :search)";
        }
        
        $sql .= " ORDER BY a.title";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        
        if ($search) {
            $searchParam = "%{$search}%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        $agendas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['is_owner'] = true;
            $row['compromissos'] = [
                'pendentes' => $row['pendentes'],
                'realizados' => $row['realizados'],
                'cancelados' => $row['cancelados'],
                'aguardando_aprovacao' => $row['aguardando_aprovacao']
            ];
            $agendas[] = $row;
        }
        
        return $agendas;
    } catch (PDOException $e) {
        error_log('Erro ao buscar agendas compartilhadas pelo usuário: ' . $e->getMessage());
        return [];
    }
}
}