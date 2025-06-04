// Namespace para evitar conflitos
window.AgendaSearch = window.AgendaSearch || {};

(function () {
  "use strict";

  const config = {
    placeholders: [
      "Pesquisar agendas...",
      "Digite o nome da agenda...",
      "Buscar por responsável...",
      "Procurar por descrição...",
    ],
    placeholderInterval: 4000,
    messageTimeout: 3000,
  };

  // ==========================================
  // INICIALIZAÇÃO
  // ==========================================

  function init() {
    const searchForm = document.querySelector(".search-form");
    const searchInput = document.querySelector(
      ".search-form input[name='search']"
    );
    const searchBox = document.querySelector(".search-box");

    if (!searchForm || !searchInput) {
      return; // Não há elementos de busca na página
    }

    setupSearchForm(searchForm, searchInput, searchBox);
    setupSearchEnhancements(searchInput, searchBox);
    highlightCurrentSearch();
    setupPagination();
  }

  function setupSearchForm(form, input, box) {
    const searchButton = form.querySelector(".btn-primary");
    const clearButton = form.querySelector(".btn-secondary");

    // Event listener para submissão
    form.addEventListener("submit", function (e) {
      const query = input.value.trim();

      if (!query) {
        e.preventDefault();
        window.location.href = form.getAttribute("action");
        return;
      }

      // Estado de loading
      if (searchButton) {
        searchButton.classList.add("loading");
        searchButton.disabled = true;

        const originalText = searchButton.innerHTML;
        searchButton.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> Buscando...';

        // Fallback para remover loading após 10 segundos
        setTimeout(function () {
          searchButton.classList.remove("loading");
          searchButton.disabled = false;
          searchButton.innerHTML = originalText;
        }, 10000);
      }
    });

    // Event listener para botão limpar
    if (clearButton) {
      clearButton.addEventListener("click", function (e) {
        e.preventDefault();
        console.log("🗑️ Limpando busca");
        window.location.href = form.getAttribute("action");
      });
    }

    // Teclas especiais
    input.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        form.submit();
      }
    });

    input.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && this.value.trim() !== "") {
        this.value = "";
        if (box) box.classList.remove("has-search");
        this.focus();
      }
    });
  }

  function setupSearchEnhancements(input, box) {
    // Verificar busca ativa na URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentSearch = urlParams.get("search");

    if (currentSearch && currentSearch.trim() !== "") {
      if (box) box.classList.add("has-search");
    }

    // Feedback visual durante digitação
    input.addEventListener("input", function () {
      const query = this.value.trim();

      if (query.length > 0) {
        if (box) box.classList.add("has-search");
      } else {
        if (box) box.classList.remove("has-search");
      }
    });

    // Placeholder dinâmico
    setupDynamicPlaceholder(input);

    // Auto-focus se não há resultados
    const emptyStates = document.querySelectorAll(".empty-state");
    if (emptyStates.length > 0 && currentSearch) {
      setTimeout(() => {
        input.focus();
        input.select();
      }, 100);
    }
  }

  function setupDynamicPlaceholder(input) {
    let placeholderIndex = 0;
    const originalPlaceholder = input.placeholder;

    const interval = setInterval(function () {
      if (input.value.trim() === "" && document.activeElement !== input) {
        placeholderIndex = (placeholderIndex + 1) % config.placeholders.length;
        input.placeholder = config.placeholders[placeholderIndex];
      }
    }, config.placeholderInterval);

    // Restaurar placeholder original ao focar
    input.addEventListener("focus", function () {
      input.placeholder = originalPlaceholder;
    });

    // Limpar interval ao sair da página
    window.addEventListener("beforeunload", function () {
      clearInterval(interval);
    });
  }

  function highlightCurrentSearch() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get("search");

    if (searchTerm && searchTerm.trim() !== "") {
      console.log("🎯 Destacando termos de busca:", searchTerm);
      highlightSearchTerms(searchTerm.trim());
    }
  }

  function highlightSearchTerms(searchTerm) {
    // Elementos que podem conter os termos de busca
    const selectors = [
      ".agenda-card .agenda-title",
      ".agenda-card .agenda-description",
      ".agenda-card .agenda-owner",
      ".public-agendas-table td",
    ];

    const searchRegex = new RegExp(`(${escapeRegex(searchTerm)})`, "gi");
    let highlightCount = 0;

    selectors.forEach((selector) => {
      const elements = document.querySelectorAll(selector);

      elements.forEach((element) => {
        // Pular elementos que já foram processados ou que são apenas texto mudo
        if (
          element.classList.contains("text-muted") ||
          element.hasAttribute("data-highlighted")
        ) {
          return;
        }

        const walker = document.createTreeWalker(
          element,
          NodeFilter.SHOW_TEXT,
          null,
          false
        );

        const textNodes = [];
        let node;

        while ((node = walker.nextNode())) {
          textNodes.push(node);
        }

        textNodes.forEach((textNode) => {
          const originalText = textNode.textContent;
          const highlightedText = originalText.replace(
            searchRegex,
            "<mark>$1</mark>"
          );

          if (highlightedText !== originalText) {
            const span = document.createElement("span");
            span.innerHTML = highlightedText;
            textNode.parentNode.replaceChild(span, textNode);
            highlightCount++;
          }
        });

        element.setAttribute("data-highlighted", "true");
      });
    });

    if (highlightCount > 0) {
      console.log(`✨ ${highlightCount} ocorrências destacadas`);
    }
  }

  function setupPagination() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = parseInt(urlParams.get("page")) || 1;
    const paginationLinks = document.querySelectorAll(
      ".pagination-link:not(.disabled)"
    );

    paginationLinks.forEach((link) => {
      try {
        const linkUrl = new URL(link.href);
        const linkPage =
          parseInt(new URLSearchParams(linkUrl.search).get("page")) || 1;

        if (linkPage === currentPage) {
          link.classList.add("current");
        }
      } catch (error) {
        console.warn("⚠️ Erro ao processar link de paginação:", error);
      }
    });

    if (paginationLinks.length > 0) {
      console.log(
        `📄 Configurada paginação para ${paginationLinks.length} links`
      );
    }
  }

  function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  function showMessage(message, type = "info") {
    // Remover mensagem anterior
    const existingMessage = document.querySelector(".search-message");
    if (existingMessage) {
      existingMessage.remove();
    }

    // Criar nova mensagem
    const messageDiv = document.createElement("div");
    messageDiv.className = `search-message alert alert-${type}`;
    messageDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1050;
      max-width: 300px;
      animation: slideInRight 0.3s ease-out;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      background: white;
      border: 1px solid #dee2e6;
    `;

    const iconMap = {
      info: "fas fa-info-circle",
      success: "fas fa-check-circle",
      warning: "fas fa-exclamation-triangle",
      danger: "fas fa-times-circle",
    };

    messageDiv.innerHTML = `
      <i class="${iconMap[type] || iconMap.info}"></i>
      ${message}
    `;

    document.body.appendChild(messageDiv);

    // Remover após timeout
    setTimeout(function () {
      if (messageDiv.parentNode) {
        messageDiv.style.animation = "slideOutRight 0.3s ease-in";
        setTimeout(function () {
          if (messageDiv.parentNode) {
            messageDiv.remove();
          }
        }, 300);
      }
    }, config.messageTimeout);

    console.log(`💬 Mensagem exibida: ${message}`);
  }

  function injectStyles() {
    if (document.querySelector("#agenda-search-styles")) {
      return; // Já injetado
    }

    const styles = document.createElement("style");
    styles.id = "agenda-search-styles";
    styles.textContent = `
      @keyframes slideInRight {
        from { opacity: 0; transform: translateX(100%); }
        to { opacity: 1; transform: translateX(0); }
      }
      
      @keyframes slideOutRight {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
      }
      
      .search-message {
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .search-message i {
        color: #004a8f;
        font-size: 1.1rem;
        flex-shrink: 0;
      }

      mark {
        background-color: #fff3cd !important;
        color: #856404 !important;
        padding: 0.1em 0.2em !important;
        border-radius: 3px !important;
        font-weight: 600 !important;
        animation: highlight 0.5s ease-out;
      }

      @keyframes highlight {
        0% { background-color: #ffeb3b; }
        100% { background-color: #fff3cd; }
      }
      
      .search-form .btn.loading {
        opacity: 0.7 !important;
        cursor: not-allowed !important;
        pointer-events: none !important;
      }

      .search-box.has-search {
        background: linear-gradient(135deg, #e6f3ff 0%, #f0f7ff 100%) !important;
        border-color: #004a8f !important;
        box-shadow: 0 4px 12px rgba(0, 74, 143, 0.15) !important;
      }

      .search-box.has-search input[type="text"] {
        border-color: #004a8f !important;
        background-color: #ffffff !important;
        font-weight: 500 !important;
      }

      .results-count {
        font-size: 0.9rem;
        font-weight: 500;
        color: #718096;
        background: rgba(113, 128, 150, 0.1);
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        border: 1px solid rgba(113, 128, 150, 0.2);
      }

      .search-info {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 2px solid #f6ad55;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        color: #856404;
        font-size: 0.95rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
        animation: slideIn 0.3s ease-out;
      }

      .search-info i {
        color: #d69e2e;
        font-size: 1.1rem;
        flex-shrink: 0;
      }

      .search-info strong {
        color: #744210;
        font-weight: 600;
      }

      @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
      }
    `;

    document.head.appendChild(styles);
    console.log("💄 Estilos de busca injetados");
  }

  // ==========================================
  // FUNÇÕES PÚBLICAS
  // ==========================================

  AgendaSearch.init = init;
  AgendaSearch.showMessage = showMessage;
  AgendaSearch.highlightSearchTerms = highlightSearchTerms;

  // ==========================================
  // AUTO-INICIALIZAÇÃO
  // ==========================================

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      injectStyles();
      init();
    });
  } else {
    injectStyles();
    init();
  }
})();

// Exposição global para compatibilidade
window.showSearchMessage = function (message, type) {
  if (window.AgendaSearch && window.AgendaSearch.showMessage) {
    window.AgendaSearch.showMessage(message, type);
  }
};
