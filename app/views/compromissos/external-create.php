<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Compromisso - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/component.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/compromissos.css">
</head>
<body class="auth-page">
    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-calendar-plus"></i> Novo Compromisso</h1>
            <a href="<?= BASE_URL ?>/" class="btn btn-link">
                <i class="fas fa-arrow-left"></i> Voltar ao Início
            </a>
        </div>
        
        <div class="external-notice">
            <i class="fas fa-user-circle"></i>
            <strong>Solicitante:</strong> <?= htmlspecialchars($externalUser['name']) ?><br>
            <strong>E-mail:</strong> <?= htmlspecialchars($externalUser['email']) ?><br>
            <strong>Agenda:</strong> <?= htmlspecialchars($agenda['title']) ?>
        </div>
        
        <?php if (isset($agenda['min_time_before']) && $agenda['min_time_before'] > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Atenção:</strong> Esta agenda requer <?= $agenda['min_time_before'] ?> horas de antecedência para criação de compromissos.
        </div>
        <?php endif; ?>

        <?php if (isset($errors) && !empty($errors)): ?>
            <div id="error-container" class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <ul id="error-list">
                    <?php foreach($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['validation_errors']) && !empty($_SESSION['validation_errors'])): ?>
            <div id="error-container" class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <ul id="error-list">
                    <?php foreach($_SESSION['validation_errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['validation_errors']); ?>
        <?php endif; ?>
        
        <form action="<?= BASE_URL ?>/compromissos/external-store" method="post" class="compromisso-form" id="compromisso-form">
            <input type="hidden" name="agenda_id" value="<?= $agendaId ?>">

            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-edit"></i> Informações do Compromisso
                </h3>
                
                <div class="form-group">
                    <label for="title">
                        <i class="fas fa-heading"></i> Título *
                    </label>
                    <input type="text" id="title" name="title" required class="form-control" 
                           placeholder="Digite o título do compromisso"
                           value="<?= isset($formData['title']) ? htmlspecialchars($formData['title']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-align-left"></i> Descrição
                    </label>
                    <textarea id="description" name="description" rows="4" class="form-control" 
                              placeholder="Descreva os detalhes do compromisso (opcional)"><?= isset($formData['description']) ? htmlspecialchars($formData['description']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location">
                        <i class="fas fa-map-marker-alt"></i> Local
                    </label>
                    <input type="text" id="location" name="location" class="form-control" 
                           placeholder="Onde será realizado o compromisso (opcional)"
                           value="<?= isset($formData['location']) ? htmlspecialchars($formData['location']) : '' ?>">
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
                        <input type="datetime-local" id="start_datetime" name="start_datetime" required class="form-control"
                            value="<?= isset($formData['start_datetime']) ? htmlspecialchars($formData['start_datetime']) : $defaultStartDateTime ?>" 
                            data-min-time="<?= $agenda['min_time_before'] ?? 0 ?>">
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
                        <input type="datetime-local" id="end_datetime" name="end_datetime" required class="form-control"
                               value="<?= isset($formData['end_datetime']) ? htmlspecialchars($formData['end_datetime']) : $defaultEndDateTime ?>">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i>
                            Deve ser posterior ao horário de início
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-flag"></i> Status
                </h3>
                
                <div class="form-group">
                    <label for="status-display">Status do Compromisso</label>
                    <div class="status-display status-warning">
                        <i class="fas fa-clock"></i>
                        <span>Aguardando Aprovação</span>
                        <small>Sua solicitação será analisada pelo responsável da agenda</small>
                    </div>
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
                        <input type="date" id="repeat_until" name="repeat_until" class="form-control"
                               value="<?= isset($formData['repeat_until']) ? htmlspecialchars($formData['repeat_until']) : '' ?>">
                        <small class="form-text text-muted">Data final da recorrência</small>
                    </div>
                    
                    <div id="repeat_days_container" class="form-group repeat-option" style="display: none;">
                        <label>
                            <i class="fas fa-calendar-week"></i> Dias da semana
                        </label>
                        <div class="checkbox-group days-grid">
                            <?php 
                            $daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                            $selectedDays = isset($formData['repeat_days']) ? $formData['repeat_days'] : [];
                            
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
            
            <div class="form-actions">
                <div class="action-group primary-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i>
                        <span>Enviar Solicitação</span>
                    </button>
                    
                    <a href="<?= BASE_URL ?>/" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script>
        // Função que mostra/esconde as opções de recorrência
        function toggleRepeatOptions() {
            const repeatType = document.querySelector('input[name="repeat_type"]:checked').value;
            const repeatUntilContainer = document.getElementById('repeat_until_container');
            const repeatDaysContainer = document.getElementById('repeat_days_container');
            
            if (repeatType === 'none') {
                repeatUntilContainer.style.display = 'none';
                repeatDaysContainer.style.display = 'none';
            } else {
                repeatUntilContainer.style.display = 'block';
                
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
            
            // Sincronizar data de término quando data de início for alterada
            const startDatetime = document.getElementById('start_datetime');
            const endDatetime = document.getElementById('end_datetime');
            
            if (startDatetime && endDatetime) {
                startDatetime.addEventListener('change', function() {
                    if (!startDatetime.value) return;
                    
                    try {
                        const startDate = new Date(startDatetime.value);
                        let endDate = null;
                        
                        if (endDatetime.value) {
                            endDate = new Date(endDatetime.value);
                        }
                        
                        // Só atualizar o fim se estiver vazio ou for anterior ao início
                        if (!endDatetime.value || !endDate || endDate <= startDate) {
                            const newEndDate = new Date(startDate);
                            newEndDate.setHours(newEndDate.getHours() + 1);
                            
                            const year = newEndDate.getFullYear();
                            const month = String(newEndDate.getMonth() + 1).padStart(2, '0');
                            const day = String(newEndDate.getDate()).padStart(2, '0');
                            const hours = String(newEndDate.getHours()).padStart(2, '0');
                            const minutes = String(newEndDate.getMinutes()).padStart(2, '0');
                            
                            endDatetime.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                        }
                    } catch (e) {
                        console.log('Erro ao processar data:', e);
                    }
                });
            }
        });
    </script>
    
    <?php unset($_SESSION['form_data']); ?>
</body>
</html>