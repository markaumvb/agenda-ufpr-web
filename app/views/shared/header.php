<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- FullCalendar CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js"></script>

    <title><?= APP_NAME ?></title>

    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/approval-modal.css">

    
    <!-- CSS Principal - Mantenha esses sempre carregados para o layout básico funcionar -->
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/component.css">
    
    <!-- CSS específicos por módulo -->
    <?php 
    // Identificar a página atual para carregar apenas o CSS necessário
    $currentUri = $_SERVER['REQUEST_URI'];
    
    // Identificar se é uma página de formulário
    $isFormPage = (
        strpos($currentUri, '/new') !== false || 
        strpos($currentUri, '/edit') !== false || 
        strpos($currentUri, '/create') !== false || 
        strpos($currentUri, '/login') !== false || 
        strpos($currentUri, '/register') !== false ||
        (strpos($currentUri, 'public=1') !== false && strpos($currentUri, '/compromissos') !== false)
    );

    // Carregar CSS para formulários
    if ($isFormPage) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/forms.css">';
    }
    
    // Carregar CSS para autenticação
    if (strpos($currentUri, '/login') !== false || strpos($currentUri, '/register') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/auth/login.css">';
    }
    
    // Carregar CSS para agendas
    if (strpos($currentUri, '/agendas') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/agendas.css">';
    }
    
    // Carregar CSS para compromissos
    if (strpos($currentUri, '/compromissos') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/compromissos.css">';
    }
    
    // Carregar CSS para compartilhamentos
    if (strpos($currentUri, '/shares') !== false || 
        strpos($currentUri, '/public-agenda') !== false || 
        (strpos($currentUri, '/compromissos') !== false && strpos($currentUri, 'public=1') !== false)) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/shares.css">';
    }

    // Carregar CSS para a página de Meus Compromissos
    if (strpos($currentUri, '/meuscompromissos') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/meuscompromissos.css">';
    }
    ?>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <h1><?= APP_NAME ?></h1>
                </div>
                <ul>
                <li><a href="<?= PUBLIC_URL ?>/">Início</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="notification-item">
                            <a href="<?= PUBLIC_URL ?>/meuscompromissos?status=aguardando_aprovacao" class="notification-icon">
                                <i class="fa fa-bell"></i>
                                <?php 
                                // Buscar contagem de compromissos aguardando aprovação
                                $pendingCount = 0;
                                if (isset($notificationModel)) {
                                    $pendingCount = $notificationModel->countPendingApprovals($_SESSION['user_id']);
                                }
                                
                                if ($pendingCount > 0): 
                                ?>
                                <span class="notification-badge"><?= $pendingCount ?></span>
                                <?php else: ?>
                                <span class="notification-badge hidden">0</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li><a href="<?= PUBLIC_URL ?>/agendas">Minhas Agendas</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/meuscompromissos">Meus Compromissos</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/shares/shared">Agendas Compartilhadas</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/agendas/all">Todas as Agendas</a></li>
                    <?php else: ?>
                        <li><a href="<?= PUBLIC_URL ?>/login">Entrar</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <?php
        // Exibir mensagens de alerta (flash messages)
        if (isset($_SESSION['flash_message'])) {
            $type = $_SESSION['flash_type'] ?? 'success';
            echo '<div class="alert alert-' . $type . '">' . $_SESSION['flash_message'] . '</div>';
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
        }
        ?>