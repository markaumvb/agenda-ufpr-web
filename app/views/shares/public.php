<?php
// Arquivo: app/views/shares/public.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($agenda['title']) ?> - Agenda Pública</title>
    
    <!-- FullCalendar CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js"></script>
    
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/shares/public.css">
    <style>
        :root {
            --agenda-color: <?= $agenda['color'] ?? '#004a8f' ?>;
        }
        
        /* Estilos específicos do FullCalendar para visualização pública */
        #calendar {
            min-height: 500px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .fc .fc-toolbar-title {
            font-size: 1.4rem;
            color: var(--agenda-color);
        }
        
        .fc .fc-button-primary {
            background-color: var(--agenda-color);
            border-color: var(--agenda-color);
        }
        
        .fc .fc-button-primary:not(:disabled):hover {
            background-color: var(--agenda-color);
            opacity: 0.9;
        }
        
        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background-color: var(--agenda-color);
            opacity: 0.8;
        }
        
        .view-options {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .view-options .btn-group {
            display: flex;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .view-option {
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            cursor: pointer;
            font-size: 0.9rem;
            color: #333;
            transition: background-color 0.2s;
        }
        
        .view-option:hover {
            background-color: #e9ecef;
        }
        
        .view-option.active {
            background-color: var(--agenda-color);
            color: white;
            border-color: var(--agenda-color);
        }
        
        .view-option:first-child {
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
        }
        
        .view-option:last-child {
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        
        /* Modal de evento */
        .event-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .event-modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .event-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .event-modal-close:hover,
        .event-modal-close:focus {
            color: black;
            text-decoration: none;
        }
        
        .event-modal-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .event-modal-title {
            margin: 0;
            font-size: 1.4rem;
            color: var(--agenda-color);
        }
        
        @media (max-width: 768px) {
            .view-options {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <header style="background-color: <?= $agenda['color'] ?? '#004a8f' ?>;">
        <div class="container">
            <div class="header-content">
                <h1><?= htmlspecialchars($agenda['title']) ?></h1>
                
                <?php if (!empty($agenda['description'])): ?>
                    <div class="description"><?= htmlspecialchars($agenda['description']) ?></div>
                <?php endif; ?>
                
                <div class="owner-info">
                    Agenda de <?= htmlspecialchars($owner['name']) ?>
                </div>
            </div>
        </div>
    </header>
    
    <main class="container">
        <!-- Opções de visualização do calendário -->
        <div class="view-options">
            <div class="btn-group" role="group">
                <button type="button" class="view-option" data-view="dayGridMonth">Mês</button>
                <button type="button" class="view-option" data-view="timeGridWeek">Semana</button>
                <button type="button" class="view-option" data-view="timeGridDay">Dia</button>
                <button type="button" class="view-option" data-view="listWeek">Lista</button>
            </div>
        </div>
        
        <!-- Calendário -->
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>
        
        <!-- Lista de Compromissos -->
        <div class="events-list-container">
            <h2 class="section-title" style="color: <?= $agenda['color'] ?? '#004a8f' ?>;">Compromissos</h2>
            
            <?php if (empty($allCompromissos)): ?>
                <div class="empty-state">
                    <p>Nenhum compromisso encontrado nesta agenda.</p>
                </div>
            <?php else: ?>
                <div class="events-filters">
                    <div class="filter-group">
                        <label for="filter-status">Status:</label>
                        <select id="filter-status" class="filter-select">
                            <option value="all">Todos</option>
                            <option value="pendente">Pendentes</option>
                            <option value="realizado">Realizados</option>
                            <option value="aguardando_aprovacao">Aguardando Aprovação</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-month">Mês:</label>
                        <select id="filter-month" class="filter-select">
                            <option value="all">Todos</option>
                            <option value="1">Janeiro</option>
                            <option value="2">Fevereiro</option>
                            <option value="3">Março</option>
                            <option value="4">Abril</option>
                            <option value="5">Maio</option>
                            <option value="6">Junho</option>
                            <option value="7">Julho</option>
                            <option value="8">Agosto</option>
                            <option value="9">Setembro</option>
                            <option value="10">Outubro</option>
                            <option value="11">Novembro</option>
                            <option value="12">Dezembro</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <input type="text" id="filter-search" placeholder="Buscar compromissos..." class="filter-input">
                    </div>
                    <div class="filter-group">
                        <button id="clear-filters" class="btn btn-secondary">Limpar Filtros</button>
                    </div>
                </div>
                
                <div class="events-list">
                    <?php foreach ($allCompromissos as $compromisso): ?>
                        <?php 
                        // Pular compromissos cancelados
                        if ($compromisso['status'] === 'cancelado') continue;
                        
                        $startDate = new DateTime($compromisso['start_datetime']);
                        $endDate = new DateTime($compromisso['end_datetime']);
                        ?>
                        
                        <div class="event-card event-status-<?= $compromisso['status'] ?>" 
                             data-status="<?= $compromisso['status'] ?>" 
                             data-month="<?= $startDate->format('n') ?>" 
                             data-date="<?= $startDate->format('Y-m-d') ?>"
                             data-id="<?= $compromisso['id'] ?>"
                             data-search="<?= htmlspecialchars(strtolower($compromisso['title'] . ' ' . $compromisso['description'] . ' ' . $compromisso['location'])) ?>">
                            <!-- Conteúdo do card de evento -->
                            <div class="event-header">
                                <h3 class="event-title"><?= htmlspecialchars($compromisso['title']) ?></h3>
                                <div class="event-status">
                                    <span class="badge badge-<?= $compromisso['status'] ?>">
                                        <?php
                                        $statusLabels = [
                                            'pendente' => 'Pendente',
                                            'realizado' => 'Realizado',
                                            'aguardando_aprovacao' => 'Aguardando'
                                        ];
                                        echo $statusLabels[$compromisso['status']] ?? $compromisso['status'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="event-details">
                                <div class="event-datetime">
                                    <div class="event-date">
                                        <i class="icon-calendar"></i>
                                        <?php if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')): ?>
                                            <?= $startDate->format('d/m/Y') ?>
                                        <?php else: ?>
                                            <?= $startDate->format('d/m/Y') ?> até <?= $endDate->format('d/m/Y') ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="event-time">
                                        <i class="icon-clock"></i>
                                        <?= $startDate->format('H:i') ?> às <?= $endDate->format('H:i') ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($compromisso['location'])): ?>
                                    <div class="event-location">
                                        <i class="icon-location"></i>
                                        <?= htmlspecialchars($compromisso['location']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($compromisso['description'])): ?>
                                    <div class="event-description">
                                        <?= nl2br(htmlspecialchars($compromisso['description'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($compromisso['repeat_type'] !== 'none'): ?>
                                    <div class="event-recurrence">
                                        <i class="icon-repeat"></i>
                                        <?php
                                        $recurrenceLabels = [
                                            'daily' => 'Repete diariamente',
                                            'weekly' => 'Repete semanalmente',
                                            'specific_days' => 'Repete em dias específicos'
                                        ];
                                        echo $recurrenceLabels[$compromisso['repeat_type']] ?? '';
                                        
                                        if ($compromisso['repeat_until']) {
                                            echo ' até ' . (new DateTime($compromisso['repeat_until']))->format('d/m/Y');
                                        }
                                        
                                        if ($compromisso['repeat_type'] === 'specific_days' && $compromisso['repeat_days']) {
                                            $daysLabels = [
                                                '0' => 'Dom',
                                                '1' => 'Seg',
                                                '2' => 'Ter',
                                                '3' => 'Qua',
                                                '4' => 'Qui',
                                                '5' => 'Sex',
                                                '6' => 'Sáb'
                                            ];
                                            
                                            $days = explode(',', $compromisso['repeat_days']);
                                            $daysText = [];
                                            
                                            foreach ($days as $day) {
                                                if (isset($daysLabels[$day])) {
                                                    $daysText[] = $daysLabels[$day];
                                                }
                                            }
                                            
                                            if (!empty($daysText)) {
                                                echo ' (' . implode(', ', $daysText) . ')';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para detalhe de eventos -->
    <div id="event-modal" class="event-modal">
        <div class="event-modal-content">
            <span class="event-modal-close">&times;</span>
            <div class="event-modal-header">
                <h2 class="event-modal-title">Detalhes do Compromisso</h2>
            </div>
            <div id="event-modal-body"></div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>Esta é uma visualização pública da agenda "<?= htmlspecialchars($agenda['title']) ?>".</p>
            <p>&copy; <?= date('Y') ?> - Sistema de Agendamento UFPR</p>
        </div>
    </footer>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Obter o container do calendário
        const calendarEl = document.getElementById('calendar');
        
        if (!calendarEl) return;
        
        // Cores para os diferentes status de compromissos
        const statusColors = {
            'pendente': '#ffc107',
            'realizado': '#28a745',
            'cancelado': '#dc3545',
            'aguardando_aprovacao': '#17a2b8'
        };
    
        // Preparar eventos para o calendário
        const events = [];
        document.querySelectorAll('.event-card').forEach(card => {
            // Pular eventos cancelados
            if (card.dataset.status === 'cancelado') return;
            
            const id = card.dataset.id;
            const title = card.querySelector('.event-title').textContent.trim();
            const status = card.dataset.status;
            const dateStart = card.querySelector('.event-datetime .event-date').textContent.trim();
            const timeElement = card.querySelector('.event-datetime .event-time');
            const time = timeElement ? timeElement.textContent.trim() : '';
            
            const location = card.querySelector('.event-location') 
              ? card.querySelector('.event-location').textContent.trim() 
              : '';
            
            const description = card.querySelector('.event-description') 
              ? card.querySelector('.event-description').textContent.trim() 
              : '';
            
            // Extrair a data e hora de início e fim do texto
            const startDateStr = card.dataset.date;
            const allDay = !time || time.indexOf('às') === -1;
            
            let startTime, endTime;
            if (!allDay && time) {
              const timeParts = time.replace(/[^\d:]/g, ' ').trim().split(/\s+/);
              startTime = timeParts[0];
              endTime = timeParts.length > 1 ? timeParts[1] : '';
            }
            
            const event = {
              id: id,
              title: title,
              start: startDateStr + (startTime ? 'T' + startTime + ':00' : ''),
              allDay: allDay,
              extendedProps: {
                status: status,
                description: description,
                location: location
              },
              backgroundColor: statusColors[status] || '<?= $agenda['color'] ?? '#004a8f' ?>',
              borderColor: statusColors[status] || '<?= $agenda['color'] ?? '#004a8f' ?>',
              textColor: '#fff',
            };
            
            if (endTime) {
              event.end = startDateStr + 'T' + endTime + ':00';
            }
            
            events.push(event);
        });
    
        // Inicializar o FullCalendar
        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt-br',
            initialView: 'dayGridMonth',
            headerToolbar: {
              left: 'prev,next today',
              center: 'title',
              right: '' // Removido pois temos botões personalizados para visualizações
            },
            weekNumbers: false,
            navLinks: true,
            editable: false, // Não permitir edição em agendas públicas
            selectable: false,
            dayMaxEvents: true,
            height: 'auto',
            events: events,
            
            // Callback quando um evento é clicado
            eventClick: function(info) {
              showEventDetails(info.event);
              
              // Evitar redirecionamento padrão
              info.jsEvent.preventDefault();
            }
        });
        
        // Renderizar o calendário
        calendar.render();
        
        // Configurar botões de visualização
        document.querySelectorAll('.view-option').forEach(button => {
            button.addEventListener('click', function() {
              const view = this.dataset.view;
              calendar.changeView(view);
              
              // Atualizar botão ativo
              document.querySelectorAll('.view-option').forEach(btn => {
                btn.classList.remove('active');
              });
              this.classList.add('active');
            });
        });
        
        // Ativar o primeiro botão (visualização de mês) por padrão
        document.querySelector('.view-option[data-view="dayGridMonth"]').classList.add('active');
        
        // Configurar filtros para atualizar o calendário
        setupFilters(calendar, events);
        
        // Função para exibir detalhes de um evento em um modal
        function showEventDetails(event) {
            // Obter ID do evento
            const eventId = event.id;
            
            // Buscar o card correspondente
            const eventCard = document.querySelector(`.event-card[data-id="${eventId}"]`);
            
            if (!eventCard) return;
            
            // Abrir modal com detalhes
            const modal = document.getElementById('event-modal');
            const modalBody = document.getElementById('event-modal-body');
            
            // Clonar o conteúdo do card para o modal
            modalBody.innerHTML = '';
            const eventDetails = eventCard.cloneNode(true);
            modalBody.appendChild(eventDetails);
            
            // Exibir modal
            modal.style.display = 'block';
            
            // Configurar botão de fechar
            const closeBtn = document.querySelector('.event-modal-close');
            closeBtn.onclick = function() {
              modal.style.display = 'none';
            };
            
            // Fechar quando clicar fora do modal
            window.onclick = function(event) {
              if (event.target == modal) {
                modal.style.display = 'none';
              }
            };
        }
        
        // Configurar filtros para atualizar o calendário também
        function setupFilters(calendar, allEvents) {
            const statusFilter = document.getElementById('filter-status');
            const monthFilter = document.getElementById('filter-month');
            const searchFilter = document.getElementById('filter-search');
            const clearFilterBtn = document.getElementById('clear-filters');
            
            // Função para aplicar filtros
            function applyFilters() {
              const statusValue = statusFilter ? statusFilter.value : 'all';
              const monthValue = monthFilter ? monthFilter.value : 'all';
              const searchValue = searchFilter ? searchFilter.value.toLowerCase() : '';
              
              // Mostrar/esconder os cards da lista
              document.querySelectorAll('.event-card').forEach(card => {
                  const status = card.dataset.status;
                  const month = card.dataset.month;
                  const searchText = card.dataset.search;
                  
                  // Aplicar filtros
                  const statusMatch = statusValue === 'all' || status === statusValue;
                  const monthMatch = monthValue === 'all' || month === monthValue;
                  const searchMatch = !searchValue || searchText.includes(searchValue);
                  
                  // Mostrar/esconder o card
                  card.style.display = statusMatch && monthMatch && searchMatch ? 'block' : 'none';
              });
              
              // Filtrar eventos do calendário
              const filteredEvents = allEvents.filter(event => {
                  // Status
                  const statusMatch = statusValue === 'all' || event.extendedProps.status === statusValue;
                  
                  // Mês
                  const eventDate = new Date(event.start);
                  const eventMonth = eventDate.getMonth() + 1;
                  const monthMatch = monthValue === 'all' || eventMonth.toString() === monthValue;
                  
                  // Texto
                  const searchableText = (event.title + ' ' + 
                                  (event.extendedProps.description || '') + ' ' + 
                                  (event.extendedProps.location || '')).toLowerCase();
                  const searchMatch = !searchValue || searchableText.includes(searchValue);
                  
                  return statusMatch && monthMatch && searchMatch;
              });
              
              // Atualizar eventos no calendário
              calendar.removeAllEvents();
              calendar.addEventSource(filteredEvents);
            }
            
            // Adicionar listeners para filtros
            if (statusFilter) statusFilter.addEventListener('change', applyFilters);
            if (monthFilter) monthFilter.addEventListener('change', applyFilters);
            if (searchFilter) searchFilter.addEventListener('input', applyFilters);
            
            // Botão para limpar filtros
            if (clearFilterBtn) {
              clearFilterBtn.addEventListener('click', function() {
                  if (statusFilter) statusFilter.value = 'all';
                  if (monthFilter) monthFilter.value = 'all';
                  if (searchFilter) searchFilter.value = '';
                  
                  // Resetar filtros e atualizar visualização
                  document.querySelectorAll('.event-card').forEach(card => {
                      card.style.display = 'block';
                  });
                  
                  calendar.removeAllEvents();
                  calendar.addEventSource(allEvents);
              });
            }
        }
    });
    </script>
</body>
</html>