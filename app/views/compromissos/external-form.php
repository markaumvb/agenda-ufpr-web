<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Compromisso - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/component.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/compromissos.css">
</head>
<body class="auth-page">
    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-calendar-plus"></i> Criar Compromisso</h1>
            <a href="<?= BASE_URL ?>/" class="btn btn-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Início
            </a>
        </div>
        
        <div class="external-notice">
            <i class="fas fa-info-circle"></i>
            <strong>Agenda:</strong> <?= htmlspecialchars($agenda['title']) ?><br>
            <strong>Responsável:</strong> <?= htmlspecialchars($agenda['owner_name'] ?? 'N/A') ?><br>
            <small>Seu compromisso ficará com status "Aguardando Aprovação" até ser analisado pelo responsável.</small>
        </div>

        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach($_SESSION['validation_errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['validation_errors']); ?>
        <?php endif; ?>
        
        <form action="<?= BASE_URL ?>/compromissos/external-create" method="post" class="compromisso-form">
            <input type="hidden" name="agenda_id" value="<?= $agenda['id'] ?>">
            
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-user"></i> Seus Dados
                </h3>
                
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="external_name">
                            <i class="fas fa-user"></i> Nome Completo *
                        </label>
                        <input type="text" id="external_name" name="external_name" required class="form-control" 
                               placeholder="Digite seu nome completo"
                               value="<?= isset($_SESSION['form_data']['external_name']) ? htmlspecialchars($_SESSION['form_data']['external_name']) : '' ?>">
                    </div>
                    
                    <div class="form-group form-group-half">
                        <label for="external_email">
                            <i class="fas fa-envelope"></i> E-mail *
                        </label>
                        <input type="email" id="external_email" name="external_email" required class="form-control" 
                               placeholder="seu@email.com"
                               value="<?= isset($_SESSION['form_data']['external_email']) ? htmlspecialchars($_SESSION['form_data']['external_email']) : '' ?>">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i>
                            Você receberá e-mails sobre o status da sua solicitação
                        </small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="external_phone">
                            <i class="fas fa-phone"></i> Telefone *
                        </label>
                        <input type="tel" id="external_phone" name="external_phone" required class="form-control" 
                               placeholder="(XX) XXXXX-XXXX"
                               value="<?= isset($_SESSION['form_data']['external_phone']) ? htmlspecialchars($_SESSION['form_data']['external_phone']) : '' ?>">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i>
                            Para contato em caso de necessidade
                        </small>
                    </div>
                    
                    <div class="form-group form-group-half">
                        <label for="external_company">
                            <i class="fas fa-building"></i> Empresa/Instituição
                        </label>
                        <input type="text" id="external_company" name="external_company" class="form-control" 
                               placeholder="Nome da sua empresa ou instituição (opcional)"
                               value="<?= isset($_SESSION['form_data']['external_company']) ? htmlspecialchars($_SESSION['form_data']['external_company']) : '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="external_subject">
                        <i class="fas fa-tag"></i> Motivo/Assunto do Agendamento *
                    </label>
                    <input type="text" id="external_subject" name="external_subject" required class="form-control" 
                           placeholder="Descreva brevemente o motivo do seu agendamento"
                           value="<?= isset($_SESSION['form_data']['external_subject']) ? htmlspecialchars($_SESSION['form_data']['external_subject']) : '' ?>">
                    <small class="form-text text-muted">
                        <i class="fas fa-lightbulb"></i>
                        Ex: Reunião comercial, consulta, atendimento técnico, etc.
                    </small>
                </div>
            </div>
            
            <div class="form-actions">
                <div class="action-group primary-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-right"></i>
                        <span>Continuar</span>
                    </button>
                    
                    <a href="<?= BASE_URL ?>/" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        // Máscara para telefone
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('external_phone');
            
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length <= 11) {
                    if (value.length <= 10) {
                        // Formato: (XX) XXXX-XXXX
                        value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                    } else {
                        // Formato: (XX) XXXXX-XXXX
                        value = value.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                    }
                }
                
                e.target.value = value;
            });
            
            // Validação do formulário
            const form = document.querySelector('.compromisso-form');
            form.addEventListener('submit', function(e) {
                const name = document.getElementById('external_name').value.trim();
                const email = document.getElementById('external_email').value.trim();
                const phone = document.getElementById('external_phone').value.trim();
                const subject = document.getElementById('external_subject').value.trim();
                
                if (!name || !email || !phone || !subject) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatórios.');
                    return false;
                }
                
                // Validar telefone (deve ter pelo menos 10 dígitos)
                const phoneDigits = phone.replace(/\D/g, '');
                if (phoneDigits.length < 10) {
                    e.preventDefault();
                    alert('Por favor, insira um telefone válido com DDD.');
                    document.getElementById('external_phone').focus();
                    return false;
                }
                
                return true;
            });
        });
    </script>
    
    <?php unset($_SESSION['form_data']); ?>
</body>
</html>