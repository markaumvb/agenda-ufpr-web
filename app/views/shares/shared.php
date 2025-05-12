
<div class="page-header">

    <?php if (isset($mostraDiagnostico) && $mostraDiagnostico): ?>
<!-- Bloco de diagnóstico - ajuda a identificar problemas -->
<div class="diagnostic-block" style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;">
    <h3>Informações de Diagnóstico</h3>
    <p><strong>Usuário atual:</strong> ID <?= $_SESSION['user_id'] ?> (<?= htmlspecialchars($diagnostico['dados_usuario']['name'] ?? 'Nome não encontrado') ?>)</p>
    
    <div style="margin-top: 10px;">
        <p><strong>Resumo:</strong></p>
        <ul>
            <li>Agendas próprias: <?= $diagnostico['total_agendas_proprias'] ?></li>
            <li>Agendas compartilhadas comigo: <?= count($diagnostico['compartilhados_comigo']) ?></li>
            <li>Agendas que compartilhei com outros: <?= count($diagnostico['compartilhados_por_mim']) ?></li>
        </ul>
    </div>
    
    <?php if (count($diagnostico['compartilhados_comigo']) > 0): ?>
    <div style="margin-top: 10px;">
        <p><strong>Detalhes das agendas compartilhadas comigo:</strong></p>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Agenda ID</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Título</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Dono</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Ativa</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Permissão Edição</th>
            </tr>
            <?php foreach ($diagnostico['compartilhados_comigo'] as $agenda): ?>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= $agenda['agenda_id'] ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($agenda['title']) ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($agenda['owner_name']) ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= $agenda['is_active'] ? 'Sim' : 'Não' ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= $agenda['can_edit'] ? 'Sim' : 'Não' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (count($diagnostico['compartilhados_por_mim']) > 0): ?>
    <div style="margin-top: 10px;">
        <p><strong>Detalhes das agendas que compartilhei:</strong></p>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Agenda ID</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Título</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Ativa</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Compartilhada com</th>
            </tr>
            <?php foreach ($diagnostico['compartilhados_por_mim'] as $agenda): ?>
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= $agenda['id'] ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($agenda['title']) ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= $agenda['is_active'] ? 'Sim' : 'Não' ?></td>
                <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($agenda['shared_with_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 15px; font-size: 12px; color: #666;">
        <p>Esta seção de diagnóstico é temporária e deve ser removida após a correção dos problemas.</p>
    </div>
</div>
<?php endif; ?>
    <div class="header-container">
        <h1>Agendas Compartilhadas</h1>
    </div>
    
    <!-- Barra de pesquisa -->
    <div class="search-box">
        <form action="<?= BASE_URL ?>/shares/shared" method="get" class="search-form">
            <input type="text" name="search" id="search" placeholder="Pesquisar agendas..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                <a href="<?= BASE_URL ?>/shares/shared" class="btn btn-secondary">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Agendas que compartilhei -->
<div class="content-container">
    <h2>Agendas que compartilhei</h2>
    <?php if (empty($mySharedAgendas)): ?>
        <div class="empty-state">
            <p>Você ainda não compartilhou nenhuma agenda.</p>
            <a href="<?= BASE_URL ?>/agendas" class="btn btn-primary">Ver minhas agendas</a>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($mySharedAgendas as $agenda): ?>
                <div class="agenda-card">
                    <div class="agenda-card-header">
                        <div class="agenda-visibility">
                            <?php if ($agenda['is_public']): ?>
                                <span class="badge badge-info">Pública</span>
                            <?php endif; ?>
                            <span class="badge badge-secondary">Compartilhada</span>
                        </div>
                        <h2 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h2>
                    </div>
                    <div class="agenda-card-body">
                        <?php if (!empty($agenda['description'])): ?>
                            <div class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></div>
                        <?php else: ?>
                            <div class="agenda-description text-muted">Sem descrição</div>
                        <?php endif; ?>
                        
                        <div class="agenda-stats">
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['pendentes'] ?></div>
                                <div class="stat-label">Pendentes</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['realizados'] ?></div>
                                <div class="stat-label">Realizados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['cancelados'] ?></div>
                                <div class="stat-label">Cancelados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['aguardando_aprovacao'] ?></div>
                                <div class="stat-label">Aguardando</div>
                            </div>
                        </div>
                    </div>
                    <div class="agenda-card-footer">
                        <div class="agenda-actions">
                            <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-primary">
                                <i class="icon-calendar"></i> Ver Compromissos
                            </a>
                            <a href="<?= BASE_URL ?>/shares?agenda_id=<?= $agenda['id'] ?>" class="btn btn-secondary">
                                <i class="icon-share"></i> Gerenciar Compartilhamentos
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Agendas compartilhadas comigo -->
<div class="content-container">
    <h2>Agendas compartilhadas comigo</h2>
    <?php if (empty($sharedWithMe)): ?>
        <div class="empty-state">
            <p>Nenhuma agenda foi compartilhada com você.</p>
        </div>
    <?php else: ?>
        <div class="agenda-grid">
            <?php foreach ($sharedWithMe as $agenda): ?>
                <div class="agenda-card">
                    <div class="agenda-card-header">
                        <div class="agenda-visibility">
                            <?php if ($agenda['can_edit']): ?>
                                <span class="badge badge-success">Edição</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Visualização</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="agenda-title"><?= htmlspecialchars($agenda['title']) ?></h2>
                    </div>
                    <div class="agenda-card-body">
                        <?php if (!empty($agenda['description'])): ?>
                            <div class="agenda-description"><?= htmlspecialchars($agenda['description']) ?></div>
                        <?php else: ?>
                            <div class="agenda-description text-muted">Sem descrição</div>
                        <?php endif; ?>
                        
                        <div class="agenda-owner">
                            <p>Compartilhada por: <?= htmlspecialchars($agenda['owner_name']) ?></p>
                        </div>
                        
                        <div class="agenda-stats">
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['pendentes'] ?></div>
                                <div class="stat-label">Pendentes</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['realizados'] ?></div>
                                <div class="stat-label">Realizados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['cancelados'] ?></div>
                                <div class="stat-label">Cancelados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value"><?= $agenda['compromissos']['aguardando_aprovacao'] ?></div>
                                <div class="stat-label">Aguardando</div>
                            </div>
                        </div>
                    </div>
                    <div class="agenda-card-footer">
                        <div class="agenda-actions">
                            <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agenda['id'] ?>" class="btn btn-primary">
                                <i class="icon-calendar"></i> Ver Compromissos
                            </a>
                            <?php if ($agenda['can_edit']): ?>
                                <a href="<?= BASE_URL ?>/compromissos/new?agenda_id=<?= $agenda['id'] ?>" class="btn btn-secondary">
                                    <i class="icon-plus"></i> Novo Compromisso
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginação -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando página <?= $page ?> de <?= $totalPages ?>
                </div>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= BASE_URL ?>/shares/shared?page=1<?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-link prev">&laquo; Primeira</a>
                        <a href="<?= BASE_URL ?>/shares/shared?page=<?= $page - 1 ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-link prev">&lsaquo; Anterior</a>
                    <?php else: ?>
                        <span class="pagination-link disabled">&laquo; Primeira</span>
                        <span class="pagination-link disabled">&lsaquo; Anterior</span>
                    <?php endif; ?>
                    
                    <?php
                    // Determinar quais páginas mostrar
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Se estamos mostrando menos de 5 páginas, ajustar para mostrar mais
                    if ($endPage - $startPage < 4) {
                        if ($startPage == 1) {
                            $endPage = min($totalPages, $startPage + 4);
                        } elseif ($endPage == $totalPages) {
                            $startPage = max(1, $endPage - 4);
                        }
                    }
                    
                    // Mostrar primeira página e elipse se necessário
                    if ($startPage > 1) {
                        echo '<a href="' . BASE_URL . '/shares/shared?page=1' . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '" class="pagination-link">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination-ellipsis">&hellip;</span>';
                        }
                    }
                    
                    // Mostrar páginas centrais
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i == $page) ? ' current' : '';
                        echo '<a href="' . BASE_URL . '/shares/shared?page=' . $i . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '" class="pagination-link' . $activeClass . '">' . $i . '</a>';
                    }
                    
                    // Mostrar última página e elipse se necessário
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="pagination-ellipsis">&hellip;</span>';
                        }
                        echo '<a href="' . BASE_URL . '/shares/shared?page=' . $totalPages . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '" class="pagination-link">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= BASE_URL ?>/shares/shared?page=<?= $page + 1 ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-link next">Próxima &rsaquo;</a>
                        <a href="<?= BASE_URL ?>/shares/shared?page=<?= $totalPages ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="pagination-link next">Última &raquo;</a>
                    <?php else: ?>
                        <span class="pagination-link disabled">Próxima &rsaquo;</span>
                        <span class="pagination-link disabled">Última &raquo;</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script src="<?= PUBLIC_URL ?>/app/assets/js/shares/shared.js"></script>