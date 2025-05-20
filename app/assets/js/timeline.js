document.addEventListener("DOMContentLoaded", function () {
  initTimelineCalendar();

  initFilters();
});

/**
 * Inicializa o calendário da timeline
 */
function initTimelineCalendar() {
  const calendarEl = document.getElementById("calendar");
  if (!calendarEl) {
    console.error("Elemento do calendário não encontrado");
    return;
  }

  // Verificar se temos eventos para exibir
  if (!window.timelineEvents || window.timelineEvents.length === 0) {
    console.log("Nenhum evento disponível para exibir");
    return;
  }

  console.log("Total de eventos carregados:", window.timelineEvents.length);

  // Processar eventos para o formato do FullCalendar
  const events = window.timelineEvents.map(function (event) {
    // Definir cores com base no status
    let backgroundColor, borderColor, textColor;

    switch (event.status) {
      case "pendente":
        backgroundColor = "#ffc107";
        borderColor = "#e0a800";
        textColor = "#000";
        break;
      case "realizado":
        backgroundColor = "#28a745";
        borderColor = "#218838";
        textColor = "#fff";
        break;
      case "cancelado":
        backgroundColor = "#dc3545";
        borderColor = "#c82333";
        textColor = "#fff";
        break;
      case "aguardando_aprovacao":
        backgroundColor = "#17a2b8";
        borderColor = "#138496";
        textColor = "#fff";
        break;
      default:
        backgroundColor = event.agendaInfo.color || "#3788d8";
        borderColor = event.agendaInfo.color || "#3788d8";
        textColor = "#fff";
    }

    // Retornar evento formatado para FullCalendar
    return {
      id: event.id,
      title: event.title,
      start: event.start,
      end: event.end,
      backgroundColor: backgroundColor,
      borderColor: borderColor,
      textColor: textColor,
      extendedProps: {
        description: event.description,
        location: event.location,
        status: event.status,
        agendaInfo: event.agendaInfo,
      },
    };
  });

  // Inicializar o FullCalendar
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "pt-br",
    initialView: "timeGridDay",
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "", // Removido pois temos botões personalizados
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
    eventClick: function (info) {
      showEventDetails(info.event);
    },
  });

  calendar.render();

  // Manipular botões de visualização
  document.querySelectorAll(".view-option").forEach((button) => {
    button.addEventListener("click", function () {
      const view = this.getAttribute("data-view");
      calendar.changeView(view);

      // Atualizar estilo dos botões
      document.querySelectorAll(".view-option").forEach((btn) => {
        btn.classList.remove("active");
      });
      this.classList.add("active");
    });
  });
}

/**
 * Mostra detalhes de um evento em um popup
 */
function showEventDetails(event) {
  const modal = document.getElementById("event-modal");
  const detailsContainer = document.getElementById("event-details");

  if (modal && detailsContainer) {
    const startTime = new Date(event.start).toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    });
    const endTime = event.end
      ? new Date(event.end).toLocaleTimeString("pt-BR", {
          hour: "2-digit",
          minute: "2-digit",
        })
      : "";

    let content = `
            <div class="event-details-header" style="border-left: 4px solid ${
              event.backgroundColor
            }; padding-left: 10px;">
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
                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: ${event.extendedProps.agendaInfo.color}; margin-right: 5px;"></span>
                    ${event.extendedProps.agendaInfo.title} 
                    <span style="font-style: italic; color: #777;">(${event.extendedProps.agendaInfo.owner})</span>
                </p>`;
    }

    if (event.extendedProps.location) {
      content += `<p><i class="fas fa-map-marker-alt"></i> <strong>Local:</strong> ${event.extendedProps.location}</p>`;
    }

    if (event.extendedProps.description) {
      content += `
                <div class="description-section mt-3 pt-3" style="border-top: 1px solid #eee;">
                    <strong>Descrição:</strong>
                    <div class="p-2 mt-2 bg-light rounded">${event.extendedProps.description.replace(
                      /\n/g,
                      "<br>"
                    )}</div>
                </div>`;
    }

    content += `</div>`;

    // Inserir conteúdo no modal
    detailsContainer.innerHTML = content;

    // Abrir o modal
    $(modal).modal("show");
  } else {
    // Fallback se o modal não estiver disponível
    alert(
      `${event.title}\n\nHorário: ${startTime} ${
        endTime ? `até ${endTime}` : ""
      }\n` +
        (event.extendedProps.location
          ? `Local: ${event.extendedProps.location}\n`
          : "") +
        (event.extendedProps.description
          ? `Descrição: ${event.extendedProps.description}`
          : "")
    );
  }
}

/**
 * Retorna o rótulo para o status do evento
 */
function getStatusLabel(status) {
  switch (status) {
    case "pendente":
      return "Pendente";
    case "realizado":
      return "Realizado";
    case "cancelado":
      return "Cancelado";
    case "aguardando_aprovacao":
      return "Aguardando Aprovação";
    default:
      return status;
  }
}

/**
 * Inicializa filtros rápidos
 */
function initFilters() {
  // Filtro de data
  const datePicker = document.getElementById("date-picker");
  if (datePicker) {
    datePicker.addEventListener("change", function () {
      document.querySelector(".filter-form").submit();
    });
  }

  // Filtro de agendas
  const agendaSelect = document.getElementById("agenda-select");
  if (agendaSelect) {
    agendaSelect.addEventListener("change", function () {
      document.querySelector(".filter-form").submit();
    });
  }
}
