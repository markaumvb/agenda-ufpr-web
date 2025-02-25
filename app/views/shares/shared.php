<?php
// Arquivo: app/views/shares/shared.php
?>

<div class="page-header">
    <div class="header-container">
        <h1>Agendas Compartilhadas Comigo</h1>
        <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-secondary">Voltar para Minhas Agendas</a>
    </div>
</div>

<?php if (empty($sharedAgendas)): ?>
    <div class="empty-state">
        <p>Nenhuma agenda foi compartilhada com você.</p>
        <p>Quando outros usuários compartilharem suas agendas com você, elas aparecerão aqui.</p>
    </div>
<?php else: ?>
    <div class="agenda-grid">
        <?php foreach ($sharedAgendas as $agenda): ?>
            <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color']) ?>;">
                <div class="agenda-card-header">
                    <h2><?= htmlspecialchars($agenda['title']) ?></h2>
                    <div class="agenda-owner">
                        Compartilhada por: <?= htmlspecialchars($agenda['owner_name']) ?>
                    </div>
                </div>
                
                <div class="agenda-card-body">
                    <?php if (!empty($agenda['description'])): ?>
                        <p class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></p>
                    <?php else: ?>
                        <p class="agenda-description text-muted">Sem descrição</p>
                    <?php endif; ?>
                    
                    <div class="agenda-permission">
                        <span class="badge <?= $agenda['can_edit'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $agenda['can_edit'] ? 'Permissão para editar' : 'Somente visualização' ?>
                        </span>
                    </div>
                </div>
                
                <div class="agenda-card-footer">
                    <a href="<?= PUBLIC_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-link">Ver Compromissos</a>
                    
                    <?php if ($agenda['can_edit']): ?>
                        <a href="<?= PUBLIC_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">Novo Compromisso</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>