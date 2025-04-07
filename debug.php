<?php
echo "<h1>Diagnóstico do Sistema</h1>";
echo "<h2>Informações do Servidor</h2>";
echo "<pre>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
echo "PHP_VERSION: " . phpversion() . "\n";
echo "</pre>";

echo "<h2>Constantes Definidas</h2>";
echo "<pre>";
require_once __DIR__ . '/app/config/constants.php';
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'Não definido') . "\n";
echo "PUBLIC_URL: " . (defined('PUBLIC_URL') ? PUBLIC_URL : 'Não definido') . "\n";
echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'Não definido') . "\n";
echo "</pre>";

echo "<h2>Teste de Autoload</h2>";
echo "<pre>";
spl_autoload_register(function ($className) {
    echo "Tentando carregar: $className\n";
    
    $directories = [
        __DIR__ . '/app/controllers/',
        __DIR__ . '/app/models/',
        __DIR__ . '/app/services/',
        __DIR__ . '/app/helpers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        echo "Verificando: $file\n";
        
        if (file_exists($file)) {
            echo "ENCONTRADO: $file\n";
            require_once $file;
            return true;
        }
    }
    
    echo "NÃO ENCONTRADO: $className\n";
    return false;
});

try {
    echo "Testando AuthController...\n";
    if (class_exists('AuthController')) {
        echo "AuthController existe!\n";
    } else {
        echo "AuthController NÃO existe!\n";
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
echo "</pre>";

echo "<h2>Teste de Rotas</h2>";
echo "<pre>";
$testRoutes = [
    '/login', 
    '/agendas', 
    '/compromissos'
];

foreach ($testRoutes as $route) {
    echo "Testando rota: $route\n";
    $pattern = '/\/agenda_ufpr\/?(.*)$/';
    $testUrl = "/agenda_ufpr" . $route;
    preg_match($pattern, $testUrl, $matches);
    $uri = isset($matches[1]) ? '/' . $matches[1] : '/';
    echo "URL: $testUrl -> URI: $uri\n";
}
echo "</pre>";

echo "<h2>Links de Teste</h2>";
echo "<a href='" . BASE_URL . "/login'>Página de Login</a><br>";
echo "<a href='" . BASE_URL . "/agendas'>Página de Agendas</a><br>";