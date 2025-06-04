<div class="page-header">
    <div class="header-container">
        <h1>Agendas Compartilhadas</h1>
    </div>
    
    <div class="search-box">
        <form action="<?= BASE_URL ?>/shares/shared" method="get" class="search-form">
            <input type="text" name="search" placeholder="Pesquisar agendas compartilhadas..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Buscar
            </button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="<?= BASE_URL ?>/shares/shared" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpar
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Informação sobre resultados da busca -->
    <?php if (!empty($_GET['search'])): ?>
        <div class="search-info">
            <i class="fas fa-search"></i> 
            Resultados para "<strong><?= htmlspecialchars($_GET['search']) ?></strong>"
        </div>
    <?php endif; ?>
</div>

<!-- Agendas que compartilhei -->
<div class="content-container">
    <section class="agendas-section">
        <h2 class="section-title">
            <i class="fas fa-share"></i>
            Agendas que compartilhei
            <?php if (!empty($_GET['search'])): ?>
                <span class="results-count">(<?= count($mySharedAgendas) ?> encontrada(s))</span>
            <?php endif; ?>
        </h2>
        
        <?php if (empty($mySharedAgendas)): ?>
            <div class="empty-state">
                <i class="fas fa-share"></i>
                <?php if (!empty($_GET['search'])): ?>
                    <h3>Nenhuma agenda encontrada</h3>
                    <p>Não foi possível encontrar agendas que você compartilhou com o termo "<strong><?= htmlspecialchars($_GET['search']) ?></strong>".</p>
                    <a href="<?= BASE_URL ?>/shares/shared" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Ver Todas as Agendas Compartilhadas
                    </a>
                <?php else: ?>
                    <h3>Você ainda não compartilhou nenhuma agenda</h3>
                    <p>Compartilhe suas agendas para colaborar com outras pessoas.</p>
                    <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> Ver minhas agendas
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="agenda-grid">
                <?php foreach ($mySharedAgendas as $agenda): ?>
                    <div class="agenda-card" style="border-left: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
                        <div class="agenda-card-header">
                            <h2 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h2>
                            <div class="agenda-visibility">
                                <?php if ($agenda['is_public']): ?>
                                    <span class="badge badge-info">Pública</span>
                                <?php endif; ?>
                                <span class="badge badge-secondary">Compartilhada</span>
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
                                    <div class="stat-value"><?= $agenda['compromissos']['pendentes'] ?></div>
                                    <div class="stat-label">Pendentes</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?= $agenda['compromissos']['realizados'] ?></div>
                                    <div class="stat-label">Realizados</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?= $agenda['compromissos']['cancelados'] ?></div>
                                    <div class="stat-label">Cancelados</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?= $agenda['compromissos']['aguardando_aprovacao'] ?></div>
                                    <div class="stat-label">Aguardando</div>
                                </div>
                            </div>
                        </div>
                        <div class="agenda-card-footer">
                            <div class="agenda-actions">
                                <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar-alt"></i> Ver Compromissos
                                </a>
                                <a href="<?= BASE_URL ?>/shares?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-share"></i> Gerenciar Compartilhamentos
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Agendas compartilhadas comigo -->
<div class="content-container">
    <section class="agendas-section">
        <h2 class="section-title">
            <i class="fas fa-share-alt"></i>
            Agendas compartilhadas comigo
            <?php if (!empty($_GET['search'])): ?>
                <span class="results-count">(<?= count($sharedWithMe) ?> encontrada(s))</span>
            <?php endif; ?>
        </h2>
        
        <?php if (empty($sharedWithMe)): ?>
            <div class="empty-state">
                <i class="fas fa-share-alt"></i>
                <?php if (!empty($_GET['search'])): ?>
                    <h3>Nenhuma agenda encontrada</h3>
                    <p>Não foram encontradas agendas compartilhadas com você usando o termo "<strong><?= htmlspecialchars($_GET['search']) ?></strong>".</p>
                    <a href="<?= BASE_URL ?>/shares/shared" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Ver Todas as Agendas Compartilhadas
                    </a>
                <?php else: ?>
                    <h3>Nenhuma agenda foi compartilhada com você</h3>
                    <p>Quando outras pessoas compartilharem agendas com você, elas aparecerão aqui.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="agenda-grid">
                <?php foreach ($sharedWithMe as $agenda): ?>
                    <div class="agenda-card" style="border-left: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
                        <div class="agenda-card-header">
                            <h2 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h2>
                            <div class="agenda-visibility">
                                <?php if ($agenda['can_edit']): ?>
                                    <span class="badge badge-success">Edição</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Visualização</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="agenda-card-body">
                            <?php if (!empty($agenda['description'])): ?>
                                <div class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></div>
                            <?php else: ?>
                                <div class="agenda-description text-muted">Sem descrição</div>
                            <?php endif; ?>
                            
                            <div class="agenda-owner">
                                <p><i class="fas fa-user"></i> Compartilhada por: <?= htmlspecialchars($agenda['owner_name']) ?></p>
                            </div>
                            
                            <div class="agenda-stats">
                                <div class="stat">
                                    <div class="stat-value"><?= $agenda['compromissos']['pendentes'] ?></div>
                                    <div class="stat-label">Pendentes</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?= $agenda['compromissos']['realizados'] ?></div>
                                    <div class="stat-label">Realizados</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?= $agenda['compromissos']['cancelados'] ?></div>
                                    <div class="stat-label">Cancelados</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?= $agenda['compromissos']['aguardando_aprovacao'] ?></div>
                                    <div class="stat-label">Aguardando</div>
                                </div>
                            </div>
                        </div>
                        <div class="agenda-card-footer">
                            <div class="agenda-actions">
                                <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar-alt"></i> Ver Compromissos
                                </a>
                                <?php if ($agenda['can_edit']): ?>
                                    <a href="<?= BASE_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-plus"></i> Novo Compromisso
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- CORRIGIDO: Paginação padronizada igual à home page -->
            <?php if (isset($paginationData) && $paginationData['total_pages'] > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        <i class="fas fa-chart-bar"></i>
                        Exibindo <strong><?= $paginationData['start_item'] ?></strong> a 
                        <strong><?= $paginationData['end_item'] ?></strong> de 
                        <strong><?= $paginationData['total_items'] ?></strong> agendas compartilhadas
                        <?php if (!empty($paginationData['search'])): ?>
                            encontradas para "<strong><?= htmlspecialchars($paginationData['search']) ?></strong>"
                        <?php endif; ?>
                    </div>
                    
                    <nav class="pagination">
                        <?php
                        $baseUrl = BASE_URL . '/shares/shared';
                        $searchParam = !empty($paginationData['search']) ? '&search=' . urlencode($paginationData['search']) : '';
                        
                        // Botão "Anterior"
                        if ($paginationData['current_page'] > 1):
                            $prevPage = $paginationData['current_page'] - 1;
                            $prevUrl = $baseUrl . '?page=' . $prevPage . $searchParam;
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
                            <a href="<?= $baseUrl ?>?page=1<?= $searchParam ?>" class="pagination-link">1</a>
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
                                <a href="<?= $baseUrl ?>?page=<?= $i ?><?= $searchParam ?>" class="pagination-link"><?= $i ?></a>
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
                            <a href="<?= $baseUrl ?>?page=<?= $paginationData['total_pages'] ?><?= $searchParam ?>" class="pagination-link"><?= $paginationData['total_pages'] ?></a>
                        <?php endif; ?>
                        
                        <?php
                        // Botão "Próximo"
                        if ($paginationData['current_page'] < $paginationData['total_pages']):
                            $nextPage = $paginationData['current_page'] + 1;
                            $nextUrl = $baseUrl . '?page=' . $nextPage . $searchParam;
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
    </section>
</div>

<script src="<?= PUBLIC_URL ?>/app/assets/js/shares/shared.js"></script>