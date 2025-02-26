<?php
// Arquivo: app/views/auth/login.php
?>

<div class="form-container">
    <h2>Login</h2>
    
    <form action="<?= BASE_URL ?>/login-process" method="post">
        <div class="form-group">
            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" required autocomplete="username">
        </div>
        
        <div class="form-group">
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        
        <div class="form-group" style="text-align: center;">
            <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
        
        <div class="form-info">
            Este sistema utiliza autenticação através do RADIUS da UFPR.<br>
            Utilize suas credenciais institucionais.
        </div>
    </form>
</div>