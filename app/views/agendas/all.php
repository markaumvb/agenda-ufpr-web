<div class="page-header">
    <div class="header-container">
        <h1>Todas as Agendas</h1>
        <div class="header-actions">
            <a href="<?= PUBLIC_URL ?>/agendas/new" class="btn btn-primary">Nova Agenda</a>
        </div>
    </div>
    
    <div class="search-box">
        <form action="<?= PUBLIC_URL ?>/agendas/all" method="get" class="search-form">
            <input type="text" name="search" placeholder="Pesquisar agendas..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <button type="submit" class="btn btn-secondary">Buscar</button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="<?= PUBLIC_URL ?>/agendas/all" class="btn btn-link">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Seção: Minhas Agendas -->
<section class="agendas-section">
    <h2 class="section-title">Minhas Agendas</h2>
    
    <?php if (empty($myAgendas)): ?>
        <div class="empty-state">
            <p>Você ainda não criou nenhuma agenda.</p>
            <a href="<?= PUBLIC_URL ?>/agendas/new" class="btn btn-primary">Criar Agenda</a>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($myAgendas as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                    <div class="agenda-card-header">
                        <h3><?= htmlspecialchars($agenda['title']) ?></h3>
                        <div class="agenda-visibility">
                            <?php if ($agenda['is_public']): ?>
                                <span class="badge badge-success">Pública</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Privada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="agenda-card-body">
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
                            
                            <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Novo Compromisso
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalMyAgendas > $perPage): ?>
            <div class="pagination-container">
                <?php
                // Criar objeto de paginação
                require_once __DIR__ . '/../../app/helpers/Pagination.php';
                $pagination = new Pagination(
                    $totalMyAgendas,
                    $perPage,
                    $page,
                    PUBLIC_URL . '/agendas/all',
                    ['section' => 'my']
                );
                echo $pagination->createLinks();
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- Seção: Agendas Compartilhadas Comigo -->
<section class="agendas-section">
    <h2 class="section-title">Agendas Compartilhadas Comigo</h2>
    
    <?php if (empty($sharedAgendas)): ?>
        <div class="empty-state">
            <p>Nenhuma agenda foi compartilhada com você.</p>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($sharedAgendas as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                    <div class="agenda-card-header">
                        <h3><?= htmlspecialchars($agenda['title']) ?></h3>
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
                            
                            <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Novo Compromisso
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalSharedAgendas > $perPage): ?>
            <div class="pagination-container">
                <?php
                // Criar objeto de paginação
                $pagination = new Pagination(
                    $totalSharedAgendas,
                    $perPage,
                    $page,
                    PUBLIC_URL . '/agendas/all',
                    ['section' => 'shared']
                );
                echo $pagination->createLinks();
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- Seção: Agendas Públicas -->
<section class="agendas-section">
    <h2 class="section-title">Agendas Públicas</h2>
    
    <?php if (empty($publicAgendas)): ?>
        <div class="empty-state">
            <p>Nenhuma agenda pública disponível no momento.</p>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($publicAgendas as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                    <div class="agenda-card-header">
                        <h3><?= htmlspecialchars($agenda['title']) ?></h3>
                        <div class="agenda-visibility">
                            <span class="badge badge-success">Pública</span>
                        </div>
                    </div>
                    
                    <div class="agenda-card-body">
                        <div class="agenda-owner">
                            <p>Proprietário: <?= htmlspecialchars($agenda['owner_name']) ?></p>
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
                            
                            <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>&public=1" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Novo Compromisso
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPublicAgendas > $perPage): ?>
            <div class="pagination-container">
                <?php
                // Criar objeto de paginação
                $pagination = new Pagination(
                    $totalPublicAgendas,
                    $perPage,
                    $page,
                    PUBLIC_URL . '/agendas/all',
                    ['section' => 'public']
                );
                echo $pagination->createLinks();
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<style>
    .section-title {
        margin-top: 2rem;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        color: #004a8f;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }
    
    .agendas-section {
        margin-bottom: 3rem;
    }
</style>