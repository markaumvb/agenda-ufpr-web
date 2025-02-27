document.addEventListener("DOMContentLoaded", function () {
  // Adicionar evento de clique para cada dia do calendário
  setupCalendarDayClicks();
  setupCloseButton();
  highlightToday();
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

      const agendaId = document.querySelector(".calendar-container").dataset
        .agendaId;
      showDayEvents(this, date, agendaId);
    });
  });
}

/**
 * Configura o botão de fechar o painel de compromissos
 */
function setupCloseButton() {
  const closeButton = document.querySelector(".day-events-close");
  if (closeButton) {
    closeButton.addEventListener("click", function () {
      hideDayEvents();
    });
  }
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
 * Exibe os compromissos de um dia específico
 */
function showDayEvents(dayElement, date, agendaId) {
  // Formatar a data para exibição
  const dateObj = new Date(date + "T00:00:00");
  const formattedDate = dateObj.toLocaleDateString("pt-BR", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  // Atualizar o título
  document.getElementById("day-events-title").textContent =
    "Compromissos de " + formattedDate;

  // Buscar compromissos do dia
  const dayEvents = dayElement.querySelectorAll(".event");

  const eventsList = document.getElementById("day-events-list");
  eventsList.innerHTML = "";

  if (dayEvents.length === 0) {
    eventsList.innerHTML =
      '<p class="no-events">Não há compromissos para este dia.</p>';
  } else {
    // Para cada evento, criar um card
    dayEvents.forEach((event) => {
      const id = event.dataset.id;
      const title =
        event.dataset.title || event.querySelector(".event-title").textContent;
      const description = event.dataset.description || "";
      const start = new Date(
        event.dataset.start || event.querySelector(".event-time").textContent
      );
      const end = new Date(event.dataset.end || "");
      const status =
        event.dataset.status || event.className.match(/event-status-(\w+)/)[1];

      const card = document.createElement("div");
      card.className = `event-card event-status-${status}`;

      // Formatar horário
      const formattedTime = `${start.toLocaleTimeString("pt-BR", {
        hour: "2-digit",
        minute: "2-digit",
      })}${
        end
          ? " - " +
            end.toLocaleTimeString("pt-BR", {
              hour: "2-digit",
              minute: "2-digit",
            })
          : ""
      }`;

      card.innerHTML = `
              <div class="event-card-header">
                  <h4>${title}</h4>
                  <span class="event-time">${formattedTime}</span>
              </div>
              ${
                description
                  ? `<div class="event-description">${description}</div>`
                  : ""
              }
              <div class="event-card-actions">
                  <a href="${BASE_URL}/public/compromissos/edit?id=${id}" class="btn btn-sm btn-secondary">
                      <i class="icon-edit"></i> Editar
                  </a>
              </div>
          `;

      eventsList.appendChild(card);
    });
  }

  // Adicionar botão para criar novo compromisso
  const canEdit =
    document.querySelector(".header-actions a.btn-primary") !== null;

  if (canEdit && agendaId) {
    const addButton = document.createElement("div");
    addButton.className = "add-event-button mt-3";
    addButton.innerHTML = `
          <a href="${BASE_URL}/public/compromissos/new?agenda_id=${agendaId}&date=${date}" class="btn btn-primary">
              <i class="fas fa-plus"></i> Novo Compromisso neste dia
          </a>
      `;

    eventsList.appendChild(addButton);
  }

  // Exibir o container
  const eventsContainer = document.getElementById("day-events-container");
  eventsContainer.style.display = "block";

  // Adicionar classe ativa no dia selecionado
  document.querySelectorAll(".calendar-day").forEach((day) => {
    day.classList.remove("selected-day");
  });
  dayElement.classList.add("selected-day");

  // Rolar para o container de eventos
  eventsContainer.scrollIntoView({ behavior: "smooth", block: "start" });
}

/**
 * Oculta o painel de compromissos
 */
function hideDayEvents() {
  document.getElementById("day-events-container").style.display = "none";

  // Remover classe ativa do dia selecionado
  document.querySelectorAll(".calendar-day").forEach((day) => {
    day.classList.remove("selected-day");
  });
}
