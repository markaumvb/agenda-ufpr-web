<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação Enviada - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/component.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/compromissos.css">
</head>
<body class="auth-page">
    <div class="form-container">
        <div class="success-header">
            <i class="fas fa-check-circle"></i>
            <h1>Solicitação Enviada com Sucesso!</h1>
            <p>Sua solicitação de compromisso foi recebida e será analisada em breve.</p>
        </div>

        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-info-circle"></i> Resumo da Solicitação
            </h3>
            
            <div class="details-grid">
                <div class="detail-item">
                    <strong><i class="fas fa-user"></i> Solicitante:</strong>
                    <span><?= htmlspecialchars($compromisso['external_name']) ?></span>
                </div>
                
                <div class="detail-item">
                    <strong><i class="fas fa-envelope"></i> E-mail:</strong>
                    <span><?= htmlspecialchars($compromisso['external_email']) ?></span>
                </div>
                
                <div class="detail-item">
                    <strong><i class="fas fa-phone"></i> Telefone:</strong>
                    <span><?= htmlspecialchars($compromisso['external_phone']) ?></span>
                </div>
                
                <div class="detail-item">
                    <strong><i class="fas fa-building"></i> Empresa/Instituição:</strong>
                    <span><?= !empty($compromisso['external_company']) ? htmlspecialchars($compromisso['external_company']) : 'Não informado' ?></span>
                </div>
                
                <div class="detail-item full-width">
                    <strong><i class="fas fa-tag"></i> Motivo/Assunto:</strong>
                    <span><?= htmlspecialchars($compromisso['external_subject']) ?></span>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-calendar"></i> Detalhes do Compromisso
            </h3>
            
            <div class="details-grid">
                <div class="detail-item">
                    <strong><i class="fas fa-calendar-alt"></i> Agenda:</strong>
                    <span><?= htmlspecialchars($agenda['title']) ?></span>
                </div>
                
                <div class="detail-item">
                    <strong><i class="fas fa-heading"></i> Título:</strong>
                    <span><?= htmlspecialchars($compromisso['title']) ?></span>
                </div>
                
                <div class="detail-item full-width">
                    <strong><i class="fas fa-clock"></i> Data/Hora:</strong>
                    <span>
                        <?php
                        $startDate = new DateTime($compromisso['start_datetime']);
                        $endDate = new DateTime($compromisso['end_datetime']);
                        echo $startDate->format('d/m/Y H:i') . ' às ' . $endDate->format('H:i');
                        ?>
                    </span>
                </div>
                
                <?php if (!empty($compromisso['location'])): ?>
                <div class="detail-item full-width">
                    <strong><i class="fas fa-map-marker-alt"></i> Local:</strong>
                    <span><?= htmlspecialchars($compromisso['location']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($compromisso['description'])): ?>
                <div class="detail-item full-width">
                    <strong><i class="fas fa-align-left"></i> Descrição:</strong>
                    <span><?= nl2br(htmlspecialchars($compromisso['description'])) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-item">
                    <strong><i class="fas fa-flag"></i> Status:</strong>
                    <span class="badge badge-warning">⏳ Aguardando Aprovação</span>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-question-circle"></i> Próximos Passos
            </h3>
            
            <div class="status-info">
                <div class="step">
                    <i class="fas fa-paper-plane text-success"></i>
                    <div>
                        <strong>✅ Solicitação Enviada</strong>
                        <p>Sua solicitação foi recebida com sucesso e todos os seus dados foram registrados.</p>
                    </div>
                </div>
                
                <div class="step">
                    <i class="fas fa-envelope text-info"></i>
                    <div>
                        <strong>📧 E-mail de Confirmação</strong>
                        <p>Você receberá um e-mail de confirmação em <strong><?= htmlspecialchars($compromisso['external_email']) ?></strong> com todos os detalhes da solicitação.</p>
                    </div>
                </div>
                
                <div class="step">
                    <i class="fas fa-user-check text-warning"></i>
                    <div>
                        <strong>⏳ Análise pelo Responsável</strong>
                        <p>O responsável pela agenda analisará sua solicitação. Caso necessário, ele pode entrar em contato via telefone <strong><?= htmlspecialchars($compromisso['external_phone']) ?></strong>.</p>
                    </div>
                </div>
                
                <div class="step">
                    <i class="fas fa-bell text-primary"></i>
                    <div>
                        <strong>📲 Notificação da Decisão</strong>
                        <p>Você será notificado por e-mail sobre a aprovação ou rejeição da sua solicitação.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section contact-info">
            <h3 class="section-title">
                <i class="fas fa-phone-alt"></i> Informações de Contato
            </h3>
            
            <div class="contact-details">
                <p><i class="fas fa-info-circle"></i> <strong>Importante:</strong> Mantenha seus dados de contato atualizados.</p>
                
                <div class="contact-grid">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>E-mail de Contato</strong>
                            <span><?= htmlspecialchars($compromisso['external_email']) ?></span>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Telefone de Contato</strong>
                            <span><?= htmlspecialchars($compromisso['external_phone']) ?></span>
                        </div>
                    </div>
                </div>
                
                <p class="contact-note">
                    <i class="fas fa-lightbulb"></i> 
                    O responsável pela agenda pode entrar em contato através destes meios caso precise esclarecer algum detalhe sobre sua solicitação.
                </p>
            </div>
        </div>

        <?php if (!empty($agenda['public_hash'])): ?>
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-eye"></i> Acompanhe a Agenda
            </h3>
            
            <p>Você pode acompanhar os compromissos públicos desta agenda:</p>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>/public-agenda/<?= $agenda['public_hash'] ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Ver Agenda Pública</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <div class="action-group primary-actions">
                <a href="<?= BASE_URL ?>/" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i>
                    <span>Voltar ao Início</span>
                </a>
                
                <?php if (!empty($agenda['public_hash'])): ?>
                <a href="<?= BASE_URL ?>/public-agenda/<?= $agenda['public_hash'] ?>" class="btn btn-secondary btn-lg">
                    <i class="fas fa-calendar"></i>
                    <span>Ver Agenda</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Estilos específicos para a página de sucesso -->
    <style>
        .success-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .success-header i {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
            display: block;
        }

        .success-header h1 {
            color: #28a745;
            margin-bottom: 0.5rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #004a8f;
        }

        .detail-item.full-width {
            grid-column: 1 / -1;
        }

        .detail-item strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .detail-item span {
            color: #666;
            font-size: 0.95rem;
        }

        .status-info {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
        }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .step:last-child {
            margin-bottom: 0;
        }

        .step i {
            font-size: 1.5rem;
            margin-top: 0.25rem;
            flex-shrink: 0;
        }

        .step strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .step p {
            margin: 0;
            color: #666;
            line-height: 1.4;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Novos estilos para informações de contato */
        .contact-info {
            background: linear-gradient(135deg, #e6f3ff 0%, #f0f7ff 100%);
            border: 2px solid #cbd5e0;
        }

        .contact-details {
            margin-top: 1rem;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .contact-item i {
            font-size: 1.5rem;
            color: #004a8f;
            flex-shrink: 0;
        }

        .contact-item strong {
            display: block;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .contact-item span {
            color: #666;
            font-size: 0.9rem;
        }

        .contact-note {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #fffbf0;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            color: #856404;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .contact-note i {
            color: #f39c12;
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .details-grid,
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .step i {
                align-self: center;
                margin-bottom: 0.5rem;
            }

            .contact-item {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
        }
    </style>
</body>
</html>