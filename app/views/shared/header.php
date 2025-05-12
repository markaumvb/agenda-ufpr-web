<?php
// Determinar se estamos em uma página de autenticação
$current_url = $_SERVER['REQUEST_URI'];
$auth_pages = ['/login', '/register', '/login-process', '/register-process'];
$is_auth_page = false;

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
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/sidebar.css">
    
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

    if ($currentUri == '/' || $currentUri == '/agenda_ufpr/' || $currentUri == '/agenda_ufpr/index.php') {
    echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/app/assets/css/public-agendas.css">';
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
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="sidebar-item">
                            <a href="<?= PUBLIC_URL ?>/meuscompromissos?status=aguardando_aprovacao" class="sidebar-link">
                                <i class="fas fa-bell"></i>
                                <span>Notificações</span>
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