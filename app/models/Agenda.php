<?php
class Agenda {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function __get($property) {
        if ($property === 'db') {
            return $this->db;
        }
        return null;
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
        
        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search)";
        }
        
        $sql .= " ORDER BY a.is_active DESC, a.title ASC";
        
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($search !== null && trim($search) !== '') {
            $searchParam = "%{$search}%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        }
        
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
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
        
        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (title LIKE :search OR description LIKE :search)";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($search !== null && trim($search) !== '') {
            $searchParam = "%{$search}%";
            $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
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
    error_log('=== INICIO AGENDA CREATE ===');
    error_log('Data recebido: ' . print_r($data, true));
    
    try {
        // Verificar se já existe agenda com o mesmo título para o usuário
        $existsQuery = "SELECT COUNT(*) FROM agendas WHERE user_id = :user_id AND title = :title";
        $existsStmt = $this->db->prepare($existsQuery);
        $existsStmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $existsStmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
        
        error_log('Executando query de verificação...');
        $existsStmt->execute();
        $exists = (int)$existsStmt->fetchColumn();
        error_log('Agendas existentes com mesmo nome: ' . $exists);
        
        if ($exists > 0) {
            $data['title'] = $data['title'] . ' (' . date('d/m/Y H:i') . ')';
            error_log('Título alterado para: ' . $data['title']);
        }
        
        // Gerar hash público se necessário
        $publicHash = '';
        if ($data['is_public']) {
            $publicHash = md5(uniqid(rand(), true));
            error_log('Hash público gerado: ' . $publicHash);
        }
        
        // Valores padrão
        $isActive = isset($data['is_active']) ? $data['is_active'] : 1;
        $minTimeBefore = isset($data['min_time_before']) ? $data['min_time_before'] : 0;
        
        error_log('Valores finais:');
        error_log('isActive: ' . $isActive);
        error_log('minTimeBefore: ' . $minTimeBefore);
        error_log('publicHash: [' . $publicHash . ']');
        
        // Query de inserção
        $query = "
            INSERT INTO agendas (user_id, title, description, is_public, color, created_at, public_hash, is_active, min_time_before)
            VALUES (:user_id, :title, :description, :is_public, :color, NOW(), :public_hash, :is_active, :min_time_before)
        ";
        
        error_log('Query: ' . $query);
        
        $stmt = $this->db->prepare($query);
        
        error_log('Fazendo bind dos parâmetros...');
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':is_public', $data['is_public'], PDO::PARAM_INT);
        $stmt->bindParam(':color', $data['color'], PDO::PARAM_STR);
        $stmt->bindParam(':public_hash', $publicHash, PDO::PARAM_STR);
        $stmt->bindParam(':is_active', $isActive, PDO::PARAM_INT);
        $stmt->bindParam(':min_time_before', $minTimeBefore, PDO::PARAM_INT);
        
        error_log('Executando INSERT...');
        error_log('Tentando inserir: user_id=' . $data['user_id'] . ', title=' . $data['title']);
        error_log('Valores bind: isActive=' . $isActive . ', minTimeBefore=' . $minTimeBefore);
        $executeResult = $stmt->execute();
        error_log('Execute result: ' . ($executeResult ? 'true' : 'false'));
        
        if ($executeResult) {
            $lastId = $this->db->lastInsertId();
            error_log('LastInsertId: ' . $lastId);
            error_log('=== SUCESSO AGENDA CREATE ===');
            return $lastId;
        } else {
            error_log('Execute retornou false');
            error_log('ErrorInfo: ' . print_r($stmt->errorInfo(), true));
            return false;
        }
        
    } catch (PDOException $e) {
        error_log('=== ERRO PDO AGENDA CREATE ===');
        error_log('Mensagem: ' . $e->getMessage());
        error_log('Código: ' . $e->getCode());
        error_log('Stack trace: ' . $e->getTraceAsString());
        error_log('Data array: ' . print_r($data, true));
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
        // CORRIGIDO: Verificação mais robusta de compromissos
        $statsQuery = "
            SELECT 
                SUM(CASE WHEN status = 'realizado' THEN 1 ELSE 0 END) as realizados,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = 'aguardando_aprovacao' THEN 1 ELSE 0 END) as aguardando_aprovacao,
                COUNT(*) as total
            FROM compromissos 
            WHERE agenda_id = :agenda_id
        ";
        
        $stmt = $this->db->prepare($statsQuery);
        $stmt->bindParam(':agenda_id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Se não há compromissos, pode excluir diretamente
        if (!$stats || $stats['total'] == 0) {
            return $this->deleteAgendaOnly($id);
        }
        
        // Verificar se há compromissos que impedem a exclusão
        if ($stats['realizados'] > 0 || $stats['cancelados'] > 0 || $stats['aguardando_aprovacao'] > 0) {
            error_log("Agenda {$id} não pode ser excluída: tem compromissos realizados/cancelados/aguardando");
            return false;
        }
        
        // Se chegou aqui, só há compromissos pendentes (que podem ser excluídos)
        return $this->deleteAgendaWithCompromissos($id);
        
    } catch (PDOException $e) {
        error_log('Erro ao verificar status dos compromissos da agenda ' . $id . ': ' . $e->getMessage());
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
    
private function deleteAgendaOnly($id) {
    try {
        // Verificar se já está em transação
        $wasInTransaction = $this->db->inTransaction();
        
        if (!$wasInTransaction) {
            $this->db->beginTransaction();
        }
        
        // 1. Excluir compartilhamentos
        $shareQuery = "DELETE FROM agenda_shares WHERE agenda_id = :id";
        $stmt = $this->db->prepare($shareQuery);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Falha ao excluir compartilhamentos");
        }
        
        // 2. Excluir a agenda
        $agendaQuery = "DELETE FROM agendas WHERE id = :id";
        $stmt = $this->db->prepare($agendaQuery);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Falha ao excluir agenda");
        }
        
        // Verificar se excluiu realmente
        if ($stmt->rowCount() === 0) {
            throw new Exception("Nenhuma agenda foi excluída - ID pode não existir");
        }
        
        if (!$wasInTransaction) {
            $this->db->commit();
        }
        
        error_log("Agenda {$id} excluída com sucesso (sem compromissos)");
        return true;
        
    } catch (Exception $e) {
        if (!$wasInTransaction && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
        error_log('Erro ao excluir agenda vazia ' . $id . ': ' . $e->getMessage());
        return false;
    }
}


    public function canBeDeleted($agendaId) {
        try {
            // Contar compromissos que impedem a exclusão (realizados e cancelados)
            $sql = "SELECT 
                        SUM(CASE WHEN status = 'realizado' THEN 1 ELSE 0 END) as realizados,
                        SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                        SUM(CASE WHEN status = 'aguardando_aprovacao' THEN 1 ELSE 0 END) as aguardando,
                        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                        COUNT(*) as total
                    FROM compromissos 
                    WHERE agenda_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$agendaId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || $result['total'] == 0) {
                // Sem compromissos = pode excluir
                return true;
            }
            
            // REGRA: Pode excluir apenas se:
            // - NÃO há compromissos realizados
            // - NÃO há compromissos cancelados  
            // - NÃO há compromissos aguardando aprovação
            // - Pode ter compromissos pendentes (serão excluídos junto)
            
            $canDelete = (
                (int)$result['realizados'] === 0 && 
                (int)$result['cancelados'] === 0 && 
                (int)$result['aguardando'] === 0
            );
            
            return $canDelete;
            
        } catch (PDOException $e) {
            error_log('Erro ao verificar se agenda pode ser excluída: ' . $e->getMessage());
            return false;
        }
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

public function searchPublicAgendas($search, $page = 1, $perPage = 10, $userId = null) {
    try {
        $offset = ($page - 1) * $perPage;
        $searchParam = "%{$search}%";
        
        $sql = "SELECT a.*, u.name as owner_name
                FROM agendas a
                INNER JOIN users u ON a.user_id = u.id
                WHERE a.is_public = 1 
                  AND a.is_active = 1
                  AND (
                      a.title LIKE :search1 OR 
                      a.description LIKE :search2 OR 
                      u.name LIKE :search3
                  )";
        
        if ($userId) {
            $sql .= " AND a.user_id != :user_id
                      AND a.id NOT IN (
                          SELECT agenda_id FROM agenda_shares WHERE user_id = :user_id2
                      )";
        }
        
        $sql .= " ORDER BY a.title
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':search1', $searchParam, PDO::PARAM_STR);
        $stmt->bindParam(':search2', $searchParam, PDO::PARAM_STR);
        $stmt->bindParam(':search3', $searchParam, PDO::PARAM_STR);
        
        if ($userId) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id2', $userId, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erro ao buscar agendas públicas: ' . $e->getMessage());
        return [];
    }
}

public function countPublicAgendasWithSearch($search, $userId = null) {
    try {
        $searchParam = "%{$search}%";
        
        $sql = "SELECT COUNT(*) as total 
                FROM agendas a
                INNER JOIN users u ON a.user_id = u.id
                WHERE a.is_public = 1 
                  AND a.is_active = 1
                  AND (
                      a.title LIKE :search1 OR 
                      a.description LIKE :search2 OR 
                      u.name LIKE :search3
                  )";

        if ($userId) {
            $sql .= " AND a.user_id != :user_id
                      AND a.id NOT IN (
                          SELECT agenda_id FROM agenda_shares WHERE user_id = :user_id2
                      )";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':search1', $searchParam, PDO::PARAM_STR);
        $stmt->bindParam(':search2', $searchParam, PDO::PARAM_STR);
        $stmt->bindParam(':search3', $searchParam, PDO::PARAM_STR);
        
        if ($userId) {
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id2', $userId, PDO::PARAM_INT);
        }
        
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