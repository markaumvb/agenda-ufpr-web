/**
 * JavaScript para formulários de agenda (criação e edição)
 */
document.addEventListener("DOMContentLoaded", function () {
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
});
