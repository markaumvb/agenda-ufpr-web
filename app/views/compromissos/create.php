<div class="form-container" data-min-time-before="<?= $agenda['min_time_before'] ?? 0 ?>">
    <div class="form-header">
        <h1><i class="fas fa-calendar-plus"></i> Novo Compromisso</h1>
        <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agendaId ?>" class="btn btn-link">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <?php if (isset($agenda['min_time_before']) && $agenda['min_time_before'] > 0): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Atenção:</strong> Esta agenda requer <?= $agenda['min_time_before'] ?> horas de antecedência para criação de compromissos.
    </div>
    <?php endif; ?>
    
    <form action="<?= PUBLIC_URL ?>/compromissos/save" method="post" id="compromisso-form" class="compromisso-form" novalidate>
        <input type="hidden" name="agenda_id" value="<?= $agendaId ?>">

        <div id="error-container" class="alert alert-danger" style="display: <?= !empty($errors) ? 'block' : 'none' ?>;">
            <i class="fas fa-exclamation-triangle"></i>
            <ul id="error-list">
                <?php if (!empty($errors)): ?>
                    <?php foreach($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-edit"></i> Informações Básicas
            </h3>
            
            <div class="form-group">
                <label for="title">
                    <i class="fas fa-heading"></i> Título *
                </label>
                <input type="text" id="title" name="title" required class="form-control" 
                       placeholder="Digite o título do compromisso">
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i> Descrição
                </label>
                <textarea id="description" name="description" rows="4" class="form-control" 
                          placeholder="Descreva os detalhes do compromisso (opcional)"></textarea>
            </div>
            
            <div class="form-group">
                <label for="location">
                    <i class="fas fa-map-marker-alt"></i> Local
                </label>
                <input type="text" id="location" name="location" class="form-control" 
                       placeholder="Onde será realizado o compromisso (opcional)">
            </div>
        </div>

        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-clock"></i> Data e Horário
            </h3>
            
            <div class="form-row">
                <div class="form-group form-group-half">
                    <label for="start_datetime">
                        <i class="fas fa-play"></i> Data e Hora de Início *
                    </label>
                    
                    <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-control" 
                        value="<?= isset($formData['start_datetime']) ? htmlspecialchars($formData['start_datetime']) : $defaultStartDateTime ?>">
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i>
                        A data deve ser futura 
                        <?php if (isset($agenda['min_time_before']) && $agenda['min_time_before'] > 0): ?>
                        e ter pelo menos <?= $agenda['min_time_before'] ?> horas de antecedência
                        <?php endif; ?>
                    </small>
                </div>
                
                <div class="form-group form-group-half">
                    <label for="end_datetime">
                        <i class="fas fa-stop"></i> Data e Hora de Término *
                    </label>
                    <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-control" 
                        value="<?= isset($formData['end_datetime']) ? htmlspecialchars($formData['end_datetime']) : $defaultEndDateTime ?>">
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i>
                        Deve ser posterior ao horário de início
                    </small>
                </div>
            </div>
        </div>

        <?php
            $isFromPublic = isset($_GET['public']) || isset($_POST['public']);
        ?>

        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-flag"></i> Status
            </h3>
            
            <div class="form-group">
                <label for="status-display">Status do Compromisso</label>
                <?php if ($isFromPublic): ?>
                    <div class="status-display status-warning">
                        <i class="fas fa-clock"></i>
                        <span>Aguardando Aprovação</span>
                        <small>Seu compromisso será analisado pelo responsável da agenda</small>
                    </div>
                    <!-- Não definimos o status aqui para permitir que a lógica do controller o faça -->
                <?php else: ?>
                    <div class="status-display status-pending">
                        <i class="fas fa-hourglass-start"></i>
                        <span>Pendente</span>
                        <small>O compromisso será criado com status pendente</small>
                    </div>
                    <input type="hidden" name="status" value="pendente">
                <?php endif; ?>
            </div>

            <?php if ($isFromPublic): ?>
                <input type="hidden" name="public" value="1">
            <?php endif; ?>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-redo"></i> Recorrência
            </h3>
            
            <div class="form-group">
                <label>Tipo de Recorrência</label>
                
                <div class="radio-group">
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="none" checked onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Não repetir</strong>
                            <small>Compromisso único</small>
                        </div>
                    </label>
                    
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="daily" onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Repetir diariamente</strong>
                            <small>Todos os dias úteis</small>
                        </div>
                    </label>
                    
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="weekly" onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Repetir semanalmente</strong>
                            <small>Mesmo dia da semana</small>
                        </div>
                    </label>
                    
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="specific_days" onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Repetir em dias específicos</strong>
                            <small>Escolha os dias da semana</small>
                        </div>
                    </label>
                </div>
                
                <div id="repeat_until_container" class="form-group repeat-option" style="display: none;">
                    <label for="repeat_until">
                        <i class="fas fa-calendar-times"></i> Repetir até
                    </label>
                    <input type="date" id="repeat_until" name="repeat_until" class="form-control">
                    <small class="form-text text-muted">Data final da recorrência</small>
                </div>
                
                <div id="repeat_days_container" class="form-group repeat-option" style="display: none;">
                    <label>
                        <i class="fas fa-calendar-week"></i> Dias da semana
                    </label>
                    <div class="checkbox-group days-grid">
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="0">
                            <span class="checkmark"></span>
                            <span class="day-label">Dom</span>
                        </label>
                        
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="1">
                            <span class="checkmark"></span>
                            <span class="day-label">Seg</span>
                        </label>
                        
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="2">
                            <span class="checkmark"></span>
                            <span class="day-label">Ter</span>
                        </label>
                        
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="3">
                            <span class="checkmark"></span>
                            <span class="day-label">Qua</span>
                        </label>
                        
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="4">
                            <span class="checkmark"></span>
                            <span class="day-label">Qui</span>
                        </label>
                        
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="5">
                            <span class="checkmark"></span>
                            <span class="day-label">Sex</span>
                        </label>
                        
                        <label class="checkbox-container">
                            <input type="checkbox" name="repeat_days[]" value="6">
                            <span class="checkmark"></span>
                            <span class="day-label">Sáb</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <div class="action-group primary-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i>
                    <span>Salvar Compromisso</span>
                </button>
                
                <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agendaId ?>" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i>
                    <span>Cancelar</span>
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Script para funções de formulário -->
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
});
</script>
<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/form.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startInput = document.getElementById('start_datetime');
    const endInput = document.getElementById('end_datetime');
    
    if (startInput && endInput) {
        // Função para sincronizar data final quando data inicial muda
        startInput.addEventListener('change', function() {
            if (!startInput.value) return;
            
            try {
                const startDate = new Date(startInput.value);
                
                // Se campo final estiver vazio OU for menor que início, sincronizar
                if (!endInput.value || new Date(endInput.value) <= startDate) {
                    const endDate = new Date(startDate);
                    endDate.setHours(endDate.getHours() + 1); // +1 hora
                    
                    // Formatar para datetime-local
                    const year = endDate.getFullYear();
                    const month = String(endDate.getMonth() + 1).padStart(2, '0');
                    const day = String(endDate.getDate()).padStart(2, '0');
                    const hours = String(endDate.getHours()).padStart(2, '0');
                    const minutes = String(endDate.getMinutes()).padStart(2, '0');
                    
                    endInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                }
                
                // Atualizar mínimo do campo final
                endInput.min = startInput.value;
                
            } catch (e) {
                console.error('Erro ao sincronizar datas:', e);
            }
        });
        
        // Validação em tempo real
        function validateDates() {
            if (startInput.value && endInput.value) {
                const start = new Date(startInput.value);
                const end = new Date(endInput.value);
                
                if (end <= start) {
                    endInput.setCustomValidity('A data de término deve ser posterior à data de início');
                } else {
                    endInput.setCustomValidity('');
                }
            }
        }
        
        startInput.addEventListener('change', validateDates);
        endInput.addEventListener('change', validateDates);
        
        // Disparar evento inicial para sincronizar se necessário
        if (startInput.value) {
            startInput.dispatchEvent(new Event('change'));
        }
    }
});
</script>