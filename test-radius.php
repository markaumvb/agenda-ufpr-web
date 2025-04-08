<?php
// test-radius.php (versão corrigida)
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/constants.php';

echo "<h1>Teste de Conexão RADIUS</h1>";

if (!class_exists('Dapphp\\Radius\\Radius')) {
    die("<p style='color:red'>Biblioteca Radius não encontrada. Execute 'composer install'</p>");
}

echo "<form method='post'>
    <label>Usuário:</label>
    <input type='text' name='username' required><br>
    <label>Senha:</label>
    <input type='password' name='password' required><br>
    <button type='submit'>Testar</button>
</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $radius = new \Dapphp\Radius\Radius();
        // Não use setPort() - use a porta padrão ou configure no construtor
        $radius->setServer('200.17.209.10')
               ->setSecret('rapadura');
        
        // Opcionalmente, você pode configurar a porta assim:
        // $radius->server = '200.17.209.10:1812';  // hostname:porta
        
        $result = $radius->accessRequest($_POST['username'], $_POST['password']);
        
        echo "<p style='padding:10px;background:" . ($result ? "#d4edda" : "#f8d7da") . ";'>";
        echo $result ? "✅ Autenticação bem-sucedida!" : "❌ Falha na autenticação";
        echo "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
    }
}