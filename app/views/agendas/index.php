<div class="page-header">
    <div class="header-container">
        <h1>Minhas Agendas</h1>
        <div class="header-actions">
            <a href="<?= PUBLIC_URL ?>/agendas/new" class="btn btn-primary">Nova Agenda</a>
            <a href="<?= PUBLIC_URL ?>/shares/shared" class="btn btn-secondary">Agendas Compartilhadas Comigo</a>
        </div>
    </div>
    
    <div class="search-box">
        <form action="<?= PUBLIC_URL ?>/agendas" method="get" class="search-form">
            <input type="text" name="search" placeholder="Pesquisar agendas..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <button type="submit" class="btn btn-secondary">Buscar</button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
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
        <?php foreach ($agendas as $agenda): ?>
            <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                <div class="agenda-card-header">
                    <h2><?= htmlspecialchars($agenda['title']) ?></h2>
                    <div class="agenda-visibility">
                        <?php if ($agenda['is_public']): ?>
                            <span class="badge badge-success">Pública</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Privada</span>
                        <?php endif; ?>
                        
                        <?php if (isset($agenda['is_owner']) && !$agenda['is_owner']): ?>
                            <span class="badge badge-info">Compartilhada</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="agenda-card-body">
                    <?php if (isset($agenda['is_owner']) && !$agenda['is_owner']): ?>
                        <div class="agenda-owner">
                            <p>Proprietário: <?= htmlspecialchars($agenda['owner_name'] ?? 'Usuário') ?></p>
                            <p>Sua permissão: <?= isset($agenda['can_edit']) && $agenda['can_edit'] ? 'Pode editar' : 'Apenas visualização' ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($agenda['description'])): ?>
                        <p class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></p>
                    <?php else: ?>
                        <p class="agenda-description text-muted">Sem descrição</p>
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
                    <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-link">Ver Compromissos</a>
                    <div class="agenda-actions">
                        <?php if (!isset($agenda['is_owner']) || $agenda['is_owner']): ?>
                            <a href="<?= PUBLIC_URL ?>/shares?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-info">Compartilhar</a>
                            <a href="<?= PUBLIC_URL ?>/agendas/edit?id=<?= $agenda['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                            
                            <?php if (isset($agenda['can_be_deleted']) && $agenda['can_be_deleted']): ?>
                                <form action="<?= PUBLIC_URL ?>/agendas/delete" method="post" class="delete-form" 
                                      onsubmit="return confirm('Tem certeza que deseja excluir esta agenda?');">
                                    <input type="hidden" name="id" value="<?= $agenda['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-danger disabled" title="Não é possível excluir esta agenda pois possui compromissos pendentes ou aguardando aprovação" disabled>
                                    Excluir
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (isset($agenda['can_edit']) && $agenda['can_edit']): ?>
                                <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">Novo Compromisso</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>