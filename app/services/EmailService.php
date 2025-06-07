<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    
    /**
     * Construtor - Configurar PHPMailer
     */
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    /**
     * Configurar SMTP usando constantes do config
     */
    private function configureSMTP() {
        try {
            // Configurações do servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = MAIL_HOST;
            $this->mailer->SMTPAuth = MAIL_AUTH;
            $this->mailer->Username = MAIL_USERNAME;
            $this->mailer->Password = MAIL_PASSWORD;
            $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
            $this->mailer->Port = MAIL_PORT;
            $this->mailer->SMTPDebug = MAIL_DEBUG;
            
            // Configurações de charset
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            
            // Remetente padrão
            $this->mailer->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
        } catch (Exception $e) {
            error_log('Erro ao configurar SMTP: ' . $e->getMessage());
            throw new Exception('Falha na configuração do e-mail');
        }
    }
    
    public function send($to, $subject, $body, $isHtml = true) {
        try {
            // Limpar destinatários anteriores
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            
            // Configurar destinatário
            $this->mailer->addAddress($to);
            
            // Configurar conteúdo
            $this->mailer->isHTML($isHtml);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            // Enviar
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("E-mail enviado com sucesso para: $to - Assunto: $subject");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Erro ao enviar e-mail: ' . $e->getMessage());
            return false;
        }
    }

    public function sendAgendaShareNotification($ownerUser, $sharedWithUser, $agenda, $canEdit) {
        $subject = 'Agendamento UFPR - Compartilhamento';
        
        // Data/hora atual formatada
        $currentDateTime = date('d/m/Y \à\s H:i');
        
        // Tipo de permissão
        $permissionType = $canEdit ? 'edição (pode criar, editar e excluir compromissos)' : 'visualização (apenas visualizar compromissos)';
        $permissionBadge = $canEdit ? 'Pode Editar' : 'Apenas Visualizar';
        $permissionColor = $canEdit ? '#28a745' : '#6c757d';
        
        // Preparar corpo do e-mail em HTML
        $body = "
            <html>
            <head>
<style>
                    body { 
                        font-family: Arial, sans-serif; 
                        line-height: 1.6; 
                        color: #333; 
                        margin: 0;
                        padding: 0;
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 0 auto; 
                        background: #ffffff;
                    }
                    .header { 
                        background: linear-gradient(135deg, #004a8f 0%, #0066cc 100%);
                        color: #fff; 
                        padding: 20px; 
                        text-align: center; 
                        border-radius: 8px 8px 0 0;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 1.5rem;
                        font-weight: 600;
                        color: #ffffff !important;
                    }
                    .content { 
                        padding: 30px 25px; 
                        background: #ffffff;
                    }
                    .agenda-info {
                        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                        border: 1px solid #e2e8f0;
                        border-radius: 8px;
                        padding: 20px;
                        margin: 20px 0;
                        border-left: 4px solid " . ($agenda['color'] ?? '#004a8f') . ";
                    }
                    .agenda-title {
                        font-size: 1.3rem;
                        font-weight: 600;
                        color: #004a8f;
                        margin-bottom: 10px;
                    }
                    .agenda-description {
                        color: #4a5568;
                        margin-bottom: 15px;
                        line-height: 1.5;
                    }
                    .permission-badge {
                        display: inline-block;
                        background-color: {$permissionColor};
                        color: white;
                        padding: 6px 12px;
                        border-radius: 20px;
                        font-size: 0.9rem;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    .info-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                        background: #ffffff;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    }
                    .info-table td {
                        padding: 12px 15px;
                        border-bottom: 1px solid #f1f5f9;
                    }
                    .info-table td:first-child {
                        background-color: #f8fafc;
                        font-weight: 600;
                        color: #2d3748;
                        width: 35%;
                    }
                    .info-table tr:last-child td {
                        border-bottom: none;
                    }
                    
                    /* BOTÕES CORRIGIDOS - CSS INLINE + IMPORTANT */
                    .btn {
                        display: inline-block !important;
                        padding: 15px 25px !important;
                        background-color: #004a8f !important;
                        background: #004a8f !important;
                        color: #ffffff !important;
                        text-decoration: none !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        margin: 10px 8px !important;
                        text-align: center !important;
                        font-size: 14px !important;
                        font-family: Arial, sans-serif !important;
                        border: 2px solid #004a8f !important;
                        box-shadow: 0 4px 12px rgba(0, 74, 143, 0.3) !important;
                    }
                    .btn:hover {
                        background-color: #003a70 !important;
                        background: #003a70 !important;
                        color: #ffffff !important;
                        text-decoration: none !important;
                        transform: translateY(-1px);
                        box-shadow: 0 6px 16px rgba(0, 74, 143, 0.4) !important;
                    }
                    .btn:visited {
                        color: #ffffff !important;
                        text-decoration: none !important;
                    }
                    .btn:active {
                        color: #ffffff !important;
                        text-decoration: none !important;
                    }
                    
                    .footer { 
                        background-color: #f8fafc; 
                        padding: 20px; 
                        text-align: center; 
                        font-size: 12px; 
                        color: #6c757d; 
                        border-radius: 0 0 8px 8px;
                        border-top: 1px solid #e2e8f0;
                    }
                    .highlight-box {
                        background: linear-gradient(135deg, #e6f3ff 0%, #f0f7ff 100%);
                        border: 1px solid #b3d9ff;
                        border-radius: 8px;
                        padding: 15px;
                        margin: 20px 0;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    
                    <div class='content'>
                        <p>Olá, <strong>{$sharedWithUser['name']}</strong>!</p>
                        
                        <p><strong>{$ownerUser['name']}</strong> compartilhou uma agenda com você no Sistema de Agendamento UFPR.</p>
                        
                        <div class='agenda-info'>
                            <div class='agenda-title'>{$agenda['title']}</div>
                            " . (!empty($agenda['description']) ? "<div class='agenda-description'>{$agenda['description']}</div>" : "") . "
                            <div style='margin-top: 10px;'>
                                <span class='permission-badge'>{$permissionBadge}</span>
                            </div>
                        </div>
                        
                        <table class='info-table'>
                            <tr>
                                <td>👤 Proprietário:</td>
                                <td><strong>{$ownerUser['name']}</strong> ({$ownerUser['email']})</td>
                            </tr>
                            <tr>
                                <td>📅 Data/Hora do Compartilhamento:</td>
                                <td><strong>{$currentDateTime}</strong></td>
                            </tr>
                            <tr>
                                <td>🔐 Permissão Concedida:</td>
                                <td><strong>{$permissionType}</strong></td>
                            </tr>
                            <tr>
                                <td>📊 Status da Agenda:</td>
                                <td>" . ($agenda['is_public'] ? '<strong style=\"color: #28a745;\">Pública</strong>' : '<strong style=\"color: #6c757d;\">Privada</strong>') . "</td>
                            </tr>
                        </table>
                        
                        <div class='highlight-box'>
                            <p style='margin: 0; font-weight: 600; color: #004a8f;'>
                                Agora você pode acessar esta agenda diretamente no sistema!
                            </p>
                        </div>
                        
                        <div style='text-align: center; margin-top: 30px;'>
                            <!-- BOTÕES COM CSS INLINE PARA MÁXIMA COMPATIBILIDADE -->
                            <a href='" . BASE_URL . "/compromissos?agenda_id={$agenda['id']}' 
                               style='display: inline-block; padding: 15px 25px; background-color: #004a8f; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 8px; text-align: center; font-size: 14px; font-family: Arial, sans-serif; border: 2px solid #004a8f; box-shadow: 0 4px 12px rgba(0, 74, 143, 0.3);'
                               class='btn'>
                                🔍 Ver Agenda Compartilhada
                            </a>";
                            
        if ($canEdit) {
            $body .= "
                            <a href='" . BASE_URL . "/compromissos/new?agenda_id={$agenda['id']}' 
                               style='display: inline-block; padding: 15px 25px; background-color: #28a745; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 10px 8px; text-align: center; font-size: 14px; font-family: Arial, sans-serif; border: 2px solid #28a745; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);'
                               class='btn'>
                                📝 Criar Compromisso
                            </a>";
        }
        
        $body .= "
                        </div>
                        
                        <p style='margin-top: 30px; font-size: 0.9em; color: #6c757d;'>
                            Acesse o sistema em <a href='" . BASE_URL . "' style='color: #004a8f;'>" . BASE_URL . "</a> e veja todas as agendas compartilhadas com você na seção \"Agendas Compartilhadas\".
                        </p>
                    </div>
                    
                    <div class='footer'>
                        <p>Este é um e-mail automático do Sistema de Agendamento UFPR.</p>
                        <p>Por favor, não responda este e-mail.</p>
                        <p>&copy; " . date('Y') . " - Universidade Federal do Paraná - Campus Jandaia do Sul</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        return $this->send($sharedWithUser['email'], $subject, $body, true);
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