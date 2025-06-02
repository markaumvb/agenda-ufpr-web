<?php
$currentUri = $_SERVER['REQUEST_URI'];
$isHomePage = ($currentUri == '/' || $currentUri == '/agenda_ufpr/' || $currentUri == '/agenda_ufpr/index.php');
?>

<div class="page-header">
    <div class="header-container">
        <h1>Sistema de Agendamento UFPR</h1>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="header-actions">
                <a href="<?= PUBLIC_URL ?>/login" class="btn btn-primary">Entrar no Sistema</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="welcome-section">
        <p class="welcome-text">
            Bem-vindo ao sistema de agendamento da UFPR Jandaia do Sul. 
            Aqui você pode visualizar agendas públicas e criar compromissos.
        </p>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span>Logado como: <strong><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']) ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- CAMPO DE BUSCA -->
<div class="search-container">
    <form method="GET" action="<?= BASE_URL ?>/" class="search-form-home">
        <input type="text" 
               name="search" 
               placeholder="Buscar agendas por título, descrição ou responsável..." 
               value="<?= htmlspecialchars($paginationData['search'] ?? '') ?>"
               class="search-input-large">
        <button type="submit" class="btn btn-primary search-btn">
            <i class="fas fa-search"></i> Buscar
        </button>
        <?php if (!empty($paginationData['search'])): ?>
            <a href="<?= BASE_URL ?>/" class="btn btn-secondary">
                <i class="fas fa-times"></i> Limpar
            </a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($publicAgendas)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <?php if (!empty($paginationData['search'])): ?>
            <h3>Nenhuma agenda encontrada</h3>
            <p>Não foi possível encontrar agendas públicas com o termo "<strong><?= htmlspecialchars($paginationData['search']) ?></strong>".</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Ver Todas as Agendas
            </a>
        <?php else: ?>
            <h3>Nenhuma agenda pública encontrada</h3>
            <p>Não há agendas públicas disponíveis no momento.</p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= PUBLIC_URL ?>/agendas" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Criar Nova Agenda
                </a>
            <?php else: ?>
                <a href="<?= PUBLIC_URL ?>/login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="public-agendas-section">
        <h2><i class="fas fa-globe"></i> Agendas Públicas</h2>
        <?php if (!empty($paginationData['search'])): ?>
            <div class="search-info">
                <i class="fas fa-search"></i> 
                Resultados para "<strong><?= htmlspecialchars($paginationData['search']) ?></strong>": 
                <?= $paginationData['total_items'] ?> agenda(s) encontrada(s)
            </div>
        <?php else: ?>
            <p>Visualize os compromissos das agendas públicas disponíveis e crie novos compromissos.</p>
        <?php endif; ?>
        
        <div class="public-agendas-table-container">
            <table class="public-agendas-table">
                <thead>
                    <tr>
                        <th>Agenda</th>
                        <th>Descrição</th>
                        <th>Responsável</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($publicAgendas as $agenda): ?>
                        <tr style="border-left-color: <?= htmlspecialchars($agenda['color'] ?? '#3788d8') ?>;">
                            <td>
                                <strong><?= htmlspecialchars($agenda['title']) ?></strong>
                            </td>
                            <td>
                                <?= htmlspecialchars($agenda['description'] ?: 'Sem descrição') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($agenda['owner_name'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!empty($agenda['public_hash'])): ?>
                                        <a href="<?= BASE_URL ?>/public-agenda/<?= $agenda['public_hash'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> Ver Agenda
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="<?= BASE_URL ?>/compromissos/new-public?agenda_id=<?= $agenda['id'] ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Criar Compromisso
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/compromissos/external-form?agenda_id=<?= $agenda['id'] ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Criar Compromisso
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- PAGINAÇÃO -->
        <?php if ($paginationData['total_pages'] > 1): ?>
            <div class="pagination-container">
                <nav class="pagination">
                    <?php
                    $baseUrl = BASE_URL . '/?';
                    if (!empty($paginationData['search'])) {
                        $baseUrl .= 'search=' . urlencode($paginationData['search']) . '&';
                    }
                    
                    // Botão "Anterior"
                    if ($paginationData['current_page'] > 1):
                        $prevPage = $paginationData['current_page'] - 1;
                    ?>
                        <a href="<?= $baseUrl ?>page=<?= $prevPage ?>" class="pagination-link prev">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php else: ?>
                        <span class="pagination-link prev disabled">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </span>
                    <?php endif; ?>
                    
                    <?php
                    // Páginas numeradas
                    $start = max(1, $paginationData['current_page'] - 2);
                    $end = min($paginationData['total_pages'], $paginationData['current_page'] + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                        if ($i == $paginationData['current_page']):
                    ?>
                            <span class="pagination-link current"><?= $i ?></span>
                    <?php else: ?>
                            <a href="<?= $baseUrl ?>page=<?= $i ?>" class="pagination-link"><?= $i ?></a>
                    <?php 
                        endif;
                    endfor; 
                    ?>
                    
                    <?php
                    // Botão "Próximo"
                    if ($paginationData['current_page'] < $paginationData['total_pages']):
                        $nextPage = $paginationData['current_page'] + 1;
                    ?>
                        <a href="<?= $baseUrl ?>page=<?= $nextPage ?>" class="pagination-link next">
                            Próximo <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-link next disabled">
                            Próximo <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus no campo de busca
    const searchInput = document.querySelector('.search-input-large');
    if (searchInput && !searchInput.value) {
        searchInput.focus();
    }
    
    // Hover nas linhas da tabela
    const tableRows = document.querySelectorAll('.public-agendas-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>