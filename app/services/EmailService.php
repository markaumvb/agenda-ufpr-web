<?php


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
        $this->password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : 'brvcsyqkbkkqhzmd';
        $this->fromEmail = $this->username;
        $this->fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Sistema de Agendamento UFPR';
    }

    public function send($to, $subject, $body, $isHtml = true) {
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
    

    public function sendExternalUserConfirmation($compromisso, $agenda) {
        $subject = 'Solicitação Recebida: ' . $compromisso['title'];
        
        // Formatar datas
        $startDate = new DateTime($compromisso['start_datetime']);
        $endDate = new DateTime($compromisso['end_datetime']);
        
        $formattedStart = $startDate->format('d/m/Y H:i');
        $formattedEnd = $endDate->format('d/m/Y H:i');
        
        // Preparar informações dos novos campos
        $phoneFormatted = $this->formatPhone($compromisso['external_phone']);
        $companyInfo = !empty($compromisso['external_company']) ? $compromisso['external_company'] : 'Não informado';
        
        // Preparar corpo do e-mail em HTML
        $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background-color: #17a2b8; color: #fff; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #004a8f; color: #fff; text-decoration: none; border-radius: 4px; }
                    .status-info { background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 15px 0; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .details-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
                    .details-table td:first-child { font-weight: bold; background-color: #f8f9fa; width: 30%; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>✅ Solicitação Recebida</h1>
                    </div>
                    <div class='content'>
                        <p>Olá, <strong>{$compromisso['external_name']}</strong>!</p>
                        <p>Sua solicitação de compromisso foi recebida com sucesso.</p>
                        
                        <h2>📋 Detalhes da Solicitação</h2>
                        <table class='details-table'>
                            <tr>
                                <td>📅 Agenda:</td>
                                <td>{$agenda['title']}</td>
                            </tr>
                            <tr>
                                <td>📝 Título:</td>
                                <td>{$compromisso['title']}</td>
                            </tr>
                            <tr>
                                <td>🏷️ Assunto/Motivo:</td>
                                <td>{$compromisso['external_subject']}</td>
                            </tr>
                            <tr>
                                <td>🕒 Data/Hora:</td>
                                <td>{$formattedStart} até {$formattedEnd}</td>
                            </tr>
                            " . (!empty($compromisso['location']) ? "
                            <tr>
                                <td>📍 Local:</td>
                                <td>{$compromisso['location']}</td>
                            </tr>" : "") . "
                            " . (!empty($compromisso['description']) ? "
                            <tr>
                                <td>📄 Descrição:</td>
                                <td>{$compromisso['description']}</td>
                            </tr>" : "") . "
                        </table>
                        
                        <h3>👤 Seus Dados de Contato</h3>
                        <table class='details-table'>
                            <tr>
                                <td>📧 E-mail:</td>
                                <td>{$compromisso['external_email']}</td>
                            </tr>
                            <tr>
                                <td>📱 Telefone:</td>
                                <td>{$phoneFormatted}</td>
                            </tr>
                            <tr>
                                <td>🏢 Empresa/Instituição:</td>
                                <td>{$companyInfo}</td>
                            </tr>
                        </table>
                        
                        <div class='status-info'>
                            <strong>📋 Status Atual:</strong> ⏳ Aguardando Aprovação<br><br>
                            <strong>⏰ Próximos Passos:</strong><br>
                            • O responsável pela agenda analisará sua solicitação<br>
                            • Você receberá um e-mail quando a decisão for tomada<br>
                            • A resposta pode ser de aprovação ou rejeição<br>
                            • Em caso de dúvidas, o responsável pode entrar em contato via telefone
                        </div>
                        
                        <p style='margin-top: 20px; text-align: center;'>
                            <a href='" . BASE_URL . "/public-agenda/{$agenda['public_hash']}' class='btn'>👁️ Ver Agenda Pública</a>
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
        
        return $this->send($compromisso['external_email'], $subject, $body);
    }
    
    /**
     * Envia notificação para o dono da agenda com informações completas do solicitante
     */
    public function sendNewCompromissoNotificationToOwner($user, $compromisso, $agenda, $solicitanteName, $solicitanteEmail, $isExternal) {
        $subject = 'Nova Solicitação de Compromisso: ' . $compromisso['title'];
        
        // Formatar datas
        $startDate = new DateTime($compromisso['start_datetime']);
        $endDate = new DateTime($compromisso['end_datetime']);
        
        $formattedStart = $startDate->format('d/m/Y H:i');
        $formattedEnd = $endDate->format('d/m/Y H:i');
        
        // Preparar informações específicas para usuários externos
        $solicitanteInfo = '';
        if ($isExternal) {
            $phoneFormatted = $this->formatPhone($compromisso['external_phone']);
            $companyInfo = !empty($compromisso['external_company']) ? $compromisso['external_company'] : 'Não informado';
            
            $solicitanteInfo = "
                <h3>👤 Dados do Solicitante (Usuário Externo)</h3>
                <table class='details-table'>
                    <tr>
                        <td>👤 Nome:</td>
                        <td>{$compromisso['external_name']}</td>
                    </tr>
                    <tr>
                        <td>📧 E-mail:</td>
                        <td>{$compromisso['external_email']}</td>
                    </tr>
                    <tr>
                        <td>📱 Telefone:</td>
                        <td>{$phoneFormatted}</td>
                    </tr>
                    <tr>
                        <td>🏷️ Assunto/Motivo:</td>
                        <td><strong>{$compromisso['external_subject']}</strong></td>
                    </tr>
                    <tr>
                        <td>🏢 Empresa/Instituição:</td>
                        <td>{$companyInfo}</td>
                    </tr>
                </table>
            ";
        } else {
            $solicitanteInfo = "
                <p><strong>👤 Solicitante:</strong> {$solicitanteName} (usuário do sistema)</p>
            ";
        }
        
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
                    .btn { display: inline-block; padding: 12px 24px; margin: 5px; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; }
                    .btn-approve { background-color: #28a745; }
                    .btn-reject { background-color: #dc3545; }
                    .btn-view { background-color: #17a2b8; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                    .details-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
                    .details-table td:first-child { font-weight: bold; background-color: #f8f9fa; width: 30%; }
                    .highlight-box { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>📩 Nova Solicitação de Compromisso</h1>
                    </div>
                    <div class='content'>
                        <p>Olá, <strong>{$user['name']}</strong>!</p>
                        <p>Uma nova solicitação de compromisso foi recebida para sua agenda <strong>{$agenda['title']}</strong>.</p>
                        
                        {$solicitanteInfo}
                        
                        <h3>📅 Detalhes do Compromisso Solicitado</h3>
                        <table class='details-table'>
                            <tr>
                                <td>📝 Título:</td>
                                <td><strong>{$compromisso['title']}</strong></td>
                            </tr>
                            <tr>
                                <td>🕒 Data/Hora:</td>
                                <td>{$formattedStart} até {$formattedEnd}</td>
                            </tr>
                            " . (!empty($compromisso['location']) ? "
                            <tr>
                                <td>📍 Local:</td>
                                <td>{$compromisso['location']}</td>
                            </tr>" : "") . "
                            " . (!empty($compromisso['description']) ? "
                            <tr>
                                <td>📄 Descrição:</td>
                                <td>{$compromisso['description']}</td>
                            </tr>" : "") . "
                            <tr>
                                <td>📊 Status:</td>
                                <td><strong>⏳ Aguardando sua aprovação</strong></td>
                            </tr>
                        </table>
                        
                        " . ($isExternal ? "
                        <div class='highlight-box'>
                            <strong>💡 Dica:</strong> Como este é um usuário externo, você pode entrar em contato diretamente via telefone ({$phoneFormatted}) se precisar esclarecer algum detalhe antes de aprovar ou rejeitar a solicitação.
                        </div>" : "") . "
                        
                        <h3>⚡ Ações Disponíveis</h3>
                        <p style='text-align: center;'>
                            <a href='" . BASE_URL . "/meuscompromissos' class='btn btn-view'>👁️ Ver Todas as Solicitações</a><br>
                            <a href='" . BASE_URL . "/compromissos?agenda_id={$agenda['id']}' class='btn btn-view'>📅 Ver Agenda Completa</a>
                        </p>
                        
                        <p style='margin-top: 30px; font-size: 0.9em; color: #666;'>
                            <strong>💡 Lembre-se:</strong> É importante analisar e responder às solicitações o mais breve possível para proporcionar uma boa experiência aos solicitantes.
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
    
    public function sendExternalUserConfirmation($compromisso, $agenda) {
        $subject = 'Solicitação Recebida: ' . $compromisso['title'];
        
        // Formatar datas
        $startDate = new DateTime($compromisso['start_datetime']);
        $endDate = new DateTime($compromisso['end_datetime']);
        
        $formattedStart = $startDate->format('d/m/Y H:i');
        $formattedEnd = $endDate->format('d/m/Y H:i');
        
        // Preparar informações dos novos campos
        $phoneFormatted = $this->formatPhone($compromisso['external_phone']);
        $companyInfo = !empty($compromisso['external_company']) ? $compromisso['external_company'] : 'Não informado';
        
        // Preparar corpo do e-mail em HTML
        $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background-color: #17a2b8; color: #fff; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #004a8f; color: #fff; text-decoration: none; border-radius: 4px; }
                    .status-info { background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 15px 0; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    .details-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
                    .details-table td:first-child { font-weight: bold; background-color: #f8f9fa; width: 30%; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>✅ Solicitação Recebida</h1>
                    </div>
                    <div class='content'>
                        <p>Olá, <strong>{$compromisso['external_name']}</strong>!</p>
                        <p>Sua solicitação de compromisso foi recebida com sucesso.</p>
                        
                        <h2>📋 Detalhes da Solicitação</h2>
                        <table class='details-table'>
                            <tr>
                                <td>📅 Agenda:</td>
                                <td>{$agenda['title']}</td>
                            </tr>
                            <tr>
                                <td>📝 Título:</td>
                                <td>{$compromisso['title']}</td>
                            </tr>
                            <tr>
                                <td>🏷️ Assunto/Motivo:</td>
                                <td>{$compromisso['external_subject']}</td>
                            </tr>
                            <tr>
                                <td>🕒 Data/Hora:</td>
                                <td>{$formattedStart} até {$formattedEnd}</td>
                            </tr>
                            " . (!empty($compromisso['location']) ? "
                            <tr>
                                <td>📍 Local:</td>
                                <td>{$compromisso['location']}</td>
                            </tr>" : "") . "
                            " . (!empty($compromisso['description']) ? "
                            <tr>
                                <td>📄 Descrição:</td>
                                <td>{$compromisso['description']}</td>
                            </tr>" : "") . "
                        </table>
                        
                        <h3>👤 Seus Dados de Contato</h3>
                        <table class='details-table'>
                            <tr>
                                <td>📧 E-mail:</td>
                                <td>{$compromisso['external_email']}</td>
                            </tr>
                            <tr>
                                <td>📱 Telefone:</td>
                                <td>{$phoneFormatted}</td>
                            </tr>
                            <tr>
                                <td>🏢 Empresa/Instituição:</td>
                                <td>{$companyInfo}</td>
                            </tr>
                        </table>
                        
                        <div class='status-info'>
                            <strong>📋 Status Atual:</strong> ⏳ Aguardando Aprovação<br><br>
                            <strong>⏰ Próximos Passos:</strong><br>
                            • O responsável pela agenda analisará sua solicitação<br>
                            • Você receberá um e-mail quando a decisão for tomada<br>
                            • A resposta pode ser de aprovação ou rejeição<br>
                            • Em caso de dúvidas, o responsável pode entrar em contato via telefone
                        </div>
                        
                        <p style='margin-top: 20px; text-align: center;'>
                            <a href='" . BASE_URL . "/public-agenda/{$agenda['public_hash']}' class='btn'>👁️ Ver Agenda Pública</a>
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
        
        return $this->send($compromisso['external_email'], $subject, $body);
    }
    
    /**
     * Envia notificação para o dono da agenda com informações completas do solicitante
     */
    public function sendNewCompromissoNotificationToOwner($user, $compromisso, $agenda, $solicitanteName, $solicitanteEmail, $isExternal) {
        $subject = 'Nova Solicitação de Compromisso: ' . $compromisso['title'];
        
        // Formatar datas
        $startDate = new DateTime($compromisso['start_datetime']);
        $endDate = new DateTime($compromisso['end_datetime']);
        
        $formattedStart = $startDate->format('d/m/Y H:i');
        $formattedEnd = $endDate->format('d/m/Y H:i');
        
        // Preparar informações específicas para usuários externos
        $solicitanteInfo = '';
        if ($isExternal) {
            $phoneFormatted = $this->formatPhone($compromisso['external_phone']);
            $companyInfo = !empty($compromisso['external_company']) ? $compromisso['external_company'] : 'Não informado';
            
            $solicitanteInfo = "
                <h3>👤 Dados do Solicitante (Usuário Externo)</h3>
                <table class='details-table'>
                    <tr>
                        <td>👤 Nome:</td>
                        <td>{$compromisso['external_name']}</td>
                    </tr>
                    <tr>
                        <td>📧 E-mail:</td>
                        <td>{$compromisso['external_email']}</td>
                    </tr>
                    <tr>
                        <td>📱 Telefone:</td>
                        <td>{$phoneFormatted}</td>
                    </tr>
                    <tr>
                        <td>🏷️ Assunto/Motivo:</td>
                        <td><strong>{$compromisso['external_subject']}</strong></td>
                    </tr>
                    <tr>
                        <td>🏢 Empresa/Instituição:</td>
                        <td>{$companyInfo}</td>
                    </tr>
                </table>
            ";
        } else {
            $solicitanteInfo = "
                <p><strong>👤 Solicitante:</strong> {$solicitanteName} (usuário do sistema)</p>
            ";
        }
        
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
                    .btn { display: inline-block; padding: 12px 24px; margin: 5px; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; }
                    .btn-approve { background-color: #28a745; }
                    .btn-reject { background-color: #dc3545; }
                    .btn-view { background-color: #17a2b8; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                    .details-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
                    .details-table td:first-child { font-weight: bold; background-color: #f8f9fa; width: 30%; }
                    .highlight-box { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>📩 Nova Solicitação de Compromisso</h1>
                    </div>
                    <div class='content'>
                        <p>Olá, <strong>{$user['name']}</strong>!</p>
                        <p>Uma nova solicitação de compromisso foi recebida para sua agenda <strong>{$agenda['title']}</strong>.</p>
                        
                        {$solicitanteInfo}
                        
                        <h3>📅 Detalhes do Compromisso Solicitado</h3>
                        <table class='details-table'>
                            <tr>
                                <td>📝 Título:</td>
                                <td><strong>{$compromisso['title']}</strong></td>
                            </tr>
                            <tr>
                                <td>🕒 Data/Hora:</td>
                                <td>{$formattedStart} até {$formattedEnd}</td>
                            </tr>
                            " . (!empty($compromisso['location']) ? "
                            <tr>
                                <td>📍 Local:</td>
                                <td>{$compromisso['location']}</td>
                            </tr>" : "") . "
                            " . (!empty($compromisso['description']) ? "
                            <tr>
                                <td>📄 Descrição:</td>
                                <td>{$compromisso['description']}</td>
                            </tr>" : "") . "
                            <tr>
                                <td>📊 Status:</td>
                                <td><strong>⏳ Aguardando sua aprovação</strong></td>
                            </tr>
                        </table>
                        
                        " . ($isExternal ? "
                        <div class='highlight-box'>
                            <strong>💡 Dica:</strong> Como este é um usuário externo, você pode entrar em contato diretamente via telefone ({$phoneFormatted}) se precisar esclarecer algum detalhe antes de aprovar ou rejeitar a solicitação.
                        </div>" : "") . "
                        
                        <h3>⚡ Ações Disponíveis</h3>
                        <p style='text-align: center;'>
                            <a href='" . BASE_URL . "/meuscompromissos' class='btn btn-view'>👁️ Ver Todas as Solicitações</a><br>
                            <a href='" . BASE_URL . "/compromissos?agenda_id={$agenda['id']}' class='btn btn-view'>📅 Ver Agenda Completa</a>
                        </p>
                        
                        <p style='margin-top: 30px; font-size: 0.9em; color: #666;'>
                            <strong>💡 Lembre-se:</strong> É importante analisar e responder às solicitações o mais breve possível para proporcionar uma boa experiência aos solicitantes.
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
     * Envia e-mail de decisão para usuário externo (aprovação/rejeição)
     */
    public function sendExternalUserDecision($compromisso, $agenda, $decision, $ownerName) {
        $isApproved = ($decision === 'approved');
        
        $subject = $isApproved 
            ? '✅ Solicitação Aprovada: ' . $compromisso['title']
            : '❌ Solicitação Rejeitada: ' . $compromisso['title'];
        
        // Formatar datas
        $startDate = new DateTime($compromisso['start_datetime']);
        $endDate = new DateTime($compromisso['end_datetime']);
        
        $formattedStart = $startDate->format('d/m/Y H:i');
        $formattedEnd = $endDate->format('d/m/Y H:i');
        
        // Cores e ícones baseados na decisão
        $headerColor = $isApproved ? '#28a745' : '#dc3545';
        $icon = $isApproved ? '✅' : '❌';
        $statusText = $isApproved ? 'APROVADA' : 'REJEITADA';
        
        // Preparar informações de contato
        $phoneFormatted = $this->formatPhone($compromisso['external_phone']);
        $companyInfo = !empty($compromisso['external_company']) ? $compromisso['external_company'] : 'Não informado';
        
        // Preparar corpo do e-mail em HTML
        $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; }
                    .header { background-color: {$headerColor}; color: #fff; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #004a8f; color: #fff; text-decoration: none; border-radius: 4px; }
                    .decision-box { background-color: " . ($isApproved ? '#d4edda' : '#f8d7da') . "; border: 1px solid " . ($isApproved ? '#c3e6cb' : '#f5c6cb') . "; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
                    .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                    .details-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
                    .details-table td:first-child { font-weight: bold; background-color: #f8f9fa; width: 35%; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>{$icon} Solicitação {$statusText}</h1>
                    </div>
                    <div class='content'>
                        <p>Olá, <strong>{$compromisso['external_name']}</strong>!</p>
                        
                        <div class='decision-box'>
                            <h2 style='margin-top: 0; color: {$headerColor};'>Sua solicitação foi {$statusText}</h2>
                            <p style='margin-bottom: 0;'>Decisão tomada por: <strong>{$ownerName}</strong></p>
                        </div>
                        
                        <h3>📋 Resumo da Solicitação:</h3>
                        <table class='details-table'>
                            <tr>
                                <td>📅 Agenda:</td>
                                <td>{$agenda['title']}</td>
                            </tr>
                            <tr>
                                <td>📝 Título:</td>
                                <td>{$compromisso['title']}</td>
                            </tr>
                            <tr>
                                <td>🏷️ Assunto/Motivo:</td>
                                <td>{$compromisso['external_subject']}</td>
                            </tr>
                            <tr>
                                <td>🕒 Data/Hora:</td>
                                <td>{$formattedStart} até {$formattedEnd}</td>
                            </tr>
                            " . (!empty($compromisso['location']) ? "
                            <tr>
                                <td>📍 Local:</td>
                                <td>{$compromisso['location']}</td>
                            </tr>" : "") . "
                            " . (!empty($compromisso['description']) ? "
                            <tr>
                                <td>📄 Descrição:</td>
                                <td>{$compromisso['description']}</td>
                            </tr>" : "") . "
                        </table>
                        
                        <h3>👤 Seus Dados:</h3>
                        <table class='details-table'>
                            <tr>
                                <td>📧 E-mail:</td>
                                <td>{$compromisso['external_email']}</td>
                            </tr>
                            <tr>
                                <td>📱 Telefone:</td>
                                <td>{$phoneFormatted}</td>
                            </tr>
                            <tr>
                                <td>🏢 Empresa/Instituição:</td>
                                <td>{$companyInfo}</td>
                            </tr>
                        </table>";
        
        if ($isApproved) {
            $body .= "
                        <p style='color: #28a745; font-weight: bold; text-align: center; font-size: 1.1em;'>
                            🎉 Parabéns! Seu compromisso foi confirmado e aparecerá na agenda pública.
                        </p>
                        <p style='text-align: center; margin-top: 20px;'>
                            📅 <strong>Não esqueça:</strong> Compareça no horário agendado!<br>
                            📞 Em caso de imprevistos, entre em contato antecipadamente.
                        </p>";
        } else {
            $body .= "
                        <p style='color: #dc3545; text-align: center;'>
                            😔 Infelizmente sua solicitação não pôde ser aprovada neste momento.
                        </p>
                        <p style='text-align: center;'>
                            💡 <strong>Sugestões:</strong><br>
                            • Tente novamente em outro horário<br>
                            • Verifique a disponibilidade na agenda pública<br>
                            • Entre em contato diretamente se necessário
                        </p>";
        }
        
        $body .= "
                        <p style='margin-top: 30px; text-align: center;'>
                            <a href='" . BASE_URL . "/public-agenda/{$agenda['public_hash']}' class='btn'>👁️ Ver Agenda Pública</a>
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
        
        return $this->send($compromisso['external_email'], $subject, $body);
    }
    
    /**
     * Formata telefone para exibição
     */
    private function formatPhone($phone) {
        if (empty($phone)) return 'Não informado';
        
        // Remove tudo que não é número
        $numbers = preg_replace('/\D/', '', $phone);
        
        // Formato (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
        if (strlen($numbers) == 11) {
            return '(' . substr($numbers, 0, 2) . ') ' . substr($numbers, 2, 5) . '-' . substr($numbers, 7, 4);
        } elseif (strlen($numbers) == 10) {
            return '(' . substr($numbers, 0, 2) . ') ' . substr($numbers, 2, 4) . '-' . substr($numbers, 6, 4);
        }
        
        return $phone; // Retorna como estava se não conseguir formatar
    }
    

}