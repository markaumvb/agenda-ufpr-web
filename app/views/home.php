<?php
// Arquivo: agenda_ufpr/app/views/home.php
?>

<div class="container">
    <div class="hero-section">
        <h1>Bem-vindo ao Sistema de Agendamento UFPR</h1>
        <p>Gerencie suas agendas e compromissos de forma eficiente</p>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/login" class="btn btn-primary">Entrar</a>
            </div>
        <?php else: ?>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">Minhas Agendas</a>
                <a href="<?= BASE_URL ?>/compromissos" class="btn btn-secondary">Meus Compromissos</a>
            </div>
        <?php endif; ?>
    </div>
    
   
</div>