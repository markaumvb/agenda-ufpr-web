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
    
    <!-- ADICIONADO: Campo de busca igual ao de agendas/index.php -->
    <div class="search-box">
        <form action="<?= PUBLIC_URL ?>/agendas/all" method="get" class="search-form">
            <input type="text" name="search" placeholder="Pesquisar em todas as agendas..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Buscar
            </button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="<?= PUBLIC_URL ?>/agendas/all" class="btn btn-secondary">
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

<!-- SEÇÃO 1: MINHAS AGENDAS -->
<section class="agendas-section">
    <h2 class="section-title">
        <i class="fas fa-calendar"></i>
        Minhas Agendas
        <?php if (!empty($_GET['search'])): ?>
            <span class="results-count">(<?= count($myAgendas) ?> encontrada(s))</span>
        <?php endif; ?>
    </h2>
    
    <?php if (empty($myAgendas)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-plus"></i>
            <?php if (!empty($_GET['search'])): ?>
                <h3>Nenhuma agenda encontrada</h3>
                <p>Não foi possível encontrar suas agendas com o termo "<strong><?= htmlspecialchars($_GET['search']) ?></strong>".</p>
                <a href="<?= PUBLIC_URL ?>/agendas/all" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Ver Todas as Agendas
                </a>
            <?php else: ?>
                <h3>Nenhuma agenda encontrada</h3>
                <p>Você ainda não criou nenhuma agenda.</p>
                <a href="<?= PUBLIC_URL ?>/agendas/new" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Criar Agenda
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($myAgendas as $agenda): ?>
                <div class="agenda-card" style="border-left: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
                    <div class="agenda-card-header">
                        <h3 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h3>
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
                            <div class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></div>
                        <?php else: ?>
                            <div class="agenda-description text-muted">Sem descrição</div>
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
        
        <!-- CORRIGIDO: Paginação padronizada -->
        <?php if (isset($totalPagesMyAgendas) && $totalPagesMyAgendas > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando <?= count($myAgendas) ?> de <?= $totalMyAgendas ?> agendas
                </div>
                <div class="pagination">
                    <?php
                    $searchParam = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?= PUBLIC_URL ?>/agendas/all?page=1<?= $searchParam ?>" class="pagination-link first">
                            &laquo; Primeira
                        </a>
                        <a href="<?= PUBLIC_URL ?>/agendas/all?page=<?= $page - 1 ?><?= $searchParam ?>" class="pagination-link prev">
                            &lsaquo; Anterior
                        </a>
                    <?php else: ?>
                        <span class="pagination-link disabled">&laquo; Primeira</span>
                        <span class="pagination-link disabled">&lsaquo; Anterior</span>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPagesMyAgendas, $page + 2); $i++): ?>
                        <a href="<?= PUBLIC_URL ?>/agendas/all?page=<?= $i ?><?= $searchParam ?>" 
                           class="pagination-link <?= $i == $page ? 'current' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPagesMyAgendas): ?>
                        <a href="<?= PUBLIC_URL ?>/agendas/all?page=<?= $page + 1 ?><?= $searchParam ?>" class="pagination-link next">
                            Próxima &rsaquo;
                        </a>
                        <a href="<?= PUBLIC_URL ?>/agendas/all?page=<?= $totalPagesMyAgendas ?><?= $searchParam ?>" class="pagination-link last">
                            Última &raquo;
                        </a>
                    <?php else: ?>
                        <span class="pagination-link disabled">Próxima &rsaquo;</span>
                        <span class="pagination-link disabled">Última &raquo;</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- SEÇÃO 2: AGENDAS COMPARTILHADAS COMIGO -->
<section class="agendas-section">
    <h2 class="section-title">
        <i class="fas fa-share-alt"></i>
        Agendas Compartilhadas Comigo
        <?php if (!empty($_GET['search'])): ?>
            <span class="results-count">(<?= count($sharedAgendas) ?> encontrada(s))</span>
        <?php endif; ?>
    </h2>
    
    <?php if (empty($sharedAgendas)): ?>
        <div class="empty-state">
            <i class="fas fa-share-alt"></i>
            <?php if (!empty($_GET['search'])): ?>
                <h3>Nenhuma agenda compartilhada encontrada</h3>
                <p>Não foram encontradas agendas compartilhadas com o termo "<strong><?= htmlspecialchars($_GET['search']) ?></strong>".</p>
            <?php else: ?>
                <h3>Nenhuma agenda compartilhada</h3>
                <p>Nenhuma agenda foi compartilhada com você.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($sharedAgendas as $agenda): ?>
                <div class="agenda-card" style="border-left: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
                    <div class="agenda-card-header">
                        <h3 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h3>
                        <div class="agenda-visibility">
                            <span class="badge badge-info">Compartilhada</span>
                            <?php if (isset($agenda['can_edit']) && $agenda['can_edit']): ?>
                                <span class="badge badge-warning">Pode Editar</span>
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
                            <p><i class="fas fa-user"></i> Proprietário: <?= htmlspecialchars($agenda['owner_name'] ?? 'Não especificado') ?></p>
                        </div>
                        
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
    <?php endif; ?>
</section>

<!-- SEÇÃO 3: AGENDAS PÚBLICAS -->
<section class="agendas-section">
    <h2 class="section-title">
        <i class="fas fa-globe"></i>
        Agendas Públicas
        <?php if (!empty($_GET['search'])): ?>
            <span class="results-count">(<?= count($publicAgendas) ?> encontrada(s))</span>
        <?php endif; ?>
    </h2>
    
    <?php if (empty($publicAgendas)): ?>
        <div class="empty-state">
            <i class="fas fa-globe"></i>
            <?php if (!empty($_GET['search'])): ?>
                <h3>Nenhuma agenda pública encontrada</h3>
                <p>Não foram encontradas agendas públicas com o termo "<strong><?= htmlspecialchars($_GET['search']) ?></strong>".</p>
            <?php else: ?>
                <h3>Nenhuma agenda pública</h3>
                <p>Nenhuma agenda pública está disponível no momento.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($publicAgendas as $agenda): ?>
                <div class="agenda-card" style="border-top: 4px solid <?= htmlspecialchars($agenda['color'] ?? '#004a8f') ?>;">
                    <div class="agenda-card-header">
                        <h3 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h3>
                        <div class="agenda-visibility">
                            <span class="badge badge-success">Pública</span>
                        </div>
                    </div>
                    
                    <div class="agenda-card-body">
                        <?php if (!empty($agenda['description'])): ?>
                            <div class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></div>
                        <?php else: ?>
                            <div class="agenda-description text-muted">Sem descrição</div>
                        <?php endif; ?>
                        
                        <div class="agenda-owner">
                            <p><i class="fas fa-user"></i> Proprietário: <?= htmlspecialchars($agenda['owner_name'] ?? 'Não especificado') ?></p>
                        </div>
                        
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
    <?php endif; ?>
</section>

<!-- ADICIONADO: JavaScript para funcionalidade de busca -->
<script src="<?= PUBLIC_URL ?>/app/assets/js/agenda/index.js"></script>
<script src="<?= PUBLIC_URL ?>/app/assets/js/agenda/common.js"></script>