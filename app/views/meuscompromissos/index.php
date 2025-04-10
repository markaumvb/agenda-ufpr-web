<div class="page-header">
    <div class="header-container">
        <h1>Meus Compromissos</h1>
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/agendas" class="btn btn-secondary">Minhas Agendas</a>
        </div>
    </div>
</div>

<?php if (empty($agendasWithCompromissos)): ?>
    <div class="empty-state">
        <p>Você não possui compromissos em nenhuma agenda.</p>
        <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">Ir para Minhas Agendas</a>
    </div>
<?php else: ?>
    <!-- Filtros gerais -->
    <div class="filter-container">
        <div class="filter-group">
            <label for="filter-status">Status:</label>
            <select id="filter-status" class="filter-select">
                <option value="all">Todos</option>
                <option value="pendente">Pendentes</option>
                <option value="realizado">Realizados</option>
                <option value="cancelado">Cancelados</option>
                <option value="aguardando_aprovacao">Aguardando Aprovação</option>
            </select>
        </div>
        
        <div class="filter-group">
            <input type="text" id="filter-search" placeholder="Buscar compromissos..." class="filter-input">
        </div>
        
        <button id="clear-filters" class="btn btn-secondary btn-sm">Limpar Filtros</button>
    </div>

    <!-- Lista de agendas com compromissos -->
    <?php foreach ($agendasWithCompromissos as $agenda): ?>
        <div class="agenda-section" style="border-left: 4px solid <?= $agenda['color'] ?>">
            <div class="agenda-header">
                <h2 class="agenda-title">
                    <?= htmlspecialchars($agenda['title']) ?>
                    <?php if ($agenda['is_owner']): ?>
                        <span class="badge badge-primary">Sua agenda</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Compartilhada</span>
                    <?php endif; ?>
                    
                    <?php if ($agenda['is_public']): ?>
                        <span class="badge badge-success">Pública</span>
                    <?php endif; ?>
                </h2>
                
                <div class="agenda-actions">
                    <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-outline">
                        Ver Calendário
                    </a>
                    
                    <?php if ($agenda['is_owner'] || $agenda['can_edit']): ?>
                        <a href="<?= BASE_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-sm btn-primary">
                            Novo Compromisso
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Compromissos da agenda -->
            <div class="compromissos-list">
                <?php foreach ($agenda['compromissos'] as $compromisso): 
                    $startDate = new DateTime($compromisso['start_datetime']);
                    $endDate = new DateTime($compromisso['end_datetime']);
                ?>
                    <div class="event-card event-status-<?= $compromisso['status'] ?>" 
                         data-status="<?= $compromisso['status'] ?>"
                         data-id="<?= $compromisso['id'] ?>"
                         data-search="<?= htmlspecialchars(strtolower($compromisso['title'] . ' ' . $compromisso['description'] . ' ' . $compromisso['location'])) ?>">
                        
                        <div class="event-header">
                            <h3 class="event-title">
                                <?= htmlspecialchars($compromisso['title']) ?>
                                
                                <?php if (isset($compromisso['created_by_current_user']) && $compromisso['created_by_current_user']): ?>
                                    <span class="badge badge-info">Criado por você</span>
                                <?php endif; ?>
                            </h3>
                            
                            <div class="event-status">
                                <span class="badge badge-<?= $compromisso['status'] ?>">
                                    <?php
                                    $statusLabels = [
                                        'pendente' => 'Pendente',
                                        'realizado' => 'Realizado',
                                        'cancelado' => 'Cancelado',
                                        'aguardando_aprovacao' => 'Aguardando Aprovação'
                                    ];
                                    echo $statusLabels[$compromisso['status']] ?? $compromisso['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="event-details">
                            <div class="event-datetime">
                                <div class="event-date">
                                    <i class="icon-calendar"></i>
                                    <?php if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')): ?>
                                        <?= $startDate->format('d/m/Y') ?>
                                    <?php else: ?>
                                        <?= $startDate->format('d/m/Y') ?> até <?= $endDate->format('d/m/Y') ?>
                                    <?php endif; ?>
                                </div>
                                <div class="event-time">
                                    <i class="icon-clock"></i>
                                    <?= $startDate->format('H:i') ?> às <?= $endDate->format('H:i') ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($compromisso['location'])): ?>
                                <div class="event-location">
                                    <i class="icon-location"></i>
                                    <?= htmlspecialchars($compromisso['location']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($compromisso['description'])): ?>
                                <div class="event-description">
                                    <?= nl2br(htmlspecialchars($compromisso['description'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="event-actions">
                            <?php if ($compromisso['status'] === 'aguardando_aprovacao' && $agenda['is_owner']): ?>
                                <!-- Opções de aprovação/rejeição (apenas para o dono da agenda) -->
                                <form action="<?= BASE_URL ?>/meuscompromissos/approve" method="post" class="action-form">
                                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success" title="Aprovar compromisso">
                                        Aprovar
                                    </button>
                                </form>
                                
                                <form action="<?= BASE_URL ?>/meuscompromissos/reject" method="post" class="action-form">
                                    <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Rejeitar compromisso">
                                        Rejeitar
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Opções regulares para compromissos -->
                                <?php if ($compromisso['status'] !== 'cancelado' && 
                                         ($agenda['is_owner'] || isset($compromisso['created_by_current_user']) && $compromisso['created_by_current_user'])): ?>
                                    <form action="<?= BASE_URL ?>/meuscompromissos/cancel" method="post" class="action-form">
                                        <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Cancelar compromisso">
                                            Cancelar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($compromisso['status'] !== 'cancelado' && 
                                         ($agenda['is_owner'] || $agenda['can_edit'] || 
                                          (isset($compromisso['created_by_current_user']) && $compromisso['created_by_current_user']))): ?>
                                    <a href="<?= BASE_URL ?>/compromissos/edit?id=<?= $compromisso['id'] ?>" class="btn btn-sm btn-secondary" title="Editar compromisso">
                                        Editar
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($agenda['is_owner']): ?>
                                    <form action="<?= BASE_URL ?>/compromissos/delete" method="post" class="action-form" onsubmit="return confirm('Tem certeza que deseja excluir este compromisso?');">
                                        <input type="hidden" name="id" value="<?= $compromisso['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Excluir compromisso">
                                            Excluir
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<style>
    /* Estilos específicos para a página de Meus Compromissos */
    .filter-container {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .agenda-section {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        padding: 1.5rem;
        position: relative;
    }
    
    .agenda-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }
    
    .agenda-title {
        margin: 0;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .agenda-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .compromissos-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-form {
        display: inline;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .agenda-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .agenda-actions {
            width: 100%;
            justify-content: space-between;
        }
        
        .filter-container {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Funcionalidade de filtro
        const filterStatus = document.getElementById('filter-status');
        const filterSearch = document.getElementById('filter-search');
        const clearFilters = document.getElementById('clear-filters');
        const eventCards = document.querySelectorAll('.event-card');
        
        function applyFilters() {
            const statusFilter = filterStatus.value;
            const searchFilter = filterSearch.value.toLowerCase().trim();
            
            // Para controlar visibilidade das seções de agenda
            const agendaSections = document.querySelectorAll('.agenda-section');
            const visibleAgendas = new Set();
            
            // Aplicar filtros aos cards de eventos
            eventCards.forEach(card => {
                const status = card.dataset.status;
                const searchText = card.dataset.search;
                
                // Verificar status
                const statusMatch = statusFilter === 'all' || status === statusFilter;
                
                // Verificar texto de busca
                const searchMatch = !searchFilter || searchText.includes(searchFilter);
                
                // Exibir ou ocultar o card
                if (statusMatch && searchMatch) {
                    card.style.display = 'block';
                    
                    // Marcar a agenda como contendo cards visíveis
                    const agendaSection = card.closest('.agenda-section');
                    if (agendaSection) {
                        visibleAgendas.add(agendaSection);
                    }
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Mostrar/esconder seções de agenda com base nos filtros
            agendaSections.forEach(section => {
                if (visibleAgendas.has(section)) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
            
            // Mostrar mensagem se nenhuma agenda estiver visível
            const noResults = document.querySelector('.no-results');
            if (noResults) {
                if (visibleAgendas.size === 0) {
                    noResults.style.display = 'block';
                } else {
                    noResults.style.display = 'none';
                }
            }
        }
        
        // Adicionar event listeners para os filtros
        if (filterStatus) {
            filterStatus.addEventListener('change', applyFilters);
        }
        
        if (filterSearch) {
            filterSearch.addEventListener('input', applyFilters);
        }
        
        // Botão para limpar filtros
        if (clearFilters) {
            clearFilters.addEventListener('click', function() {
                if (filterStatus) filterStatus.value = 'all';
                if (filterSearch) filterSearch.value = '';
                applyFilters();
            });
        }
        
        // Inicializar a visibilidade inicial das seções de agenda
        const agendaSections = document.querySelectorAll('.agenda-section');
        agendaSections.forEach(section => {
            const visibleCards = section.querySelectorAll('.event-card[style="display: block"]');
            if (visibleCards.length === 0 && filterStatus.value !== 'all') {
                section.style.display = 'none';
            }
        });
    });
</script>