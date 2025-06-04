<?php

class AgendaShare {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    

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
    

    public function getSharedWithUser($userId, $activeOnly = true, $page = 1, $perPage = 10, $search = null) {
    try {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT a.*, 
               s.can_edit,
               u.name as owner_name,
               u.id as owner_id
            FROM agenda_shares s
            INNER JOIN agendas a ON s.agenda_id = a.id
            INNER JOIN users u ON a.user_id = u.id
            WHERE s.user_id = :user_id";
        
        if ($activeOnly) {
            $sql .= " AND a.is_active = 1";
        }
        
        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search OR u.name LIKE :search2)";
        }
        
        $sql .= " ORDER BY a.title LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        
        if ($search !== null && trim($search) !== '') {
            $searchParam = "%{$search}%";
            $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $agendas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['is_owner'] = false;
            $row['compromissos'] = [
                'pendentes' => $this->countCompromissosByStatus($row['id'], 'pendente'),
                'realizados' => $this->countCompromissosByStatus($row['id'], 'realizado'),
                'cancelados' => $this->countCompromissosByStatus($row['id'], 'cancelado'),
                'aguardando_aprovacao' => $this->countCompromissosByStatus($row['id'], 'aguardando_aprovacao')
            ];
            $agendas[] = $row;
        }
        
        return $agendas;
    } catch (PDOException $e) {
        error_log('Erro ao buscar agendas compartilhadas: ' . $e->getMessage());
        return [];
    }
}
    

    public function countSharedWithUser($userId, $activeOnly = true, $search = null) {
    try {
        $sql = "SELECT COUNT(*) as total 
                FROM agenda_shares s
                INNER JOIN agendas a ON s.agenda_id = a.id
                INNER JOIN users u ON a.user_id = u.id
                WHERE s.user_id = :user_id";
        
        if ($activeOnly) {
            $sql .= " AND a.is_active = 1";
        }
        
        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search OR u.name LIKE :search2)";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        
        if ($search !== null && trim($search) !== '') {
            $searchParam = "%{$search}%";
            $stmt->bindValue(':search', $searchParam, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return intval($row['total']);
    } catch (PDOException $e) {
        error_log('Erro ao contar agendas compartilhadas: ' . $e->getMessage());
        return 0;
    }
}
    
public function getAgendasSharedByUser($userId, $search = null) {
    try {
        $sql = "SELECT DISTINCT a.id, a.title, a.description, a.color, a.is_public, a.is_active,
                       (SELECT name FROM users WHERE id = a.user_id) as owner_name
                FROM agendas a
                WHERE a.user_id = :user_id 
                AND EXISTS (
                    SELECT 1 FROM agenda_shares 
                    WHERE agenda_id = a.id
                )";
        
        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search)";
        }
        
        $sql .= " ORDER BY a.title";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        
        if ($search !== null && trim($search) !== '') {
            $stmt->bindValue(':search', "%{$search}%", PDO::PARAM_STR);
        }
        
        $stmt->execute();
        
        $agendas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Adicionar dados dos usuários com quem esta agenda foi compartilhada
            $sharesSql = "SELECT u.name 
                          FROM agenda_shares s
                          JOIN users u ON s.user_id = u.id
                          WHERE s.agenda_id = ?";
            
            $sharesStmt = $this->db->prepare($sharesSql);
            $sharesStmt->execute([$row['id']]);
            $shares = $sharesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $row['is_owner'] = true;
            $row['can_edit'] = true;
            $row['compromissos'] = [
                'pendentes' => $this->countCompromissosByStatus($row['id'], 'pendente'),
                'realizados' => $this->countCompromissosByStatus($row['id'], 'realizado'),
                'cancelados' => $this->countCompromissosByStatus($row['id'], 'cancelado'),
                'aguardando_aprovacao' => $this->countCompromissosByStatus($row['id'], 'aguardando_aprovacao')
            ];
            $row['shared_with'] = $shares;
            
            $agendas[] = $row;
        }
        
        return $agendas;
    } catch (PDOException $e) {
        error_log('Erro ao buscar agendas compartilhadas pelo usuário: ' . $e->getMessage());
        return [];
    }
}
    
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
    

    public function countCompromissosByStatus($agendaId, $status) {
        try {
            $sql = "SELECT COUNT(*) FROM compromissos WHERE agenda_id = ? AND status = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$agendaId, $status]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao contar compromissos por status: ' . $e->getMessage());
            return 0;
        }
    }
}