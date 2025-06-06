document.addEventListener("DOMContentLoaded", function () {
  // ===== FUNCIONALIDADE EXISTENTE: COPIAR URL PÚBLICA =====

  // Usar a função copyToClipboard do namespace AgendaUFPR
  document.querySelectorAll(".input-group .btn").forEach((button) => {
    button.addEventListener("click", function () {
      const input = this.closest(".input-group").querySelector("input");
      if (input) {
        AgendaUFPR.utils.copyToClipboard(input.value);

        // Feedback visual
        const originalText = this.textContent;
        this.textContent = "Copiado!";
        setTimeout(() => {
          this.textContent = originalText;
        }, 2000);
      }
    });
  });

  // ===== NOVA FUNCIONALIDADE: ENVIO DE E-MAIL =====

  // Adicionar confirmação e loading nos botões de e-mail
  document
    .querySelectorAll('form[action*="/shares/send-email"]')
    .forEach((form) => {
      form.addEventListener("submit", function (e) {
        e.preventDefault(); // Prevenir envio imediato

        // Obter dados do usuário
        const userRow = this.closest("tr");
        const userName = userRow
          .querySelector("td:first-child")
          .textContent.trim();
        const userEmail = userRow
          .querySelector("td:nth-child(3)")
          .textContent.trim();

        // Confirmação personalizada
        const confirmMessage = `Deseja enviar e-mail de notificação sobre o compartilhamento para:\n\n👤 ${userName}\n📧 ${userEmail}\n\nO usuário receberá um e-mail com detalhes da agenda compartilhada.`;

        if (confirm(confirmMessage)) {
          // Adicionar estado de loading no botão
          const submitButton = this.querySelector(".btn-email");
          const originalContent = submitButton.innerHTML;

          // Estado de loading
          submitButton.disabled = true;
          submitButton.innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> Enviando...';
          submitButton.classList.add("loading");

          // Submeter o formulário
          this.submit();

          // Fallback: remover loading após 10 segundos (caso não redirecione)
          setTimeout(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalContent;
            submitButton.classList.remove("loading");
          }, 10000);
        }
      });
    });

  // ===== MELHORIAS NA UX =====

  // Adicionar tooltips nos botões de e-mail
  document.querySelectorAll(".btn-email").forEach((btn) => {
    btn.setAttribute(
      "title",
      "Enviar e-mail de notificação sobre o compartilhamento"
    );
    btn.setAttribute("data-toggle", "tooltip");
    btn.setAttribute("data-placement", "top");
  });

  // Adicionar tooltips nos botões de remoção
  document.querySelectorAll(".btn-danger").forEach((btn) => {
    if (btn.innerHTML.includes("Remover")) {
      btn.setAttribute("title", "Remover compartilhamento com este usuário");
      btn.setAttribute("data-toggle", "tooltip");
      btn.setAttribute("data-placement", "top");
    }
  });

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

  // ===== MELHORIAS NO FORMULÁRIO DE COMPARTILHAMENTO =====

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
        alert("❌ O nome de usuário deve ter pelo menos 3 caracteres.");
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

  // ===== MELHORIAS NOS TOGGLES DE PERMISSÃO =====

  // Confirmação ao alterar permissões
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

  // ===== ESTADO DE LOADING CSS DINÂMICO =====

  // Adicionar CSS para loading se não existir
  if (!document.querySelector("#loading-styles")) {
    const style = document.createElement("style");
    style.id = "loading-styles";
    style.textContent = `
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
      
      /* Melhorar visual dos tooltips */
      [data-toggle="tooltip"] {
        cursor: help;
      }
    `;
    document.head.appendChild(style);
  }

  // ===== INICIALIZAÇÃO DE TOOLTIPS (SE BOOTSTRAP ESTIVER DISPONÍVEL) =====

  // Verificar se jQuery e Bootstrap estão disponíveis para tooltips
  if (typeof $ !== "undefined" && $.fn.tooltip) {
    $('[data-toggle="tooltip"]').tooltip();
  }

  // ===== LOGS DE DEBUG (APENAS EM DEVELOPMENT) =====

  if (
    window.location.hostname === "localhost" ||
    window.location.hostname.includes("127.0.0.1")
  ) {
  }
});

// ===== FUNÇÕES GLOBAIS (ACESSÍVEIS FORA DO DOMContentLoaded) =====

/**
 * Função para mostrar mensagens de feedback personalizadas
 */
window.showShareMessage = function (message, type = "info", duration = 3000) {
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
};

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
