<?php
// Arquivo: app/views/agendas/edit.php
?>

<div class="form-container">
    <div class="form-header">
        <h1>Editar Agenda</h1>
        <a href="<?= BASE_URL ?>/public/agendas" class="btn btn-link">Voltar</a>
    </div>
    
    <form action="<?= BASE_URL ?>/public/agendas/update" method="post">
        <input type="hidden" name="id" value="<?= $agenda['id'] ?>">
        
        <div class="form-group">
            <label for="title">Título *</label>
            <input type="text" id="title" name="title" required class="form-control" 
                   value="<?= htmlspecialchars($agenda['title']) ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3" class="form-control"><?= htmlspecialchars($agenda['description']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="color">Cor</label>
            <input type="color" id="color" name="color" class="form-control color-picker" 
                   value="<?= htmlspecialchars($agenda['color']) ?>">
            <small class="form-text">Escolha uma cor para identificar esta agenda</small>
        </div>
        
        <div class="form-group checkbox-group">
            <label class="checkbox-container">
                <input type="checkbox" id="is_public" name="is_public" value="1" 
                       <?= $agenda['is_public'] ? 'checked' : '' ?>>
                <span class="checkmark"></span>
                Agenda pública (visível para todos)
            </label>
            <small class="form-text">Se marcada, qualquer pessoa poderá visualizar esta agenda, mas apenas você poderá editar os compromissos.</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="<?= BASE_URL ?>/public/agendas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
    /* Estilos para o formulário */
    .form-container {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 1rem;
    }
    
    .form-header h1 {
        margin: 0;
        font-size: 1.5rem;
        color: #004a8f;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    
    .color-picker {
        height: 40px;
        padding: 5px;
        width: 100px;
    }
    
    .form-text {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .checkbox-group {
        margin-top: 1.5rem;
    }
    
    .checkbox-container {
        display: block;
        position: relative;
        padding-left: 30px;
        margin-bottom: 12px;
        cursor: pointer;
        font-weight: normal;
    }
    
    .checkbox-container input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    
    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 20px;
        width: 20px;
        background-color: #eee;
        border-radius: 4px;
    }
    
    .checkbox-container:hover input ~ .checkmark {
        background-color: #ccc;
    }
    
    .checkbox-container input:checked ~ .checkmark {
        background-color: #004a8f;
    }
    
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }
    
    .checkbox-container input:checked ~ .checkmark:after {
        display: block;
    }
    
    .checkbox-container .checkmark:after {
        left: 7px;
        top: 3px;
        width: 6px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 2rem;
        border-top: 1px solid #eee;
        padding-top: 1rem;
    }
</style>