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
    
    <form action="<?= PUBLIC_URL ?>/compromissos/update" method="post" id="compromisso-form" novalidate>

        <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
        <input type="hidden" name="agenda_id" value="<?= $compromisso['agenda_id'] ?>">
        
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
                <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-control">
            </div>
            
            <div class="form-group form-group-half">
                <label for="end_datetime">Data e Hora de Término *</label>
                <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-control">
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
            <!-- BOTÕES PRINCIPAIS -->
            <div class="action-group primary-actions">
                <!-- BOTÃO 1: SALVAR ALTERAÇÕES -->
                <button type="submit" class="btn btn-action btn-primary">
                    <i class="icon-save"></i>
                    <span>Salvar Alterações</span>
                </button>
                
                <!-- BOTÃO 2: CANCELAR -->
                <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $compromisso['agenda_id'] ?>" class="btn btn-action btn-secondary">

                    <i class="icon-cancel"></i>
                    <span>Cancelar</span>
                </a>
            </div>
            
            <!-- BOTÕES SECUNDÁRIOS (apenas se status for pendente) -->
            <?php if ($compromisso['status'] === 'pendente'): ?>
            <div class="action-group secondary-actions">
                <!-- BOTÃO 3: EXCLUIR COMPROMISSO -->
                <form action="<?= PUBLIC_URL ?>/compromissos/delete" method="post" onsubmit="console.log('Formulário de delete sendo submetido', this); return confirm('Tem certeza que deseja excluir este compromisso?');">

                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                    <button type="submit" class="btn btn-action btn-danger">
                        <i class="icon-trash"></i>
                        <span>Excluir Compromisso</span>
                    </button>
                </form>
                
                <!-- BOTÕES PARA EVENTOS RECORRENTES -->
                <?php if (!empty($compromisso['group_id'])): ?>
                    <!-- BOTÃO 4: EXCLUIR ESTE E FUTUROS -->
                    <form action="<?= PUBLIC_URL ?>/compromissos/delete" method="post" onsubmit="return confirm('Tem certeza que deseja excluir este e todos os compromissos futuros desta série?');">
                        <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                        <input type="hidden" name="delete_future" value="1">
                        <button type="submit" class="btn btn-action btn-danger">
                            <i class="icon-trash-all"></i>
                            <span>Excluir Este e Futuros</span>
                        </button>
                    </form>
                    
                    <!-- BOTÃO 5: CANCELAR TODOS OS COMPROMISSOS -->
                    <form action="<?= PUBLIC_URL ?>/compromissos/cancel-future" method="post" onsubmit="return confirm('Tem certeza que deseja cancelar todos os compromissos desta série (incluindo o atual)?');">
                        <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                        <button type="submit" class="btn btn-action btn-warning">
                            <i class="icon-ban"></i>
                            <span>Cancelar Todos os Compromissos</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/form.js"></script>
<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/validation.js"></script>

<script>
// Função que mostra/esconde as opções de recorrência
function toggleRepeatOptions() {
    const repeatType = document.querySelector('input[name="repeat_type"]:checked').value;
    const repeatUntilContainer = document.getElementById('repeat_until_container');
    const repeatDaysContainer = document.getElementById('repeat_days_container');
    
    // Mostrar/esconder container de "repetir até"
    if (repeatType === 'none') {
        repeatUntilContainer.style.display = 'none';
        repeatDaysContainer.style.display = 'none';
    } else {
        repeatUntilContainer.style.display = 'block';
        
        // Mostrar/esconder container de dias específicos
        if (repeatType === 'specific_days') {
            repeatDaysContainer.style.display = 'block';
        } else {
            repeatDaysContainer.style.display = 'none';
        }
    }
}

// Inicializar as opções de recorrência
document.addEventListener('DOMContentLoaded', function() {
    toggleRepeatOptions();
    
    // Prevenir conflitos apenas em formulários de delete
    const deleteForms = document.querySelectorAll('form[action*="/delete"]');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Tem certeza que deseja excluir?')) {
                e.preventDefault();
            }
        });
    });
    
    // Links de cancelar NÃO devem ter confirmação
    const cancelLinks = document.querySelectorAll('a.btn-secondary[href*="compromissos?agenda_id"]');
    cancelLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Não fazer nada - deixar navegação normal
        });
    });
});
</script>