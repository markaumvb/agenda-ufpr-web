<?php
// Arquivo: app/views/agendas/create.php
?>

<div class="form-container">
    <div class="form-header">
        <h1>Nova Agenda</h1>
        <a href="<?= BASE_URL ?>/agendas" class="btn btn-link">Voltar</a>
    </div>
    
    <form action="<?= BASE_URL ?>/agendas/save" method="post">
        <div class="form-group">
            <label for="title">Título *</label>
            <input type="text" id="title" name="title" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3" class="form-control"></textarea>
        </div>
        
        <div class="form-group">
            <label for="color">Cor</label>
            <input type="color" id="color" name="color" value="#3788d8" class="form-control color-picker">
            <small class="form-text">Escolha uma cor para identificar esta agenda</small>
        </div>
        
        <div class="form-group checkbox-group">
            <label class="checkbox-container">
                <input type="checkbox" id="is_public" name="is_public" value="1">
                <span class="checkmark"></span>
                Agenda pública (visível para todos)
            </label>
            <small class="form-text">Se marcada, qualquer pessoa poderá visualizar esta agenda, mas apenas você poderá editar os compromissos.</small>
        </div>

        <div class="form-group checkbox-group">
        <label class="checkbox-container">
            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
            <span class="checkmark"></span>
            Agenda ativa
        </label>
        <small class="form-text">Se desmarcada, a agenda ficará desativada e não poderá receber novos compromissos.</small>
         </div>
            
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= BASE_URL ?>/agendas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script src="<?= PUBLIC_URL ?>/assets/js/agendas/create.js"></script>