/**
 * Timeline.js - Script para a funcionalidade da Linha do Tempo
 */
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar o calendário da timeline
  initTimelineCalendar();
});

/**
 * Inicializa o calendário da timeline
 */
function initTimelineCalendar() {
  const calendarEl = document.getElementById("calendar");
  if (!calendarEl) return;

  // Verificar se temos eventos para exibir
  if (!window.timelineEvents || window.timelineEvents.length === 0) return;

  // Processar eventos para o formato do FullCalendar
  const events = window.timelineEvents.map(function (event) {
    // Definir cores com base no status e na cor da agenda
    let backgroundColor, borderColor, textColor;
    const agendaColor = event.agendaInfo.color || "#3788d8";

    switch (event.status) {
      case "pendente":
        backgroundColor = agendaColor;
        borderColor = agendaColor;
        textColor = getContrastColor(agendaColor);
        break;
      case "realizado":
        // Versão mais clara da cor da agenda para eventos realizados
        backgroundColor = lightenColor(agendaColor, 30);
        borderColor = agendaColor;
        textColor = getContrastColor(backgroundColor);
        break;
      default:
        backgroundColor = agendaColor;
        borderColor = agendaColor;
        textColor = getContrastColor(agendaColor);
    }

    // Construir conteúdo do evento com mais informações
    let eventContent = {
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

    return eventContent;
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
    eventContent: function (arg) {
      // Personalizar o conteúdo do evento para mostrar mais informações
      return createEventContent(arg.event);
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
 * Cria o conteúdo personalizado para o evento
 */
function createEventContent(event) {
  // Obter informações do evento
  const title = event.title;
  const timeText = event.start
    ? event.start.toLocaleTimeString("pt-BR", {
        hour: "2-digit",
        minute: "2-digit",
      })
    : "";
  const location = event.extendedProps.location || "";
  const agendaName = event.extendedProps.agendaInfo.title || "";

  // Criar elementos HTML para o conteúdo do evento
  const container = document.createElement("div");
  container.className = "fc-event-content-wrapper";
  container.style.padding = "2px 4px";
  container.style.overflow = "hidden";
  container.style.width = "100%";
  container.style.height = "100%";

  // Título do evento
  const titleEl = document.createElement("div");
  titleEl.className = "fc-event-title";
  titleEl.textContent = title;
  titleEl.style.fontWeight = "bold";
  titleEl.style.whiteSpace = "nowrap";
  titleEl.style.overflow = "hidden";
  titleEl.style.textOverflow = "ellipsis";

  // Informações adicionais
  const infoEl = document.createElement("div");
  infoEl.className = "fc-event-info";
  infoEl.style.fontSize = "0.85em";
  infoEl.style.opacity = "0.8";

  // Adicionar tempo
  const timeEl = document.createElement("span");
  timeEl.textContent = timeText;
  infoEl.appendChild(timeEl);

  // Adicionar local se disponível
  if (location) {
    const locationEl = document.createElement("span");
    locationEl.textContent = ` • ${location}`;
    infoEl.appendChild(locationEl);
  }

  // Adicionar nome da agenda
  const agendaEl = document.createElement("span");
  agendaEl.textContent = ` • ${agendaName}`;
  agendaEl.style.fontSize = "0.8em";
  agendaEl.style.fontStyle = "italic";
  infoEl.appendChild(agendaEl);

  // Adicionar elementos ao container
  container.appendChild(titleEl);
  container.appendChild(infoEl);

  return { domNodes: [container] };
}

/**
 * Mostra detalhes de um evento em um popup
 */
function showEventDetails(event) {
  const modal = document.getElementById("event-modal");
  const detailsContainer = document.getElementById("event-details");

  if (modal && detailsContainer) {
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

    let content = `
            <div class="event-details-header" style="border-left: 4px solid ${
              event.backgroundColor
            }; padding-left: 10px; margin-bottom: 15px;">
                <h4 style="margin-bottom: 5px;">${event.title}</h4>
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

    content += `
            <div class="mt-3 pt-3" style="border-top: 1px solid #eee;">
                <a href="${PUBLIC_URL}/compromissos?agenda_id=${event.extendedProps.agendaInfo.id}" class="btn btn-primary btn-sm">
                    <i class="fas fa-calendar-alt"></i> Ver na Agenda
                </a>
            </div>
        `;

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
 * Calculando a cor de contraste para garantir legibilidade
 */
function getContrastColor(hexColor) {
  // Remover o # se existir
  hexColor = hexColor.replace("#", "");

  // Converter para RGB
  const r = parseInt(hexColor.substr(0, 2), 16);
  const g = parseInt(hexColor.substr(2, 2), 16);
  const b = parseInt(hexColor.substr(4, 2), 16);

  // Calcular a luminosidade
  const yiq = (r * 299 + g * 587 + b * 114) / 1000;

  // Retornar branco ou preto com base na luminosidade
  return yiq >= 128 ? "#000000" : "#ffffff";
}

/**
 * Clarear uma cor por uma porcentagem
 */
function lightenColor(color, percent) {
  // Remover o # se existir
  color = color.replace("#", "");

  // Converter para RGB
  let r = parseInt(color.substr(0, 2), 16);
  let g = parseInt(color.substr(2, 2), 16);
  let b = parseInt(color.substr(4, 2), 16);

  // Clarear
  r = Math.min(255, Math.floor(r + (255 - r) * (percent / 100)));
  g = Math.min(255, Math.floor(g + (255 - g) * (percent / 100)));
  b = Math.min(255, Math.floor(b + (255 - b) * (percent / 100)));

  // Converter de volta para hex
  const rHex = r.toString(16).padStart(2, "0");
  const gHex = g.toString(16).padStart(2, "0");
  const bHex = b.toString(16).padStart(2, "0");

  return `#${rHex}${gHex}${bHex}`;
}
