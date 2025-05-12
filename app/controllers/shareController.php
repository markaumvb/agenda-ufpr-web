<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/AuthorizationService.php';

class ShareController extends BaseController {
    private $agendaModel;
    private $shareModel;
    private $userModel;
    private $authService;
    
    public function __construct() {
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/AgendaShare.php';
        require_once __DIR__ . '/../models/User.php';
        
        $this->agendaModel = new Agenda();
        $this->shareModel = new AgendaShare();
        $this->userModel = new User();
        $this->authService = new AuthorizationService();
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    
    public function index() {
        // Obter o ID da agenda da URL
        $agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
        
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para gerenciar compartilhamentos desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter a agenda
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter os compartilhamentos da agenda
        $shares = $this->shareModel->getSharesByAgenda($agendaId);
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/shares/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Adiciona um novo compartilhamento de agenda
     */
    public function add() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $canEdit = isset($_POST['can_edit']) ? true : false;
        
        // Validar dados
        if (!$agendaId || !$username) {
            $_SESSION['flash_message'] = 'Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para compartilhar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar o usuário pelo nome de usuário
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $_SESSION['flash_message'] = 'Usuário não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se o usuário não é o próprio dono da agenda
        if ($user['id'] == $_SESSION['user_id']) {
            $_SESSION['flash_message'] = 'Você não pode compartilhar a agenda com você mesmo';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
            exit;
        }
        
        // Compartilhar a agenda
        $result = $this->shareModel->shareAgenda($agendaId, $user['id'], $canEdit);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Agenda compartilhada com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao compartilhar agenda';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }
    
    /**
     * Remove um compartilhamento de agenda
     */
    public function remove() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        // Validar dados
        if (!$agendaId || !$userId) {
            $_SESSION['flash_message'] = 'Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para gerenciar compartilhamentos desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Remover o compartilhamento
        $result = $this->shareModel->removeShare($agendaId, $userId);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Compartilhamento removido com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao remover compartilhamento';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }
    
    /**
     * Atualiza as permissões de um compartilhamento
     */
    public function updatePermission() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $canEdit = isset($_POST['can_edit']) ? true : false;
        
        // Validar dados
        if (!$agendaId || !$userId) {
            $_SESSION['flash_message'] = 'Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para gerenciar compartilhamentos desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Atualizar o compartilhamento
        $result = $this->shareModel->shareAgenda($agendaId, $userId, $canEdit);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Permissões atualizadas com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao atualizar permissões';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }
    
public function shared() {
    $userId = $_SESSION['user_id'];
    
    // Processar parâmetro de busca
    $search = isset($_GET['search']) ? htmlspecialchars(filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '') : null;
    
    // Parâmetros de paginação
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10; // Número de itens por página
    
    // Verificar se o usuário está logado
    $this->checkAuth();
    
    // Buscar agendas compartilhadas com o usuário (com paginação e busca)
    $sharedWithMe = $this->shareModel->getSharedWithUser($userId, true, $page, $perPage, $search);
    $totalSharedWithMe = $this->shareModel->countSharedWithUser($userId, true, $search);
    
    // Buscar agendas que o usuário compartilhou (com busca)
    $mySharedAgendas = $this->shareModel->getAgendasSharedByUser($userId, $search);
    
    // Calcular número total de páginas para paginação
    $totalPages = ceil($totalSharedWithMe / $perPage);
    
    // Carregar view
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/shares/shared.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}
    
    public function generatePublicUrl() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter o ID da agenda
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para alterar a visibilidade desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar a agenda
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Definir nova visibilidade (alternar entre público e privado)
        $newIsPublic = !$agenda['is_public'];
        
        // Se estiver tornando a agenda pública, gerar um hash
        $hash = $agenda['public_hash'];
        if ($newIsPublic && empty($hash)) {
            $hash = md5(uniqid(rand(), true));
        }
        
        // Atualizar a agenda - PRESERVANDO o valor is_active
        $result = $this->agendaModel->update($agendaId, [
            'title' => $agenda['title'],
            'description' => $agenda['description'],
            'is_public' => $newIsPublic,
            'color' => $agenda['color'],
            'is_active' => $agenda['is_active'] // Preservar o valor atual
        ]);
        
        // Se estiver tornando a agenda pública, atualizar o hash
        if ($newIsPublic && empty($agenda['public_hash'])) {
            $this->agendaModel->updatePublicHash($agendaId, $hash);
        }
        
        if ($result) {
            if ($newIsPublic) {
                $_SESSION['flash_message'] = 'Agenda marcada como pública com sucesso';
            } else {
                $_SESSION['flash_message'] = 'Agenda marcada como privada com sucesso';
            }
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao alterar visibilidade da agenda';
            $_SESSION['flash_type'] = 'danger';
        }
        
        // Redirecionar para a página de compartilhamentos
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }
}