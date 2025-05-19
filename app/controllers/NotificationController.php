<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Compromisso.php';
require_once __DIR__ . '/../models/Agenda.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class NotificationController extends BaseController {
    private $notificationModel;
    private $compromissoModel;
    private $agendaModel;
    private $userModel;
    
    public function __construct() {
        $this->notificationModel = new Notification();
        $this->compromissoModel = new Compromisso();
        $this->agendaModel = new Agenda();
        $this->userModel = new User();

        $this->db = Database::getInstance()->getConnection();
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    
    /**
     * Exibe a lista de notificações do usuário
     */
    public function index() {
    $userId = $_SESSION['user_id'];
    
    // Obter parâmetros de filtro e paginação
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $onlyUnread = isset($_GET['unread']) && $_GET['unread'] == '1';
    $itemsPerPage = 20;
    
    // Calcular offset para paginação
    $offset = ($page - 1) * $itemsPerPage;
    
    // Obter total de notificações para paginação
    $totalNotifications = $this->notificationModel->countByUser($userId, $onlyUnread);
    
    // Obter notificações paginadas
    $notifications = $this->notificationModel->getAllByUser($userId, $offset, $itemsPerPage, $onlyUnread);
    
    // Calcular informações de paginação
    $totalPages = ceil($totalNotifications / $itemsPerPage);
    if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
    
    $startRecord = $totalNotifications > 0 ? ($page - 1) * $itemsPerPage + 1 : 0;
    $endRecord = min($startRecord + $itemsPerPage - 1, $totalNotifications);
    
    // Preparar parâmetros de query para links de paginação
    $queryParams = $onlyUnread ? 'unread=1' : '';
    
    // Depuração - exibir valor do filtro (temporário)
    echo "<!-- Debug: onlyUnread = " . ($onlyUnread ? 'true' : 'false') . " -->";
    
    // Exibir a view
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/notifications/index.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}
    
    /**
     * Marca uma notificação como lida
     */
    public function markAsRead() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/notifications');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Notificação não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/notifications');
            exit;
        }
        
        // Marcar como lida
        $result = $this->notificationModel->markAsRead($id, $userId);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Notificação marcada como lida';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao marcar notificação como lida';
            $_SESSION['flash_type'] = 'danger';
        }
        
        // Redirecionar de volta à página anterior
        $redirectUrl = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : BASE_URL . '/notifications';
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/notifications');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Marcar todas como lidas
        $result = $this->notificationModel->markAllAsRead($userId);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Todas as notificações foram marcadas como lidas';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao marcar notificações como lidas';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
public function acceptCompromisso() {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    $compromissoId = filter_input(INPUT_POST, 'compromisso_id', FILTER_VALIDATE_INT);
    $approveAll = isset($_POST['approve_all']);
    
    if (!$notificationId || !$compromissoId) {
        $_SESSION['flash_message'] = 'Parâmetros inválidos';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Buscar o compromisso
    $compromisso = $this->compromissoModel->getById($compromissoId);
    
    if (!$compromisso) {
        $_SESSION['flash_message'] = 'Compromisso não encontrado';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Verificar se o usuário é o dono da agenda
    $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
    
    if (!$isOwner) {
        $_SESSION['flash_message'] = 'Você não tem permissão para aprovar este compromisso';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Verificar se o compromisso está aguardando aprovação
    if ($compromisso['status'] !== 'aguardando_aprovacao') {
        $_SESSION['flash_message'] = 'Este compromisso não está aguardando aprovação';
        $_SESSION['flash_type'] = 'warning';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Se for aprovar todos e for um compromisso recorrente
    if ($approveAll && !empty($compromisso['group_id'])) {
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Atualizar todos os compromissos do mesmo grupo
            $query = "UPDATE compromissos 
                      SET status = 'pendente' 
                      WHERE group_id = :group_id 
                      AND status = 'aguardando_aprovacao'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
            $result = $stmt->execute();
            
            if ($result) {
                // Buscar a agenda
                $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
                
                // Criar notificação para o criador do compromisso
                if (!empty($compromisso['created_by']) && $compromisso['created_by'] != $userId) {
                    // Buscar quantidade de ocorrências atualizadas
                    $countQuery = "SELECT COUNT(*) FROM compromissos 
                                  WHERE group_id = :group_id 
                                  AND status = 'pendente'";
                    $countStmt = $this->db->prepare($countQuery);
                    $countStmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
                    $countStmt->execute();
                    $occurrenceCount = $countStmt->fetchColumn();
                    
                    // Formatar data
                    $dateObj = new DateTime($compromisso['start_datetime']);
                    $formattedDate = $dateObj->format('d/m/Y \à\s H:i');
                    
                    // Buscar informações do aprovador
                    $owner = $this->userModel->getById($userId);
                    $ownerName = $owner ? $owner['name'] : 'Administrador';
                    
                    // Criar mensagem
                    $message = "Sua série de compromissos \"{$compromisso['title']}\" com {$occurrenceCount} ocorrências iniciando em {$formattedDate} na agenda \"{$agenda['title']}\" foi APROVADA por {$ownerName}";
                    
                    // Criar notificação
                    $this->notificationModel->create([
                        'user_id' => $compromisso['created_by'],
                        'compromisso_id' => $compromissoId,
                        'message' => $message,
                        'is_read' => 0
                    ]);
                }
                
                $this->db->commit();
                
                $_SESSION['flash_message'] = 'Todos os compromissos da série foram aprovados com sucesso';
                $_SESSION['flash_type'] = 'success';
                
                // Marcar a notificação como lida
                $this->notificationModel->markAsRead($notificationId, $userId);
            } else {
                $this->db->rollBack();
                $_SESSION['flash_message'] = 'Erro ao aprovar os compromissos da série';
                $_SESSION['flash_type'] = 'danger';
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_message'] = 'Erro ao processar a operação: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
    } else {
        // Aprovar apenas um compromisso (código existente)
        $data = [
            'title' => $compromisso['title'],
            'description' => $compromisso['description'],
            'start_datetime' => $compromisso['start_datetime'],
            'end_datetime' => $compromisso['end_datetime'],
            'location' => $compromisso['location'],
            'repeat_type' => $compromisso['repeat_type'],
            'repeat_until' => $compromisso['repeat_until'],
            'repeat_days' => $compromisso['repeat_days'],
            'status' => 'pendente'
        ];
        
        $result = $this->compromissoModel->update($compromissoId, $data);
        
        if ($result) {
            // Marcar a notificação como lida
            $this->notificationModel->markAsRead($notificationId, $userId);
            
            // Criar notificação para o criador do compromisso
            if (!empty($compromisso['created_by']) && $compromisso['created_by'] != $userId) {
                // Buscar a agenda
                $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
                
                // Formatar data
                $dateObj = new DateTime($compromisso['start_datetime']);
                $formattedDate = $dateObj->format('d/m/Y \à\s H:i');
                
                // Buscar informações do aprovador
                $owner = $this->userModel->getById($userId);
                $ownerName = $owner ? $owner['name'] : 'Administrador';
                
                // Criar mensagem
                $message = "Seu compromisso \"{$compromisso['title']}\" para {$formattedDate} na agenda \"{$agenda['title']}\" foi APROVADO por {$ownerName}";
                
                // Criar notificação
                $this->notificationModel->create([
                    'user_id' => $compromisso['created_by'],
                    'compromisso_id' => $compromissoId,
                    'message' => $message,
                    'is_read' => 0
                ]);
            }
            
            $_SESSION['flash_message'] = 'Compromisso aprovado com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao aprovar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
    }
    
    header('Location: ' . BASE_URL . '/notifications/view?id=' . $notificationId);
    exit;
}

public function rejectCompromisso() {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    $compromissoId = filter_input(INPUT_POST, 'compromisso_id', FILTER_VALIDATE_INT);
    $rejectAll = isset($_POST['reject_all']);
    
    if (!$notificationId || !$compromissoId) {
        $_SESSION['flash_message'] = 'Parâmetros inválidos';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Buscar o compromisso
    $compromisso = $this->compromissoModel->getById($compromissoId);
    
    if (!$compromisso) {
        $_SESSION['flash_message'] = 'Compromisso não encontrado';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Verificar se o usuário é o dono da agenda
    $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
    
    if (!$isOwner) {
        $_SESSION['flash_message'] = 'Você não tem permissão para rejeitar este compromisso';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Verificar se o compromisso está aguardando aprovação
    if ($compromisso['status'] !== 'aguardando_aprovacao') {
        $_SESSION['flash_message'] = 'Este compromisso não está aguardando aprovação';
        $_SESSION['flash_type'] = 'warning';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Se for rejeitar todos e for um compromisso recorrente
    if ($rejectAll && !empty($compromisso['group_id'])) {
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Atualizar todos os compromissos do mesmo grupo
            $query = "UPDATE compromissos 
                      SET status = 'cancelado' 
                      WHERE group_id = :group_id 
                      AND status = 'aguardando_aprovacao'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
            $result = $stmt->execute();
            
            if ($result) {
                // Buscar a agenda
                $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
                
                // Criar notificação para o criador do compromisso
                if (!empty($compromisso['created_by']) && $compromisso['created_by'] != $userId) {
                    // Buscar quantidade de ocorrências atualizadas
                    $countQuery = "SELECT COUNT(*) FROM compromissos 
                                  WHERE group_id = :group_id 
                                  AND status = 'cancelado'";
                    $countStmt = $this->db->prepare($countQuery);
                    $countStmt->bindParam(':group_id', $compromisso['group_id'], PDO::PARAM_STR);
                    $countStmt->execute();
                    $occurrenceCount = $countStmt->fetchColumn();
                    
                    // Formatar data
                    $dateObj = new DateTime($compromisso['start_datetime']);
                    $formattedDate = $dateObj->format('d/m/Y \à\s H:i');
                    
                    // Buscar informações do rejeitador
                    $owner = $this->userModel->getById($userId);
                    $ownerName = $owner ? $owner['name'] : 'Administrador';
                    
                    // Criar mensagem
                    $message = "Sua série de compromissos \"{$compromisso['title']}\" com {$occurrenceCount} ocorrências iniciando em {$formattedDate} na agenda \"{$agenda['title']}\" foi REJEITADA por {$ownerName}";
                    
                    // Criar notificação
                    $this->notificationModel->create([
                        'user_id' => $compromisso['created_by'],
                        'compromisso_id' => $compromissoId,
                        'message' => $message,
                        'is_read' => 0
                    ]);
                }
                
                $this->db->commit();
                
                $_SESSION['flash_message'] = 'Todos os compromissos da série foram rejeitados com sucesso';
                $_SESSION['flash_type'] = 'success';
                
                // Marcar a notificação como lida
                $this->notificationModel->markAsRead($notificationId, $userId);
            } else {
                $this->db->rollBack();
                $_SESSION['flash_message'] = 'Erro ao rejeitar os compromissos da série';
                $_SESSION['flash_type'] = 'danger';
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_message'] = 'Erro ao processar a operação: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
    } else {
        // Rejeitar apenas um compromisso (código existente)
        $data = [
            'title' => $compromisso['title'],
            'description' => $compromisso['description'],
            'start_datetime' => $compromisso['start_datetime'],
            'end_datetime' => $compromisso['end_datetime'],
            'location' => $compromisso['location'],
            'repeat_type' => $compromisso['repeat_type'],
            'repeat_until' => $compromisso['repeat_until'],
            'repeat_days' => $compromisso['repeat_days'],
            'status' => 'cancelado'
        ];
        
        $result = $this->compromissoModel->update($compromissoId, $data);
        
        if ($result) {
            // Marcar a notificação como lida
            $this->notificationModel->markAsRead($notificationId, $userId);
            
            // Criar notificação para o criador do compromisso
            if (!empty($compromisso['created_by']) && $compromisso['created_by'] != $userId) {
                // Buscar a agenda
                $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
                
                // Formatar data
                $dateObj = new DateTime($compromisso['start_datetime']);
                $formattedDate = $dateObj->format('d/m/Y \à\s H:i');
                
                // Buscar informações do rejeitador
                $owner = $this->userModel->getById($userId);
                $ownerName = $owner ? $owner['name'] : 'Administrador';
                
                // Criar mensagem
                $message = "Seu compromisso \"{$compromisso['title']}\" para {$formattedDate} na agenda \"{$agenda['title']}\" foi REJEITADO por {$ownerName}";
                
                // Criar notificação
                $this->notificationModel->create([
                    'user_id' => $compromisso['created_by'],
                    'compromisso_id' => $compromissoId,
                    'message' => $message,
                    'is_read' => 0
                ]);
            }
            
            $_SESSION['flash_message'] = 'Compromisso rejeitado com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao rejeitar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
    }
    
    header('Location: ' . BASE_URL . '/notifications/view?id=' . $notificationId);
    exit;
}
    
    /**
     * Exclui uma notificação
     */
    public function delete() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/notifications');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Notificação não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/notifications');
            exit;
        }
        
        // Excluir notificação
        $result = $this->notificationModel->delete($id, $userId);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Notificação excluída com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao excluir notificação';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    /**
     * Obtém detalhes completos de uma notificação
     * 
     * @param int $id ID da notificação
     * @param int $userId ID do usuário
     * @return array|bool Dados da notificação ou false se não encontrada
     */
    private function getNotificationDetails($id, $userId) {
        // Buscar notificação básica
        $stmt = $this->notificationModel->getOne($id, $userId);
        
        if (!$stmt) {
            return false;
        }
        
        $notification = $stmt;
        
        // Se a notificação estiver associada a um compromisso, buscar detalhes
        if (!empty($notification['compromisso_id'])) {
            $compromisso = $this->compromissoModel->getById($notification['compromisso_id']);
            
            if ($compromisso) {
                $notification['compromisso'] = $compromisso;
                
                // Buscar detalhes da agenda
                $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
                if ($agenda) {
                    $notification['agenda'] = $agenda;
                    
                    // Verificar se o usuário é o dono da agenda
                    $notification['is_agenda_owner'] = ($agenda['user_id'] == $userId);
                }
                
                // Buscar informações do criador do compromisso
                if (!empty($compromisso['created_by'])) {
                    $creator = $this->userModel->getById($compromisso['created_by']);
                    if ($creator) {
                        $notification['compromisso_creator'] = $creator;
                    }
                }
            }
        }
        
        return $notification;
    }

    public function create($data) {
    try {
        // Verificamos se já estão presentes no array de dados
        $isRecurring = isset($data['is_recurring']) ? $data['is_recurring'] : 0;
        $groupId = isset($data['group_id']) ? $data['group_id'] : null;
        
        $query = "
            INSERT INTO notifications (user_id, compromisso_id, message, is_read, created_at, is_recurring, group_id)
            VALUES (:user_id, :compromisso_id, :message, :is_read, NOW(), :is_recurring, :group_id)
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':compromisso_id', $data['compromisso_id'], PDO::PARAM_INT);
        $stmt->bindParam(':message', $data['message'], PDO::PARAM_STR);
        $stmt->bindParam(':is_read', $data['is_read'], PDO::PARAM_BOOL);
        $stmt->bindParam(':is_recurring', $isRecurring, PDO::PARAM_BOOL);
        $stmt->bindParam(':group_id', $groupId, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        error_log('Erro ao criar notificação: ' . $e->getMessage());
        return false;
    }
}

public function view() {
    $userId = $_SESSION['user_id'];
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        $_SESSION['flash_message'] = 'Notificação não especificada';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Obter detalhes completos da notificação
    $notification = $this->getNotificationDetails($id, $userId);
    
    if (!$notification) {
        $_SESSION['flash_message'] = 'Notificação não encontrada';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/notifications');
        exit;
    }
    
    // Se for um compromisso recorrente, buscar todas as ocorrências
    if (isset($notification['compromisso']) && !empty($notification['compromisso']['group_id'])) {
        // Buscar todas as ocorrências do grupo
        $query = "SELECT * FROM compromissos WHERE group_id = :group_id ORDER BY start_datetime";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':group_id', $notification['compromisso']['group_id'], PDO::PARAM_STR);
        $stmt->execute();
        $occurrences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar à notificação
        $notification['occurrences'] = $occurrences;
    }
    
    // Marcar como lida automaticamente
    $this->notificationModel->markAsRead($id, $userId);
    
    // Exibir a view
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/notifications/view.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}


}