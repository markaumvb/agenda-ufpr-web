/**
 * Script principal do sistema de agendamento UFPR
 * Carrega e inicializa os módulos apropriados com base na página atual
 */

// Namespace principal da aplicação
const AgendaUFPR = {
  // Configurações globais
  config: {
    baseUrl:
      document.head.querySelector("base")?.href || window.location.origin,
  },

  // Utilitários comuns
  utils: {
    // Formatar data para exibição
    formatDate: function (dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toLocaleDateString("pt-BR");
    },

    // Formatar hora para exibição
    formatTime: function (dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toLocaleTimeString("pt-BR", {
        hour: "2-digit",
        minute: "2-digit",
      });
    },

    // Formatar data e hora para exibição
    formatDateTime: function (dateString) {
      if (!dateString) return "";
      const date = new Date(dateString);
      return `${this.formatDate(dateString)} ${this.formatTime(dateString)}`;
    },

    // Confirmar ação com o usuário
    confirm: function (message, callback) {
      if (confirm(message)) {
        callback();
      }
    },

    // Copiar para a área de transferência
    copyToClipboard: function (text) {
      const tempInput = document.createElement("input");
      tempInput.value = text;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand("copy");
      document.body.removeChild(tempInput);
    },
  },

  // Módulo de Agendas
  agendas: {
    // Inicializar página de listagem de agendas
    initIndex: function () {
      // Confirmação para exclusão
      document.querySelectorAll(".delete-form").forEach((form) => {
        form.addEventListener("submit", function (event) {
          event.preventDefault();
          AgendaUFPR.utils.confirm(
            "Tem certeza que deseja excluir esta agenda?",
            () => {
              this.submit();
            }
          );
        });
      });
    },

    // Inicializar formulário de criação de agenda
    initCreate: function () {
      const form = document.querySelector("form");
      const titleInput = document.getElementById("title");
      const colorInput = document.getElementById("color");

      if (!form || !titleInput) return;

      // Validação do formulário
      form.addEventListener("submit", function (event) {
        if (!titleInput.value.trim()) {
          event.preventDefault();
          alert("O título da agenda é obrigatório.");
          titleInput.focus();
        }
      });

      // Atualização em tempo real da cor
      if (colorInput) {
        colorInput.addEventListener("input", function () {});
      }
    },

    // Inicializar formulário de edição de agenda
    initEdit: function () {
      this.initCreate(); // Reutiliza a lógica de criação
    },
  },

  // Módulo de Compromissos
  compromissos: {
    // Inicializar página de listagem de compromissos
    initIndex: function () {
      // Filtros
      const filterStatus = document.getElementById("filter-status");
      const filterMonth = document.getElementById("filter-month");
      const filterSearch = document.getElementById("filter-search");

      if (filterStatus)
        filterStatus.addEventListener("change", this.applyFilters);
      if (filterMonth)
        filterMonth.addEventListener("change", this.applyFilters);
      if (filterSearch)
        filterSearch.addEventListener("input", this.applyFilters);

      // Inicializar calendário
      this.initCalendar();
    },

    // Aplicar filtros na lista de compromissos
    applyFilters: function () {
      const filterStatus = document.getElementById("filter-status");
      const filterMonth = document.getElementById("filter-month");
      const filterSearch = document.getElementById("filter-search");
      const eventCards = document.querySelectorAll(".event-card");

      if (!eventCards.length) return;

      const statusFilter = filterStatus ? filterStatus.value : "all";
      const monthFilter = filterMonth ? filterMonth.value : "all";
      const searchFilter = filterSearch
        ? filterSearch.value.toLowerCase().trim()
        : "";

      eventCards.forEach((card) => {
        const status = card.dataset.status;
        const month = card.dataset.month;
        const searchText = card.dataset.search;

        // Verificar correspondência com filtros
        const statusMatch = statusFilter === "all" || status === statusFilter;
        const monthMatch = monthFilter === "all" || month === monthFilter;
        const searchMatch = !searchFilter || searchText?.includes(searchFilter);

        // Exibir ou ocultar o card
        card.style.display =
          statusMatch && monthMatch && searchMatch ? "block" : "none";
      });
    },

    // Inicializar interações do calendário
    initCalendar: function () {
      // Configurar cliques nos dias do calendário
      document
        .querySelectorAll(".calendar-day:not(.empty-day)")
        .forEach((day) => {
          day.addEventListener("click", function () {
            const date = this.dataset.date;
            if (!date) return;

            // Remover seleção anterior
            document.querySelectorAll(".calendar-day").forEach((d) => {
              d.classList.remove("selected-day");
            });

            // Adicionar classe de seleção ao dia clicado
            this.classList.add("selected-day");

            // Exibir compromissos do dia
            AgendaUFPR.compromissos.showDayEvents(this, date);
          });
        });

      // Configurar botão de fechar painel de compromissos
      const closeButton = document.querySelector(".day-events-close");
      if (closeButton) {
        closeButton.addEventListener("click", function () {
          document.getElementById("day-events-container").style.display =
            "none";
          document.querySelectorAll(".calendar-day").forEach((day) => {
            day.classList.remove("selected-day");
          });
        });
      }

      // Destacar o dia atual
      const today = new Date().toISOString().split("T")[0];
      const todayCell = document.querySelector(
        `.calendar-day[data-date="${today}"]`
      );
      if (todayCell) {
        todayCell.classList.add("today");
      }
    },

    // Exibir compromissos de um dia específico
    showDayEvents: function (dayElement, date) {
      // Formatar a data para exibição
      const dateObj = new Date(date + "T00:00:00");
      const formattedDate = dateObj.toLocaleDateString("pt-BR", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });

      // Atualizar o título
      document.getElementById("day-events-title").textContent =
        "Compromissos de " + formattedDate;

      // Buscar compromissos do dia
      const dayEvents = dayElement.querySelectorAll(".event");
      const eventsList = document.getElementById("day-events-list");
      eventsList.innerHTML = "";

      if (dayEvents.length === 0) {
        eventsList.innerHTML =
          '<p class="no-events">Não há compromissos para este dia.</p>';
      } else {
        // Para cada evento, criar um card
        dayEvents.forEach((event) => {
          const id = event.dataset.id;
          const title =
            event.dataset.title ||
            event.querySelector(".event-title")?.textContent;
          const description = event.dataset.description || "";
          const start = new Date(
            event.dataset.start ||
              event.querySelector(".event-time")?.textContent
          );
          const end = new Date(event.dataset.end || "");
          const status =
            event.dataset.status ||
            event.className.match(/event-status-(\w+)/)?.[1];

          if (!title || !status) return;

          const card = document.createElement("div");
          card.className = `event-card event-status-${status}`;

          // Formatar horário
          const formattedTime = `${start.toLocaleTimeString("pt-BR", {
            hour: "2-digit",
            minute: "2-digit",
          })}${
            end
              ? " - " +
                end.toLocaleTimeString("pt-BR", {
                  hour: "2-digit",
                  minute: "2-digit",
                })
              : ""
          }`;

          card.innerHTML = `
            <div class="event-card-header">
              <h4>${title}</h4>
              <span class="event-time">${formattedTime}</span>
            </div>
            ${
              description
                ? `<div class="event-description">${description}</div>`
                : ""
            }
            <div class="event-card-actions">
              <a href="${
                AgendaUFPR.config.baseUrl
              }/compromissos/edit?id=${id}" class="btn btn-sm btn-secondary">
                <i class="icon-edit"></i> Editar
              </a>
            </div>
          `;

          eventsList.appendChild(card);
        });
      }

      // Adicionar botão para criar novo compromisso se tiver permissão
      const agendaId = document.querySelector(".calendar-container")?.dataset
        .agendaId;
      const canEdit =
        document.querySelector(".header-actions a.btn-primary") !== null;

      if (canEdit && agendaId) {
        const addButton = document.createElement("div");
        addButton.className = "add-event-button mt-3";
        addButton.innerHTML = `
          <a href="${AgendaUFPR.config.baseUrl}/compromissos/new?agenda_id=${agendaId}&date=${date}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Compromisso neste dia
          </a>
        `;
        eventsList.appendChild(addButton);
      }

      // Exibir o container
      const eventsContainer = document.getElementById("day-events-container");
      if (eventsContainer) {
        eventsContainer.style.display = "block";
        eventsContainer.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    },

    // Inicializar formulário de criação de compromisso
    initCreate: function () {
      document
        .querySelectorAll('input[name="repeat_type"]')
        .forEach((input) => {
          input.addEventListener("change", this.toggleRepeatOptions);
        });

      const startDatetime = document.getElementById("start_datetime");
      const endDatetime = document.getElementById("end_datetime");

      if (startDatetime)
        startDatetime.addEventListener("change", this.checkTimeConflict);
      if (endDatetime)
        endDatetime.addEventListener("change", this.checkTimeConflict);

      // Inicializar opções de repetição
      this.toggleRepeatOptions();

      // Validação do formulário
      const form = document.querySelector("form");
      if (form) {
        form.addEventListener("submit", function (event) {
          const title = document.getElementById("title").value;
          if (!title.trim()) {
            event.preventDefault();
            alert("O título do compromisso é obrigatório");
            return;
          }

          const repeatType = document.querySelector(
            'input[name="repeat_type"]:checked'
          ).value;

          // Se for um evento recorrente, verificar se a data final foi definida
          if (repeatType !== "none") {
            const repeatUntil = document.getElementById("repeat_until").value;

            if (!repeatUntil) {
              event.preventDefault();
              alert(
                "Para eventos recorrentes, é necessário definir uma data final"
              );
              return;
            }

            // Para dias específicos, verificar se pelo menos um dia foi selecionado
            if (repeatType === "specific_days") {
              const checkboxes = document.querySelectorAll(
                'input[name="repeat_days[]"]:checked'
              );

              if (checkboxes.length === 0) {
                event.preventDefault();
                alert(
                  "Selecione pelo menos um dia da semana para a recorrência"
                );
                return;
              }
            }
          }
        });
      }
    },

    // Controla a exibição das opções de repetição
    toggleRepeatOptions: function () {
      const repeatType = document.querySelector(
        'input[name="repeat_type"]:checked'
      )?.value;
      if (!repeatType) return;

      const repeatUntilContainer = document.getElementById(
        "repeat_until_container"
      );
      const repeatDaysContainer = document.getElementById(
        "repeat_days_container"
      );

      if (!repeatUntilContainer || !repeatDaysContainer) return;

      // Mostrar/esconder a opção de "até quando"
      if (repeatType === "none") {
        repeatUntilContainer.style.display = "none";
        repeatDaysContainer.style.display = "none";
      } else {
        repeatUntilContainer.style.display = "block";

        // Mostrar/esconder dias da semana apenas para a opção "specific_days"
        repeatDaysContainer.style.display =
          repeatType === "specific_days" ? "block" : "none";
      }
    },

    // Verifica conflitos de horário
    checkTimeConflict: function () {
      const startDatetime = document.getElementById("start_datetime")?.value;
      const endDatetime = document.getElementById("end_datetime")?.value;

      if (!startDatetime || !endDatetime) return;

      // Verificar se a data final é maior que a inicial
      if (new Date(endDatetime) <= new Date(startDatetime)) {
        alert(
          "A data e hora de término deve ser posterior à data e hora de início."
        );
      }

      // Aqui poderia ser adicionada uma verificação AJAX para conflitos
      // com outros compromissos no banco de dados
    },

    // Inicializar formulário de edição de compromisso
    initEdit: function () {
      this.initCreate(); // Reutiliza a lógica de criação

      // Adicionar confirmação para exclusão
      document.querySelectorAll(".delete-form").forEach((form) => {
        form.addEventListener("submit", function (event) {
          event.preventDefault();
          AgendaUFPR.utils.confirm(
            "Tem certeza que deseja excluir este compromisso?",
            () => {
              this.submit();
            }
          );
        });
      });

      // Verificar se há formulários de exclusão de eventos futuros
      document
        .querySelectorAll('form[action*="delete"][action*="future"]')
        .forEach((form) => {
          form.addEventListener("submit", function (event) {
            event.preventDefault();
            AgendaUFPR.utils.confirm(
              "Tem certeza que deseja excluir este compromisso e todas as suas ocorrências futuras?",
              () => {
                this.submit();
              }
            );
          });
        });
    },
  },

  // Módulo de Compartilhamento
  shares: {
    // Inicializar página de compartilhamento
    initIndex: function () {
      // Copiar URL para área de transferência
      document.querySelectorAll(".input-group .btn").forEach((button) => {
        button.addEventListener("click", function () {
          const input = this.closest(".input-group").querySelector("input");
          if (input) {
            AgendaUFPR.utils.copyToClipboard(input.value);

            const originalText = this.textContent;
            this.textContent = "Copiado!";
            setTimeout(() => {
              this.textContent = originalText;
            }, 2000);
          }
        });
      });

      // Confirmação para remoção de compartilhamento
      document
        .querySelectorAll('form[action*="/shares/remove"]')
        .forEach((form) => {
          form.addEventListener("submit", function (event) {
            event.preventDefault();
            AgendaUFPR.utils.confirm(
              "Tem certeza que deseja remover o compartilhamento com este usuário?",
              () => {
                this.submit();
              }
            );
          });
        });
    },

    // Inicializar visualização de agenda pública
    initPublic: function () {
      // Filtros para agenda pública
      const filterStatus = document.getElementById("filter-status");
      const filterMonth = document.getElementById("filter-month");
      const filterSearch = document.getElementById("filter-search");

      if (filterStatus)
        filterStatus.addEventListener(
          "change",
          AgendaUFPR.compromissos.applyFilters
        );
      if (filterMonth)
        filterMonth.addEventListener(
          "change",
          AgendaUFPR.compromissos.applyFilters
        );
      if (filterSearch)
        filterSearch.addEventListener(
          "input",
          AgendaUFPR.compromissos.applyFilters
        );

      // Configurar interações do calendário
      document
        .querySelectorAll(".calendar-day:not(.empty-day)")
        .forEach((day) => {
          day.addEventListener("click", function () {
            const date = this.dataset.date;
            if (!date) return;

            document.querySelectorAll(".calendar-day").forEach((d) => {
              d.classList.remove("selected-day");
            });

            this.classList.add("selected-day");

            // Mostrar compromissos do dia
            const dayEvents = this.querySelectorAll(".event");
            const eventsList = document.getElementById("day-events-list");

            if (!eventsList) return;

            eventsList.innerHTML = "";

            // Formatar data para o título
            const dateObj = new Date(date + "T00:00:00");
            const formattedDate = dateObj.toLocaleDateString("pt-BR", {
              weekday: "long",
              year: "numeric",
              month: "long",
              day: "numeric",
            });

            document.getElementById("day-events-title").textContent =
              "Compromissos de " + formattedDate;

            if (dayEvents.length === 0) {
              eventsList.innerHTML =
                '<p class="no-events">Não há compromissos para este dia.</p>';
            } else {
              // Criar cards para cada evento
              dayEvents.forEach((event) => {
                const title = event.dataset.title;
                const description = event.dataset.description || "";
                const location = event.dataset.location || "";
                const start = new Date(event.dataset.start);
                const end = new Date(event.dataset.end || "");
                const status = event.dataset.status;

                const card = document.createElement("div");
                card.className = `event-card event-status-${status}`;

                // Formatar horário
                const formattedTime = `${start.toLocaleTimeString("pt-BR", {
                  hour: "2-digit",
                  minute: "2-digit",
                })}${
                  end
                    ? " - " +
                      end.toLocaleTimeString("pt-BR", {
                        hour: "2-digit",
                        minute: "2-digit",
                      })
                    : ""
                }`;

                let html = `
                <div class="event-card-header">
                  <h4>${title}</h4>
                  <span class="event-time">${formattedTime}</span>
                </div>
              `;

                if (description) {
                  html += `<div class="event-description">${description}</div>`;
                }

                if (location) {
                  html += `<div class="event-location"><i class="icon-location"></i> ${location}</div>`;
                }

                card.innerHTML = html;
                eventsList.appendChild(card);
              });
            }

            // Exibir o container
            const eventsContainer = document.getElementById(
              "day-events-container"
            );
            if (eventsContainer) {
              eventsContainer.style.display = "block";
            }
          });
        });

      // Configurar botão de fechar
      const closeButton = document.querySelector(".day-events-close");
      if (closeButton) {
        closeButton.addEventListener("click", function () {
          const container = document.getElementById("day-events-container");
          if (container) {
            container.style.display = "none";
          }

          document.querySelectorAll(".calendar-day").forEach((day) => {
            day.classList.remove("selected-day");
          });
        });
      }
    },
  },

  // Inicializar a aplicação com base na página atual
  init: function () {
    const path = window.location.pathname;

    // Detectar a página atual
    if (path.includes("/agendas/new") || path.includes("/agendas/create")) {
      this.agendas.initCreate();
    } else if (path.includes("/agendas/edit")) {
      this.agendas.initEdit();
    } else if (path.includes("/agendas")) {
      this.agendas.initIndex();
    } else if (
      path.includes("/compromissos/new") ||
      path.includes("/compromissos/create")
    ) {
      this.compromissos.initCreate();
    } else if (path.includes("/compromissos/edit")) {
      this.compromissos.initEdit();
    } else if (path.includes("/compromissos")) {
      this.compromissos.initIndex();
    } else if (path.includes("/shares") && !path.includes("/shares/shared")) {
      this.shares.initIndex();
    } else if (path.includes("/public-agenda")) {
      this.shares.initPublic();
    }
  },
};

// Inicializar quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function () {
  AgendaUFPR.init();
});
