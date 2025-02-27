<?php
// Arquivo: app/views/shares/public.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($agenda['title']) ?> - Agenda Pública</title>
    <link rel="stylesheet" href="<?= PUBLIC_URL ?>/assets/css/shares/public.css">
    <script src="<?= PUBLIC_URL ?>/assets/js/shares/public.js" defer></script>
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
        <!-- Calendário -->
        <div class="calendar-container public-view">
    <div class="calendar-header">
        <h2 class="calendar-title" style="color: <?= $agenda['color'] ?? '#004a8f' ?>;"><?= ucfirst($calendarData['monthName']) ?> <?= $calendarData['year'] ?></h2>
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
                <?php foreach ($week as $dayIndex => $dayData): ?>
                    <?php if ($dayData['day'] === null): ?>
                        <div class="calendar-day empty-day"></div>
                    <?php else: ?>
                        <?php 
                        // Determinar se o dia tem eventos, filtrando eventos cancelados na visualização pública
                        $activeEvents = array_filter($dayData['compromissos'], function($comp) {
                            return $comp['status'] !== 'cancelado';
                        });
                        $hasEvents = !empty($activeEvents);
                        $isToday = date('Y-m-d') == sprintf('%04d-%02d-%02d', $calendarData['year'], $calendarData['month'], $dayData['day']);
                        $dayClasses = ['calendar-day'];
                        if ($hasEvents) $dayClasses[] = 'has-events';
                        if ($isToday) $dayClasses[] = 'today';
                        ?>
                        <div class="<?= implode(' ', $dayClasses) ?>">
                            <div class="day-header">
                                <span class="day-number" style="<?= $isToday ? 'background-color:' . ($agenda['color'] ?? '#004a8f') . ';' : '' ?>"><?= $dayData['day'] ?></span>
                            </div>
                            
                            <?php if ($hasEvents): ?>
                                <div class="day-events">
                                    <?php 
                                    // Limitar a exibição para os 3 primeiros eventos ativos
                                    $displayEvents = array_slice($activeEvents, 0, 3);
                                    foreach ($displayEvents as $compromisso): 
                                    ?>
                                        <div class="event event-status-<?= $compromisso['status'] ?>" style="border-left-color: <?= $agenda['color'] ?? '#004a8f' ?>;">
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
                    
                    <footer>
                        <div class="container">
                            <p>Esta é uma visualização pública da agenda "<?= htmlspecialchars($agenda['title']) ?>".</p>
                            <p>&copy; <?= date('Y') ?> - Sistema de Agendamento UFPR</p>
                        </div>
                    </footer>
                    </body>
                    </html>