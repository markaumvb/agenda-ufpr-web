<?php
// Arquivo: app/views/auth/register.php
?>

<div class="form-container">
    <h2>Complete seu Cadastro</h2>
    
    <div class="alert alert-info">
        <p><strong>Primeiro acesso detectado!</strong></p>
        <p>Sua autenticação via RADIUS foi bem-sucedida. Como este é seu primeiro acesso no Sistema de Agendamento UFPR, 
        precisamos de algumas informações adicionais para completar seu cadastro.</p>
    </div>
    
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
    
    <form action="<?= BASE_URL ?>/register-process" method="post" class="register-form">
        <div class="form-group">
            <label for="username">Usuário UFPR:</label>
            <input type="text" id="username" name="username" 
                   value="<?= htmlspecialchars($username ?? '') ?>" 
                   readonly class="form-control readonly">
            <small class="form-text">Seu login institucional UFPR</small>
        </div>
        
        <div class="form-group">
            <label for="name">Nome Completo:</label>
            <input type="text" id="name" name="name" required 
                   value="<?= htmlspecialchars($_SESSION['form_data']['name'] ?? '') ?>"
                   class="form-control <?= isset($_SESSION['error_fields']['name']) ? 'is-invalid' : '' ?>">
            <?php if (isset($_SESSION['error_fields']['name'])): ?>
                <div class="field-error"><?= $_SESSION['error_fields']['name'] ?></div>
            <?php endif; ?>
        </div>
                
        <div class="form-group form-actions">
            <button type="submit" class="btn btn-primary">Concluir Cadastro</button>
        </div>
    </form>
</div>

<?php
// Limpar possíveis dados de formulário e erros específicos de campo
unset($_SESSION['error_fields']);
unset($_SESSION['form_data']);
?>