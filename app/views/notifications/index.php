<div class="container">
    <div class="page-header">
        <div class="header-container">
            <h1>Notificações</h1>
            <div class="header-actions">
                <?php if ($totalNotifications > 0): ?>
                <form action="<?= BASE_URL ?>/notifications/mark-all-read" method="post" class="d-inline">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-check-double"></i> Marcar todas como lidas
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="notification-filters">
        <div class="filter-toggle">
            <a href="<?= BASE_URL ?>/notifications<?= $onlyUnread ? '' : '?unread=1' ?>" class="btn <?= $onlyUnread ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= $onlyUnread ? 'Todas as notificações' : 'Apenas não lidas' ?>
            </a>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <p><?= $onlyUnread ? 'Não há notificações não lidas' : 'Não há notificações' ?></p>
        </div>
    <?php else: ?>
        <div class="notification-list-container">
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <a href="<?= BASE_URL ?>/notifications/view?id=<?= $notification['id'] ?>" class="list-group-item notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                        <div class="notification-header">
                            <div class="notification-title">
                                <?php if (!$notification['is_read']): ?>
                                    <span class="unread-badge"></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($notification['message']) ?>
                            </div>
                            <div class="notification-date">
                                <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                            </div>
                        </div>
                        <?php if (!empty($notification['compromisso_title'])): ?>
                            <div class="notification-meta">
                                <span class="badge badge-info">Compromisso</span>
                                <span class="meta-text"><?= htmlspecialchars($notification['compromisso_title']) ?></span>
                                <?php if (!empty($notification['agenda_title'])): ?>
                                    <span class="badge badge-secondary">Agenda</span>
                                    <span class="meta-text"><?= htmlspecialchars($notification['agenda_title']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?= $startRecord ?> a <?= $endRecord ?> de <?= $totalNotifications ?> notificações
                </div>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= BASE_URL ?>/notifications?page=1<?= $queryParams ? '&'.$queryParams : '' ?>" class="pagination-link">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/notifications?page=<?= $page - 1 ?><?= $queryParams ? '&'.$queryParams : '' ?>" class="pagination-link">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-link disabled">
                            <i class="fas fa-angle-double-left"></i>
                        </span>
                        <span class="pagination-link disabled">
                            <i class="fas fa-angle-left"></i>
                        </span>
                    <?php endif; ?>

                    <?php
                    // Determinar quais páginas mostrar na paginação
                    $startPage = max($page - 2, 1);
                    $endPage = min($startPage + 4, $totalPages);
                    
                    // Se estamos nas últimas páginas, ajustar para mostrar 5 páginas
                    if ($endPage - $startPage < 4) {
                        $startPage = max($endPage - 4, 1);
                    }

                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="pagination-link current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/notifications?page=<?= $i ?><?= $queryParams ? '&'.$queryParams : '' ?>" class="pagination-link"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= BASE_URL ?>/notifications?page=<?= $page + 1 ?><?= $queryParams ? '&'.$queryParams : '' ?>" class="pagination-link">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/notifications?page=<?= $totalPages ?><?= $queryParams ? '&'.$queryParams : '' ?>" class="pagination-link">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-link disabled">
                            <i class="fas fa-angle-right"></i>
                        </span>
                        <span class="pagination-link disabled">
                            <i class="fas fa-angle-double-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>