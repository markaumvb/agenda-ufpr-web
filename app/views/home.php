<div class="container">
    <div class="hero-section">
        <h1>Bem-vindo ao Sistema de Agendamento UFPR</h1>



        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/login" class="btn btn-primary">Entrar</a>
            </div>
        <?php else: ?>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">Minhas Agendas</a>
                <a href="<?= BASE_URL ?>/meuscompromissos" class="btn btn-secondary">Meus Compromissos</a>
                </div>
        <?php endif; ?>
                <h3>Consulta de Agendas públicas</h3>
    </div>
    
   
</div>