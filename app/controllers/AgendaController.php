<?php
require_once __DIR__ . '/BaseController.php';
class AgendaController {
    private $agendaModel;
    

    public function __construct() {
        // Carregar o modelo de agenda
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Agenda.php';
        $this->agendaModel = new Agenda();
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você precisa estar logado para acessar essa página';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
    
    /**
     * Exibe a lista de agendas do usuário
     */
    public function index() {
        $userId = $_SESSION['user_id'];
        $search = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) : null;
        
        // Buscar todas as agendas acessíveis pelo usuário
        $agendas = $this->agendaModel->getAllAccessibleByUser($userId, $search);
        
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
            if ($agenda['is_owner']) {
                $agenda['can_be_deleted'] = $this->agendaModel->canBeDeleted($agenda['id']);
            } else {
                $agenda['can_be_deleted'] = false;
            }
        }
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/agendas/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Exibe o formulário para criar uma nova agenda
     */
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
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Obter os dados do formulário
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING) ?: '#3788d8';
        
        // Validar os dados
        if (empty($title)) {
            $_SESSION['flash_message'] = 'O título da agenda é obrigatório';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas/new');
            exit;
        }
        
        // Preparar os dados para salvar
        $data = [
            'user_id' => $_SESSION['user_id'],
            'title' => $title,
            'description' => $description,
            'is_public' => $isPublic,
            'color' => $color
        ];
        
        // Salvar no banco
        $result = $this->agendaModel->create($data);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Agenda criada com sucesso';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . BASE_URL . '/public/agendas');
        } else {
            $_SESSION['flash_message'] = 'Erro ao criar agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas/new');
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
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Verificar se a agenda pertence ao usuário
        if (!$this->agendaModel->belongsToUser($id, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para editar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Buscar os dados da agenda
        $agenda = $this->agendaModel->getById($id);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
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
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Obter o ID da agenda
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Verificar se a agenda pertence ao usuário
        if (!$this->agendaModel->belongsToUser($id, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para editar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Obter os dados do formulário
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $color = filter_input(INPUT_POST, 'color', FILTER_SANITIZE_STRING) ?: '#3788d8';
        
        // Validar os dados
        if (empty($title)) {
            $_SESSION['flash_message'] = 'O título da agenda é obrigatório';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas/edit?id=' . $id);
            exit;
        }
        
        // Preparar os dados para atualizar
        $data = [
            'title' => $title,
            'description' => $description,
            'is_public' => $isPublic,
            'color' => $color
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
        
        header('Location: ' . BASE_URL . '/public/agendas');
        exit;
    }
    
    /**
     * Exclui uma agenda
     */
    public function delete() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Obter o ID da agenda
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Verificar se a agenda pertence ao usuário
        if (!$this->agendaModel->belongsToUser($id, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para excluir esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Verificar se a agenda pode ser excluída
        if (!$this->agendaModel->canBeDeleted($id)) {
            $_SESSION['flash_message'] = 'Esta agenda não pode ser excluída pois possui compromissos pendentes ou aguardando aprovação';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Excluir a agenda
        $result = $this->agendaModel->delete($id);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Agenda excluída com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao excluir agenda';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/public/agendas');
        exit;
    }
}