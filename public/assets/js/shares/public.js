// Filtros da lista de compromissos
document.addEventListener("DOMContentLoaded", function () {
  const filterStatus = document.getElementById("filter-status");
  const filterMonth = document.getElementById("filter-month");
  const filterSearch = document.getElementById("filter-search");
  const eventCards = document.querySelectorAll(".event-card");

  if (!filterStatus || !filterMonth || !filterSearch) return;

  // Aplicar filtros quando alterados
  filterStatus.addEventListener("change", applyFilters);
  filterMonth.addEventListener("change", applyFilters);
  filterSearch.addEventListener("input", applyFilters);

  // Função para aplicar os filtros
  function applyFilters() {
    const statusFilter = filterStatus.value;
    const monthFilter = filterMonth.value;
    const searchFilter = filterSearch.value.toLowerCase().trim();

    eventCards.forEach((card) => {
      const status = card.dataset.status;
      const month = card.dataset.month;
      const searchText = card.dataset.search;

      // Verificar status
      const statusMatch = statusFilter === "all" || status === statusFilter;

      // Verificar mês
      const monthMatch = monthFilter === "all" || month === monthFilter;

      // Verificar texto de busca
      const searchMatch = searchText.includes(searchFilter);

      // Exibir ou ocultar o card
      if (statusMatch && monthMatch && searchMatch) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  }

  // Definir mês atual no filtro (se disponível na URL)
  const urlParams = new URLSearchParams(window.location.search);
  const monthParam = urlParams.get("month");
  if (
    monthParam &&
    filterMonth.querySelector(`option[value="${monthParam}"]`)
  ) {
    filterMonth.value = monthParam;
  }
});
