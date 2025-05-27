document.addEventListener("DOMContentLoaded", function () {
  // Obter o elemento do calendário
  const calendarEl = document.getElementById("calendar");
  if (!calendarEl) {
    console.error("Elemento do calendário não encontrado!");
    return;
  }

  // Obter o ID da agenda
  const agendaId = document.querySelector(".calendar-container")?.dataset
    .agendaId;
  const canEdit =
    document.querySelector(".header-actions a.btn-primary") !== null;
  const baseUrl =
    window.location.origin + (window.PUBLIC_URL || "/agenda_ufpr");

  // Cores para os diferentes status de compromissos
  const statusColors = {
    pendente: "#ffc107",
    realizado: "#28a745",
    cancelado: "#dc3545",
    aguardando_aprovacao: "#17a2b8",
  };

  // Preparar eventos para o calendário
  let events = [];

  // Se temos compromissos disponíveis na variável global, usá-los
  if (window.allCompromissos && Array.isArray(window.allCompromissos)) {
    window.allCompromissos.forEach((compromisso) => {
      const event = {
        id: compromisso.id,
        title: compromisso.title || "Sem título",
        start: compromisso.start_datetime,
        end: compromisso.end_datetime,
        allDay: false,
        extendedProps: {
          status: compromisso.status,
          description: compromisso.description || "",
          location: compromisso.location || "",
        },
        backgroundColor: statusColors[compromisso.status] || "#3788d8",
        borderColor: statusColors[compromisso.status] || "#3788d8",
        textColor: compromisso.status === "pendente" ? "#000" : "#fff",
        classNames: ["event-status-" + compromisso.status],
        // IMPORTANTE: DESABILITAR EDIÇÃO COMPLETAMENTE
        editable: false,
        startEditable: false,
        durationEditable: false,
      };

      // Para eventos cancelados, adicionar estilo visual
      if (compromisso.status === "cancelado") {
        event.textDecoration = "line-through";
        event.backgroundColor = "rgba(220, 53, 69, 0.6)";
        event.borderColor = "#dc3545";
      }

      events.push(event);
    });
  } else {
    // Fallback: extrair dados dos cards HTML se a variável global não estiver disponível
    document.querySelectorAll(".event-card").forEach((card) => {
      const id = card.dataset.id;
      const title =
        card.querySelector(".event-title")?.textContent.trim() || "Sem título";
      const status = card.dataset.status || "pendente";
      const dateStart = card.dataset.date;

      // Verificar se temos todos os dados necessários
      if (!id || !dateStart) return;

      const description = card.querySelector(".event-description")
        ? card.querySelector(".event-description").textContent.trim()
        : "";

      const location = card.querySelector(".event-location")
        ? card.querySelector(".event-location").textContent.trim()
        : "";

      // Extrair a data e hora de início e fim do texto
      const startDateStr = dateStart;
      const allDay = true; // Definir como true por padrão, a menos que tenhamos hora específica

      // Criar o evento com propriedades apropriadas
      const event = {
        id: id,
        title: title,
        start: startDateStr,
        allDay: allDay,
        extendedProps: {
          status: status,
          description: description,
          location: location,
        },
        backgroundColor: statusColors[status] || "#3788d8",
        borderColor: statusColors[status] || "#3788d8",
        textColor: status === "pendente" ? "#000" : "#fff",
        classNames: ["event-status-" + status],
        // IMPORTANTE: DESABILITAR EDIÇÃO COMPLETAMENTE
        editable: false,
        startEditable: false,
        durationEditable: false,
      };

      // Para eventos cancelados, adicionar estilo visual
      if (status === "cancelado") {
        event.textDecoration = "line-through";
        event.backgroundColor = "rgba(220, 53, 69, 0.6)";
        event.borderColor = "#dc3545";
      }

      events.push(event);
    });
  }

  // Inicializar o FullCalendar com opções otimizadas
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "pt-br",
    initialView: "dayGridMonth",
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "", // Removido pois temos botões personalizados para visualizações
    },
    weekNumbers: false,
    navLinks: true,

    // DESABILITAR COMPLETAMENTE A EDIÇÃO E ARRASTAR
    editable: false,
    selectable: canEdit,
    eventStartEditable: false,
    eventDurationEditable: false,
    eventResizableFromStart: false,
    eventDragStart: function () {
      return false; // Bloquear início do arrasto
    },

    dayMaxEvents: false,
    eventMaxStack: 6,
    height: "auto",
    events: events,

    // Formatação de eventos
    eventTimeFormat: {
      hour: "2-digit",
      minute: "2-digit",
      meridiem: false,
    },

    // Personalização de eventos
    eventContent: function (info) {
      const status = info.event.extendedProps.status || "pendente";

      // Para eventos cancelados, aplicar estilo especial
      if (status === "cancelado") {
        const wrapper = document.createElement("div");
        wrapper.classList.add(
          "fc-event-main-wrapper",
          "event-status-" + status
        );

        const title = document.createElement("div");
        title.classList.add("fc-event-title-container");
        title.innerHTML =
          '<div class="fc-event-title fc-sticky" style="text-decoration: line-through;">' +
          info.event.title +
          "</div>";

        wrapper.appendChild(title);
        return { domNodes: [wrapper] };
      }

      // Para eventos normais, garantir que o título seja exibido
      const wrapper = document.createElement("div");
      wrapper.classList.add("fc-event-main-wrapper", "event-status-" + status);

      const title = document.createElement("div");
      title.classList.add("fc-event-title-container");
      title.innerHTML =
        '<div class="fc-event-title fc-sticky">' + info.event.title + "</div>";

      wrapper.appendChild(title);
      return { domNodes: [wrapper] };
    },

    // CALLBACK QUANDO UM EVENTO É CLICADO - MOSTRAR MODAL
    eventClick: function (info) {
      // Prevenir comportamento padrão
      info.jsEvent.preventDefault();

      // Mostrar modal com detalhes do evento
      showEventModal(info.event);
    },

    // Callback quando um espaço do calendário é clicado (para criar novo evento)
    dateClick: function (info) {
      if (canEdit) {
        const clickedDate = info.date;
        const now = new Date();

        // Verificar se a data está no passado
        if (clickedDate < now) {
          alert("Não é possível criar compromissos em datas passadas.");
          return;
        }

        const date = info.dateStr;
        window.location.href = `${baseUrl}/compromissos/new?agenda_id=${agendaId}&date=${date}`;
      }
    },

    // REMOVER COMPLETAMENTE OS EVENTOS DE DRAG E DROP
    // (não incluir eventDrop e eventResize)

    // Garantir que o título seja sempre exibido
    displayEventTime: false,

    // Após o calendário ter sido renderizado, adicionar classes às células
    viewDidMount: function () {
      setTimeout(function () {
        document
          .querySelectorAll(".fc-daygrid-day-events")
          .forEach(function (el) {
            if (el.children.length > 0) {
              el.closest(".fc-daygrid-day").classList.add("has-events");
            }
          });
      }, 200);
    },
  });

  // Expor o calendário globalmente para uso em outros scripts
  window.calendar = calendar;

  // Renderizar o calendário
  calendar.render();

  // Após a renderização, aplicar destaque ao texto dos eventos e remover cursors de drag
  setTimeout(function () {
    document.querySelectorAll(".fc-event-title").forEach(function (el) {
      el.style.whiteSpace = "nowrap";
      el.style.overflow = "hidden";
      el.style.textOverflow = "ellipsis";
      el.style.fontWeight = "bold";
      el.style.display = "block";
      el.style.fontSize = "0.9em";
    });

    // IMPORTANTE: Garantir que nenhum evento seja arrastável
    document.querySelectorAll(".fc-event").forEach(function (eventEl) {
      eventEl.style.cursor = "pointer"; // Apenas pointer, não move
      eventEl.title = "Clique para ver detalhes";

      // Remover qualquer handle de redimensionamento
      const resizers = eventEl.querySelectorAll(".fc-event-resizer");
      resizers.forEach((resizer) => resizer.remove());
    });
  }, 500);

  // Configurar botões de visualização
  document.querySelectorAll(".view-option").forEach((button) => {
    button.addEventListener("click", function () {
      const view = this.dataset.view;
      calendar.changeView(view);

      // Atualizar botão ativo
      document.querySelectorAll(".view-option").forEach((btn) => {
        btn.classList.remove("active");
      });
      this.classList.add("active");
    });
  });

  // Ativar o primeiro botão (visualização de mês) por padrão
  const defaultViewButton = document.querySelector(
    '.view-option[data-view="dayGridMonth"]'
  );
  if (defaultViewButton) {
    defaultViewButton.classList.add("active");
  }

  // Configurar filtros para atualizar o calendário
  setupFilters(calendar, events);

  // Função para filtração do calendário
  function setupFilters(calendar, allEvents) {
    // Obter elementos de filtro
    const filterStatus = document.getElementById("filter-status");
    const filterMonth = document.getElementById("filter-month");
    const filterSearch = document.getElementById("filter-search");
    const clearFilterBtn = document.getElementById("clear-filters");

    if (!filterStatus && !filterMonth && !filterSearch) return;

    // Aplicar filtros
    function applyFilters() {
      const statusValue = filterStatus ? filterStatus.value : "all";
      const monthValue = filterMonth ? filterMonth.value : "all";
      const searchValue = filterSearch ? filterSearch.value.toLowerCase() : "";

      // Filtrar os eventos
      const filteredEvents = allEvents.filter((event) => {
        // Status
        const statusMatch =
          statusValue === "all" || event.extendedProps.status === statusValue;

        // Mês
        const eventDate = new Date(event.start);
        const eventMonth = (eventDate.getMonth() + 1).toString();
        const monthMatch = monthValue === "all" || eventMonth === monthValue;

        // Texto
        const searchableText = (
          event.title +
          " " +
          (event.extendedProps.description || "") +
          " " +
          (event.extendedProps.location || "")
        ).toLowerCase();
        const searchMatch =
          !searchValue || searchableText.includes(searchValue);

        return statusMatch && monthMatch && searchMatch;
      });

      // Atualizar eventos no calendário
      calendar.removeAllEvents();
      calendar.addEventSource(filteredEvents);

      // Após adicionar os eventos filtrados, garantir que não sejam arrastáveis
      setTimeout(function () {
        document.querySelectorAll(".fc-event").forEach(function (eventEl) {
          eventEl.style.cursor = "pointer";
          eventEl.title = "Clique para ver detalhes";

          // Remover handles de redimensionamento
          const resizers = eventEl.querySelectorAll(".fc-event-resizer");
          resizers.forEach((resizer) => resizer.remove());
        });
      }, 200);
    }

    // Adicionar listeners
    if (filterStatus) filterStatus.addEventListener("change", applyFilters);
    if (filterMonth) filterMonth.addEventListener("change", applyFilters);
    if (filterSearch) filterSearch.addEventListener("input", applyFilters);

    // Configurar botão de limpar
    if (clearFilterBtn) {
      clearFilterBtn.addEventListener("click", function () {
        if (filterStatus) filterStatus.value = "all";
        if (filterMonth) filterMonth.value = "all";
        if (filterSearch) filterSearch.value = "";

        calendar.removeAllEvents();
        calendar.addEventSource(allEvents);

        // Reaplicar estilos após limpar filtros
        setTimeout(function () {
          document.querySelectorAll(".fc-event").forEach(function (eventEl) {
            eventEl.style.cursor = "pointer";
            eventEl.title = "Clique para ver detalhes";

            // Remover handles de redimensionamento
            const resizers = eventEl.querySelectorAll(".fc-event-resizer");
            resizers.forEach((resizer) => resizer.remove());
          });
        }, 200);
      });
    }
  }
});

/**
 * FUNÇÃO PARA MOSTRAR MODAL DE DETALHES DO EVENTO
 */
function showEventModal(event) {
  // Verificar se o modal já existe, se não, criar
  let modal = document.getElementById("event-details-modal");

  if (!modal) {
    modal = createEventModal();
    document.body.appendChild(modal);
  }

  // Preencher o conteúdo do modal
  populateEventModal(modal, event);

  // Mostrar o modal usando Bootstrap
  if (typeof $ !== "undefined" && $.fn.modal) {
    $(modal).modal("show");
  } else {
    // Fallback se jQuery/Bootstrap não estiver disponível
    modal.style.display = "block";
    modal.classList.add("show");
  }
}

/**
 * CRIAR O MODAL DE DETALHES DO EVENTO
 */
function createEventModal() {
  const modal = document.createElement("div");
  modal.className = "modal fade";
  modal.id = "event-details-modal";
  modal.tabIndex = -1;
  modal.setAttribute("role", "dialog");
  modal.setAttribute("aria-labelledby", "eventModalLabel");
  modal.setAttribute("aria-hidden", "true");

  modal.innerHTML = `
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="eventModalLabel">Detalhes do Compromisso</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar" onclick="closeEventModal()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="event-modal-body">
          <!-- Conteúdo será inserido aqui -->
        </div>
        <div class="modal-footer" id="event-modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closeEventModal()">
            <i class="fas fa-times"></i> Fechar
          </button>
        </div>
      </div>
    </div>
  `;

  // Adicionar evento de clique no overlay para fechar
  modal.addEventListener("click", function (e) {
    if (e.target === modal) {
      closeEventModal();
    }
  });

  return modal;
}

/**
 * PREENCHER O MODAL COM OS DADOS DO EVENTO
 */
function populateEventModal(modal, event) {
  const modalBody = modal.querySelector("#event-modal-body");
  const modalTitle = modal.querySelector("#eventModalLabel");

  // Atualizar título do modal
  modalTitle.textContent = event.title;

  // Formatar datas
  const startDate = event.start ? event.start.toLocaleDateString("pt-BR") : "";
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

  // Obter status e formatar
  const status = event.extendedProps.status || "pendente";
  const statusLabels = {
    pendente: "Pendente",
    realizado: "Realizado",
    cancelado: "Cancelado",
    aguardando_aprovacao: "Aguardando Aprovação",
  };
  const statusLabel = statusLabels[status] || status;

  // Criar conteúdo do modal
  let content = `
    <div class="event-details">
      <div class="row">
        <div class="col-md-8">
          <h4 class="event-title">${event.title}</h4>
          <div class="event-info mt-3">
            <p><i class="fas fa-calendar-alt text-primary"></i> <strong>Data:</strong> ${startDate}</p>
            <p><i class="fas fa-clock text-primary"></i> <strong>Horário:</strong> ${startTime}${
    endTime ? ` às ${endTime}` : ""
  }</p>
            ${
              event.extendedProps.location
                ? `<p><i class="fas fa-map-marker-alt text-primary"></i> <strong>Local:</strong> ${event.extendedProps.location}</p>`
                : ""
            }
          </div>
        </div>
        <div class="col-md-4 text-right">
          <span class="badge badge-${status} badge-lg">${statusLabel}</span>
        </div>
      </div>
  `;

  // Adicionar descrição se existir
  if (event.extendedProps.description) {
    content += `
      <div class="mt-4">
        <h6><i class="fas fa-align-left text-primary"></i> Descrição:</h6>
        <div class="description-content bg-light p-3 rounded">
          ${event.extendedProps.description.replace(/\n/g, "<br>")}
        </div>
      </div>
    `;
  }

  content += "</div>";
  modalBody.innerHTML = content;
}

/**
 * FECHAR O MODAL
 */
function closeEventModal() {
  const modal = document.getElementById("event-details-modal");
  if (modal) {
    if (typeof $ !== "undefined" && $.fn.modal) {
      $(modal).modal("hide");
    } else {
      modal.style.display = "none";
      modal.classList.remove("show");
    }
  }
}
