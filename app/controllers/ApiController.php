<?php
// app/controllers/ApiController.php

/**
 * Controlador para endpoints de API (AJAX)
 */
class ApiController {
    private $compromissoModel;
    private $agendaModel;
    private $notificationModel;
    private $authService;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Carregar modelos necessários
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/Notification.php';
        require_once __DIR__ . '/../services/AuthorizationService.php';
        
        $this->compromissoModel = new Compromisso();
        $this->agendaModel = new Agenda();
        $this->notificationModel = new Notification();
        $this->authService = new AuthorizationService();
        
        // Verificar autenticação para todos os endpoints (exceto os públicos)
        if (!$this->isPublicEndpoint()) {
            $this->checkAuth();
        }
    }
    
    /**
     * Verifica se o endpoint atual é público
     * 
     * @return bool Se o endpoint é público
     */
    private function isPublicEndpoint() {
        // Lista de endpoints públicos
        $publicEndpoints = [
            'check-server-status'
        ];
        
        // Obter o endpoint da URL
        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', $uri);
        $endpoint = end($parts);
        
        return in_array($endpoint, $publicEndpoints);
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Não autenticado'], 401);
            exit;
        }
    }
    
    /**
     * Verifica status do servidor
     */
    public function checkServerStatus() {
        $this->jsonResponse([
            'status' => 'online',
            'timestamp' => time(),
            'formatted_time' => date('Y-m-d H:i:s'),
            'version' => APP_VERSION
        ]);
    }
    
    /**
     * Verifica conflito de horário para compromissos
     */
    public function checkTimeConflict() {
        try {
            // Obter parâmetros
            $agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
            $startDatetime = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
            $endDatetime = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);
            $compromissoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
            
            // Validar parâmetros
            if (!$agendaId || !$startDatetime || !$endDatetime) {
                $this->jsonResponse(['error' => 'Parâmetros inválidos'], 400);
                exit;
            }
            
            // Verificar acesso à agenda
            $canAccess = $this->authService->canAccessAgenda($agendaId, $_SESSION['user_id']);
            if (!$canAccess) {
                $this->jsonResponse(['error' => 'Sem permissão para acessar esta agenda'], 403);
                exit;
            }
            
            // Verificar conflito
            $hasConflict = $this->compromissoModel->hasTimeConflict($agendaId, $startDatetime, $endDatetime, $compromissoId);
            
            $this->jsonResponse([
                'conflict' => $hasConflict,
                'message' => $hasConflict ? 'O horário conflita com outro compromisso' : 'Horário disponível'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Busca usuários para compartilhamento
     */
    public function searchUsers() {
        try {
            // Obter termo de busca
            $query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
            
            if (empty($query) || strlen($query) < 3) {
                $this->jsonResponse(['users' => []]);
                exit;
            }
            
            // Carregar modelo de usuário
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            
            // Buscar usuários
            $users = $userModel->search($query);
            
            // Remover dados sensíveis e o próprio usuário
            $filteredUsers = [];
            foreach ($users as $user) {
                if ($user['id'] != $_SESSION['user_id']) {
                    $filteredUsers[] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name']
                    ];
                }
            }
            
            $this->jsonResponse(['users' => $filteredUsers]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtém notificações não lidas
     */
    public function getNotifications() {
        try {
            // Obter notificações não lidas
            $notifications = $this->notificationModel->getUnreadByUser($_SESSION['user_id']);
            
            // Formatar notificações para exibição
            $formattedNotifications = [];
            foreach ($notifications as $notification) {
                $startDate = null;
                if (!empty($notification['start_datetime'])) {
                    $startDate = new DateTime($notification['start_datetime']);
                }
                
                $formattedNotifications[] = [
                    'id' => $notification['id'],
                    'message' => $notification['message'],
                    'compromisso_id' => $notification['compromisso_id'],
                    'compromisso_title' => $notification['compromisso_title'] ?? null,
                    'agenda_id' => $notification['agenda_id'] ?? null,
                    'agenda_title' => $notification['agenda_title'] ?? null,
                    'date' => $startDate ? $startDate->format('d/m/Y H:i') : null,
                    'created_at' => (new DateTime($notification['created_at']))->format('d/m/Y H:i')
                ];
            }
            
            $this->jsonResponse([
                'total' => count($notifications),
                'notifications' => $formattedNotifications
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Marca notificação como lida
     */
    public function markNotificationRead() {
        try {
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['error' => 'Método não permitido'], 405);
                exit;
            }
            
            // Obter ID da notificação
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            
            if (!$id) {
                $this->jsonResponse(['error' => 'ID da notificação inválido'], 400);
                exit;
            }
            
            // Marcar como lida
            $result = $this->notificationModel->markAsRead($id, $_SESSION['user_id']);
            
            $this->jsonResponse([
                'success' => $result,
                'message' => $result ? 'Notificação marcada como lida' : 'Erro ao marcar notificação'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    public function markAllNotificationsRead() {
        try {
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['error' => 'Método não permitido'], 405);
                exit;
            }
            
            // Marcar todas como lidas
            $result = $this->notificationModel->markAllAsRead($_SESSION['user_id']);
            
            $this->jsonResponse([
                'success' => $result,
                'message' => $result ? 'Todas as notificações marcadas como lidas' : 'Erro ao marcar notificações'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Envia resposta em JSON
     * 
     * @param mixed $data Dados a serem enviados
     * @param int $statusCode Código de status HTTP
     */
    private function jsonResponse($data, $statusCode = 200) {
        // Definir cabeçalhos
        header('Content-Type: application/json');
        http_response_code($statusCode);
        
        // Enviar resposta
        echo json_encode($data);
        exit;
    }

    public function getPendingApprovals() {
        try {
            // Verificar se o usuário está autenticado
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['error' => 'Não autenticado'], 401);
                exit;
            }
            
            $userId = $_SESSION['user_id'];
            
            // Buscar agendas que o usuário é proprietário
            $query = "SELECT id FROM agendas WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $agendaIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($agendaIds)) {
                $this->jsonResponse(['count' => 0, 'compromissos' => []]);
                exit;
            }
            
            // Buscar compromissos aguardando aprovação nas agendas do usuário
            $placeholders = implode(',', array_fill(0, count($agendaIds), '?'));
            
            $query = "
                SELECT c.*, a.title as agenda_title, u.name as created_by_name
                FROM compromissos c
                JOIN agendas a ON c.agenda_id = a.id
                LEFT JOIN users u ON c.created_by = u.id
                WHERE c.agenda_id IN ({$placeholders})
                AND c.status = 'aguardando_aprovacao'
                ORDER BY c.start_datetime ASC
            ";
            
            $stmt = $this->db->prepare($query);
            
            // Associar agendaIds aos placeholders
            foreach ($agendaIds as $index => $agendaId) {
                $stmt->bindValue($index + 1, $agendaId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $compromissos = $stmt->fetchAll();
            
            // Formatação para o frontend
            $formattedCompromissos = [];
            foreach ($compromissos as $compromisso) {
                $formattedCompromissos[] = [
                    'id' => $compromisso['id'],
                    'title' => $compromisso['title'],
                    'description' => $compromisso['description'],
                    'start_datetime' => $compromisso['start_datetime'],
                    'end_datetime' => $compromisso['end_datetime'],
                    'location' => $compromisso['location'],
                    'status' => $compromisso['status'],
                    'agenda_id' => $compromisso['agenda_id'],
                    'agenda_title' => $compromisso['agenda_title'],
                    'created_by' => $compromisso['created_by'],
                    'created_by_name' => $compromisso['created_by_name'] ?? 'Usuário'
                ];
            }
            
            $this->jsonResponse([
                'count' => count($formattedCompromissos),
                'compromissos' => $formattedCompromissos
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}