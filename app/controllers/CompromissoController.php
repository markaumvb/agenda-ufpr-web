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
    // Obter ID da agenda
    $agendaId = isset($_GET['agenda_id']) ? (int)$_GET['agenda_id'] : 0;
    $isPublic = isset($_GET['public']) && $_GET['public'] == 1;
    
    if (!$agendaId) {
        // Tratar erro...
        $_SESSION['flash_message'] = 'Agenda não especificada';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    // Carregar dados da agenda
    $agendaModel = new Agenda();
    $agenda = $agendaModel->getById($agendaId);
    
    if (!$agenda) {
        // Tratar erro...
        $_SESSION['flash_message'] = 'Agenda não encontrada';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    // Verificar permissão e determinar status padrão
    $userId = $_SESSION['user_id'];
    $authService = new AuthorizationService();
    
    // Determinar se o usuário pode criar compromissos nesta agenda
    $canCreate = $authService->canAccessAgenda($agendaId, $userId);
    if (!$canCreate) {
        $_SESSION['flash_message'] = 'Você não tem permissão para acessar esta agenda';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    // Verificar se há erros de validação da sessão
    $errors = [];
    if (isset($_SESSION['validation_errors'])) {
        $errors = $_SESSION['validation_errors'];
        unset($_SESSION['validation_errors']);
    }
    
    // Recuperar dados do formulário da sessão
    $formData = [];
    if (isset($_SESSION['form_data'])) {
        $formData = $_SESSION['form_data'];
        unset($_SESSION['form_data']);
    }
    
    // Definir data e hora padrão considerando a antecedência mínima
    $currentDate = new DateTime();
    
    // Verificar se a agenda tem um tempo mínimo de antecedência
    $minTimeBefore = isset($agenda['min_time_before']) ? (int)$agenda['min_time_before'] : 0;
    
    if ($minTimeBefore > 0) {
        // Adicionar o tempo mínimo à data atual
        $currentDate->add(new DateInterval("PT{$minTimeBefore}H"));
    }
    
    // Se a data tiver sido passada na URL, usar essa
    $selectedDate = isset($_GET['date']) ? $_GET['date'] : null;
    if ($selectedDate) {
        $selectedDateTime = new DateTime($selectedDate);
        // Substituir apenas a data, manter o horário já calculado
        $currentDate->setDate(
            $selectedDateTime->format('Y'),
            $selectedDateTime->format('m'),
            $selectedDateTime->format('d')
        );
    }
    
    $endDate = clone $currentDate;
    $endDate->add(new DateInterval('PT1H')); // Adiciona 1 hora
    
    // Formatar datas para o formato HTML datetime-local
    $defaultStartDateTime = $currentDate->format('Y-m-d\TH:i');
    $defaultEndDateTime = $endDate->format('Y-m-d\TH:i');
    
    // Exibir a view
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/compromissos/create.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}
    
    /**
     * Processa o formulário de criação de compromisso
     */
public function store() {
    // Verificar se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Obter dados do formulário com validação melhorada
    $agendaId = isset($_POST['agenda_id']) ? (int)$_POST['agenda_id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $startDatetime = isset($_POST['start_datetime']) ? $_POST['start_datetime'] : '';
    $endDatetime = isset($_POST['end_datetime']) ? $_POST['end_datetime'] : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $repeatType = isset($_POST['repeat_type']) ? $_POST['repeat_type'] : 'none';
    $repeatUntil = isset($_POST['repeat_until']) ? $_POST['repeat_until'] : '';
    $repeatDays = isset($_POST['repeat_days']) && is_array($_POST['repeat_days']) ? $_POST['repeat_days'] : [];

    // Validar dados
    $data = [
        'agenda_id' => $agendaId,
        'title' => $title,
        'description' => $description,
        'start_datetime' => $startDatetime,
        'end_datetime' => $endDatetime,
        'location' => $location,
        'repeat_type' => $repeatType,
        'repeat_until' => $repeatUntil,
        'repeat_days' => $repeatDays
    ];
    
    // Validar dados usando o método comum
    $errors = $this->validateCompromissoData($data);
    
    if (!empty($errors)) {
        $_SESSION['validation_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId);
        exit;
    }
    
    // Para eventos recorrentes, verificar conflitos para todas as ocorrências
    if ($repeatType !== 'none') {
        $conflicts = $this->compromissoModel->checkRecurringConflicts(
            $repeatType,
            $startDatetime, 
            $endDatetime, 
            $repeatUntil,
            isset($repeatDays) && is_array($repeatDays) ? implode(',', $repeatDays) : '',
            $agendaId
        );
        
        if (!empty($conflicts)) {
            $_SESSION['validation_errors'] = $conflicts;
            $_SESSION['form_data'] = $_POST;
            header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId);
            exit;
        }
    } else {
        // Verificar conflito para evento único
        if ($this->compromissoModel->hasTimeConflict($agendaId, $startDatetime, $endDatetime)) {
            $conflictingEvent = $this->compromissoModel->getConflictingEvent($agendaId, $startDatetime, $endDatetime);
            
            if ($conflictingEvent) {
                $conflictDate = new DateTime($startDatetime);
                $error = "Conflito no dia " . $conflictDate->format('d/m/Y') . 
                         " às " . $conflictDate->format('H:i') . 
                         ": \"" . $conflictingEvent['title'] . "\"";
                
                $_SESSION['validation_errors'] = [$error];
                $_SESSION['form_data'] = $_POST;
                header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId);
                exit;
            }
        }
    }
    
    // Verificar se a agenda existe e está ativa
    $agenda = $this->agendaModel->getById($agendaId);
    
    if (!$agenda) {
        $_SESSION['validation_errors'] = ['Agenda não encontrada'];
        header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId);
        exit;
    } else if (!$agenda['is_active']) {
        $_SESSION['validation_errors'] = ['Esta agenda está desativada e não pode receber novos compromissos'];
        header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId);
        exit;
    }
    
    // Verificar permissões
    $isOwner = ($agenda['user_id'] == $userId);
    $canEdit = $isOwner;
    $isPublic = isset($_POST['public']) && $_POST['public'] == '1';
    
    // Status padrão para o compromisso
    $status = 'pendente';
    
    // Verificar permissões para não-donos
    if (!$isOwner) {
        require_once __DIR__ . '/../models/AgendaShare.php';
        $shareModel = new AgendaShare();
        $shared = $shareModel->checkAccess($agendaId, $userId);
        
        if ($shared) {
            $canEdit = $shared['can_edit'];
            // Status para usuário com agenda compartilhada: pendente
            $status = 'pendente';
        } elseif ($agenda['is_public']) {
            // Status para usuário sem acesso à agenda, mas é pública: aguardando aprovação
            $status = 'aguardando_aprovacao';
        } else {
            // Se não for compartilhada e não for pública, não tem acesso
            $_SESSION['flash_message'] = 'Você não tem permissão para acessar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header("Location: " . PUBLIC_URL . "/agendas");
            exit;
        }
    }
    
    // Garantir que as datas estão no formato correto
    if (!empty($startDatetime)) {
        $startDatetime = date('Y-m-d H:i:s', strtotime($startDatetime));
    }
    if (!empty($endDatetime)) {
        $endDatetime = date('Y-m-d H:i:s', strtotime($endDatetime));
    }
    
    // Criar o compromisso com tipos corretos
    $compromissoData = [
        'agenda_id' => (int)$agendaId,
        'title' => $title,
        'description' => $description ?: '',
        'start_datetime' => $startDatetime,
        'end_datetime' => $endDatetime,
        'location' => $location ?: '',
        'status' => $status,
        'created_by' => (int)$userId,
        'repeat_type' => $repeatType,
        'repeat_days' => is_array($repeatDays) ? implode(',', $repeatDays) : '',
        'repeat_until' => ($repeatType !== 'none' && !empty($repeatUntil)) ? $repeatUntil : null
    ];
    
    // Salvar o compromisso (agora o model já lida com recorrências)
    $compromissoId = $this->compromissoModel->create($compromissoData);
    
    if (!$compromissoId) {
        $_SESSION['flash_message'] = 'Erro ao criar o compromisso';
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId);
        exit;
    }
    
    // Mostrar mensagem de sucesso e redirecionar
    $_SESSION['flash_message'] = 'Compromisso criado com sucesso';
    $_SESSION['flash_type'] = 'success';
    
    if ($status === 'aguardando_aprovacao') {
        $_SESSION['flash_message'] = 'Compromisso criado e está aguardando aprovação do dono da agenda';
    }
    
    header("Location: " . PUBLIC_URL . "/compromissos?agenda_id=" . $agendaId);
    exit;
}
    

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
    
    // NOVA VERIFICAÇÃO: Apenas compromissos pendentes podem ser editados
    if ($compromisso['status'] !== 'pendente') {
        $_SESSION['flash_message'] = 'Apenas compromissos com status pendente podem ser editados';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . BASE_URL . '/meuscompromissos');
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
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
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
        
        // Dados a serem validados
        $data = [
            'id' => $id,
            'agenda_id' => $agendaId,
            'title' => $title,
            'description' => $description,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => $location,
            'repeat_type' => $repeatType,
            'repeat_until' => $repeatUntil,
            'repeat_days' => $repeatDays,
            'status' => $status
        ];
        
        // Validar dados usando o método comum
        $errors = $this->validateCompromissoData($data, $id);
        
    if (!empty($errors)) {
        // Manter o sistema de flash messages para compatibilidade
        $_SESSION['flash_message'] = 'Por favor, corrija os erros abaixo.';
        $_SESSION['flash_type'] = 'danger';
        
        // Também armazenar os erros detalhados e dados do formulário para a nova interface
        $_SESSION['validation_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        
        // Redirecionar de volta para o formulário
        header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId);
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
        $dataToUpdate = [
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
        $result = $this->compromissoModel->update($id, $dataToUpdate, $updateFutureOccurrences);
        
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
        
        // Para compromissos recorrentes, atualizar todos os eventos do grupo se necessário
        if (!empty($compromisso['group_id'])) {
            $result = $this->compromissoModel->updateGroupStatus($compromisso['group_id'], $status);
        } else {
            $result = $this->compromissoModel->update($id, $data);
        }
        
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
        
        echo json_encode(['conflict' => $hasConflict]);
        exit;
    }


private function validateCompromissoData($data, $compromissoId = null) {
    $errors = [];
    
    // Validar campos obrigatórios
    if (empty($data['agenda_id'])) {
        $errors[] = 'A agenda é obrigatória';
    }
    
    if (empty($data['title'])) {
        $errors[] = 'O título é obrigatório';
    }
    
    if (empty($data['start_datetime'])) {
        $errors[] = 'A data e hora de início são obrigatórias';
    }
    
    if (empty($data['end_datetime'])) {
        $errors[] = 'A data e hora de término são obrigatórias';
    }
    
    // Verificar se a data inicial está no futuro e respeita o tempo mínimo
    if (!empty($data['start_datetime']) && !empty($data['agenda_id'])) {
        // Determinar se é uma edição ou criação
        $isEditing = ($compromissoId !== null);
        $dateErrors = $this->compromissoModel->validateCompromissoDate($data['agenda_id'], $data['start_datetime'], $isEditing);
        if (!empty($dateErrors)) {
            $errors = array_merge($errors, $dateErrors);
        }
    }
    
    // Verificar se a data final é maior que a inicial
    if (!empty($data['start_datetime']) && !empty($data['end_datetime'])) {
        $start = new DateTime($data['start_datetime']);
        $end = new DateTime($data['end_datetime']);
        
        if ($end <= $start) {
            $errors[] = 'A data e hora de término deve ser posterior à data e hora de início';
        }
    }
    
    // Verificar recorrência
    if (isset($data['repeat_type']) && $data['repeat_type'] !== 'none') {
        if (empty($data['repeat_until'])) {
            $errors[] = 'Para eventos recorrentes, é necessário definir uma data final';
        }
        
        if ($data['repeat_type'] === 'specific_days' && empty($data['repeat_days'])) {
            $errors[] = 'Selecione pelo menos um dia da semana para a recorrência';
        }
    }
    
    return $errors;
}

    public function newPublic() {
    // Obter ID da agenda
    $agendaId = isset($_GET['agenda_id']) ? (int)$_GET['agenda_id'] : 0;
    
    if (!$agendaId) {
        $_SESSION['flash_message'] = 'Agenda não especificada';
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . PUBLIC_URL . "/agendas");
        exit;
    }
    
    // Salvar na sessão para uso após o login
    $_SESSION['redirect_to_new_compromisso'] = $agendaId;
    
    // Verificar se o usuário está logado
    if (!isset($_SESSION['user_id'])) {
        // Redirecionar para login (sem parâmetros complexos)
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }
    
    // Se já estiver logado, redirecionar para o formulário normal
    header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId . "&public=1");
    exit;
}
    
}