<div class="form-container">
    <h2>Login no Sistema de Agendamento UFPR</h2>
    <p class="form-info">Acesse com seu usuário@ufpr.br e senha para gerenciar suas agendas e compromissos.</p>
    
    <?php if (isset($_SESSION['validation_errors'])): ?>
    <div class="alert alert-danger">
        <ul class="validation-errors">
            <?php foreach ($_SESSION['validation_errors'] as $error): ?>
                <li><?= $error ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    
    <form action="<?= BASE_URL ?>/login-process" method="post" class="login-form">
        <div class="form-group">
            <label for="username">Usuário UFPR:</label>
            <input type="text" id="username" name="username" required 
                   autocomplete="username" placeholder="email@ufpr.br"
                   class="form-control <?= isset($_SESSION['error_fields']['username']) ? 'is-invalid' : '' ?>">
            <?php if (isset($_SESSION['error_fields']['username'])): ?>
                <div class="field-error"><?= $_SESSION['error_fields']['username'] ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required 
                   autocomplete="current-password"
                   class="form-control <?= isset($_SESSION['error_fields']['password']) ? 'is-invalid' : '' ?>">
            <?php if (isset($_SESSION['error_fields']['password'])): ?>
                <div class="field-error"><?= $_SESSION['error_fields']['password'] ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group form-actions">
            <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
        
        <div class="auth-info">
            <p>Apenas usuários da UFPR podem acessar ao sistema de agendamento</p>
        </div>
    </form>
</div>

<?php
// Limpar possíveis mensagens de erro específicas de campo
unset($_SESSION['error_fields']);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar parâmetros da URL
    const urlParams = new URLSearchParams(window.location.search);
    const agendaHash = urlParams.get('agenda_hash');
    const redirectTo = urlParams.get('redirect_to');
    const isPublic = urlParams.get('public');
    
    if (agendaHash && redirectTo) {
        // Adicionar campos ocultos ao formulário
        const form = document.querySelector('.login-form');
        if (form) {
            const hiddenHash = document.createElement('input');
            hiddenHash.type = 'hidden';
            hiddenHash.name = 'agenda_hash';
            hiddenHash.value = agendaHash;
            form.appendChild(hiddenHash);
            
            const hiddenRedirect = document.createElement('input');
            hiddenRedirect.type = 'hidden';
            hiddenRedirect.name = 'redirect_to';
            hiddenRedirect.value = redirectTo;
            form.appendChild(hiddenRedirect);
            
            if (isPublic) {
                const hiddenPublic = document.createElement('input');
                hiddenPublic.type = 'hidden';
                hiddenPublic.name = 'public';
                hiddenPublic.value = isPublic;
                form.appendChild(hiddenPublic);
            }
        }
    }
});
</script>