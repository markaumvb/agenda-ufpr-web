<?php
// Arquivo: app/views/shares/public.php (versão corrigida)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($agenda['title']) ?> - Agenda Pública</title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/style.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/component.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/compromissos.css">
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/app/assets/css/shares.css">
    
    <!-- FullCalendar CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js"></script>
    
    <style>
        :root {
            --agenda-color: <?= $agenda['color'] ?? '#004a8f' ?>;
        }
        
        body {
            font-family: "Roboto", sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        header {
            background-color: var(--agenda-color);
            color: #fff;
            padding: 2rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        .header-content h1 {
            margin: 0 0 1rem 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .description {
            margin-bottom: 1rem;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .owner-info {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        main {
            padding: 2rem 0;
        }
        
        /* Opções de visualização do calendário */
        .view-options {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .btn-group {
            display: flex;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        
        .view-option {
            padding: 0.7rem 1.2rem;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            cursor: pointer;
            font-size: 0.95rem;
            color: #333;
            transition: all 0.2s ease;
        }
        
        .view-option:hover {
            background-color: #e9ecef;
        }
        
        .view-option.active {
            background-color: var(--agenda-color);
            color: white;
            border-color: var(--agenda-color);
        }
        
        /* Calendário */
        .calendar-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        #calendar {
            min-height: 500px;
        }
        
        /* Lista de compromissos */
        .events-list-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: var(--agenda-color);
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .events-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-select,
        .filter-input {
            padding: 0.6rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .filter-input {
            min-width: 250px;
        }
        
        /* Cards de eventos */
        .events-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .event-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .event-card.event-status-pendente {
            border-left: 4px solid #ffc107;
        }
        
        .event-card.event-status-realizado {
            border-left: 4px solid #28a745;
        }
        
        .event-card.event-status-cancelado {
            border-left: 4px solid #dc3545;
        }
        
        .event-card.event-status-aguardando_aprovacao {
            border-left: 4px solid #17a2b8;
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .event-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .event-status {
            display: flex;
            align-items: center;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-pendente {
            background-color: #fff9e6;
            color: #ffc107;
        }
        
        .badge-realizado {
            background-color: #e6f4ea;
            color: #28a745;
        }
        
        .badge-cancelado {
            background-color: #f8e6e6;
            color: #dc3545;
        }
        
        .badge-aguardando_aprovacao {
            background-color: #e6f7fa;
            color: #17a2b8;
        }
        
        .event-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .event-datetime {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .event-location,
        .event-recurrence {
            font-size: 0.9rem;
            color: #666;
        }
        
        .event-description {
            font-size: 0.9rem;
            line-height: 1.5;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
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
        
        /* Ícones usando pseudo-elementos */
        .icon-calendar::before {
            content: "📅 ";
        }
        
        .icon-clock::before {
            content: "🕒 ";
        }
        
        .icon-location::before {
            content: "📍 ";
        }
        
        .icon-repeat::before {
            content: "🔄 ";
        }
        
        /* Estado vazio */
        .empty-state {
            text-align: center;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        footer {
            background-color: var(--agenda-color);
            color: white;
            padding: 1.5rem 0;
            text-align: center;
            margin-top: 2rem;
            opacity: 0.9;
        }
        
        /* Botões */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--agenda-color);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .header-content h1 {
                font-size: 1.5rem;
            }
            
            .events-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-input {
                min-width: auto;
            }
            
            .view-options {
                flex-wrap: wrap;
            }
            
            .view-option {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
            
            .event-datetime {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .calendar-container,
            .events-list-container {
                padding: 1rem;
            }
            
            .event-modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
    </style>
</head>
<body>
    <header>
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
    
    <div class="agenda-actions" style="margin-top: 20px; text-align: center;">
                    <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>&public=1" class="btn btn-primary" style="padding: 12px 24px; font-size: 1.1rem;">
                        <i class="fas fa-plus"></i> Criar Compromisso na Agenda
                    </a>
                </div>
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
            <h2 class="section-title">Compromissos</h2>
            
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