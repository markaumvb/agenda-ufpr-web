<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 1.5rem;">Login</h2>
    
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
        
        <p style="text-align: center; margin-top: 1rem;">
            Este sistema utiliza autenticação através do RADIUS da UFPR.<br>
            Utilize suas credenciais institucionais.
        </p>
    </form>
</div>