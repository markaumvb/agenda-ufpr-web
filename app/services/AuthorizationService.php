// app/services/AuthorizationService.php
<?php
class AuthorizationService {
    private $agendaModel;
    private $shareModel;
    
    public function __construct() {
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/AgendaShare.php';
        
        $this->agendaModel = new Agenda();
        $this->shareModel = new AgendaShare();
    }
    
    /**
     * Verifica se o usuário tem acesso a uma agenda
     */
    public function canAccessAgenda($agendaId, $userId) {
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            return false;
        }
        
        // Se for o dono ou a agenda for pública
        if ($agenda['user_id'] == $userId || $agenda['is_public']) {
            return true;
        }
        
        // Verificar compartilhamento
        return $this->shareModel->checkAccess($agendaId, $userId) !== false;
    }
    
    /**
     * Verifica se o usuário pode editar uma agenda
     */
    public function canEditAgenda($agendaId, $userId) {
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            return false;
        }
        
        // Se for o dono
        if ($agenda['user_id'] == $userId) {
            return true;
        }
        
        // Verificar permissão de edição
        return $this->shareModel->canEdit($agendaId, $userId);
    }
    
    /**
     * Verifica se o usuário é o dono da agenda
     */
    public function isAgendaOwner($agendaId, $userId) {
        return $this->agendaModel->belongsToUser($agendaId, $userId);
    }
}