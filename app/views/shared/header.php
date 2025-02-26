<?php
// Arquivo: app/views/shared/header.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/assets/css/style.css">
    
    <!-- CSS específicos por módulo -->
    <?php 
    // Identificar a página atual para carregar apenas o CSS necessário
    $currentUri = $_SERVER['REQUEST_URI'];
    
    // Carregar CSS para agendas
// No app/views/shared/header.php, dentro da seção que carrega CSS específicos
        if (strpos($currentUri, '/agendas') !== false) {
            echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/assets/css/agendas.css">';
        }
    
    // Carregar CSS para compromissos
    if (strpos($currentUri, '/compromissos') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/assets/css/compromissos.css">';
    }
    
    // Carregar CSS para compartilhamentos
    if (strpos($currentUri, '/shares') !== false || strpos($currentUri, '/public-agenda') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/assets/css/shares.css">';
    }
    
    // Carregar CSS para formulários (criar nova agenda, editar compromisso, etc.)
    if (strpos($currentUri, '/new') !== false || 
        strpos($currentUri, '/edit') !== false || 
        strpos($currentUri, '/create') !== false) {
        echo '<link rel="stylesheet" href="' . PUBLIC_URL . '/assets/css/forms.css">';
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
                        <li><a href="<?= PUBLIC_URL ?>/agendas">Minhas Agendas</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/shares/shared">Agendas Compartilhadas</a></li>
                        <li><a href="<?= PUBLIC_URL ?>/logout">Sair</a></li>
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