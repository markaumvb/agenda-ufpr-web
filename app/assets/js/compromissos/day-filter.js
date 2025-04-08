document.addEventListener("DOMContentLoaded", function () {
  console.log("Calendar day filter initialized");

  // Selecionar todos os dias do calendário
  const calendarDays = document.querySelectorAll(
    ".calendar-day:not(.empty-day)"
  );

  // Container para exibir eventos filtrados
  const eventsList = document.querySelector(".events-list");
  const allEventCards = document.querySelectorAll(".event-card");

  // Adicionar evento de clique a cada dia
  calendarDays.forEach((day) => {
    day.addEventListener("click", function () {
      console.log("Day clicked:", this.dataset.date);

      // Remover classe selecionada de todos os dias
      calendarDays.forEach((d) => d.classList.remove("selected-day"));

      // Adicionar classe selecionada ao dia clicado
      this.classList.add("selected-day");

      // Filtrar eventos pela data selecionada
      const selectedDate = this.dataset.date;

      // Primeiro, ocultar todos os eventos
      allEventCards.forEach((card) => {
        card.style.display = "none";
      });

      // Mostrar apenas eventos da data selecionada
      const filteredEvents = document.querySelectorAll(
        `.event-card[data-date="${selectedDate}"]`
      );

      // Se não houver eventos para o dia, mostrar mensagem
      if (filteredEvents.length === 0) {
        // Verificar se já existe uma mensagem
        let noEventsMsg = document.querySelector(".no-events-message");
        if (!noEventsMsg) {
          noEventsMsg = document.createElement("div");
          noEventsMsg.className = "no-events-message";
          noEventsMsg.innerHTML = `<p>Não há eventos para o dia ${new Date(
            selectedDate
          ).toLocaleDateString("pt-BR")}</p>`;

          // Botão para limpar filtro
          const clearBtn = document.createElement("button");
          clearBtn.className = "btn btn-outline";
          clearBtn.textContent = "Mostrar todos os eventos";
          clearBtn.addEventListener("click", clearFilter);
          noEventsMsg.appendChild(clearBtn);

          eventsList.prepend(noEventsMsg);
        } else {
          noEventsMsg.style.display = "block";
        }
      } else {
        // Esconder mensagem se existir
        const noEventsMsg = document.querySelector(".no-events-message");
        if (noEventsMsg) {
          noEventsMsg.style.display = "none";
        }

        // Mostrar eventos filtrados
        filteredEvents.forEach((event) => {
          event.style.display = "block";
        });
      }

      // Rolar até a lista de eventos
      eventsList.scrollIntoView({ behavior: "smooth" });
    });
  });

  // Função para limpar filtro
  function clearFilter() {
    // Mostrar todos os eventos
    allEventCards.forEach((card) => {
      card.style.display = "block";
    });

    // Esconder mensagem de não há eventos
    const noEventsMsg = document.querySelector(".no-events-message");
    if (noEventsMsg) {
      noEventsMsg.style.display = "none";
    }

    // Remover seleção no calendário
    calendarDays.forEach((d) => d.classList.remove("selected-day"));
  }

  // Adicionar um botão para limpar filtro
  const filtersArea = document.querySelector(".events-filters");
  if (filtersArea) {
    const clearFilterBtn = document.createElement("button");
    clearFilterBtn.className = "btn btn-sm btn-outline clear-filter-btn";
    clearFilterBtn.textContent = "Limpar filtros";
    clearFilterBtn.addEventListener("click", clearFilter);
    filtersArea.appendChild(clearFilterBtn);
  }
});
