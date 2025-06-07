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

  // ==========================================
  // FUNCIONALIDADE DE COPIAR URL PÚBLICA
  // ==========================================

  // Usar a função copyToClipboard do namespace AgendaUFPR ou fallback
  document.querySelectorAll(".input-group .btn").forEach((button) => {
    button.addEventListener("click", function () {
      const input = this.closest(".input-group").querySelector("input");
      if (input) {
        // Tentar usar clipboard API moderna
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard
            .writeText(input.value)
            .then(() => {
              showCopyFeedback(this);
            })
            .catch(() => {
              fallbackCopyToClipboard(input, this);
            });
        } else {
          fallbackCopyToClipboard(input, this);
        }
      }
    });
  });

  // Função fallback para copiar
  function fallbackCopyToClipboard(input, button) {
    input.select();
    input.setSelectionRange(0, 99999); // Para mobile
    try {
      document.execCommand("copy");
      showCopyFeedback(button);
    } catch (err) {
      console.error("Erro ao copiar: ", err);
      showMessage("Erro ao copiar URL", "danger");
    }
  }

  // Feedback visual para cópia
  function showCopyFeedback(button) {
    const originalText = button.textContent;
    button.textContent = "Copiado!";
    button.classList.add("btn-success");
    button.classList.remove("btn-primary");

    setTimeout(() => {
      button.textContent = originalText;
      button.classList.remove("btn-success");
      button.classList.add("btn-primary");
    }, 2000);
  }

  // ==========================================
  // ENVIO DIRETO DE E-MAIL (SEM CONFIRMAÇÃO)
  // ==========================================

  // Envio direto de e-mail sem confirmação
  document.querySelectorAll(".email-form").forEach((form) => {
    form.addEventListener("submit", function (e) {
      // Não prevenir o envio, deixar acontecer naturalmente

      // Adicionar estado de loading no botão
      const submitButton = this.querySelector(".btn-email");
      if (submitButton) {
        const originalContent = submitButton.innerHTML;

        // Estado de loading
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        submitButton.classList.add("loading");

        // Fallback: remover loading após 10 segundos (caso não redirecione)
        setTimeout(() => {
          submitButton.disabled = false;
          submitButton.innerHTML = originalContent;
          submitButton.classList.remove("loading");
        }, 10000);
      }
    });
  });

  // ==========================================
  // MELHORIAS DE UX
  // ==========================================

  // Verificar se há uma busca ativa na URL
  if (searchBox && searchInput) {
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
  }

  // ==========================================
  // MELHORIAS NO FORMULÁRIO DE COMPARTILHAMENTO
  // ==========================================

  // Validação do campo username
  const usernameInput = document.querySelector('input[name="username"]');
  if (usernameInput) {
    usernameInput.addEventListener("input", function () {
      const value = this.value.trim();

      // Remover espaços no início e fim
      if (value !== this.value) {
        this.value = value;
      }

      // Feedback visual para username válido
      if (value.length >= 3) {
        this.classList.add("is-valid");
        this.classList.remove("is-invalid");
      } else if (value.length > 0) {
        this.classList.add("is-invalid");
        this.classList.remove("is-valid");
      } else {
        this.classList.remove("is-valid", "is-invalid");
      }
    });

    // Prevenir espaços no username
    usernameInput.addEventListener("keypress", function (e) {
      if (e.key === " ") {
        e.preventDefault();
        return false;
      }
    });
  }

  // Melhorar feedback do formulário de compartilhamento
  const shareForm = document.querySelector(".share-form");
  if (shareForm) {
    shareForm.addEventListener("submit", function (e) {
      const usernameField = this.querySelector('input[name="username"]');
      const username = usernameField ? usernameField.value.trim() : "";

      if (username.length < 3) {
        e.preventDefault();
        showMessage(
          "❌ O nome de usuário deve ter pelo menos 3 caracteres.",
          "danger"
        );
        if (usernameField) {
          usernameField.focus();
          usernameField.classList.add("is-invalid");
        }
        return false;
      }

      // Adicionar loading no botão de compartilhar
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> Compartilhando...';

        // Fallback para remover loading
        setTimeout(() => {
          submitButton.disabled = false;
          submitButton.innerHTML = originalText;
        }, 8000);
      }
    });
  }

  // ==========================================
  // MELHORIAS NOS TOGGLES DE PERMISSÃO
  // ==========================================

  // Confirmação ao alterar permissões (mantida porque é importante)
  document
    .querySelectorAll('.permission-form input[type="checkbox"]')
    .forEach((checkbox) => {
      checkbox.addEventListener("change", function (e) {
        e.preventDefault(); // Prevenir mudança imediata

        const userRow = this.closest("tr");
        const userName = userRow
          .querySelector("td:first-child")
          .textContent.trim();
        const newPermission = this.checked ? "Pode Editar" : "Apenas Ver";
        const currentPermission = this.checked ? "Apenas Ver" : "Pode Editar";

        const confirmMessage = `Alterar permissão de ${userName}?\n\nDe: ${currentPermission}\nPara: ${newPermission}\n\nEsta ação será aplicada imediatamente.`;

        if (confirm(confirmMessage)) {
          // Reativar o checkbox e submeter o formulário
          this.checked = !this.checked; // Inverter de volta
          this.onchange = null; // Remover listener temporariamente
          this.click(); // Simular click para ativar

          // Re-adicionar listener após um delay
          setTimeout(() => {
            this.onchange = arguments.callee;
          }, 100);
        } else {
          // Reverter a mudança
          this.checked = !this.checked;
        }
      });
    });

  // ==========================================
  // FEEDBACK VISUAL PARA BOTÕES
  // ==========================================

  // Feedback visual ao passar mouse sobre os botões
  document.querySelectorAll(".action-buttons-group .btn").forEach((btn) => {
    btn.addEventListener("mouseenter", function () {
      if (!this.classList.contains("loading")) {
        this.style.transform = "translateY(-1px)";
      }
    });

    btn.addEventListener("mouseleave", function () {
      if (!this.classList.contains("loading")) {
        this.style.transform = "translateY(0)";
      }
    });
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

  function showMessage(message, type = "info", duration = 3000) {
    // Remover mensagem anterior se existir
    const existingMessage = document.querySelector(".share-message");
    if (existingMessage) {
      existingMessage.remove();
    }

    // Criar nova mensagem
    const messageDiv = document.createElement("div");
    messageDiv.className = `share-message alert alert-${type}`;
    messageDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1050;
      max-width: 350px;
      animation: slideInRight 0.3s ease-out;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      font-size: 0.9rem;
    `;

    // Ícones por tipo
    const icons = {
      success: "✅",
      danger: "❌",
      warning: "⚠️",
      info: "ℹ️",
    };

    messageDiv.innerHTML = `
      <strong>${icons[type] || icons.info}</strong>
      ${message}
    `;

    document.body.appendChild(messageDiv);

    // Remover após o tempo especificado
    setTimeout(function () {
      if (messageDiv.parentNode) {
        messageDiv.style.animation = "slideOutRight 0.3s ease-in";
        setTimeout(function () {
          if (messageDiv.parentNode) {
            messageDiv.remove();
          }
        }, 300);
      }
    }, duration);
  }

  // ==========================================
  // ESTILOS DINÂMICOS (se não existirem)
  // ==========================================

  if (!document.querySelector("#shares-styles")) {
    const styles = document.createElement("style");
    styles.id = "shares-styles";
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
      
      .share-message {
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
      
      /* Feedback visual para campos de input */
      .form-control.is-valid {
        border-color: #28a745;
        box-shadow: 0 0 0 0.1rem rgba(40, 167, 69, 0.25);
      }
      
      .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.1rem rgba(220, 53, 69, 0.25);
      }
      
      /* Animação suave para botões */
      .action-buttons-group .btn {
        transition: all 0.2s ease;
      }
      
      .btn.loading {
        opacity: 0.7;
        cursor: not-allowed;
        pointer-events: none;
      }
      
      .btn.loading:hover {
        transform: none !important;
      }
      
      .fa-spin {
        animation: fa-spin 1s infinite linear;
      }
      
      @keyframes fa-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }

      /* Estilos essenciais para botões de ação */
      .action-buttons-group {
        display: flex !important;
        gap: 0.5rem !important;
        align-items: center !important;
        justify-content: flex-start !important;
        flex-wrap: wrap !important;
      }

      .action-buttons-group .btn {
        margin: 0 !important;
        white-space: nowrap !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.8rem !important;
        font-weight: 500 !important;
        border-radius: 4px !important;
        transition: all 0.2s ease !important;
        text-decoration: none !important;
        border: none !important;
        cursor: pointer !important;
      }

      .action-buttons-group .btn i {
        font-size: 0.75rem !important;
      }

      /* Botão de enviar e-mail */
      .btn-email {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
        color: #fff !important;
        border-color: #17a2b8 !important;
      }

      .btn-email:hover {
        background: linear-gradient(135deg, #138496 0%, #117a8b 100%) !important;
        color: #fff !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3) !important;
        text-decoration: none !important;
      }

      .btn-email:focus {
        box-shadow: 0 0 0 2px rgba(23, 162, 184, 0.25) !important;
      }

      /* Melhorias no botão de remover */
      .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        color: #fff !important;
        border-color: #dc3545 !important;
      }

      .btn-danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #bd2130 100%) !important;
        color: #fff !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3) !important;
        text-decoration: none !important;
      }

      .btn-danger:focus {
        box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25) !important;
      }

      /* Responsividade para botões de ação */
      @media (max-width: 768px) {
        .action-buttons-group {
          flex-direction: column !important;
          gap: 0.25rem !important;
        }

        .action-buttons-group .btn {
          width: 100% !important;
          justify-content: center !important;
          font-size: 0.75rem !important;
          padding: 0.25rem 0.5rem !important;
        }
      }

      @media (max-width: 576px) {
        .action-buttons-group .btn {
          font-size: 0.7rem !important;
          padding: 0.2rem 0.4rem !important;
        }

        .action-buttons-group .btn i {
          font-size: 0.7rem !important;
        }
      }
    `;
    document.head.appendChild(styles);
  }

  // Limpar intervals ao sair da página
  window.addEventListener("beforeunload", function () {
    // Limpar possíveis intervals criados
  });

  // Expor função para uso global
  window.showShareMessage = showMessage;
});

// ===== FUNÇÕES GLOBAIS (ACESSÍVEIS FORA DO DOMContentLoaded) =====

/**
 * Função para validar e-mail (caso necessário no futuro)
 */
window.validateEmail = function (email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

/**
 * Função para formatar nome de usuário (remover espaços, etc.)
 */
window.formatUsername = function (username) {
  return username.trim().toLowerCase().replace(/\s+/g, "");
};

/**
 * Função legacy para compatibilidade
 */
window.copyToClipboard = function (inputId) {
  const input = document.getElementById(inputId);
  if (input) {
    input.select();
    input.setSelectionRange(0, 99999);

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(input.value)
        .then(() => {
          //console.log("URL copiada com sucesso");
        })
        .catch(() => {
          document.execCommand("copy");
        });
    } else {
      document.execCommand("copy");
    }
  }
};
