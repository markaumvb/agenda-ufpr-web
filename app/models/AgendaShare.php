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

/**
 * Método simplificado para obter agendas compartilhadas com o usuário
 */
public function getSharedWithUser($userId, $activeOnly = true, $page = 1, $perPage = 10, $search = null) {
    try {
        // Preparar a base da consulta SQL
        $sql = "SELECT a.id, a.title, a.description, a.color, a.is_public, a.is_active, 
                       s.can_edit, u.name as owner_name, u.id as owner_id
                FROM agenda_shares s
                JOIN agendas a ON s.agenda_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE s.user_id = :user_id";
        
        // Adicionar filtro para agendas ativas
        $params = [':user_id' => $userId];
        
        if ($activeOnly) {
            $sql .= " AND a.is_active = 1";
        }
        
        // Adicionar filtro de busca
        if ($search) {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search OR u.name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        // Ordenar e aplicar limites de paginação
        $sql .= " ORDER BY a.title";
        
        if ($perPage > 0) {
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = (int)$perPage;
            $params[':offset'] = (int)$offset;
        }
        
        // Preparar e executar a consulta
        $stmt = $this->db->prepare($sql);
        
        // Vincular parâmetros
        foreach ($params as $param => $value) {
            if (in_array($param, [':limit', ':offset'])) {
                $stmt->bindValue($param, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($param, $value);
            }
        }
        
        $stmt->execute();
        
        // Processar resultados
        $agendas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Adicionar dados de compromissos (usando contador direto)
            $compromissosSql = "SELECT 
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = 'realizado' THEN 1 ELSE 0 END) as realizados,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN status = 'aguardando_aprovacao' THEN 1 ELSE 0 END) as aguardando_aprovacao
                FROM compromissos
                WHERE agenda_id = ?";
            
            $compStmt = $this->db->prepare($compromissosSql);
            $compStmt->execute([$row['id']]);
            $contadores = $compStmt->fetch(PDO::FETCH_ASSOC);
            
            $row['is_owner'] = false; // Não é o dono, é um compartilhamento
            $row['compromissos'] = [
                'pendentes' => (int)$contadores['pendentes'],
                'realizados' => (int)$contadores['realizados'],
                'cancelados' => (int)$contadores['cancelados'],
                'aguardando_aprovacao' => (int)$contadores['aguardando_aprovacao']
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
    try {
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
        
        $total = intval($row['total']);
        error_log("Total de agendas compartilhadas com o usuário $userId: $total");
        
        return $total;
    } catch (PDOException $e) {
        error_log('Erro ao contar agendas compartilhadas com o usuário: ' . $e->getMessage());
        return 0;
    }
}

public function getAgendasSharedByUser($userId, $search = null) {
    try {
        // Consulta para encontrar agendas do usuário que têm compartilhamentos
        $sql = "SELECT DISTINCT a.id, a.title, a.description, a.color, a.is_public, a.is_active,
                       (SELECT name FROM users WHERE id = a.user_id) as owner_name
                FROM agendas a
                WHERE a.user_id = :userId 
                AND EXISTS (
                    SELECT 1 FROM agenda_shares 
                    WHERE agenda_id = a.id
                )";
        
        // Adicionar filtro de busca
        $params = [':userId' => $userId];
        
        if ($search) {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $sql .= " ORDER BY a.title";
        
        // Preparar e executar a consulta
        $stmt = $this->db->prepare($sql);
        
        // Vincular parâmetros
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        
        // Processar resultados
        $agendas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Adicionar dados de compromissos
            $compromissosSql = "SELECT 
                SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = 'realizado' THEN 1 ELSE 0 END) as realizados,
                SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN status = 'aguardando_aprovacao' THEN 1 ELSE 0 END) as aguardando_aprovacao
                FROM compromissos
                WHERE agenda_id = ?";
                
            $compStmt = $this->db->prepare($compromissosSql);
            $compStmt->execute([$row['id']]);
            $contadores = $compStmt->fetch(PDO::FETCH_ASSOC);
            
            // Adicionar dados dos usuários com quem esta agenda foi compartilhada
            $sharesSql = "SELECT u.name 
                          FROM agenda_shares s
                          JOIN users u ON s.user_id = u.id
                          WHERE s.agenda_id = ?";
            
            $sharesStmt = $this->db->prepare($sharesSql);
            $sharesStmt->execute([$row['id']]);
            $shares = $sharesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $row['is_owner'] = true; // É o dono
            $row['can_edit'] = true; // Dono sempre pode editar
            $row['compromissos'] = [
                'pendentes' => (int)$contadores['pendentes'],
                'realizados' => (int)$contadores['realizados'],
                'cancelados' => (int)$contadores['cancelados'],
                'aguardando_aprovacao' => (int)$contadores['aguardando_aprovacao']
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

public function diagnosticarCompartilhamentos($userId) {
    $resultado = [
        'compartilhados_comigo' => [],
        'compartilhados_por_mim' => [],
        'user_id_atual' => $userId
    ];
    
    try {
        // 1. Verificar agendas compartilhadas COM o usuário (simplificado ao máximo)
        $sql = "SELECT s.*, a.title, a.is_active, u.name as owner_name 
                FROM agenda_shares s
                JOIN agendas a ON s.agenda_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE s.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['compartilhados_comigo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Verificar agendas onde o usuário é o dono e que foram compartilhadas com outros
        $sql = "SELECT a.id, a.title, a.is_active, s.user_id as shared_with, u.name as shared_with_name  
                FROM agendas a
                JOIN agenda_shares s ON a.id = s.agenda_id
                JOIN users u ON s.user_id = u.id
                WHERE a.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['compartilhados_por_mim'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Verificar se o usuário tem agendas
        $sql = "SELECT COUNT(*) FROM agendas WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['total_agendas_proprias'] = $stmt->fetchColumn();
        
        // 4. Verificar se o usuário está na tabela de usuários
        $sql = "SELECT id, username, name FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['dados_usuario'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado;
    } catch (PDOException $e) {
        error_log('Erro no diagnóstico de compartilhamentos: ' . $e->getMessage());
        $resultado['erro'] = $e->getMessage();
        return $resultado;
    }
}
}