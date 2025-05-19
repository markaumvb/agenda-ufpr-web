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
                    <span class="badge badge-secondary">Lida</span>
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
                        $isRecurring = !empty($compromisso['group_id']) && isset($notification['occurrences']) && count($notification['occurrences']) > 1;
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
                            
                            <?php if ($isRecurring): ?>
                                <div class="data-row">
                                    <div class="data-label">Recorrência:</div>
                                    <div class="data-value">
                                        <?php 
                                            $recurrenceText = '';
                                            switch($compromisso['repeat_type']) {
                                                case 'daily':
                                                    $recurrenceText = 'Repete diariamente';
                                                    break;
                                                case 'weekly':
                                                    $recurrenceText = 'Repete semanalmente';
                                                    break;
                                                case 'specific_days':
                                                    $days = explode(',', $compromisso['repeat_days']);
                                                    $dayNames = [
                                                        '0' => 'Domingo',
                                                        '1' => 'Segunda',
                                                        '2' => 'Terça',
                                                        '3' => 'Quarta',
                                                        '4' => 'Quinta',
                                                        '5' => 'Sexta',
                                                        '6' => 'Sábado'
                                                    ];
                                                    $daysList = [];
                                                    foreach ($days as $day) {
                                                        if (isset($dayNames[$day])) {
                                                            $daysList[] = $dayNames[$day];
                                                        }
                                                    }
                                                    $recurrenceText = 'Repete nos dias: ' . implode(', ', $daysList);
                                                    break;
                                            }
                                            
                                            if (!empty($compromisso['repeat_until'])) {
                                                $untilDate = new DateTime($compromisso['repeat_until']);
                                                $recurrenceText .= ' até ' . $untilDate->format('d/m/Y');
                                            }
                                            
                                            echo $recurrenceText;
                                        ?>
                                    </div>
                                </div>
                                <div class="data-row">
                                    <div class="data-label">Total de ocorrências:</div>
                                    <div class="data-value"><?= count($notification['occurrences']) ?></div>
                                </div>
                            <?php endif; ?>
                            
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
    <?php if ($isRecurring): ?>
        <!-- Interface para compromissos recorrentes -->
        <div class="recurrence-info alert alert-info mt-3 mb-4">
            <h4><i class="fas fa-repeat"></i> Compromisso Recorrente</h4>
            <p class="mb-1">Este é um compromisso recorrente com <?= count($notification['occurrences']) ?> ocorrências.</p>
            <p class="mb-0">Escolha abaixo se deseja aprovar/rejeitar apenas este compromisso ou toda a série de uma vez.</p>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar"></i> Apenas Este Compromisso</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Sua ação afetará apenas a ocorrência do dia <?= (new DateTime($compromisso['start_datetime']))->format('d/m/Y') ?>.</p>
                        <div class="d-flex justify-content-between">
                            <form action="<?= BASE_URL ?>/notifications/accept-compromisso" method="post">
                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Aprovar
                                </button>
                            </form>
                            
                            <form action="<?= BASE_URL ?>/notifications/reject-compromisso" method="post">
                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Rejeitar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Toda a Série</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Sua ação afetará todas as <?= count($notification['occurrences']) ?> ocorrências pendentes da série.</p>
                        <div class="d-flex justify-content-between">
                            <form action="<?= BASE_URL ?>/notifications/accept-compromisso" method="post">
                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                                <input type="hidden" name="approve_all" value="1">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check-double"></i> Aprovar Todos
                                </button>
                            </form>
                            
                            <form action="<?= BASE_URL ?>/notifications/reject-compromisso" method="post">
                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                                <input type="hidden" name="reject_all" value="1">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i> Rejeitar Todos
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Interface original para compromissos não recorrentes -->
        <div class="d-flex justify-content-center mt-3">
            <form action="<?= BASE_URL ?>/notifications/accept-compromisso" method="post" class="mr-2">
                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check"></i> Aprovar Compromisso
                </button>
            </form>
            
            <form action="<?= BASE_URL ?>/notifications/reject-compromisso" method="post">
                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                <input type="hidden" name="compromisso_id" value="<?= $compromisso['id'] ?>">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-times"></i> Rejeitar Compromisso
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
                        <?php endif; ?>
                        
                        <div class="view-compromisso-link mt-3">
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
            </div>
        </div>
    </div>
</div>