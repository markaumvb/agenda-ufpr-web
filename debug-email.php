<?php


// Carregar configurações
require_once __DIR__ . '/app/config/constants.php';
require_once __DIR__ . '/vendor/autoload.php';

// Configurar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔧 Debug do EmailService</h1>";
echo "<hr>";

// 1. VERIFICAR CONSTANTES DE E-MAIL
echo "<h2>📧 1. Verificação das Constantes de E-mail</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Constante</th><th>Valor</th><th>Status</th></tr>";

$emailConstants = [
    'MAIL_HOST' => MAIL_HOST,
    'MAIL_PORT' => MAIL_PORT,
    'MAIL_USERNAME' => MAIL_USERNAME,
    'MAIL_PASSWORD' => '***OCULTO***',
    'MAIL_FROM_NAME' => MAIL_FROM_NAME,
    'MAIL_FROM_EMAIL' => MAIL_FROM_EMAIL,
    'MAIL_ENCRYPTION' => MAIL_ENCRYPTION,
    'MAIL_AUTH' => MAIL_AUTH ? 'true' : 'false',
    'MAIL_DEBUG' => MAIL_DEBUG
];

foreach ($emailConstants as $const => $value) {
    $status = !empty($value) ? "✅ OK" : "❌ VAZIO";
    echo "<tr><td>{$const}</td><td>{$value}</td><td>{$status}</td></tr>";
}
echo "</table>";

echo "<hr>";

// 2. VERIFICAR PHPMailer
echo "<h2>📦 2. Verificação do PHPMailer</h2>";
try {
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    echo "✅ PHPMailer carregado com sucesso<br>";
    echo "📍 Versão do PHPMailer: " . PHPMailer::VERSION . "<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar PHPMailer: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 3. TESTE DE CONEXÃO SMTP
echo "<h2>🔌 3. Teste de Conexão SMTP</h2>";
try {
    $testMailer = new PHPMailer(true);
    
    // Configurar para debug verbose
    $testMailer->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $testMailer->Debugoutput = function($str, $level) {
        echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px; font-family: monospace; font-size: 12px;'>";
        echo htmlspecialchars($str);
        echo "</div>";
    };
    
    $testMailer->isSMTP();
    $testMailer->Host = MAIL_HOST;
    $testMailer->SMTPAuth = MAIL_AUTH;
    $testMailer->Username = MAIL_USERNAME;
    $testMailer->Password = MAIL_PASSWORD;
    $testMailer->SMTPSecure = MAIL_ENCRYPTION;
    $testMailer->Port = MAIL_PORT;
    $testMailer->Timeout = 10; // 10 segundos de timeout
    
    echo "<h3>🔍 Detalhes da Conexão SMTP:</h3>";
    
    // Tentar conectar
    if ($testMailer->smtpConnect()) {
        echo "<div style='color: green; font-weight: bold;'>✅ Conexão SMTP estabelecida com sucesso!</div>";
        $testMailer->smtpClose();
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Falha na conexão SMTP</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Erro na conexão SMTP: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// 4. TESTE DO EmailService
echo "<h2>⚙️ 4. Teste do EmailService</h2>";
try {
    require_once __DIR__ . '/app/services/EmailService.php';
    
    $emailService = new EmailService();
    echo "✅ EmailService instanciado com sucesso<br>";
    
    // E-mail de teste
    $testEmail = 'fabio.gat88@gmail.com'; // Use um e-mail válido para teste
    $testSubject = 'Teste do Sistema de Agendamento UFPR - ' . date('d/m/Y H:i:s');
    $testBody = "
        <html>
        <body>
            <h2>✅ Teste de E-mail</h2>
            <p>Este é um e-mail de teste do sistema de agendamento.</p>
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            <p><strong>Servidor:</strong> " . $_SERVER['HTTP_HOST'] . "</p>
            <p>Se você recebeu este e-mail, o sistema está funcionando corretamente!</p>
        </body>
        </html>
    ";
    
    echo "<h3>📨 Enviando e-mail de teste...</h3>";
    echo "<strong>Para:</strong> {$testEmail}<br>";
    echo "<strong>Assunto:</strong> {$testSubject}<br><br>";
    
    $result = $emailService->send($testEmail, $testSubject, $testBody, true);
    
    if ($result) {
        echo "<div style='color: green; font-weight: bold; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb;'>";
        echo "✅ E-MAIL ENVIADO COM SUCESSO!<br>";
        echo "Verifique a caixa de entrada de {$testEmail}";
        echo "</div>";
    } else {
        echo "<div style='color: red; font-weight: bold; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb;'>";
        echo "❌ FALHA NO ENVIO DO E-MAIL";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Erro no EmailService: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// 5. VERIFICAR LOGS DE ERRO
echo "<h2>📋 5. Últimos Logs de Erro</h2>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "<strong>Arquivo de log:</strong> {$errorLog}<br><br>";
    
    $logLines = file($errorLog);
    $lastLines = array_slice($logLines, -20); // Últimas 20 linhas
    
    echo "<div style='background: #f8f9fa; padding: 10px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: scroll;'>";
    foreach ($lastLines as $line) {
        if (stripos($line, 'mail') !== false || stripos($line, 'smtp') !== false) {
            echo "<div style='color: red;'>" . htmlspecialchars($line) . "</div>";
        } else {
            echo htmlspecialchars($line) . "<br>";
        }
    }
    echo "</div>";
} else {
    echo "ℹ️ Log de erros não encontrado ou não configurado<br>";
}

echo "<hr>";

// 6. INFORMAÇÕES DO SISTEMA
echo "<h2>💻 6. Informações do Sistema</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Item</th><th>Valor</th></tr>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>OpenSSL</td><td>" . (extension_loaded('openssl') ? '✅ Habilitado' : '❌ Desabilitado') . "</td></tr>";
echo "<tr><td>Socket</td><td>" . (extension_loaded('socket') ? '✅ Habilitado' : '❌ Desabilitado') . "</td></tr>";
echo "<tr><td>CURL</td><td>" . (extension_loaded('curl') ? '✅ Habilitado' : '❌ Desabilitado') . "</td></tr>";
echo "<tr><td>Date/Time</td><td>" . date('Y-m-d H:i:s T') . "</td></tr>";
echo "<tr><td>Server</td><td>" . $_SERVER['HTTP_HOST'] . "</td></tr>";
echo "</table>";

echo "<hr>";

// 7. DICAS DE RESOLUÇÃO
echo "<h2>💡 7. Possíveis Soluções</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3;'>";
echo "<h3>Se o e-mail não foi enviado, verifique:</h3>";
echo "<ul>";
echo "<li><strong>Firewall:</strong> Porta 587 (TLS) deve estar aberta</li>";
echo "<li><strong>Autenticação:</strong> Username e password corretos</li>";
echo "<li><strong>TLS/SSL:</strong> Certificados válidos</li>";
echo "<li><strong>Configuração SMTP:</strong> smtp.ufpr.br permite conexões externas?</li>";
echo "<li><strong>Rate Limiting:</strong> Servidor pode estar limitando envios</li>";
echo "<li><strong>DNS:</strong> Resolução do hostname smtp.ufpr.br</li>";
echo "</ul>";

echo "<h3>Comandos úteis para debug no servidor:</h3>";
echo "<code style='background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;'>";
echo "# Testar conectividade SMTP<br>";
echo "telnet smtp.ufpr.br 587<br><br>";
echo "# Verificar DNS<br>";
echo "nslookup smtp.ufpr.br<br><br>";
echo "# Testar porta<br>";
echo "nc -zv smtp.ufpr.br 587";
echo "</code>";
echo "</div>";

echo "<hr>";
echo "<p><em>Debug concluído às " . date('Y-m-d H:i:s') . "</em></p>";
?>