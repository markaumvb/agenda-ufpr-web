/**
 * Arquivo: public/assets/js/agendas/create.js
 * JavaScript para o formulário de criação de agenda
 */

document.addEventListener("DOMContentLoaded", function () {
  // Elementos do formulário
  const form = document.querySelector("form");
  const titleInput = document.getElementById("title");
  const colorInput = document.getElementById("color");

  // Validação do formulário antes do envio
  form.addEventListener("submit", function (event) {
    // Verificar se o título foi preenchido
    if (!titleInput.value.trim()) {
      event.preventDefault();
      alert("O título da agenda é obrigatório.");
      titleInput.focus();
      return false;
    }

    // Outros potenciais validações podem ser adicionadas aqui
  });

  // Atualização em tempo real da cor selecionada
  colorInput.addEventListener("input", function () {
    // Você pode adicionar uma visualização da cor selecionada, por exemplo:
    // document.querySelector('.color-preview').style.backgroundColor = this.value;
    console.log("Cor selecionada:", colorInput.value);
  });
});
