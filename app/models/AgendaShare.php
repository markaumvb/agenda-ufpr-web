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
}