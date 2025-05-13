<div class="container">
    <div class="page-header">
        <div class="header-container">
            <h1>Notificação</h1>
            <div class="header-actions">
                <a href="<?= BASE_URL ?>/notifications" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar para notificações
                </a>
            </div>
        </div>
    </div>

    <div class="notification-detail-container">
        <div class="notification-card">
            <div class="notification-card-header">
                <div class="notification-status">
                    <?php if ($notification['is_read']): ?>
                        <span class="badge badge-secondary">Lida</span>
                    <?php else: ?>
                        <span class="badge badge-primary">Não lida</span>
                    <?php endif; ?>
                </div>
                <div class="notification-date">
                    <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
                </div>
            </div>
            <div class="notification-card-body">
                <div class="notification-message">
                    <?= htmlspecialchars($notification['message']) ?>
                </div>
                
                <?php if (isset($notification['compromisso'])): ?>
                    <?php 
                        $compromisso = $notification['compromisso']; 
                        $isOwner = isset($notification['is_agenda_owner']) ? $notification['is_agenda_owner'] : false;
                        $canApprove = $isOwner && $compromisso['status'] === 'aguardando_aprovacao';
                    ?>
                    <div class="compromisso-details">
                        <h3>Detalhes do Compromisso</h3>
                        <div class="compromisso-data">
                            <div class="data-row">
                                <div class="data-label">Título:</div>
                                <div class="data-value"><?= htmlspecialchars($compromisso['title']) ?></div>
                            </div>
                            
                            <div class="data-row">
                                <div class="data-label">Data/Hora:</div>
                                <div class="data-value">
                                    <?= date('d/m/Y H:i', strtotime($compromisso['start_datetime'])) ?> até 
                                    <?= date('d/m/Y H:i', strtotime($compromisso['end_datetime'])) ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($compromisso['location'])): ?>
                                <div class="data-row">
                                    <div class="data-label">Local:</div>
                                    <div class="data-value"><?= htmlspecialchars($compromisso['location']) ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="data-row">
                                <div class="data-label">Status:</div>
                                <div class="data-value">
                                    <span class="badge badge-<?= $compromisso['status'] ?>">
                                        <?php 
                                            $statusLabels = [
                                                'pendente' => 'Pendente',
                                                'realizado' => 'Realizado',
                                                'cancelado' => 'Cancelado',
                                                'aguardando_aprovacao' => 'Aguardando Aprovação'
                                            ];
                                            echo isset($statusLabels[$compromisso['status']]) ? $statusLabels[$compromisso['status']] : $compromisso['status'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (isset($notification['agenda'])): ?>
                                <div class="data-row">
                                    <div class="data-label">Agenda:</div>
                                    <div class="data-value"><?= htmlspecialchars($notification['agenda']['title']) ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($notification['compromisso_creator'])): ?>
                                <div class="data-row">
                                    <div class="data-label">Criado por:</div>
                                    <div class="data-value"><?= htmlspecialchars($notification['compromisso_creator']['name']) ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($compromisso['description'])): ?>
                                <div class="data-row description-row">
                                    <div class="data-label">Descrição:</div>
                                    <div class="data-value description-text"><?= nl2br(htmlspecialchars($compromisso['description'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($canApprove): ?>
                            <div class="notification-actions">
                                <form action="<?= BASE_URL ?>/notifications/accept-compromisso" method="post" class="d-inline">
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Aprovar Compromisso
                                    </button>
                                </form>
                                
                                <form action="<?= BASE_URL ?>/notifications/reject-compromisso" method="post" class="d-inline">
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Rejeitar Compromisso
                                    </button>
                                </form>
                            </div>
                        <?php elseif (!$notification['is_read']): ?>
                            <div class="notification-actions">
                                <form action="<?= BASE_URL ?>/notifications/mark-read" method="post">
                                    <input type="hidden" name="id" value="<?= $notification['id'] ?>">
                                    <input type="hidden" name="redirect_url" value="<?= BASE_URL ?>/notifications/view?id=<?= $notification['id'] ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check"></i> Marcar como lida
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="view-compromisso-link">
                            <a href="<?= BASE_URL ?>/compromissos/view?id=<?= $compromisso['id'] ?>" class="btn btn-outline-primary">
                                <i class="fas fa-calendar-alt"></i> Ver detalhes do compromisso
                            </a>
                            
                            <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $compromisso['agenda_id'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-calendar"></i> Ver agenda
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="notification-card-footer">
                <form action="<?= BASE_URL ?>/notifications/delete" method="post" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta notificação?');">
                    <input type="hidden" name="id" value="<?= $notification['id'] ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Excluir Notificação
                    </button>
                </form>
                
                <?php if (!$notification['is_read']): ?>
                    <form action="<?= BASE_URL ?>/notifications/mark-read" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= $notification['id'] ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Marcar como lida
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>