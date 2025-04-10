document.addEventListener("DOMContentLoaded", function () {
  // Funcionalidade de filtro para tabelas
  const filterStatus = document.getElementById("filter-status");
  const filterSearch = document.getElementById("filter-search");
  const clearFilters = document.getElementById("clear-filters");
  const resetFilters = document.getElementById("reset-filters");
  const compromissoRows = document.querySelectorAll(".compromisso-row");

  // Marcar linhas que têm descrição
  compromissoRows.forEach((row) => {
    const id = row.dataset.id;
    const descRow = document.getElementById("desc-" + id);
    if (descRow) {
      row.classList.add("has-description");
    }

    // Toggle para expandir descrição
    row.addEventListener("click", function (e) {
      // Ignorar clique em botões
      if (
        e.target.tagName === "BUTTON" ||
        e.target.tagName === "A" ||
        e.target.closest("button") ||
        e.target.closest("a")
      ) {
        return;
      }

      const id = this.dataset.id;
      const descRow = document.getElementById("desc-" + id);
      if (descRow) {
        if (descRow.style.display === "none") {
          descRow.style.display = "table-row";
          this.classList.add("expanded");
        } else {
          descRow.style.display = "none";
          this.classList.remove("expanded");
        }
      }
    });
  });

  function applyFilters() {
    const statusFilter = filterStatus.value;
    const searchFilter = filterSearch.value.toLowerCase().trim();

    // Para controlar visibilidade das seções de agenda
    const agendaSections = document.querySelectorAll(".agenda-section");
    const visibleAgendas = new Set();
    let visibleCount = 0;

    // Aplicar filtros às linhas da tabela
    compromissoRows.forEach((row) => {
      const status = row.dataset.status;
      const searchText = row.dataset.search;

      // Verificar status
      const statusMatch = statusFilter === "all" || status === statusFilter;

      // Verificar texto de busca
      const searchMatch = !searchFilter || searchText.includes(searchFilter);

      // Exibir ou ocultar a linha
      if (statusMatch && searchMatch) {
        row.style.display = "table-row";
        visibleCount++;

        // Marcar a agenda como contendo compromissos visíveis
        const agendaSection = row.closest(".agenda-section");
        if (agendaSection) {
          visibleAgendas.add(agendaSection);
        }

        // Esconder a linha de descrição para manter consistência
        const id = row.dataset.id;
        const descRow = document.getElementById("desc-" + id);
        if (descRow) {
          descRow.style.display = "none";
          row.classList.remove("expanded");
        }
      } else {
        row.style.display = "none";

        // Esconder a linha de descrição também
        const id = row.dataset.id;
        const descRow = document.getElementById("desc-" + id);
        if (descRow) {
          descRow.style.display = "none";
        }
      }
    });

    // Mostrar/esconder seções de agenda com base nos filtros
    agendaSections.forEach((section) => {
      if (visibleAgendas.has(section)) {
        section.style.display = "block";
      } else {
        section.style.display = "none";
      }
    });

    // Mostrar mensagem se nenhuma agenda estiver visível
    const noResults = document.querySelector(".no-results");
    if (noResults) {
      if (visibleCount === 0) {
        noResults.style.display = "block";
      } else {
        noResults.style.display = "none";
      }
    }
  }

  // Adicionar event listeners para os filtros
  if (filterStatus) {
    filterStatus.addEventListener("change", applyFilters);
  }

  if (filterSearch) {
    filterSearch.addEventListener("input", applyFilters);
  }

  // Botão para limpar filtros
  function clearAllFilters() {
    if (filterStatus) filterStatus.value = "all";
    if (filterSearch) filterSearch.value = "";
    applyFilters();
  }

  if (clearFilters) {
    clearFilters.addEventListener("click", clearAllFilters);
  }

  if (resetFilters) {
    resetFilters.addEventListener("click", clearAllFilters);
  }
});
