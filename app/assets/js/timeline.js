document.addEventListener("DOMContentLoaded", function () {
  initTimelineCalendar();
  setupModalClose();
});

function initTimelineCalendar() {
  const calendarEl = document.getElementById("calendar");
  if (!calendarEl) {
    console.error("Calendar element not found!");
    return;
  }

  // Get events or initialize empty array if not available
  const events = window.timelineEvents || [];
  const initialDate = window.selectedDate || new Date();

  // Process events to use agenda colors
  const processedEvents = events.map((event) => {
    const agendaColor = event.agendaInfo?.color || "#3788d8";

    return {
      ...event,
      backgroundColor: agendaColor,
      borderColor: agendaColor,
      textColor: getContrastTextColor(agendaColor),
      extendedProps: {
        ...event.extendedProps,
        agendaInfo: event.agendaInfo,
      },
    };
  });

  // Initialize FullCalendar with enhanced configuration
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "pt-br",
    initialView: "timeGridDay",
    initialDate: initialDate,
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "", // We use custom buttons instead
    },

    datesSet: function (info) {
      // Atualizar o campo de data quando navegar
      const dateInput = document.getElementById("date-picker");
      if (dateInput) {
        const newDate = info.start.toISOString().split("T")[0];
        if (dateInput.value !== newDate) {
          dateInput.value = newDate;
          // Auto-submit do formulário
          dateInput.closest("form").submit();
        }
      }
    },

    // Time settings for better visualization
    slotMinTime: "06:00:00",
    slotMaxTime: "22:00:00",
    slotDuration: "00:30:00", // 30-minute slots
    slotLabelInterval: "01:00:00", // Show hour labels
    allDaySlot: false,
    nowIndicator: true,
    scrollTime: "08:00:00", // Start scroll at 8 AM

    // Layout and appearance
    height: "auto",
    aspectRatio: 1.8,
    expandRows: true,

    // Event settings
    events: processedEvents,
    eventDisplay: "block",
    eventTimeFormat: {
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    },

    // Enhanced event rendering
    eventContent: function (arg) {
      const event = arg.event;
      const agendaInfo = event.extendedProps.agendaInfo;
      const status = event.extendedProps.status;

      // Create custom HTML for better event display
      const eventHtml = `
        <div class="fc-event-main-frame">
          <div class="fc-event-title-container">
            <div class="fc-event-title fc-sticky">${event.title}</div>
          </div>
          <div class="fc-event-details">
            ${
              event.extendedProps.location
                ? `<div class="fc-event-location">
                <i class="fas fa-map-marker-alt"></i> ${event.extendedProps.location}
              </div>`
                : ""
            }
            <div class="fc-event-agenda">
              <span class="agenda-dot" style="background-color: ${
                agendaInfo?.color || "#3788d8"
              }"></span>
              ${agendaInfo?.title || "Agenda"}
            </div>
            <div class="fc-event-status status-${status}">
              ${getStatusLabel(status)}
            </div>
          </div>
        </div>
      `;

      return { html: eventHtml };
    },

    // Event interaction
    eventClick: function (info) {
      showEventDetails(info.event);
    },

    // Enhanced event mounting with status styling
    eventDidMount: function (info) {
      const event = info.event;
      const status = event.extendedProps.status;
      const agendaColor = event.extendedProps.agendaInfo?.color || "#3788d8";

      // Add status class
      if (status) {
        info.el.setAttribute("data-status", status);
        info.el.classList.add(`event-status-${status}`);
      }

      // Apply agenda color as base color
      info.el.style.backgroundColor = agendaColor;
      info.el.style.borderColor = agendaColor;
      info.el.style.borderLeftWidth = "4px";
      info.el.style.borderLeftStyle = "solid";

      // Apply status-specific styling
      applyStatusStyling(info.el, status, agendaColor);

      // Enhanced hover effect
      info.el.addEventListener("mouseenter", function () {
        this.style.transform = "scale(1.02)";
        this.style.boxShadow = "0 4px 12px rgba(0,0,0,0.15)";
        this.style.zIndex = "10";
      });

      info.el.addEventListener("mouseleave", function () {
        this.style.transform = "scale(1)";
        this.style.boxShadow = "0 2px 6px rgba(0,0,0,0.1)";
        this.style.zIndex = "auto";
      });
    },

    // Custom no events content
    noEventsContent: function () {
      return `
        <div class="fc-no-events-message">
          <div class="no-events-icon">
            <i class="fas fa-calendar-times"></i>
          </div>
          <h3>Nenhum compromisso</h3>
          <p>Não há compromissos para esta data</p>
        </div>
      `;
    },

    // Loading state
    loading: function (isLoading) {
      if (isLoading) {
        calendarEl.classList.add("fc-loading");
      } else {
        calendarEl.classList.remove("fc-loading");
      }
    },

    // Ensure calendar is always visible
    datesSet: function () {
      // Force calendar to always show, even without events
      const calendarContainer = document.querySelector(".fc-view-harness");
      if (calendarContainer) {
        calendarContainer.style.minHeight = "500px";
        calendarContainer.style.display = "block";
      }

      // Update view button states
      updateViewButtonStates();
    },
  });

  // Render the calendar
  calendar.render();

  // Store calendar reference globally
  window.timelineCalendar = calendar;

  // Setup view buttons with improved feedback
  setupViewButtons(calendar);

  // Setup auto-refresh functionality
  setupAutoRefresh(calendar);
}

/**
 * Apply status-specific styling to events
 */
function applyStatusStyling(element, status, baseColor) {
  switch (status) {
    case "realizado":
      element.style.opacity = "0.75";
      element.style.background = `linear-gradient(135deg, ${baseColor} 0%, ${adjustColorBrightness(
        baseColor,
        -20
      )} 100%)`;
      break;

    case "cancelado":
      element.style.opacity = "0.6";
      element.style.textDecoration = "line-through";
      element.style.background = `repeating-linear-gradient(45deg, ${baseColor}, ${baseColor} 10px, ${adjustColorBrightness(
        baseColor,
        -30
      )} 10px, ${adjustColorBrightness(baseColor, -30)} 20px)`;
      break;

    case "aguardando_aprovacao":
      element.style.background = `linear-gradient(90deg, ${baseColor} 0%, ${adjustColorBrightness(
        baseColor,
        20
      )} 50%, ${baseColor} 100%)`;
      element.style.animation = "pulse-approval 2s infinite";
      break;

    case "pendente":
    default:
      element.style.background = `linear-gradient(135deg, ${baseColor} 0%, ${adjustColorBrightness(
        baseColor,
        10
      )} 100%)`;
      break;
  }
}

/**
 * Adjust color brightness
 */
function adjustColorBrightness(color, amount) {
  const usePound = color[0] === "#";
  const col = usePound ? color.slice(1) : color;
  const num = parseInt(col, 16);
  let r = (num >> 16) + amount;
  let g = ((num >> 8) & 0x00ff) + amount;
  let b = (num & 0x0000ff) + amount;
  r = r > 255 ? 255 : r < 0 ? 0 : r;
  g = g > 255 ? 255 : g < 0 ? 0 : g;
  b = b > 255 ? 255 : b < 0 ? 0 : b;
  return (
    (usePound ? "#" : "") +
    ((r << 16) | (g << 8) | b).toString(16).padStart(6, "0")
  );
}

/**
 * Get contrast text color based on background
 */
function getContrastTextColor(hexColor) {
  const r = parseInt(hexColor.slice(1, 3), 16);
  const g = parseInt(hexColor.slice(3, 5), 16);
  const b = parseInt(hexColor.slice(5, 7), 16);
  const brightness = (r * 299 + g * 587 + b * 114) / 1000;
  return brightness > 128 ? "#000000" : "#FFFFFF";
}

function setupModalClose() {
  const modal = document.getElementById("event-modal");
  const closeBtn = document.querySelector(
    ".btn-close, .modal .close, [data-bs-dismiss='modal']"
  );

  // Close button click
  if (closeBtn) {
    closeBtn.addEventListener("click", function () {
      closeModal();
    });
  }

  // Click outside modal
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  }

  // ESC key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && modal && modal.classList.contains("show")) {
      closeModal();
    }
  });
}

function closeModal() {
  const modal = document.getElementById("event-modal");
  if (modal) {
    modal.classList.remove("show");
    modal.style.display = "none";
  }
}

function setupViewButtons(calendar) {
  document.querySelectorAll(".view-option").forEach((button) => {
    button.addEventListener("click", function () {
      const view = this.getAttribute("data-view");

      if (view && calendar) {
        calendar.changeView(view);

        // Update active states with animation
        document.querySelectorAll(".view-option").forEach((btn) => {
          btn.classList.remove("active");
          btn.style.transform = "scale(1)";
        });

        this.classList.add("active");
        this.style.transform = "scale(1.05)";

        // Reset transform after animation
        setTimeout(() => {
          this.style.transform = "scale(1)";
        }, 150);

        // Update calendar settings based on view
        updateCalendarForView(calendar, view);
      }
    });
  });
}

/**
 * Update calendar settings for different views
 */
function updateCalendarForView(calendar, view) {
  switch (view) {
    case "timeGridDay":
      calendar.setOption("slotMinTime", "06:00:00");
      calendar.setOption("slotMaxTime", "22:00:00");
      calendar.setOption("scrollTime", "08:00:00");
      break;
    case "listDay":
      // List view doesn't need time restrictions
      break;
  }
}

/**
 * Update view button states
 */
function updateViewButtonStates() {
  const currentView = window.timelineCalendar?.view?.type;
  if (currentView) {
    document.querySelectorAll(".view-option").forEach((btn) => {
      if (btn.getAttribute("data-view") === currentView) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });
  }
}

/**
 * Setup auto-refresh functionality
 */
function setupAutoRefresh(calendar) {
  // Auto-refresh every 5 minutes to get updated data
  setInterval(() => {
    if (document.hasFocus()) {
      calendar.refetchEvents();
    }
  }, 300000); // 5 minutes
}

/**
 * Enhanced event details modal
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
  const status = event.extendedProps.status;
  const statusLabel = getStatusLabel(status);

  let content = `
    <div class="event-details-header" style="border-left-color: ${agendaColor}; background: linear-gradient(90deg, ${agendaColor}15 0%, transparent 100%);">
      <div class="event-title-row">
        <h4>${event.title}</h4>
        <span class="badge badge-${status} status-badge">${statusLabel}</span>
      </div>
    </div>
    <div class="event-details-body">
      <div class="detail-section">
        <div class="detail-item">
          <i class="fas fa-clock detail-icon"></i>
          <div class="detail-content">
            <strong>Horário:</strong> 
            ${startTime} ${endTime ? `até ${endTime}` : ""}
          </div>
        </div>
      </div>`;

  if (event.extendedProps.agendaInfo) {
    content += `
      <div class="detail-section">
        <div class="detail-item">
          <i class="fas fa-calendar detail-icon"></i>
          <div class="detail-content">
            <strong>Agenda:</strong>
            <div class="agenda-info">
              <span class="agenda-color-indicator" style="background-color: ${agendaColor}"></span>
              <span class="agenda-name">${
                event.extendedProps.agendaInfo.title
              }</span>
              <span class="agenda-owner">${
                event.extendedProps.agendaInfo.owner || "Desconhecido"
              }</span>
            </div>
          </div>
        </div>
      </div>`;
  }

  if (event.extendedProps.location) {
    content += `
      <div class="detail-section">
        <div class="detail-item">
          <i class="fas fa-map-marker-alt detail-icon"></i>
          <div class="detail-content">
            <strong>Local:</strong> ${event.extendedProps.location}
          </div>
        </div>
      </div>`;
  }

  if (event.extendedProps.description) {
    content += `
      <div class="detail-section description-section">
        <div class="detail-item">
          <i class="fas fa-align-left detail-icon"></i>
          <div class="detail-content">
            <strong>Descrição:</strong>
            <div class="description-text">${event.extendedProps.description.replace(
              /\n/g,
              "<br>"
            )}</div>
          </div>
        </div>
      </div>`;
  }

  content += `
    <div class="detail-section actions-section">
      <a href="${PUBLIC_URL}/compromissos?agenda_id=${event.extendedProps.agendaInfo?.id}" class="btn btn-primary btn-action">
        <i class="fas fa-calendar-alt"></i> Ver na Agenda
      </a>
    </div>
  `;

  // Insert content and show modal
  detailsContainer.innerHTML = content;

  // Add show class for animation
  modal.classList.add("show");
  modal.style.display = "block";

  // Focus trap for accessibility
  modal.focus();
}

/**
 * Get localized status label
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
