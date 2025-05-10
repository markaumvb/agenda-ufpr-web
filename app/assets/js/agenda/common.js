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
  // Confirmação para exclusão de agenda
  const deleteForms = document.querySelectorAll(".delete-form");
  deleteForms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      if (!confirm("Tem certeza que deseja excluir esta agenda?")) {
        event.preventDefault();
      }
    });
  });
}

// Exportar funções para uso em outros arquivos
window.AgendaCommon = {
  initAgendaForm,
  initDeleteConfirmations,
};
