<div class="page-header">
    <div class="header-container">
        <h1>Agendas Compartilhadas</h1>
    </div>
    
    <!-- CORRIGIDO: Campo de busca padronizado -->
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
    
    <!-- ADICIONADO: Informação sobre resultados da busca -->
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
            
            <!-- CORRIGIDO: Paginação padronizada -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Mostrando página <?= $page ?> de <?= $totalPages ?>
                    </div>
                    <div class="pagination">
                        <?php
                        $searchParam = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?= BASE_URL ?>/shares/shared?page=1<?= $searchParam ?>" class="pagination-link first">
                                &laquo; Primeira
                            </a>
                            <a href="<?= BASE_URL ?>/shares/shared?page=<?= $page - 1 ?><?= $searchParam ?>" class="pagination-link prev">
                                &lsaquo; Anterior
                            </a>
                        <?php else: ?>
                            <span class="pagination-link disabled">&laquo; Primeira</span>
                            <span class="pagination-link disabled">&lsaquo; Anterior</span>
                        <?php endif; ?>
                        
                        <?php
                        // Determinar quais páginas mostrar
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        // Se estamos mostrando menos de 5 páginas, ajustar para mostrar mais
                        if ($endPage - $startPage < 4) {
                            if ($startPage == 1) {
                                $endPage = min($totalPages, $startPage + 4);
                            } elseif ($endPage == $totalPages) {
                                $startPage = max(1, $endPage - 4);
                            }
                        }
                        
                        // Mostrar primeira página e elipse se necessário
                        if ($startPage > 1) {
                            echo '<a href="' . BASE_URL . '/shares/shared?page=1' . $searchParam . '" class="pagination-link">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="pagination-ellipsis">&hellip;</span>';
                            }
                        }
                        
                        // Mostrar páginas centrais
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $activeClass = ($i == $page) ? ' current' : '';
                            echo '<a href="' . BASE_URL . '/shares/shared?page=' . $i . $searchParam . '" class="pagination-link' . $activeClass . '">' . $i . '</a>';
                        }
                        
                        // Mostrar última página e elipse se necessário
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="pagination-ellipsis">&hellip;</span>';
                            }
                            echo '<a href="' . BASE_URL . '/shares/shared?page=' . $totalPages . $searchParam . '" class="pagination-link">' . $totalPages . '</a>';
                        }
                        ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= BASE_URL ?>/shares/shared?page=<?= $page + 1 ?><?= $searchParam ?>" class="pagination-link next">Próxima &rsaquo;</a>
                            <a href="<?= BASE_URL ?>/shares/shared?page=<?= $totalPages ?><?= $searchParam ?>" class="pagination-link last">Última &raquo;</a>
                        <?php else: ?>
                            <span class="pagination-link disabled">Próxima &rsaquo;</span>
                            <span class="pagination-link disabled">Última &raquo;</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<script src="<?= PUBLIC_URL ?>/app/assets/js/shares/shared.js"></script>