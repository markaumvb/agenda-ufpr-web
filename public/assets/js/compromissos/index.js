// Filtros da lista de compromissos
document.addEventListener("DOMContentLoaded", function () {
  const filterStatus = document.getElementById("filter-status");
  const filterMonth = document.getElementById("filter-month");
  const filterSearch = document.getElementById("filter-search");
  const eventCards = document.querySelectorAll(".event-card");

  // Inicializar filtros
  if (filterMonth) {
    // Obter o mês da URL ou usar o mês atual
    const urlParams = new URLSearchParams(window.location.search);
    const monthParam = urlParams.get("month");

    if (
      monthParam &&
      filterMonth.querySelector(`option[value="${monthParam}"]`)
    ) {
      filterMonth.value = monthParam;
    }
  }

  // Aplicar filtros quando alterados
  if (filterStatus) filterStatus.addEventListener("change", applyFilters);
  if (filterMonth) filterMonth.addEventListener("change", applyFilters);
  if (filterSearch) filterSearch.addEventListener("input", applyFilters);

  // Função para aplicar os filtros
  function applyFilters() {
    const statusFilter = filterStatus ? filterStatus.value : "all";
    const monthFilter = filterMonth ? filterMonth.value : "all";
    const searchFilter = filterSearch
      ? filterSearch.value.toLowerCase().trim()
      : "";

    eventCards.forEach((card) => {
      const status = card.dataset.status;
      const month = card.dataset.month;
      const searchText = card.dataset.search;

      // Verificar status
      const statusMatch = statusFilter === "all" || status === statusFilter;

      // Verificar mês
      const monthMatch = monthFilter === "all" || month === monthFilter;

      // Verificar texto de busca
      const searchMatch = !searchFilter || searchText.includes(searchFilter);

      // Exibir ou ocultar o card
      if (statusMatch && monthMatch && searchMatch) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });
  }
});
