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

    <!-- CSS Principal - Mantenha esses sempre carregados para o layout básico funcionar -->
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/component.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/approval-modal.css">
    
    <!-- CSS específicos por módulo - SIMPLIFICADO -->
    <?php 
    // Identificar a página atual
    $currentUri = $_SERVER['REQUEST_URI'];
    
    // Páginas de compromissos e formulários
    if (strpos($currentUri, '/compromissos') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/compromissos.css">';
        
        // Páginas de formulário recebem estilo de formulário
        if (strpos($currentUri, '/new') !== false || strpos($currentUri, '/edit') !== false) {
            echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/forms.css">';
        }
    }
    
    // Carrega CSS de formulário para todas essas páginas
    if (strpos($currentUri, '/new') !== false || 
        strpos($currentUri, '/edit') !== false || 
        strpos($currentUri, '/create') !== false || 
        strpos($currentUri, '/login') !== false || 
        strpos($currentUri, '/register') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/forms.css">';
    }
    
    // Autenticação
    if (strpos($currentUri, '/login') !== false || strpos($currentUri, '/register') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/auth/login.css">';
    }
    
    // Agendas
    if (strpos($currentUri, '/agendas') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/agendas.css">';
    }
    
    // Compartilhamentos
    if (strpos($currentUri, '/shares') !== false || strpos($currentUri, '/public-agenda') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/shares.css">';
    }
    
    // Meus Compromissos
    if (strpos($currentUri, '/meuscompromissos') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/meuscompromissos.css">';
    }
    
    // Página com parâmetro public=1
    if (strpos($currentUri, 'public=1') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/forms.css">';
    }
    ?>
    
    <!-- CSS para eventos do calendário -->
    <style>
    /* Estilos para eventos do calendário - sempre incluídos */
    .fc-event {
        border: none;
        border-radius: 4px;
        padding: 2px 4px;
    }

    .fc-event-main {
        padding: 2px;
    }

    /* Status específicos */
    .fc-event.pendente, 
    .fc-event[data-status="pendente"] {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
    }

    .fc-event.realizado, 
    .fc-event[data-status="realizado"] {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }

    .fc-event.cancelado, 
    .fc-event[data-status="cancelado"] {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        text-decoration: line-through;
    }

    .fc-event.aguardando_aprovacao, 
    .fc-event[data-status="aguardando_aprovacao"] {
        background-color: #17a2b8 !important;
        border-color: #17a2b8 !important;
    }
    </style>
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