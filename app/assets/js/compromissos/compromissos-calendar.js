// app/assets/js/compromissos-calendar.js
// Script específico para o calendário de compromissos

document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");
  if (!calendarEl) {
    return;
  }

  // Obter eventos do PHP (assumindo que estão em uma variável global)
  const events = window.compromissosEvents || [];

  // Inicializar o FullCalendar
  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: "pt-br",
    initialView: "dayGridMonth",
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay",
    },

    // DESABILITAR ARRASTAR E EDITAR
    editable: false, // Não permite editar eventos
    eventResizableFromStart: false, // Não permite redimensionar do início
    eventDurationEditable: false, // Não permite editar duração
    eventStartEditable: false, // Não permite editar data/hora de início
    eventDragStart: function () {
      // Previne início do arrasto
      return false;
    },

    // Configurações de altura e aparência
    height: "auto",
    allDaySlot: false,
    slotMinTime: "06:00:00",
    slotMaxTime: "22:00:00",
    nowIndicator: true,

    // Formatação de tempo
    eventTimeFormat: {
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    },

    // Carregar eventos
    events: events,

    // CONFIGURAR CLIQUE DO EVENTO PARA ABRIR MODAL
    eventClick: function (info) {
      // Prevenir comportamento padrão
      info.jsEvent.preventDefault();

      // Mostrar detalhes do evento em modal
      showEventModal(info.event);
    },

    // Customizar renderização do evento
    eventDidMount: function (info) {
      // Adicionar classes CSS baseadas no status
      if (info.event.extendedProps.status) {
        info.el.classList.add(
          "event-status-" + info.event.extendedProps.status
        );
        info.el.setAttribute("data-status", info.event.extendedProps.status);
      }

      // Remover qualquer cursor que indique que é arrastável
      info.el.style.cursor = "pointer";

      // Adicionar título para hover
      info.el.title = info.event.title + "\nClique para ver detalhes";
    },

    // Configurar seleção de datas para criar novos eventos
    selectable: true,
    selectMirror: true,
    select: function (info) {
      // Redirecionar para criar novo compromisso nesta data
      const agendaId = getAgendaIdFromUrl();
      const selectedDate = info.startStr.split("T")[0];
      window.location.href = `${BASE_URL}/compromissos/new?agenda_id=${agendaId}&date=${selectedDate}`;
    },
  });

  // Renderizar o calendário
  calendar.render();

  // Configurar botões de visualização personalizados se existirem
  setupViewButtons(calendar);
});

/**
 * Mostra os detalhes do evento em um modal
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
  $(modal).modal("show");
}

/**
 * Cria o modal de detalhes do evento
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="event-modal-body">
                    <!-- Conteúdo será inserido aqui -->
                </div>
                <div class="modal-footer" id="event-modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    `;

  return modal;
}

/**
 * Preenche o modal com os dados do evento
 */
function populateEventModal(modal, event) {
  const modalBody = modal.querySelector("#event-modal-body");
  const modalFooter = modal.querySelector("#event-modal-footer");
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

  // Adicionar informações de recorrência se existir
  if (
    event.extendedProps.repeat_type &&
    event.extendedProps.repeat_type !== "none"
  ) {
    const repeatTypes = {
      daily: "Diariamente",
      weekly: "Semanalmente",
      specific_days: "Dias específicos",
    };
    const repeatLabel =
      repeatTypes[event.extendedProps.repeat_type] ||
      event.extendedProps.repeat_type;

    content += `
            <div class="mt-3">
                <h6><i class="fas fa-redo text-primary"></i> Recorrência:</h6>
                <p class="mb-0">${repeatLabel}</p>
                ${
                  event.extendedProps.repeat_until
                    ? `<small class="text-muted">Até: ${new Date(
                        event.extendedProps.repeat_until
                      ).toLocaleDateString("pt-BR")}</small>`
                    : ""
                }
            </div>
        `;
  }

  content += "</div>";
  modalBody.innerHTML = content;

  // Adicionar botões de ação no footer baseado no status e permissões
  const agendaId = getAgendaIdFromUrl();
  let footerButtons = `
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Fechar
        </button>
    `;

  // Adicionar botões de ação se o usuário tiver permissão
  if (event.extendedProps.can_edit && status === "pendente") {
    footerButtons = `
            <a href="${BASE_URL}/compromissos/edit?id=${event.id}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
            <button type="button" class="btn btn-warning" onclick="changeEventStatus(${event.id}, 'realizado')">
                <i class="fas fa-check"></i> Marcar como Realizado
            </button>
            <button type="button" class="btn btn-danger" onclick="changeEventStatus(${event.id}, 'cancelado')">
                <i class="fas fa-ban"></i> Cancelar
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                <i class="fas fa-times"></i> Fechar
            </button>
        `;
  }

  modalFooter.innerHTML = footerButtons;
}

/**
 * Configurar botões de visualização customizados
 */
function setupViewButtons(calendar) {
  const viewButtons = document.querySelectorAll(".view-option");
  if (viewButtons.length > 0) {
    viewButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const view = this.getAttribute("data-view");
        if (view && calendar) {
          calendar.changeView(view);

          // Atualizar estado ativo dos botões
          viewButtons.forEach((btn) => btn.classList.remove("active"));
          this.classList.add("active");
        }
      });
    });
  }
}

/**
 * Obter ID da agenda da URL
 */
function getAgendaIdFromUrl() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get("agenda_id");
}

/**
 * Alterar status do evento
 */
function changeEventStatus(eventId, newStatus) {
  if (!confirm(`Tem certeza que deseja alterar o status deste compromisso?`)) {
    return;
  }

  // Criar formulário para enviar via POST
  const form = document.createElement("form");
  form.method = "POST";
  form.action = `${BASE_URL}/compromissos/change-status`;

  const idInput = document.createElement("input");
  idInput.type = "hidden";
  idInput.name = "id";
  idInput.value = eventId;

  const statusInput = document.createElement("input");
  statusInput.type = "hidden";
  statusInput.name = "status";
  statusInput.value = newStatus;

  form.appendChild(idInput);
  form.appendChild(statusInput);
  document.body.appendChild(form);
  form.submit();
}
