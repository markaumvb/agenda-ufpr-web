<?php
// Determinar se estamos em uma página de autenticação
$current_url = $_SERVER['REQUEST_URI'];
$auth_pages = ['/login', '/register', '/login-process', '/register-process', '/timeline']; 
$is_auth_page = false;

if (isset($_SESSION['user_id']) && !isset($notificationModel)) {
    require_once __DIR__ . '/../../models/Database.php';
    require_once __DIR__ . '/../../models/Notification.php';
    $notificationModel = new Notification();
}

foreach ($auth_pages as $page) {
    if (strpos($current_url, $page) !== false) {
        $is_auth_page = true;
        break;
    }
}

// Adicionar classe específica ao body para páginas de autenticação
$body_class = $is_auth_page ? 'auth-page' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Language" content="pt-BR">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- FullCalendar CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js"></script>
    <-- FlatPicker ->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


    <title><?= APP_NAME ?></title>

    <!-- CSS Base - Carregado em todas as páginas -->
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/component.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/sidebar.css">

    <?php 
    // Identificar a página atual
    $currentUri = $_SERVER['REQUEST_URI'];
    
    // CORRIGIDO: Carregamento condicional de CSS específicos
    
    // CSS para páginas de agendas (incluindo home e all)
    if (strpos($currentUri, '/agendas') !== false || 
        $currentUri == '/' || 
        $currentUri == '/agenda_ufpr/' || 
        $currentUri == '/agenda_ufpr/index.php' || 
        strpos($currentUri, '/?') !== false || 
        strpos($currentUri, '/agenda_ufpr/?') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/agendas.css">';
    }
    
    // CSS para compromissos
    if (strpos($currentUri, '/compromissos') !== false || strpos($currentUri, '/meuscompromissos') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/compromissos.css">';
    }
    
    // CSS para compartilhamento
    if (strpos($currentUri, '/shares') !== false || strpos($currentUri, '/public-agenda') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/shares.css">';
    }
    
    // CSS para notificações
    if (strpos($currentUri, '/notifications') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/notifications.css">';
    }
    
    // CSS para autenticação
    if (strpos($currentUri, '/login') !== false || strpos($currentUri, '/register') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/auth.css">';
    }
    
    // CSS para timeline
    if (strpos($currentUri, '/timeline') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/modules/timeline.css">';
    }
    ?>
    
    <!-- CSS para eventos do calendário -->
    <style>
    /* Estilos para eventos do calendário - sempre incluídos */
    .fc-event {
        border: none;
        border-radius: var(--border-radius);
        padding: 2px 4px;
    }

    .fc-event-main {
        padding: 2px;
    }

    /* Status específicos */
    .fc-event.pendente, 
    .fc-event[data-status="pendente"] {
        background-color: var(--warning-color) !important;
        border-color: var(--warning-color) !important;
    }

    .fc-event.realizado, 
    .fc-event[data-status="realizado"] {
        background-color: var(--success-color) !important;
        border-color: var(--success-color) !important;
    }

    .fc-event.cancelado, 
    .fc-event[data-status="cancelado"] {
        background-color: var(--danger-color) !important;
        border-color: var(--danger-color) !important;
        text-decoration: line-through;
    }

    .fc-event.aguardando_aprovacao, 
    .fc-event[data-status="aguardando_aprovacao"] {
        background-color: var(--info-color) !important;
        border-color: var(--info-color) !important;
    }
    </style>
    
    <!-- Bootstrap e jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

</head>
<body class="<?php echo $body_class; ?>">
    <?php if (!$is_auth_page): ?>
    <!-- Layout com sidebar apenas para páginas que não sejam de autenticação -->
    <div class="layout-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-logo"><?= APP_NAME ?></h1>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-content">
                <ul class="sidebar-menu">
                    <li class="sidebar-item">
                        <a href="<?= PUBLIC_URL ?>/" class="sidebar-link">
                            <i class="fas fa-home"></i>
                            <span>Início</span>
                        </a>
                    </li>
                    
                    <!-- Link para Timeline (acessível para todos) -->
                    <li class="sidebar-item">
                        <a href="<?= PUBLIC_URL ?>/timeline" class="sidebar-link">
                            <i class="fas fa-stream"></i>
                            <span>Linha do Tempo</span>
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="sidebar-item">
                        <a href="<?= PUBLIC_URL ?>/meuscompromissos?status=aguardando_aprovacao" class="sidebar-link">
                            <i class="fas fa-clock"></i>
                            <span>Aprovações Pendentes</span>
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

                    <!-- Adicionar o novo item de notificações -->
                    <li class="sidebar-item">
                        <a href="<?= PUBLIC_URL ?>/notifications" class="sidebar-link">
                            <i class="fas fa-bell"></i>
                            <span>Notificações</span>
                            <?php 
                            // Buscar contagem de notificações não lidas
                            $unreadCount = 0;
                            if (isset($notificationModel)) {
                                $unreadCount = $notificationModel->countByUser($_SESSION['user_id'], true);
                            }
                            
                            if ($unreadCount > 0): 
                            ?>
                            <span class="notification-badge"><?= $unreadCount ?></span>
                            <?php else: ?>
                            <span class="notification-badge hidden">0</span>
                            <?php endif; ?>
                        </a>
                    </li>
                        
                        <li class="sidebar-item">
                            <a href="<?= PUBLIC_URL ?>/agendas" class="sidebar-link">
                                <i class="fas fa-calendar"></i>
                                <span>Minhas Agendas</span>
                            </a>
                        </li>
                        
                        <li class="sidebar-item">
                            <a href="<?= PUBLIC_URL ?>/meuscompromissos" class="sidebar-link">
                                <i class="fas fa-tasks"></i>
                                <span>Meus Compromissos</span>
                            </a>
                        </li>
                        
                        <li class="sidebar-item">
                            <a href="<?= PUBLIC_URL ?>/shares/shared" class="sidebar-link">
                                <i class="fas fa-share-alt"></i>
                                <span>Agendas Compartilhadas</span>
                            </a>
                        </li>
                        
                        <li class="sidebar-item">
                            <a href="<?= PUBLIC_URL ?>/agendas/all" class="sidebar-link">
                                <i class="fas fa-globe"></i>
                                <span>Todas as Agendas</span>
                            </a>
                        </li>
                        
                        <li class="sidebar-item sidebar-bottom">
                            <a href="<?= PUBLIC_URL ?>/logout" class="sidebar-link">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Sair</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="sidebar-item">
                            <a href="<?= PUBLIC_URL ?>/login" class="sidebar-link">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Entrar</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>
        
        <!-- Adicionar overlay para dispositivos móveis -->
        <div class="sidebar-overlay"></div>
        
        <!-- Botão de menu para dispositivos móveis -->
        <button class="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Conteúdo principal -->
        <main class="main-content">
    <?php else: ?>
        <!-- Layout simplificado sem sidebar para páginas de autenticação -->
        <main class="main-content full-width">
    <?php endif; ?>
            <div class="container">
                <?php
                // Exibir mensagens de alerta (flash messages)
                if (isset($_SESSION['flash_message'])) {
                    $type = $_SESSION['flash_type'] ?? 'success';
                    echo '<div class="alert alert-' . $type . '">' . $_SESSION['flash_message'] . '</div>';
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                }
                ?>