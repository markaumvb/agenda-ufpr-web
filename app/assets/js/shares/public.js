ocument.addEventListener("DOMContentLoaded", function () {
  // Filtros da lista de compromissos
  const filterStatus = document.getElementById("filter-status");
  const filterMonth = document.getElementById("filter-month");
  const filterSearch = document.getElementById("filter-search");
  const eventCards = document.querySelectorAll(".event-card");
  const clearFilters = document.getElementById("clear-filters");

  // Aplicar filtros quando alterados
  if (filterStatus) filterStatus.addEventListener("change", applyFilters);
  if (filterMonth) filterMonth.addEventListener("change", applyFilters);
  if (filterSearch) filterSearch.addEventListener("input", applyFilters);
  if (clearFilters) clearFilters.addEventListener("click", clearAllFilters);

  // Função para limpar filtros
  function clearAllFilters() {
    if (filterStatus) filterStatus.value = "all";
    if (filterMonth) filterMonth.value = "all";
    if (filterSearch) filterSearch.value = "";

    applyFilters();
  }

  // Função para aplicar os filtros
  function applyFilters() {
    const statusFilter = filterStatus ? filterStatus.value : "all";
    const monthFilter = filterMonth ? filterMonth.value : "all";
    const searchFilter = filterSearch
      ? filterSearch.value.toLowerCase().trim()
      : "";

    let visibleCount = 0;

    // Filtrar cards de eventos
    eventCards.forEach((card) => {
      const status = card.dataset.status;
      const month = card.dataset.month;
      const searchText = card.dataset.search || "";

      // Verificar correspondência com filtros
      const statusMatch = statusFilter === "all" || status === statusFilter;
      const monthMatch = monthFilter === "all" || month === monthFilter;
      const searchMatch = !searchFilter || searchText.includes(searchFilter);

      // Exibir ou ocultar o card
      const isVisible = statusMatch && monthMatch && searchMatch;
      card.style.display = isVisible ? "block" : "none";

      if (isVisible) visibleCount++;
    });

    // Atualizar o calendário (se estiver disponível na página)
    if (window.calendar && window.allEvents) {
      const filteredEvents = window.allEvents.filter((event) => {
        // Status
        const statusMatch =
          statusFilter === "all" || event.extendedProps.status === statusFilter;

        // Mês
        const eventDate = new Date(event.start);
        const eventMonth = (eventDate.getMonth() + 1).toString();
        const monthMatch = monthFilter === "all" || eventMonth === monthFilter;

        // Texto
        const searchableText = (
          event.title +
          " " +
          (event.extendedProps.description || "") +
          " " +
          (event.extendedProps.location || "")
        ).toLowerCase();
        const searchMatch =
          !searchFilter || searchableText.includes(searchFilter);

        return statusMatch && monthMatch && searchMatch;
      });

      window.calendar.removeAllEvents();
      window.calendar.addEventSource(filteredEvents);
    }

    // Mostrar mensagem se não há resultados
    const noResults = document.querySelector(".no-results");
    if (noResults) {
      noResults.style.display = visibleCount === 0 ? "block" : "none";
    }
  }

  // Expor funções para uso global
  window.applyFilters = applyFilters;
  window.clearAllFilters = clearAllFilters;
});
