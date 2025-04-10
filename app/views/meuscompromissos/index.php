<div class="page-header">
    <div class="header-container">
        <h1>Meus Compromissos</h1>
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/agendas" class="btn btn-secondary">Minhas Agendas</a>
        </div>
    </div>
</div>

<?php if (empty($agendasWithCompromissos) && empty($compromissos)): ?>
    <div class="empty-state">
        <p>Você não possui compromissos em nenhuma agenda.</p>
        <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">Ir para Minhas Agendas</a>
    </div>
<?php else: ?>
    <!-- Filtros -->
    <div class="filter-container">
        <div class="filter-group">
            <label for="filter-agenda">Agenda:</label>
            <select id="filter-agenda" class="filter-select">
                <option value="all">Todas as Agendas</option>
                <?php foreach ($agendas as $agenda): ?>
                    <option value="<?= $agenda['id'] ?>"><?= htmlspecialchars($agenda['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
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
            <label for="filter-period">Período:</label>
            <select id="filter-period" class="filter-select">
                <option value="all">Todos</option>
                <option value="today">Hoje</option>
                <option value="tomorrow">Amanhã</option>
                <option value="week">Esta Semana</option>
                <option value="month">Este Mês</option>
                <option value="past">Passados</option>
            </select>
        </div>
        
        <div class="filter-group">
            <input type="text" id="filter-search" placeholder="Buscar compromissos..." class="filter-input">
        </div>
        
        <button id="clear-filters" class="btn btn-secondary btn-sm">Limpar Filtros</button>
    </div>

    <!-- Data Grid de Compromissos -->
    <div class="data-grid-container">
        <table class="data-grid" id="compromissos-table">
            <thead>
                <tr>
                    <th class="col-agenda">Agenda</th>
                    <th class="col-title">Título</th>
                    <th class="col-date">Data</th>
                    <th class="col-time">Horário</th>
                    <th class="col-location">Local</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compromissos as $index => $compromisso): 
                    $startDate = new DateTime($compromisso['start_datetime']);
                    $endDate = new DateTime($compromisso['end_datetime']);
                    
                    // Obter a agenda relacionada
                    $agendaInfo = null;
                    foreach ($agendas as $agenda) {
                        if ($agenda['id'] == $compromisso['agenda_id']) {
                            $agendaInfo = $agenda;
                            break;
                        }
                    }
                ?>
                    <tr class="compromisso-row <?= $compromisso['status'] === 'cancelado' ? 'status-cancelled' : '' ?>" 
                        data-status="<?= $compromisso['status'] ?>"
                        data-agenda="<?= $compromisso['agenda_id'] ?>" 
                        data-date="<?= $startDate->format('Y-m-d') ?>"
                        data-id="<?= $compromisso['id'] ?>" 
                        data-search="<?= htmlspecialchars(strtolower($compromisso['title'] . ' ' . $compromisso['description'] . ' ' . $compromisso['location'])) ?>">
                        
                        <td class="col-agenda">
                            <div class="agenda-tag" style="background-color: <?= htmlspecialchars($agendaInfo ? $agendaInfo['color'] : '#888') ?>;">
                                <?= htmlspecialchars($agendaInfo ? $agendaInfo['title'] : 'Agenda') ?>
                            </div>
                        </td>
                        
                        <td class="col-title <?= $compromisso['status'] === 'cancelado' ? 'text-cancelled' : '' ?>">
                            <?= htmlspecialchars($compromisso['title']) ?>
                            <?php if ($compromisso['created_by'] == $_SESSION['user_id']): ?>
                                <span class="badge badge-info badge-sm">Criado por você</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="col-date">
                            <?php if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')): ?>
                                <?= $startDate->format('d/m/Y') ?>
                            <?php else: ?>
                                <?= $startDate->format('d/m/Y') ?> a <?= $endDate->format('d/m/Y') ?>
                            <?php endif; ?>
                        </td>
                        
                        <td class="col-time">
                            <?= $startDate->format('H:i') ?> às <?= $endDate->format('H:i') ?>
                        </td>
                        
                        <td class="col-location">
                            <?= htmlspecialchars($compromisso['location'] ?: '-') ?>
                        </td>
                        
                        <td class="col-status">
                            <span class="badge badge-<?= $compromisso['status'] ?>">
                                <?php
                                $statusLabels = [
                                    'pendente' => 'Pendente',
                                    'realizado' => 'Realizado',
                                    'cancelado' => 'Cancelado',
                                    'aguardando_aprovacao' => 'Aguardando Aprovação'
                                ];
                                echo $statusLabels[$compromisso['status']] ?? $compromisso['status'];
                                ?>
                            </span>
                        </td>
                        
                        <td class="col-actions">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button">
                                    Ações
                                </button>
                                <div class="dropdown-menu">
                                    <a href="<?= BASE_URL ?>/compromissos/view?id=<?= $compromisso['id'] ?>" class="dropdown-item">
                                        Ver Detalhes
                                    </a>
                                    
                                    <?php if ($compromisso['status'] === 'aguardando_aprovacao' && $agendaInfo && $agendaInfo['is_owner']): ?>
                                        <a href="#" class="dropdown-item approve-btn" data-id="<?= $compromisso['id'] ?>">
                                            Aprovar
                                        </a>
                                        <a href="#" class="dropdown-item reject-btn" data-id="<?= $compromisso['id'] ?>">
                                            Rejeitar
                                        </a>
                                    <?php else: ?>
                                        <?php if ($compromisso['status'] !== 'cancelado' && 
                                              ($agendaInfo && $agendaInfo['is_owner'] || $compromisso['created_by'] == $_SESSION['user_id'])): ?>
                                            <a href="#" class="dropdown-item cancel-btn" data-id="<?= $compromisso['id'] ?>">
                                                Cancelar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($compromisso['status'] !== 'cancelado' && 
                                              ($agendaInfo && ($agendaInfo['is_owner'] || $agendaInfo['can_edit']) || 
                                               $compromisso['created_by'] == $_SESSION['user_id'])): ?>
                                            <a href="<?= BASE_URL ?>/compromissos/edit?id=<?= $compromisso['id'] ?>" class="dropdown-item">
                                                Editar
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($agendaInfo && $agendaInfo['is_owner']): ?>
                                            <a href="#" class="dropdown-item delete-btn" data-id="<?= $compromisso['id'] ?>">
                                                Excluir
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Linha para descrição (expandida ao clicar) -->
                    <?php if (!empty($compromisso['description'])): ?>
                    <tr class="description-row" id="desc-<?= $compromisso['id'] ?>" style="display: none;">
                        <td colspan="7" class="description-cell">
                            <div class="description-content">
                                <strong>Descrição:</strong>
                                <div><?= nl2br(htmlspecialchars($compromisso['description'])) ?></div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginação -->
    <div class="pagination-container">
        <div class="pagination-info">
            Exibindo <?= $startRecord ?> a <?= $endRecord ?> de <?= $totalRecords ?> compromissos
        </div>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="<?= BASE_URL ?>/meuscompromissos?page=1<?= $queryParams ?>" class="pagination-link first">
                    &laquo; Primeira
                </a>
                <a href="<?= BASE_URL ?>/meuscompromissos?page=<?= $currentPage - 1 ?><?= $queryParams ?>" class="pagination-link prev">
                    &lsaquo; Anterior
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <a href="<?= BASE_URL ?>/meuscompromissos?page=<?= $i ?><?= $queryParams ?>" 
                   class="pagination-link <?= $i == $currentPage ? 'current' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= BASE_URL ?>/meuscompromissos?page=<?= $currentPage + 1 ?><?= $queryParams ?>" class="pagination-link next">
                    Próxima &rsaquo;
                </a>
                <a href="<?= BASE_URL ?>/meuscompromissos?page=<?= $totalPages ?><?= $queryParams ?>" class="pagination-link last">
                    Última &raquo;
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Formulários para ações via POST -->
    <form id="cancelForm" action="<?= BASE_URL ?>/meuscompromissos/cancel" method="post" style="display: none;">
        <input type="hidden" name="id" id="cancel-id">
    </form>
    
    <form id="approveForm" action="<?= BASE_URL ?>/meuscompromissos/approve" method="post" style="display: none;">
        <input type="hidden" name="id" id="approve-id">
    </form>
    
    <form id="rejectForm" action="<?= BASE_URL ?>/meuscompromissos/reject" method="post" style="display: none;">
        <input type="hidden" name="id" id="reject-id">
    </form>
    
    <form id="deleteForm" action="<?= BASE_URL ?>/compromissos/delete" method="post" style="display: none;">
        <input type="hidden" name="id" id="delete-id">
    </form>
    
    <!-- Mensagem para quando nenhum compromisso corresponder aos filtros -->
    <div class="no-results" style="display: none;">
        <p>Nenhum compromisso corresponde aos filtros selecionados.</p>
        <button id="reset-filters" class="btn btn-primary">Limpar Filtros</button>
    </div>
    <script src="<?= PUBLIC_URL ?>/assets/js/compromissos/meus-compromissos.js"></script>
<?php endif; ?>