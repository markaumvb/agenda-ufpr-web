/**
 * Usado em todas as páginas que têm busca: agendas, agendas/all, shares/shared
 */

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

    if (!searchForm || !searchInput || !searchBox) {
      return; // Não há elementos de busca na página
    }

    setupSearchForm(searchForm, searchInput, searchBox);
    setupSearchEnhancements(searchInput, searchBox);
    highlightCurrentSearch();
    setupPagination();

    console.log("✅ AgendaSearch inicializado");
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

        // Fallback
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
        box.classList.remove("has-search");
        this.focus();
      }
    });
  }

  function setupSearchEnhancements(input, box) {
    // Verificar busca ativa na URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentSearch = urlParams.get("search");

    if (currentSearch && currentSearch.trim() !== "") {
      box.classList.add("has-search");
    }

    // Feedback visual durante digitação
    input.addEventListener("input", function () {
      const query = this.value.trim();

      if (query.length > 0) {
        box.classList.add("has-search");
      } else {
        box.classList.remove("has-search");
      }
    });

    // Placeholder dinâmico
    setupDynamicPlaceholder(input);

    // Auto-focus se não há resultados
    const emptyStates = document.querySelectorAll(".empty-state");
    if (emptyStates.length > 0 && currentSearch) {
      input.focus();
      input.select();
    }
  }

  function setupDynamicPlaceholder(input) {
    let placeholderIndex = 0;

    const interval = setInterval(function () {
      if (input.value.trim() === "" && document.activeElement !== input) {
        placeholderIndex = (placeholderIndex + 1) % config.placeholders.length;
        input.placeholder = config.placeholders[placeholderIndex];
      }
    }, config.placeholderInterval);

    // Limpar interval ao sair da página
    window.addEventListener("beforeunload", function () {
      clearInterval(interval);
    });
  }

  function highlightCurrentSearch() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get("search");

    if (searchTerm && searchTerm.trim() !== "") {
      highlightSearchTerms(searchTerm.trim());
    }
  }

  function highlightSearchTerms(searchTerm) {
    const cards = document.querySelectorAll(".agenda-card");
    const searchRegex = new RegExp(`(${escapeRegex(searchTerm)})`, "gi");

    cards.forEach((card) => {
      const title = card.querySelector(".agenda-title");
      const description = card.querySelector(".agenda-description");
      const owner = card.querySelector(".agenda-owner");

      if (title) {
        highlightElement(title, searchRegex);
      }

      if (description && !description.classList.contains("text-muted")) {
        highlightElement(description, searchRegex);
      }

      if (owner) {
        highlightElement(owner, searchRegex);
      }
    });
  }

  function highlightElement(element, regex) {
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
      const highlightedText = originalText.replace(regex, "<mark>$1</mark>");

      if (highlightedText !== originalText) {
        const span = document.createElement("span");
        span.innerHTML = highlightedText;
        textNode.parentNode.replaceChild(span, textNode);
      }
    });
  }

  function setupPagination() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = parseInt(urlParams.get("page")) || 1;
    const paginationLinks = document.querySelectorAll(
      ".pagination-link:not(.disabled)"
    );

    paginationLinks.forEach((link) => {
      const linkUrl = new URL(link.href);
      const linkPage =
        parseInt(new URLSearchParams(linkUrl.search).get("page")) || 1;

      if (linkPage === currentPage) {
        link.classList.add("current");
      }
    });
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
    `;

    messageDiv.innerHTML = `
      <i class="fas fa-info-circle"></i>
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
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        padding: 1rem;
        font-size: 0.9rem;
        background: white;
        border: 1px solid #dee2e6;
      }

      mark {
        background-color: #fff3cd !important;
        color: #856404 !important;
        padding: 0.1em 0.2em !important;
        border-radius: 3px !important;
        font-weight: 600 !important;
      }
      
      .search-form .btn.loading {
        opacity: 0.7;
        cursor: not-allowed;
        pointer-events: none;
      }
    `;

    document.head.appendChild(styles);
  }

  AgendaSearch.init = init;
  AgendaSearch.showMessage = showMessage;
  AgendaSearch.highlightSearchTerms = highlightSearchTerms;

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
window.showSearchMessage = window.AgendaSearch.showMessage;
