<?php
// Arquivo: app/views/shares/public.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="script.js" defer></script>
    <title><?= htmlspecialchars($agenda['title']) ?> - Agenda Pública</title>
    
    <style>
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Cabeçalho */
        header {
            background-color: <?= $agenda['color'] ?? '#004a8f' ?>;
            color: #fff;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .header-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .header-content h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header-content .description {
            font-style: italic;
            opacity: 0.9;
            max-width: 800px;
        }
        
        .owner-info {
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        /* Calendário */
        .calendar-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .calendar-title {
            margin: 0;
            font-size: 1.5rem;
            color: <?= $agenda['color'] ?? '#004a8f' ?>;
        }
        
        .calendar-navigation {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #ddd;
            color: #666;
        }
        
        .btn-outline:hover {
            background-color: #f5f5f5;
        }
        
        .calendar {
            border: 1px solid #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background-color: #f5f5f5;
            border-bottom: 1px solid #eee;
        }
        
        .weekday {
            padding: 0.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            color: #666;
        }
        
        .calendar-week {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border-bottom: 1px solid #eee;
        }
        
        .calendar-week:last-child {
            border-bottom: none;
        }
        
        .calendar-day {
            min-height: 100px;
            padding: 0.5rem;
            border-right: 1px solid #eee;
            position: relative;
        }
        
        .calendar-day:last-child {
            border-right: none;
        }
        
        .empty-day {
            background-color: #f9f9f9;
        }
        
        .day-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .day-number {
            font-weight: 600;
            color: #333;
        }
        
        .today .day-number {
            background-color: <?= $agenda['color'] ?? '#004a8f' ?>;
            color: #fff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .has-events {
            background-color: #f0f8ff;
        }
        
        .day-events {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        /* Eventos no calendário */
        .event {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            background-color: #e6f0fd;
            border-left: 3px solid <?= $agenda['color'] ?? '#004a8f' ?>;
        }
        
        .event-status-pendente {
            border-left-color: #ffc107;
            background-color: #fff9e6;
        }
        
        .event-status-realizado {
            border-left-color: #28a745;
            background-color: #e6f4ea;
        }
        
        .event-status-cancelado {
            border-left-color: #dc3545;
            background-color: #f8e6e6;
            text-decoration: line-through;
        }
        
        .event-status-aguardando_aprovacao {
            border-left-color: #17a2b8;
            background-color: #e6f7fa;
        }
        
        .event-time {
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .more-events {
            font-size: 0.8rem;
            text-align: center;
            color: #666;
            background-color: #f5f5f5;
            padding: 0.25rem;
            border-radius: 4px;
        }
        
        /* Lista de eventos */
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
            color: <?= $agenda['color'] ?? '#004a8f' ?>;
        }
        
        .events-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-select, .filter-input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-input {
            min-width: 250px;
        }
        
        .events-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        /* Cards de eventos */
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
        }
        
        .event-card.event-status-cancelado .event-title {
            text-decoration: line-through;
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
        
        .event-location, .event-recurrence {
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
        
        /* Ícones básicos usando pseudo-elementos */
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
        
        /* Rodapé */
        footer {
            text-align: center;
            padding: 2rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Estado vazio */
        .empty-state {
            text-align: center;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .empty-state p {
            margin-bottom: 1rem;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .calendar-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .calendar-navigation {
                width: 100%;
                justify-content: space-between;
            }
            
            .weekday {
                font-size: 0.8rem;
                padding: 0.25rem;
            }
            
            .calendar-day {
                min-height: 80px;
                font-size: 0.8rem;
            }
            
            .events-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-input {
                min-width: auto;
            }
            
            .event-datetime {
                flex-direction: column;
                gap: 0.25rem;
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
    
    <main class="container">
        <!-- Calendário -->
        <div class="calendar-container">
            <div class="calendar-header">
                <h2 class="calendar-title"><?= ucfirst($calendarData['monthName']) ?> <?= $calendarData['year'] ?></h2>
                <div class="calendar-navigation">
                    <a href="<?= BASE_URL ?>/public/public-agenda/<?= $agenda['public_hash'] ?>?month=<?= $calendarData['previousMonth'] ?>&year=<?= $calendarData['previousYear'] ?>" class="btn btn-outline">
                        &laquo; Mês Anterior
                    </a>
                    <a href="<?= BASE_URL ?>/public/public-agenda/<?= $agenda['public_hash'] ?>" class="btn btn-outline">
                        Mês Atual
                    </a>
                    <a href="<?= BASE_URL ?>/public/public-agenda/<?= $agenda['public_hash'] ?>?month=<?= $calendarData['nextMonth'] ?>&year=<?= $calendarData['nextYear'] ?>" class="btn btn-outline">
                        Próximo Mês &raquo;
                    </a>
                </div>
            </div>
            
            <div class="calendar">
                <div class="calendar-weekdays">
                    <div class="weekday">Dom</div>
                    <div class="weekday">Seg</div>
                    <div class="weekday">Ter</div>
                    <div class="weekday">Qua</div>
                    <div class="weekday">Qui</div>
                    <div class="weekday">Sex</div>
                    <div class="weekday">Sáb</div>
                </div>
                
                <?php foreach ($calendarData['weeks'] as $week): ?>
                    <div class="calendar-week">
                        <?php foreach ($week as $dayData): ?>
                            <?php if ($dayData['day'] === null): ?>
                                <div class="calendar-day empty-day"></div>
                            <?php else: ?>
                                <div class="calendar-day <?= count($dayData['compromissos']) > 0 ? 'has-events' : '' ?> <?= date('Y-m-d') == sprintf('%04d-%02d-%02d', $calendarData['year'], $calendarData['month'], $dayData['day']) ? 'today' : '' ?>">
                                    <div class="day-header">
                                        <span class="day-number"><?= $dayData['day'] ?></span>
                                    </div>
                                    
                                    <?php if (count($dayData['compromissos']) > 0): ?>
                                        <div class="day-events">
                                            <?php 
                                            // Limitar a exibição para os 3 primeiros eventos
                                            $displayEvents = array_slice($dayData['compromissos'], 0, 3);
                                            foreach ($displayEvents as $compromisso): 
                                                // Não mostrar compromissos cancelados na visualização pública
                                                if ($compromisso['status'] === 'cancelado') continue;
                                            ?>
                                                <div class="event event-status-<?= $compromisso['status'] ?>" title="<?= htmlspecialchars($compromisso['title']) ?>">
                                                    <span class="event-time">
                                                        <?= (new DateTime($compromisso['start_datetime']))->format('H:i') ?>
                                                    </span>
                                                    <span class="event-title">
                                                        <?= htmlspecialchars(mb_strimwidth($compromisso['title'], 0, 20, '...')) ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                            
                                            <?php 
                                            // Contar compromissos não cancelados
                                            $activeEvents = array_filter($dayData['compromissos'], function($comp) {
                                                return $comp['status'] !== 'cancelado';
                                            });
                                            
                                            if (count($activeEvents) > 3): 
                                            ?>
                                                <div class="more-events">
                                                    +<?= count($activeEvents) - 3 ?> mais
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
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
                </div>
                
                <div class="events-list">
                    <?php foreach ($allCompromissos as $compromisso): ?>
                        <?php 
                        // Pular compromissos cancelados
                        if ($compromisso['status'] === 'cancelado') continue;
                        
                        $startDate = new DateTime($compromisso['start_datetime']);
                        $endDate = new DateTime($compromisso['end_datetime']);
                        ?>
                        
                        <div class="event-card event-status-<?= $compromisso['status'] ?>" data-status="<?= $compromisso['status'] ?>" data-month="<?= $startDate->format('n') ?>" data-search="<?= htmlspecialchars(strtolower($compromisso['title'] . ' ' . $compromisso['description'] . ' ' . $compromisso['location'])) ?>">
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
    
    <footer>
        <div class="container">
            <p>Esta é uma visualização pública da agenda "<?= htmlspecialchars($agenda['title']) ?>".</p>
            <p>&copy; <?= date('Y') ?> - Sistema de Agendamento UFPR</p>
        </div>
    </footer>
    
    <script>
    // Filtros da lista de compromissos
   
    </script>
</body>
</html>