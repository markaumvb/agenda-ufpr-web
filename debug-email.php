<?php
/**
 * Script de Debug para EmailService - Versão Simplificada
 * Salve como: debug-email.php na raiz do projeto
 * Acesse via: https://200.238.174.7/agenda_ufpr/debug-email.php
 */

// Configurar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug E-mail</title></head><body>";
echo "<h1>🔧 Debug do EmailService - Versão Simplificada</h1>";
echo "<hr>";

// 1. Carregar configurações
echo "<h2>⚙️ 1. Carregando Configurações</h2>";
try {
    require_once __DIR__ . '/app/config/constants.php';
    echo "✅ Constantes carregadas<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar constantes: " . $e->getMessage() . "<br>";
    exit;
}

try {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✅ Autoload carregado<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar autoload: " . $e->getMessage() . "<br>";
    exit;
}

echo "<hr>";

// 2. VERIFICAR CONSTANTES
echo "<h2>📧 2. Verificação das Constantes</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Constante</th><th>Valor</th><th>Status</th></tr>";

$constants = [
    'MAIL_HOST' => defined('MAIL_HOST') ? MAIL_HOST : 'NÃO DEFINIDO',
    'MAIL_PORT' => defined('MAIL_PORT') ? MAIL_PORT : 'NÃO DEFINIDO',
    'MAIL_USERNAME' => defined('MAIL_USERNAME') ? MAIL_USERNAME : 'NÃO DEFINIDO',
    'MAIL_PASSWORD' => defined('MAIL_PASSWORD') ? '***OCULTO***' : 'NÃO DEFINIDO',
    'MAIL_FROM_EMAIL' => defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'NÃO DEFINIDO',
    'MAIL_ENCRYPTION' => defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'NÃO DEFINIDO',
    'MAIL_AUTH' => defined('MAIL_AUTH') ? (MAIL_AUTH ? 'true' : 'false') : 'NÃO DEFINIDO'
];

foreach ($constants as $name => $value) {
    $status = ($value !== 'NÃO DEFINIDO' && !empty($value)) ? "✅ OK" : "❌ PROBLEMA";
    echo "<tr><td>{$name}</td><td>{$value}</td><td>{$status}</td></tr>";
}
echo "</table>";
echo "<hr>";

// 3. TESTE BÁSICO DO PHPMailer
echo "<h2>📦 3. Teste Básico do PHPMailer</h2>";

if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "❌ PHPMailer não foi carregado. Verificando autoload...<br>";
    
    // Tentar carregar manualmente
    $composerAutoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        echo "📁 Arquivo autoload encontrado: {$composerAutoload}<br>";
        require_once $composerAutoload;
        
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            echo "✅ PHPMailer carregado após inclusão manual<br>";
        } else {
            echo "❌ PHPMailer ainda não carregado. Execute 'composer install'<br>";
        }
    } else {
        echo "❌ Arquivo autoload não encontrado. Execute 'composer install'<br>";
    }
} else {
    echo "✅ PHPMailer está disponível<br>";
}

echo "<hr>";

// 4. TESTE DE CONECTIVIDADE BÁSICA
echo "<h2>🔌 4. Teste de Conectividade</h2>";

if (defined('MAIL_HOST') && defined('MAIL_PORT')) {
    $host = MAIL_HOST;
    $port = MAIL_PORT;
    
    echo "Testando conexão para {$host}:{$port}...<br>";
    
    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    if ($fp) {
        echo "✅ Conexão TCP estabelecida com sucesso<br>";
        fclose($fp);
    } else {
        echo "❌ Falha na conexão TCP: {$errno} - {$errstr}<br>";
    }
} else {
    echo "❌ MAIL_HOST ou MAIL_PORT não definidos<br>";
}

echo "<hr>";

// 5. TESTE DO EmailService
echo "<h2>⚙️ 5. Teste do EmailService</h2>";

try {
    $emailServicePath = __DIR__ . '/app/services/EmailService.php';
    if (!file_exists($emailServicePath)) {
        throw new Exception("Arquivo EmailService.php não encontrado: {$emailServicePath}");
    }
    
    require_once $emailServicePath;
    echo "✅ EmailService.php incluído<br>";
    
    if (!class_exists('EmailService')) {
        throw new Exception("Classe EmailService não foi definida");
    }
    
    echo "✅ Classe EmailService encontrada<br>";
    
    // Tentar instanciar
    $emailService = new EmailService();
    echo "✅ EmailService instanciado com sucesso<br>";
    
    // E-mail de teste simples
    $testEmail = 'markaumvb@gmail.com'; // Altere para um e-mail de teste válido
    $testSubject = 'Teste Sistema UFPR - ' . date('H:i:s');
    $testMessage = 'Este é um teste simples do sistema de e-mail. Enviado em: ' . date('d/m/Y H:i:s');
    
    echo "<br><strong>📨 Tentando enviar e-mail de teste...</strong><br>";
    echo "Para: {$testEmail}<br>";
    echo "Assunto: {$testSubject}<br><br>";
    
    // Capturar possíveis erros
    ob_start();
    $result = $emailService->send($testEmail, $testSubject, $testMessage, false);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #ddd;'>";
        echo "<strong>Output capturado:</strong><br>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
    }
    
    if ($result) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; color: #155724;'>";
        echo "<strong>✅ E-MAIL ENVIADO COM SUCESSO!</strong><br>";
        echo "Verifique a caixa de entrada de {$testEmail}";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; color: #721c24;'>";
        echo "<strong>❌ FALHA NO ENVIO</strong><br>";
        echo "Verifique os logs e configurações acima.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; color: #721c24;'>";
    echo "<strong>❌ ERRO:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Linha:</strong> " . $e->getLine();
    echo "</div>";
}

echo "<hr>";

// 6. VERIFICAR LOGS
echo "<h2>📋 6. Logs do Sistema</h2>";

// Verificar error_log padrão
$errorLogFile = ini_get('error_log');
if ($errorLogFile && file_exists($errorLogFile)) {
    echo "📁 Log encontrado: {$errorLogFile}<br>";
    
    $lines = @file($errorLogFile);
    if ($lines) {
        $recent = array_slice($lines, -10); // Últimas 10 linhas
        echo "<div style='background: #f8f9fa; padding: 10px; font-family: monospace; font-size: 12px;'>";
        foreach ($recent as $line) {
            echo htmlspecialchars($line) . "<br>";
        }
        echo "</div>";
    }
} else {
    echo "ℹ️ Log de erros não configurado ou não encontrado<br>";
}

echo "<hr>";

// 7. INFORMAÇÕES FINAIS
echo "<h2>💻 7. Informações do Sistema</h2>";
echo "<strong>PHP:</strong> " . phpversion() . "<br>";
echo "<strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? 'Disponível' : 'Não disponível') . "<br>";
echo "<strong>Sockets:</strong> " . (extension_loaded('sockets') ? 'Disponível' : 'Não disponível') . "<br>";
echo "<strong>Data/Hora:</strong> " . date('Y-m-d H:i:s T') . "<br>";

echo "<hr>";
echo "<p><em>Debug concluído às " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>