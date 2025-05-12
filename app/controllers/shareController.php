<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/AuthorizationService.php';

class ShareController extends BaseController {
    private $agendaModel;
    private $shareModel;
    private $userModel;
    private $authService;
    
    public function __construct() {
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/Agenda.php';
        require_once __DIR__ . '/../models/AgendaShare.php';
        require_once __DIR__ . '/../models/User.php';
        
        $this->agendaModel = new Agenda();
        $this->shareModel = new AgendaShare();
        $this->userModel = new User();
        $this->authService = new AuthorizationService();
        
        // Verificar se o usuário está logado
        $this->checkAuth();
    }
    
    public function index() {
        // Obter o ID da agenda da URL
        $agendaId = filter_input(INPUT_GET, 'agenda_id', FILTER_VALIDATE_INT);
        
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para gerenciar compartilhamentos desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter a agenda
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter os compartilhamentos da agenda
        $shares = $this->shareModel->getSharesByAgenda($agendaId);
        
        // Exibir a view
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/shares/index.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Adiciona um novo compartilhamento de agenda
     */
    public function add() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $canEdit = isset($_POST['can_edit']) ? true : false;
        
        // Validar dados
        if (!$agendaId || !$username) {
            $_SESSION['flash_message'] = 'Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para compartilhar esta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar o usuário pelo nome de usuário
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $_SESSION['flash_message'] = 'Usuário não encontrado';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
            exit;
        }
        
        // Verificar se o usuário não é o próprio dono da agenda
        if ($user['id'] == $_SESSION['user_id']) {
            $_SESSION['flash_message'] = 'Você não pode compartilhar a agenda com você mesmo';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
            exit;
        }
        
        // Compartilhar a agenda
        $result = $this->shareModel->shareAgenda($agendaId, $user['id'], $canEdit);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Agenda compartilhada com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao compartilhar agenda';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }
    
    /**
     * Remove um compartilhamento de agenda
     */
    public function remove() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        
        // Validar dados
        if (!$agendaId || !$userId) {
            $_SESSION['flash_message'] = 'Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para gerenciar compartilhamentos desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Remover o compartilhamento
        $result = $this->shareModel->removeShare($agendaId, $userId);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Compartilhamento removido com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao remover compartilhamento';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }
    
    /**
     * Atualiza as permissões de um compartilhamento
     */
    public function updatePermission() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter dados do formulário
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $canEdit = isset($_POST['can_edit']) ? true : false;
        
        // Validar dados
        if (!$agendaId || !$userId) {
            $_SESSION['flash_message'] = 'Dados inválidos';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para gerenciar compartilhamentos desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Atualizar o compartilhamento
        $result = $this->shareModel->shareAgenda($agendaId, $userId, $canEdit);
        
        if ($result) {
            $_SESSION['flash_message'] = 'Permissões atualizadas com sucesso';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao atualizar permissões';
            $_SESSION['flash_type'] = 'danger';
        }
        
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }
    public function shared() {
    $userId = $_SESSION['user_id'];
    
    // Log para depuração
    error_log("Método shared() chamado para usuário ID: $userId");
    
    // Processar parâmetro de busca
    $search = isset($_GET['search']) ? htmlspecialchars(filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW) ?? '') : null;
    
    // Parâmetros de paginação
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10; // Número de itens por página
    
    // Verificar se o usuário está logado
    $this->checkAuth();
    
    // Executar diagnóstico para verificar exatamente o que está acontecendo
    $diagnostico = $this->shareModel->diagnosticarCompartilhamentos($userId);
    
    // Registrar resultados do diagnóstico no log
    error_log("DIAGNÓSTICO para usuário ID $userId:");
    error_log("Total de agendas próprias: " . $diagnostico['total_agendas_proprias']);
    error_log("Compartilhadas comigo: " . count($diagnostico['compartilhados_comigo']));
    error_log("Compartilhadas por mim: " . count($diagnostico['compartilhados_por_mim']));
    
    if (count($diagnostico['compartilhados_comigo']) > 0) {
        error_log("Detalhes das agendas compartilhadas comigo:");
        foreach ($diagnostico['compartilhados_comigo'] as $agenda) {
            error_log("ID: {$agenda['agenda_id']} | Título: {$agenda['title']} | Ativa: {$agenda['is_active']} | Dono: {$agenda['owner_name']}");
        }
    }
    
    if (count($diagnostico['compartilhados_por_mim']) > 0) {
        error_log("Detalhes das agendas que compartilhei:");
        foreach ($diagnostico['compartilhados_por_mim'] as $agenda) {
            error_log("ID: {$agenda['id']} | Título: {$agenda['title']} | Ativa: {$agenda['is_active']} | Compartilhada com: {$agenda['shared_with_name']}");
        }
    }
    
    // Usar os dados do diagnóstico como base para exibição
    $sharedWithMe = [];
    foreach ($diagnostico['compartilhados_comigo'] as $agenda) {
        if ($agenda['is_active'] == 1) { // Verificar apenas agendas ativas
            // Preparar o formato esperado pela view
            $sharedWithMe[] = [
                'id' => $agenda['agenda_id'],
                'title' => $agenda['title'],
                'description' => '', // Não temos na consulta simplificada, mas poderia ser adicionado
                'is_owner' => false,
                'can_edit' => $agenda['can_edit'],
                'owner_name' => $agenda['owner_name'],
                'compromissos' => [
                    'pendentes' => 0, // Valores padrão
                    'realizados' => 0,
                    'cancelados' => 0,
                    'aguardando_aprovacao' => 0
                ]
            ];
        }
    }
    
    // Processar agendas que o usuário compartilhou
    $mySharedAgendas = [];
    // Criar um array que agrupa por ID da agenda (para não duplicar)
    $agendaPorId = [];
    
    foreach ($diagnostico['compartilhados_por_mim'] as $agenda) {
        // Combinar todos os compartilhamentos da mesma agenda
        if (!isset($agendaPorId[$agenda['id']])) {
            $agendaPorId[$agenda['id']] = [
                'id' => $agenda['id'],
                'title' => $agenda['title'],
                'description' => '', // Não temos na consulta simplificada
                'is_owner' => true,
                'can_edit' => true, // Dono sempre pode editar
                'compromissos' => [
                    'pendentes' => 0,
                    'realizados' => 0,
                    'cancelados' => 0,
                    'aguardando_aprovacao' => 0
                ],
                'shared_with' => [] // Lista de usuários com quem compartilhou
            ];
        }
        
        // Adicionar usuário à lista de compartilhamentos desta agenda
        $agendaPorId[$agenda['id']]['shared_with'][] = $agenda['shared_with_name'];
    }
    
    // Converter o array associativo em array sequencial
    foreach ($agendaPorId as $agenda) {
        $mySharedAgendas[] = $agenda;
    }
    
    // Dados para paginação
    $totalSharedWithMe = count($sharedWithMe);
    $totalPages = ceil($totalSharedWithMe / $perPage);
    
    // Aplicar paginação manualmente
    $sharedWithMe = array_slice($sharedWithMe, ($page - 1) * $perPage, $perPage);
    
    // Carregar view com o diagnóstico para possíveis consultas
    $mostraDiagnostico = true; // Variável para controlar exibição do diagnóstico na view
    
    require_once __DIR__ . '/../views/shared/header.php';
    require_once __DIR__ . '/../views/shares/shared.php';
    require_once __DIR__ . '/../views/shared/footer.php';
}
    
    public function generatePublicUrl() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Obter o ID da agenda
        $agendaId = filter_input(INPUT_POST, 'agenda_id', FILTER_VALIDATE_INT);
        
        if (!$agendaId) {
            $_SESSION['flash_message'] = 'Agenda não especificada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Verificar se o usuário é o dono da agenda
        if (!$this->authService->isAgendaOwner($agendaId, $_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Você não tem permissão para alterar a visibilidade desta agenda';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Buscar a agenda
        $agenda = $this->agendaModel->getById($agendaId);
        
        if (!$agenda) {
            $_SESSION['flash_message'] = 'Agenda não encontrada';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/agendas');
            exit;
        }
        
        // Definir nova visibilidade (alternar entre público e privado)
        $newIsPublic = !$agenda['is_public'];
        
        // Se estiver tornando a agenda pública, gerar um hash
        $hash = $agenda['public_hash'];
        if ($newIsPublic && empty($hash)) {
            $hash = md5(uniqid(rand(), true));
        }
        
        // Atualizar a agenda - PRESERVANDO o valor is_active
        $result = $this->agendaModel->update($agendaId, [
            'title' => $agenda['title'],
            'description' => $agenda['description'],
            'is_public' => $newIsPublic,
            'color' => $agenda['color'],
            'is_active' => $agenda['is_active'] // Preservar o valor atual
        ]);
        
        // Se estiver tornando a agenda pública, atualizar o hash
        if ($newIsPublic && empty($agenda['public_hash'])) {
            $this->agendaModel->updatePublicHash($agendaId, $hash);
        }
        
        if ($result) {
            if ($newIsPublic) {
                $_SESSION['flash_message'] = 'Agenda marcada como pública com sucesso';
            } else {
                $_SESSION['flash_message'] = 'Agenda marcada como privada com sucesso';
            }
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Erro ao alterar visibilidade da agenda';
            $_SESSION['flash_type'] = 'danger';
        }
        
        // Redirecionar para a página de compartilhamentos
        header('Location: ' . BASE_URL . '/shares?agenda_id=' . $agendaId);
        exit;
    }

    public function diagnostico() {
    // Garantir que apenas usuários logados possam acessar
    $this->checkAuth();
    
    $userId = $_SESSION['user_id'];
    
    // Resultado do diagnóstico
    $resultado = [
        'user_id' => $userId,
        'timestamp' => date('Y-m-d H:i:s'),
        'dados_usuario' => [],
        'agendas_proprias' => [],
        'compartilhados_comigo' => [],
        'compartilhados_por_mim' => []
    ];
    
    try {
        // 1. Verificar dados do usuário
        $sql = "SELECT id, username, name, email FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['dados_usuario'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 2. Verificar agendas próprias do usuário
        $sql = "SELECT id, title, description, is_active FROM agendas WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['agendas_proprias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Verificar agendas compartilhadas COM o usuário
        $sql = "SELECT s.*, a.title as agenda_title, a.is_active, u.name as owner_name 
                FROM agenda_shares s
                JOIN agendas a ON s.agenda_id = a.id
                JOIN users u ON a.user_id = u.id
                WHERE s.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['compartilhados_comigo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Verificar agendas que o usuário compartilhou com outros
        $sql = "SELECT s.*, a.title as agenda_title, a.is_active, u.name as shared_with_name 
                FROM agenda_shares s
                JOIN agendas a ON s.agenda_id = a.id
                JOIN users u ON s.user_id = u.id
                WHERE a.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $resultado['compartilhados_por_mim'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Verificar todos os registros na tabela agenda_shares
        $sql = "SELECT COUNT(*) FROM agenda_shares";
        $stmt = $this->db->query($sql);
        $resultado['total_registros_shares'] = $stmt->fetchColumn();
        
    } catch (PDOException $e) {
        $resultado['erro'] = $e->getMessage();
    }
    
    // Exibir resultados
    echo '<html><head><title>Diagnóstico de Compartilhamentos</title>';
    echo '<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #004a8f; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .empty { color: #999; font-style: italic; }
    </style>';
    echo '</head><body>';
    
    echo '<h1>Diagnóstico de Compartilhamentos</h1>';
    echo '<p>Data/hora: ' . $resultado['timestamp'] . '</p>';
    
    echo '<div class="section">';
    echo '<h2>Dados do Usuário</h2>';
    if ($resultado['dados_usuario']) {
        echo '<table>';
        echo '<tr><th>ID</th><td>' . $resultado['dados_usuario']['id'] . '</td></tr>';
        echo '<tr><th>Username</th><td>' . htmlspecialchars($resultado['dados_usuario']['username']) . '</td></tr>';
        echo '<tr><th>Nome</th><td>' . htmlspecialchars($resultado['dados_usuario']['name']) . '</td></tr>';
        echo '<tr><th>Email</th><td>' . htmlspecialchars($resultado['dados_usuario']['email']) . '</td></tr>';
        echo '</table>';
    } else {
        echo '<p class="empty">Nenhum dado de usuário encontrado.</p>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Agendas Próprias (' . count($resultado['agendas_proprias']) . ')</h2>';
    if (!empty($resultado['agendas_proprias'])) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Título</th><th>Ativa</th></tr>';
        foreach ($resultado['agendas_proprias'] as $agenda) {
            echo '<tr>';
            echo '<td>' . $agenda['id'] . '</td>';
            echo '<td>' . htmlspecialchars($agenda['title']) . '</td>';
            echo '<td>' . ($agenda['is_active'] ? 'Sim' : 'Não') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="empty">Nenhuma agenda própria encontrada.</p>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Agendas Compartilhadas Comigo (' . count($resultado['compartilhados_comigo']) . ')</h2>';
    if (!empty($resultado['compartilhados_comigo'])) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Agenda ID</th><th>Título</th><th>Dono</th><th>Ativa</th><th>Pode Editar</th></tr>';
        foreach ($resultado['compartilhados_comigo'] as $share) {
            echo '<tr>';
            echo '<td>' . $share['id'] . '</td>';
            echo '<td>' . $share['agenda_id'] . '</td>';
            echo '<td>' . htmlspecialchars($share['agenda_title']) . '</td>';
            echo '<td>' . htmlspecialchars($share['owner_name']) . '</td>';
            echo '<td>' . ($share['is_active'] ? 'Sim' : 'Não') . '</td>';
            echo '<td>' . ($share['can_edit'] ? 'Sim' : 'Não') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="empty">Nenhuma agenda compartilhada comigo encontrada.</p>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Agendas Compartilhadas Por Mim (' . count($resultado['compartilhados_por_mim']) . ')</h2>';
    if (!empty($resultado['compartilhados_por_mim'])) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Agenda ID</th><th>Título</th><th>Compartilhada Com</th><th>Ativa</th><th>Pode Editar</th></tr>';
        foreach ($resultado['compartilhados_por_mim'] as $share) {
            echo '<tr>';
            echo '<td>' . $share['id'] . '</td>';
            echo '<td>' . $share['agenda_id'] . '</td>';
            echo '<td>' . htmlspecialchars($share['agenda_title']) . '</td>';
            echo '<td>' . htmlspecialchars($share['shared_with_name']) . '</td>';
            echo '<td>' . ($share['is_active'] ? 'Sim' : 'Não') . '</td>';
            echo '<td>' . ($share['can_edit'] ? 'Sim' : 'Não') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="empty">Nenhuma agenda compartilhada por mim encontrada.</p>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Informações Adicionais</h2>';
    echo '<p>Total de registros na tabela agenda_shares: ' . $resultado['total_registros_shares'] . '</p>';
    if (isset($resultado['erro'])) {
        echo '<p style="color: red;">Erro: ' . $resultado['erro'] . '</p>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Links Úteis</h2>';
    echo '<p><a href="' . BASE_URL . '/shares/shared">Voltar para Agendas Compartilhadas</a></p>';
    echo '<p><a href="' . BASE_URL . '/agendas">Voltar para Minhas Agendas</a></p>';
    echo '</div>';
    
    echo '</body></html>';
    exit;
}
}