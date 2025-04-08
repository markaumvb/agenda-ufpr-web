<?php
// auth-debug.php (remova após resolver o problema)
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/constants.php';
require_once __DIR__ . '/app/services/RadiusService.php';
require_once __DIR__ . '/app/models/Database.php';
require_once __DIR__ . '/app/models/User.php';

echo "<h1>Teste de Fluxo de Autenticação</h1>";

// Verificar configurações
echo "<h2>Configurações</h2>";
echo "<pre>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'Não definido') . "\n";
echo "PUBLIC_URL: " . (defined('PUBLIC_URL') ? PUBLIC_URL : 'Não definido') . "\n";
echo "RADIUS_SERVER: " . (defined('RADIUS_SERVER') ? RADIUS_SERVER : 'Não definido') . "\n";
echo "</pre>";

// Verificar rotas registradas
echo "<h2>Rotas</h2>";
echo "<p>Simular acesso a: <code>/login</code></p>";
$requestUri = "/agenda_ufpr/login";
$pattern = '/\/agenda_ufpr\/?(.*)$/';
preg_match($pattern, $requestUri, $matches);
$uri = isset($matches[1]) ? '/' . $matches[1] : '/';
echo "URI extraída: <code>$uri</code>";

echo "<h2>Teste de Login</h2>";
echo "<form method='post'>";
echo "Usuário: <input type='text' name='username' required><br>";
echo "Senha: <input type='password' name='password' required><br>";
echo "<button type='submit'>Testar Login Completo</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        echo "<h3>Passo 1: Autenticação RADIUS</h3>";
        $radiusService = new RadiusService();
        $authenticated = $radiusService->authenticate($username, $password);
        
        if (!$authenticated) {
            echo "<p style='color:red'>Falha na autenticação RADIUS</p>";
            exit;
        }
        
        echo "<p style='color:green'>Autenticação RADIUS bem-sucedida!</p>";
        
        echo "<h3>Passo 2: Verificar Usuário no Sistema</h3>";
        $userModel = new User();
        $user = $userModel->findByUsername($username);
        
        if (!$user) {
            echo "<p>Usuário não encontrado no sistema. Seria redirecionado para registro.</p>";
        } else {
            echo "<p>Usuário encontrado: ID = {$user['id']}, Nome = {$user['name']}</p>";
            echo "<p>Login bem-sucedido! Seria redirecionado para a página inicial.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
    }
}
?>