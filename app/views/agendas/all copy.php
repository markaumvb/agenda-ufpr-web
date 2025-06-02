<?php
// Função auxiliar para evitar duplicatas por ID
function filterDuplicateAgendas($agendas) {
    $uniqueAgendas = [];
    $uniqueIds = [];
    
    foreach ($agendas as $agenda) {
        if (!in_array($agenda['id'], $uniqueIds)) {
            $uniqueIds[] = $agenda['id'];
            $uniqueAgendas[] = $agenda;
        }
    }
    
    return $uniqueAgendas;
}

// Filtrar duplicatas em cada categoria de agendas
$myAgendas = filterDuplicateAgendas($myAgendas);
$sharedAgendas = isset($sharedAgendas) ? filterDuplicateAgendas($sharedAgendas) : [];
$publicAgendas = isset($publicAgendas) ? filterDuplicateAgendas($publicAgendas) : [];
?>

<div class="page-header">
    <div class="header-container">
        <h1>Todas as Agendas</h1>
        <div class="header-actions">
            <a href="<?= PUBLIC_URL ?>/agendas/new" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Agenda
            </a>
        </div>
    </div>
</div>

<!-- SEÇÃO 1: MINHAS AGENDAS -->
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
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
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
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['pendentes'] : 0 ?></span>
                                <span class="stat-label">Pendentes</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['realizados'] : 0 ?></span>
                                <span class="stat-label">Realizados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['cancelados'] : 0 ?></span>
                                <span class="stat-label">Cancelados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['aguardando_aprovacao'] : 0 ?></span>
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
        
        <?php if (isset($totalMyAgendas) && $totalMyAgendas > $perPage): ?>
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

<!-- SEÇÃO 2: AGENDAS COMPARTILHADAS COMIGO -->
<section class="agendas-section">
    <h2 class="section-title">Agendas Compartilhadas Comigo</h2>
    
    <?php if (empty($sharedAgendas)): ?>
        <div class="empty-state">
            <p>Nenhuma agenda foi compartilhada com você.</p>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($sharedAgendas as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
                    <div class="agenda-card-header">
                        <h3><?= htmlspecialchars($agenda['title']) ?></h3>
                        <div class="agenda-visibility">
                            <span class="badge badge-info">Compartilhada</span>
                            <?php if (isset($agenda['can_edit']) && $agenda['can_edit']): ?>
                                <span class="badge badge-warning">Pode Editar</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="agenda-card-body">
                        <?php if (!empty($agenda['description'])): ?>
                            <p class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></p>
                        <?php else: ?>
                            <p class="agenda-description text-muted">Sem descrição</p>
                        <?php endif; ?>
                        
                        <p class="agenda-owner">
                            Proprietário: <?= htmlspecialchars($agenda['owner_name'] ?? 'Não especificado') ?>
                        </p>
                        
                        <div class="agenda-stats">
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['pendentes'] : 0 ?></span>
                                <span class="stat-label">Pendentes</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['realizados'] : 0 ?></span>
                                <span class="stat-label">Realizados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['cancelados'] : 0 ?></span>
                                <span class="stat-label">Cancelados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['aguardando_aprovacao'] : 0 ?></span>
                                <span class="stat-label">Aguardando</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="agenda-card-footer">
                        <div class="agenda-actions">
                            <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar-alt"></i> Ver Compromissos
                            </a>
                            
                            <?php if (isset($agenda['can_edit']) && $agenda['can_edit']): ?>
                                <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Novo Compromisso
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (isset($totalSharedAgendas) && $totalSharedAgendas > $perPage): ?>
            <div class="pagination-container">
                <?php
                // Criar objeto de paginação
                require_once __DIR__ . '/../../app/helpers/Pagination.php';
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

<!-- SEÇÃO 3: AGENDAS PÚBLICAS -->
<section class="agendas-section">
    <h2 class="section-title">Agendas Públicas</h2>
    
    <?php if (empty($publicAgendas)): ?>
        <div class="empty-state">
            <p>Nenhuma agenda pública está disponível no momento.</p>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($publicAgendas as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
                    <div class="agenda-card-header">
                        <h3><?= htmlspecialchars($agenda['title']) ?></h3>
                        <div class="agenda-visibility">
                            <span class="badge badge-success">Pública</span>
                        </div>
                    </div>
                    
                    <div class="agenda-card-body">
                        <?php if (!empty($agenda['description'])): ?>
                            <p class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></p>
                        <?php else: ?>
                            <p class="agenda-description text-muted">Sem descrição</p>
                        <?php endif; ?>
                        
                        <p class="agenda-owner">
                            Proprietário: <?= htmlspecialchars($agenda['owner_name'] ?? 'Não especificado') ?>
                        </p>
                        
                        <div class="agenda-stats">
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['pendentes'] : 0 ?></span>
                                <span class="stat-label">Pendentes</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['realizados'] : 0 ?></span>
                                <span class="stat-label">Realizados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['cancelados'] : 0 ?></span>
                                <span class="stat-label">Cancelados</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= isset($agenda['compromissos']) ? $agenda['compromissos']['aguardando_aprovacao'] : 0 ?></span>
                                <span class="stat-label">Aguardando</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="agenda-card-footer">
                        <div class="agenda-actions">
                            <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-calendar-alt"></i> Ver Compromissos
                            </a>
                            
                            <?php if (isset($agenda['is_active']) && $agenda['is_active']): ?>
                                <a href="<?= PUBLIC_URL ?>/compromissos/new-public?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus"></i> Solicitar Compromisso
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (isset($totalPublicAgendas) && $totalPublicAgendas > $perPage): ?>
            <div class="pagination-container">
                <?php
                // Criar objeto de paginação
                require_once __DIR__ . '/../../app/helpers/Pagination.php';
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