<?php

// Verificar se as variáveis necessárias estão definidas
if (!isset($pagination) || !($pagination instanceof Pagination)) {
    return; // Não renderizar se não há objeto de paginação
}

// Verificar se há mais de uma página
if ($pagination->getTotalPages() <= 1) {
    return; // Não renderizar se há apenas uma página
}

// Obter informações da paginação
$currentPage = $pagination->getCurrentPage();
$totalPages = $pagination->getTotalPages();
$hasNext = $pagination->hasNextPage();
$hasPrev = $pagination->hasPreviousPage();

// Informações adicionais opcionais
$paginationInfo = $pagination->getInfo();
$baseUrl = isset($baseUrl) ? $baseUrl : $_SERVER['REQUEST_URI'];
$queryParams = isset($queryParams) ? $queryParams : [];

// Construir URL base sem parâmetro page
$baseUrl = strtok($baseUrl, '?');

// Função para criar URL com parâmetros
function createPaginationUrl($baseUrl, $page, $queryParams = []) {
    $params = $queryParams;
    $params['page'] = $page;
    return $baseUrl . '?' . http_build_query($params);
}
?>

<div class="pagination-container">
    <!-- Informações da paginação -->
    <div class="pagination-info">
        <i class="fas fa-chart-bar"></i>
        <?= $paginationInfo ?>
        <?php if (isset($extraInfo) && !empty($extraInfo)): ?>
            - <?= $extraInfo ?>
        <?php endif; ?>
    </div>
    
    <!-- Links de navegação -->
    <nav class="pagination">
        <!-- Botão "Anterior" -->
        <?php if ($hasPrev): ?>
            <a href="<?= createPaginationUrl($baseUrl, $currentPage - 1, $queryParams) ?>" 
               class="pagination-link prev">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
        <?php else: ?>
            <span class="pagination-link prev disabled">
                <i class="fas fa-chevron-left"></i> Anterior
            </span>
        <?php endif; ?>
        
        <?php
        // Calcular range de páginas a exibir
        $maxLinks = 5;
        $startPage = max(1, $currentPage - floor($maxLinks / 2));
        $endPage = min($totalPages, $startPage + $maxLinks - 1);
        
        // Ajustar startPage se necessário
        $startPage = max(1, $endPage - $maxLinks + 1);
        
        // Link para primeira página se não estiver no range
        if ($startPage > 1): ?>
            <a href="<?= createPaginationUrl($baseUrl, 1, $queryParams) ?>" 
               class="pagination-link">1</a>
            <?php if ($startPage > 2): ?>
                <span class="pagination-ellipsis">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Links de página -->
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <?php if ($i == $currentPage): ?>
                <span class="pagination-link current"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= createPaginationUrl($baseUrl, $i, $queryParams) ?>" 
                   class="pagination-link"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <!-- Link para última página se não estiver no range -->
        <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
                <span class="pagination-ellipsis">...</span>
            <?php endif; ?>
            <a href="<?= createPaginationUrl($baseUrl, $totalPages, $queryParams) ?>" 
               class="pagination-link"><?= $totalPages ?></a>
        <?php endif; ?>
        
        <!-- Botão "Próximo" -->
        <?php if ($hasNext): ?>
            <a href="<?= createPaginationUrl($baseUrl, $currentPage + 1, $queryParams) ?>" 
               class="pagination-link next">
                Próximo <i class="fas fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="pagination-link next disabled">
                Próximo <i class="fas fa-chevron-right"></i>
            </span>
        <?php endif; ?>
    </nav>
</div>