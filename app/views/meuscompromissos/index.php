<div class="page-header">
    <div class="header-container">
        <h1>Meus Compromissos</h1>
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/agendas" class="btn btn-secondary">Minhas Agendas</a>
        </div>
    </div>
</div>

<?php if (empty($agendasWithCompromissos)): ?>
    <div class="empty-state">
        <p>Você não possui compromissos em nenhuma agenda.</p>
        <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">Ir para Minhas Agendas</a>
    </div>
<?php else: ?>
    <!-- Filtros -->
    <div class="filter-container">
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
            <input type="text" id="filter-search" placeholder="Buscar compromissos..." class="filter-input">
        </div>
        
        <button id="clear-filters" class="btn btn-secondary btn-sm">Limpar Filtros</button>
    </div>

    <!-- Agendas com compromissos -->
    <?php foreach ($agendasWithCompromissos as $agenda): ?>
        <div class="agenda-section" style="border-left: 4px solid <?= $agenda['color'] ?>">
            <div class="agenda-header">
                <h2 class="agenda-title">
                    <?= htmlspecialchars($agenda['title']) ?>
                    <?php if ($agenda['is_owner']): ?>
                        <span class="badge badge-primary">Sua agenda</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">COMPARTILHADA</span>
                    <?php endif; ?>
                    
                    <?php if ($agenda['is_public']): ?>
                        <span class="badge badge-success">PÚBLICA</span>
                    <?php endif; ?>
                </h2>
                
                <div class="agenda-actions">
                    <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-outline">
                        Ver Calendário
                    </a>
                    
                    <?php if ($agenda['is_owner'] || $agenda['can_edit']): ?>
                        <a href="<?= BASE_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                            Novo Compromisso
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="compromissos-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Local</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agenda['compromissos'] as $compromisso): 
                            $startDate = new DateTime($compromisso['start_datetime']);
                            $endDate = new DateTime($compromisso['end_datetime']);
                        ?>
                            <tr class="compromisso-row" 
                                data-status="<?= $compromisso['status'] ?>"
                                data-id="<?= $compromisso['id'] ?>"
                                data-search="<?= htmlspecialchars(strtolower($compromisso['title'] . ' ' . $compromisso['description'] . ' ' . $compromisso['location'])) ?>">
                                
                                <td class="compromisso-title <?= $compromisso['status'] === 'cancelado' ? 'text-cancelled' : '' ?>">
                                    <?= htmlspecialchars($compromisso['title']) ?>
                                    <?php if (isset($compromisso['created_by_current_user']) && $compromisso['created_by_current_user']): ?>
                                        <span class="badge badge-info">CRIADO POR VOCÊ</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')): ?>
                                        <?= $startDate->format('d/m/Y') ?>
                                    <?php else: ?>
                                        <?= $startDate->format('d/m/Y') ?> até <?= $endDate->format('d/m/Y') ?>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?= $startDate->format('H:i') ?> às <?= $endDate->format('H:i') ?>
                                </td>
                                
                                <td>
                                    <?= htmlspecialchars($compromisso['location'] ?: '-') ?>
                                </td>
                                
                                <td>
                                    <span class="badge badge-<?= $compromisso['status'] ?>">
                                        <?php
                                        $statusLabels = [
                                            'pendente' => 'Pendente',
                                            'realizado' => 'Realizado',
                                            'cancelado' => 'CANCELADO',
                                            'aguardando_aprovacao' => 'Aguardando Aprovação'
                                        ];
                                        echo $statusLabels[$compromisso['status']] ?? $compromisso['status'];
                                        ?>
                                    </span>
                                </td>
                                
                                <td class="actions-column">
                                    <?php if ($compromisso['status'] === 'aguardando_aprovacao' && $agenda['is_owner']): ?>
                                        <!-- Opções de aprovação/rejeição -->
                                        <div class="action-buttons">
                                            <form action="<?= BASE_URL ?>/meuscompromissos/approve" method="post" class="action-form">
                                                <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success" title="Aprovar compromisso">
                                                    Aprovar
                                                </button>
                                            </form>
                                            
                                            <form action="<?= BASE_URL ?>/meuscompromissos/reject" method="post" class="action-form">
                                                <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Rejeitar compromisso">
                                                    Rejeitar
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <!-- Opções regulares -->
                                        <div class="action-buttons">
                                            <?php if ($compromisso['status'] !== 'cancelado' && 
                                                    ($agenda['is_owner'] || 
                                                    (isset($compromisso['created_by_current_user']) && $compromisso['created_by_current_user']))): ?>
                                                <form action="<?= BASE_URL ?>/meuscompromissos/cancel" method="post" class="action-form">
                                                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Cancelar compromisso">
                                                        Cancelar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($compromisso['status'] !== 'cancelado' && 
                                                    ($agenda['is_owner'] || $agenda['can_edit'] || 
                                                    (isset($compromisso['created_by_current_user']) && $compromisso['created_by_current_user']))): ?>
                                                <a href="<?= BASE_URL ?>/compromissos/edit?id=<?= $compromisso['id'] ?>" class="btn btn-sm btn-secondary" title="Editar compromisso">
                                                    Editar
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($agenda['is_owner']): ?>
                                                <form action="<?= BASE_URL ?>/compromissos/delete" method="post" class="action-form" onsubmit="return confirm('Tem certeza que deseja excluir este compromisso?');">
                                                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir compromisso">
                                                        Excluir
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <?php if (!empty($compromisso['description'])): ?>
                            <tr class="description-row" id="desc-<?= $compromisso['id'] ?>">
                                <td colspan="6" class="description-cell">
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
        </div>
    <?php endforeach; ?>
    
    <!-- Mensagem para nenhum resultado -->
    <div class="no-results">
        <p>Nenhum compromisso corresponde aos filtros selecionados.</p>
        <button id="reset-filters" class="btn btn-primary">Limpar Filtros</button>
    </div>
<?php endif; ?>

<script src="<?= PUBLIC_URL ?>/assets/js/compromissos/tabela.js"></script>