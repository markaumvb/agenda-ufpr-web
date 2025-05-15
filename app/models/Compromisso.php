<?php

class Compromisso {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    

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
     * Obtém o total de compromissos de uma agenda
     * 
     * @param int $agendaId ID da agenda
     * @param array $filters Filtros opcionais (status, data, etc)
     * @return int Número total de compromissos
     */
    public function countByAgenda($agendaId, $filters = []) {
        try {
            $query = "SELECT COUNT(*) FROM compromissos WHERE agenda_id = :agenda_id";
            $params = ['agenda_id' => $agendaId];
            
            // Aplicar filtros se houver
            if (!empty($filters)) {
                if (isset($filters['status']) && $filters['status'] !== 'all') {
                    $query .= " AND status = :status";
                    $params['status'] = $filters['status'];
                }
                
                if (isset($filters['month']) && $filters['month'] !== 'all') {
                    $query .= " AND MONTH(start_datetime) = :month";
                    $params['month'] = $filters['month'];
                }
                
                if (isset($filters['search']) && !empty($filters['search'])) {
                    $query .= " AND (title LIKE :search OR description LIKE :search OR location LIKE :search)";
                    $params['search'] = '%' . $filters['search'] . '%';
                }
            }
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao contar compromissos: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtém compromissos de uma agenda com paginação
     * 
     * @param int $agendaId ID da agenda
     * @param int $offset Início da paginação
     * @param int $limit Limite de registros
     * @param array $filters Filtros opcionais (status, data, etc)
     * @param string $orderBy Campo para ordenação
     * @param string $order Direção da ordenação (ASC, DESC)
     * @return array Lista de compromissos
     */
    public function getByAgendaPaginated($agendaId, $offset = 0, $limit = 10, $filters = [], $orderBy = 'start_datetime', $order = 'ASC') {
        try {
            $query = "SELECT * FROM compromissos WHERE agenda_id = :agenda_id";
            $params = ['agenda_id' => $agendaId];
            
            // Aplicar filtros se houver
            if (!empty($filters)) {
                if (isset($filters['status']) && $filters['status'] !== 'all') {
                    $query .= " AND status = :status";
                    $params['status'] = $filters['status'];
                }
                
                if (isset($filters['month']) && $filters['month'] !== 'all') {
                    $query .= " AND MONTH(start_datetime) = :month";
                    $params['month'] = $filters['month'];
                }
                
                if (isset($filters['search']) && !empty($filters['search'])) {
                    $query .= " AND (title LIKE :search OR description LIKE :search OR location LIKE :search)";
                    $params['search'] = '%' . $filters['search'] . '%';
                }
            }
            
            // Ordenação
            $validOrderColumns = ['start_datetime', 'end_datetime', 'title', 'status', 'created_at'];
            $validOrderDirections = ['ASC', 'DESC'];
            
            if (!in_array($orderBy, $validOrderColumns)) {
                $orderBy = 'start_datetime';
            }
            
            if (!in_array(strtoupper($order), $validOrderDirections)) {
                $order = 'ASC';
            }
            
            $query .= " ORDER BY {$orderBy} {$order}";
            
            // Paginação
            $query .= " LIMIT :offset, :limit";
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            
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

            $createdBy = isset($data['created_by']) ? $data['created_by'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

            // Se não for recorrente, usar o método padrão de criação
            if (!$isRecurring) {
                $query = "
                    INSERT INTO compromissos (
                        agenda_id, title, description, start_datetime, end_datetime, 
                        location, repeat_type, repeat_until, repeat_days, status,
                        created_at, created_by
                    ) VALUES (
                        :agenda_id, :title, :description, :start_datetime, :end_datetime, 
                        :location, :repeat_type, :repeat_until, :repeat_days, :status,
                        NOW(), :created_by
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
                $stmt->bindParam(':created_by', $createdBy, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $newId = $this->db->lastInsertId();
                    
                    // Criar notificação para o dono da agenda
                    $this->createNotificationForAgendaOwner($newId);
                    
                    return $newId;
                }
                
                return false;
            }
            
            // Para eventos recorrentes, iniciar transação
            $this->db->beginTransaction();
            
            // Gerar ID de grupo para eventos recorrentes
            $groupId = $this->generateUUID();

            //quem criou o evento
            $createdBy = isset($data['created_by']) ? $data['created_by'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

            
            // Inserir o evento principal
            $query = "
                INSERT INTO compromissos (
                    agenda_id, title, description, start_datetime, end_datetime, 
                    location, repeat_type, repeat_until, repeat_days, status,
                    created_at, group_id, is_recurring, created_by
                ) VALUES (
                    :agenda_id, :title, :description, :start_datetime, :end_datetime, 
                    :location, :repeat_type, :repeat_until, :repeat_days, :status,
                    NOW(), :group_id, 1, :created_by
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
            $stmt->bindParam(':created_by', $createdBy, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->db->rollBack();
                return false;
            }
            
            $parentId = $this->db->lastInsertId();
            
            // Criar ocorrências recorrentes baseadas no tipo de repetição
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
                        created_at, group_id, is_recurring, parent_id, created_by
                    ) VALUES (
                        :agenda_id, :title, :description, :start_datetime, :end_datetime, 
                        :location, :repeat_type, :repeat_until, :repeat_days, :status,
                        NOW(), :group_id, 1, :parent_id, :created_by
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
                $stmt->bindParam(':created_by', $createdBy, PDO::PARAM_INT);
                
                if (!$stmt->execute()) {
                    $this->db->rollBack();
                    return false;
                }
            }
            
            // Criar notificação para o dono da agenda
            $this->createNotificationForAgendaOwner($parentId);
            
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
     * Cria uma notificação para o dono da agenda
     * 
     * @param int $compromissoId ID do compromisso criado
     * @return bool Resultado da operação
     */
    private function createNotificationForAgendaOwner($compromissoId) {
        try {
            // Obter o compromisso
            $compromisso = $this->getById($compromissoId);
            if (!$compromisso) {
                return false;
            }
            
            // Obter a agenda
            $query = "SELECT a.*, u.id as owner_id, u.name as owner_name FROM agendas a 
                     JOIN users u ON a.user_id = u.id 
                     WHERE a.id = :agenda_id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':agenda_id', $compromisso['agenda_id'], PDO::PARAM_INT);
            $stmt->execute();
            $agenda = $stmt->fetch();
            
            if (!$agenda) {
                return false;
            }
            
            // Se o usuário atual não for o dono da agenda, criar notificação
            if ($_SESSION['user_id'] != $agenda['owner_id']) {
                // Verificar se o módulo de notificação está disponível
                if (!class_exists('Notification')) {
                    require_once __DIR__ . '/Notification.php';
                }
                
                $notificationModel = new Notification();
                
                // Formatar data
                $dateObj = new DateTime($compromisso['start_datetime']);
                $formattedDate = $dateObj->format('d/m/Y \à\s H:i');
                
                // Criar notificação
                $message = "Novo compromisso \"{$compromisso['title']}\" foi adicionado em sua agenda \"{$agenda['title']}\" para {$formattedDate}";
                
                $notificationData = [
                    'user_id' => $agenda['owner_id'],
                    'compromisso_id' => $compromissoId,
                    'message' => $message,
                    'is_read' => 0
                ];
                
                $notificationModel->create($notificationData);
                
                // Enviar e-mail (opcional)
                $this->sendEmailNotification($agenda['owner_id'], $compromissoId);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia notificação por e-mail (se configurado)
     * 
     * @param int $userId ID do usuário
     * @param int $compromissoId ID do compromisso
     * @return bool Resultado do envio
     */
    private function sendEmailNotification($userId, $compromissoId) {
        try {
            // Verificar se o serviço de e-mail está disponível
            if (!class_exists('EmailService')) {
                require_once __DIR__ . '/../services/EmailService.php';
            }
            
            // Obter dados do usuário
            $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user || empty($user['email'])) {
                return false;
            }
            
            // Obter dados do compromisso
            $compromisso = $this->getById($compromissoId);
            if (!$compromisso) {
                return false;
            }
            
            // Obter dados da agenda
            $query = "SELECT * FROM agendas WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $compromisso['agenda_id'], PDO::PARAM_INT);
            $stmt->execute();
            $agenda = $stmt->fetch();
            
            if (!$agenda) {
                return false;
            }
            
            // Enviar e-mail
            $emailService = new EmailService();
            return $emailService->sendNewCompromissoNotification($user, $compromisso, $agenda);
        } catch (Exception $e) {
            error_log('Erro ao enviar e-mail de notificação: ' . $e->getMessage());
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
            // Melhorar a lógica de detecção de conflitos
            $query = "
                SELECT COUNT(*) FROM compromissos 
                WHERE agenda_id = :agenda_id
                AND status != 'cancelado'
                AND (
                    (start_datetime < :end_datetime AND end_datetime > :start_datetime)
                )
            ";
            
            $params = [
                ':agenda_id' => $agendaId,
                ':start_datetime' => $startDatetime,
                ':end_datetime' => $endDatetime
            ];
            
            // Se fornecido um ID para exclusão, adiciona à query
            if ($excludeId !== null) {
                $query .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeId;
                
                // Também excluir do mesmo grupo se for um evento recorrente
                $compromisso = $this->getById($excludeId);
                if ($compromisso && !empty($compromisso['group_id'])) {
                    $query .= " AND (group_id IS NULL OR group_id != :group_id)";
                    $params[':group_id'] = $compromisso['group_id'];
                }
            }
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
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
        // Limitar número máximo de recorrências para evitar sobrecarga
        $MAX_OCCURRENCES = 100;
        
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
        
        // Contador de ocorrências para limitar
        $occurrenceCount = 0;
        
        // Para cada tipo de recorrência
        switch ($repeatType) {
            case 'daily':
                // Avançar um dia de cada vez
                $currentDate->modify('+1 day');
                
                while (($untilDate === null || $currentDate <= $untilDate) && $occurrenceCount < $MAX_OCCURRENCES) {
                    // Pular fins de semana (sábado e domingo)
                    $dayOfWeek = (int)$currentDate->format('w');
                    
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
                    $occurrenceCount++;
                }
                break;
                
            case 'weekly':
                // Armazenar o dia da semana do evento original
                $originalDayOfWeek = (int)$startDate->format('w');
                
                // Avançar uma semana
                $currentDate->modify('+1 week');
                
                while (($untilDate === null || $currentDate <= $untilDate) && $occurrenceCount < $MAX_OCCURRENCES) {
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
                    $occurrenceCount++;
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
                
                while (($untilDate === null || $currentDate <= $untilDate) && $occurrenceCount < $MAX_OCCURRENCES) {
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
                        
                        $occurrenceCount++;
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

    public function deleteAllFromAgenda($agendaId) {
        $sql = "DELETE FROM compromissos WHERE agenda_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$agendaId]);
    }

    public function countByStatus($agendaId, $status) {
        $sql = "SELECT COUNT(*) as count FROM compromissos WHERE agenda_id = ? AND status = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$agendaId, $status]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }


public function validateCompromissoDate($agendaId, $startDatetime, $isEditing = false) {
    try {
        $errors = [];
        
        // Obter informações da agenda
        $query = "SELECT min_time_before FROM agendas WHERE id = :agenda_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':agenda_id', $agendaId, PDO::PARAM_INT);
        $stmt->execute();
        
        $agenda = $stmt->fetch();
        
        if (!$agenda) {
            return ['Agenda não encontrada'];
        }
        
        // Converter strings para objetos DateTime
        $start = new DateTime($startDatetime);
        $now = new DateTime();
        
        // Verifica se a data inicial está no futuro
        if ($start <= $now) {
            $errors[] = 'A data e hora de início deve ser no futuro';
            return $errors;
        }
        
        // Verifica a antecedência mínima apenas para novos compromissos, não para edições
        if (!$isEditing && $agenda['min_time_before'] > 0) {
            // Calcular a data mínima permitida
            $minDate = new DateTime();
            $minDate->add(new DateInterval("PT{$agenda['min_time_before']}H"));
            
            if ($start < $minDate) {
                $errors[] = "A data e hora de início deve ter pelo menos {$agenda['min_time_before']} horas de antecedência";
            }
        }
        
        return $errors;
    } catch (Exception $e) {
        error_log('Erro ao validar data do compromisso: ' . $e->getMessage());
        return ['Erro ao validar data do compromisso'];
    }
}

    
}