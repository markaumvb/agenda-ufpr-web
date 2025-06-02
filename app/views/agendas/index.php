<div class="page-header">
    <div class="header-container">
        <h1>Minhas Agendas</h1>
        <div class="header-actions">
            <a href="<?= PUBLIC_URL ?>/agendas/new" class="btn btn-primary">Nova Agenda</a>
        </div>
    </div>
    
    <div class="search-box">
        <form action="<?= PUBLIC_URL ?>/agendas" method="get" class="search-form">
            <input type="text" name="search" placeholder="Pesquisar agendas..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <label class="checkbox-container" style="margin-left: 10px;">
                <input type="checkbox" name="include_inactive" value="1" 
                       <?= isset($_GET['include_inactive']) && $_GET['include_inactive'] == 1 ? 'checked' : '' ?>>
                <span class="checkmark"></span>
                Incluir agendas desativadas
            </label>
            <button type="submit" class="btn btn-secondary">Buscar</button>
            <?php if ((isset($_GET['search']) && !empty($_GET['search'])) || (isset($_GET['include_inactive']) && $_GET['include_inactive'] == 1)): ?>
                <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-link">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Listagem de agendas em cards -->
<?php if (empty($agendas)): ?>
    <div class="empty-state">
        <p>Nenhuma agenda encontrada.</p>
        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <p>Tente uma busca diferente ou <a href="<?= PUBLIC_URL ?>/agendas">veja todas as agendas</a>.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="agenda-grid">
        <?php 
        unset($agenda);
        
        // Itere sobre o array usando o índice para evitar problemas de referência
        for ($i = 0; $i < count($agendas); $i++): 
            $agenda = $agendas[$i];
        ?>
            <div class="agenda-card" style="border-left: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                <div class="agenda-card-header">
                    <h2 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h2>
                    <div class="agenda-visibility">
                        <?php if ($agenda['is_public']): ?>
                            <span class="badge badge-success">Pública</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Privada</span>
                        <?php endif; ?>
                        
                        <?php if (!$agenda['is_active']): ?>
                            <span class="badge badge-danger">Desativada</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="agenda-card-body">
                    <?php if (!empty($agenda['description'])): ?>
                        <div class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></div>
                    <?php else: ?>
                        <div class="agenda-description text-muted">Sem descrição</div>
                    <?php endif; ?>
                    
                    <div class="agenda-stats">
                        <div class="stat">
                            <span class="stat-value"><?= $agenda['compromissos']['realizados'] ?? 0 ?></span>
                            <span class="stat-label">Realizados</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?= $agenda['compromissos']['cancelados'] ?? 0 ?></span>
                            <span class="stat-label">Cancelados</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?= $agenda['compromissos']['pendentes'] ?? 0 ?></span>
                            <span class="stat-label">Pendentes</span>
                        </div>
                        <div class="stat">
                            <span class="stat-value"><?= $agenda['compromissos']['aguardando_aprovacao'] ?? 0 ?></span>
                            <span class="stat-label">Aguardando</span>
                        </div>
                    </div>
                </div>
                
                <div class="agenda-card-footer">
                    <div class="agenda-actions">
                        <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-calendar-alt"></i> Ver Compromissos
                        </a>
                        
                        <a href="<?= PUBLIC_URL ?>/shares?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-share"></i> Compartilhar
                        </a>
                        
                        <a href="<?= PUBLIC_URL ?>/agendas/edit?id=<?= $agenda['id'] ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        
                        <?php if (isset($agenda['can_be_deleted']) && $agenda['can_be_deleted']): ?>
                            <form action="<?= PUBLIC_URL ?>/agendas/delete" method="post" class="delete-form" 
                                onsubmit="return confirm('Tem certeza que deseja excluir esta agenda?');">
                                <input type="hidden" name="id" value="<?= $agenda['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa-solid fa-trash"></i> Excluir
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-sm btn-danger disabled" title="Não é possível excluir esta agenda pois possui compromissos pendentes ou aguardando aprovação" disabled>
                                <i class="fa-solid fa-trash"></i> Excluir
                            </button>
                        <?php endif; ?>
                        
                        <form action="<?= PUBLIC_URL ?>/agendas/toggle-active" method="post" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $agenda['id'] ?>">
                            <input type="hidden" name="is_active" value="<?= $agenda['is_active'] ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-sm <?= $agenda['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                <?= $agenda['is_active'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>
    
    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            Mostrando <?= count($agendas) ?> de <?= $totalAgendas ?> agendas
        </div>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= PUBLIC_URL ?>/agendas?page=1<?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= isset($_GET['include_inactive']) && $_GET['include_inactive'] ? '&include_inactive=1' : '' ?>" class="pagination-link first">
                    &laquo; Primeira
                </a>
                <a href="<?= PUBLIC_URL ?>/agendas?page=<?= $page - 1 ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= isset($_GET['include_inactive']) && $_GET['include_inactive'] ? '&include_inactive=1' : '' ?>" class="pagination-link prev">
                    &lsaquo; Anterior
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="<?= PUBLIC_URL ?>/agendas?page=<?= $i ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= isset($_GET['include_inactive']) && $_GET['include_inactive'] ? '&include_inactive=1' : '' ?>" 
                   class="pagination-link <?= $i == $page ? 'current' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="<?= PUBLIC_URL ?>/agendas?page=<?= $page + 1 ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= isset($_GET['include_inactive']) && $_GET['include_inactive'] ? '&include_inactive=1' : '' ?>" class="pagination-link next">
                    Próxima &rsaquo;
                </a>
                <a href="<?= PUBLIC_URL ?>/agendas?page=<?= $totalPages ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?><?= isset($_GET['include_inactive']) && $_GET['include_inactive'] ? '&include_inactive=1' : '' ?>" class="pagination-link last">
                    Última &raquo;
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<script src="<?= PUBLIC_URL ?>/app/assets/js/agenda/index.js"></script>
<script src="<?= PUBLIC_URL ?>/app/assets/js/agenda/common.js"></script>