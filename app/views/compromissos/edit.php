<?php
// Buscar dados da agenda se não existir
if (!isset($agenda)) {
    $agenda = $this->agendaModel->getById($compromisso['agenda_id']);
}
?>

<div class="form-container" data-min-time-before="<?= $agenda['min_time_before'] ?? 0 ?>">
    <div class="form-header">
        <h1><i class="fas fa-edit"></i> Editar Compromisso</h1>
        <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $compromisso['agenda_id'] ?>" class="btn btn-link">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    
    <?php if (!empty($compromisso['group_id'])): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Compromisso Recorrente</strong> - Este compromisso faz parte de uma série recorrente.
        </div>
    <?php endif; ?>
    
    <!-- FORMULÁRIO PRINCIPAL DE EDIÇÃO -->
    <form action="<?= PUBLIC_URL ?>/compromissos/update" method="post" id="compromisso-form" class="compromisso-form" novalidate>
        <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
        <input type="hidden" name="agenda_id" value="<?= $compromisso['agenda_id'] ?>">
        
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-edit"></i> Informações Básicas
            </h3>
            
            <div class="form-group">
                <label for="title">
                    <i class="fas fa-heading"></i> Título *
                </label>
                <input type="text" id="title" name="title" required class="form-control" 
                       placeholder="Digite o título do compromisso"
                       value="<?= htmlspecialchars($compromisso['title']) ?>">
            </div>
            
            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i> Descrição
                </label>
                <textarea id="description" name="description" rows="3" class="form-control" 
                          placeholder="Descreva os detalhes do compromisso (opcional)"><?= htmlspecialchars($compromisso['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="location">
                    <i class="fas fa-map-marker-alt"></i> Local
                </label>
                <input type="text" id="location" name="location" class="form-control" 
                       placeholder="Onde será realizado o compromisso (opcional)"
                       value="<?= htmlspecialchars($compromisso['location']) ?>">
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-clock"></i> Data e Horário
            </h3>
            
            <div class="form-row-datetime">
                <div class="form-group form-group-datetime">
                    <label for="start_datetime">
                        <i class="fas fa-play"></i> Data e Hora de Início *
                    </label>
                    <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-control"
                        value="<?= htmlspecialchars($compromisso['start_datetime']) ?>">
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i>
                        A data deve ser futura
                    </small>
                </div>
                
                <div class="form-group form-group-datetime">
                    <label for="end_datetime">
                        <i class="fas fa-stop"></i> Data e Hora de Término *
                    </label>
                    <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-control"
                        value="<?= htmlspecialchars($compromisso['end_datetime']) ?>">
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i>
                        Deve ser posterior ao horário de início
                    </small>
                </div>
                
                <div class="form-group form-group-duration">
                    <div id="duration-card" class="duration-card">
                        <div class="duration-header">
                            <i class="fas fa-hourglass-half"></i>
                            <span>Duração</span>
                        </div>
                        <div id="duration-value" class="duration-value">1h 00min</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-flag"></i> Status
            </h3>
            
            <div class="form-group">
                <label for="status">
                    <i class="fas fa-flag"></i> Status do Compromisso
                </label>
                <select id="status" name="status" class="form-control">
                    <option value="pendente" <?= $compromisso['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    <option value="realizado" <?= $compromisso['status'] === 'realizado' ? 'selected' : '' ?>>Realizado</option>
                    <option value="cancelado" <?= $compromisso['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    <option value="aguardando_aprovacao" <?= $compromisso['status'] === 'aguardando_aprovacao' ? 'selected' : '' ?>>Aguardando Aprovação</option>
                </select>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-redo"></i> Recorrência
            </h3>
            
            <div class="form-group">
                <label>Tipo de Recorrência</label>
                
                <div class="radio-group">
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="none" <?= $compromisso['repeat_type'] === 'none' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Não repetir</strong>
                            <small>Compromisso único</small>
                        </div>
                    </label>
                    
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="daily" <?= $compromisso['repeat_type'] === 'daily' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Repetir diariamente</strong>
                            <small>Todos os dias úteis (máx. 12h duração)</small>
                        </div>
                    </label>
                    
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="weekly" <?= $compromisso['repeat_type'] === 'weekly' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Repetir semanalmente</strong>
                            <small>Mesmo dia da semana (máx. 12h duração)</small>
                        </div>
                    </label>
                    
                    <label class="radio-container">
                        <input type="radio" name="repeat_type" value="specific_days" <?= $compromisso['repeat_type'] === 'specific_days' ? 'checked' : '' ?> onchange="toggleRepeatOptions()">
                        <span class="radiomark"></span>
                        <div class="radio-content">
                            <strong>Repetir em dias específicos</strong>
                            <small>Escolha os dias da semana (máx. 12h duração)</small>
                        </div>
                    </label>
                </div>
                
                <div id="recurrence-warning" class="alert alert-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Atenção:</strong> Compromissos com recorrência não podem ter duração superior a 12 horas.
                </div>
                
                <div id="repeat_until_container" class="form-group repeat-option" style="display: none;">
                    <label for="repeat_until">
                        <i class="fas fa-calendar-times"></i> Repetir até
                    </label>
                    <input type="date" id="repeat_until" name="repeat_until" class="form-control"
                           value="<?= $compromisso['repeat_until'] ?? '' ?>">
                    <small class="form-text text-muted">Data final da recorrência</small>
                </div>
                
                <div id="repeat_days_container" class="form-group repeat-option" style="display: none;">
                    <label>
                        <i class="fas fa-calendar-week"></i> Dias da semana
                    </label>
                    <div class="checkbox-group days-grid">
                        <?php 
                        $daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                        $selectedDays = isset($repeatDays) ? $repeatDays : [];
                        
                        for ($i = 0; $i < 7; $i++): 
                        ?>
                            <label class="checkbox-container">
                                <input type="checkbox" name="repeat_days[]" value="<?= $i ?>" 
                                       <?= in_array((string)$i, $selectedDays) ? 'checked' : '' ?>>
                                <span class="checkmark"></span>
                                <span class="day-label"><?= $daysOfWeek[$i] ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($compromisso['group_id'])): ?>
        <div class="form-section">
            <h3 class="section-title">
                <i class="fas fa-cogs"></i> Opções Avançadas
            </h3>
            
            <div class="form-group">
                <label>Opções para eventos recorrentes</label>
                <div class="checkbox-group">
                    <label class="checkbox-container">
                        <input type="checkbox" name="update_future" value="1">
                        <span class="checkmark"></span>
                        <span class="day-label">Aplicar alterações a todos os eventos futuros desta série</span>
                    </label>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- BOTÕES PRINCIPAIS (DENTRO DO FORMULÁRIO) -->
        <div class="form-actions">
            <div class="action-group primary-actions">
                <button type="submit" class="btn btn-action btn-primary">
                    <i class="fas fa-save"></i>
                    <span>Salvar Alterações</span>
                </button>
                
                <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $compromisso['agenda_id'] ?>" class="btn btn-action btn-secondary">
                    <i class="fas fa-times"></i>
                    <span>Cancelar</span>
                </a>
            </div>
        </div>
    </form>
    
    <!-- ===== FORMULÁRIOS DE EXCLUSÃO (FORA DO FORMULÁRIO PRINCIPAL) ===== -->
    <?php if (in_array($compromisso['status'], ['pendente', 'aguardando_aprovacao'])): ?>
    <div class="delete-actions-section">
        <h3 class="section-title">
            <i class="fas fa-exclamation-triangle text-danger"></i> Ações de Exclusão
        </h3>
        
        <div class="action-group secondary-actions">
            <!-- FORMULÁRIO 1: EXCLUIR COMPROMISSO INDIVIDUAL -->
            <form action="<?= PUBLIC_URL ?>/compromissos/delete" method="post" class="delete-form-individual" onsubmit="return confirm('Tem certeza que deseja excluir este compromisso específico?');">
                <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                <button type="submit" class="btn btn-action btn-danger">
                    <i class="fas fa-trash"></i>
                    <span>Excluir Compromisso</span>
                </button>
            </form>
                        
            <!-- FORMULÁRIOS PARA EVENTOS RECORRENTES -->
            <?php if (!empty($compromisso['group_id'])): ?>
                <!-- FORMULÁRIO 2: EXCLUIR ESTE E FUTUROS -->
                <form action="<?= PUBLIC_URL ?>/compromissos/delete" method="post" class="delete-form-future" onsubmit="return confirm('Tem certeza que deseja excluir este e todos os compromissos futuros desta série?');">
                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                    <input type="hidden" name="delete_future" value="1">
                    <button type="submit" class="btn btn-action btn-danger">
                        <i class="fas fa-trash-alt"></i>
                        <span>Excluir Este e Futuros</span>
                    </button>
                </form>
                
                <!-- FORMULÁRIO 3: CANCELAR TODOS OS COMPROMISSOS -->
                <form action="<?= PUBLIC_URL ?>/compromissos/cancel-future" method="post" class="cancel-form-all" onsubmit="return confirm('Tem certeza que deseja cancelar todos os compromissos desta série (incluindo o atual)?');">
                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                    <button type="submit" class="btn btn-action btn-warning">
                        <i class="fas fa-ban"></i>
                        <span>Cancelar Todos os Compromissos</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/form.js"></script>
<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/validation.js"></script>

<script>
// Função que mostra/esconde as opções de recorrência
function toggleRepeatOptions() {
    const repeatType = document.querySelector('input[name="repeat_type"]:checked').value;
    const repeatUntilContainer = document.getElementById('repeat_until_container');
    const repeatDaysContainer = document.getElementById('repeat_days_container');
    const recurrenceWarning = document.getElementById('recurrence-warning');
    
    // Mostrar/esconder container de "repetir até"
    if (repeatType === 'none') {
        repeatUntilContainer.style.display = 'none';
        repeatDaysContainer.style.display = 'none';
        recurrenceWarning.style.display = 'none';
    } else {
        repeatUntilContainer.style.display = 'block';
        recurrenceWarning.style.display = 'block';
        
        // Mostrar/esconder container de dias específicos
        if (repeatType === 'specific_days') {
            repeatDaysContainer.style.display = 'block';
        } else {
            repeatDaysContainer.style.display = 'none';
        }
    }
    
    // Verificar duração ao mudar tipo de recorrência
    checkDurationForRecurrence();
}

// Função para calcular e exibir duração
function calculateDuration() {
    const startInput = document.getElementById('start_datetime');
    const endInput = document.getElementById('end_datetime');
    const durationValue = document.getElementById('duration-value');
    const durationCard = document.getElementById('duration-card');
    
    if (!startInput.value || !endInput.value) {
        durationCard.classList.remove('show');
        return;
    }
    
    const start = new Date(startInput.value);
    const end = new Date(endInput.value);
    
    if (end <= start) {
        durationCard.classList.remove('show');
        return;
    }
    
    const diffMs = end - start;
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
    
    durationValue.textContent = `${diffHours}h ${diffMinutes.toString().padStart(2, '0')}min`;
    durationCard.classList.add('show');
    
    // Verificar se excede 12h para recorrência
    checkDurationForRecurrence();
}

// Função para verificar duração em eventos recorrentes
function checkDurationForRecurrence() {
    const repeatType = document.querySelector('input[name="repeat_type"]:checked').value;
    const startInput = document.getElementById('start_datetime');
    const endInput = document.getElementById('end_datetime');
    const durationCard = document.getElementById('duration-card');
    
    if (repeatType !== 'none' && startInput.value && endInput.value) {
        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        const diffMs = end - start;
        const diffHours = diffMs / (1000 * 60 * 60);
        
        if (diffHours > 12) {
            durationCard.classList.add('duration-error');
        } else {
            durationCard.classList.remove('duration-error');
        }
    } else {
        durationCard.classList.remove('duration-error');
    }
}

// Inicializar as opções de recorrência
document.addEventListener('DOMContentLoaded', function() {
    toggleRepeatOptions();
    calculateDuration();
    
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
                
                // Calcular duração
                calculateDuration();
                
            } catch (e) {
                console.error('Erro ao sincronizar datas:', e);
            }
        });
        
        // Calcular duração quando campo final muda
        endInput.addEventListener('change', function() {
            calculateDuration();
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
    
    // Event listeners para recorrência
    document.querySelectorAll('input[name="repeat_type"]').forEach(input => {
        input.addEventListener('change', checkDurationForRecurrence);
    });
});
</script>