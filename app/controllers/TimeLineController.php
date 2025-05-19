<?php
require_once __DIR__ . '/BaseController.php';

class TimelineController extends BaseController {
    private $agendaModel;
    private $compromissoModel;
    private $userModel;
    
    public function __construct() {
        // Inicialização dos modelos
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/Compromisso.php';
        require_once __DIR__ . '/../models/User.php';
        
        $this->agendaModel = new Agenda();
        $this->compromissoModel = new Compromisso();
        $this->userModel = new User();
        
        // NÃO chamar checkAuth() aqui, diferente de outros controllers
    }
    
    public function index() {
        // Get current date or from GET parameter
        $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        // Parse selected date
        try {
            $date = new DateTime($selectedDate);
            $formattedDate = $date->format('Y-m-d');
        } catch (Exception $e) {
            $date = new DateTime();
            $formattedDate = $date->format('Y-m-d');
        }
        
        // Get all public agendas
        $publicAgendas = $this->agendaModel->getAllPublicActive();
        
        // Get selected agendas from filter or use all
        $selectedAgendas = isset($_GET['agendas']) && is_array($_GET['agendas']) ? $_GET['agendas'] : [];
        
        // Get search query
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Initialize events array
        $allEvents = [];
        
        // For each public agenda, get events for the selected date
        foreach ($publicAgendas as $agenda) {
            // Skip if not in selected agendas (if filter is active)
            if (!empty($selectedAgendas) && !in_array($agenda['id'], $selectedAgendas)) {
                continue;
            }
            
            // Get start and end of day
            $startDay = $formattedDate . ' 00:00:00';
            $endDay = $formattedDate . ' 23:59:59';
            
            // Get events for this agenda on the selected date
            $events = $this->compromissoModel->getByAgendaAndDateRange(
                $agenda['id'],
                $startDay,
                $endDay
            );
            
            // Filter by search query if needed
            if (!empty($searchQuery)) {
                $events = array_filter($events, function($event) use ($searchQuery) {
                    return (
                        stripos($event['title'], $searchQuery) !== false ||
                        stripos($event['description'], $searchQuery) !== false ||
                        stripos($event['location'], $searchQuery) !== false
                    );
                });
            }
            
            // Add agenda info to each event
            foreach ($events as &$event) {
                $event['agenda_info'] = [
                    'id' => $agenda['id'],
                    'title' => $agenda['title'],
                    'color' => $agenda['color'],
                    'owner_name' => $agenda['owner_name'] ?? 'Desconhecido'
                ];
                
                // Add creator info if available
                if (!empty($event['created_by'])) {
                    $creator = $this->userModel->getById($event['created_by']);
                    if ($creator) {
                        $event['creator_name'] = $creator['name'];
                    }
                }
                
                // Add to the combined array
                $allEvents[] = $event;
            }
        }
        
        // Sort events by start time
        usort($allEvents, function($a, $b) {
            return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
        });
        
        // Carregar a visualização sem verificar autenticação
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/timeline/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
}