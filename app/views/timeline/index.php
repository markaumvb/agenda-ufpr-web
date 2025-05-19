<div class="page-header">
    <div class="header-container">
        <h1>Linha do Tempo - Eventos Públicos</h1>
    </div>
    
    <!-- Filtros -->
    <div class="timeline-filters">
        <form action="<?= PUBLIC_URL ?>/timeline" method="get" id="timeline-filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="date-filter">Data:</label>
                    <input type="date" id="date-filter" name="date" value="<?= $formattedDate ?>" class="form-control">
                </div>
                
                <div class="filter-group">
                    <label for="agendas-filter">Agendas:</label>
                    <select id="agendas-filter" name="agendas[]" multiple class="form-control">
                        <?php foreach ($publicAgendas as $agenda): ?>
                            <option value="<?= $agenda['id'] ?>" 
                                    <?= in_array($agenda['id'], $selectedAgendas) ? 'selected' : '' ?>
                                    data-color="<?= $agenda['color'] ?>">
                                <?= htmlspecialchars($agenda['title']) ?> (<?= htmlspecialchars($agenda['owner_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search-filter">Pesquisar:</label>
                    <input type="text" id="search-filter" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Buscar por título, descrição ou local" class="form-control">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?= PUBLIC_URL ?>/timeline" class="btn btn-secondary">Limpar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Indicador de hora atual -->
<div class="current-time-indicator">
    <div class="time-label">Agora: <span id="current-time"></span></div>
</div>

<!-- Container do calendário -->
<div class="timeline-container">
    <div id="timeline-calendar"></div>
</div>

<!-- Modal de detalhes do evento -->
<div class="modal fade" id="event-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Evento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="event-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar FullCalendar
    const calendarEl = document.getElementById('timeline-calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridDay',
        initialDate: '<?= $formattedDate ?>',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridDay,listDay'
        },
        locale: 'pt-br',
        allDaySlot: false,
        nowIndicator: true,
        height: 'auto',
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        events: <?= json_encode(array_map(function($event) {
            return [
                'id' => $event['id'],
                'title' => $event['title'],
                'start' => $event['start_datetime'],
                'end' => $event['end_datetime'],
                'backgroundColor' => $event['agenda_info']['color'],
                'borderColor' => $event['agenda_info']['color'],
                'extendedProps' => [
                    'description' => $event['description'],
                    'location' => $event['location'],
                    'status' => $event['status'],
                    'agendaTitle' => $event['agenda_info']['title'],
                    'agendaColor' => $event['agenda_info']['color'],
                    'ownerName' => $event['agenda_info']['owner_name'],
                    'creatorName' => $event['creator_name'] ?? 'Desconhecido'
                ]
            ];
        }, $allEvents)) ?>,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        eventClassNames: function(arg) {
            return [`event-status-${arg.event.extendedProps.status}`];
        }
    });
    
    calendar.render();
    
    // Atualizar hora atual a cada minuto
    updateCurrentTime();
    setInterval(updateCurrentTime, 60000);
    
    // Atualizar calendário a cada 30 minutos
    setInterval(function() {
        calendar.refetchEvents();
        showUpdateMessage();
    }, 30 * 60 * 1000);
    
    // Função para atualizar o indicador de hora atual
    function updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('pt-BR', {
            hour: '2-digit',
            minute: '2-digit'
        });
        document.getElementById('current-time').textContent = timeString;
    }
    
    // Função para mostrar mensagem de atualização
    function showUpdateMessage() {
        const indicator = document.createElement('div');
        indicator.className = 'auto-update-indicator';
        indicator.textContent = 'Calendário atualizado!';
        document.body.appendChild(indicator);
        
        // Mostra a mensagem
        setTimeout(() => {
            indicator.classList.add('visible');
        }, 100);
        
        // Remove a mensagem após 3 segundos
        setTimeout(() => {
            indicator.classList.remove('visible');
            setTimeout(() => {
                document.body.removeChild(indicator);
            }, 300);
        }, 3000);
    }
    
    // Função para mostrar detalhes do evento no modal
    function showEventDetails(event) {
        const props = event.extendedProps;
        const start = new Date(event.start);
        const end = new Date(event.end);
        
        const formattedStart = formatDateTime(start);
        const formattedEnd = formatDateTime(end);
        
        const statusLabels = {
            'pendente': 'Pendente',
            'realizado': 'Realizado',
            'cancelado': 'Cancelado',
            'aguardando_aprovacao': 'Aguardando Aprovação'
        };
        
        // Preencher o modal com detalhes do evento
        let content = `
            <div class="event-header" style="border-left: 4px solid ${props.agendaColor}; padding-left: 10px;">
                <h4>${event.title}</h4>
                <div class="agenda-info">
                    <span class="agenda-color-dot" style="background-color: ${props.agendaColor}"></span>
                    <strong>Agenda:</strong> ${props.agendaTitle} (${props.ownerName})
                </div>
            </div>
            <div class="event-details">
                <p><strong>Horário:</strong> ${formattedStart} até ${formattedEnd}</p>
                <p><strong>Status:</strong> <span class="badge badge-${props.status}">${statusLabels[props.status] || props.status}</span></p>
                <p><strong>Criado por:</strong> ${props.creatorName}</p>
                ${props.location ? `<p><strong>Local:</strong> ${props.location}</p>` : ''}
                ${props.description ? `<p><strong>Descrição:</strong><br>${props.description}</p>` : ''}
            </div>
        `;
        
        // Definir o conteúdo e mostrar o modal
        document.querySelector('#event-details-modal .event-info').innerHTML = content;
        $('#event-details-modal').modal('show');
    }
    
    // Função auxiliar para formatar data e hora
    function formatDateTime(date) {
        return date.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Enviar o formulário quando mudar a data
    document.getElementById('date-filter').addEventListener('change', function() {
        document.getElementById('timeline-filter-form').submit();
    });
});
</script>