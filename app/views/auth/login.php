<?php
// Arquivo: app/views/auth/login.php
?>

<div class="form-container">
    <h2>Login</h2>
    <p>Somente pessoas com e-mail da @ufpr.br podem acessar ao sistema</p><br>
    
    <form action="<?= BASE_URL ?>/login-process" method="post">
        <div class="form-group">
            <label for="username">Usuário (usuário da UFPR):</label>
            <input type="text" id="username" name="username" required autocomplete="username" placeholder="usuario@ufpr.br">
        </div>
        
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        
        <div class="form-group" style="text-align: center;">
            <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
        
    </form>
</div>