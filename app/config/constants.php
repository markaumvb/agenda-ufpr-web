<?php
// Ambiente (development ou production)
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
define('MAIL_PASSWORD', 'senha_do_email');
define('MAIL_FROM_NAME', 'Sistema de Agendamento UFPR');

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
define('SESSION_LIFETIME', 86400); // 24 horas em segundos

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