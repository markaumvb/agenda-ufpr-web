document.addEventListener("DOMContentLoaded", function () {
  // Configurar cliques no calendário
  setupCalendarDayClicks();

  // Destacar dia atual
  highlightToday();

  // Adicionar botão para limpar filtros
  addClearFiltersButton();
});

/**
 * Configura os eventos de clique nos dias do calendário
 */
function setupCalendarDayClicks() {
  const calendarDays = document.querySelectorAll(
    ".calendar-day:not(.empty-day)"
  );

  calendarDays.forEach((day) => {
    day.addEventListener("click", function () {
      const date = this.dataset.date;
      if (!date) return;

      // Verificar se o dia já está selecionado
      const isSelected = this.classList.contains("selected-day");

      // Remover seleção de todos os dias
      document.querySelectorAll(".calendar-day").forEach((d) => {
        d.classList.remove("selected-day");
      });

      // Se o dia já estava selecionado, limpar o filtro
      if (isSelected) {
        window.selectedDate = null;
      } else {
        // Caso contrário, selecionar o dia e aplicar o filtro
        this.classList.add("selected-day");
        window.selectedDate = date;
      }

      // Aplicar filtros (função do index.js)
      if (typeof window.applyFilters === "function") {
        window.applyFilters();

        // Rolar para a lista de compromissos
        const eventsList = document.querySelector(".events-list-container");
        if (eventsList) {
          eventsList.scrollIntoView({ behavior: "smooth" });
        }
      }
    });
  });
}

/**
 * Destaca o dia atual no calendário
 */
function highlightToday() {
  const today = new Date().toISOString().split("T")[0];
  const todayCell = document.querySelector(
    `.calendar-day[data-date="${today}"]`
  );

  if (todayCell) {
    todayCell.classList.add("today");
  }
}

/**
 * Adiciona botão para limpar filtros na área de filtros
 */
function addClearFiltersButton() {
  const filtersContainer = document.querySelector(".events-filters");

  if (filtersContainer && !document.querySelector(".calendar-clear-btn")) {
    const clearBtn = document.createElement("div");
    clearBtn.className = "filter-group";
    clearBtn.innerHTML = `<button class="btn btn-secondary calendar-clear-btn">Limpar Todos os Filtros</button>`;

    filtersContainer.appendChild(clearBtn);

    // Adicionar evento de clique
    clearBtn
      .querySelector(".calendar-clear-btn")
      .addEventListener("click", function () {
        if (typeof window.clearFilters === "function") {
          window.clearFilters();
        }
      });
  }
}
