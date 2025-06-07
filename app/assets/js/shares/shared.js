document.addEventListener("DOMContentLoaded", function () {
  // ==========================================
  // ELEMENTOS DO DOM
  // ==========================================

  const searchForm = document.querySelector(".search-form");
  const searchInput = document.querySelector(
    ".search-form input[name='search']"
  );
  const searchButton = document.querySelector(".search-form .btn-primary");
  const clearSearchBtn = document.querySelector(".search-form .btn-secondary");
  const searchBox = document.querySelector(".search-box");
  const paginationLinks = document.querySelectorAll(
    ".pagination-link:not(.disabled)"
  );

  if (!searchForm || !searchInput || !searchBox) {
    return;
  }

  // ==========================================
  // FUNCIONALIDADE PRINCIPAL
  // ==========================================

  // Event listener para submissão do formulário
  searchForm.addEventListener("submit", function (e) {
    const query = searchInput.value.trim();

    // Se campo vazio, redirecionar para página sem busca
    if (!query) {
      e.preventDefault();
      window.location.href = searchForm.getAttribute("action");
      return;
    }

    // Adicionar estado de loading no botão
    if (searchButton) {
      searchButton.classList.add("loading");
      searchButton.disabled = true;

      // Adicionar texto de loading
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
  if (clearSearchBtn) {
    clearSearchBtn.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = searchForm.getAttribute("action");
    });
  }

  // ==========================================
  // MELHORIAS DE UX
  // ==========================================

  // Verificar se há uma busca ativa na URL
  const urlParams = new URLSearchParams(window.location.search);
  const currentSearch = urlParams.get("search");

  if (currentSearch && currentSearch.trim() !== "") {
    searchBox.classList.add("has-search");

    // Destacar termos de busca nos resultados
    highlightSearchTerms(currentSearch);
  }

  // Melhorar UX do campo de busca
  searchInput.addEventListener("input", function () {
    const query = this.value.trim();

    if (query.length > 0) {
      searchBox.classList.add("has-search");
    } else {
      searchBox.classList.remove("has-search");
    }
  });

  // Funcionalidade para teclas especiais
  searchInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      searchForm.submit();
    }
  });

  searchInput.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (this.value.trim() !== "") {
        this.value = "";
        searchBox.classList.remove("has-search");
        this.focus();
      }
    }
  });

  // Auto-focus no campo de busca se não houver resultado
  const emptyStates = document.querySelectorAll(".empty-state");
  if (emptyStates.length > 0 && currentSearch) {
    searchInput.focus();
    searchInput.select();
  }

  // ==========================================
  // PLACEHOLDER DINÂMICO
  // ==========================================

  const placeholders = [
    "Pesquisar agendas compartilhadas...",
    "Digite o nome da agenda...",
    "Buscar por responsável...",
    "Procurar agenda compartilhada...",
  ];

  let placeholderIndex = 0;

  // Mudar placeholder a cada 4 segundos se o campo estiver vazio
  const placeholderInterval = setInterval(function () {
    if (
      searchInput.value.trim() === "" &&
      document.activeElement !== searchInput
    ) {
      placeholderIndex = (placeholderIndex + 1) % placeholders.length;
      searchInput.placeholder = placeholders[placeholderIndex];
    }
  }, 4000);

  // ==========================================
  // PAGINAÇÃO
  // ==========================================

  // Highlight para a página atual
  const currentPage = parseInt(urlParams.get("page")) || 1;
  paginationLinks.forEach((link) => {
    const linkUrl = new URL(link.href);
    const linkPage =
      parseInt(new URLSearchParams(linkUrl.search).get("page")) || 1;

    if (linkPage === currentPage) {
      link.classList.add("current");
    }
  });

  // ==========================================
  // FUNÇÕES AUXILIARES
  // ==========================================

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
    const originalText = element.textContent;
    const highlightedText = originalText.replace(regex, "<mark>$1</mark>");

    if (highlightedText !== originalText) {
      element.innerHTML = highlightedText;
    }
  }

  function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  function showMessage(message, type = "info") {
    // Remover mensagem anterior se existir
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

    // Remover após 3 segundos
    setTimeout(function () {
      if (messageDiv.parentNode) {
        messageDiv.style.animation = "slideOutRight 0.3s ease-in";
        setTimeout(function () {
          if (messageDiv.parentNode) {
            messageDiv.remove();
          }
        }, 300);
      }
    }, 3000);
  }

  // ==========================================
  // ESTILOS DINÂMICOS (se não existirem)
  // ==========================================

  if (!document.querySelector("#search-styles")) {
    const styles = document.createElement("style");
    styles.id = "search-styles";
    styles.textContent = `
      @keyframes slideInRight {
        from {
          opacity: 0;
          transform: translateX(100%);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }
      
      @keyframes slideOutRight {
        from {
          opacity: 1;
          transform: translateX(0);
        }
        to {
          opacity: 0;
          transform: translateX(100%);
        }
      }
      
      .search-message {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        padding: 1rem;
        font-size: 0.9rem;
      }

      mark {
        background-color: #fff3cd;
        color: #856404;
        padding: 0.1em 0.2em;
        border-radius: 3px;
        font-weight: 600;
      }
      
      .search-suggestions {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #004a8f;
      }
      
      .search-suggestions h4 {
        margin-bottom: 0.75rem;
        color: #004a8f;
        font-size: 1rem;
      }
      
      .search-suggestions ul {
        margin: 0;
        padding-left: 1.5rem;
      }
      
      .search-suggestions li {
        margin-bottom: 0.5rem;
        color: #6c757d;
        font-size: 0.9rem;
      }
    `;
    document.head.appendChild(styles);
  }

  // Limpar interval ao sair da página
  window.addEventListener("beforeunload", function () {
    clearInterval(placeholderInterval);
  });

  window.showSearchMessage = showMessage;
});
