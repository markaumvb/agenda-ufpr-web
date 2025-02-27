/**
 * Script para interação com o calendário
 * Arquivo: public/assets/js/compromissos/calendar.js
 */

document.addEventListener("DOMContentLoaded", function () {
  // Adicionar evento de clique para cada dia do calendário
  setupCalendarDayClicks();
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
      const dayNumber = this.querySelector(".day-number")?.textContent;
      if (!dayNumber) return;

      const month = document.querySelector(".calendar-title").dataset.month;
      const year = document.querySelector(".calendar-title").dataset.year;
      const agendaId = document.querySelector(".calendar-container").dataset
        .agendaId;

      if (!month || !year || !agendaId) return;

      const dateStr = `${year}-${month.padStart(2, "0")}-${dayNumber.padStart(
        2,
        "0"
      )}`;

      // Obter compromissos deste dia (diretamente do DOM para evitar requisições adicionais)
      const dayEvents = this.querySelectorAll(".event");

      if (dayEvents.length > 0) {
        showDayEvents(dateStr, dayEvents);
      } else {
        // Se não há eventos no DOM mas o dia tem compromissos (talvez ocultos)
        if (this.classList.contains("has-events")) {
          fetchAndShowDayEvents(dateStr, agendaId);
        } else {
          // Opcionalmente, mostrar mensagem de que não há compromissos
          createNewEvent(dateStr, agendaId);
        }
      }
    });
  });
}

/**
 * Exibe os compromissos de um dia específico
 */
function showDayEvents(dateStr, dayEvents) {
  // Criar overlay
  const overlay = document.createElement("div");
  overlay.className = "day-details-overlay";

  // Formatar a data para exibição
  const date = new Date(dateStr);
  const formattedDate = date.toLocaleDateString("pt-BR", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  // Criar conteúdo
  overlay.innerHTML = `
    <div class="day-details-content">
      <div class="day-details-header">
        <h3 class="day-details-title">Compromissos de ${formattedDate}</h3>
        <button class="day-details-close">&times;</button>
      </div>
      <div class="day-events-list"></div>
    </div>
  `;

  // Adicionar eventos
  const eventsList = overlay.querySelector(".day-events-list");

  if (dayEvents.length === 0) {
    eventsList.innerHTML = "<p>Não há compromissos para este dia.</p>";
  } else {
    // Para cada evento no DOM, extrair informações e criar card
    dayEvents.forEach((event) => {
      const eventId = event.dataset.id;
      const eventTitle =
        event.dataset.title ||
        event.querySelector(".event-title")?.textContent ||
        "Compromisso";
      const eventTime =
        event.dataset.time ||
        event.querySelector(".event-time")?.textContent ||
        "";
      const eventStatus =
        event.dataset.status ||
        event.className.match(/event-status-([a-z_]+)/)?.[1] ||
        "pendente";

      // Criar elemento para o evento
      const eventCard = document.createElement("div");
      eventCard.className = `event-card event-status-${eventStatus}`;
      eventCard.innerHTML = `
        <div class="event-header">
          <h4>${eventTitle}</h4>
          <span class="badge badge-${eventStatus}">${getStatusLabel(
        eventStatus
      )}</span>
        </div>
        <div class="event-time">${eventTime}</div>
        <div class="event-actions">
          <a href="${BASE_URL}/public/compromissos/edit?id=${eventId}" class="btn btn-sm btn-secondary">
            <i class="icon-edit"></i> Editar
          </a>
        </div>
      `;

      eventsList.appendChild(eventCard);
    });
  }

  // Adicionar botão para criar novo compromisso
  const agendaId = document.querySelector(".calendar-container").dataset
    .agendaId;
  const canEdit =
    document.querySelector(".calendar-container").dataset.canEdit === "true";

  if (canEdit && agendaId) {
    const addButton = document.createElement("div");
    addButton.className = "text-center mt-4";
    addButton.innerHTML = `
      <a href="${BASE_URL}/public/compromissos/new?agenda_id=${agendaId}&date=${dateStr}" class="btn btn-primary">
        <i class="icon-plus"></i> Novo Compromisso
      </a>
    `;

    eventsList.appendChild(addButton);
  }

  // Adicionar overlay ao DOM
  document.body.appendChild(overlay);

  // Exibir overlay com animação
  setTimeout(() => {
    overlay.style.display = "flex";
  }, 10);

  // Configurar fechamento
  const closeButton = overlay.querySelector(".day-details-close");
  closeButton.addEventListener("click", () => {
    document.body.removeChild(overlay);
  });

  // Fechar ao clicar fora
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      document.body.removeChild(overlay);
    }
  });
}

/**
 * Busca e exibe os compromissos de um dia específico via AJAX
 */
function fetchAndShowDayEvents(dateStr, agendaId) {
  // Se estiver usando o calendário público, ajustar URL
  const isPublic = window.location.pathname.includes("public-agenda");
  let url;

  if (isPublic) {
    const hash = window.location.pathname.split("/").pop();
    url = `${BASE_URL}/public/api/public-events?hash=${hash}&date=${dateStr}`;
  } else {
    url = `${BASE_URL}/public/api/events?agenda_id=${agendaId}&date=${dateStr}`;
  }

  // Mostrar indicador de carregamento
  const loadingOverlay = document.createElement("div");
  loadingOverlay.className = "day-details-overlay";
  loadingOverlay.innerHTML = `
    <div class="day-details-content">
      <div class="text-center">
        <p>Carregando compromissos...</p>
      </div>
    </div>
  `;
  document.body.appendChild(loadingOverlay);
  loadingOverlay.style.display = "flex";

  // Fazer a requisição
  fetch(url)
    .then((response) => response.json())
    .then((data) => {
      document.body.removeChild(loadingOverlay);

      if (data.events && data.events.length > 0) {
        showDayEventsFromData(dateStr, data.events, agendaId);
      } else {
        createNewEvent(dateStr, agendaId);
      }
    })
    .catch((error) => {
      console.error("Erro ao buscar compromissos:", error);
      document.body.removeChild(loadingOverlay);

      // Mostrar mensagem de erro
      const errorOverlay = document.createElement("div");
      errorOverlay.className = "day-details-overlay";
      errorOverlay.innerHTML = `
        <div class="day-details-content">
          <div class="day-details-header">
            <h3 class="day-details-title">Erro</h3>
            <button class="day-details-close">&times;</button>
          </div>
          <p>Ocorreu um erro ao buscar os compromissos. Tente novamente.</p>
        </div>
      `;
      document.body.appendChild(errorOverlay);
      errorOverlay.style.display = "flex";

      const closeButton = errorOverlay.querySelector(".day-details-close");
      closeButton.addEventListener("click", () => {
        document.body.removeChild(errorOverlay);
      });
    });
}

/**
 * Exibe os compromissos buscados via AJAX
 */
function showDayEventsFromData(dateStr, events, agendaId) {
  const dayEvents = [];

  // Converter eventos do formato JSON para o formato esperado por showDayEvents
  events.forEach((event) => {
    const eventElement = document.createElement("div");
    eventElement.className = `event event-status-${event.status}`;
    eventElement.dataset.id = event.id;
    eventElement.dataset.title = event.title;
    eventElement.dataset.time = formatTimeRange(
      event.start_datetime,
      event.end_datetime
    );
    eventElement.dataset.status = event.status;

    dayEvents.push(eventElement);
  });

  showDayEvents(dateStr, dayEvents);
}

/**
 * Exibe o formulário para criar um novo compromisso
 */
function createNewEvent(dateStr, agendaId) {
  const canEdit =
    document.querySelector(".calendar-container").dataset.canEdit === "true";

  if (!canEdit) {
    showDayEvents(dateStr, []);
    return;
  }

  // Redirecionar para formulário de criação
  window.location.href = `${BASE_URL}/public/compromissos/new?agenda_id=${agendaId}&date=${dateStr}`;
}

/**
 * Formata o intervalo de horas
 */
function formatTimeRange(startDatetime, endDatetime) {
  const start = new Date(startDatetime);
  const end = new Date(endDatetime);

  return `${start.toLocaleTimeString("pt-BR", {
    hour: "2-digit",
    minute: "2-digit",
  })} - 
          ${end.toLocaleTimeString("pt-BR", {
            hour: "2-digit",
            minute: "2-digit",
          })}`;
}

/**
 * Retorna o label para cada status
 */
function getStatusLabel(status) {
  const labels = {
    pendente: "Pendente",
    realizado: "Realizado",
    cancelado: "Cancelado",
    aguardando_aprovacao: "Aguardando",
  };

  return labels[status] || status;
}
