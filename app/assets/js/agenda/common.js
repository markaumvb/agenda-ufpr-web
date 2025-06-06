function initAgendaForm() {
  // Elementos do formulário
  const form = document.querySelector("form");
  const titleInput = document.getElementById("title");
  const colorInput = document.getElementById("color");

  // Verificar se estamos em um formulário de edição ou criação
  const isEditForm = window.location.href.includes("/edit");

  // Validação do formulário antes do envio
  if (form && !form.classList.contains("delete-form")) {
    form.addEventListener("submit", function (event) {
      // Verificar se o título foi preenchido
      if (titleInput && !titleInput.value.trim()) {
        event.preventDefault();
        alert("O título da agenda é obrigatório.");
        titleInput.focus();
        return false;
      }
    });
  }

  // Atualização em tempo real da cor selecionada
  if (colorInput) {
    colorInput.addEventListener("input", function () {});
  }
}

function initDeleteConfirmations() {
  // Remover todos os event listeners existentes usando uma abordagem diferente
  const deleteForms = document.querySelectorAll(".delete-form");

  deleteForms.forEach((form, index) => {
    // Verificar se já tem o event listener para evitar duplicação
    if (form.hasAttribute("data-delete-listener")) {
      return; // Já tem listener, pular
    }

    // Marcar que este formulário já tem listener
    form.setAttribute("data-delete-listener", "true");

    // Adicionar event listener único
    form.addEventListener(
      "submit",
      function (event) {
        event.preventDefault(); // Sempre prevenir envio inicial
        event.stopPropagation(); // Evitar propagação

        // Verificar se já está sendo processado
        if (this.hasAttribute("data-processing")) {
          return false;
        }

        // Marcar como sendo processado
        this.setAttribute("data-processing", "true");

        // Obter o nome da agenda se disponível
        const agendaCard = this.closest(".agenda-card");
        const agendaTitle = agendaCard
          ? agendaCard.querySelector(".agenda-title")?.textContent?.trim()
          : "";

        // Mensagem personalizada
        let confirmMessage = "Tem certeza que deseja excluir esta agenda?";
        if (agendaTitle) {
          confirmMessage = `Tem certeza que deseja excluir a agenda "${agendaTitle}"?`;
        }
        confirmMessage += "\n\nEsta ação não pode ser desfeita.";

        // Mostrar confirmação
        const confirmed = confirm(confirmMessage);

        if (confirmed) {
          // Desabilitar o botão para evitar cliques múltiplos
          const submitBtn = this.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML =
              '<i class="fas fa-spinner fa-spin"></i> Excluindo...';
          }

          // Submeter o formulário
          this.submit();
        } else {
          console.log("❌ Exclusão cancelada pelo usuário");
          // Remover marca de processamento se cancelou
          this.removeAttribute("data-processing");
        }

        return false;
      },
      { once: false }
    );
  });
}

// Função para resetar listeners se necessário (para debugging)
function resetDeleteListeners() {
  const deleteForms = document.querySelectorAll(".delete-form");
  deleteForms.forEach((form) => {
    form.removeAttribute("data-delete-listener");
    form.removeAttribute("data-processing");
  });
  initDeleteConfirmations();
}

// Exportar funções para uso em outros arquivos
window.AgendaCommon = {
  initAgendaForm,
  initDeleteConfirmations,
  resetDeleteListeners, // Para debugging
};

// Debug: Expor função para resetar se necessário
window.resetDeleteListeners = resetDeleteListeners;
