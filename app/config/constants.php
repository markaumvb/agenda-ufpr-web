<?php
define('ENVIRONMENT', 'development');

// Configurações de URL
define('BASE_URL', 'https://200.238.174.7/agenda_ufpr');
define('PUBLIC_URL', BASE_URL);

// Informações da aplicação
define('APP_NAME', 'Sistema de Agendamento UFPR / Jandaia do Sul');
define('APP_VERSION', '1.0.0');

// Configurações de e-mail
define('MAIL_HOST', 'smtp.ufpr.br');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'sistema.agenda@ufpr.br');
define('MAIL_PASSWORD', 'brvcsyqkbkkqhzmd'); // Senha real do EmailService
define('MAIL_FROM_NAME', 'Sistema de Agendamento UFPR');
define('MAIL_FROM_EMAIL', 'sistema.agenda@ufpr.br');
// Configurações SMTP adicionais
define('MAIL_ENCRYPTION', 'tls'); // tls ou ssl
define('MAIL_AUTH', true); // Autenticação SMTP
define('MAIL_DEBUG', 0); // 0=off, 1=client, 2=server

// Configurações RADIUS (autenticação)
define('RADIUS_SERVER', '200.17.209.10');
define('RADIUS_SECRET', 'rapadura');
define('RADIUS_PORT', 1812);

// Em ambiente de desenvolvimento, podemos simular o RADIUS
if (ENVIRONMENT === 'development') {
    define('SIMULATE_RADIUS', false);
}

// Configurações de segurança
define('SESSION_NAME', 'agenda_ufpr_session');
define('SESSION_LIFETIME', 86400); // 24 horas

// Configurar tratamento de erros de acordo com o ambiente
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
}

// Iniciar sessão com configurações seguras
session_name(SESSION_NAME);
ini_set('session.cookie_httponly', 1);
if (ENVIRONMENT === 'production') {
    ini_set('session.cookie_secure', 1);
}
session_start();

if (ENVIRONMENT === 'production') {
    ini_set('display_errors', 0);
    error_reporting(0);
}

setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
setlocale(LC_CTYPE, 'pt_BR.UTF-8', 'pt_BR', 'portuguese');