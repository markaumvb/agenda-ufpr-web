<?php
// Arquivo: app/models/Compromisso.php

/**
 * Modelo para gerenciar os dados de compromissos
 */
class Compromisso {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtém todos os compromissos de uma agenda em um intervalo de datas
     * 
     * @param int $agendaId ID da agenda
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @return array Lista de compromissos
     */
    public function getByAgendaAndDateRange($agendaId, $startDate, $endDate) {
        try {
            $query = "
                SELECT * 
                FROM compromissos 
                WHERE agenda_id = :agenda_id 
                AND (
                    (DATE(start_datetime) BETWEEN :start_date AND :end_date)
                    OR (DATE(end_datetime) BETWEEN :start_date AND :end_date)
                    OR (DATE(start_datetime) <= :start_date AND DATE(end_datetime) >= :end_date)
                )
                ORDER BY start_datetime
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar compromissos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todos os compromissos de uma agenda
     * 
     * @param int $agendaId ID da agenda
     * @return array Lista de compromissos
     */
    public function getAllByAgenda($agendaId) {
        try {
            $query = "SELECT * FROM compromissos WHERE agenda_id = :agenda_id ORDER BY start_datetime";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Erro ao buscar compromissos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém um compromisso específico
     * 
     * @param int $id ID do compromisso
     * @return array|bool Dados do compromisso ou false se não encontrado
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM compromissos WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Erro ao buscar compromisso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria um novo compromisso
     * 
     * @param array $data Dados do compromisso
     * @return int|bool ID do compromisso criado ou false em caso de erro
     */
    public function create($data) {
        try {
            $query = "
                INSERT INTO compromissos (
                    agenda_id, title, description, start_datetime, end_datetime, 
                    location, repeat_type, repeat_until, repeat_days, status,
                    created_at
                ) VALUES (
                    :agenda_id, :title, :description, :start_datetime, :end_datetime, 
                    :location, :repeat_type, :repeat_until, :repeat_days, :status,
                    NOW()
                )
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $data['agenda_id'], PDO::PARAM_INT);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':start_datetime', $data['start_datetime'], PDO::PARAM_STR);
            $stmt->bindParam(':end_datetime', $data['end_datetime'], PDO::PARAM_STR);
            $stmt->bindParam(':location', $data['location'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_type', $data['repeat_type'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_until', $data['repeat_until'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_days', $data['repeat_days'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Erro ao criar compromisso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza um compromisso existente
     * 
     * @param int $id ID do compromisso
     * @param array $data Dados a serem atualizados
     * @return bool Resultado da operação
     */
    public function update($id, $data) {
        try {
            $query = "
                UPDATE compromissos SET
                    title = :title,
                    description = :description,
                    start_datetime = :start_datetime,
                    end_datetime = :end_datetime,
                    location = :location,
                    repeat_type = :repeat_type,
                    repeat_until = :repeat_until,
                    repeat_days = :repeat_days,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':start_datetime', $data['start_datetime'], PDO::PARAM_STR);
            $stmt->bindParam(':end_datetime', $data['end_datetime'], PDO::PARAM_STR);
            $stmt->bindParam(':location', $data['location'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_type', $data['repeat_type'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_until', $data['repeat_until'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_days', $data['repeat_days'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao atualizar compromisso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui um compromisso
     * 
     * @param int $id ID do compromisso
     * @return bool Resultado da operação
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM compromissos WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao excluir compromisso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se há conflito de horário para um novo compromisso
     * 
     * @param int $agendaId ID da agenda
     * @param string $startDatetime Data e hora de início (formato Y-m-d H:i:s)
     * @param string $endDatetime Data e hora de término (formato Y-m-d H:i:s)
     * @param int $excludeId ID do compromisso a ser excluído da verificação (opcional)
     * @return bool True se houver conflito, false caso contrário
     */
    public function hasTimeConflict($agendaId, $startDatetime, $endDatetime, $excludeId = null) {
        try {
            $query = "
                SELECT COUNT(*) FROM compromissos 
                WHERE agenda_id = :agenda_id
                AND (
                    (start_datetime < :end_datetime AND end_datetime > :start_datetime)
                )
            ";
            
            // Se fornecido um ID para exclusão, adiciona à query
            if ($excludeId !== null) {
                $query .= " AND id != :exclude_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':start_datetime', $startDatetime, PDO::PARAM_STR);
            $stmt->bindParam(':end_datetime', $endDatetime, PDO::PARAM_STR);
            
            if ($excludeId !== null) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar conflito de horário: ' . $e->getMessage());
            return false;
        }
    }
}