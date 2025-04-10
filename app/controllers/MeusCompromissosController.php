<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/AuthorizationService.php';

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
     * Exibe a página principal com todos os compromissos do usuário agrupados por agenda
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        
        // Obter todas as agendas acessíveis pelo usuário (próprias e compartilhadas)
        $agendas = $this->agendaModel->getAllAccessibleByUser($userId);
        
        // Para cada agenda, buscar os compromissos
        $agendasWithCompromissos = [];
        
        foreach ($agendas as $agenda) {
            // Definir se o usuário é o dono da agenda
            $isOwner = ($agenda['user_id'] == $userId);
            
            // Verificar permissões de edição para usuários com compartilhamento
            $canEdit = $isOwner;
            if (!$isOwner) {
                $canEdit = $this->shareModel->canEdit($agenda['id'], $userId);
            }
            
            // Buscar compromissos da agenda
            $compromissos = $this->compromissoModel->getAllByAgenda($agenda['id']);
            
            // Filtrar compromissos se necessário (por exemplo, para agendas compartilhadas,
            // pode mostrar apenas os criados pelo usuário e os pendentes)
            
            // Adicionar informações extras a cada compromisso
            foreach ($compromissos as &$compromisso) {
                $compromisso['is_owner'] = $isOwner;
                $compromisso['can_edit'] = $canEdit;
                $compromisso['created_by_current_user'] = ($compromisso['created_by'] == $userId);
            }
            
            // Adicionar a agenda com seus compromissos ao array
            if (!empty($compromissos)) {
                $agenda['compromissos'] = $compromissos;
                $agenda['is_owner'] = $isOwner;
                $agenda['can_edit'] = $canEdit;
                $agendasWithCompromissos[] = $agenda;
            }
        }
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/meuscompromissos/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
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
        
        header('Location: ' . BASE_URL . '/meuscompromissos');
        exit;
    }
    
    /**
     * Processa a edição de um compromisso, colocando-o em status de aprovação se necessário
     */
    public function editCompromisso() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Obter dados do formulário
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $startDatetime = filter_input(INPUT_POST, 'start_datetime', FILTER_SANITIZE_STRING);
        $endDatetime = filter_input(INPUT_POST, 'end_datetime', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        
        // Validar dados obrigatórios
        if (!$id || !$title || !$startDatetime || !$endDatetime) {
            $_SESSION['flash_message'] = 'Todos os campos obrigatórios devem ser preenchidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Buscar o compromisso atual
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            $_SESSION['flash_message'] = 'Compromisso não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda ou tem permissão para editar
        $userId = $_SESSION['user_id'];
        $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
        $canEdit = $isOwner || ($compromisso['created_by'] == $userId);
        
        if (!$canEdit) {
            $_SESSION['flash_message'] = 'Você não tem permissão para editar este compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Verificar se há conflito de horário com outros compromissos
        if ($this->compromissoModel->hasTimeConflict($compromisso['agenda_id'], $startDatetime, $endDatetime, $id)) {
            $_SESSION['flash_message'] = 'Existe um conflito de horário com outro compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Definir o status apropriado:
        // - Se o usuário é dono da agenda, mantém o status atual ou define para 'pendente'
        // - Se não é dono, coloca em 'aguardando_aprovacao'
        $status = $isOwner ? ($compromisso['status'] == 'cancelado' ? 'cancelado' : 'pendente') : 'aguardando_aprovacao';
        
        // Preparar dados para atualizar
        $data = [
            'title' => $title,
            'description' => $description,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => $location,
            'status' => $status,
            'repeat_type' => $compromisso['repeat_type'],
            'repeat_until' => $compromisso['repeat_until'],
            'repeat_days' => $compromisso['repeat_days']
        ];
        
        // Atualizar no banco
        $result = $this->compromissoModel->update($id, $data);
        
        if ($result) {
            if ($status == 'aguardando_aprovacao') {
                $_SESSION['flash_message'] = 'Compromisso atualizado e aguardando aprovação do dono da agenda';
            } else {
                $_SESSION['flash_message'] = 'Compromisso atualizado com sucesso';
            }
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao atualizar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/meuscompromissos');
        exit;
    }
    
    /**
     * Aprova um compromisso que está aguardando aprovação
     */
    public function approveCompromisso() {
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
        
        // Verificar se o usuário é o dono da agenda
        $userId = $_SESSION['user_id'];
        $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
        
        if (!$isOwner) {
            $_SESSION['flash_message'] = 'Apenas o dono da agenda pode aprovar compromissos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Verificar se o compromisso está realmente aguardando aprovação
        if ($compromisso['status'] != 'aguardando_aprovacao') {
            $_SESSION['flash_message'] = 'Este compromisso não está aguardando aprovação';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
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
            $_SESSION['flash_message'] = 'Compromisso aprovado com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao aprovar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/meuscompromissos');
        exit;
    }
    
    /**
     * Rejeita um compromisso que está aguardando aprovação
     */
    public function rejectCompromisso() {
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
        
        // Verificar se o usuário é o dono da agenda
        $userId = $_SESSION['user_id'];
        $isOwner = $this->agendaModel->belongsToUser($compromisso['agenda_id'], $userId);
        
        if (!$isOwner) {
            $_SESSION['flash_message'] = 'Apenas o dono da agenda pode rejeitar compromissos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Verificar se o compromisso está realmente aguardando aprovação
        if ($compromisso['status'] != 'aguardando_aprovacao') {
            $_SESSION['flash_message'] = 'Este compromisso não está aguardando aprovação';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/meuscompromissos');
            exit;
        }
        
        // Preparar dados para atualizar o status (voltando ao estado anterior ou cancelando)
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
            $_SESSION['flash_message'] = 'Compromisso rejeitado e cancelado';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao rejeitar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/meuscompromissos');
        exit;
    }
}