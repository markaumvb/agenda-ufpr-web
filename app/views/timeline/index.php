<?php
// app/views/timeline/index.php - Com estilização melhorada para os filtros
?>

<div class="container">
    <div class="page-header">
        <div class="header-container">
            <h1>Linha do Tempo</h1>
            <div class="header-actions">
                <a href="<?= PUBLIC_URL ?>/" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros da timeline -->
    <div class="timeline-filters">
        <form action="<?= PUBLIC_URL ?>/timeline" method="get" class="filter-form">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="date-picker">Data</label>
                    <input type="date" id="date-picker" name="date" class="form-control" 
                           value="<?= htmlspecialchars($date->format('Y-m-d')) ?>">
                </div>
                
                <?php if (!empty($publicAgendas)): ?>
                <div class="col-md-5 mb-3">
                    <label>Agendas</label>
                    <div class="agendas-filter-container">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="select-all-agendas" 
                                <?= empty($selectedAgendas) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="select-all-agendas">
                                <strong>Selecionar Todas</strong>
                            </label>
                        </div>
                        <hr>
                        <?php foreach ($publicAgendas as $agenda): ?>
                        <div class="form-check agenda-item">
                            <input class="form-check-input agenda-checkbox" type="checkbox" 
                                   name="agendas[]" value="<?= $agenda['id'] ?>" id="agenda-<?= $agenda['id'] ?>"
                                   <?= (empty($selectedAgendas) || in_array($agenda['id'], $selectedAgendas)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="agenda-<?= $agenda['id'] ?>">
                                <span class="agenda-color-dot" style="background-color: <?= htmlspecialchars($agenda['color']) ?>;"></span>
                                <?= htmlspecialchars($agenda['title']) ?> 
                                <small class="text-muted">(<?= htmlspecialchars($agenda['owner_name']) ?>)</small>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="col-md-4 mb-3">
                    <label for="search-input">Buscar</label>
                    <div class="input-group">
                        <input type="text" id="search-input" name="search" class="form-control" 
                               placeholder="Título, local ou descrição" value="<?= htmlspecialchars($searchQuery) ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Opções de visualização do calendário -->
    <div class="view-options mb-4">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary view-option active" data-view="timeGridDay">Dia</button>
            <button type="button" class="btn btn-outline-primary view-option" data-view="listDay">Lista</button>
        </div>
    </div>

    <!-- Mensagem quando não há eventos -->
    <?php if (empty($allEvents)): ?>
    <div class="alert alert-info">
        <p>Nenhum compromisso encontrado para esta data.</p>
    </div>
    <?php endif; ?>

    <!-- Calendário FullCalendar -->
    <div class="calendar-container">
        <div id="calendar"></div>
    </div>

    <!-- Modal para detalhes do evento -->
    <div id="event-modal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Compromisso</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="event-details">
                    <!-- Detalhes do evento serão inseridos aqui -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para passar os dados dos eventos para o JavaScript -->
<script>
// Preparar eventos para o JavaScript
window.timelineEvents = [
    <?php foreach ($allEvents as $event): ?>
    {
        id: <?= json_encode($event['id']) ?>,
        title: <?= json_encode($event['title']) ?>,
        start: <?= json_encode($event['start_datetime']) ?>,
        end: <?= json_encode($event['end_datetime']) ?>,
        description: <?= json_encode($event['description'] ?? '') ?>,
        location: <?= json_encode($event['location'] ?? '') ?>,
        status: <?= json_encode($event['status']) ?>,
        agendaInfo: {
            id: <?= json_encode($event['agenda_info']['id'] ?? '') ?>,
            title: <?= json_encode($event['agenda_info']['title'] ?? 'Agenda') ?>,
            color: <?= json_encode($event['agenda_info']['color'] ?? '#ccc') ?>,
            owner: <?= json_encode($event['agenda_info']['owner_name'] ?? 'Desconhecido') ?>
        }
    },
    <?php endforeach; ?>
];

// Lógica para o checkbox "Selecionar Todas"
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-agendas');
    const agendaCheckboxes = document.querySelectorAll('.agenda-checkbox');
    
    if (selectAllCheckbox) {
        // Atualizar o estado do checkbox "Selecionar Todas" com base nos checkboxes individuais
        function updateSelectAllCheckbox() {
            let allChecked = true;
            agendaCheckboxes.forEach(checkbox => {
                if (!checkbox.checked) {
                    allChecked = false;
                }
            });
            selectAllCheckbox.checked = allChecked;
        }
        
        // Quando clica em "Selecionar Todas"
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            agendaCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
        
        // Quando clica em um checkbox individual
        agendaCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectAllCheckbox);
        });
        
        // Auto-submeter o formulário quando mudar a data
        const datePicker = document.getElementById('date-picker');
        if (datePicker) {
            datePicker.addEventListener('change', function() {
                document.querySelector('.filter-form').submit();
            });
        }
    }
});
</script>

<!-- Carregar o script externo da timeline -->
<script src="<?= PUBLIC_URL ?>/app/assets/js/timeline.js"></script>