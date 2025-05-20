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
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    
    /**
     * Exibe a timeline de eventos públicos
     */
    public function index() {
        // Registrar o início da execução para debug
        error_log("TimelineController::index iniciado");
        
        // Obter data atual ou do parâmetro GET
        $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        // Analisar a data selecionada
        try {
            $date = new DateTime($selectedDate);
            $formattedDate = $date->format('Y-m-d');
        } catch (Exception $e) {
            $date = new DateTime();
            $formattedDate = $date->format('Y-m-d');
        }
        
        error_log("Timeline: data selecionada: " . $formattedDate);
        
        // Obter todas as agendas públicas
        $publicAgendas = $this->agendaModel->getAllPublicActive();
        error_log("Timeline: encontradas " . count($publicAgendas) . " agendas públicas ativas");
        
        // Log para depuração - listar IDs das agendas públicas
        if (!empty($publicAgendas)) {
            $agendaIds = array_map(function($a) { return $a['id']; }, $publicAgendas);
            error_log("Timeline: IDs das agendas públicas: " . implode(", ", $agendaIds));
        }
        
        // Filtrar por agenda específica, se fornecida
        $selectedAgendaId = isset($_GET['agenda_id']) && $_GET['agenda_id'] !== 'all' ? 
                            intval($_GET['agenda_id']) : null;
        
        if ($selectedAgendaId) {
            error_log("Timeline: filtrando pela agenda ID: " . $selectedAgendaId);
        }
        
        // Obter query de busca
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        if (!empty($searchQuery)) {
            error_log("Timeline: termo de busca: " . $searchQuery);
        }
        
        // Inicializar array de eventos
        $allEvents = [];
        
        // Para cada agenda pública, obter eventos para a data selecionada
        foreach ($publicAgendas as $agenda) {
            // Pular se uma agenda específica foi selecionada e não for esta
            if ($selectedAgendaId !== null && $agenda['id'] != $selectedAgendaId) {
                continue;
            }
            
            // Definir início e fim do dia
            $startDay = $formattedDate . ' 00:00:00';
            $endDay = $formattedDate . ' 23:59:59';
            
            error_log("Timeline: buscando eventos para agenda ID " . $agenda['id'] . 
                      " (" . $agenda['title'] . ") entre " . $startDay . " e " . $endDay);
            
            // Obter eventos para esta agenda na data selecionada
            $events = $this->compromissoModel->getByAgendaAndDateRange(
                $agenda['id'],
                $startDay,
                $endDay
            );
            
            error_log("Timeline: encontrados " . count($events) . " eventos para agenda ID " . $agenda['id']);
            
            // Filtrar por query de busca, se necessário
            if (!empty($searchQuery)) {
                $eventsBeforeFilter = count($events);
                $events = array_filter($events, function($event) use ($searchQuery) {
                    return (
                        stripos($event['title'], $searchQuery) !== false ||
                        stripos($event['description'] ?? '', $searchQuery) !== false ||
                        stripos($event['location'] ?? '', $searchQuery) !== false
                    );
                });
                error_log("Timeline: após filtro de busca, restaram " . count($events) . " eventos dos " . $eventsBeforeFilter . " anteriores");
            }
            
            // Adicionar informações da agenda a cada evento
            foreach ($events as $event) {
                // Verificação adicional de debugging para o status do evento
                error_log("Timeline: processando evento ID " . $event['id'] . 
                          " (" . $event['title'] . "), status: " . $event['status']);
                
                // Vamos incluir TODOS os eventos, incluindo cancelados, para depuração
                
                // Adicionar informações da agenda
                $event['agenda_info'] = [
                    'id' => $agenda['id'],
                    'title' => $agenda['title'],
                    'color' => $agenda['color'],
                    'owner_name' => $agenda['owner_name'] ?? 'Desconhecido'
                ];
                
                // Adicionar informações do criador, se disponível
                if (!empty($event['created_by'])) {
                    $creator = $this->userModel->getById($event['created_by']);
                    if ($creator) {
                        $event['creator_name'] = $creator['name'];
                    }
                }
                
                // Adicionar ao array combinado
                $allEvents[] = $event;
            }
        }
        
        // Ordenar eventos por hora de início
        usort($allEvents, function($a, $b) {
            return strtotime($a['start_datetime']) - strtotime($b['start_datetime']);
        });
        
        error_log("Timeline: total de " . count($allEvents) . " eventos processados");
        
        // Carregar a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/timeline/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
}