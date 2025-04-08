<?php
// Arquivo: public/radius_debug.php

// Carregar as configurações e constantes
require_once __DIR__ . '/../app/config/constants.php';
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/services/RadiusService.php';

// Habilitar exibição detalhada de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico do RADIUS</h1>";

// Verificar configurações
echo "<h2>Configurações RADIUS</h2>";
echo "<pre>";
echo "RADIUS_SERVER: " . (defined('RADIUS_SERVER') ? RADIUS_SERVER : 'Não definido') . "\n";
echo "RADIUS_PORT: " . (defined('RADIUS_PORT') ? RADIUS_PORT : 'Não definido') . "\n";
echo "RADIUS_SECRET: " . (defined('RADIUS_SECRET') ? '******' : 'Não definido') . "\n";
echo "SIMULATE_RADIUS: " . (defined('SIMULATE_RADIUS') && SIMULATE_RADIUS ? 'Sim' : 'Não') . "\n";
echo "</pre>";

// Verificar extensão RADIUS
echo "<h2>Status da Extensão RADIUS</h2>";
if (extension_loaded('radius')) {
    echo "<p style='color:green'>✓ Extensão RADIUS está instalada e carregada.</p>";
} else {
    echo "<p style='color:red'>✗ Extensão RADIUS não está instalada ou não está carregada.</p>";
    echo "<p>Você pode instalar a extensão com: <code>sudo apt-get install php-radius</code> (Ubuntu/Debian) ou compilar manualmente.</p>";
}

// Listar funções disponíveis
if (extension_loaded('radius')) {
    echo "<h3>Funções RADIUS disponíveis:</h3>";
    echo "<pre>";
    $radius_functions = get_extension_funcs('radius');
    print_r($radius_functions);
    echo "</pre>";
}

// Testar conexão RADIUS
echo "<h2>Teste de Autenticação RADIUS</h2>";
echo "<form method='post'>";
echo "Usuário: <input type='text' name='username' required><br>";
echo "Senha: <input type='password' name='password' required><br>";
echo "<button type='submit' name='test_radius'>Testar Autenticação</button>";
echo "</form>";

// Processar formulário
if (isset($_POST['test_radius'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<h3>Tentando autenticar usuário: $username</h3>";
    
    try {
        // Inicializar o serviço RADIUS
        $radiusService = new RadiusService();
        
        // Configurar modo de depuração
        $isDebug = true;
        
        // Testar autenticação
        echo "<pre>";
        echo "1. Verificando configuração SIMULATE_RADIUS...\n";
        echo "   SIMULATE_RADIUS: " . (defined('SIMULATE_RADIUS') && SIMULATE_RADIUS ? 'Ativado' : 'Desativado') . "\n\n";
        
        echo "2. Verificando disponibilidade da extensão RADIUS...\n";
        echo "   Extensão RADIUS: " . (extension_loaded('radius') ? 'Disponível' : 'Indisponível') . "\n\n";
        
        echo "3. Tentando autenticação...\n";
        $result = $radiusService->authenticate($username, $password);
        
        if ($result) {
            echo "\n✓ Autenticação RADIUS bem-sucedida para o usuário '$username'\n";
        } else {
            echo "\n✗ Falha na autenticação RADIUS para o usuário '$username'\n";
        }
        echo "</pre>";
        
        // Verificar detalhes no log de erros
        echo "<p>Verifique também o arquivo de log de erros do PHP para mensagens adicionais.</p>";
        
    } catch (Exception $e) {
        echo "<div style='color:red'>";
        echo "<h4>Erro durante a autenticação:</h4>";
        echo "<pre>{$e->getMessage()}</pre>";
        echo "<p>Tipo: " . get_class($e) . "</p>";
        echo "<p>Arquivo: {$e->getFile()}:{$e->getLine()}</p>";
        echo "<h4>Stack Trace:</h4>";
        echo "<pre>{$e->getTraceAsString()}</pre>";
        echo "</div>";
    }
}

// Testar função de fallback
echo "<h2>Teste da Função de Fallback</h2>";
if (isset($_POST['test_radius'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        // Criar uma classe RadiusService modificada para testar apenas o método de fallback
        class TestRadiusService extends RadiusService {
            public function testFallback($username, $password) {
                return $this->authenticateWithSockets($username, $password);
            }
        }
        
        $testService = new TestRadiusService();
        echo "<pre>";
        echo "Testando método de fallback (authenticateWithSockets)...\n";
        $fallbackResult = $testService->testFallback($username, $password);
        
        if ($fallbackResult) {
            echo "✓ Autenticação via sockets bem-sucedida para o usuário '$username'\n";
        } else {
            echo "✗ Falha na autenticação via sockets para o usuário '$username'\n";
        }
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "<div style='color:red'>";
        echo "<h4>Erro durante o teste de fallback:</h4>";
        echo "<pre>{$e->getMessage()}</pre>";
        echo "</div>";
    }
}

// Verificar últimas entradas no log de erros
echo "<h2>Verificação do Log de Erros</h2>";
$logPath = ini_get('error_log');
if (file_exists($logPath) && is_readable($logPath)) {
    echo "<p>Últimas 20 linhas do log de erros ($logPath):</p>";
    echo "<pre>";
    $logContent = file($logPath);
    $lastLines = array_slice($logContent, -20);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>Não foi possível ler o arquivo de log de erros. Caminho: $logPath</p>";
    
    // Tente encontrar o arquivo de log em locais comuns
    $commonLogPaths = [
        '/var/log/apache2/error.log',
        '/var/log/httpd/error_log',
        '/var/log/nginx/error.log',
        '/var/log/php-fpm/error.log',
        '/var/log/php-errors.log'
    ];
    
    foreach ($commonLogPaths as $path) {
        if (file_exists($path) && is_readable($path)) {
            echo "<p>Log encontrado em: $path</p>";
            echo "<pre>";
            $logContent = file($path);
            $lastLines = array_slice($logContent, -10);
            foreach ($lastLines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
            break;
        }
    }
}

// Exibir recomendações
echo "<h2>Recomendações</h2>";
echo "<ul>";
if (!extension_loaded('radius')) {
    echo "<li>Instale a extensão RADIUS do PHP ou verifique se ela está habilitada no php.ini</li>";
    echo "<li>Enquanto a extensão não estiver disponível, considere usar o modo de simulação (SIMULATE_RADIUS = true)</li>";
} else {
    echo "<li>Verifique se as configurações RADIUS_SERVER, RADIUS_PORT e RADIUS_SECRET estão corretas</li>";
    echo "<li>Verifique a conectividade de rede com o servidor RADIUS</li>";
}
echo "<li>Verifique as credenciais do usuário no servidor RADIUS</li>";
echo "<li>Consulte os logs do servidor RADIUS para detalhes sobre falhas de autenticação</li>";
echo "</ul>";
?>