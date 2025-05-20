<?php
// app/views/timeline/index.php - Versão corrigida usando CSS existente e garantindo exibição dos eventos
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
                    <label for="agenda-select">Agenda</label>
                    <select id="agenda-select" name="agenda_id" class="form-control">
                        <option value="all" <?= (!isset($_GET['agenda_id']) || $_GET['agenda_id'] == 'all') ? 'selected' : '' ?>>Todas as Agendas</option>
                        <?php foreach ($publicAgendas as $agenda): ?>
                        <option value="<?= $agenda['id'] ?>" <?= (isset($_GET['agenda_id']) && $_GET['agenda_id'] == $agenda['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($agenda['title']) ?> (<?= htmlspecialchars($agenda['owner_name']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
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
            <button type="button" class="btn btn-outline view-option active" data-view="timeGridDay">Dia</button>
            <button type="button" class="btn btn-outline view-option" data-view="listDay">Lista</button>
        </div>
    </div>

    <!-- Debug - Para verificar se os eventos estão sendo recuperados -->
    <?php if (empty($allEvents)): ?>
    <div class="alert alert-info">
        <p>Nenhum compromisso encontrado para esta data.</p>
    </div>
    <?php else: ?>
    <div class="alert alert-success d-none">
        <p>Encontrado(s) <?= count($allEvents) ?> compromisso(s) para exibição.</p>
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

<!-- Script para inicializar o FullCalendar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submeter o formulário quando mudar a data
    const datePicker = document.getElementById('date-picker');
    if (datePicker) {
        datePicker.addEventListener('change', function() {
            document.querySelector('.filter-form').submit();
        });
    }

    // Auto-submeter o formulário quando mudar a agenda
    const agendaSelect = document.getElementById('agenda-select');
    if (agendaSelect) {
        agendaSelect.addEventListener('change', function() {
            document.querySelector('.filter-form').submit();
        });
    }

    // Inicializar o FullCalendar
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.error('Elemento do calendário não encontrado');
        return;
    }

    // Preparar eventos para o FullCalendar
    const events = [];
    
    <?php foreach ($allEvents as $event): 
        // Filtrar apenas eventos pendentes ou realizados
        if (!in_array($event['status'], ['pendente', 'realizado'])) continue;
        
        // Usar a cor da agenda para o evento
        $colorHex = !empty($event['agenda_info']['color']) ? 
            $event['agenda_info']['color'] : 
            ($event['status'] === 'pendente' ? '#ffc107' : '#28a745');
    ?>
    events.push({
        id: <?= json_encode($event['id']) ?>,
        title: <?= json_encode($event['title']) ?>,
        start: <?= json_encode($event['start_datetime']) ?>,
        end: <?= json_encode($event['end_datetime']) ?>,
        backgroundColor: <?= json_encode($colorHex) ?>,
        borderColor: <?= json_encode($colorHex) ?>,
        textColor: '#fff',
        description: <?= json_encode($event['description'] ?? '') ?>,
        location: <?= json_encode($event['location'] ?? '') ?>,
        status: <?= json_encode($event['status']) ?>,
        agendaInfo: {
            title: <?= json_encode($event['agenda_info']['title'] ?? 'Agenda') ?>,
            color: <?= json_encode($event['agenda_info']['color'] ?? '#ccc') ?>,
            owner: <?= json_encode($event['agenda_info']['owner_name'] ?? 'Desconhecido') ?>
        }
    });
    <?php endforeach; ?>

    console.log('Total de eventos encontrados:', events.length);

    // Criar o calendário
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt-br',
        initialView: 'timeGridDay', // Vista padrão: dia
        initialDate: '<?= $date->format('Y-m-d') ?>',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''  // Removido pois temos botões personalizados
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
        height: 'auto',
        events: events,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        eventClick: function(info) {
            showEventDetails(info.event);
        }
    });

    calendar.render();
    
    // Manipular botões de visualização
    document.querySelectorAll('.view-option').forEach(button => {
        button.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            calendar.changeView(view);
            
            // Atualizar estilo dos botões
            document.querySelectorAll('.view-option').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Função para mostrar os detalhes do evento
    function showEventDetails(event) {
        const modal = document.getElementById('event-modal');
        const detailsContainer = document.getElementById('event-details');
        
        if (modal && detailsContainer) {
            const startTime = new Date(event.start).toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            const endTime = new Date(event.end).toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            
            let content = `
                <div class="event-details-header" style="border-left: 4px solid ${event.backgroundColor}; padding-left: 10px;">
                    <h4>${event.title}</h4>
                    <span class="badge badge-${event.extendedProps.status}">
                        ${event.extendedProps.status === 'pendente' ? 'Pendente' : 'Realizado'}
                    </span>
                </div>
                <div class="event-details-body">
                    <p>
                        <i class="fas fa-clock"></i> <strong>Horário:</strong> 
                        ${startTime} até ${endTime}
                    </p>`;
                    
            if (event.extendedProps.agendaInfo) {
                content += `
                    <p>
                        <i class="fas fa-calendar"></i> <strong>Agenda:</strong> 
                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: ${event.extendedProps.agendaInfo.color}; margin-right: 5px;"></span>
                        ${event.extendedProps.agendaInfo.title} 
                        <span style="font-style: italic; color: #777;">(${event.extendedProps.agendaInfo.owner})</span>
                    </p>`;
            }
            
            if (event.extendedProps.location) {
                content += `<p><i class="fas fa-map-marker-alt"></i> <strong>Local:</strong> ${event.extendedProps.location}</p>`;
            }
            
            if (event.extendedProps.description) {
                content += `
                    <div class="description-section mt-3 pt-3" style="border-top: 1px solid #eee;">
                        <strong>Descrição:</strong>
                        <div class="p-2 mt-2 bg-light rounded">${event.extendedProps.description.replace(/\n/g, '<br>')}</div>
                    </div>`;
            }
            
            content += `</div>`;
            
            // Inserir conteúdo no modal
            detailsContainer.innerHTML = content;
            
            // Abrir o modal
            $(modal).modal('show');
        }
    }
});
</script>