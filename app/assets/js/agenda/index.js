/**
 * Arquivo: public/assets/js/agendas/index.js
 * JavaScript para a página de listagem de agendas
 */

document.addEventListener("DOMContentLoaded", function () {
  // Inicializar comportamentos específicos da página de agendas
  console.log("Página de listagem de agendas carregada");

  // Confirmação para exclusão de agenda
  const deleteForms = document.querySelectorAll(".delete-form");
  deleteForms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      if (!confirm("Tem certeza que deseja excluir esta agenda?")) {
        event.preventDefault();
      }
    });
  });
});
