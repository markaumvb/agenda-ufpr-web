<?php
// Arquivo: app/views/compromissos/index.php
?>

<div class="page-header">
<div class="header-container">
    <h1><?= htmlspecialchars($agenda['title']) ?></h1>
    <div class="header-actions">
        <?php if ($isOwner || (isset($agenda['can_edit']) && $agenda['can_edit'])): ?>
            <?php if ($agenda['is_active']): ?>
                <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-primary">Novo Compromisso</a>
            <?php else: ?>
                <button class="btn btn-primary" disabled title="Agenda desativada">Novo Compromisso</button>
            <?php endif; ?>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-secondary">Voltar para Agendas</a>
    </div>
</div>
    
    <div class="agenda-meta">
        <span class="badge <?= $agenda['is_public'] ? 'badge-success' : 'badge-secondary' ?>">
            <?= $agenda['is_public'] ? 'Agenda Pública' : 'Agenda Privada' ?>
        </span>
        <?php if (!$isOwner): ?>
            <span class="agenda-owner">Proprietário: <?= htmlspecialchars($agenda['user_name'] ?? 'Usuário') ?></span>
        <?php endif; ?>
    </div>
        <?php if (!$agenda['is_active']): ?>
        <div class="alert alert-warning">
            <strong>Atenção:</strong> Esta agenda está desativada. Não é possível criar novos compromissos.
        </div>
    <?php endif; ?>
</div>

<!-- Opções de visualização do calendário -->
<div class="view-options">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline view-option" data-view="dayGridMonth">Mês</button>
        <button type="button" class="btn btn-outline view-option" data-view="timeGridWeek">Semana</button>
        <button type="button" class="btn btn-outline view-option" data-view="timeGridDay">Dia</button>
        <button type="button" class="btn btn-outline view-option" data-view="listWeek">Lista</button>
    </div>
</div>

<!-- FullCalendar Container -->
<div class="calendar-container" data-agenda-id="<?= $agenda['id'] ?>">
    <div id="calendar"></div>
</div>

<!-- Lista de Compromissos Filtrados -->
<div class="events-list-container">
    <h2 class="section-title">Compromissos</h2>
    
    <?php if (empty($allCompromissos)): ?>
        <div class="empty-state">
            <p>Nenhum compromisso encontrado nesta agenda.</p>
            <?php if ($isOwner || (isset($agenda['can_edit']) && $agenda['can_edit'])): ?>
                <p>Clique em "Novo Compromisso" para adicionar seu primeiro compromisso.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="events-filters">
            <div class="filter-group">
                <label for="filter-status">Status:</label>
                <select id="filter-status" class="filter-select">
                    <option value="all">Todos</option>
                    <option value="pendente">Pendentes</option>
                    <option value="realizado">Realizados</option>
                    <option value="cancelado">Cancelados</option>
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
                $startDate = new DateTime($compromisso['start_datetime']);
                $endDate = new DateTime($compromisso['end_datetime']);
                ?>
                
                <div class="event-card event-status-<?= $compromisso['status'] ?>" 
                     data-status="<?= $compromisso['status'] ?>" 
                     data-month="<?= $startDate->format('n') ?>" 
                     data-date="<?= $startDate->format('Y-m-d') ?>"
                     data-id="<?= $compromisso['id'] ?>"
                     data-search="<?= htmlspecialchars(strtolower($compromisso['title'] . ' ' . $compromisso['description'] . ' ' . $compromisso['location'])) ?>">
                    <div class="event-header">
                        <h3 class="event-title"><?= htmlspecialchars($compromisso['title']) ?></h3>
                        <div class="event-status">
                            <span class="badge badge-<?= $compromisso['status'] ?>">
                                <?php
                                $statusLabels = [
                                    'pendente' => 'Pendente',
                                    'realizado' => 'Realizado',
                                    'cancelado' => 'Cancelado',
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
                    
                    <?php if ($isOwner || (isset($agenda['can_edit']) && $agenda['can_edit'])): ?>
                        <div class="event-actions">
                            <?php if ($compromisso['status'] !== 'realizado'): ?>
                                <form action="<?= PUBLIC_URL ?>/compromissos/change-status" method="post" class="status-form">
                                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                    <input type="hidden" name="status" value="realizado">
                                    <button type="submit" class="btn btn-sm btn-success" title="Marcar como realizado">
                                        <i class="icon-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($compromisso['status'] !== 'cancelado'): ?>
                                <form action="<?= PUBLIC_URL ?>/compromissos/change-status" method="post" class="status-form">
                                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                    <input type="hidden" name="status" value="cancelado">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Cancelar compromisso">
                                        <i class="icon-cancel"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="<?= PUBLIC_URL ?>/compromissos/edit?id=<?= $compromisso['id'] ?>" class="btn btn-sm btn-secondary" title="Editar compromisso">
                                <i class="icon-edit"></i>
                            </a>
                            
                            <form action="<?= PUBLIC_URL ?>/compromissos/delete" method="post" class="delete-form" onsubmit="return confirm('Tem certeza que deseja excluir este compromisso?');">
                                <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir compromisso">
                                    <i class="icon-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para detalhe de eventos -->
<div id="event-modal" class="event-modal" style="display: none;">
    <div class="event-modal-content">
        <span class="event-modal-close">&times;</span>
        <div id="event-modal-body"></div>
    </div>
</div>

<!-- Incluir o novo JavaScript do calendário -->
<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/fullcalendar.js"></script>

<!-- Scripts originais para compatibilidade com filtros existentes -->
<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/index.js"></script>