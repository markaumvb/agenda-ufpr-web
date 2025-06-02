<div class="page-header">
    <div class="header-container">
        <h1>Meus Compromissos</h1>
    </div>
</div>

<?php if (empty($compromissos)): ?>
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

    <!-- NOVA DIV DE AÇÕES EM MASSA -->
    <div class="bulk-actions-container" id="bulk-actions-container" style="display: none;">
        <div class="bulk-actions-header">
            <span id="selected-count">0 compromissos selecionados</span>
            <button id="clear-selection" class="btn btn-link btn-sm">Limpar seleção</button>
        </div>
        <div class="bulk-actions-buttons">
            <button id="bulk-approve" class="btn btn-success btn-sm">
                <i class="fas fa-check"></i> Aprovar Selecionados
            </button>
            <button id="bulk-reject" class="btn btn-danger btn-sm">
                <i class="fas fa-times"></i> Rejeitar Selecionados
            </button>
            <button id="select-all-visible" class="btn btn-primary btn-sm">
                <i class="fas fa-check-square"></i> Selecionar Todos Visíveis
            </button>
        </div>
    </div>

    <!-- Data Grid de Compromissos -->
    <div class="data-grid-container">
        <table class="data-grid" id="compromissos-table">
            <thead>
                <tr>
                    <!-- NOVA COLUNA DE CHECKBOX -->
                    <th class="col-checkbox">
                        <input type="checkbox" id="select-all-checkbox" class="bulk-checkbox-header">
                    </th>
                    <th class="col-agenda">Agenda</th>
                    <th class="col-title">Título</th>
                    <th class="col-date">Data</th>
                    <th class="col-time">Horário</th>
                    <th class="col-location">Local</th>
                    <th class="col-status">Status</th>
                    <th class="col-actions" style="width: 290px;"></th> 
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compromissos as $index => $compromisso): 
                    $startDate = new DateTime($compromisso['start_datetime']);
                    $endDate = new DateTime($compromisso['end_datetime']);
                    
                    // Obter agenda info da propriedade agenda_info do compromisso
                    $agendaInfo = $compromisso['agenda_info'] ?? null;
                    
                    // Adicionar classe para linhas zebradas
                    $rowClass = $index % 2 == 0 ? 'even-row' : 'odd-row';
                    
                    // Verificar se este compromisso pode ser selecionado para ações em massa
                    $canBulkAction = ($compromisso['status'] === 'aguardando_aprovacao' && $agendaInfo && isset($agendaInfo['is_owner']) && $agendaInfo['is_owner']);
                ?>
                    <tr class="compromisso-row <?= $rowClass ?> <?= $compromisso['status'] === 'cancelado' ? 'status-cancelled' : '' ?>" 
                        data-status="<?= $compromisso['status'] ?>"
                        data-agenda="<?= $compromisso['agenda_id'] ?>" 
                        data-date="<?= $startDate->format('Y-m-d') ?>"
                        data-id="<?= $compromisso['id'] ?>" 
                        data-search="<?= htmlspecialchars(strtolower($compromisso['title'] . ' ' . $compromisso['description'] . ' ' . $compromisso['location'])) ?>">
                        
                        <!-- NOVA COLUNA DE CHECKBOX -->
                        <td class="col-checkbox">
                            <?php if ($canBulkAction): ?>
                                <input type="checkbox" class="bulk-checkbox" value="<?= $compromisso['id'] ?>" data-status="<?= $compromisso['status'] ?>">
                            <?php endif; ?>
                        </td>
                        
                        <td class="col-agenda">
                            <div class="agenda-tag" style="background-color: <?= htmlspecialchars($agendaInfo ? $agendaInfo['color'] : '#888') ?>;">
                                <?= htmlspecialchars($agendaInfo ? $agendaInfo['title'] : 'Agenda') ?>
                            </div>
                        </td>
                        
                        <td class="col-title <?= $compromisso['status'] === 'cancelado' ? 'text-cancelled' : '' ?>">
                            <div class="title-content">
                                <div class="title-main"><?= htmlspecialchars($compromisso['title']) ?></div>
                                <div class="appointment-meta">
                                    <?php if ($compromisso['created_by'] == $_SESSION['user_id']): ?>
                                        <span class="badge badge-info badge-sm">Criado por você</span>
                                            <?php elseif (isset($compromisso['creator_name'])): ?>
                                                <span class="badge badge-primary badge-sm" 
                                                    <?php if (isset($compromisso['creator_email'])): ?>
                                                        title="E-mail: <?= htmlspecialchars($compromisso['creator_email']) ?>"
                                                    <?php endif; ?>>
                                                    Criado por <?= htmlspecialchars($compromisso['creator_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                    
                                    <?php if ($agendaInfo && $agendaInfo['user_id'] != $_SESSION['user_id']): ?>
                                        <span class="badge badge-secondary badge-sm">Agenda de <?= htmlspecialchars($agendaInfo['owner_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
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
                            <?php if ($compromisso['status'] === 'aguardando_aprovacao' && $agendaInfo && isset($agendaInfo['is_owner']) && $agendaInfo['is_owner']): ?>
                                <!-- Botões para compromissos aguardando aprovação (apenas para dono da agenda) -->
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-success approve-btn" data-id="<?= $compromisso['id'] ?>">
                                        <i class="fas fa-check"></i> Aprovar
                                    </button>
                                    <button class="btn btn-sm btn-danger reject-btn" data-id="<?= $compromisso['id'] ?>">
                                        <i class="fas fa-times"></i> Rejeitar
                                    </button>
                                </div>
                            <?php elseif ($compromisso['status'] === 'pendente'): ?>
                                <!-- Botões apenas para compromissos pendentes -->
                                <div class="action-buttons">
                                    <?php if (isset($agendaInfo['is_owner']) && $agendaInfo['is_owner'] || $compromisso['created_by'] == $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-warning cancel-btn" data-id="<?= $compromisso['id'] ?>">
                                            <i class="fas fa-ban"></i> Cancelar
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($agendaInfo['is_owner']) && ($agendaInfo['is_owner'] || isset($agendaInfo['can_edit']) && $agendaInfo['can_edit']) || 
                                            $compromisso['created_by'] == $_SESSION['user_id']): ?>
                                        <a href="<?= BASE_URL ?>/compromissos/edit?id=<?= $compromisso['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($agendaInfo['is_owner']) && $agendaInfo['is_owner']): ?>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $compromisso['id'] ?>">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Nenhuma ação para compromissos cancelados ou realizados -->
                                <!-- Deixar em branco conforme solicitado -->
                            <?php endif; ?>
                    </td>
                    </tr>
                    
                    <!-- Linha para descrição (expandida ao clicar) -->
                    <?php if (!empty($compromisso['description'])): ?>
                    <tr class="description-row <?= $rowClass ?>" id="desc-<?= $compromisso['id'] ?>" style="display: none;">
                        <td colspan="8" class="description-cell">
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
    <?php if (isset($startRecord)): ?>
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
    <?php endif; ?>
    
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
    
    <!-- NOVOS FORMULÁRIOS PARA AÇÕES EM MASSA -->
    <form id="bulkApproveForm" action="<?= BASE_URL ?>/meuscompromissos/bulk-approve" method="post" style="display: none;">
        <input type="hidden" name="ids" id="bulk-approve-ids">
    </form>
    
    <form id="bulkRejectForm" action="<?= BASE_URL ?>/meuscompromissos/bulk-reject" method="post" style="display: none;">
        <input type="hidden" name="ids" id="bulk-reject-ids">
    </form>
    
    <!-- Mensagem para quando nenhum compromisso corresponder aos filtros -->
    <div class="no-results" style="display: none;">
        <p>Nenhum compromisso corresponde aos filtros selecionados.</p>
        <button id="reset-filters" class="btn btn-primary">Limpar Filtros</button>
    </div>
<?php endif; ?>

<script src="<?= PUBLIC_URL ?>/app/assets/js/compromissos/meus-compromissos.js"></script>