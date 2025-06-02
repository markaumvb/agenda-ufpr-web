<?php
// Carregar configurações e constantes
require_once __DIR__ . '/app/config/constants.php';
require_once __DIR__ . '/vendor/autoload.php';

// Função para carregar classes automaticamente
spl_autoload_register(function ($className) {
    // Lista de diretórios para buscar classes
    $directories = [
        __DIR__ . '/app/controllers/',
        __DIR__ . '/app/models/',
        __DIR__ . '/app/services/',
        __DIR__ . '/app/helpers/'
    ];
    
    // Verificar se o arquivo existe em algum dos diretórios
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// Sistema de roteamento simplificado
$requestUri = $_SERVER['REQUEST_URI'];

// Extrair a parte da URI após /agenda_ufpr/
$pattern = '/\/agenda_ufpr\/?(.*)$/';
preg_match($pattern, $requestUri, $matches);
$uri = isset($matches[1]) ? '/' . $matches[1] : '/';

// Remover parâmetros de query se existirem
if (strpos($uri, '?') !== false) {
    $uri = substr($uri, 0, strpos($uri, '?'));
}

// Remover barra no final se existir (exceto para a home)
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

// Rota padrão
if ($uri === '/' || $uri === '/index.php' || $uri === '') {
    // Debug - Verificar qual rota está sendo acessada
    error_log("URI original: " . $_SERVER['REQUEST_URI']);
    error_log("URI processada: " . $uri);
    require_once __DIR__ . '/app/models/Database.php';
    require_once __DIR__ . '/app/models/Agenda.php';
    require_once __DIR__ . '/app/models/User.php';
    
    $agendaModel = new Agenda();
    $publicAgendas = $agendaModel->getAllPublicActive();
    
    require_once __DIR__ . '/app/views/shared/header.php';
    require_once __DIR__ . '/app/views/home.php';
    require_once __DIR__ . '/app/views/shared/footer.php';
    exit;
}

// Mapeamento básico de rotas
$routes = [
    // Rotas de autenticação
    '/login' => [
        'controller' => 'AuthController',
        'action' => 'showLoginForm',
        'method' => 'GET'
    ],
    '/login-process' => [
        'controller' => 'AuthController',
        'action' => 'login',
        'method' => 'POST'
    ],
    '/logout' => [
        'controller' => 'AuthController',
        'action' => 'logout',
        'method' => 'GET'
    ],
    '/register' => [
        'controller' => 'AuthController',
        'action' => 'showRegisterForm',
        'method' => 'GET'
    ],
    '/register-process' => [
        'controller' => 'AuthController',
        'action' => 'register',
        'method' => 'POST'
    ],
    
    // Rotas de agendas
    '/agendas' => [
        'controller' => 'AgendaController',
        'action' => 'index',
        'method' => 'GET'
    ],
    '/agendas/new' => [
        'controller' => 'AgendaController',
        'action' => 'create',
        'method' => 'GET'
    ],
    '/agendas/save' => [
        'controller' => 'AgendaController',
        'action' => 'store',
        'method' => 'POST'
    ],
    '/agendas/edit' => [
        'controller' => 'AgendaController',
        'action' => 'edit',
        'method' => 'GET'
    ],
    '/agendas/update' => [
        'controller' => 'AgendaController',
        'action' => 'update',
        'method' => 'POST'
    ],
    '/agendas/delete' => [
        'controller' => 'AgendaController',
        'action' => 'delete',
        'method' => 'POST'
    ],
    '/agendas/toggle-public' => [
        'controller' => 'ShareController',
        'action' => 'generatePublicUrl',
        'method' => 'POST'
    ],
    
    // Rotas de compromissos
    '/compromissos' => [
        'controller' => 'CompromissoController',
        'action' => 'index',
        'method' => 'GET'
    ],
    '/compromissos/new' => [
        'controller' => 'CompromissoController',
        'action' => 'create',
        'method' => 'GET'
    ],
    '/compromissos/save' => [
        'controller' => 'CompromissoController',
        'action' => 'store',
        'method' => 'POST'
    ],
    '/compromissos/edit' => [
        'controller' => 'CompromissoController',
        'action' => 'edit',
        'method' => 'GET'
    ],
    '/compromissos/update' => [
        'controller' => 'CompromissoController',
        'action' => 'update',
        'method' => 'POST'
    ],
    '/compromissos/delete' => [
        'controller' => 'CompromissoController',
        'action' => 'delete',
        'method' => 'POST'
    ],
    '/compromissos/change-status' => [
        'controller' => 'CompromissoController',
        'action' => 'changeStatus',
        'method' => 'POST'
    ],
    '/compromissos/check-conflict' => [
        'controller' => 'CompromissoController',
        'action' => 'checkConflict',
        'method' => 'GET'
    ],
    '/compromissos/cancel-future' => [
        'controller' => 'CompromissoController',
        'action' => 'cancelFuture',
        'method' => 'POST'
    ],
    
    // Rotas de compartilhamento
    '/shares' => [
        'controller' => 'ShareController',
        'action' => 'index',
        'method' => 'GET'
    ],
    '/shares/add' => [
        'controller' => 'ShareController',
        'action' => 'add',
        'method' => 'POST'
    ],
    '/shares/remove' => [
        'controller' => 'ShareController',
        'action' => 'remove',
        'method' => 'POST'
    ],
    '/shares/update-permission' => [
        'controller' => 'ShareController',
        'action' => 'updatePermission',
        'method' => 'POST'
    ],
    '/shares/shared' => [
        'controller' => 'ShareController',
        'action' => 'shared',
        'method' => 'GET'
    ],
    '/shares/toggle-public' => [
        'controller' => 'ShareController',
        'action' => 'generatePublicUrl',
        'method' => 'POST'
    ],
    '/api/check-server-status' => [
        'controller' => 'ApiController',
        'action' => 'checkServerStatus',
        'method' => 'GET'
    ],
    '/api/check-time-conflict' => [
        'controller' => 'ApiController',
        'action' => 'checkTimeConflict',
        'method' => 'GET'
    ],
    '/api/search-users' => [
        'controller' => 'ApiController',
        'action' => 'searchUsers',
        'method' => 'GET'
    ],
    '/api/notifications' => [
        'controller' => 'ApiController',
        'action' => 'getNotifications',
        'method' => 'GET'
    ],
    '/api/mark-notification-read' => [
        'controller' => 'ApiController',
        'action' => 'markNotificationRead',
        'method' => 'POST'
    ],
    '/api/mark-all-notifications-read' => [
        'controller' => 'ApiController',
        'action' => 'markAllNotificationsRead',
        'method' => 'POST'
    ],
    '/compromissos/update-date' => [
        'controller' => 'CompromissoController',
        'action' => 'updateDate',
        'method' => 'POST'
    ],
    '/meuscompromissos' => [
        'controller' => 'MeusCompromissosController',
        'action' => 'index',
        'method' => 'GET'
    ],
    '/meuscompromissos/cancel' => [
        'controller' => 'MeusCompromissosController',
        'action' => 'cancelCompromisso',
        'method' => 'POST'
    ],
    '/meuscompromissos/edit' => [
        'controller' => 'MeusCompromissosController',
        'action' => 'editCompromisso',
        'method' => 'POST'
    ],
    '/meuscompromissos/approve' => [
        'controller' => 'MeusCompromissosController',
        'action' => 'approveCompromisso',
        'method' => 'POST'
    ],
    '/meuscompromissos/reject' => [
        'controller' => 'MeusCompromissosController',
        'action' => 'rejectCompromisso',
        'method' => 'POST'
    ],
    '/agendas/toggle-active' => [
        'controller' => 'AgendaController',
        'action' => 'toggleActive',
        'method' => 'POST'
    ],
    '/api/pending-approvals' => [
        'controller' => 'ApiController',
        'action' => 'getPendingApprovals',
        'method' => 'GET'
    ],
    '/agendas/all' => [
        'controller' => 'AgendaController',
        'action' => 'allAgendas',
        'method' => 'GET'
    ],
    '/notifications' => [
        'controller' => 'NotificationController',
        'action' => 'index',
        'method' => 'GET'
    ],
    '/notifications/view' => [
        'controller' => 'NotificationController',
        'action' => 'view',
        'method' => 'GET'
    ],
    '/notifications/mark-read' => [
        'controller' => 'NotificationController',
        'action' => 'markAsRead',
        'method' => 'POST'
    ],
    '/notifications/mark-all-read' => [
        'controller' => 'NotificationController',
        'action' => 'markAllAsRead',
        'method' => 'POST'
    ],
    '/notifications/delete' => [
        'controller' => 'NotificationController',
        'action' => 'delete',
        'method' => 'POST'
    ],
    '/notifications/accept-compromisso' => [
        'controller' => 'NotificationController',
        'action' => 'acceptCompromisso',
        'method' => 'POST'
    ],
    '/notifications/reject-compromisso' => [
        'controller' => 'NotificationController',
        'action' => 'rejectCompromisso',
        'method' => 'POST'
    ],
    '/api/check-min-time-before' => [
    'controller' => 'ApiController',
    'action' => 'checkMinTimeBefore',
    'method' => 'GET'
    ],
    '/timeline' => [
        'controller' => 'TimelineController',
        'action' => 'index',
        'method' => 'GET'
],
'/compromissos/new-public' => [
    'controller' => 'CompromissoController',
    'action' => 'newPublic',
    'method' => 'GET'
],
'/timeline' => [
    'controller' => 'TimeLineController',
    'action' => 'index',
    'method' => 'GET'
],
'/compromissos/external-form' => [
    'controller' => 'CompromissoController',
    'action' => 'externalForm',
    'method' => 'GET'
],
'/compromissos/external-create' => [
    'controller' => 'CompromissoController',
    'action' => 'externalCreate',
    'method' => 'POST'  // <-- CORRIGIDO
],
'/compromissos/external-store' => [
    'controller' => 'CompromissoController',
    'action' => 'externalStore',
    'method' => 'POST'
],
'/compromissos/external-new' => [
    'controller' => 'CompromissoController',
    'action' => 'externalNew',  
    'method' => 'GET'
],
'/compromissos/external-success' => [
    'controller' => 'CompromissoController',
    'action' => 'externalSuccess',
    'method' => 'GET'
],
'/meuscompromissos/bulk-approve' => [
        'controller' => 'MeusCompromissosController',
        'action' => 'bulkApprove',
        'method' => 'POST'
    ],
    '/meuscompromissos/bulk-reject' => [
        'controller' => 'MeusCompromissosController',
        'action' => 'bulkReject',
        'method' => 'POST'
    ],

];


// Verificar se a rota corresponde a um padrão de agenda pública
if (preg_match('|^/public-agenda/([a-f0-9]+)$|', $uri, $matches)) {
    $hash = $matches[1];
    require_once __DIR__ . '/app/controllers/PublicController.php';
    $controller = new PublicController();
    $controller->viewPublicAgenda($hash);
    exit;
}

// Verificar se a rota existe
if (array_key_exists($uri, $routes)) {
    $route = $routes[$uri];
    
    // Verificar se o método de requisição coincide
    if ($_SERVER['REQUEST_METHOD'] !== $route['method']) {
        header('HTTP/1.1 405 Method Not Allowed');
        echo "Método não permitido";
        exit;
    }
    
    // Carregar o controlador e executar a ação
    $controllerName = $route['controller'];
    $actionName = $route['action'];
    
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        
        if (method_exists($controller, $actionName)) {
            // Executar a ação
            $controller->$actionName();
            exit;
        }
    }
}

// Se chegou até aqui, a rota não foi encontrada
header('HTTP/1.1 404 Not Found');
require_once __DIR__ . '/app/views/shared/header.php';
require_once __DIR__ . '/app/views/errors/404.php';
require_once __DIR__ . '/app/views/shared/footer.php';