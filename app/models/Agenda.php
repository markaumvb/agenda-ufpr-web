<?php
class Agenda {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function countAllByUser($userId, $search = null) {
        try {
            $query = "SELECT COUNT(*) FROM agendas WHERE user_id = :user_id";
            $params = ['user_id' => $userId];
            
            if ($search) {
                $query .= " AND (title LIKE :search OR description LIKE :search)";
                $params['search'] = "%{$search}%";
            }
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao contar agendas: ' . $e->getMessage());
            return 0;
        }
    }

    public function getAllByUser($userId, $search = null, $includeInactive = false, $page = 1, $perPage = 12) {
        try {
            $sql = "SELECT DISTINCT a.* FROM agendas a WHERE a.user_id = :user_id";
            
            if (!$includeInactive) {
                $sql .= " AND a.is_active = 1";
            }
            
            $params = [':user_id' => $userId];
            if ($search !== null && $search !== '') {
                $sql .= " AND (a.title LIKE :search OR a.description LIKE :search)";
                $params[':search'] = "%{$search}%";
            }
            
            $sql .= " ORDER BY a.is_active DESC, a.title ASC";
            
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao buscar agendas do usuário: ' . $e->getMessage());
            return [];
        }
    }

    public function countByUser($userId, $search = null, $includeInactive = false) {
        try {
            $sql = "SELECT COUNT(*) FROM agendas WHERE user_id = :user_id";
            
            if (!$includeInactive) {
                $sql .= " AND is_active = 1";
            }
            
            $params = [':user_id' => $userId];
            if ($search !== null && $search !== '') {
                $sql .= " AND (title LIKE :search OR description LIKE :search)";
                $params[':search'] = "%{$search}%";
            }
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao contar agendas do usuário: ' . $e->getMessage());
            return 0;
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT * FROM agendas WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $agenda = $stmt->fetch();
            
            if ($agenda) {
                $agenda['is_active'] = (bool)(int)$agenda['is_active'];
            }
            
            return $agenda;
        } catch (PDOException $e) {
            error_log('Erro ao buscar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
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
    
    public function create($data) {
        try {
            $existsQuery = "SELECT COUNT(*) FROM agendas WHERE user_id = :user_id AND title = :title";
            $existsStmt = $this->db->prepare($existsQuery);
            $existsStmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $existsStmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $existsStmt->execute();
            
            if ((int)$existsStmt->fetchColumn() > 0) {
                $data['title'] = $data['title'] . ' (' . date('d/m/Y H:i') . ')';
            }
            
            $publicHash = '';
            if ($data['is_public']) {
                $publicHash = md5(uniqid(rand(), true));
            }
            
            $isActive = isset($data['is_active']) ? $data['is_active'] : 1;
            $minTimeBefore = isset($data['min_time_before']) ? $data['min_time_before'] : 0;
            
            $query = "
                INSERT INTO agendas (user_id, title, description, is_public, color, created_at, public_hash, is_active, min_time_before)
                VALUES (:user_id, :title, :description, :is_public, :color, NOW(), :public_hash, :is_active, :min_time_before)
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $data['is_public'], PDO::PARAM_INT);
            $stmt->bindParam(':color', $data['color'], PDO::PARAM_STR);
            $stmt->bindParam(':public_hash', $publicHash, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $isActive, PDO::PARAM_INT);
            $stmt->bindParam(':min_time_before', $minTimeBefore, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao criar agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        try {
            $currentAgenda = $this->getById($id);
            if (!$currentAgenda) {
                return false;
            }
            
            $publicHash = $currentAgenda['public_hash'];
            if ($data['is_public'] && empty($publicHash)) {
                $publicHash = md5(uniqid(rand(), true));
            }
            
            $query = "
                UPDATE agendas
                SET title = :title,
                    description = :description,
                    is_public = :is_public,
                    color = :color,
                    public_hash = :public_hash,
                    is_active = :is_active,
                    min_time_before = :min_time_before,
                    updated_at = NOW()
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':is_public', $data['is_public'], PDO::PARAM_INT);
            $stmt->bindParam(':color', $data['color'], PDO::PARAM_STR);
            $stmt->bindParam(':public_hash', $publicHash, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $data['is_active'], PDO::PARAM_INT);
            $stmt->bindParam(':min_time_before', $data['min_time_before'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao atualizar agenda: ' . $e->getMessage());
            return false;
        }
    }

    public function toggleActive($id, $status) {
        try {
            $query = "UPDATE agendas SET is_active = :is_active, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':is_active', $status, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao alterar status de ativação da agenda: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $query = "
                SELECT COUNT(*) FROM compromissos 
                WHERE agenda_id = :agenda_id 
                AND (status = 'pendente' OR status = 'aguardando_aprovacao')
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ((int)$stmt->fetchColumn() > 0) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            $query = "DELETE FROM agenda_shares WHERE agenda_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $query = "DELETE FROM compromissos WHERE agenda_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $query = "DELETE FROM agendas WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao excluir agenda: ' . $e->getMessage());
            return false;
        }
    }
    
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
    
    public function canBeDeleted($agendaId) {
        $sql = "SELECT COUNT(*) as count FROM compromissos 
                WHERE agenda_id = ? AND status IN ('realizado', 'cancelado')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$agendaId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['count'] === 0;
    }

    public function updateStatus($agendaId, $isActive) {
        $sql = "UPDATE agendas SET is_active = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$isActive ? 1 : 0, $agendaId]);
    }

    public function getPublicAgendas($userId, $activeOnly = true, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT a.*, 
               u.name as owner_name,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'pendente') as pendentes,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'realizado') as realizados,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'cancelado') as cancelados,
               (SELECT COUNT(*) FROM compromissos WHERE agenda_id = a.id AND status = 'aguardando_aprovacao') as aguardando_aprovacao
                FROM agendas a
                INNER JOIN users u ON a.user_id = u.id
                WHERE a.is_public = 1 
                  AND a.user_id != :user_id
                  AND a.id NOT IN (
                      SELECT agenda_id FROM agenda_shares WHERE user_id = :user_id2
                  )";
        
        if ($activeOnly) {
            $sql .= " AND a.is_active = 1";
        }
        
        $sql .= " ORDER BY a.title
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id2', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $agendas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['is_owner'] = false;
            $row['can_edit'] = false;
            $row['compromissos'] = [
                'pendentes' => $row['pendentes'],
                'realizados' => $row['realizados'],
                'cancelados' => $row['cancelados'],
                'aguardando_aprovacao' => $row['aguardando_aprovacao']
            ];
            $agendas[] = $row;
        }
        
        return $agendas;
    }

    public function countPublicAgendas($userId, $activeOnly = true) {
        $sql = "SELECT COUNT(*) as total 
                FROM agendas a
                WHERE a.is_public = 1 
                  AND a.user_id != :user_id
                  AND a.id NOT IN (
                      SELECT agenda_id FROM agenda_shares WHERE user_id = :user_id2
                  )";
        
        if ($activeOnly) {
            $sql .= " AND a.is_active = 1";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id2', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    public function getAllPublicActive() {
        try {
            $query = "
                SELECT a.*, u.name as owner_name
                FROM agendas a
                JOIN users u ON a.user_id = u.id
                WHERE a.is_public = 1 
                AND a.is_active = 1
                ORDER BY a.title
            ";
            
            $stmt = $this->db->query($query);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao obter agendas públicas: ' . $e->getMessage());
            return [];
        }
    }

    // CORREÇÃO PRINCIPAL: Método de busca SEM condição de public_hash
    public function searchPublicAgendas($search, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT a.*, u.name as owner_name
                    FROM agendas a
                    INNER JOIN users u ON a.user_id = u.id
                    WHERE a.is_public = 1 
                      AND a.is_active = 1
                      AND (
                          a.title LIKE :search OR 
                          a.description LIKE :search OR 
                          u.name LIKE :search
                      )
                    ORDER BY a.title
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $searchParam = "%{$search}%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao buscar agendas públicas: ' . $e->getMessage());
            return [];
        }
    }

    // CORREÇÃO PRINCIPAL: Método de contagem SEM condição de public_hash
    public function countPublicAgendasWithSearch($search) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM agendas a
                    INNER JOIN users u ON a.user_id = u.id
                    WHERE a.is_public = 1 
                      AND a.is_active = 1
                      AND (
                          a.title LIKE :search OR 
                          a.description LIKE :search OR 
                          u.name LIKE :search
                      )";
            
            $stmt = $this->db->prepare($sql);
            $searchParam = "%{$search}%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return intval($row['total']);
        } catch (PDOException $e) {
            error_log('Erro ao contar agendas públicas: ' . $e->getMessage());
            return 0;
        }
    }

    public function getAllPublicActivePaginated($page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $query = "
                SELECT a.*, u.name as owner_name
                FROM agendas a
                JOIN users u ON a.user_id = u.id
                WHERE a.is_public = 1 
                AND a.is_active = 1
                ORDER BY a.title
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao obter agendas públicas paginadas: ' . $e->getMessage());
            return [];
        }
    }

    public function countAllPublicActive() {
        try {
            $query = "
                SELECT COUNT(*) as total
                FROM agendas a
                WHERE a.is_public = 1 
                AND a.is_active = 1
            ";
            
            $stmt = $this->db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return intval($result['total']);
        } catch (PDOException $e) {
            error_log('Erro ao contar agendas públicas: ' . $e->getMessage());
            return 0;
        }
    }

    // Métodos mantidos para compatibilidade
    public function getAllAccessibleByUser($userId, $search = null, $includeInactive = false) {
        return $this->getAllByUser($userId, $search, $includeInactive);
    }
    
    public function countAllAccessibleByUser($userId, $search = null) {
        return $this->countByUser($userId, $search, true);
    }
    
    public function getAllAccessibleByUserPaginated($userId, $offset = 0, $limit = 10, $search = null) {
        $page = floor($offset / $limit) + 1;
        return $this->getAllByUser($userId, $search, true, $page, $limit);
    }
    
    public function checkUserAccess($agendaId, $userId) {
        try {
            if ($this->belongsToUser($agendaId, $userId)) {
                return true;
            }
            
            $agenda = $this->getById($agendaId);
            if ($agenda && $agenda['is_public']) {
                return true;
            }
            
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
    
    public function canUserEdit($agendaId, $userId) {
        try {
            if ($this->belongsToUser($agendaId, $userId)) {
                return true;
            }
            
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
    
    public function getByPublicHash($hash) {
        try {
            $query = "SELECT * FROM agendas WHERE public_hash = :hash AND is_active = 1 LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
            $stmt->execute();
            
            $agenda = $stmt->fetch();
            
            if ($agenda) {
                $agenda['is_active'] = (bool)(int)$agenda['is_active'];
                $agenda['is_public'] = (bool)(int)$agenda['is_public'];
            }
            
            return $agenda;
        } catch (PDOException $e) {
            error_log('Erro ao buscar agenda por hash: ' . $e->getMessage());
            return false;
        }
    }
}