<div class="form-container">
    <div class="form-header">
        <h1>Editar Agenda</h1>
        <a href="<?= BASE_URL ?>/agendas" class="btn btn-link">Voltar</a>
    </div>
    
    <form action="<?= BASE_URL ?>/agendas/update" method="post">
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
            <input type="color" id="color" name="color" class="color-picker" 
                value="<?= htmlspecialchars($agenda['color']) ?>">
            <small class="form-text">Escolha uma cor para identificar esta agenda</small>
        </div>
        
        <div class="form-group">
            <label for="min_time_before">Antecedência mínima para compromissos</label>
            <select id="min_time_before" name="min_time_before" class="form-control">
                <option value="0" <?= $agenda['min_time_before'] == 0 ? 'selected' : '' ?>>Sem antecedência mínima</option>
                <option value="12" <?= $agenda['min_time_before'] == 12 ? 'selected' : '' ?>>12 horas</option>
                <option value="24" <?= $agenda['min_time_before'] == 24 ? 'selected' : '' ?>>24 horas</option>
                <option value="36" <?= $agenda['min_time_before'] == 36 ? 'selected' : '' ?>>36 horas</option>
                <option value="48" <?= $agenda['min_time_before'] == 48 ? 'selected' : '' ?>>48 horas</option>
            </select>
            <small class="form-text">Define quanto tempo de antecedência é necessário para criar compromissos nesta agenda</small>
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

        <div class="form-group checkbox-group">
            <label class="checkbox-container">
                <input type="checkbox" id="is_active" name="is_active" value="1" 
                    <?= $agenda['is_active'] ? 'checked' : '' ?>>
                <span class="checkmark"></span>
                Agenda ativa
            </label>
            <small class="form-text">Se desmarcada, a agenda ficará desativada e não poderá receber novos compromissos.</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="<?= BASE_URL ?>/agendas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?= PUBLIC_URL ?>/app/assets/js/agenda/form.js"></script>