/**
 * JavaScript para página de agendas compartilhadas
 * Arquivo: app/assets/js/shares/shared.js
 */
document.addEventListener("DOMContentLoaded", function () {
  // Elementos originais
  const searchForm = document.querySelector(".search-form");
  const searchInput = document.getElementById("search");
  const clearSearchBtn = document.querySelector(".search-form .btn-secondary");
  const paginationLinks = document.querySelectorAll(
    ".pagination-link:not(.disabled)"
  );

  // Novos elementos para melhorias visuais
  const searchBox = document.querySelector(".search-box");
  const searchButton = document.querySelector(".search-form .btn-primary");

  // ==========================================
  // FUNCIONALIDADE ORIGINAL MANTIDA
  // ==========================================

  // Event listeners originais
  if (searchForm) {
    searchForm.addEventListener("submit", function (e) {
      // Evitar submissão se campo de busca estiver vazio
      if (!searchInput || !searchInput.value.trim()) {
        e.preventDefault();
        window.location.href = searchForm.getAttribute("action");
        return;
      }

      // Adicionar estado de loading no botão
      if (searchButton) {
        searchButton.classList.add("loading");
        searchButton.disabled = true;
      }
    });
  }

  if (clearSearchBtn) {
    clearSearchBtn.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = searchForm.getAttribute("action");
    });
  }

  // Highlight para a página atual (mantido original)
  const currentPage =
    parseInt(new URLSearchParams(window.location.search).get("page")) || 1;
  paginationLinks.forEach((link) => {
    const linkPage = new URLSearchParams(new URL(link.href).search).get("page");
    if (parseInt(linkPage) === currentPage) {
      link.classList.add("current");
    }
  });

  // ==========================================
  // NOVAS FUNCIONALIDADES ADICIONADAS
  // ==========================================

  // Verificar se há elementos necessários para as melhorias
  if (!searchForm || !searchInput || !searchBox) {
    return; // Sair se elementos essenciais não existem
  }

  // Verificar se há uma busca ativa na URL
  const urlParams = new URLSearchParams(window.location.search);
  const currentSearch = urlParams.get("search");

  if (currentSearch && currentSearch.trim() !== "") {
    searchBox.classList.add("has-search");
  }

  // Melhorar UX do campo de busca
  let searchTimeout;

  searchInput.addEventListener("input", function () {
    clearTimeout(searchTimeout);

    const query = this.value.trim();

    // Adicionar ou remover classe baseado no conteúdo
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
  const noResults = document.querySelector(".empty-state");
  if (noResults && currentSearch) {
    searchInput.focus();
    searchInput.select();
  }

  // Destacar termos de busca nos resultados
  if (currentSearch && currentSearch.length > 0) {
    highlightSearchTerms(currentSearch);
  }

  // Adicionar suporte para placeholder dinâmico
  const placeholders = [
    "Pesquisar agendas...",
    "Digite o nome da agenda...",
    "Buscar por responsável...",
    "Procurar agenda compartilhada...",
  ];

  let placeholderIndex = 0;

  // Mudar placeholder a cada 4 segundos se o campo estiver vazio
  setInterval(function () {
    if (
      searchInput.value.trim() === "" &&
      document.activeElement !== searchInput
    ) {
      placeholderIndex = (placeholderIndex + 1) % placeholders.length;
      searchInput.placeholder = placeholders[placeholderIndex];
    }
  }, 4000);

  // Adicionar feedback visual quando não há resultados
  const agendaGrid = document.querySelector(".agenda-grid");
  if (agendaGrid && currentSearch) {
    const agendaCards = agendaGrid.querySelectorAll(".agenda-card");

    if (agendaCards.length === 0) {
      // Adicionar sugestões de busca alternativa
      const emptyState = document.querySelector(".empty-state");
      if (emptyState && !emptyState.querySelector(".search-suggestions")) {
        const suggestions = document.createElement("div");
        suggestions.className = "search-suggestions";
        suggestions.innerHTML = `
                    <h4>💡 Dicas de busca:</h4>
                    <ul>
                        <li>Verifique a ortografia das palavras</li>
                        <li>Tente termos mais gerais</li>
                        <li>Use apenas palavras-chave importantes</li>
                        <li>Experimente buscar pelo nome do responsável</li>
                    </ul>
                `;
        emptyState.appendChild(suggestions);
      }
    }
  }

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

      if (description) {
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

  function showSearchMessage(message, type = "info") {
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
  // ESTILOS DINÂMICOS
  // ==========================================

  // Adicionar estilos para as animações das mensagens (se não existirem)
  if (!document.querySelector("#search-message-styles")) {
    const styles = document.createElement("style");
    styles.id = "search-message-styles";
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

            .search-form .btn.loading {
                opacity: 0.7;
                cursor: not-allowed;
                pointer-events: none;
            }

            .search-form .btn.loading::after {
                content: "";
                width: 16px;
                height: 16px;
                margin-left: 0.5rem;
                border: 2px solid transparent;
                border-top-color: currentColor;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
    document.head.appendChild(styles);
  }

  // ==========================================
  // EXPOSIÇÃO DE FUNÇÕES GLOBAIS (MANTIDO)
  // ==========================================

  // Expor funções úteis para uso global se necessário
  window.showSearchMessage = showSearchMessage;
});
