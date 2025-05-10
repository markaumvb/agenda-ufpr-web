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
        title: compromisso.title || "Sem título", // Garantir que sempre tenha um título
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
    navLinks: true, // Permite clicar nos nomes de dias/semanas para navegar
    editable: canEdit,
    selectable: canEdit,
    dayMaxEvents: false, // Importante! Permite que os eventos sejam exibidos completamente
    eventMaxStack: 6, // Limitar número de eventos visíveis por dia antes do link "mais"
    height: "auto",
    events: events,

    // Estas opções são cruciais para exibir adequadamente os eventos
    eventTimeFormat: {
      hour: "2-digit",
      minute: "2-digit",
      meridiem: false,
    },

    // Personalização de eventos - esta é a chave para garantir que títulos sejam exibidos
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

    // Callback quando um evento é clicado
    eventClick: function (info) {
      showEventDetails(info.event);

      // Evitar redirecionamento padrão
      info.jsEvent.preventDefault();
    },

    // Callback quando um espaço do calendário é clicado (para criar novo evento)
    dateClick: function (info) {
      if (canEdit) {
        const date = info.dateStr;
        window.location.href = `${baseUrl}/compromissos/new?agenda_id=${agendaId}&date=${date}`;
      }
    },

    // Callback quando um evento é arrastado e reposicionado (se edição estiver ativada)
    eventDrop: function (info) {
      if (canEdit && confirm("Confirma alterar a data do compromisso?")) {
        updateEventDate(info.event.id, info.event.start, info.event.end);
      } else {
        info.revert();
      }
    },

    // Callback quando um evento é redimensionado (se edição estiver ativada)
    eventResize: function (info) {
      if (canEdit && confirm("Confirma alterar a duração do compromisso?")) {
        updateEventDate(info.event.id, info.event.start, info.event.end);
      } else {
        info.revert();
      }
    },

    // Garantir que o título seja sempre exibido
    displayEventTime: false, // Ocultar o horário do evento para dar mais espaço ao título

    // Após o calendário ter sido renderizado, adicionar classes às células
    viewDidMount: function () {
      // Adicionar classes para destaque em dias com eventos
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

  // Após a renderização, aplicar destaque ao texto dos eventos
  setTimeout(function () {
    document.querySelectorAll(".fc-event-title").forEach(function (el) {
      el.style.whiteSpace = "nowrap";
      el.style.overflow = "hidden";
      el.style.textOverflow = "ellipsis";
      el.style.fontWeight = "bold";
      el.style.display = "block";
      el.style.fontSize = "0.9em";
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

  // Função para exibir detalhes de um evento em um modal ou tooltip
  function showEventDetails(event) {
    // Obter ID do evento
    const eventId = event.id;

    // Buscar o card correspondente
    const eventCard = document.querySelector(
      `.event-card[data-id="${eventId}"]`
    );

    if (!eventCard) return;

    // Abrir modal com detalhes
    const modal = document.getElementById("event-modal");
    if (modal) {
      // Garantir que o modal esteja escondido inicialmente
      modal.style.display = "none";
      const modalBody = document.getElementById("event-modal-body");

      // Limpar conteúdo anterior
      modalBody.innerHTML = "";

      // Clonar o conteúdo do card para o modal
      const eventDetails = eventCard.cloneNode(true);
      modalBody.appendChild(eventDetails);

      // Exibir modal
      modal.style.display = "block";

      // Configurar botão de fechar
      const closeBtn = document.querySelector(".event-modal-close");
      if (closeBtn) {
        closeBtn.onclick = function () {
          modal.style.display = "none";
        };
      }

      // Fechar quando clicar fora do modal
      window.onclick = function (event) {
        if (event.target == modal) {
          modal.style.display = "none";
        }
      };
    }
  }

  // Função para atualizar a data de um evento
  function updateEventDate(eventId, startDate, endDate) {
    // Formatar as datas
    const start = startDate ? startDate.toISOString() : "";
    const end = endDate ? endDate.toISOString() : "";

    // Criar FormData
    const formData = new FormData();
    formData.append("id", eventId);
    formData.append("start", start);
    formData.append("end", end);

    // Enviar requisição
    fetch(`${baseUrl}/compromissos/update-date`, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log("Evento atualizado com sucesso");
        } else {
          console.error("Erro ao atualizar evento:", data.message);
          alert("Erro ao atualizar o compromisso: " + data.message);
        }
      })
      .catch((error) => {
        console.error("Erro na requisição:", error);
        alert("Erro ao comunicar com o servidor");
      });
  }

  // Função para filtragem do calendário
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
      });
    }
  }
});
