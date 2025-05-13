<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/AuthorizationService.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class MeusCompromissosController extends BaseController {
    private $compromissoModel;
    private $agendaModel;
    private $authService;
    private $shareModel;
    
    public function __construct() {
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/AgendaShare.php';
        
        $this->compromissoModel = new Compromisso();
        $this->agendaModel = new Agenda();
        $this->authService = new AuthorizationService();
        $this->shareModel = new AgendaShare();
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    
    /**
     * Exibe a página principal com os compromissos do usuário em formato de data grid
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        
        // Obter todos os compromissos acessíveis pelo usuário
        $compromissosData = $this->getCompromissosData($userId);
        $compromissos = $compromissosData['compromissos'];
        $totalRecords = $compromissosData['total'];
        
        // Paginação
        $itemsPerPage = 20;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        
        $totalPages = ceil($totalRecords / $itemsPerPage);
        if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;
        
        $startRecord = ($currentPage - 1) * $itemsPerPage + 1;
        $endRecord = min($startRecord + $itemsPerPage - 1, $totalRecords);
        
        // Apenas os compromissos da página atual
        $compromissos = array_slice($compromissos, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
        
        // Obter todas as agendas acessíveis pelo usuário (para o filtro)
        $agendas = $this->agendaModel->getAllAccessibleByUser($userId);
        
        // Preparar parâmetros de query para links de paginação
        $queryParams = '';
        if (isset($_GET['agenda'])) {
            $queryParams .= '&agenda=' . urlencode($_GET['agenda']);
        }
        if (isset($_GET['status'])) {
            $queryParams .= '&status=' . urlencode($_GET['status']);
        }
        if (isset($_GET['period'])) {
            $queryParams .= '&period=' . urlencode($_GET['period']);
        }
        if (isset($_GET['search'])) {
            $queryParams .= '&search=' . urlencode($_GET['search']);
        }
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/meuscompromissos/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Obtém todos os compromissos do usuário
     */
    private function getCompromissosData($userId) {
        // Obter todas as agendas acessíveis pelo usuário
        $agendas = $this->agendaModel->getAllAccessibleByUser($userId);
        
        // Para cada agenda, buscar os compromissos
        $allCompromissos = [];
        $processedIds = []; // Para evitar duplicatas
        
        foreach ($agendas as $agenda) {
            // Definir se o usuário é o dono da agenda
            $isOwner = ($agenda['user_id'] == $userId);
            
            // Verificar permissões de edição
            $canEdit = $isOwner;
            if (!$isOwner) {
                $canEdit = $this->shareModel->canEdit($agenda['id'], $userId);
            }
            
            // Adicionar informações à agenda
            $agenda['is_owner'] = $isOwner;
            $agenda['can_edit'] = $canEdit;
            
            // Buscar compromissos da agenda
            $compromissos = $this->compromissoModel->getAllByAgenda($agenda['id']);
            
            foreach ($compromissos as $compromisso) {
                // Verificar se já foi processado
                if (in_array($compromisso['id'], $processedIds)) {
                    continue;
                }
                if ($compromisso['created_by'] == $userId || $agenda['user_id'] == $userId) {
                $processedIds[] = $compromisso['id']; // Marcar como processado
                
                // Adicionar informações extras
                $compromisso['agenda_info'] = $agenda;
                
                // Adicionar à lista principal
                $allCompromissos[] = $compromisso;
                }
            }
        }
        
        // Aplicar filtros da URL
        $filteredCompromissos = $this->applyFilters($allCompromissos);
        
        // Ordenar compromissos (por padrão, pela data de início)
        usort($filteredCompromissos, function($a, $b) {
            return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
        });
        
        return [
            'compromissos' => $filteredCompromissos,
            'total' => count($filteredCompromissos)
        ];
    }
    
    /**
     * Aplica filtros da URL aos compromissos
     */
    private function applyFilters($compromissos) {
        $filtered = [];
        
        // Filtro por agenda
        $agendaFilter = isset($_GET['agenda']) ? $_GET['agenda'] : null;
        
        // Filtro por status
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
        
        // Filtro por período
        $periodFilter = isset($_GET['period']) ? $_GET['period'] : null;
        
        // Filtro por busca
        $searchFilter = isset($_GET['search']) ? strtolower($_GET['search']) : null;
        
        // Calcular datas para filtros de período
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $weekStart = date('Y-m-d', strtotime('this week'));
        $weekEnd = date('Y-m-d', strtotime('this week +6 days'));
        $monthStart = date('Y-m-d', strtotime('first day of this month'));
        $monthEnd = date('Y-m-d', strtotime('last day of this month'));
        
        foreach ($compromissos as $compromisso) {
            $include = true;
            
            // Filtrar por agenda
            if ($agendaFilter && $agendaFilter !== 'all' && $compromisso['agenda_id'] != $agendaFilter) {
                $include = false;
            }
            
            // Filtrar por status
            if ($statusFilter && $statusFilter !== 'all' && $compromisso['status'] !== $statusFilter) {
                $include = false;
            }
            
            // Filtrar por período
            if ($periodFilter && $periodFilter !== 'all') {
                $compromissoDate = date('Y-m-d', strtotime($compromisso['start_datetime']));
                
                switch ($periodFilter) {
                    case 'today':
                        if ($compromissoDate !== $today) {
                            $include = false;
                        }
                        break;
                    case 'tomorrow':
                        if ($compromissoDate !== $tomorrow) {
                            $include = false;
                        }
                        break;
                    case 'week':
                        if ($compromissoDate < $weekStart || $compromissoDate > $weekEnd) {
                            $include = false;
                        }
                        break;
                    case 'month':
                        if ($compromissoDate < $monthStart || $compromissoDate > $monthEnd) {
                            $include = false;
                        }
                        break;
                    case 'past':
                        if ($compromissoDate >= $today) {
                            $include = false;
                        }
                        break;
                }
            }
            
            // Filtrar por texto de busca
            if ($searchFilter) {
                $searchText = strtolower($compromisso['title'] . ' ' . 
                                       $compromisso['description'] . ' ' . 
                                       $compromisso['location']);
                
                if (strpos($searchText, $searchFilter) === false) {
                    $include = false;
                }
            }
            
            if ($include) {
                $filtered[] = $compromisso;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Cancela um compromisso específico
     */
    public function cancelCompromisso() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Obter o ID do compromisso
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Compromisso não especificado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Buscar o compromisso
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            $_SESSION['flash_message'] = 'Compromisso não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Verificar se o usuário pode cancelar o compromisso
        $userId = $_SESSION['user_id'];
        $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
        
        // Usuário pode cancelar se for dono da agenda ou se for o criador do compromisso
        $canCancel = $isOwner || ($compromisso['created_by'] == $userId);
        
        if (!$canCancel) {
            $_SESSION['flash_message'] = 'Você não tem permissão para cancelar este compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Atualizar o status para cancelado
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
        
        $result = $this->compromissoModel->update($id, $data);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Compromisso cancelado com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao cancelar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
        
        // Preservar filtros da URL ao redirecionar
        $redirectUrl = BASE_URL . '/meuscompromissos';
        $queryParams = $this->getQueryParamsString();
        if ($queryParams) {
            $redirectUrl .= '?' . $queryParams;
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    /**
     * Aprova um compromisso que está aguardando aprovação
     */
    public function approveCompromisso() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Método não permitido']);
                exit;
            } else {
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Obter o ID do compromisso
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Compromisso não especificado']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Compromisso não especificado';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Buscar o compromisso
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Compromisso não encontrado']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Compromisso não encontrado';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Verificar se o usuário é o dono da agenda
        $userId = $_SESSION['user_id'];
        $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
        
        if (!$isOwner) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Apenas o dono da agenda pode aprovar compromissos']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Apenas o dono da agenda pode aprovar compromissos';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Verificar se o compromisso está realmente aguardando aprovação
        if ($compromisso['status'] != 'aguardando_aprovacao') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Este compromisso não está aguardando aprovação']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Este compromisso não está aguardando aprovação';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Preparar dados para atualizar o status
        $data = [
            'title' => $compromisso['title'],
            'description' => $compromisso['description'],
            'start_datetime' => $compromisso['start_datetime'],
            'end_datetime' => $compromisso['end_datetime'],
            'location' => $compromisso['location'],
            'repeat_type' => $compromisso['repeat_type'],
            'repeat_until' => $compromisso['repeat_until'],
            'repeat_days' => $compromisso['repeat_days'],
            'status' => 'pendente' // Mudar para pendente após aprovação
        ];
        
        // Atualizar o status
        $result = $this->compromissoModel->update($id, $data);
        
        if ($result) {
            // Criar notificação para o criador do compromisso
            if (!empty($compromisso['created_by']) && $compromisso['created_by'] != $userId) {
                $this->createApprovalNotification($compromisso['created_by'], $id, 'approved');
            }
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => true, 'message' => 'Compromisso aprovado com sucesso']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Compromisso aprovado com sucesso';
                $_SESSION['flash_type'] = 'success';
            }
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Erro ao aprovar compromisso']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Erro ao aprovar compromisso';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        
        // Redirecionar apenas para requisições não-AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            // Preservar filtros da URL ao redirecionar
            $redirectUrl = BASE_URL . '/meuscompromissos';
            $queryParams = $this->getQueryParamsString();
            if ($queryParams) {
                $redirectUrl .= '?' . $queryParams;
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Rejeita um compromisso que está aguardando aprovação
     */
    public function rejectCompromisso() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Método não permitido']);
                exit;
            } else {
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Obter o ID do compromisso
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Compromisso não especificado']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Compromisso não especificado';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Buscar o compromisso
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Compromisso não encontrado']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Compromisso não encontrado';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Verificar se o usuário é o dono da agenda
        $userId = $_SESSION['user_id'];
        $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
        
        if (!$isOwner) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Apenas o dono da agenda pode rejeitar compromissos']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Apenas o dono da agenda pode rejeitar compromissos';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Verificar se o compromisso está realmente aguardando aprovação
        if ($compromisso['status'] != 'aguardando_aprovacao') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Este compromisso não está aguardando aprovação']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Este compromisso não está aguardando aprovação';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/meuscompromissos');
                exit;
            }
        }
        
        // Preparar dados para atualizar o status (cancelando o compromisso)
        $data = [
            'title' => $compromisso['title'],
            'description' => $compromisso['description'],
            'start_datetime' => $compromisso['start_datetime'],
            'end_datetime' => $compromisso['end_datetime'],
            'location' => $compromisso['location'],
            'repeat_type' => $compromisso['repeat_type'],
            'repeat_until' => $compromisso['repeat_until'],
            'repeat_days' => $compromisso['repeat_days'],
            'status' => 'cancelado' // Cancelar o compromisso ao rejeitar
        ];
        
        // Atualizar o status
        $result = $this->compromissoModel->update($id, $data);
        
        if ($result) {
            // Criar notificação para o criador do compromisso
            if (!empty($compromisso['created_by']) && $compromisso['created_by'] != $userId) {
                $this->createApprovalNotification($compromisso['created_by'], $id, 'rejected');
            }
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => true, 'message' => 'Compromisso rejeitado e cancelado']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Compromisso rejeitado e cancelado';
                $_SESSION['flash_type'] = 'success';
            }
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => 'Erro ao rejeitar compromisso']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Erro ao rejeitar compromisso';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        
        // Redirecionar apenas para requisições não-AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            // Preservar filtros da URL ao redirecionar
            $redirectUrl = BASE_URL . '/meuscompromissos';
            $queryParams = $this->getQueryParamsString();
            if ($queryParams) {
                $redirectUrl .= '?' . $queryParams;
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Obtém os parâmetros da query string atuais
     */
    private function getQueryParamsString() {
        $params = [];
        
        // Preservar página atual
        if (isset($_GET['page'])) {
            $params['page'] = $_GET['page'];
        }
        
        // Preservar filtros
        if (isset($_GET['agenda'])) {
            $params['agenda'] = $_GET['agenda'];
        }
        
        if (isset($_GET['status'])) {
            $params['status'] = $_GET['status'];
        }
        
        if (isset($_GET['period'])) {
            $params['period'] = $_GET['period'];
        }
        
        if (isset($_GET['search'])) {
            $params['search'] = $_GET['search'];
        }
        
        return http_build_query($params);
    }

    private function createApprovalNotification($userId, $compromissoId, $action) {
        // Verificar se o modelo de notificação está disponível
        if (!class_exists('Notification')) {
            require_once __DIR__ . '/../models/Notification.php';
        }
        
        $notificationModel = new Notification();
        
        // Buscar o compromisso
        $compromisso = $this->compromissoModel->getById($compromissoId);
        if (!$compromisso) return false;
        
        // Formatar data
        $dateObj = new DateTime($compromisso['start_datetime']);
        $formattedDate = $dateObj->format('d/m/Y \à\s H:i');
        
        // Definir mensagem baseada na ação
        if ($action === 'approved') {
            $message = "Seu compromisso \"{$compromisso['title']}\" foi aprovado para {$formattedDate}";
        } else {
            $message = "Seu compromisso \"{$compromisso['title']}\" para {$formattedDate} foi rejeitado";
        }
        
        // Criar notificação
        $notificationData = [
            'user_id' => $userId,
            'compromisso_id' => $compromissoId,
            'message' => $message,
            'is_read' => 0
        ];
        
        return $notificationModel->create($notificationData);
    }
}