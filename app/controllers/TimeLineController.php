<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Agenda.php';
require_once __DIR__ . '/../models/Compromisso.php';
require_once __DIR__ . '/../models/User.php';

class TimelineController extends BaseController {
    private $agendaModel;
    private $compromissoModel;
    private $userModel;
    
    public function __construct() {
        $this->agendaModel = new Agenda();
        $this->compromissoModel = new Compromisso();
        $this->userModel = new User();
        
        // Timeline é pública, não verificar autenticação
        // $this->checkAuth();
    }
    
    /**
     * Exibe a timeline de eventos públicos com melhorias
     */
    public function index() {
        // Obter data atual ou do parâmetro GET
        $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        // Validar e processar a data selecionada
        try {
            $date = new DateTime($selectedDate);
            $formattedDate = $date->format('Y-m-d');
        } catch (Exception $e) {
            // Se a data for inválida, usar data atual
            $date = new DateTime();
            $formattedDate = $date->format('Y-m-d');
        }
        
        // Obter todas as agendas públicas ativas com informações do proprietário
        $publicAgendas = $this->getPublicAgendasWithDetails();
        
        // Obter agendas selecionadas do filtro
        $selectedAgendas = isset($_GET['agendas']) && is_array($_GET['agendas']) ? 
            array_map('intval', $_GET['agendas']) : [];
        
        // Se não houver agendas selecionadas, considerar todas as agendas
        $useAllAgendas = empty($selectedAgendas);
        
        // Obter query de busca
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Inicializar array de eventos
        $allEvents = [];
        $eventStats = [
            'total' => 0,
            'pendente' => 0,
            'realizado' => 0,
            'cancelado' => 0,
            'aguardando_aprovacao' => 0
        ];
        
        // Log para debug
        error_log("Timeline: Processando " . count($publicAgendas) . " agendas públicas para data: " . $formattedDate);
        
        // Para cada agenda pública, obter eventos para a data selecionada
        foreach ($publicAgendas as $agenda) {
            // Pular se agendas específicas foram selecionadas e esta não for uma delas
            if (!$useAllAgendas && !in_array($agenda['id'], $selectedAgendas)) {
                continue;
            }
            
            // Definir período do dia completo
            $startDay = $formattedDate . ' 00:00:00';
            $endDay = $formattedDate . ' 23:59:59';
            
            // Obter eventos para esta agenda na data selecionada
            $events = $this->compromissoModel->getByAgendaAndDateRange(
                $agenda['id'],
                $startDay,
                $endDay
            );
            
            error_log("Timeline: Agenda {$agenda['id']} ({$agenda['title']}) - {$events} eventos encontrados");
            
            // Filtrar por query de busca, se necessário
            if (!empty($searchQuery)) {
                $events = $this->filterEventsBySearch($events, $searchQuery);
            }
            
            // Processar cada evento
            foreach ($events as $event) {
                // Não incluir eventos cancelados na timeline pública por padrão
                // Mas manter outros status para transparência
                if ($event['status'] === 'cancelado') {
                    continue;
                }
                
                // Adicionar informações da agenda ao evento
                $event['agenda_info'] = [
                    'id' => $agenda['id'],
                    'title' => $agenda['title'],
                    'color' => $agenda['color'] ?? '#3788d8', // Cor padrão se não definida
                    'owner_name' => $agenda['owner_name'] ?? 'Usuário'
                ];
                
                // Adicionar informações do criador, se disponível e diferente do dono
                if (!empty($event['created_by']) && $event['created_by'] != $agenda['user_id']) {
                    $creator = $this->userModel->getById($event['created_by']);
                    if ($creator) {
                        $event['creator_name'] = $creator['name'];
                    }
                }
                
                // Atualizar estatísticas
                $eventStats['total']++;
                $status = $event['status'] ?? 'pendente';
                if (isset($eventStats[$status])) {
                    $eventStats[$status]++;
                }
                
                // Adicionar ao array combinado
                $allEvents[] = $event;
            }
        }
        
        // Ordenar eventos por hora de início
        usort($allEvents, function($a, $b) {
            return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
        });
        
        // Log final
        error_log("Timeline: Total de {$eventStats['total']} eventos processados para {$formattedDate}");
        
        // Preparar dados para a view
        $viewData = [
            'date' => $date,
            'formattedDate' => $formattedDate,
            'publicAgendas' => $publicAgendas,
            'selectedAgendas' => $selectedAgendas,
            'searchQuery' => $searchQuery,
            'allEvents' => $allEvents,
            'eventStats' => $eventStats,
            'useAllAgendas' => $useAllAgendas
        ];
        
        // Carregar a view
        $this->renderTimelineView($viewData);
    }
    
    /**
     * Obter agendas públicas com detalhes dos proprietários
     */
    private function getPublicAgendasWithDetails() {
        try {
            return $this->agendaModel->getAllPublicActive();
        } catch (Exception $e) {
            error_log('Erro ao obter agendas públicas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Filtrar eventos por termo de busca
     */
    private function filterEventsBySearch($events, $searchQuery) {
        if (empty($searchQuery)) {
            return $events;
        }
        
        $searchTerm = strtolower($searchQuery);
        
        return array_filter($events, function($event) use ($searchTerm) {
            $searchableFields = [
                strtolower($event['title'] ?? ''),
                strtolower($event['description'] ?? ''),
                strtolower($event['location'] ?? '')
            ];
            
            foreach ($searchableFields as $field) {
                if (strpos($field, $searchTerm) !== false) {
                    return true;
                }
            }
            
            return false;
        });
    }
    
    /**
     * Renderizar a view da timeline
     */
    private function renderTimelineView($data) {
        // Extrair variáveis para a view
        extract($data);
        
        // Carregar arquivos da view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/timeline/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * API endpoint para obter eventos de uma data específica (AJAX)
     */
    public function getEventsForDate() {
        // Verificar se é uma requisição AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Requisição inválida']);
            return;
        }
        
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $agendaIds = isset($_GET['agendas']) ? explode(',', $_GET['agendas']) : [];
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        try {
            // Validar data
            $dateObj = new DateTime($date);
            $formattedDate = $dateObj->format('Y-m-d');
            
            // Obter agendas públicas
            $publicAgendas = $this->getPublicAgendasWithDetails();
            
            $events = [];
            
            foreach ($publicAgendas as $agenda) {
                // Filtrar por agendas selecionadas se especificado
                if (!empty($agendaIds) && !in_array($agenda['id'], $agendaIds)) {
                    continue;
                }
                
                $startDay = $formattedDate . ' 00:00:00';
                $endDay = $formattedDate . ' 23:59:59';
                
                $agendaEvents = $this->compromissoModel->getByAgendaAndDateRange(
                    $agenda['id'],
                    $startDay,
                    $endDay
                );
                
                // Filtrar por busca se especificado
                if (!empty($search)) {
                    $agendaEvents = $this->filterEventsBySearch($agendaEvents, $search);
                }
                
                foreach ($agendaEvents as $event) {
                    if ($event['status'] === 'cancelado') {
                        continue;
                    }
                    
                    $event['agenda_info'] = [
                        'id' => $agenda['id'],
                        'title' => $agenda['title'],
                        'color' => $agenda['color'] ?? '#3788d8',
                        'owner_name' => $agenda['owner_name'] ?? 'Usuário'
                    ];
                    
                    $events[] = $event;
                }
            }
            
            // Ordenar por hora
            usort($events, function($a, $b) {
                return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
            });
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'events' => $events,
                'date' => $formattedDate,
                'total' => count($events)
            ]);
            
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar eventos: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Obter estatísticas gerais da timeline
     */
    public function getTimelineStats() {
        try {
            $publicAgendas = $this->getPublicAgendasWithDetails();
            $today = date('Y-m-d');
            
            $stats = [
                'total_agendas' => count($publicAgendas),
                'events_today' => 0,
                'events_this_week' => 0,
                'events_this_month' => 0
            ];
            
            // Calcular eventos para diferentes períodos
            $startWeek = date('Y-m-d', strtotime('monday this week'));
            $endWeek = date('Y-m-d', strtotime('sunday this week'));
            $startMonth = date('Y-m-01');
            $endMonth = date('Y-m-t');
            
            foreach ($publicAgendas as $agenda) {
                // Eventos hoje
                $todayEvents = $this->compromissoModel->getByAgendaAndDateRange(
                    $agenda['id'],
                    $today . ' 00:00:00',
                    $today . ' 23:59:59'
                );
                $stats['events_today'] += count(array_filter($todayEvents, function($e) {
                    return $e['status'] !== 'cancelado';
                }));
                
                // Eventos desta semana
                $weekEvents = $this->compromissoModel->getByAgendaAndDateRange(
                    $agenda['id'],
                    $startWeek . ' 00:00:00',
                    $endWeek . ' 23:59:59'
                );
                $stats['events_this_week'] += count(array_filter($weekEvents, function($e) {
                    return $e['status'] !== 'cancelado';
                }));
                
                // Eventos este mês
                $monthEvents = $this->compromissoModel->getByAgendaAndDateRange(
                    $agenda['id'],
                    $startMonth . ' 00:00:00',
                    $endMonth . ' 23:59:59'
                );
                $stats['events_this_month'] += count(array_filter($monthEvents, function($e) {
                    return $e['status'] !== 'cancelado';
                }));
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao carregar estatísticas: ' . $e->getMessage()
            ]);
        }
    }
}