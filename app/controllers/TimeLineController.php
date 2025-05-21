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
        //$this->checkAuth();
    }
    
    /**
     * Exibe a timeline de eventos públicos
     */
    public function index() {
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
        
        // Obter todas as agendas públicas ativas
        $publicAgendas = $this->agendaModel->getAllPublicActive();
        
        // Obter agendas selecionadas do filtro
        $selectedAgendas = isset($_GET['agendas']) && is_array($_GET['agendas']) ? $_GET['agendas'] : [];
        
        // Se não houver agendas selecionadas, considerar todas as agendas
        $useAllAgendas = empty($selectedAgendas);
        
        // Obter query de busca
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Inicializar array de eventos
        $allEvents = [];
        
        // Para cada agenda pública, obter eventos para a data selecionada
        foreach ($publicAgendas as $agenda) {
            // Pular se uma agenda específica foi selecionada e não for esta
            if (!$useAllAgendas && !in_array($agenda['id'], $selectedAgendas)) {
                continue;
            }
            
            // Definir início e fim do dia
            $startDay = $formattedDate . ' 00:00:00';
            $endDay = $formattedDate . ' 23:59:59';
            
            // Obter eventos para esta agenda na data selecionada
            $events = $this->compromissoModel->getByAgendaAndDateRange(
                $agenda['id'],
                $startDay,
                $endDay
            );
            
            // Filtrar por query de busca, se necessário
            if (!empty($searchQuery)) {
                $events = array_filter($events, function($event) use ($searchQuery) {
                    $searchQuery = strtolower($searchQuery);
                    return (
                        stripos(strtolower($event['title']), $searchQuery) !== false ||
                        stripos(strtolower($event['description'] ?? ''), $searchQuery) !== false ||
                        stripos(strtolower($event['location'] ?? ''), $searchQuery) !== false
                    );
                });
            }
            
            // Adicionar informações da agenda a cada evento
            foreach ($events as $event) {
                // Não incluir eventos cancelados ou aguardando aprovação
                if ($event['status'] === 'cancelado' || $event['status'] === 'aguardando_aprovacao') {
                    continue;
                }
                
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
        
        // Carregar a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/timeline/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
}