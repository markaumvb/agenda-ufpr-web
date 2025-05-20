/**
 * Timeline.js - Script para a funcionalidade da Linha do Tempo
 */
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar a visualização do calendário
  initTimelineCalendar();

  // Alternar entre visualizações de lista e calendário
  initViewToggle();

  // Inicializar filtros rápidos
  initFilters();
});

/**
 * Inicializa o calendário da timeline
 */
function initTimelineCalendar() {
  var calendarEl = document.getElementById("timeline-calendar");
  if (!calendarEl) return;

  // Verificar se temos eventos para exibir
  if (!window.timelineEvents || window.timelineEvents.length === 0) {
    return;
  }

  // Processar eventos para o formato do FullCalendar
  var events = window.timelineEvents.map(function (event) {
    var startDateTime = new Date(event.start_datetime);
    var endDateTime = new Date(event.end_datetime);

    // Definir cores com base no status
    var backgroundColor, borderColor, textColor;

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
        backgroundColor = event.agenda_info.color;
        borderColor = event.agenda_info.color;
        textColor = "#fff";
    }

    // Retornar evento formatado para FullCalendar
    return {
      id: event.id,
      title: event.title,
      start: startDateTime,
      end: endDateTime,
      backgroundColor: backgroundColor,
      borderColor: borderColor,
      textColor: textColor,
      extendedProps: {
        description: event.description,
        location: event.location,
        status: event.status,
        agendaInfo: event.agenda_info,
      },
    };
  });

  // Inicializar o FullCalendar
  var calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "pt-br",
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "timeGridDay,listDay",
    },
    initialView: "timeGridDay",
    navLinks: true,
    selectable: false,
    selectMirror: true,
    dayMaxEvents: true,
    nowIndicator: true,
    slotMinTime: "07:00:00",
    slotMaxTime: "22:00:00",
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

  // Renderizar o calendário
  calendar.render();

  // Ajustar as cores dos eventos baseados no status
  setTimeout(function () {
    updateEventStyles();
  }, 100);
}

/**
 * Mostra detalhes de um evento em um popup
 */
function showEventDetails(event) {
  // Obter propriedades estendidas
  var extendedProps = event.extendedProps;

  // Criar conteúdo do modal
  var modalContent = '<div class="event-detail-popup">';
  modalContent += '<div class="event-header">';
  modalContent += "<h4>" + event.title + "</h4>";

  // Status do evento
  var statusClass = "";
  var statusText = "";

  switch (extendedProps.status) {
    case "pendente":
      statusClass = "badge-pendente";
      statusText = "Pendente";
      break;
    case "realizado":
      statusClass = "badge-realizado";
      statusText = "Realizado";
      break;
    case "cancelado":
      statusClass = "badge-cancelado";
      statusText = "Cancelado";
      break;
    case "aguardando_aprovacao":
      statusClass = "badge-aguardando_aprovacao";
      statusText = "Aguardando Aprovação";
      break;
  }

  modalContent += '<span class="' + statusClass + '">' + statusText + "</span>";
  modalContent += "</div>";

  // Informações da agenda
  modalContent += '<div class="agenda-info">';
  modalContent +=
    '<span class="agenda-color-dot" style="background-color: ' +
    extendedProps.agendaInfo.color +
    '"></span>';
  modalContent += "Agenda: " + extendedProps.agendaInfo.title;
  modalContent += " (" + extendedProps.agendaInfo.owner_name + ")";
  modalContent += "</div>";

  // Detalhes do evento
  modalContent += '<div class="event-details">';

  // Data e hora
  var startTime = event.start ? formatDateTime(event.start) : "";
  var endTime = event.end ? formatDateTime(event.end) : "";

  modalContent +=
    "<p><strong>Horário:</strong> " + startTime + " até " + endTime + "</p>";

  // Local
  if (extendedProps.location) {
    modalContent +=
      "<p><strong>Local:</strong> " + extendedProps.location + "</p>";
  }

  // Descrição
  if (extendedProps.description) {
    modalContent +=
      "<p><strong>Descrição:</strong><br>" +
      formatDescription(extendedProps.description) +
      "</p>";
  }

  modalContent += "</div>";

  // Adicionar botões de ação
  modalContent += '<div class="modal-actions">';
  modalContent +=
    '<a href="' +
    baseUrl +
    "/compromissos?agenda_id=" +
    extendedProps.agendaInfo.id +
    '" class="btn btn-primary">Ver na Agenda</a>';
  modalContent += "</div>";

  modalContent += "</div>";

  // Exibir o modal (usando biblioteca ou função personalizada)
  alert(
    event.title +
      "\n\nHorário: " +
      startTime +
      " até " +
      endTime +
      "\n" +
      (extendedProps.location
        ? "Local: " + extendedProps.location + "\n"
        : "") +
      (extendedProps.description
        ? "Descrição: " + extendedProps.description
        : "")
  );
}

/**
 * Formata data e hora para exibição
 */
function formatDateTime(date) {
  if (!date) return "";

  var hours = date.getHours().toString().padStart(2, "0");
  var minutes = date.getMinutes().toString().padStart(2, "0");

  return hours + ":" + minutes;
}

/**
 * Formata descrição para exibição em HTML
 */
function formatDescription(description) {
  if (!description) return "";

  // Substituir quebras de linha por <br>
  return description.replace(/\n/g, "<br>");
}

/**
 * Inicializa o toggle entre visualização de lista e calendário
 */
function initViewToggle() {
  var viewOptions = document.querySelectorAll(".view-option");
  var calendarContainer = document.getElementById("calendar-container");
  var eventsList = document.querySelector(".events-list");

  if (!viewOptions || !calendarContainer || !eventsList) return;

  viewOptions.forEach(function (option) {
    option.addEventListener("click", function () {
      // Remover classe ativa de todas as opções
      viewOptions.forEach(function (opt) {
        opt.classList.remove("active");
      });

      // Adicionar classe ativa à opção clicada
      this.classList.add("active");

      // Alternar visualização
      if (this.dataset.view === "list") {
        calendarContainer.style.display = "none";
        eventsList.style.display = "block";
      } else {
        calendarContainer.style.display = "block";
        eventsList.style.display = "none";

        // Disparar redimensionamento para ajustar o calendário
        window.dispatchEvent(new Event("resize"));
      }
    });
  });
}

/**
 * Inicializa filtros rápidos
 */
function initFilters() {
  // Filtro de data
  var dateFilter = document.getElementById("date");
  if (dateFilter) {
    dateFilter.addEventListener("change", function () {
      document.getElementById("timeline-filter-form").submit();
    });
  }

  // Filtro de agendas
  var agendaFilters = document.querySelectorAll('input[name="agendas[]"]');
  if (agendaFilters) {
    agendaFilters.forEach(function (filter) {
      filter.addEventListener("change", function () {
        document.getElementById("timeline-filter-form").submit();
      });
    });
  }
}

/**
 * Atualiza estilos dos eventos no calendário baseado no status
 */
function updateEventStyles() {
  // Selecionar todos os eventos no calendário
  var eventElements = document.querySelectorAll(".fc-event");

  // Para cada evento, adicionar classe baseada no status
  eventElements.forEach(function (element) {
    var eventId = element.getAttribute("data-event-id");
    if (!eventId) return;

    // Encontrar o evento nos dados
    var event = window.timelineEvents.find(function (ev) {
      return ev.id == eventId;
    });

    if (event) {
      // Adicionar classe baseada no status
      element.classList.add("event-status-" + event.status);
    }
  });
}

// Variável global para URL base
var baseUrl = window.location.origin + "/agenda_ufpr";
