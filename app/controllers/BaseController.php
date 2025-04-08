// app/controllers/BaseController.php
<?php
class BaseController {
    /**
     * Verifica se o usuário está autenticado
     */
    protected function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você precisa estar logado para acessar essa página';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
    
    /**
     * Verifica o acesso a uma agenda
     * 
     * @param int $agendaId ID da agenda
     * @param int $userId ID do usuário
     * @param bool $requireOwner Se é necessário ser o proprietário
     * @return array|bool Retorna dados da agenda ou false
     */
    protected function checkAgendaAccess($agendaId, $userId, $requireOwner = false) {
        require_once __DIR__ . '/../models/Agenda.php';
        $agendaModel = new Agenda();
        
        $agenda = $agendaModel->getById($agendaId);
        
        if (!$agenda) {
            return false;
        }
        
        // Verificar se é o dono
        $isOwner = $agenda['user_id'] == $userId;
        
        if ($requireOwner && !$isOwner) {
            return false;
        }
        
        // Verificar acesso
        if (!$isOwner) {
            if ($agenda['is_public']) {
                return $agenda;
            }
            
            require_once __DIR__ . '/../models/AgendaShare.php';
            $shareModel = new AgendaShare();
            if (!$shareModel->checkAccess($agendaId, $userId)) {
                return false;
            }
        }
        
        return $agenda;
    }
}