document.addEventListener("DOMContentLoaded", function () {
  // Selecionar todas as linhas da tabela de agendas públicas
  const agendaRows = document.querySelectorAll(
    ".public-agendas-table tbody tr"
  );

  // Adicionar evento de clique às linhas da tabela
  agendaRows.forEach((row) => {
    row.addEventListener("click", function (e) {
      // Não navegar se o clique for no botão (já tem seu próprio link)
      if (
        e.target.tagName === "A" ||
        e.target.tagName === "BUTTON" ||
        e.target.closest("a") ||
        e.target.closest("button")
      ) {
        return;
      }

      // Encontrar o link de visualização na linha e redirecionara para ele
      const viewLink = this.querySelector("a.btn");
      if (viewLink) {
        window.location.href = viewLink.href;
      }
    });

    // Adicionar cursor de ponteiro para indicar que a linha é clicável
    row.style.cursor = "pointer";
  });

  // Adicionar efeitos de hover aprimorados
  agendaRows.forEach((row) => {
    row.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-2px)";
      this.style.boxShadow = "0 4px 12px rgba(0, 74, 143, 0.15)";
    });

    row.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
      this.style.boxShadow = "";
    });
  });
});
