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

<!-- SEÇÃO DE BUSCA -->
<div class="search-box">
    <form class="search-form" method="GET" action="<?= BASE_URL ?>/">
        <div class="search-input-group">
            <input type="text" 
                   name="search" 
                   placeholder="Buscar agendas por título, descrição ou responsável..." 
                   value="<?= htmlspecialchars($paginationData['search'] ?? '') ?>"
                   class="form-control">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Buscar
            </button>
            <?php if (!empty($paginationData['search'])): ?>
                <a href="<?= BASE_URL ?>/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpar
                </a>
            <?php endif; ?>
        </div>
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
        <div class="section-header">
            <h2><i class="fas fa-globe"></i> Agendas Públicas</h2>
            <?php if (!empty($paginationData['search'])): ?>
                <div class="search-results-info">
                    <p>
                        <i class="fas fa-search"></i> 
                        Resultados para "<strong><?= htmlspecialchars($paginationData['search']) ?></strong>": 
                        <?= $paginationData['total_items'] ?> agenda(s) encontrada(s)
                    </p>
                </div>
            <?php else: ?>
                <p>Visualize os compromissos das agendas públicas disponíveis e crie novos compromissos.</p>
            <?php endif; ?>
        </div>
        
        <!-- INFORMAÇÕES DE PAGINAÇÃO -->
        <?php if ($paginationData['total_items'] > 0): ?>
            <div class="pagination-info">
                <span>
                    Mostrando <?= $paginationData['start_item'] ?> - <?= $paginationData['end_item'] ?> 
                    de <?= $paginationData['total_items'] ?> agenda(s)
                </span>
            </div>
        <?php endif; ?>
        
        <div class="public-agendas-table-container">
            <table class="public-agendas-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Agenda</th>
                        <th><i class="fas fa-align-left"></i> Descrição</th>
                        <th><i class="fas fa-user"></i> Responsável</th>
                        <th><i class="fas fa-cogs"></i> Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($publicAgendas as $agenda): ?>
                        <tr style="border-left-color: <?= htmlspecialchars($agenda['color'] ?? '#3788d8') ?>;">
                            <td>
                                <div class="agenda-info">
                                    <span class="agenda-color-indicator" style="background-color: <?= htmlspecialchars($agenda['color'] ?? '#3788d8') ?>;"></span>
                                    <strong><?= htmlspecialchars($agenda['title']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <div class="agenda-description">
                                    <?= htmlspecialchars($agenda['description'] ?: 'Sem descrição') ?>
                                </div>
                            </td>
                            <td>
                                <div class="agenda-owner">
                                    <i class="fas fa-user-circle"></i>
                                    <?= htmlspecialchars($agenda['owner_name'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (!empty($agenda['public_hash'])): ?>
                                        <a href="<?= BASE_URL ?>/public-agenda/<?= $agenda['public_hash'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> Ver Agenda
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // MODIFICAÇÃO PRINCIPAL: Detectar se usuário está logado
                                    if (isset($_SESSION['user_id'])): 
                                        // Usuário LOGADO: usar rota para usuários logados
                                    ?>
                                        <a href="<?= BASE_URL ?>/compromissos/new-public?agenda_id=<?= $agenda['id'] ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Criar Compromisso
                                        </a>
                                    <?php else: 
                                        // Usuário NÃO LOGADO: manter fluxo externo original
                                    ?>
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
                    // Lógica para mostrar páginas
                    $start = max(1, $paginationData['current_page'] - 2);
                    $end = min($paginationData['total_pages'], $paginationData['current_page'] + 2);
                    
                    // Primeira página
                    if ($start > 1):
                    ?>
                        <a href="<?= $baseUrl ?>page=1" class="pagination-link">1</a>
                        <?php if ($start > 2): ?>
                            <span class="pagination-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php
                    // Páginas do meio
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
                    // Última página
                    if ($end < $paginationData['total_pages']):
                        if ($end < $paginationData['total_pages'] - 1):
                    ?>
                            <span class="pagination-ellipsis">...</span>
                    <?php endif; ?>
                        <a href="<?= $baseUrl ?>page=<?= $paginationData['total_pages'] ?>" class="pagination-link"><?= $paginationData['total_pages'] ?></a>
                    <?php endif; ?>
                    
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
        
        <!-- RESUMO -->
        <div class="agendas-summary">
            <p class="summary-text">
                <i class="fas fa-info-circle"></i>
                <strong><?= $paginationData['total_items'] ?></strong> agenda(s) pública(s) 
                <?php if (!empty($paginationData['search'])): ?>
                    encontrada(s) para "<strong><?= htmlspecialchars($paginationData['search']) ?></strong>"
                <?php else: ?>
                    disponível(is) para agendamento
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar efeito hover nas linhas da tabela
    const tableRows = document.querySelectorAll('.public-agendas-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Auto-focus no campo de busca se estiver vazio
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput && !searchInput.value) {
        searchInput.focus();
    }
    
    // Submeter busca ao pressionar Enter
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
});
</script>