// Filtros da lista de compromissos
document.addEventListener("DOMContentLoaded", function () {
  const filterStatus = document.getElementById("filter-status");
  const filterMonth = document.getElementById("filter-month");
  const filterSearch = document.getElementById("filter-search");
  const eventCards = document.querySelectorAll(".event-card");
  const clearFilters = document.getElementById("clear-filters");

  // Adicionar variável global para rastrear o filtro de data
  window.selectedDate = null;

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
  if (clearFilters) clearFilters.addEventListener("click", clearAllFilters);

  // Função para aplicar os filtros
  function applyFilters() {
    const statusFilter = filterStatus ? filterStatus.value : "all";
    const monthFilter = filterMonth ? filterMonth.value : "all";
    const searchFilter = filterSearch
      ? filterSearch.value.toLowerCase().trim()
      : "";
    const dateFilter = window.selectedDate;

    // Contador para saber quantos eventos estão sendo exibidos
    let visibleCount = 0;

    eventCards.forEach((card) => {
      const status = card.dataset.status;
      const month = card.dataset.month;
      const searchText = card.dataset.search || "";
      const cardDate = card.dataset.date;

      // Verificar status
      const statusMatch = statusFilter === "all" || status === statusFilter;

      // Verificar mês
      const monthMatch = monthFilter === "all" || month === monthFilter;

      // Verificar texto de busca
      const searchMatch = !searchFilter || searchText.includes(searchFilter);

      // Verificar data (se aplicável)
      const dateMatch = !dateFilter || cardDate === dateFilter;

      // Exibir ou ocultar o card
      if (statusMatch && monthMatch && searchMatch && dateMatch) {
        card.style.display = "block";
        visibleCount++;
      } else {
        card.style.display = "none";
      }
    });

    // Mostrar ou ocultar mensagem de "nenhum compromisso encontrado"
    const noEventsMessage = document.querySelector(".no-events-message");

    if (visibleCount === 0) {
      // Se não existe mensagem, criar uma
      if (!noEventsMessage) {
        const eventsListContainer = document.querySelector(".events-list");
        if (eventsListContainer) {
          const message = document.createElement("div");
          message.className = "no-events-message";
          message.innerHTML = `
            <p>Nenhum compromisso encontrado com os filtros selecionados.</p>
            <button class="btn btn-secondary clear-filters-btn">Limpar Filtros</button>
          `;

          // Adicionar ao topo da lista
          eventsListContainer.prepend(message);

          // Adicionar evento ao botão
          const clearBtn = message.querySelector(".clear-filters-btn");
          if (clearBtn) {
            clearBtn.addEventListener("click", clearAllFilters);
          }
        }
      } else {
        noEventsMessage.style.display = "block";
      }
    } else if (noEventsMessage) {
      // Esconder mensagem se existir e houver eventos
      noEventsMessage.style.display = "none";
    }
  }

  // Função para limpar todos os filtros
  function clearAllFilters() {
    // Resetar filtros
    if (filterStatus) filterStatus.value = "all";
    if (filterMonth) filterMonth.value = "all";
    if (filterSearch) filterSearch.value = "";

    // Limpar filtro de data
    window.selectedDate = null;

    // Remover destaque do calendário
    document.querySelectorAll(".calendar-day").forEach((day) => {
      day.classList.remove("selected-day");
    });

    // Aplicar filtros limpos
    applyFilters();
  }

  // Tornar as funções acessíveis globalmente
  window.applyFilters = applyFilters;
  window.clearAllFilters = clearAllFilters;

  // Executar filtros iniciais
  applyFilters();
});
