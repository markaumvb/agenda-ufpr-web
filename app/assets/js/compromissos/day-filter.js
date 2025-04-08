document.addEventListener("DOMContentLoaded", function () {
  console.log("🔍 Day Filter Script Loaded");

  // Selecionar dias do calendário
  const calendarDays = document.querySelectorAll(
    ".calendar-day:not(.empty-day)"
  );
  console.log(`🔍 Found ${calendarDays.length} calendar days`);

  // Selecionar cartões de eventos
  const eventCards = document.querySelectorAll(".event-card");
  console.log(`🔍 Found ${eventCards.length} event cards`);

  // Verificar se os cartões têm o atributo data-date
  let cardsWithDateAttr = 0;
  eventCards.forEach((card) => {
    if (card.dataset.date) {
      cardsWithDateAttr++;
    } else {
      console.warn("⚠️ Card without data-date attribute:", card);
    }
  });
  console.log(
    `🔍 ${cardsWithDateAttr} of ${eventCards.length} cards have data-date attribute`
  );

  // Adicionar estilo a cada dia do calendário
  calendarDays.forEach((day) => {
    // Garantir visualmente que está selecionável
    day.style.cursor = "pointer";

    // Adicionar evento de clique com debug
    day.addEventListener("click", function (e) {
      console.log("🔍 Calendar day clicked:", this);
      console.log("🔍 Date attribute:", this.dataset.date);

      e.preventDefault(); // Previnir comportamento padrão se houver
      e.stopPropagation(); // Impedir propagação do evento

      const date = this.dataset.date;
      if (!date) {
        console.warn("⚠️ Clicked day has no date attribute");
        return;
      }

      // Remover seleção de todos os dias
      calendarDays.forEach((d) => {
        d.classList.remove("selected-day");
        console.log("🔍 Removed selection from day");
      });

      // Se já estava selecionado, apenas limpar a seleção
      if (this.classList.contains("selected-day")) {
        console.log("🔍 Day was already selected, showing all events");
        this.classList.remove("selected-day");

        // Mostrar todos os cards
        eventCards.forEach((card) => {
          card.style.display = "block";
        });
        return;
      }

      // Adicionar classe de seleção
      this.classList.add("selected-day");
      console.log("🔍 Added selected-day class");

      // Contar quantos eventos correspondem à data
      let matchCount = 0;

      // Filtrar eventos
      eventCards.forEach((card) => {
        const cardDate = card.dataset.date;
        console.log(`🔍 Card date: ${cardDate}, Selected date: ${date}`);

        if (cardDate === date) {
          card.style.display = "block";
          matchCount++;
        } else {
          card.style.display = "none";
        }
      });

      console.log(`🔍 Found ${matchCount} matching events for date ${date}`);

      // Rolar para a lista de eventos
      const eventsContainer = document.querySelector(".events-list-container");
      if (eventsContainer) {
        eventsContainer.scrollIntoView({ behavior: "smooth" });
        console.log("🔍 Scrolled to events container");
      } else {
        console.warn("⚠️ Events container not found");
      }
    });
  });

  // Adicionar botão para limpar filtros
  const filtersArea = document.querySelector(".events-filters");
  if (filtersArea) {
    console.log("🔍 Found filters area, adding clear button");

    const clearBtn = document.createElement("button");
    clearBtn.className = "btn btn-sm btn-secondary";
    clearBtn.textContent = "Limpar filtro do calendário";
    clearBtn.style.marginLeft = "auto";

    clearBtn.addEventListener("click", function () {
      console.log("🔍 Clear button clicked");

      // Remover seleções do calendário
      calendarDays.forEach((day) => {
        day.classList.remove("selected-day");
      });

      // Mostrar todos os eventos
      eventCards.forEach((card) => {
        card.style.display = "block";
      });
    });

    filtersArea.appendChild(clearBtn);
  } else {
    console.warn("⚠️ Filters area not found");
  }
});
