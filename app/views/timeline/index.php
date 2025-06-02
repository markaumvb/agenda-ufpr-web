<div class="container">
    <div class="page-header">
        <div class="header-container">
            <h1>Linha do Tempo Pública</h1>
            <div class="header-actions">
                <a href="<?= PUBLIC_URL ?>/" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Início
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros aprimorados -->
    <div class="timeline-filters">
        <form action="<?= PUBLIC_URL ?>/timeline" method="get" class="filter-form">
            <div class="row">
                <!-- Primeira linha: Data e Busca -->
                <div class="col-lg-8 mb-3">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="date-picker">📅 Data Selecionada</label>
                            <input type="date" id="date-picker" name="date" class="form-control" 
                                   value="<?= htmlspecialchars($date->format('Y-m-d')) ?>"
                                   onchange="this.form.submit()">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="search-input">🔍 Buscar Compromissos</label>
                            <input type="text" id="search-input" name="search" class="form-control" 
                                  placeholder="Digite título, descrição ou local..." 
                                  value="<?= htmlspecialchars($searchQuery) ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Segunda linha: Filtro de Agendas -->
                <?php if (!empty($publicAgendas)): ?>
                <div class="col-lg-4 mb-3">
                    <label>🗂️ Filtrar por Agendas</label>
                    <div class="agendas-filter-container">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="select-all-agendas" 
                                <?= empty($selectedAgendas) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="select-all-agendas">
                                <strong>✅ Selecionar Todas</strong>
                            </label>
                        </div>
                        <hr style="margin: 0.5rem 0; border-color: #cbd5e0;">
                        <div class="agendas-scroll-area">
                            <?php foreach ($publicAgendas as $index => $agenda): ?>
                            <div class="form-check agenda-item">
                                <input class="form-check-input agenda-checkbox" type="checkbox" 
                                       name="agendas[]" value="<?= $agenda['id'] ?>" id="agenda-<?= $agenda['id'] ?>"
                                       <?= (empty($selectedAgendas) || in_array($agenda['id'], $selectedAgendas)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="agenda-<?= $agenda['id'] ?>">
                                    <span class="agenda-color-dot" style="background-color: <?= htmlspecialchars($agenda['color']) ?>;"></span>
                                    <span class="agenda-details">
                                        <strong><?= htmlspecialchars($agenda['title']) ?></strong>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Botões de ação -->
            <div class="row">
                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar Compromissos
                    </button>
                    <a href="<?= PUBLIC_URL ?>/timeline" class="btn btn-secondary btn-clear-filters">
                        <i class="fas fa-refresh"></i> Limpar Filtros
                    </a>
                </div>
            </div>
        </form>
    </div>


    <!-- Opções de visualização aprimoradas -->
    <div class="view-options">
        <div class="btn-group" role="group" aria-label="Opções de visualização">
            <button type="button" class="btn btn-outline-primary view-option active" data-view="timeGridDay" title="Visualização por dia">
                <i class="fas fa-calendar-day"></i> Dia
            </button>
            <button type="button" class="btn btn-outline-primary view-option" data-view="listDay" title="Visualização em lista">
                <i class="fas fa-list"></i> Lista
            </button>
        </div>
    </div>

    <!-- Container do calendário sempre visível -->
    <div class="timeline-container">
        <div id="calendar"></div>
        
        <?php if (empty($allEvents)): ?>
        <!-- Mensagem quando não há eventos -->
        <div class="empty-timeline-message" style="display: none;">
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3>Nenhum compromisso encontrado</h3>
                <p>Não há compromissos públicos para a data selecionada: <strong><?= $date->format('d/m/Y') ?></strong></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Estatísticas rápidas -->
    <?php if (!empty($publicAgendas)): ?>
    <div class="timeline-stats">
        <div class="stats-container">
            <h4><i class="fas fa-chart-bar"></i> Estatísticas</h4>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?= count($publicAgendas) ?></span>
                    <span class="stat-label">Agendas Públicas</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= count($allEvents) ?></span>
                    <span class="stat-label">Compromissos Hoje</span>
                </div>
                <?php 
                $statusCounts = [];
                foreach ($allEvents as $event) {
                    $status = $event['status'] ?? 'pendente';
                    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                }
                ?>
                <?php if (isset($statusCounts['pendente'])): ?>
                <div class="stat-item status-pendente">
                    <span class="stat-number"><?= $statusCounts['pendente'] ?></span>
                    <span class="stat-label">Pendentes</span>
                </div>
                <?php endif; ?>
                <?php if (isset($statusCounts['realizado'])): ?>
                <div class="stat-item status-realizado">
                    <span class="stat-number"><?= $statusCounts['realizado'] ?></span>
                    <span class="stat-label">Realizados</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal aprimorado para detalhes do evento -->
    <div id="event-modal" class="modal fade" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">
                        <i class="fas fa-info-circle"></i> Detalhes do Compromisso
                    </h5>
                    <button type="button" class="close" onclick="closeModal()" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="event-details">
                    <!-- Detalhes do evento serão inseridos aqui via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionais inline para melhor integração -->
<style>
    .page-description {
        color: #6c757d;
        font-size: 1.1rem;
        margin-top: 0.5rem;
        margin-bottom: 0;
    }

    .date-info {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #004a8f;
    }

    .selected-date-display {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .selected-date-display h3 {
        margin: 0;
        color: #004a8f;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .events-count {
        background: #004a8f;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .timeline-stats {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .timeline-stats h4 {
        color: #004a8f;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        transition: transform 0.2s ease;
    }

    .stat-item:hover {
        transform: translateY(-2px);
    }

    .stat-item.status-pendente {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    }

    .stat-item.status-realizado {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    }

    .stat-number {
        display: block;
        font-size: 2rem;
        font-weight: 700;
        color: #004a8f;
        line-height: 1;
    }

    .stat-label {
        display: block;
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 0.25rem;
        font-weight: 500;
    }

    .empty-timeline-message {
        text-align: center;
        padding: 3rem 2rem;
    }

    .empty-icon {
        font-size: 4rem;
        color: #cbd5e0;
        margin-bottom: 1rem;
    }

    .empty-suggestions {
        margin-top: 1.5rem;
        text-align: left;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .empty-suggestions ul {
        list-style-type: none;
        padding: 0;
    }

    .empty-suggestions li {
        padding: 0.25rem 0;
        position: relative;
        padding-left: 1.5rem;
    }

    .empty-suggestions li::before {
        content: "💡";
        position: absolute;
        left: 0;
    }

    .agenda-details {
        flex: 1;
        overflow: hidden;
    }

    .agenda-details strong {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Melhorar espaçamento entre agendas */
    .agenda-item {
        margin-bottom: 0.5rem !important;
        padding: 0.4rem 0.5rem;
        border-radius: 6px;
        transition: background-color 0.2s ease;
    }

    .agenda-item:hover {
        background: rgba(0, 74, 143, 0.05);
    }

    /* Botão limpar filtros melhorado */
    .btn-clear-filters {
        background: #6c757d !important;
        border-color: #6c757d !important;
        color: white !important;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-clear-filters:hover {
        background: #5a6268 !important;
        border-color: #5a6268 !important;
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    @media (max-width: 768px) {
        .selected-date-display {
            flex-direction: column;
            align-items: flex-start;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .stat-number {
            font-size: 1.5rem;
        }
    }
</style>

<!-- Scripts para a timeline -->
<script>
// Configurar dados globais para o JavaScript
window.PUBLIC_URL = '<?= PUBLIC_URL ?>';

// Preparar eventos para o JavaScript com cores das agendas
window.timelineEvents = [
    <?php foreach ($allEvents as $index => $event): ?>
    {
        id: <?= json_encode($event['id']) ?>,
        title: <?= json_encode($event['title']) ?>,
        start: <?= json_encode($event['start_datetime']) ?>,
        end: <?= json_encode($event['end_datetime']) ?>,
        description: <?= json_encode($event['description'] ?? '') ?>,
        location: <?= json_encode($event['location'] ?? '') ?>,
        status: <?= json_encode($event['status']) ?>,
        agendaInfo: {
            id: <?= json_encode($event['agenda_info']['id'] ?? '') ?>,
            title: <?= json_encode($event['agenda_info']['title'] ?? 'Agenda') ?>,
            color: <?= json_encode($event['agenda_info']['color'] ?? '#3788d8') ?>,
            owner: <?= json_encode($event['agenda_info']['owner_name'] ?? 'Desconhecido') ?>
        },
        creatorName: <?= json_encode($event['creator_name'] ?? '') ?>
    }<?= $index < count($allEvents) - 1 ? ',' : '' ?>
    <?php endforeach; ?>
];

// Guardar a data selecionada
window.selectedDate = '<?= $date->format('Y-m-d') ?>';

// Configurar checkbox "Selecionar Todas"
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-agendas');
    const agendaCheckboxes = document.querySelectorAll('.agenda-checkbox');
    
    if (selectAllCheckbox && agendaCheckboxes.length > 0) {
        // Função para atualizar o estado do "Selecionar Todas"
        function updateSelectAllState() {
            const checkedCount = document.querySelectorAll('.agenda-checkbox:checked').length;
            selectAllCheckbox.checked = checkedCount === agendaCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < agendaCheckboxes.length;
        }
        
        // Quando clica em "Selecionar Todas"
        selectAllCheckbox.addEventListener('change', function() {
            agendaCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Quando clica em checkbox individual
        agendaCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectAllState);
        });
        
        // Estado inicial
        updateSelectAllState();
    }
    
    // Auto-submit no campo de busca após pausa na digitação
    let searchTimeout;
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 1000); // Submit após 1 segundo de pausa
        });
    }
});
</script>

<!-- Carregar o script da timeline melhorado -->
<script src="<?= PUBLIC_URL ?>/app/assets/js/timeline.js"></script>