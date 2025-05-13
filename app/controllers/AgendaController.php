<?php
require_once __DIR__ . '/BaseController.php';
class AgendaController extends BaseController {
    private $agendaModel;
    

    public function __construct() {
        // Carregar o modelo de agenda
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Agenda.php';
        $this->agendaModel = new Agenda();
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    

    public function index() {
    $userId = $_SESSION['user_id'];
    
    // Correção: Processar parâmetro de busca corretamente
    $search = isset($_GET['search']) ? htmlspecialchars(filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '') : null;
    $includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] == 1;
    
    // Página atual para paginação
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 12; // 12 agendas por página
    
    // Buscar apenas as agendas do usuário (não as compartilhadas ou públicas)
    $agendas = $this->agendaModel->getAllByUser($userId, $search, $includeInactive, $page, $perPage);
    $totalAgendas = $this->agendaModel->countByUser($userId, $search, $includeInactive);
    
    // Calcular total de páginas
    $totalPages = ceil($totalAgendas / $perPage);
    
    // Para cada agenda, adicionar a contagem de compromissos por status
    foreach ($agendas as &$agenda) {
        $stats = $this->agendaModel->countCompromissosByStatus($agenda['id']);
        $agenda['compromissos'] = $stats ?: [
            'realizados' => 0,
            'cancelados' => 0,
            'pendentes' => 0,
            'aguardando_aprovacao' => 0,
            'total' => 0
        ];
        
        // Verificar se a agenda pode ser excluída (apenas para agendas próprias)
        $agenda['can_be_deleted'] = $this->agendaModel->canBeDeleted($agenda['id']);
    }
    
    // Exibir a view
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/agendas/index.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}

    public function create() {
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/agendas/create.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Salva uma nova agenda no banco de dados
     */
    public function store() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter os dados do formulário
        $title = htmlspecialchars(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW) ?? '');
        $description = htmlspecialchars(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW) ?? '');
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $color = filter_input(INPUT_POST, 'color', FILTER_UNSAFE_RAW) ?: '#3788d8';
        $minTimeBefore = filter_input(INPUT_POST, 'min_time_before', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0, 'max_range' => 48]]);
        // Validar os dados     
        if (empty($title)) {
            $_SESSION['flash_message'] = 'O título da agenda é obrigatório';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas/new');
            exit;
        }
        
        // Preparar os dados para salvar
        $data = [
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'description' => $description,
            'is_public' => $isPublic,
            'is_active' => $isActive,
            'color' => $color,
            'min_time_before' => $minTimeBefore
        ];
        
        // Salvar no banco
        $result = $this->agendaModel->create($data);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Agenda criada com sucesso';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/agendas');
        } else {
            $_SESSION['flash_message'] = 'Erro ao criar agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas/new');
        }
        
        exit;
    }
    
    /**
     * Exibe o formulário para editar uma agenda
     */
    public function edit() {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se a agenda pertence ao usuário
        if (!$this->agendaModel->belongsToUser($id, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para editar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar os dados da agenda
        $agenda = $this->agendaModel->getById($id);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/agendas/edit.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Atualiza uma agenda no banco de dados
     */
    public function update() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter o ID da agenda
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se a agenda pertence ao usuário
        if (!$this->agendaModel->belongsToUser($id, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para editar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter os dados do formulário
        $title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
        $description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW);
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $color = htmlspecialchars(filter_input(INPUT_POST, 'color', FILTER_UNSAFE_RAW) ?? '') ?: '#3788d8';
        $minTimeBefore = filter_input(INPUT_POST, 'min_time_before', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0, 'max_range' => 48]]);

        
        // Validar os dados
        if (empty($title)) {
            $_SESSION['flash_message'] = 'O título da agenda é obrigatório';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas/edit?id=' . $id);
            exit;
        }
        
        // Preparar os dados para atualizar
        $data = [
            'title' => $title,
            'description' => $description,
            'is_public' => $isPublic,
            'is_active' => $isActive,
            'color' => $color,
            'min_time_before' => $minTimeBefore
        ];
        
        // Atualizar no banco
        $result = $this->agendaModel->update($id, $data);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Agenda atualizada com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao atualizar agenda';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/agendas');
        exit;
    }
    
    public function delete() {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        // Verificar se o ID da agenda foi enviado
        if (!isset($_POST['id'])) {
            $_SESSION['flash_message'] = 'ID da agenda não fornecido.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        $agendaId = $_POST['id'];
        $userId = $_SESSION['user_id'];
        
        // Carregar os modelos necessários
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../services/AuthorizationService.php';
        
        $agendaModel = new Agenda();
        $compromissoModel = new Compromisso();
        $authService = new AuthorizationService();
        
        // Verificar se a agenda existe
        $agenda = $agendaModel->getById($agendaId);
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        $isOwner = $authService->isAgendaOwner($agendaId, $userId);
        
        if (!$isOwner) {
            $_SESSION['flash_message'] = 'Você não tem permissão para excluir esta agenda.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        // Verificar se há compromissos realizados ou cancelados
        $realizadosCount = $compromissoModel->countByStatus($agendaId, 'realizado');
        $canceladosCount = $compromissoModel->countByStatus($agendaId, 'cancelado');
        
        if ($realizadosCount > 0 || $canceladosCount > 0) {
            $_SESSION['flash_message'] = 'Não é possível excluir a agenda pois há compromissos realizados ou cancelados.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        // Exclui todos os compartilhamentos da agenda
        require_once __DIR__ . '/../models/AgendaShare.php';
        $shareModel = new AgendaShare();
        $shareModel->deleteAllFromAgenda($agendaId);
        
        // Exclui todos os compromissos pendentes
        $compromissoModel->deleteAllFromAgenda($agendaId);
        
        // Exclui a agenda
        $result = $agendaModel->delete($agendaId);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Agenda excluída com sucesso!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao excluir agenda.';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }

    public function toggleActive() {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        // Verificar se os dados necessários foram enviados
        if (!isset($_POST['id']) || !isset($_POST['is_active'])) {
            $_SESSION['flash_message'] = 'Dados incompletos.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        $agendaId = $_POST['id'];
        $isActive = $_POST['is_active'] == '1'; // Converter para booleano
        $userId = $_SESSION['user_id'];
        
        // Carregar os modelos necessários
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../services/AuthorizationService.php';
        
        $agendaModel = new Agenda();
        $compromissoModel = new Compromisso();
        $authService = new AuthorizationService();
        
        // Verificar se a agenda existe
        $agenda = $agendaModel->getById($agendaId);
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        $isOwner = $authService->isAgendaOwner($agendaId, $userId);
        
        if (!$isOwner) {
            $_SESSION['flash_message'] = 'Você não tem permissão para alterar esta agenda.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        // Se estiver tentando desativar a agenda
        if (!$isActive) {
            // Verificar se há compromissos pendentes ou aguardando aprovação
            $pendingCount = $compromissoModel->countByStatus($agendaId, 'pendente');
            $awaitingCount = $compromissoModel->countByStatus($agendaId, 'aguardando_aprovacao');
            
            if ($pendingCount > 0 || $awaitingCount > 0) {
                $_SESSION['flash_message'] = 'Não é possível desativar a agenda pois há compromissos pendentes ou aguardando aprovação.';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . PUBLIC_URL . '/agendas');
                exit;
            }
        }
        
        // Atualizar o status da agenda
        $result = $agendaModel->updateStatus($agendaId, $isActive);
        
        if ($result) {
            $_SESSION['flash_message'] = $isActive ? 'Agenda ativada com sucesso!' : 'Agenda desativada com sucesso!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao atualizar o status da agenda.';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }

    /**
 * Exibe todas as agendas organizadas por tipo
 */

public function allAgendas() {
    // Verificar se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Processar parâmetro de busca
    $search = isset($_GET['search']) ? htmlspecialchars(filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '') : null;
    
    // Inicializar modelos localmente
    require_once __DIR__ . '/../models/Agenda.php';
    $agendaModel = new Agenda();
    
    require_once __DIR__ . '/../models/AgendaShare.php';
    $shareModel = new AgendaShare();
    
    // Parâmetros de paginação
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 12; // 12 agendas por página
    
    // Buscar agendas do usuário (dono)
    $myAgendas = $agendaModel->getAllByUser($userId, $search, true, $page, $perPage);
    
    // ADICIONAR ESTA PARTE: Remover duplicatas usando IDs
    $uniqueAgendas = [];
    $uniqueIds = [];
    foreach ($myAgendas as $agenda) {
        if (!in_array($agenda['id'], $uniqueIds)) {
            $uniqueIds[] = $agenda['id'];
            $uniqueAgendas[] = $agenda;
        }
    }
    $myAgendas = $uniqueAgendas;
    
    $totalMyAgendas = $agendaModel->countByUser($userId, $search, true);
    
    // Adicionar contagem de compromissos para cada agenda
    foreach ($myAgendas as &$agenda) {
        $stats = $agendaModel->countCompromissosByStatus($agenda['id']);
        $agenda['compromissos'] = $stats ?: [
            'pendentes' => 0,
            'realizados' => 0,
            'cancelados' => 0,
            'aguardando_aprovacao' => 0,
            'total' => 0
        ];
    }
    
    // Buscar agendas compartilhadas com o usuário
    $sharedAgendas = $shareModel->getSharedWithUser($userId, true, $page, $perPage);
    $totalSharedAgendas = $shareModel->countSharedWithUser($userId, true);
    
    // Buscar agendas públicas (excluindo as que o usuário já tem acesso)
    $publicAgendas = $agendaModel->getPublicAgendas($userId, true, $page, $perPage);
    $totalPublicAgendas = $agendaModel->countPublicAgendas($userId, true);
    
    // Carregar view
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/agendas/all.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}
}