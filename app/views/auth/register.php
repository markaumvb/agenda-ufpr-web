<?php
// Arquivo: agenda_ufpr/app/views/auth/register.php
?>

<div class="form-container">
    <h2 style="text-align: center; margin-bottom: 1.5rem;">Complete seu Cadastro</h2>
    
    <div class="alert alert-info">
        Sua autenticação via RADIUS foi bem-sucedida. Como este é seu primeiro acesso,
        precisamos de algumas informações adicionais.
    </div>
    
    <form action="<?= BASE_URL ?>/register-process" method="post">
        <div class="form-group">
            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" value="<?= $username ?? '' ?>" readonly>
            <small>Seu nome de usuário UFPR</small>
        </div>
        
        <div class="form-group">
            <label for="name">Nome Completo:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>
            <small>Preferencialmente seu e-mail institucional</small>
        </div>
        
        <div class="form-group" style="text-align: center;">
            <button type="submit" class="btn btn-primary">Concluir Cadastro</button>
        </div>
    </form>
</div>