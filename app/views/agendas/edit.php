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

<script src="<?= PUBLIC_URL ?>/assets/js/agendas/edit.js"></script>