<?php
// Arquivo: app/views/compromissos/edit.php
?>

<div class="form-container">
    <div class="form-header">
        <h1>Editar Compromisso</h1>
        <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $compromisso['agenda_id'] ?>" class="btn btn-link">Voltar</a>
    </div>
    
    <?php if (!empty($compromisso['group_id'])): ?>
        <div class="alert alert-info">
            <strong>Compromisso Recorrente</strong> - Este compromisso faz parte de uma série recorrente.
        </div>
    <?php endif; ?>
    
    <form action="<?= PUBLIC_URL ?>/compromissos/update" method="post">
        <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
        
        <div class="form-group">
            <label for="title">Título *</label>
            <input type="text" id="title" name="title" required class="form-control" 
                   value="<?= htmlspecialchars($compromisso['title']) ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3" class="form-control"><?= htmlspecialchars($compromisso['description']) ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group form-group-half">
                <label for="start_datetime">Data e Hora de Início *</label>
                <input type="datetime-local" id="start_datetime" name="start_datetime" required class="form-control"
                       value="<?= htmlspecialchars($compromisso['start_datetime']) ?>">
            </div>
            
            <div class="form-group form-group-half">
                <label for="end_datetime">Data e Hora de Término *</label>
                <input type="datetime-local" id="end_datetime" name="end_datetime" required class="form-control"
                       value="<?= htmlspecialchars($compromisso['end_datetime']) ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="location">Local</label>
            <input type="text" id="location" name="location" class="form-control"
                   value="<?= htmlspecialchars($compromisso['location']) ?>">
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="pendente" <?= $compromisso['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                <option value="realizado" <?= $compromisso['status'] === 'realizado' ? 'selected' : '' ?>>Realizado</option>
                <option value="cancelado" <?= $compromisso['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                <option value="aguardando_aprovacao" <?= $compromisso['status'] === 'aguardando_aprovacao' ? 'selected' : '' ?>>Aguardando Aprovação</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Recorrência</label>
            
            <div class="radio-group">
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="none" <?= $compromisso['repeat_type'] === 'none' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Não repetir
                </label>
                
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="daily" <?= $compromisso['repeat_type'] === 'daily' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Repetir diariamente
                </label>
                
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="weekly" <?= $compromisso['repeat_type'] === 'weekly' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Repetir semanalmente
                </label>
                
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="specific_days" <?= $compromisso['repeat_type'] === 'specific_days' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Repetir em dias específicos
                </label>
            </div>
            
            <div id="repeat_until_container" class="form-group repeat-option" style="display: none;">
                <label for="repeat_until">Repetir até</label>
                <input type="date" id="repeat_until" name="repeat_until" class="form-control"
                       value="<?= $compromisso['repeat_until'] ?? '' ?>">
            </div>
            
            <div id="repeat_days_container" class="form-group repeat-option" style="display: none;">
                <label>Dias da semana</label>
                <div class="checkbox-group">
                    <?php 
                    $daysOfWeek = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                    $selectedDays = isset($repeatDays) ? $repeatDays : [];
                    
                    for ($i = 0; $i < 7; $i++): 
                    ?>
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="<?= $i ?>" <?= in_array((string)$i, $selectedDays) ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            <?= $daysOfWeek[$i] ?>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($compromisso['group_id'])): ?>
        <div class="form-group recurring-options">
            <label>Opções para eventos recorrentes</label>
            <div class="checkbox-group">
                <label class="checkbox-container">
                    <input type="checkbox" name="update_future" value="1">
                    <span class="checkmark"></span>
                    Aplicar alterações a todos os eventos futuros desta série
                </label>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $compromisso['agenda_id'] ?>" class="btn btn-secondary">Cancelar</a>
            
            <div class="form-actions-end">
                <form action="<?= PUBLIC_URL ?>/compromissos/delete" method="post" class="delete-form" onsubmit="return confirm('Tem certeza que deseja excluir este compromisso?');">
                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                    <button type="submit" class="btn btn-danger">Excluir Compromisso</button>
                </form>
                
                <?php if (!empty($compromisso['group_id'])): ?>
                <form action="<?= PUBLIC_URL ?>/compromissos/delete" method="post" class="delete-form" onsubmit="return confirm('Tem certeza que deseja excluir este e todos os compromissos futuros desta série?');">
                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                    <input type="hidden" name="delete_future" value="1">
                    <button type="submit" class="btn btn-danger">Excluir Este e Futuros</button>
                </form>
                
                <form action="<?= PUBLIC_URL ?>/compromissos/cancel-future" method="post" class="delete-form" onsubmit="return confirm('Tem certeza que deseja cancelar todos os compromissos futuros desta série?');">
                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                    <button type="submit" class="btn btn-warning">Cancelar Futuros</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script src="<?= PUBLIC_URL ?>/assets/js/compromissos/edit.js"></script>
<script>
// Script específico para eventos recorrentes
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar exibição das opções de repetição
    toggleRepeatOptions();
});
</script>