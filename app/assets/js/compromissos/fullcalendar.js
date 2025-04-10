/**
 * Script para gerenciar o FullCalendar no sistema de agendas
 * arquivo: app/assets/js/compromissos/fullcalendar.js
 */

document.addEventListener("DOMContentLoaded", function () {
  // Obter o container do calendário
  const calendarEl = document.getElementById("calendar");

  if (!calendarEl) return;

  // Obter o ID da agenda
  const agendaId = document.querySelector(".calendar-container").dataset
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
  const events = [];
  document.querySelectorAll(".event-card").forEach((card) => {
    const id = card.dataset.id;
    const title = card.querySelector(".event-title").textContent.trim();
    const status = card.dataset.status;
    const dateStart = card
      .querySelector(".event-datetime .event-date")
      .textContent.trim();
    const timeElement = card.querySelector(".event-datetime .event-time");
    const time = timeElement ? timeElement.textContent.trim() : "";

    const location = card.querySelector(".event-location")
      ? card.querySelector(".event-location").textContent.trim()
      : "";

    const description = card.querySelector(".event-description")
      ? card.querySelector(".event-description").textContent.trim()
      : "";

    // Extrair a data e hora de início e fim do texto
    const startDateStr = card.dataset.date;
    const allDay = !time || time.indexOf("às") === -1;

    let startTime, endTime;
    if (!allDay && time) {
      const timeParts = time
        .replace(/[^\d:]/g, " ")
        .trim()
        .split(/\s+/);
      startTime = timeParts[0];
      endTime = timeParts.length > 1 ? timeParts[1] : "";
    }

    const event = {
      id: id,
      title: title,
      start: startDateStr + (startTime ? "T" + startTime + ":00" : ""),
      allDay: allDay,
      extendedProps: {
        status: status,
        description: description,
        location: location,
      },
      backgroundColor: statusColors[status] || "#3788d8",
      borderColor: statusColors[status] || "#3788d8",
      textColor: "#fff",
    };

    if (endTime) {
      event.end = startDateStr + "T" + endTime + ":00";
    }

    // Para eventos cancelados, adicionar estilo visual
    if (status === "cancelado") {
      event.textDecoration = "line-through";
      event.backgroundColor = "rgba(220, 53, 69, 0.6)";
      event.borderColor = "#dc3545";
    }

    events.push(event);
  });

  // Inicializar o FullCalendar
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
    dayMaxEvents: true, // Limitar número de eventos visíveis por dia
    height: "auto",
    events: events,

    // Personalização de eventos
    eventContent: function (info) {
      const status = info.event.extendedProps.status;

      // Personalizar visualmente eventos cancelados
      if (status === "cancelado") {
        const eventEl = document.createElement("div");
        eventEl.classList.add("fc-event-content", "canceled-event");
        eventEl.innerHTML = `<div class="fc-event-title" style="text-decoration: line-through;">${info.event.title}</div>`;
        return { domNodes: [eventEl] };
      }

      return null; // Usar o padrão para outros status
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
  });

  // Renderizar o calendário
  calendar.render();

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
  document
    .querySelector('.view-option[data-view="dayGridMonth"]')
    .classList.add("active");

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
    const modalBody = document.getElementById("event-modal-body");

    // Clonar o conteúdo do card para o modal
    modalBody.innerHTML = "";
    const eventDetails = eventCard.cloneNode(true);

    // Se o usuário tem permissão para editar, adicionar botão direto
    if (canEdit) {
      const editBtn = eventDetails.querySelector('a[href*="edit"]');
      if (editBtn) {
        const editBtnClone = editBtn.cloneNode(true);
        editBtnClone.className = "btn btn-primary btn-block mt-3";
        editBtnClone.innerText = "Editar Compromisso";
        editBtnClone.title = "";

        // Adicionar ao final
        modalBody.appendChild(eventDetails);
        modalBody.appendChild(editBtnClone);
      } else {
        modalBody.appendChild(eventDetails);
      }
    } else {
      modalBody.appendChild(eventDetails);
    }

    // Exibir modal
    modal.style.display = "block";

    // Configurar botão de fechar
    const closeBtn = document.querySelector(".event-modal-close");
    closeBtn.onclick = function () {
      modal.style.display = "none";
    };

    // Fechar quando clicar fora do modal
    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    };
  }

  // Função para atualizar a data de um evento via AJAX
  function updateEventDate(id, start, end) {
    // Obter formato adequado das datas
    const startStr = start.toISOString();
    const endStr = end ? end.toISOString() : "";

    // Criar FormData para envio
    const formData = new FormData();
    formData.append("id", id);
    formData.append("start", startStr);
    if (endStr) formData.append("end", endStr);

    // Fazer requisição AJAX
    fetch(`${baseUrl}/compromissos/update-date`, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Mostrar mensagem de sucesso temporária
          const message = document.createElement("div");
          message.className = "alert alert-success update-message";
          message.textContent = "Compromisso atualizado com sucesso";
          document.querySelector(".calendar-container").prepend(message);

          // Esconder após 3 segundos
          setTimeout(() => {
            message.remove();
          }, 3000);
        } else {
          // Mostrar mensagem de erro e reverter
          alert(data.message || "Erro ao atualizar compromisso");
          window.location.reload();
        }
      })
      .catch((error) => {
        console.error("Erro na requisição:", error);
        alert("Erro ao atualizar compromisso. Tente novamente.");
        window.location.reload();
      });
  }

  // Configurar filtros para atualizar o calendário também
  function setupFilters(calendar, allEvents) {
    const statusFilter = document.getElementById("filter-status");
    const monthFilter = document.getElementById("filter-month");
    const searchFilter = document.getElementById("filter-search");
    const clearFilterBtn = document.getElementById("clear-filters");

    // Função para aplicar filtros
    function applyFilters() {
      const statusValue = statusFilter ? statusFilter.value : "all";
      const monthValue = monthFilter ? monthFilter.value : "all";
      const searchValue = searchFilter ? searchFilter.value.toLowerCase() : "";

      // Filtrar eventos
      const filteredEvents = allEvents.filter((event) => {
        // Status
        const statusMatch =
          statusValue === "all" || event.extendedProps.status === statusValue;

        // Mês
        const eventDate = new Date(event.start);
        const eventMonth = eventDate.getMonth() + 1; // getMonth() retorna 0-11
        const monthMatch =
          monthValue === "all" || eventMonth.toString() === monthValue;

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

    // Adicionar listeners para filtros
    if (statusFilter) statusFilter.addEventListener("change", applyFilters);
    if (monthFilter) monthFilter.addEventListener("change", applyFilters);
    if (searchFilter) searchFilter.addEventListener("input", applyFilters);

    // Botão para limpar filtros
    if (clearFilterBtn) {
      clearFilterBtn.addEventListener("click", function () {
        if (statusFilter) statusFilter.value = "all";
        if (monthFilter) monthFilter.value = "all";
        if (searchFilter) searchFilter.value = "";

        // Resetar filtros e atualizar visualização
        calendar.removeAllEvents();
        calendar.addEventSource(allEvents);

        // Chamar a função de filtros originais se existir
        if (typeof window.clearFilters === "function") {
          window.clearFilters();
        }
      });
    }
  }
});
