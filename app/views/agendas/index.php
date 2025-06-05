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
                    <?= isset($_GET['include_inactive']) && $_GET['include_inactive'] == '1' ? 'checked' : '' ?>>
                <span class="checkmark"></span>
                Incluir agendas desativadas
            </label>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Buscar
            </button>
            <?php if ((isset($_GET['search']) && !empty($_GET['search'])) || (isset($_GET['include_inactive']) && $_GET['include_inactive'] == 1)): ?>
                <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpar
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- ADICIONADO: Informação sobre resultados da busca -->
    <?php if (!empty($_GET['search'])): ?>
        <div class="search-info">
            <i class="fas fa-search"></i> 
            Resultados para "<strong><?= htmlspecialchars($_GET['search']) ?></strong>": 
            <?= isset($paginationData) ? $paginationData['total_items'] : count($agendas) ?> agenda(s) encontrada(s)
        </div>
    <?php endif; ?>
</div>

<!-- Listagem de agendas em cards -->
<?php if (empty($agendas)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <?php if (!empty($_GET['search'])): ?>
            <h3>Nenhuma agenda encontrada</h3>
            <p>Não foi possível encontrar agendas com o termo "<strong><?= htmlspecialchars($_GET['search']) ?></strong>".</p>
            <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Ver Todas as Agendas
            </a>
        <?php else: ?>
            <h3>Nenhuma agenda encontrada</h3>
            <p>Você ainda não criou nenhuma agenda.</p>
            <a href="<?= PUBLIC_URL ?>/agendas/new" class="btn btn-primary">
                <i class="fas fa-plus"></i> Criar Nova Agenda
            </a>
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

                <form action="<?= PUBLIC_URL ?>/agendas/delete" 
                    method="post" 
                    class="delete-form" 
                    data-agenda-title="<?= htmlspecialchars($agenda['title']) ?>"
                    style="display: inline;">
                    <input type="hidden" name="id" value="<?= $agenda['id'] ?>">
                    <button type="submit" 
                            class="btn btn-sm btn-danger delete-btn"
                            data-agenda-id="<?= $agenda['id'] ?>"
                            title="Excluir agenda">
                        <i class="fa-solid fa-trash"></i> Excluir
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-sm btn-danger disabled" 
                        title="Não é possível excluir esta agenda pois possui compromissos realizados, cancelados ou aguardando aprovação" 
                        disabled>
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
    
    <!-- CORRIGIDO: Paginação padronizada igual à home page -->
    <?php if (isset($paginationData) && $paginationData['total_pages'] > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                <i class="fas fa-chart-bar"></i>
                Exibindo <strong><?= $paginationData['start_item'] ?></strong> a 
                <strong><?= $paginationData['end_item'] ?></strong> de 
                <strong><?= $paginationData['total_items'] ?></strong> agendas
                <?php if (!empty($paginationData['search'])): ?>
                    encontradas para "<strong><?= htmlspecialchars($paginationData['search']) ?></strong>"
                <?php endif; ?>
            </div>
            
            <nav class="pagination">
                <?php
                $baseUrl = PUBLIC_URL . '/agendas';
                $searchParam = !empty($paginationData['search']) ? '&search=' . urlencode($paginationData['search']) : '';
                $inactiveParam = isset($_GET['include_inactive']) && $_GET['include_inactive'] == '1' ? '&include_inactive=1' : '';
                $queryParams = $searchParam . $inactiveParam;
                
                // Botão "Anterior"
                if ($paginationData['current_page'] > 1):
                    $prevPage = $paginationData['current_page'] - 1;
                    $prevUrl = $baseUrl . '?page=' . $prevPage . $queryParams;
                ?>
                    <a href="<?= $prevUrl ?>" class="pagination-link prev">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php else: ?>
                    <span class="pagination-link prev disabled">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </span>
                <?php endif; ?>
                
                <?php
                // Páginas numeradas
                $start = max(1, $paginationData['current_page'] - 2);
                $end = min($paginationData['total_pages'], $paginationData['current_page'] + 2);
                
                // Primeira página
                if ($start > 1): ?>
                    <a href="<?= $baseUrl ?>?page=1<?= $queryParams ?>" class="pagination-link">1</a>
                    <?php if ($start > 2): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php
                // Páginas do range atual
                for ($i = $start; $i <= $end; $i++):
                    if ($i == $paginationData['current_page']):
                ?>
                        <span class="pagination-link current"><?= $i ?></span>
                <?php else: ?>
                        <a href="<?= $baseUrl ?>?page=<?= $i ?><?= $queryParams ?>" class="pagination-link"><?= $i ?></a>
                <?php 
                    endif;
                endfor; 
                ?>
                
                <?php
                // Última página
                if ($end < $paginationData['total_pages']): ?>
                    <?php if ($end < $paginationData['total_pages'] - 1): ?>
                        <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?>?page=<?= $paginationData['total_pages'] ?><?= $queryParams ?>" class="pagination-link"><?= $paginationData['total_pages'] ?></a>
                <?php endif; ?>
                
                <?php
                // Botão "Próximo"
                if ($paginationData['current_page'] < $paginationData['total_pages']):
                    $nextPage = $paginationData['current_page'] + 1;
                    $nextUrl = $baseUrl . '?page=' . $nextPage . $queryParams;
                ?>
                    <a href="<?= $nextUrl ?>" class="pagination-link next">
                        Próximo <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-link next disabled">
                        Próximo <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script src="<?= PUBLIC_URL ?>/app/assets/js/agenda/index.js"></script>
<script src="<?= PUBLIC_URL ?>/app/assets/js/agenda/common.js"></script>