<?php
// app/services/EmailService.php

/**
 * Serviço para envio de e-mails
 */
class EmailService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Carregar configurações definidas em constants.php
        $this->host = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.ufpr.br';
        $this->port = defined('MAIL_PORT') ? MAIL_PORT : 587;
        $this->username = defined('MAIL_USERNAME') ? MAIL_USERNAME : 'sistema.agenda@ufpr.br';
        $this->password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
        $this->fromEmail = $this->username;
        $this->fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Sistema de Agendamento UFPR';
    }
    
    /**
     * Envia um e-mail
     * 
     * @param string $to E-mail do destinatário
     * @param string $subject Assunto
     * @param string $body Corpo do e-mail
     * @param bool $isHtml Se o corpo é HTML
     * @return bool Resultado do envio
     */
    public function send($to, $subject, $body, $isHtml = true) {
        // Em ambiente de desenvolvimento, simular o envio
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            return $this->logEmail($to, $subject, $body);
        }
        
        // Preparar cabeçalhos
        $headers = [
            'From' => $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To' => $this->fromEmail,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0'
        ];
        
        // Definir tipo de conteúdo
        if ($isHtml) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        }
        
        // Preparar cabeçalhos para mail()
        $headerString = '';
        foreach ($headers as $name => $value) {
            $headerString .= $name . ': ' . $value . "\r\n";
        }
        
        // Tentar enviar o e-mail
        return mail($to, $subject, $body, $headerString);
    }
    
    /**
     * Envia e-mail de notificação de novo compromisso
     * 
     * @param array $user Dados do usuário
     * @param array $compromisso Dados do compromisso
     * @param array $agenda Dados da agenda
     * @return bool Resultado do envio
     */
    public function sendNewCompromissoNotification($user, $compromisso, $agenda) {
        $subject = 'Novo Compromisso: ' . $compromisso['title'];
        
        // Formatar datas
        $startDate = new DateTime($compromisso['start_datetime']);
        $endDate = new DateTime($compromisso['end_datetime']);
        
        $formattedStart = $startDate->format('d/m/Y H:i');
        $formattedEnd = $endDate->format('d/m/Y H:i');
        
        // Preparar corpo do e-mail em HTML
        $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background-color: #004a8f; color: #fff; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #004a8f; color: #fff; text-decoration: none; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Novo Compromisso</h1>
                    </div>
                    <div class='content'>
                        <p>Olá, {$user['name']}!</p>
                        <p>Um novo compromisso foi adicionado à sua agenda <strong>{$agenda['title']}</strong>:</p>
                        
                        <h2>{$compromisso['title']}</h2>
                        <p><strong>Data/Hora:</strong> {$formattedStart} até {$formattedEnd}</p>
                        
                        " . (!empty($compromisso['location']) ? "<p><strong>Local:</strong> {$compromisso['location']}</p>" : "") . "
                        
                        " . (!empty($compromisso['description']) ? "<p><strong>Descrição:</strong> {$compromisso['description']}</p>" : "") . "
                        
                        <p style='margin-top: 20px;'>
                            <a href='" . BASE_URL . "/compromissos/view?id={$compromisso['id']}' class='btn'>Ver Compromisso</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>Este é um e-mail automático. Por favor, não responda.</p>
                        <p>Sistema de Agendamento UFPR &copy; " . date('Y') . "</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        return $this->send($user['email'], $subject, $body);
    }
    
    /**
     * Envia e-mail de compartilhamento de agenda
     * 
     * @param array $owner Dados do proprietário da agenda
     * @param array $user Dados do usuário com quem a agenda foi compartilhada
     * @param array $agenda Dados da agenda
     * @param bool $canEdit Se o usuário pode editar a agenda
     * @return bool Resultado do envio
     */
    public function sendAgendaShareNotification($owner, $user, $agenda, $canEdit) {
        $subject = 'Agenda Compartilhada: ' . $agenda['title'];
        
        $permissionType = $canEdit ? 'edição' : 'visualização';
        
        // Preparar corpo do e-mail em HTML
        $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background-color: #004a8f; color: #fff; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #004a8f; color: #fff; text-decoration: none; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Agenda Compartilhada</h1>
                    </div>
                    <div class='content'>
                        <p>Olá, {$user['name']}!</p>
                        <p><strong>{$owner['name']}</strong> compartilhou uma agenda com você:</p>
                        
                        <h2>{$agenda['title']}</h2>
                        <p>{$agenda['description']}</p>
                        
                        <p>Você tem permissão de <strong>{$permissionType}</strong> para esta agenda.</p>
                        
                        <p style='margin-top: 20px;'>
                            <a href='" . BASE_URL . "/compromissos?agenda_id={$agenda['id']}' class='btn'>Ver Agenda</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>Este é um e-mail automático. Por favor, não responda.</p>
                        <p>Sistema de Agendamento UFPR &copy; " . date('Y') . "</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        return $this->send($user['email'], $subject, $body);
    }
    
    /**
     * Registra o e-mail em um arquivo de log (para ambiente de desenvolvimento)
     * 
     * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $body Corpo do e-mail
     * @return bool Sempre retorna true
     */
    private function logEmail($to, $subject, $body) {
        $logFile = __DIR__ . '/../../logs/email.log';
        $logDir = dirname($logFile);
        
        // Criar diretório de logs se não existir
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Formatar mensagem de log
        $log = "=== E-MAIL SIMULADO - " . date('Y-m-d H:i:s') . " ===\n";
        $log .= "Para: {$to}\n";
        $log .= "Assunto: {$subject}\n";
        $log .= "Corpo:\n{$body}\n\n";
        
        // Escrever no arquivo de log
        file_put_contents($logFile, $log, FILE_APPEND);
        
        return true;
    }
}