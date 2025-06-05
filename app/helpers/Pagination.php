<?php

class Pagination {
    private $totalItems;      // Total de itens
    private $itemsPerPage;    // Itens por página
    private $currentPage;     // Página atual
    private $totalPages;      // Total de páginas
    private $baseUrl;         // URL base para links
    private $queryParams;     // Parâmetros de consulta adicionais


    public function __construct($totalItems, $itemsPerPage = 10, $currentPage = 1, $baseUrl = '', $queryParams = []) {
        $this->totalItems = (int) $totalItems;
        $this->itemsPerPage = (int) $itemsPerPage;
        $this->currentPage = (int) $currentPage;
        $this->baseUrl = $baseUrl;
        $this->queryParams = $queryParams;
        
        // Calcular total de páginas
        $this->totalPages = ceil($this->totalItems / $this->itemsPerPage);
        
        // Ajustar página atual se necessário
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        } elseif ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }
    }
    

    public function getOffset() {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }
    

    public function getLimit() {
        return $this->itemsPerPage;
    }
    

    public function getCurrentPage() {
        return $this->currentPage;
    }
    

    public function getTotalPages() {
        return $this->totalPages;
    }
    

    public function hasNextPage() {
        return $this->currentPage < $this->totalPages;
    }
    

    public function hasPreviousPage() {
        return $this->currentPage > 1;
    }
    

    private function createUrl($page) {
        $params = $this->queryParams;
        $params['page'] = $page;
        
        return $this->baseUrl . '?' . http_build_query($params);
    }
    

    public function createLinks($maxLinks = 5) {
        if ($this->totalPages <= 1) {
            return '';
        }
        
        $html = '<div class="pagination">';
        
        // Botão "Anterior"
        if ($this->hasPreviousPage()) {
            $html .= '<a href="' . $this->createUrl($this->currentPage - 1) . '" class="pagination-link prev">&laquo; Anterior</a>';
        } else {
            $html .= '<span class="pagination-link prev disabled">&laquo; Anterior</span>';
        }
        
        // Calcular range de páginas a exibir
        $startPage = max(1, $this->currentPage - floor($maxLinks / 2));
        $endPage = min($this->totalPages, $startPage + $maxLinks - 1);
        
        // Ajustar startPage se necessário
        $startPage = max(1, $endPage - $maxLinks + 1);
        
        // Link para primeira página se não estiver no range
        if ($startPage > 1) {
            $html .= '<a href="' . $this->createUrl(1) . '" class="pagination-link">1</a>';
            if ($startPage > 2) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
        }
        
        // Links de página
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $this->currentPage) {
                $html .= '<span class="pagination-link current">' . $i . '</span>';
            } else {
                $html .= '<a href="' . $this->createUrl($i) . '" class="pagination-link">' . $i . '</a>';
            }
        }
        
        // Link para última página se não estiver no range
        if ($endPage < $this->totalPages) {
            if ($endPage < $this->totalPages - 1) {
                $html .= '<span class="pagination-ellipsis">...</span>';
            }
            $html .= '<a href="' . $this->createUrl($this->totalPages) . '" class="pagination-link">' . $this->totalPages . '</a>';
        }
        
        // Botão "Próxima"
        if ($this->hasNextPage()) {
            $html .= '<a href="' . $this->createUrl($this->currentPage + 1) . '" class="pagination-link next">Próxima &raquo;</a>';
        } else {
            $html .= '<span class="pagination-link next disabled">Próxima &raquo;</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    

    public function getInfo() {
        $start = ($this->currentPage - 1) * $this->itemsPerPage + 1;
        $end = min($start + $this->itemsPerPage - 1, $this->totalItems);
        
        if ($this->totalItems === 0) {
            return 'Nenhum item encontrado';
        }
        
        return "Exibindo {$start} a {$end} de {$this->totalItems} itens";
    }
}