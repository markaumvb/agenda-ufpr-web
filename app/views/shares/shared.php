<div class="page-header">
    <div class="header-container">
        <h1>Agendas Compartilhadas</h1>
        <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-secondary">Voltar para Minhas Agendas</a>
    </div>
</div>

<!-- Busca -->
<div class="search-box">
    <form action="<?= PUBLIC_URL ?>/shares/shared" method="get" class="search-form">
        <input type="text" name="search" placeholder="Pesquisar agendas..." 
               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <button type="submit" class="btn btn-secondary">Buscar</button>
        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <a href="<?= PUBLIC_URL ?>/shares/shared" class="btn btn-link">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Seção: Agendas compartilhadas comigo -->
<section class="agendas-section">
    <h2 class="section-title">Agendas Compartilhadas Comigo</h2>
    
    <?php if (empty($sharedWithMe)): ?>
        <div class="empty-state">
            <p>Nenhuma agenda foi compartilhada com você.</p>
            <p>Quando outros usuários compartilharem suas agendas com você, elas aparecerão aqui.</p>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($sharedWithMe as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                    <div class="agenda-card-header">
                        <h2><?= htmlspecialchars($agenda['title']) ?></h2>
                        <div class="agenda-visibility">
                            <?php if ($agenda['is_public']): ?>
                                <span class="badge badge-success">Pública</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Privada</span>
                            <?php endif; ?>
                            
                            <span class="badge badge-info">Compartilhada</span>
                        </div>
                    </div>
                    
                    <div class="agenda-card-body">
                        <div class="agenda-owner">
                            <p>Proprietário: <?= htmlspecialchars($agenda['owner_name']) ?></p>
                            <p>Sua permissão: <?= $agenda['can_edit'] ? 'Pode editar' : 'Apenas visualização' ?></p>
                        </div>
                        
                        <?php if (!empty($agenda['description'])): ?>
                            <p class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></p>
                        <?php else: ?>
                            <p class="agenda-description text-muted">Sem descrição</p>
                        <?php endif; ?>
                        
                        <div class="agenda-stats">
                            <div class="stat">
                                <span class="stat-value"><?= $agenda['compromissos']['pendentes'] ?></span>
                                <span class="stat-label">Pendentes</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= $agenda['compromissos']['realizados'] ?></span>
                                <span class="stat-label">Realizados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= $agenda['compromissos']['cancelados'] ?></span>
                                <span class="stat-label">Cancelados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= $agenda['compromissos']['aguardando_aprovacao'] ?></span>
                                <span class="stat-label">Aguardando</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="agenda-card-footer">
                        <div class="agenda-actions">
                            <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar-alt"></i> Ver Compromissos
                            </a>
                            
                            <?php if ($agenda['can_edit']): ?>
                                <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Novo Compromisso
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPagesSharedWithMe > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?= count($sharedWithMe) ?> de <?= $totalSharedWithMe ?> agendas
                </div>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= PUBLIC_URL ?>/shares/shared?page=1<?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-link first">
                            &laquo; Primeira
                        </a>
                        <a href="<?= PUBLIC_URL ?>/shares/shared?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-link prev">
                            &lsaquo; Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPagesSharedWithMe, $page + 2); $i++): ?>
                        <a href="<?= PUBLIC_URL ?>/shares/shared?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                           class="pagination-link <?= $i == $page ? 'current' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPagesSharedWithMe): ?>
                        <a href="<?= PUBLIC_URL ?>/shares/shared?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-link next">
                            Próxima &rsaquo;
                        </a>
                        <a href="<?= PUBLIC_URL ?>/shares/shared?page=<?= $totalPagesSharedWithMe ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-link last">
                            Última &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- Seção: Minhas Agendas Compartilhadas -->
<section class="agendas-section">
    <h2 class="section-title">Minhas Agendas Compartilhadas</h2>
    
    <?php if (empty($mySharedAgendas)): ?>
        <div class="empty-state">
            <p>Você não compartilhou nenhuma agenda com outros usuários.</p>
            <p>Para compartilhar uma agenda, acesse a agenda desejada e clique em "Compartilhar".</p>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($mySharedAgendas as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                    <div class="agenda-card-header">
                        <h2><?= htmlspecialchars($agenda['title']) ?></h2>
                        <div class="agenda-visibility">
                            <?php if ($agenda['is_public']): ?>
                                <span class="badge badge-success">Pública</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Privada</span>
                            <?php endif; ?>
                            
                            <span class="badge badge-info">Compartilhada com <?= count($agenda['shared_with']) ?> usuário(s)</span>
                        </div>
                    </div>
                    
                    <div class="agenda-card-body">
                        <?php if (!empty($agenda['description'])): ?>
                            <p class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></p>
                        <?php else: ?>
                            <p class="agenda-description text-muted">Sem descrição</p>
                        <?php endif; ?>
                        
                        <div class="agenda-shared-with">
                            <h4>Compartilhada com:</h4>
                            <ul class="shared-users-list">
                                <?php foreach ($agenda['shared_with'] as $share): ?>
                                    <li>
                                        <span class="shared-user-name"><?= htmlspecialchars($share['name']) ?></span>
                                        <span class="shared-user-permission badge <?= $share['can_edit'] ? 'badge-success' : 'badge-secondary' ?>">
                                            <?= $share['can_edit'] ? 'Pode editar' : 'Somente leitura' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="agenda-card-footer">
                        <div class="agenda-actions">
                            <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar-alt"></i> Ver Compromissos
                            </a>
                            
                            <a href="<?= PUBLIC_URL ?>/shares?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-secondary">
                                <i class="fas fa-share-alt"></i> Gerenciar Compartilhamento
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>