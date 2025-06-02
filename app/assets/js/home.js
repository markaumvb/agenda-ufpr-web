/**
 * JavaScript específico para a página inicial (home.php)
 * Gerencia busca, paginação e UX
 */

class HomePageManager {
  constructor() {
    this.searchForm = null;
    this.searchBtn = null;
    this.searchInput = null;
    this.isSubmitting = false;
    this.originalBtnContent = "";

    this.init();
  }

  init() {
    // Aguardar DOM estar pronto
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => this.setup());
    } else {
      this.setup();
    }
  }

  setup() {
    this.cacheElements();
    this.bindEvents();
    this.enhanceTable();
    this.setupPagination();
    this.autoFocus();
    this.resetButtonState();
  }

  cacheElements() {
    this.searchForm = document.getElementById("searchForm");
    this.searchBtn = document.getElementById("searchBtn");
    this.searchInput = document.getElementById("searchInput");

    if (this.searchBtn) {
      this.originalBtnContent = this.searchBtn.innerHTML;
    }
  }

  bindEvents() {
    // Evento de submit do formulário
    if (this.searchForm && this.searchBtn) {
      this.searchForm.addEventListener("submit", (e) => this.handleSubmit(e));
    }

    // Evento Enter no campo de busca
    if (this.searchInput) {
      this.searchInput.addEventListener("keypress", (e) =>
        this.handleKeyPress(e)
      );
    }

    // Resetar estado do botão quando a página carregar
    window.addEventListener("load", () => this.resetButtonState());
    window.addEventListener("pageshow", () => this.resetButtonState());

    // Prevenir múltiplos submits usando beforeunload
    window.addEventListener("beforeunload", () => this.resetButtonState());
  }

  handleSubmit(e) {
    // Prevenir múltiplos submits
    if (this.isSubmitting) {
      e.preventDefault();
      return false;
    }

    // Validar se há conteúdo (busca vazia é permitida)
    const searchTerm = this.searchInput ? this.searchInput.value.trim() : "";

    // Marcar como submetendo
    this.isSubmitting = true;
    this.setLoadingState();

    // Timeout de segurança para resetar o botão (5 segundos)
    setTimeout(() => {
      this.resetButtonState();
    }, 5000);

    // Permitir que o form seja submetido
    return true;
  }

  handleKeyPress(e) {
    if (e.key === "Enter") {
      e.preventDefault();
      if (this.searchForm && !this.isSubmitting) {
        this.searchForm.submit();
      }
    }
  }

  setLoadingState() {
    if (this.searchBtn) {
      this.searchBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Buscando...';
      this.searchBtn.disabled = true;
      this.searchBtn.style.opacity = "0.7";
      this.searchBtn.style.cursor = "not-allowed";
    }
  }

  resetButtonState() {
    if (this.searchBtn && this.originalBtnContent) {
      this.isSubmitting = false;
      this.searchBtn.innerHTML = this.originalBtnContent;
      this.searchBtn.disabled = false;
      this.searchBtn.style.opacity = "1";
      this.searchBtn.style.cursor = "pointer";
    }
  }

  autoFocus() {
    // Auto-focus no campo de busca apenas se não houver valor e não for mobile
    if (
      this.searchInput &&
      !this.searchInput.value.trim() &&
      !this.isMobile()
    ) {
      setTimeout(() => {
        this.searchInput.focus();
      }, 100);
    }
  }

  enhanceTable() {
    const tableRows = document.querySelectorAll(
      ".public-agendas-table tbody tr"
    );

    tableRows.forEach((row) => {
      // Efeito hover melhorado
      row.addEventListener("mouseenter", function () {
        this.style.transform = "translateY(-2px)";
        this.style.transition = "all 0.3s ease";
      });

      row.addEventListener("mouseleave", function () {
        this.style.transform = "translateY(0)";
      });

      // Adicionar indicador de clique nos botões
      const buttons = row.querySelectorAll(".btn");
      buttons.forEach((btn) => {
        btn.addEventListener("click", function (e) {
          // Efeito visual de clique
          this.style.transform = "scale(0.95)";
          setTimeout(() => {
            this.style.transform = "scale(1)";
          }, 150);
        });
      });
    });
  }

  setupPagination() {
    const paginationLinks = document.querySelectorAll(
      ".pagination-link:not(.disabled):not(.current)"
    );

    paginationLinks.forEach((link) => {
      if (link.tagName === "A") {
        link.addEventListener("click", function (e) {
          // Adicionar indicador de loading
          this.style.opacity = "0.6";
          this.style.pointerEvents = "none";

          // Adicionar spinner se for um link de página
          if (
            !this.classList.contains("prev") &&
            !this.classList.contains("next")
          ) {
            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Fallback para resetar se não carregar
            setTimeout(() => {
              this.innerHTML = originalContent;
              this.style.opacity = "1";
              this.style.pointerEvents = "auto";
            }, 3000);
          }
        });
      }
    });
  }

  isMobile() {
    return (
      window.innerWidth <= 768 ||
      /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent
      )
    );
  }

  // Método público para resetar estado (pode ser chamado externamente)
  forceReset() {
    this.resetButtonState();
  }
}

// Inicializar quando o script carregar
let homeManager;

// Aguardar o DOM e inicializar
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", function () {
    homeManager = new HomePageManager();
  });
} else {
  homeManager = new HomePageManager();
}

// Expor globalmente para debug se necessário
window.HomePageManager = HomePageManager;
window.homeManager = homeManager;

// Fallback adicional para garantir que o botão seja resetado
window.addEventListener("load", function () {
  if (window.homeManager) {
    window.homeManager.forceReset();
  }
});

// Resetar estado ao usar botão voltar do navegador
window.addEventListener("pageshow", function (event) {
  if (event.persisted && window.homeManager) {
    window.homeManager.forceReset();
  }
});
