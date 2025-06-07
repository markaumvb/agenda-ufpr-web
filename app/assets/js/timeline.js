/**
 * Timeline JavaScript - Versão Melhorada
 * app/assets/js/timeline.js
 */

document.addEventListener("DOMContentLoaded", function () {
  // Configurações globais
  const config = {
    locale: "pt-br",
    timeZone: "America/Sao_Paulo",
    dateFormat: "dd/MM/yyyy",
    timeFormat: "HH:mm",
  };

  // Inicializar o calendário da timeline
  initializeTimelineCalendar();

  // Configurar filtros
  setupFilters();

  // Configurar modal de detalhes
  setupEventModal();

  /**
   * Inicializar o FullCalendar para timeline
   */
  function initializeTimelineCalendar() {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl) {
      return;
    }

    // Verificar se os eventos estão disponíveis
    const events = window.timelineEvents || [];

    // Preparar eventos para o FullCalendar com cores das agendas
    const calendarEvents = events.map((event) => {
      const agendaColor = event.agendaInfo?.color || "#3788d8";
      const statusColors = {
        pendente: "#ffc107",
        realizado: "#28a745",
        cancelado: "#dc3545",
        aguardando_aprovacao: "#17a2b8",
      };

      return {
        id: event.id,
        title: event.title,
        start: event.start,
        end: event.end,
        backgroundColor: statusColors[event.status] || agendaColor,
        borderColor: statusColors[event.status] || agendaColor,
        textColor: getContrastColor(statusColors[event.status] || agendaColor),
        extendedProps: {
          description: event.description,
          location: event.location,
          status: event.status,
          agendaInfo: event.agendaInfo,
          creatorName: event.creatorName,
        },
        classNames: [`event-status-${event.status}`],
      };
    });

    // Inicializar FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
      locale: config.locale,
      timeZone: config.timeZone,
      initialView: "timeGridDay",
      initialDate: window.selectedDate || new Date(),

      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "", // Removido para usar botões customizados
      },

      // Configurações visuais
      height: "auto",
      aspectRatio: 1.8,
      expandRows: true,
      nowIndicator: true,
      scrollTime: "08:00:00",
      slotMinTime: "06:00:00",
      slotMaxTime: "22:00:00",

      // Desabilitar interações
      editable: false,
      selectable: false,
      eventStartEditable: false,
      eventDurationEditable: false,

      // Eventos
      events: calendarEvents,

      // Formatação
      eventTimeFormat: {
        hour: "2-digit",
        minute: "2-digit",
        meridiem: false,
      },

      dayHeaderFormat: {
        weekday: "long",
        month: "long",
        day: "numeric",
      },

      // Callbacks
      eventClick: function (info) {
        info.jsEvent.preventDefault();
        showEventDetails(info.event);
      },

      eventDidMount: function (info) {
        // Adicionar tooltip
        info.el.title = `${info.event.title} - Clique para detalhes`;

        // Adicionar classes específicas
        if (info.event.extendedProps.status) {
          info.el.classList.add(
            `event-status-${info.event.extendedProps.status}`
          );
        }

        // Adicionar informações extras no evento
        const eventContent = info.el.querySelector(".fc-event-title");
        if (eventContent && info.event.extendedProps.location) {
          const locationEl = document.createElement("div");
          locationEl.className = "fc-event-location";
          locationEl.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${info.event.extendedProps.location}`;
          eventContent.appendChild(locationEl);
        }

        // Adicionar informação da agenda
        if (eventContent && info.event.extendedProps.agendaInfo) {
          const agendaEl = document.createElement("div");
          agendaEl.className = "fc-event-agenda";
          agendaEl.innerHTML = `
                        <span class="agenda-dot" style="background-color: ${info.event.extendedProps.agendaInfo.color}"></span>
                        ${info.event.extendedProps.agendaInfo.title}
                    `;
          eventContent.appendChild(agendaEl);
        }
      },

      noEventsContent: function () {
        return `
                    <div class="fc-no-events-message">
                        <div class="no-events-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h3>Nenhum compromisso encontrado</h3>
                        <p>Não há eventos públicos para esta data</p>
                    </div>
                `;
      },
    });

    // Renderizar o calendário
    calendar.render();

    // Expor globalmente para uso em outros scripts
    window.timelineCalendar = calendar;

    // Configurar botões de visualização
    setupViewButtons(calendar);

    // Mostrar/ocultar mensagem de eventos vazios
    toggleEmptyMessage(events.length === 0);
  }

  /**
   * Configurar botões de visualização
   */
  function setupViewButtons(calendar) {
    const viewButtons = document.querySelectorAll(".view-option");

    viewButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const view = this.getAttribute("data-view");

        if (view && calendar) {
          calendar.changeView(view);

          // Atualizar botão ativo
          viewButtons.forEach((btn) => btn.classList.remove("active"));
          this.classList.add("active");

          // Ajustar altura baseado na visualização
          if (view === "listDay") {
            calendar.setOption("height", "auto");
          } else {
            calendar.setOption("height", 600);
          }
        }
      });
    });
  }

  /**
   * Configurar filtros da timeline
   */
  function setupFilters() {
    const searchInput = document.getElementById("search-input");
    const dateInput = document.getElementById("date-picker");
    const agendaCheckboxes = document.querySelectorAll(".agenda-checkbox");
    const selectAllCheckbox = document.getElementById("select-all-agendas");

    // Configurar busca com debounce
    if (searchInput) {
      let searchTimeout;
      searchInput.addEventListener("input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          filterEvents();
        }, 500);
      });
    }

    // Configurar mudança de data
    if (dateInput) {
      dateInput.addEventListener("change", function () {
        const newDate = this.value;
        if (window.timelineCalendar) {
          window.timelineCalendar.gotoDate(newDate);
        }
        // Atualizar URL sem recarregar a página
        updateUrlDate(newDate);
      });
    }

    // Configurar checkboxes de agendas
    agendaCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", function () {
        filterEvents();
        updateSelectAllState();
      });
    });

    // Configurar "Selecionar Todas"
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener("change", function () {
        const isChecked = this.checked;
        agendaCheckboxes.forEach((checkbox) => {
          checkbox.checked = isChecked;
        });
        filterEvents();
      });
    }

    // Estado inicial do "Selecionar Todas"
    updateSelectAllState();
  }

  /**
   * Filtrar eventos baseado nos critérios selecionados
   */
  function filterEvents() {
    if (!window.timelineCalendar || !window.timelineEvents) return;

    const searchTerm =
      document.getElementById("search-input")?.value.toLowerCase().trim() || "";
    const selectedAgendas = Array.from(
      document.querySelectorAll(".agenda-checkbox:checked")
    ).map((cb) => parseInt(cb.value));

    // Filtrar eventos
    const filteredEvents = window.timelineEvents.filter((event) => {
      // Filtro por busca
      const searchMatch =
        !searchTerm ||
        event.title.toLowerCase().includes(searchTerm) ||
        (event.description &&
          event.description.toLowerCase().includes(searchTerm)) ||
        (event.location && event.location.toLowerCase().includes(searchTerm));

      // Filtro por agendas selecionadas
      const agendaMatch =
        selectedAgendas.length === 0 ||
        selectedAgendas.includes(event.agendaInfo?.id);

      return searchMatch && agendaMatch;
    });

    // Preparar eventos para o calendário
    const calendarEvents = filteredEvents.map((event) => {
      const agendaColor = event.agendaInfo?.color || "#3788d8";
      const statusColors = {
        pendente: "#ffc107",
        realizado: "#28a745",
        cancelado: "#dc3545",
        aguardando_aprovacao: "#17a2b8",
      };

      return {
        id: event.id,
        title: event.title,
        start: event.start,
        end: event.end,
        backgroundColor: statusColors[event.status] || agendaColor,
        borderColor: statusColors[event.status] || agendaColor,
        textColor: getContrastColor(statusColors[event.status] || agendaColor),
        extendedProps: {
          description: event.description,
          location: event.location,
          status: event.status,
          agendaInfo: event.agendaInfo,
          creatorName: event.creatorName,
        },
        classNames: [`event-status-${event.status}`],
      };
    });

    // Atualizar eventos no calendário
    window.timelineCalendar.removeAllEvents();
    window.timelineCalendar.addEventSource(calendarEvents);

    // Mostrar/ocultar mensagem de eventos vazios
    toggleEmptyMessage(filteredEvents.length === 0);
  }

  /**
   * Atualizar estado do checkbox "Selecionar Todas"
   */
  function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById("select-all-agendas");
    const agendaCheckboxes = document.querySelectorAll(".agenda-checkbox");

    if (!selectAllCheckbox || agendaCheckboxes.length === 0) return;

    const checkedCount = document.querySelectorAll(
      ".agenda-checkbox:checked"
    ).length;
    const totalCount = agendaCheckboxes.length;

    selectAllCheckbox.checked = checkedCount === totalCount;
    selectAllCheckbox.indeterminate =
      checkedCount > 0 && checkedCount < totalCount;
  }

  /**
   * Configurar modal de detalhes do evento
   */
  function setupEventModal() {
    const modal = document.getElementById("event-modal");
    if (!modal) return;

    // Fechar modal com ESC
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        closeModal();
      }
    });

    // Fechar modal clicando fora
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  }

  /**
   * Mostrar detalhes do evento
   */
  function showEventDetails(event) {
    const modal = document.getElementById("event-modal");
    const modalBody = document.getElementById("event-details");

    if (!modal || !modalBody) {
      console.warn("Modal não encontrado");
      return;
    }

    // Preparar dados do evento
    const startDate = new Date(event.start);
    const endDate = event.end ? new Date(event.end) : null;
    const agendaInfo = event.extendedProps.agendaInfo || {};

    // Formatar datas
    const dateFormatter = new Intl.DateTimeFormat("pt-BR", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    const timeFormatter = new Intl.DateTimeFormat("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    });

    const formattedDate = dateFormatter.format(startDate);
    const startTime = timeFormatter.format(startDate);
    const endTime = endDate ? timeFormatter.format(endDate) : "";

    // Status labels
    const statusLabels = {
      pendente: "Pendente",
      realizado: "Realizado",
      cancelado: "Cancelado",
      aguardando_aprovacao: "Aguardando Aprovação",
    };

    const statusLabel =
      statusLabels[event.extendedProps.status] || event.extendedProps.status;
    const statusClass = `status-${event.extendedProps.status}`;

    // Construir HTML do modal
    modalBody.innerHTML = `
            <div class="event-details-header" style="border-left-color: ${
              agendaInfo.color || "#3788d8"
            };">
                <div class="event-title-row">
                    <h4>${event.title}</h4>
                    <span class="badge ${statusClass} status-badge">${statusLabel}</span>
                </div>
            </div>
            
            <div class="event-details-body">
                <div class="detail-section">
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt detail-icon"></i>
                        <div class="detail-content">
                            <strong>Data</strong>
                            ${formattedDate}
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <i class="fas fa-clock detail-icon"></i>
                        <div class="detail-content">
                            <strong>Horário</strong>
                            ${startTime}${endTime ? ` às ${endTime}` : ""}
                        </div>
                    </div>
                    
                    ${
                      event.extendedProps.location
                        ? `
                    <div class="detail-item">
                        <i class="fas fa-map-marker-alt detail-icon"></i>
                        <div class="detail-content">
                            <strong>Local</strong>
                            ${event.extendedProps.location}
                        </div>
                    </div>
                    `
                        : ""
                    }
                    
                    <div class="detail-item">
                        <i class="fas fa-folder detail-icon"></i>
                        <div class="detail-content">
                            <strong>Agenda</strong>
                            <div class="agenda-info">
                                <span class="agenda-color-indicator" style="background-color: ${
                                  agendaInfo.color || "#3788d8"
                                }"></span>
                                <span class="agenda-name">${
                                  agendaInfo.title || "Agenda"
                                }</span>
                                <span class="agenda-owner">por ${
                                  agendaInfo.owner || "Desconhecido"
                                }</span>
                            </div>
                        </div>
                    </div>
                    
                    ${
                      event.extendedProps.creatorName
                        ? `
                    <div class="detail-item">
                        <i class="fas fa-user detail-icon"></i>
                        <div class="detail-content">
                            <strong>Solicitado por</strong>
                            ${event.extendedProps.creatorName}
                        </div>
                    </div>
                    `
                        : ""
                    }
                </div>
                
                ${
                  event.extendedProps.description
                    ? `
                <div class="detail-section description-section">
                    <div class="detail-item">
                        <i class="fas fa-align-left detail-icon"></i>
                        <div class="detail-content">
                            <strong>Descrição</strong>
                            <div class="description-text">${event.extendedProps.description.replace(
                              /\n/g,
                              "<br>"
                            )}</div>
                        </div>
                    </div>
                </div>
                `
                    : ""
                }
            </div>
        `;

    // Mostrar modal
    modal.classList.add("show");
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  /**
   * Fechar modal
   */
  function closeModal() {
    const modal = document.getElementById("event-modal");
    if (modal) {
      modal.classList.remove("show");
      modal.style.display = "none";
      document.body.style.overflow = "";
    }
  }

  // Expor função globalmente
  window.closeModal = closeModal;

  /**
   * Mostrar/ocultar mensagem de eventos vazios
   */
  function toggleEmptyMessage(show) {
    const emptyMessage = document.querySelector(".empty-timeline-message");
    if (emptyMessage) {
      emptyMessage.style.display = show ? "block" : "none";
    }
  }

  /**
   * Atualizar data na URL sem recarregar
   */
  function updateUrlDate(date) {
    const url = new URL(window.location);
    url.searchParams.set("date", date);
    window.history.replaceState({}, "", url);
    window.selectedDate = date;
  }

  /**
   * Calcular cor de contraste para texto
   */
  function getContrastColor(hexColor) {
    // Remover # se presente
    const color = hexColor.replace("#", "");

    // Converter para RGB
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);

    // Calcular luminância
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    // Retornar cor de contraste
    return luminance > 0.5 ? "#000000" : "#ffffff";
  }
});
