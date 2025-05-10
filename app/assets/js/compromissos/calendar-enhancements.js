/**
 * Melhorias para a visualização do FullCalendar
 * Este script deve ser adicionado após o script original compromissos/fullcalendar.js
 */
document.addEventListener("DOMContentLoaded", function () {
  // Verificar se o calendário já foi inicializado
  if (typeof calendar === "undefined" || !calendar) {
    console.warn("Calendário não inicializado ainda");
    return;
  }

  // Melhorar a visualização de eventos no FullCalendar
  setTimeout(function () {
    // Adicionar classe especial a dias com eventos
    highlightDaysWithEvents();

    // Garantir que eventos estejam visíveis
    forceRefreshCalendar();
  }, 500); // Pequeno delay para garantir que o calendário foi renderizado

  // Funções auxiliares
  function highlightDaysWithEvents() {
    // Adicionar classe aos dias que têm eventos
    document.querySelectorAll(".fc-daygrid-day").forEach(function (dayEl) {
      const events = dayEl.querySelectorAll(".fc-daygrid-event");
      if (events.length > 0) {
        dayEl.classList.add("has-events");
      }
    });
  }

  function forceRefreshCalendar() {
    // Forçar o FullCalendar a refazer o layout
    if (calendar && typeof calendar.updateSize === "function") {
      calendar.updateSize();
    }
  }

  // Melhorar os eventos quando a visualização muda
  if (calendar) {
    calendar.on("viewDidMount", function () {
      setTimeout(highlightDaysWithEvents, 100);
    });
  }

  // Adicionar botão para alternar visibilidade dos eventos (útil para debug)
  const calendarContainer = document.querySelector(".calendar-container");
  if (calendarContainer) {
    const debugBtn = document.createElement("button");
    debugBtn.innerText = "Forçar Atualização";
    debugBtn.className = "btn btn-sm btn-secondary";
    debugBtn.style.marginBottom = "10px";
    debugBtn.addEventListener("click", function () {
      forceRefreshCalendar();
      highlightDaysWithEvents();
    });

    // Adicionar o botão discretamente ao container
    const viewOptions = document.querySelector(".view-options");
    if (viewOptions) {
      viewOptions.appendChild(debugBtn);
    } else {
      calendarContainer.insertBefore(debugBtn, calendarContainer.firstChild);
    }
  }

  // Verificar se temos eventos para mostrar
  if (window.allCompromissos && Array.isArray(window.allCompromissos)) {
    console.log("Eventos disponíveis: " + window.allCompromissos.length);
  } else {
    // Alternativa para localizar eventos na página
    const eventCards = document.querySelectorAll(".event-card");
    console.log("Cards de eventos na página: " + eventCards.length);

    // Se tiver eventos nos cards mas não no calendário, tentar adicionar manualmente
    if (eventCards.length > 0 && calendar) {
      const eventsFromCards = [];

      eventCards.forEach((card) => {
        const id = card.dataset.id;
        const title = card.querySelector(".event-title")?.textContent.trim();
        const status = card.dataset.status || "pendente";
        const date = card.dataset.date;

        if (id && title && date) {
          const event = {
            id: id,
            title: title,
            start: date,
            allDay: true,
            className: "event-status-" + status,
            backgroundColor: getStatusColor(status),
            borderColor: getStatusColor(status),
          };

          eventsFromCards.push(event);
        }
      });

      if (eventsFromCards.length > 0) {
        calendar.addEventSource(eventsFromCards);
      }
    }
  }

  function getStatusColor(status) {
    const colors = {
      pendente: "#ffc107",
      realizado: "#28a745",
      cancelado: "#dc3545",
      aguardando_aprovacao: "#17a2b8",
    };
    return colors[status] || "#3788d8";
  }
});

setTimeout(function () {
  // Aplicar classes de status a todos os eventos existentes
  document.querySelectorAll(".fc-event").forEach(function (eventEl) {
    // Verificar se tem dados de status
    const eventObj = eventEl.fcSeg?.eventRange?.def?.extendedProps;
    if (eventObj && eventObj.status) {
      // Adicionar classe de status aos eventos
      eventEl.classList.add("event-status-" + eventObj.status);
    }
  });

  // Buscar elementos que possam ter a classe errada
  const eventElements = document.querySelectorAll(".fc-daygrid-event");
  eventElements.forEach(function (el) {
    // Se o elemento não tem classe de status, procurar pelo texto de status ou pela cor
    if (!el.className.includes("event-status-")) {
      const statusColors = {
        "#ffc107": "pendente",
        "#28a745": "realizado",
        "#dc3545": "cancelado",
        "#17a2b8": "aguardando_aprovacao",
      };

      // Verificar pela cor de fundo
      const style = window.getComputedStyle(el);
      const bgColor = style.backgroundColor;

      // Tentar combinar com as cores conhecidas
      let matchedStatus = null;
      for (const [color, status] of Object.entries(statusColors)) {
        // Converter cor para RGB para comparação
        const tempDiv = document.createElement("div");
        tempDiv.style.color = color;
        document.body.appendChild(tempDiv);
        const colorRgb = window.getComputedStyle(tempDiv).color;
        document.body.removeChild(tempDiv);

        // Se a cor combina, adicionar a classe
        if (bgColor === colorRgb) {
          matchedStatus = status;
          break;
        }
      }

      if (matchedStatus) {
        el.classList.add("event-status-" + matchedStatus);
      } else {
        // Se não encontrou por cor, adicionar classe padrão
        el.classList.add("event-status-pendente");
      }
    }
  });
}, 800);
