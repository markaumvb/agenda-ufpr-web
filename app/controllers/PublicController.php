<?php

class PublicController {
    private $agendaModel;
    private $compromissoModel;
    private $userModel;
    

    public function __construct() {
        // Carregar os modelos necessários
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../services/CalendarService.php';
        
        $this->agendaModel = new Agenda();
        $this->compromissoModel = new Compromisso();
        $this->userModel = new User();
        $this->calendarService = new CalendarService();
    }
    
    /**
     * Exibe a agenda pública
     */
    public function viewPublicAgenda($hash = null) {
        // Verificar se o hash foi fornecido
        if (!$hash) {
            header('HTTP/1.1 404 Not Found');
            echo "Agenda não encontrada";
            exit;
        }
        
        // Buscar a agenda pelo hash
        $agenda = $this->agendaModel->getByPublicHash($hash);
        
        if (!$agenda || !(bool)(int)$agenda['is_public'] || !(bool)(int)$agenda['is_active']) {
            header('HTTP/1.1 404 Not Found');
            echo "Agenda não encontrada, não é pública ou está desativada";
            exit;
        }
        
        // Buscar o dono da agenda
        // Verificamos se o método existe no User model
        if (method_exists($this->userModel, 'getById')) {
            $owner = $this->userModel->getById($agenda['user_id']);
        } else {
            // Método alternativo se getById não existir
            $owner = $this->userModel->findById($agenda['user_id']);
            
            // Se ainda não encontrou, tentar outro método
            if (!$owner) {
                $owner = $this->getUserById($agenda['user_id']);
            }
        }
        
        if (!$owner) {
            // Criar um owner padrão se não encontrado para evitar erros
            $owner = [
                'id' => $agenda['user_id'],
                'name' => 'Usuário',
                'email' => 'email@exemplo.com'
            ];
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
            $agenda['id'],
            $firstDay->format('Y-m-d'),
            $lastDay->format('Y-m-d')
        );
        
        // Preparar dados para a view
        $calendarData = $this->calendarService->prepareCalendarData($month, $year, $compromissos);
        
        // Obter todos os compromissos da agenda para a lista
        $allCompromissos = $this->compromissoModel->getAllByAgenda($agenda['id']);
        
        // Exibir a view
        require_once __DIR__ . '/../views/shares/public.php';
    }
    
    /**
     * Método auxiliar para buscar usuário pelo ID
     * Implementado como fallback caso o método não exista no modelo
     */
    private function getUserById($userId) {
        try {
            $db = Database::getInstance()->getConnection();
            $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
    

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
            // Pular compromissos cancelados na visualização pública
            if ($compromisso['status'] === 'cancelado') {
                continue;
            }
            
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
}