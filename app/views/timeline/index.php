<?php
// app/views/timeline/index.php - Versão com depuração avançada
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

    <!-- Seção de Depuração -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informações de Depuração</h5>
        </div>
        <div class="card-body">
            <h6>Parâmetros recebidos:</h6>
            <ul>
                <li><strong>Data selecionada:</strong> <?= $date->format('Y-m-d') ?></li>
                <li><strong>Agenda ID selecionada:</strong> <?= isset($_GET['agenda_id']) ? htmlspecialchars($_GET['agenda_id']) : 'Todas' ?></li>
                <li><strong>Termo de pesquisa:</strong> <?= !empty($searchQuery) ? htmlspecialchars($searchQuery) : 'Nenhum' ?></li>
            </ul>
            
            <h6>Agendas públicas disponíveis (<?= count($publicAgendas) ?>):</h6>
            <?php if (empty($publicAgendas)): ?>
                <div class="alert alert-warning">Nenhuma agenda pública encontrada! Este é o problema.</div>
            <?php else: ?>
                <ul>
                <?php foreach ($publicAgendas as $idx => $agenda): ?>
                    <li>
                        ID: <?= $agenda['id'] ?> - 
                        Título: <?= htmlspecialchars($agenda['title']) ?> - 
                        Dono: <?= htmlspecialchars($agenda['owner_name'] ?? 'Desconhecido') ?>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <h6>Compromissos encontrados (<?= count($allEvents) ?>):</h6>
            <?php if (empty($allEvents)): ?>
                <div class="alert alert-danger">Nenhum compromisso encontrado para esta data nas agendas públicas.</div>
            <?php else: ?>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Agenda</th>
                            <th>Status</th>
                            <th>Horário</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allEvents as $event): ?>
                        <tr>
                            <td><?= $event['id'] ?></td>
                            <td><?= htmlspecialchars($event['title']) ?></td>
                            <td><?= htmlspecialchars($event['agenda_info']['title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($event['status']) ?></td>
                            <td>
                                <?= (new DateTime($event['start_datetime']))->format('H:i') ?> - 
                                <?= (new DateTime($event['end_datetime']))->format('H:i') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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

// Debug info
console.log('Total de eventos disponíveis:', window.timelineEvents.length);
</script>

<!-- Carregar o script externo da timeline -->
<script src="<?= PUBLIC_URL ?>/app/assets/js/timeline.js"></script>