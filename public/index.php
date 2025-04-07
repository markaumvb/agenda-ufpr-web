<?php
// Carregar configurações e constantes
require_once __DIR__ . '/../app/config/constants.php';

// Função para carregar classes automaticamente
spl_autoload_register(function ($className) {
    // Lista de diretórios para buscar classes
    $directories = [
        __DIR__ . '/../app/controllers/',
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/services/',
        __DIR__ . '/../app/helpers/'
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

// Sistema de roteamento revisado
$requestUri = $_SERVER['REQUEST_URI'];

// CORREÇÃO: Definir basePath de modo consistente
$scriptName = $_SERVER['SCRIPT_NAME'];
$scriptDir = dirname($scriptName);
$basePath = str_replace('/public', '', $scriptDir);

// Remover a base path e parâmetros de query da URI
$uri = parse_url($requestUri, PHP_URL_PATH);
$uri = str_replace($basePath, '', $uri);

// Ajustar a URI para processar corretamente
// Se URI começa com /public, remover essa parte
if (strpos($uri, '/public') === 0) {
    $uri = substr($uri, strlen('/public'));
}

// Rota padrão
if ($uri === '/' || $uri === '/index.php' || $uri === '') {
    require_once __DIR__ . '/../app/views/shared/header.php';
    require_once __DIR__ . '/../app/views/home.php';
    require_once __DIR__ . '/../app/views/shared/footer.php';
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
    ]
];

// Verificar se a rota corresponde a um padrão de agenda pública
if (preg_match('|^/public-agenda/([a-f0-9]+)$|', $uri, $matches)) {
    $hash = $matches[1];
    require_once __DIR__ . '/../app/controllers/PublicController.php';
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
require_once __DIR__ . '/../app/views/shared/header.php';
require_once __DIR__ . '/../app/views/errors/404.php';
require_once __DIR__ . '/../app/views/shared/footer.php';