<?php
// app/models/Notification.php

/**
 * Modelo para gerenciar notificações do sistema
 */
class Notification {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria uma nova notificação
     * 
     * @param array $data Dados da notificação
     * @return int|bool ID da notificação criada ou false em caso de erro
     */
    public function create($data) {
        try {
            $query = "
                INSERT INTO notifications (user_id, compromisso_id, message, is_read, created_at)
                VALUES (:user_id, :compromisso_id, :message, :is_read, NOW())
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':compromisso_id', $data['compromisso_id'], PDO::PARAM_INT);
            $stmt->bindParam(':message', $data['message'], PDO::PARAM_STR);
            $stmt->bindParam(':is_read', $data['is_read'], PDO::PARAM_BOOL);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém notificações não lidas de um usuário
     * 
     * @param int $userId ID do usuário
     * @param int $limit Limite de notificações (opcional)
     * @return array Lista de notificações
     */
    public function getUnreadByUser($userId, $limit = 10) {
        try {
            $query = "
                SELECT n.*, c.title as compromisso_title, c.start_datetime, c.agenda_id,
                       a.title as agenda_title
                FROM notifications n
                LEFT JOIN compromissos c ON n.compromisso_id = c.id
                LEFT JOIN agendas a ON c.agenda_id = a.id
                WHERE n.user_id = :user_id AND n.is_read = 0
                ORDER BY n.created_at DESC
                LIMIT :limit
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar notificações não lidas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todas as notificações de um usuário com paginação
     * 
     * @param int $userId ID do usuário
     * @param int $offset Offset para paginação
     * @param int $limit Limite de notificações por página
     * @return array Lista de notificações
     */
    public function getAllByUser($userId, $offset = 0, $limit = 10) {
        try {
            $query = "
                SELECT n.*, c.title as compromisso_title, c.start_datetime, c.agenda_id,
                       a.title as agenda_title
                FROM notifications n
                LEFT JOIN compromissos c ON n.compromisso_id = c.id
                LEFT JOIN agendas a ON c.agenda_id = a.id
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC
                LIMIT :offset, :limit
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar notificações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta o total de notificações de um usuário
     * 
     * @param int $userId ID do usuário
     * @param bool $onlyUnread Se deve contar apenas não lidas
     * @return int Total de notificações
     */
    public function countByUser($userId, $onlyUnread = false) {
        try {
            $query = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id";
            
            if ($onlyUnread) {
                $query .= " AND is_read = 0";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao contar notificações: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Marca uma notificação como lida
     * 
     * @param int $id ID da notificação
     * @param int $userId ID do usuário (para verificação)
     * @return bool Resultado da operação
     */
    public function markAsRead($id, $userId) {
        try {
            $query = "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao marcar notificação como lida: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca todas as notificações de um usuário como lidas
     * 
     * @param int $userId ID do usuário
     * @return bool Resultado da operação
     */
    public function markAllAsRead($userId) {
        try {
            $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao marcar todas notificações como lidas: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui uma notificação
     * 
     * @param int $id ID da notificação
     * @param int $userId ID do usuário (para verificação)
     * @return bool Resultado da operação
     */
    public function delete($id, $userId) {
        try {
            $query = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao excluir notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui notificações antigas
     * 
     * @param int $days Excluir notificações com mais de X dias
     * @return bool Resultado da operação
     */
    public function deleteOld($days = 30) {
        try {
            $query = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':days', $days, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao excluir notificações antigas: ' . $e->getMessage());
            return false;
        }
    }
}