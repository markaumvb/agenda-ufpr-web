<?php
// Arquivo: app/controllers/CompromissoController.php

/**
 * Controlador para gerenciar os compromissos
 */
class CompromissoController {
    private $compromissoModel;
    private $agendaModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Carregar os modelos necessários
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../models/Agenda.php';
        
        $this->compromissoModel = new Compromisso();
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
            header('Location: ' . BASE_URL . '/public/login');
            exit;
        }
    }
    
    /**
     * Exibe o calendário e a lista de compromissos de uma agenda
     */
    public function index() {
        // Obter o ID da agenda da URL
        $agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
        
        // Se não foi fornecido um ID de agenda, redirecionar para a lista de agendas
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Verificar se a agenda existe e se o usuário tem acesso a ela
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Verificar se o usuário tem acesso à agenda
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
            header('Location: ' . BASE_URL . '/public/agendas');
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
        $calendarData = $this->prepareCalendarData($month, $year, $compromissos);
        
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
    
    /**
     * Prepara os dados para o calendário
     * 
     * @param int $month Mês (1-12)
     * @param int $year Ano
     * @param array $compromissos Lista de compromissos
     * @return array Dados para o calendário
     */
    private function prepareCalendarData($month, $year, $compromissos) {
        // Primeiro dia do mês
        $firstDay = new DateTime("$year-$month-01");
        
        // Último dia do mês
        $lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));
        
        // Dia da semana do primeiro dia (0 = Domingo, 6 = Sábado)
        $firstDayOfWeek = $firstDay->format('w');
        
        // Total de dias no mês
        $totalDays = $lastDay->format('j');
        
        // Preparar array de semanas e dias
        $weeks = [];
        $day = 1;
        $currentWeek = 0;
        
        // Inicializar a primeira semana com dias vazios
        $weeks[$currentWeek] = array_fill(0, 7, ['day' => null, 'compromissos' => []]);
        
        // Preencher com os dias do mês anterior se necessário
        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $weeks[$currentWeek][$i] = ['day' => null, 'compromissos' => []];
        }
        
        // Preencher os dias do mês
        for ($i = $firstDayOfWeek; $i < 7; $i++) {
            if ($day <= $totalDays) {
                $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                $weeks[$currentWeek][$i] = ['day' => $day, 'compromissos' => []];
                $day++;
            }
        }
        
        // Continuar com as próximas semanas
        while ($day <= $totalDays) {
            $currentWeek++;
            $weeks[$currentWeek] = array_fill(0, 7, ['day' => null, 'compromissos' => []]);
            
            for ($i = 0; $i < 7; $i++) {
                if ($day <= $totalDays) {
                    $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
                    $weeks[$currentWeek][$i] = ['day' => $day, 'compromissos' => []];
                    $day++;
                }
            }
        }
        
        // Adicionar compromissos ao calendário
        foreach ($compromissos as $compromisso) {
            $startDate = new DateTime($compromisso['start_datetime']);
            $endDate = new DateTime($compromisso['end_datetime']);
            
            // Verificar se é o mês atual
            if ($startDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) &&
                $endDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                continue;
            }
            
            // Se o compromisso começar antes do mês atual, ajustar para o primeiro dia
            if ($startDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                $startDate = new DateTime("$year-$month-01");
            }
            
            // Se o compromisso terminar depois do mês atual, ajustar para o último dia
            if ($endDate->format('Y-m') != "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                $endDate = $lastDay;
            }
            
            // Percorrer todos os dias do compromisso
            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                if ($currentDate->format('Y-m') == "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT)) {
                    $day = $currentDate->format('j');
                    
                    // Encontrar a semana e dia correspondente
                    foreach ($weeks as $weekIndex => $week) {
                        foreach ($week as $dayIndex => $dayData) {
                            if ($dayData['day'] == $day) {
                                $weeks[$weekIndex][$dayIndex]['compromissos'][] = $compromisso;
                            }
                        }
                    }
                }
                $currentDate->modify('+1 day');
            }
        }
        
        // Mapeamento dos nomes dos meses para português (opcional)
        $monthNames = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];
        
        // Retornar dados para o calendário
        return [
            'month' => $month,
            'year' => $year,
            'weeks' => $weeks,
            'monthName' => $monthNames[$month] ?? $firstDay->format('F'),
            'previousMonth' => $month == 1 ? 12 : $month - 1,
            'previousYear' => $month == 1 ? $year - 1 : $year,
            'nextMonth' => $month == 12 ? 1 : $month + 1,
            'nextYear' => $month == 12 ? $year + 1 : $year
        ];
    }
    
    /**
     * Exibe o formulário para criar um novo compromisso
     */
    public function create() {
        // Obter o ID da agenda da URL
        $agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
        
        // Se não foi fornecido um ID de agenda, redirecionar para a lista de agendas
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Verificar se a agenda existe e se o usuário tem acesso a ela
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
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
            header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $agendaId);
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
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $startDatetime = filter_input(INPUT_POST, 'start_datetime', FILTER_SANITIZE_STRING);
        $endDatetime = filter_input(INPUT_POST, 'end_datetime', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $repeatType = filter_input(INPUT_POST, 'repeat_type', FILTER_SANITIZE_STRING);
        $repeatUntil = filter_input(INPUT_POST, 'repeat_until', FILTER_SANITIZE_STRING);
        $repeatDays = isset($_POST['repeat_days']) ? implode(',', $_POST['repeat_days']) : null;
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) ?: 'pendente';
        
        // Validar dados obrigatórios
        if (!$agendaId || !$title || !$startDatetime || !$endDatetime) {
            $_SESSION['flash_message'] = 'Todos os campos obrigatórios devem ser preenchidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/compromissos/new?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se a agenda existe e se o usuário tem acesso a ela
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
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
            header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $agendaId);
            exit;
        }
        
        // Para eventos recorrentes, validar data final da recorrência
        if ($repeatType != 'none' && empty($repeatUntil)) {
            $_SESSION['flash_message'] = 'Para eventos recorrentes, é necessário definir uma data final';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/compromissos/new?agenda_id=' . $agendaId);
            exit;
        }
        
        // Para eventos com dias específicos, validar se pelo menos um dia foi selecionado
        if ($repeatType == 'specific_days' && empty($repeatDays)) {
            $_SESSION['flash_message'] = 'Selecione pelo menos um dia da semana para a recorrência';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/compromissos/new?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se há conflito de horário
        if ($this->compromissoModel->hasTimeConflict($agendaId, $startDatetime, $endDatetime)) {
            $_SESSION['flash_message'] = 'Existe um conflito de horário com outro compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/compromissos/new?agenda_id=' . $agendaId);
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
        
        header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $agendaId);
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
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Buscar o compromisso
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            $_SESSION['flash_message'] = 'Compromisso não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
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
            header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
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
            header('Location: ' . BASE_URL . '/public/agendas');
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
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $updateFutureOccurrences = isset($_POST['update_future']) ? true : false;
        
        // Validar dados obrigatórios
        if (!$id || !$title || !$startDatetime || !$endDatetime) {
            $_SESSION['flash_message'] = 'Todos os campos obrigatórios devem ser preenchidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/compromissos/edit?id=' . $id);
            exit;
        }
        
        // Buscar o compromisso atual
        $compromisso = $this->compromissoModel->getById($id);
        
        if (!$compromisso) {
            $_SESSION['flash_message'] = 'Compromisso não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
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
            header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
            exit;
        }
        
        // Verificar se há conflito de horário com outros compromissos
        if ($this->compromissoModel->hasTimeConflict($compromisso['agenda_id'], $startDatetime, $endDatetime, $id)) {
            $_SESSION['flash_message'] = 'Existe um conflito de horário com outro compromisso';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/compromissos/edit?id=' . $id);
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
        
        header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
        exit;
    }
    
    /**
     * Exclui um compromisso
     */
    public function delete() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
        // Obter o ID do compromisso
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $deleteFuture = isset($_POST['delete_future']) ? true : false;
        
        if (!$id) {
            $_SESSION['flash_message'] = 'Compromisso não especificado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/public/agendas');
            exit;
        }
        
// Buscar o compromisso
$compromisso = $this->compromissoModel->getById($id);
        
if (!$compromisso) {
    $_SESSION['flash_message'] = 'Compromisso não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/public/agendas');
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
    header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
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

header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
exit;
}

/**
* Cancela eventos futuros de uma série recorrente
*/
public function cancelFuture() {
// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/agendas');
    exit;
}

// Obter o ID do compromisso
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash_message'] = 'Compromisso não especificado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/public/agendas');
    exit;
}

// Buscar o compromisso
$compromisso = $this->compromissoModel->getById($id);

if (!$compromisso) {
    $_SESSION['flash_message'] = 'Compromisso não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/public/agendas');
    exit;
}

// Verificar se é um evento recorrente
if (empty($compromisso['group_id'])) {
    $_SESSION['flash_message'] = 'Este não é um compromisso recorrente';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
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
    header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
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

header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
exit;
}

/**
* Alterar o status de um compromisso
*/
public function changeStatus() {
// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/public/agendas');
    exit;
}

// Obter dados do formulário
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

if (!$id || !$status) {
    $_SESSION['flash_message'] = 'Parâmetros inválidos';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/public/agendas');
    exit;
}

// Buscar o compromisso
$compromisso = $this->compromissoModel->getById($id);

if (!$compromisso) {
    $_SESSION['flash_message'] = 'Compromisso não encontrado';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . '/public/agendas');
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
    header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
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

header('Location: ' . BASE_URL . '/public/compromissos?agenda_id=' . $compromisso['agenda_id']);
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