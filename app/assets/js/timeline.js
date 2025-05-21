document.addEventListener("DOMContentLoaded", function () {
  // Initialize the calendar as soon as the DOM is ready
  initTimelineCalendar();
});

/**
 * Initializes the timeline calendar
 */
function initTimelineCalendar() {
  const calendarEl = document.getElementById("calendar");
  if (!calendarEl) {
    console.error("Calendar element not found!");
    return;
  }

  // Get events or initialize empty array if not available
  const events = window.timelineEvents || [];

  // Initialize FullCalendar
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "pt-br",
    initialView: "timeGridDay", // Start with day view
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "", // We use custom buttons instead
    },
    slotMinTime: "06:00:00",
    slotMaxTime: "22:00:00",
    allDaySlot: false,
    nowIndicator: true,
    height: "auto",
    events: events,
    eventTimeFormat: {
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    },
    eventContent: function (arg) {
      // Custom event rendering
      return {
        html: `
          <div class="fc-event-main-frame">
            <div class="fc-event-title-container">
              <div class="fc-event-title fc-sticky">${arg.event.title}</div>
            </div>
            <div class="fc-event-text">
              ${
                arg.event.extendedProps.location
                  ? `<i class="fas fa-map-marker-alt"></i> ${arg.event.extendedProps.location}`
                  : ""
              }
              ${
                arg.event.extendedProps.location &&
                arg.event.extendedProps.agendaInfo
                  ? " • "
                  : ""
              }
              ${
                arg.event.extendedProps.agendaInfo
                  ? `<i class="fas fa-calendar"></i> ${arg.event.extendedProps.agendaInfo.title}`
                  : ""
              }
            </div>
          </div>
        `,
      };
    },
    eventClick: function (info) {
      showEventDetails(info.event);
    },
    eventDidMount: function (info) {
      // Add status to element for CSS styling
      if (info.event.extendedProps.status) {
        info.el.setAttribute("data-status", info.event.extendedProps.status);
      }

      // Set background color based on agenda color
      if (
        info.event.extendedProps.agendaInfo &&
        info.event.extendedProps.agendaInfo.color
      ) {
        const status = info.event.extendedProps.status;
        let color = info.event.extendedProps.agendaInfo.color;

        // Modify color based on status
        if (status === "realizado") {
          // Lighter version for completed events
          info.el.style.opacity = "0.7";
        } else if (status === "cancelado") {
          // Add strike-through for cancelled events
          info.el.style.textDecoration = "line-through";
          info.el.style.opacity = "0.7";
        }

        // Apply colors
        info.el.style.backgroundColor = color;
        info.el.style.borderLeftColor = color;
      }
    },
  });

  // Render the calendar
  calendar.render();

  // Set up view switching buttons
  document.querySelectorAll(".view-option").forEach((button) => {
    button.addEventListener("click", function () {
      const view = this.getAttribute("data-view");

      // Switch view if valid
      if (view && calendar) {
        calendar.changeView(view);

        // Update active button
        document.querySelectorAll(".view-option").forEach((btn) => {
          btn.classList.remove("active");
        });
        this.classList.add("active");
      }
    });
  });
}

/**
 * Shows event details in a modal
 */
function showEventDetails(event) {
  const modal = document.getElementById("event-modal");
  const detailsContainer = document.getElementById("event-details");

  if (!modal || !detailsContainer) return;

  const startTime = event.start
    ? event.start.toLocaleTimeString("pt-BR", {
        hour: "2-digit",
        minute: "2-digit",
      })
    : "";
  const endTime = event.end
    ? event.end.toLocaleTimeString("pt-BR", {
        hour: "2-digit",
        minute: "2-digit",
      })
    : "";

  const agendaColor = event.extendedProps.agendaInfo?.color || "#3788d8";

  let content = `
    <div class="event-details-header" style="border-left-color: ${agendaColor}">
      <h4>${event.title}</h4>
      <span class="badge badge-${event.extendedProps.status}">
        ${getStatusLabel(event.extendedProps.status)}
      </span>
    </div>
    <div class="event-details-body">
      <p>
        <i class="fas fa-clock"></i> <strong>Horário:</strong> 
        ${startTime} ${endTime ? `até ${endTime}` : ""}
      </p>`;

  if (event.extendedProps.agendaInfo) {
    content += `
      <p>
        <i class="fas fa-calendar"></i> <strong>Agenda:</strong> 
        <span class="agenda-color-dot" style="background-color: ${agendaColor}"></span>
        ${event.extendedProps.agendaInfo.title} 
        <span class="text-muted">(${
          event.extendedProps.agendaInfo.owner || "Desconhecido"
        })</span>
      </p>`;
  }

  if (event.extendedProps.location) {
    content += `<p><i class="fas fa-map-marker-alt"></i> <strong>Local:</strong> ${event.extendedProps.location}</p>`;
  }

  if (event.extendedProps.description) {
    content += `
      <div class="description-section">
        <strong>Descrição:</strong>
        <div class="bg-light description-text">${event.extendedProps.description.replace(
          /\n/g,
          "<br>"
        )}</div>
      </div>`;
  }

  content += `
    <div class="mt-3 pt-3 border-top">
      <a href="${PUBLIC_URL}/compromissos?agenda_id=${event.extendedProps.agendaInfo?.id}" class="btn btn-primary btn-sm">
        <i class="fas fa-calendar-alt"></i> Ver na Agenda
      </a>
    </div>`;

  // Insert content and show modal
  detailsContainer.innerHTML = content;
  $(modal).modal("show");
}

/**
 * Returns the label for event status
 */
function getStatusLabel(status) {
  const labels = {
    pendente: "Pendente",
    realizado: "Realizado",
    cancelado: "Cancelado",
    aguardando_aprovacao: "Aguardando Aprovação",
  };
  return labels[status] || status;
}
