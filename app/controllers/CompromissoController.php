<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/CalendarService.php';
require_once __DIR__ . '/../services/AuthorizationService.php';

class CompromissoController extends BaseController {
    private $compromissoModel;
    private $agendaModel;
    private $calendarService;
    private $authService;
    

    public function __construct() {
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../models/Agenda.php';
        
        $this->compromissoModel = new Compromisso();
        $this->agendaModel = new Agenda();
        $this->calendarService = new CalendarService();
        $this->authService = new AuthorizationService();
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    

    public function index() {
        // Obter o ID da agenda da URL
        $agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
        
        // Se não foi fornecido um ID de agenda, redirecionar para a lista de agendas
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se a agenda existe e se o usuário tem acesso a ela
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        $canAccess = $agenda['is_public'] || $agenda['user_id'] == $_SESSION['user_id'];
        
        if (!$canAccess) {
            // Verificar se há compartilhamento
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canAccess = $shareModel->checkAccess($agendaId, $_SESSION['user_id']);
        }
        
        if (!$canAccess) {
            $_SESSION['flash_message'] = 'Você não tem permissão para acessar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter mês e ano do calendário da URL ou usar o mês atual
        $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT) ?: date('n');
        $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: date('Y');
        
        // Validar mês e ano
        if ($month < 1 || $month > 12) $month = date('n');
        if ($year < 2000 || $year > 2100) $year = date('Y');
        
        // Calcular datas de início e fim do mês
        $firstDay = new DateTime("$year-$month-01");
        $lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));
        
        // Obter os compromissos do mês
        $compromissos = $this->compromissoModel->getByAgendaAndDateRange(
            $agendaId,
            $firstDay->format('Y-m-d'),
            $lastDay->format('Y-m-d')
        );
        
        // Preparar dados para a view
        $calendarData = $this->calendarService->prepareCalendarData($month, $year, $compromissos);
        
        // Obter todos os compromissos da agenda para a lista
        $allCompromissos = $this->compromissoModel->getAllByAgenda($agendaId);
        
        // Verificar se o usuário é o dono da agenda
        $isOwner = $agenda['user_id'] == $_SESSION['user_id'];
        
        // Verificar permissões de edição para usuários com compartilhamento
        $canEdit = $isOwner;
        if (!$isOwner) {
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canEdit = $shareModel->canEdit($agendaId, $_SESSION['user_id']);
            // Adicionar flag à agenda para uso na view
            $agenda['can_edit'] = $canEdit;
        }
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/compromissos/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    

    public function create() {
        // Obter o ID da agenda da URL
        $agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
        
        // Se não foi fornecido um ID de agenda, redirecionar para a lista de agendas
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se a agenda existe e se o usuário tem acesso a ela
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }

        if (!$agenda['is_active']) {
            $_SESSION['flash_message'] = 'Não é possível criar compromissos em uma agenda desativada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda ou tem permissão para editar
        $isOwner = $agenda['user_id'] == $_SESSION['user_id'];
        $canEdit = $isOwner;
        
        if (!$isOwner) {
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canEdit = $shareModel->canEdit($agendaId, $_SESSION['user_id']);
        }
        
        if (!$canEdit) {
            $_SESSION['flash_message'] = 'Você não tem permissão para criar compromissos nesta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $agendaId);
            exit;
        }
        
        // Data e hora padrão (próxima hora)
        $defaultDate = new DateTime();
        $defaultDate->setTime($defaultDate->format('H') + 1, 0);
        $defaultStartDateTime = $defaultDate->format('Y-m-d\TH:i');
        
        $defaultEndDate = clone $defaultDate;
        $defaultEndDate->modify('+1 hour');
        $defaultEndDateTime = $defaultEndDate->format('Y-m-d\TH:i');
        
        // Obter datas pré-selecionadas da URL (se houver)
        $selectedDate = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
        if ($selectedDate) {
            $selectedDate = new DateTime($selectedDate);
            $defaultStartDateTime = $selectedDate->format('Y-m-d\TH:i');
            
            $defaultEndDate = clone $selectedDate;
            $defaultEndDate->modify('+1 hour');
            $defaultEndDateTime = $defaultEndDate->format('Y-m-d\TH:i');
        }
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/compromissos/create.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Salva um novo compromisso no banco de dados
     */
    public function store() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $title = htmlspecialchars(filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW) ?? '');
        $description = htmlspecialchars(filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW) ?? '');
        $startDatetime = htmlspecialchars(filter_input(INPUT_POST, 'start_datetime', FILTER_UNSAFE_RAW) ?? '');
        $endDatetime = htmlspecialchars(filter_input(INPUT_POST, 'end_datetime', FILTER_UNSAFE_RAW) ?? '');
        $location = htmlspecialchars(filter_input(INPUT_POST, 'location', FILTER_UNSAFE_RAW) ?? '');
        $repeatType = htmlspecialchars(filter_input(INPUT_POST, 'repeat_type', FILTER_UNSAFE_RAW) ?? '');
        $repeatUntil = htmlspecialchars(filter_input(INPUT_POST, 'repeat_until', FILTER_UNSAFE_RAW) ?? '');
        $repeatDays = isset($_POST['repeat_days']) ? implode(',', $_POST['repeat_days']) : null;
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?: 'pendente';
        
        // Validar dados obrigatórios
        if (!$agendaId || !$title || !$startDatetime || !$endDatetime) {
            $_SESSION['flash_message'] = 'Todos os campos obrigatórios devem ser preenchidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos/new?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se a agenda existe e se o usuário tem acesso a ela
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }

        // Verificar se a agenda está ativa
    if (!$agenda['is_active']) {
        $_SESSION['flash_message'] = 'Não é possível criar compromissos em uma agenda desativada';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $agendaId);
        exit;
    }
    
        
        // Verificar se o usuário é o dono da agenda ou tem permissão para editar
        $isOwner = $agenda['user_id'] == $_SESSION['user_id'];
        $canEdit = $isOwner;
        
        if (!$isOwner) {
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canEdit = $shareModel->canEdit($agendaId, $_SESSION['user_id']);
        }
        
        if (!$canEdit) {
            $_SESSION['flash_message'] = 'Você não tem permissão para criar compromissos nesta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $agendaId);
            exit;
        }
        
        // Para eventos recorrentes, validar data final da recorrência
        if ($repeatType != 'none' && empty($repeatUntil)) {
            $_SESSION['flash_message'] = 'Para eventos recorrentes, é necessário definir uma data final';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos/new?agenda_id=' . $agendaId);
            exit;
        }
        
        // Para eventos com dias específicos, validar se pelo menos um dia foi selecionado
        if ($repeatType == 'specific_days' && empty($repeatDays)) {
            $_SESSION['flash_message'] = 'Selecione pelo menos um dia da semana para a recorrência';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos/new?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se há conflito de horário
        if ($this->compromissoModel->hasTimeConflict($agendaId, $startDatetime, $endDatetime)) {
            $_SESSION['flash_message'] = 'Existe um conflito de horário com outro compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos/new?agenda_id=' . $agendaId);
            exit;
        }
        
        // Preparar dados para salvar
        $data = [
            'agenda_id' => $agendaId,
            'title' => $title,
            'description' => $description,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => $location,
            'repeat_type' => $repeatType,
            'repeat_until' => ($repeatType != 'none' && $repeatUntil) ? $repeatUntil : null,
            'repeat_days' => ($repeatType == 'specific_days' && $repeatDays) ? $repeatDays : null,
            'status' => $status
        ];
        
        // Salvar no banco
        $result = $this->compromissoModel->create($data);
        
        if ($result) {
            // Enviar notificação para o dono da agenda
            if ($_SESSION['user_id'] != $agenda['user_id']) {
                // Adicionar notificação (você precisaria criar um modelo NotificationModel para isso)
                // $notificationModel->create([...]);
            }
            
            $_SESSION['flash_message'] = 'Compromisso criado com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao criar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $agendaId);
        exit;
    }
    
    /**
     * Exibe o formulário para editar um compromisso
     */
    public function edit() {
        // Obter o ID do compromisso da URL
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Compromisso não especificado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar o compromisso
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            $_SESSION['flash_message'] = 'Compromisso não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar a agenda do compromisso
        $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
        
        // Verificar se o usuário é o dono da agenda ou tem permissão para editar
        $isOwner = $agenda['user_id'] == $_SESSION['user_id'];
        $canEdit = $isOwner;
        
        if (!$isOwner) {
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canEdit = $shareModel->canEdit($compromisso['agenda_id'], $_SESSION['user_id']);
        }
        
        if (!$canEdit) {
            $_SESSION['flash_message'] = 'Você não tem permissão para editar este compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
            exit;
        }
        
        // Formatar datas para o formulário HTML5
        $compromisso['start_datetime'] = (new DateTime($compromisso['start_datetime']))->format('Y-m-d\TH:i');
        $compromisso['end_datetime'] = (new DateTime($compromisso['end_datetime']))->format('Y-m-d\TH:i');
        
        if ($compromisso['repeat_until']) {
            $compromisso['repeat_until'] = (new DateTime($compromisso['repeat_until']))->format('Y-m-d');
        }
        
        // Array de dias da semana para repetição específica
        $repeatDays = $compromisso['repeat_days'] ? explode(',', $compromisso['repeat_days']) : [];
        
        // Verificar se é parte de um evento recorrente
        $isRecurring = !empty($compromisso['group_id']);
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/compromissos/edit.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Atualiza um compromisso existente
     */
    public function update() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $startDatetime = filter_input(INPUT_POST, 'start_datetime', FILTER_SANITIZE_STRING);
        $endDatetime = filter_input(INPUT_POST, 'end_datetime', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $repeatType = filter_input(INPUT_POST, 'repeat_type', FILTER_SANITIZE_STRING);
        $repeatUntil = filter_input(INPUT_POST, 'repeat_until', FILTER_SANITIZE_STRING);
        $repeatDays = isset($_POST['repeat_days']) ? implode(',', $_POST['repeat_days']) : null;
        $status = htmlspecialchars(filter_input(INPUT_POST, 'status', FILTER_UNSAFE_RAW) ?? '') ?: 'pendente';
        $updateFutureOccurrences = isset($_POST['update_future']) ? true : false;
        
        // Validar dados obrigatórios
        if (!$id || !$title || !$startDatetime || !$endDatetime) {
            $_SESSION['flash_message'] = 'Todos os campos obrigatórios devem ser preenchidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos/edit?id=' . $id);
            exit;
        }
        
        // Buscar o compromisso atual
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            $_SESSION['flash_message'] = 'Compromisso não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar a agenda do compromisso
        $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
        
        // Verificar se o usuário é o dono da agenda ou tem permissão para editar
        $isOwner = $agenda['user_id'] == $_SESSION['user_id'];
        $canEdit = $isOwner;
        
        if (!$isOwner) {
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canEdit = $shareModel->canEdit($compromisso['agenda_id'], $_SESSION['user_id']);
        }
        
        if (!$canEdit) {
            $_SESSION['flash_message'] = 'Você não tem permissão para editar este compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
            exit;
        }
        
        // Verificar se há conflito de horário com outros compromissos
        if ($this->compromissoModel->hasTimeConflict($compromisso['agenda_id'], $startDatetime, $endDatetime, $id)) {
            $_SESSION['flash_message'] = 'Existe um conflito de horário com outro compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos/edit?id=' . $id);
            exit;
        }
        
        // Preparar dados para atualizar
        $data = [
            'title' => $title,
            'description' => $description,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => $location,
            'repeat_type' => $repeatType,
            'repeat_until' => ($repeatType != 'none' && $repeatUntil) ? $repeatUntil : null,
            'repeat_days' => ($repeatType == 'specific_days' && $repeatDays) ? $repeatDays : null,
            'status' => $status
        ];
        
        // Atualizar no banco
        $result = $this->compromissoModel->update($id, $data, $updateFutureOccurrences);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Compromisso atualizado com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao atualizar compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
        exit;
    }

    public function updateDate() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados do formulário
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $startDatetime = filter_input(INPUT_POST, 'start', FILTER_SANITIZE_STRING);
        $endDatetime = filter_input(INPUT_POST, 'end', FILTER_SANITIZE_STRING);
        
        // Validar dados
        if (!$id || !$startDatetime) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }
        
        // Buscar o compromisso atual
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Compromisso não encontrado']);
            exit;
        }
        
        // Verificar se o usuário pode editar
        $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
        $isOwner = $agenda['user_id'] == $_SESSION['user_id'];
        $canEdit = $isOwner;
        
        if (!$isOwner) {
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canEdit = $shareModel->canEdit($compromisso['agenda_id'], $_SESSION['user_id']);
        }
        
        if (!$canEdit) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sem permissão para editar']);
            exit;
        }
        
        // Se não foi fornecido um horário de término, calcular com base na duração original
        if (!$endDatetime) {
            $start = new DateTime($compromisso['start_datetime']);
            $end = new DateTime($compromisso['end_datetime']);
            $duration = $start->diff($end);
            
            $newStart = new DateTime($startDatetime);
            $newEnd = clone $newStart;
            $newEnd->add($duration);
            
            $endDatetime = $newEnd->format('Y-m-d\TH:i:s');
        }
        
        // Verificar conflito de horário
        if ($this->compromissoModel->hasTimeConflict($compromisso['agenda_id'], $startDatetime, $endDatetime, $id)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Conflito de horário com outro compromisso']);
            exit;
        }
        
        // Preparar dados para atualização
        $data = [
            'title' => $compromisso['title'],
            'description' => $compromisso['description'],
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => $compromisso['location'],
            'status' => $compromisso['status'],
            'repeat_type' => $compromisso['repeat_type'],
            'repeat_until' => $compromisso['repeat_until'],
            'repeat_days' => $compromisso['repeat_days']
        ];
        
        // Atualizar apenas este evento (não as recorrências futuras)
        $result = $this->compromissoModel->update($id, $data, false);
        
        // Retornar resposta
        header('Content-Type: application/json');
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Compromisso atualizado com sucesso'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao atualizar compromisso'
            ]);
        }
        exit;
    }
    
    /**
     * Exclui um compromisso
     */
    public function delete() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter o ID do compromisso
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $deleteFuture = isset($_POST['delete_future']) ? true : false;
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Compromisso não especificado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar o compromisso
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            $_SESSION['flash_message'] = 'Compromisso não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // NOVA REGRA: Verificar se o status é 'pendente'
        if ($compromisso['status'] !== 'pendente') {
            $_SESSION['flash_message'] = 'Apenas compromissos com status pendente podem ser excluídos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
            exit;
        }
        
        // Buscar a agenda do compromisso
        $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
        
        // Verificar se o usuário é o dono da agenda ou tem permissão para editar
        $isOwner = $agenda['user_id'] == $_SESSION['user_id'];
        $canEdit = $isOwner;
        
        if (!$isOwner) {
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            $canEdit = $shareModel->canEdit($compromisso['agenda_id'], $_SESSION['user_id']);
        }
        
        if (!$canEdit) {
            $_SESSION['flash_message'] = 'Você não tem permissão para excluir este compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
            exit;
        }
        
        // Excluir o compromisso
        $result = $this->compromissoModel->delete($id, $deleteFuture);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Compromisso excluído com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao excluir compromisso';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
        exit;
    }

/**
* Cancela eventos futuros de uma série recorrente
*/
public function cancelFuture() {
// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/agendas');
    exit;
}

// Obter o ID do compromisso
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash_message'] = 'Compromisso não especificado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/agendas');
    exit;
}

// Buscar o compromisso
$compromisso = $this->compromissoModel->getById($id);

if (!$compromisso) {
    $_SESSION['flash_message'] = 'Compromisso não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/agendas');
    exit;
}

// Verificar se é um evento recorrente
if (empty($compromisso['group_id'])) {
    $_SESSION['flash_message'] = 'Este não é um compromisso recorrente';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
    exit;
}

// Buscar a agenda do compromisso
$agenda = $this->agendaModel->getById($compromisso['agenda_id']);

// Verificar se o usuário é o dono da agenda ou tem permissão para editar
$isOwner = $agenda['user_id'] == $_SESSION['user_id'];
$canEdit = $isOwner;

if (!$isOwner) {
    require_once __DIR__ . '/../models/AgendaShare.php';
    $shareModel = new AgendaShare();
    $canEdit = $shareModel->canEdit($compromisso['agenda_id'], $_SESSION['user_id']);
}

if (!$canEdit) {
    $_SESSION['flash_message'] = 'Você não tem permissão para modificar este compromisso';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
    exit;
}

// Cancelar todas as ocorrências futuras
$result = $this->compromissoModel->cancelFutureOccurrences($id);

if ($result) {
    $_SESSION['flash_message'] = 'Compromissos futuros cancelados com sucesso';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Erro ao cancelar compromissos futuros';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
exit;
}

/**
* Alterar o status de um compromisso
*/
public function changeStatus() {
// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/agendas');
    exit;
}

// Obter dados do formulário
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

if (!$id || !$status) {
    $_SESSION['flash_message'] = 'Parâmetros inválidos';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/agendas');
    exit;
}

// Buscar o compromisso
$compromisso = $this->compromissoModel->getById($id);

if (!$compromisso) {
    $_SESSION['flash_message'] = 'Compromisso não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/agendas');
    exit;
}

// Buscar a agenda do compromisso
$agenda = $this->agendaModel->getById($compromisso['agenda_id']);

// Verificar se o usuário é o dono da agenda ou tem permissão para editar
$isOwner = $agenda['user_id'] == $_SESSION['user_id'];
$canEdit = $isOwner;

if (!$isOwner) {
    require_once __DIR__ . '/../models/AgendaShare.php';
    $shareModel = new AgendaShare();
    $canEdit = $shareModel->canEdit($compromisso['agenda_id'], $_SESSION['user_id']);
}

if (!$canEdit) {
    $_SESSION['flash_message'] = 'Você não tem permissão para modificar este compromisso';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
    exit;
}

// Atualizar o status
$data = [
    'title' => $compromisso['title'],
    'description' => $compromisso['description'],
    'start_datetime' => $compromisso['start_datetime'],
    'end_datetime' => $compromisso['end_datetime'],
    'location' => $compromisso['location'],
    'repeat_type' => $compromisso['repeat_type'],
    'repeat_until' => $compromisso['repeat_until'],
    'repeat_days' => $compromisso['repeat_days'],
    'status' => $status
];

$result = $this->compromissoModel->update($id, $data);

if ($result) {
    $_SESSION['flash_message'] = 'Status do compromisso atualizado com sucesso';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Erro ao atualizar status do compromisso';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ' . BASE_URL . '/compromissos?agenda_id=' . $compromisso['agenda_id']);
exit;
}

/**
* Verifica se há conflito de horário (usado via AJAX)
*/
public function checkConflict() {
// Obter parâmetros da requisição
$agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
$startDatetime = filter_input(INPUT_GET, 'start', FILTER_SANITIZE_STRING);
$endDatetime = filter_input(INPUT_GET, 'end', FILTER_SANITIZE_STRING);
$compromissoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Validar parâmetros
if (!$agendaId || !$startDatetime || !$endDatetime) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

// Verificar se o usuário tem acesso à agenda
$agenda = $this->agendaModel->getById($agendaId);
if (!$agenda) {
    echo json_encode(['error' => 'Agenda não encontrada']);
    exit;
}

// Verificar se o usuário tem acesso à agenda
$canAccess = $agenda['user_id'] == $_SESSION['user_id'];
if (!$canAccess) {
    require_once __DIR__ . '/../models/AgendaShare.php';
    $shareModel = new AgendaShare();
    $canAccess = $shareModel->checkAccess($agendaId, $_SESSION['user_id']);
}

if (!$canAccess) {
    echo json_encode(['error' => 'Sem permissão para acessar esta agenda']);
    exit;
}

// Verificar se há conflito
$hasConflict = $this->compromissoModel->hasTimeConflict($agendaId, $startDatetime, $endDatetime, $compromissoId);

// Retornar resultado em JSON
echo json_encode(['conflict' => $hasConflict]);
exit;
}
}