function initAgendaForm() {
  // Elementos do formulário
  const form = document.querySelector("form");
  const titleInput = document.getElementById("title");
  const colorInput = document.getElementById("color");

  // Verificar se estamos em um formulário de edição ou criação
  const isEditForm = window.location.href.includes("/edit");

  // Validação do formulário antes do envio
  if (form) {
    form.addEventListener("submit", function (event) {
      // Verificar se o título foi preenchido
      if (!titleInput.value.trim()) {
        event.preventDefault();
        alert("O título da agenda é obrigatório.");
        titleInput.focus();
        return false;
      }
    });
  }

  // Atualização em tempo real da cor selecionada
  if (colorInput) {
    colorInput.addEventListener("input", function () {
      console.log("Cor selecionada:", colorInput.value);
    });
  }
}

function initDeleteConfirmations() {
  // CORRIGIDO: Confirmação única para exclusão de agenda
  const deleteForms = document.querySelectorAll(".delete-form");

  // Remover event listeners anteriores para evitar duplicação
  deleteForms.forEach((form) => {
    // Criar um clone do formulário para remover todos os event listeners
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
  });

  // Adicionar event listeners aos novos formulários
  const newDeleteForms = document.querySelectorAll(".delete-form");
  newDeleteForms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      event.preventDefault(); // Previne o envio imediato

      // Obter o nome da agenda se disponível
      const agendaCard = this.closest(".agenda-card");
      const agendaTitle = agendaCard
        ? agendaCard.querySelector(".agenda-title")?.textContent
        : "";

      // Mensagem personalizada
      let confirmMessage = "Tem certeza que deseja excluir esta agenda?";
      if (agendaTitle) {
        confirmMessage = `Tem certeza que deseja excluir a agenda "${agendaTitle}"?`;
      }
      confirmMessage += "\n\nEsta ação não pode ser desfeita.";

      // Mostrar confirmação única
      if (confirm(confirmMessage)) {
        // Se confirmou, submeter o formulário
        this.submit();
      }
      // Se não confirmou, não faz nada (o evento já foi preventDefault)
    });
  });
}

// Exportar funções para uso em outros arquivos
window.AgendaCommon = {
  initAgendaForm,
  initDeleteConfirmations,
};
