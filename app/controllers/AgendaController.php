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
    
    // CORRIGIDO: Processar parâmetro de busca igual à home page
    $search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
    $includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] == 1;
    
    // Página atual para paginação
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 12; // 12 agendas por página
    
    // CORRIGIDO: Buscar agendas com lógica consistente
    if (!empty($search)) {
        // Se há busca, usar busca específica
        $agendas = $this->agendaModel->getAllByUser($userId, $search, $includeInactive, $page, $perPage);
        $totalAgendas = $this->agendaModel->countByUser($userId, $search, $includeInactive);
    } else {
        // Se não há busca, buscar todas
        $agendas = $this->agendaModel->getAllByUser($userId, null, $includeInactive, $page, $perPage);
        $totalAgendas = $this->agendaModel->countByUser($userId, null, $includeInactive);
    }
    
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
    
    // ADICIONADO: Dados de paginação para a view
    $paginationData = [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $totalAgendas,
        'per_page' => $perPage,
        'start_item' => ($page - 1) * $perPage + 1,
        'end_item' => min($page * $perPage, $totalAgendas),
        'search' => $search
    ];
    
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
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING) ?? '');
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '');
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING) ?: '#3788d8';
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
        $_SESSION['flash_message'] = 'Usuário não autenticado.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . PUBLIC_URL . '/login');
        exit;
    }
    
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['flash_message'] = 'Método de requisição inválido.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    // Verificar se o ID da agenda foi enviado
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $_SESSION['flash_message'] = 'ID da agenda não fornecido ou inválido.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    $agendaId = (int)$_POST['id'];
    $userId = $_SESSION['user_id'];
    
    try {
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
        
        // CORRIGIDO: Verificação aprimorada de compromissos
        $stats = $agendaModel->countCompromissosByStatus($agendaId);
        
        // Log para debug
        error_log("Tentativa de exclusão da agenda {$agendaId} - Stats: " . json_encode($stats));
        
        // Verificar se há compromissos que impedem a exclusão
        $hasBlockingAppointments = (
            $stats['realizados'] > 0 || 
            $stats['cancelados'] > 0 || 
            $stats['aguardando_aprovacao'] > 0
        );
        
        if ($hasBlockingAppointments) {
            $messages = [];
            if ($stats['realizados'] > 0) {
                $messages[] = $stats['realizados'] . ' compromisso(s) realizado(s)';
            }
            if ($stats['cancelados'] > 0) {
                $messages[] = $stats['cancelados'] . ' compromisso(s) cancelado(s)';
            }
            if ($stats['aguardando_aprovacao'] > 0) {
                $messages[] = $stats['aguardando_aprovacao'] . ' compromisso(s) aguardando aprovação';
            }
            
            $_SESSION['flash_message'] = 'Não é possível excluir a agenda "' . htmlspecialchars($agenda['title']) . '" pois há ' . implode(', ', $messages) . '.';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . PUBLIC_URL . '/agendas');
            exit;
        }
        
        // CORRIGIDO: Melhor controle de transação
        $connection = $agendaModel->db;
        
        // Verificar se já está em transação
        if ($connection->inTransaction()) {
            error_log("Aviso: Transação já iniciada, fazendo rollback preventivo");
            $connection->rollBack();
        }
        
        // Iniciar nova transação
        $connection->beginTransaction();
        
        try {
            // Se há compromissos pendentes, avisar que serão excluídos junto
            if ($stats['pendentes'] > 0) {
                error_log("Excluindo agenda '{$agenda['title']}' com {$stats['pendentes']} compromisso(s) pendente(s)");
            }
            
            // 1. Exclui todos os compartilhamentos da agenda
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $shareResult = $shareModel->deleteAllFromAgenda($agendaId);
            
            if (!$shareResult) {
                throw new Exception("Falha ao excluir compartilhamentos da agenda");
            }
            
            // 2. Exclui todas as notificações relacionadas aos compromissos da agenda
            $notificationDeleteQuery = "DELETE FROM notifications WHERE compromisso_id IN (SELECT id FROM compromissos WHERE agenda_id = ?)";
            $notificationStmt = $connection->prepare($notificationDeleteQuery);
            $notificationResult = $notificationStmt->execute([$agendaId]);
            
            if (!$notificationResult) {
                throw new Exception("Falha ao excluir notificações da agenda");
            }
            
            // 3. Exclui todos os compromissos (pendentes e outros se houver)
            $compromissoDeleteQuery = "DELETE FROM compromissos WHERE agenda_id = ?";
            $compromissoStmt = $connection->prepare($compromissoDeleteQuery);
            $compromissoResult = $compromissoStmt->execute([$agendaId]);
            
            if (!compromissoResult) {
                throw new Exception("Falha ao excluir compromissos da agenda");
            }
            
            // 4. Exclui a agenda
            $agendaDeleteQuery = "DELETE FROM agendas WHERE id = ?";
            $agendaStmt = $connection->prepare($agendaDeleteQuery);
            $agendaResult = $agendaStmt->execute([$agendaId]);
            
            if (!$agendaResult) {
                throw new Exception("Falha ao excluir a agenda");
            }
            
            // Se chegou até aqui, faz commit
            $connection->commit();
            
            // Mensagem de sucesso
            $message = 'Agenda "' . htmlspecialchars($agenda['title']) . '" excluída com sucesso!';
            if ($stats['pendentes'] > 0) {
                $message .= ' (' . $stats['pendentes'] . ' compromisso(s) pendente(s) também foram excluídos)';
            }
            
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = 'success';
            
            error_log("Agenda {$agendaId} excluída com sucesso pelo usuário {$userId}");
            
        } catch (Exception $e) {
            // Rollback da transação em caso de erro
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            
            // Re-throw para ser capturado pelo catch externo
            throw $e;
        }
        
    } catch (Exception $e) {
        // Log do erro detalhado
        error_log('Erro detalhado ao excluir agenda ' . $agendaId . ': ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        
        // Verificar se há transação pendente e fazer rollback
        if (isset($connection) && $connection->inTransaction()) {
            try {
                $connection->rollBack();
                error_log('Rollback realizado com sucesso');
            } catch (Exception $rollbackError) {
                error_log('Erro no rollback: ' . $rollbackError->getMessage());
            }
        }
        
        // Mensagem de erro para o usuário (sem expor detalhes técnicos)
        $_SESSION['flash_message'] = 'Erro ao excluir a agenda. Por favor, tente novamente ou contate o administrador.';
        $_SESSION['flash_type'] = 'danger';
    }
    
    // Redirecionar sempre para a lista de agendas
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
     * Exibe todas as agendas organizadas por tipo - CORRIGIDO COM BUSCA
     */
public function allAgendas() {
    // Verificar se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // CORRIGIDO: Processar parâmetro de busca igual à home page
    $search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : '';
    
    // Inicializar modelos localmente
    require_once __DIR__ . '/../models/Agenda.php';
    $agendaModel = new Agenda();
    
    require_once __DIR__ . '/../models/AgendaShare.php';
    $shareModel = new AgendaShare();
    
    // Parâmetros de paginação
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 12; // 12 agendas por página
    
    // CORRIGIDO: Inicializar todas as variáveis sempre
    $myAgendas = [];
    $totalMyAgendas = 0;
    $sharedAgendas = [];
    $totalSharedAgendas = 0;
    $publicAgendas = [];
    $totalPublicAgendas = 0;
    
    // CORRIGIDO: Buscar agendas do usuário (dono) COM BUSCA CONSISTENTE
    if (!empty($search)) {
        $myAgendas = $agendaModel->getAllByUser($userId, $search, true, $page, $perPage);
        $totalMyAgendas = $agendaModel->countByUser($userId, $search, true);
    } else {
        $myAgendas = $agendaModel->getAllByUser($userId, null, true, $page, $perPage);
        $totalMyAgendas = $agendaModel->countByUser($userId, null, true);
    }
    
    // Remover duplicatas usando IDs
    $uniqueAgendas = [];
    $uniqueIds = [];
    foreach ($myAgendas as $agenda) {
        if (!in_array($agenda['id'], $uniqueIds)) {
            $uniqueIds[] = $agenda['id'];
            $uniqueAgendas[] = $agenda;
        }
    }
    $myAgendas = $uniqueAgendas;
    
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
    
    // CORRIGIDO: Buscar agendas compartilhadas COM BUSCA CONSISTENTE
    if (!empty($search)) {
        $sharedAgendas = $shareModel->getSharedWithUser($userId, true, $page, $perPage, $search);
        $totalSharedAgendas = $shareModel->countSharedWithUser($userId, true, $search);
    } else {
        $sharedAgendas = $shareModel->getSharedWithUser($userId, true, $page, $perPage, null);
        $totalSharedAgendas = $shareModel->countSharedWithUser($userId, true, null);
    }
    
    // CORRIGIDO: Buscar agendas públicas COM BUSCA CONSISTENTE
    if (!empty($search)) {
        // Se há busca, usar método de busca específico
        $publicAgendas = $agendaModel->searchPublicAgendas($search, $page, $perPage, $userId);
        $totalPublicAgendas = $agendaModel->countPublicAgendasWithSearch($search, $userId);
    } else {
        // Se não há busca, usar método normal
        $publicAgendas = $agendaModel->getPublicAgendas($userId, true, $page, $perPage);
        $totalPublicAgendas = $agendaModel->countPublicAgendas($userId, true);
    }
    
    // Calcular total de páginas para cada seção
    $totalPagesMyAgendas = ceil($totalMyAgendas / $perPage);
    $totalPagesSharedAgendas = ceil($totalSharedAgendas / $perPage);
    $totalPagesPublicAgendas = ceil($totalPublicAgendas / $perPage);
    
    // ADICIONADO: Dados de paginação para a view
    $paginationData = [
        'current_page' => $page,
        'total_pages' => max($totalPagesMyAgendas, $totalPagesSharedAgendas, $totalPagesPublicAgendas),
        'total_items' => $totalMyAgendas + $totalSharedAgendas + $totalPublicAgendas,
        'per_page' => $perPage,
        'search' => $search
    ];
    
    // Carregar view
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/agendas/all.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}
}