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
     * Cria um novo compromisso com suporte a recorrência
     * 
     * @param array $data Dados do compromisso
     * @return int|bool ID do compromisso criado ou false em caso de erro
     */
    public function create($data) {
        try {
            // Verificar se é um evento recorrente
            $isRecurring = $data['repeat_type'] !== 'none';
            
            // Se não for recorrente, usar o método padrão de criação
            if (!$isRecurring) {
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
            }
            
            // Para eventos recorrentes, iniciar transação
            $this->db->beginTransaction();
            
            // Gerar ID de grupo para eventos recorrentes
            $groupId = $this->generateUUID();
            
            // Inserir o evento principal
            $query = "
                INSERT INTO compromissos (
                    agenda_id, title, description, start_datetime, end_datetime, 
                    location, repeat_type, repeat_until, repeat_days, status,
                    created_at, group_id, is_recurring
                ) VALUES (
                    :agenda_id, :title, :description, :start_datetime, :end_datetime, 
                    :location, :repeat_type, :repeat_until, :repeat_days, :status,
                    NOW(), :group_id, 1
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
            $stmt->bindParam(':group_id', $groupId, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            $parentId = $this->db->lastInsertId();
            
            // Criar eventos recorrentes baseados no tipo de repetição
            $occurrences = $this->calculateOccurrences(
                $data['repeat_type'],
                $data['start_datetime'],
                $data['end_datetime'],
                $data['repeat_until'],
                $data['repeat_days']
            );
            
            // Pular a primeira ocorrência pois já foi criada como evento principal
            array_shift($occurrences);
            
            // Inserir cada ocorrência
            foreach ($occurrences as $occurrence) {
                $query = "
                    INSERT INTO compromissos (
                        agenda_id, title, description, start_datetime, end_datetime, 
                        location, repeat_type, repeat_until, repeat_days, status,
                        created_at, group_id, is_recurring, parent_id
                    ) VALUES (
                        :agenda_id, :title, :description, :start_datetime, :end_datetime, 
                        :location, :repeat_type, :repeat_until, :repeat_days, :status,
                        NOW(), :group_id, 1, :parent_id
                    )
                ";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':agenda_id', $data['agenda_id'], PDO::PARAM_INT);
                $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
                $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
                $stmt->bindParam(':start_datetime', $occurrence['start'], PDO::PARAM_STR);
                $stmt->bindParam(':end_datetime', $occurrence['end'], PDO::PARAM_STR);
                $stmt->bindParam(':location', $data['location'], PDO::PARAM_STR);
                $stmt->bindParam(':repeat_type', $data['repeat_type'], PDO::PARAM_STR);
                $stmt->bindParam(':repeat_until', $data['repeat_until'], PDO::PARAM_STR);
                $stmt->bindParam(':repeat_days', $data['repeat_days'], PDO::PARAM_STR);
                $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
                $stmt->bindParam(':group_id', $groupId, PDO::PARAM_STR);
                $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
                
                if (!$stmt->execute()) {
                    $this->db->rollBack();
                    return false;
                }
            }
            
            // Se chegou até aqui, confirmar a transação
            $this->db->commit();
            
            return $parentId;
            
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao criar compromisso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza um compromisso existente
     * 
     * @param int $id ID do compromisso
     * @param array $data Dados a serem atualizados
     * @param bool $updateFutureOccurrences Se deve atualizar ocorrências futuras
     * @return bool Resultado da operação
     */
    public function update($id, $data, $updateFutureOccurrences = false) {
        try {
            // Obter o compromisso atual
            $compromisso = $this->getById($id);
            
            if (!$compromisso) {
                return false;
            }
            
            // Se não for parte de um grupo ou não quiser atualizar as ocorrências futuras,
            // atualizar apenas o evento específico
            if (empty($compromisso['group_id']) || !$updateFutureOccurrences) {
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
            }
            
            // Iniciar transação para atualizar todas as ocorrências futuras
            $this->db->beginTransaction();
            
            // Atualizar o evento atual
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
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // Atualizar todas as ocorrências futuras do mesmo grupo
            $query = "
                UPDATE compromissos
                SET title = :title,
                    description = :description,
                    location = :location,
                    repeat_type = :repeat_type,
                    repeat_until = :repeat_until,
                    repeat_days = :repeat_days,
                    status = :status,
                    updated_at = NOW()
                WHERE group_id = :group_id
                AND start_datetime > :current_time
                AND id != :id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $data['title'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':location', $data['location'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_type', $data['repeat_type'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_until', $data['repeat_until'], PDO::PARAM_STR);
            $stmt->bindParam(':repeat_days', $data['repeat_days'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
            $stmt->bindParam(':current_time', $compromisso['start_datetime'], PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // Confirmar transação
            $this->db->commit();
            
            return true;
            
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao atualizar compromisso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exclui um compromisso
     * 
     * @param int $id ID do compromisso
     * @param bool $deleteFutureOccurrences Se deve excluir ocorrências futuras
     * @return bool Resultado da operação
     */
    public function delete($id, $deleteFutureOccurrences = false) {
        try {
            // Obter o compromisso
            $compromisso = $this->getById($id);
            
            if (!$compromisso) {
                return false;
            }
            
            // Se não for parte de um grupo ou não quiser excluir as ocorrências futuras,
            // excluir apenas o evento específico
            if (empty($compromisso['group_id']) || !$deleteFutureOccurrences) {
                $query = "DELETE FROM compromissos WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                return $stmt->execute();
            }
            
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Excluir o evento atual
            $query = "DELETE FROM compromissos WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // Excluir todas as ocorrências futuras do mesmo grupo
            $query = "
                DELETE FROM compromissos
                WHERE group_id = :group_id
                AND start_datetime >= :current_time
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
            $stmt->bindParam(':current_time', $compromisso['start_datetime'], PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // Confirmar transação
            $this->db->commit();
            
            return true;
            
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao excluir compromisso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancela todas as ocorrências futuras de um evento recorrente
     * 
     * @param int $id ID do compromisso
     * @return bool Resultado da operação
     */
    public function cancelFutureOccurrences($id) {
        try {
            // Obter o compromisso
            $compromisso = $this->getById($id);
            
            if (!$compromisso || empty($compromisso['group_id'])) {
                return false;
            }
            
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Atualizar o status do evento atual para cancelado
            $query = "
                UPDATE compromissos
                SET status = 'cancelado', updated_at = NOW()
                WHERE id = :id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // Atualizar o status de todas as ocorrências futuras para cancelado
            $query = "
                UPDATE compromissos
                SET status = 'cancelado', updated_at = NOW()
                WHERE group_id = :group_id
                AND start_datetime > :current_time
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
            $stmt->bindParam(':current_time', $compromisso['start_datetime'], PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            // Confirmar transação
            $this->db->commit();
            
            return true;
            
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao cancelar ocorrências futuras: ' . $e->getMessage());
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
                AND status != 'cancelado'
                AND (
                    (start_datetime < :end_datetime AND end_datetime > :start_datetime)
                )
            ";
            
            // Se fornecido um ID para exclusão, adiciona à query
            if ($excludeId !== null) {
                $query .= " AND id != :exclude_id";
                
                // Também excluir do mesmo grupo se for um evento recorrente
                $compromisso = $this->getById($excludeId);
                if ($compromisso && !empty($compromisso['group_id'])) {
                    $query .= " AND (group_id IS NULL OR group_id != :group_id)";
                }
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
            $stmt->bindParam(':start_datetime', $startDatetime, PDO::PARAM_STR);
            $stmt->bindParam(':end_datetime', $endDatetime, PDO::PARAM_STR);
            
            if ($excludeId !== null) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
                
                if (isset($compromisso) && !empty($compromisso['group_id'])) {
                    $stmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao verificar conflito de horário: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcula todas as ocorrências de um evento recorrente
     * 
     * @param string $repeatType Tipo de recorrência
     * @param string $startDatetime Data e hora inicial do evento
     * @param string $endDatetime Data e hora final do evento
     * @param string $repeatUntil Data limite da recorrência
     * @param string $repeatDays Dias específicos para recorrência (para tipo specific_days)
     * @return array Lista de ocorrências com datas de início e fim
     */
    private function calculateOccurrences($repeatType, $startDatetime, $endDatetime, $repeatUntil, $repeatDays) {
        // Converter strings para objetos DateTime
        $startDate = new DateTime($startDatetime);
        $endDate = new DateTime($endDatetime);
        $untilDate = $repeatUntil ? new DateTime($repeatUntil) : null;
        
        // Calcular a duração do evento
        $duration = $startDate->diff($endDate);
        
        // Armazenar todas as ocorrências
        $occurrences = [];
        
        // Adicionar a primeira ocorrência (o evento original)
        $occurrences[] = [
            'start' => $startDatetime,
            'end' => $endDatetime
        ];
        
        // Definir a data atual para iteração
        $currentDate = clone $startDate;
        
        // Para cada tipo de recorrência
        switch ($repeatType) {
            case 'daily':
                // Avançar um dia de cada vez
                $currentDate->modify('+1 day');
                
                while ($untilDate === null || $currentDate <= $untilDate) {
                    // Dias da semana: 0 (Domingo) a 6 (Sábado)
                    $dayOfWeek = (int)$currentDate->format('w');
                    
                    // Pular fins de semana (sábado e domingo)
                    if ($dayOfWeek === 0 || $dayOfWeek === 6) {
                        $currentDate->modify('+1 day');
                        continue;
                    }
                    
                    // Calcular nova data de início
                    $newStart = clone $currentDate;
                    $newStart->setTime($startDate->format('H'), $startDate->format('i'), $startDate->format('s'));
                    
                    // Calcular nova data de término
                    $newEnd = clone $newStart;
                    $newEnd->add($duration);
                    
                    // Adicionar à lista de ocorrências
                    $occurrences[] = [
                        'start' => $newStart->format('Y-m-d H:i:s'),
                        'end' => $newEnd->format('Y-m-d H:i:s')
                    ];
                    
                    // Avançar para o próximo dia
                    $currentDate->modify('+1 day');
                }
                break;
                
            case 'weekly':
                // Armazenar o dia da semana do evento original
                $originalDayOfWeek = (int)$startDate->format('w');
                
                // Avançar uma semana
                $currentDate->modify('+1 week');
                
                while ($untilDate === null || $currentDate <= $untilDate) {
                    // Calcular nova data de início
                    $newStart = clone $currentDate;
                    $newStart->setTime($startDate->format('H'), $startDate->format('i'), $startDate->format('s'));
                    
                    // Calcular nova data de término
                    $newEnd = clone $newStart;
                    $newEnd->add($duration);
                    
                    // Adicionar à lista de ocorrências
                    $occurrences[] = [
                        'start' => $newStart->format('Y-m-d H:i:s'),
                        'end' => $newEnd->format('Y-m-d H:i:s')
                    ];
                    
                    // Avançar para a próxima semana
                    $currentDate->modify('+1 week');
                }
                break;
                
            case 'specific_days':
                // Se não houver dias específicos, retornar apenas a primeira ocorrência
                if (empty($repeatDays)) {
                    return $occurrences;
                }
                
                // Converter a string de dias para um array
                $selectedDays = explode(',', $repeatDays);
                
                // Armazenar o dia atual para não repetir no mesmo dia
                $originalDay = $startDate->format('Y-m-d');
                
                // Avançar um dia de cada vez
                $currentDate->modify('+1 day');
                
                while ($untilDate === null || $currentDate <= $untilDate) {
                    // Verificar se o dia da semana está selecionado
                    $dayOfWeek = (string)$currentDate->format('w');
                    
                    if (in_array($dayOfWeek, $selectedDays)) {
                        // Manter a hora original, mas mudar a data
                        $newStart = new DateTime($currentDate->format('Y-m-d') . ' ' . $startDate->format('H:i:s'));
                        
                        // Calcular nova data de término mantendo a duração
                        $newEnd = clone $newStart;
                        $newEnd->add($duration);
                        
                        // Adicionar à lista de ocorrências
                        $occurrences[] = [
                            'start' => $newStart->format('Y-m-d H:i:s'),
                            'end' => $newEnd->format('Y-m-d H:i:s')
                        ];
                    }
                    
                    // Avançar para o próximo dia
                    $currentDate->modify('+1 day');
                }
                break;
        }
        
        return $occurrences;
    }
    
    /**
     * Gera um UUID v4
     * 
     * @return string UUID no formato string
     */
    private function generateUUID() {
        // Gerar 16 bytes aleatórios
        if (function_exists('random_bytes')) {
            $data = random_bytes(16);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);
        } else {
            // Fallback menos seguro
            $data = '';
            for ($i = 0; $i < 16; $i++) {
                $data .= chr(mt_rand(0, 255));
            }
        }
        
// Configurar versão (4) e variante (RFC 4122)
$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

// Formatar como string UUID
$hex = bin2hex($data);
return sprintf(
    '%s-%s-%s-%s-%s',
    substr($hex, 0, 8),
    substr($hex, 8, 4),
    substr($hex, 12, 4),
    substr($hex, 16, 4),
    substr($hex, 20, 12)
);
}
}