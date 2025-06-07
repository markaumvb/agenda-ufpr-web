/**
 * Script para gerenciar as notificações do usuário
 * Arquivo: public/assets/js/notifications.js
 */

// Namespace para notificações dentro do objeto AgendaUFPR
AgendaUFPR.notifications = {
  // Contador de notificações
  count: 0,

  // Configurações
  config: {
    refreshInterval: 60000, // Atualizar a cada 1 minuto
    maxNotifications: 5, // Máximo de notificações para exibir no menu
    apiEndpoint: "/api/notifications",
    markReadEndpoint: "/api/mark-notification-read",
    markAllReadEndpoint: "/api/mark-all-notifications-read",
  },

  // Inicializar o sistema de notificações
  init: function () {
    // Verificar se o usuário está logado
    if (!document.querySelector(".user-menu")) {
      return;
    }

    // Obter o contador de notificações
    this.counter = document.querySelector(".notification-count");
    this.dropdown = document.querySelector(".notification-dropdown");
    this.list = document.querySelector(".notification-list");

    if (!this.counter || !this.dropdown || !this.list) {
      return;
    }

    // Carregar notificações iniciais
    this.loadNotifications();

    // Configurar atualização periódica
    setInterval(() => {
      this.loadNotifications();
    }, this.config.refreshInterval);

    // Configurar evento de clique no ícone de notificações
    document
      .querySelector(".notification-icon")
      .addEventListener("click", (e) => {
        e.preventDefault();
        this.toggleDropdown();
      });

    // Fechar dropdown ao clicar fora
    document.addEventListener("click", (e) => {
      if (
        !e.target.closest(".notification-dropdown") &&
        !e.target.closest(".notification-icon")
      ) {
        this.closeDropdown();
      }
    });

    // Configurar botão de marcar todas como lidas
    const markAllReadBtn = document.querySelector(".mark-all-read");
    if (markAllReadBtn) {
      markAllReadBtn.addEventListener("click", (e) => {
        e.preventDefault();
        this.markAllAsRead();
      });
    }
  },

  // Carrega notificações via AJAX
  loadNotifications: function () {
    fetch(AgendaUFPR.config.baseUrl + this.config.apiEndpoint)
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          return;
        }

        this.count = data.total;
        this.updateCounter();
        this.renderNotifications(data.notifications);
      })
      .catch((error) => {});
  },

  // Atualiza o contador de notificações
  updateCounter: function () {
    if (this.count > 0) {
      this.counter.textContent = this.count;
      this.counter.classList.remove("hidden");
    } else {
      this.counter.textContent = "0";
      this.counter.classList.add("hidden");
    }
  },

  // Renderiza as notificações no dropdown
  renderNotifications: function (notifications) {
    // Limpar lista
    this.list.innerHTML = "";

    if (notifications.length === 0) {
      this.list.innerHTML =
        '<li class="empty-notification">Nenhuma notificação no momento</li>';
      return;
    }

    // Limitar quantidade de notificações exibidas
    const notificationsToShow = notifications.slice(
      0,
      this.config.maxNotifications
    );

    // Adicionar cada notificação à lista
    notificationsToShow.forEach((notification) => {
      const item = document.createElement("li");
      item.className = "notification-item";
      item.dataset.id = notification.id;

      // Criar link para o compromisso ou agenda, se disponível
      let targetUrl = "#";
      if (notification.compromisso_id) {
        targetUrl = `${AgendaUFPR.config.baseUrl}/compromissos/view?id=${notification.compromisso_id}`;
      } else if (notification.agenda_id) {
        targetUrl = `${AgendaUFPR.config.baseUrl}/compromissos?agenda_id=${notification.agenda_id}`;
      }

      item.innerHTML = `
              <a href="${targetUrl}" class="notification-link" data-id="${notification.id}">
                  <div class="notification-content">
                      <div class="notification-message">${notification.message}</div>
                      <div class="notification-date">${notification.created_at}</div>
                  </div>
                  <button class="mark-read-btn" title="Marcar como lida">
                      <i class="fas fa-check"></i>
                  </button>
              </a>
          `;

      // Adicionar à lista
      this.list.appendChild(item);

      // Configurar eventos
      const markReadBtn = item.querySelector(".mark-read-btn");
      if (markReadBtn) {
        markReadBtn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          this.markAsRead(notification.id, item);
        });
      }

      const link = item.querySelector(".notification-link");
      if (link) {
        link.addEventListener("click", () => {
          this.markAsRead(notification.id);
        });
      }
    });

    // Adicionar rodapé com total e link para todas as notificações
    if (notifications.length > this.config.maxNotifications) {
      const more = document.createElement("li");
      more.className = "notification-footer";
      more.innerHTML = `
              <a href="${AgendaUFPR.config.baseUrl}/notifications">
                  Ver todas (${notifications.length})
              </a>
          `;
      this.list.appendChild(more);
    }
  },

  // Abre/fecha o dropdown de notificações
  toggleDropdown: function () {
    if (this.dropdown.classList.contains("show")) {
      this.closeDropdown();
    } else {
      this.openDropdown();
    }
  },

  // Abre o dropdown
  openDropdown: function () {
    this.dropdown.classList.add("show");
  },

  // Fecha o dropdown
  closeDropdown: function () {
    this.dropdown.classList.remove("show");
  },

  // Marca uma notificação como lida
  markAsRead: function (id, item = null) {
    const formData = new FormData();
    formData.append("id", id);

    fetch(AgendaUFPR.config.baseUrl + this.config.markReadEndpoint, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Se um item foi passado, remover da lista
          if (item) {
            item.classList.add("fade-out");
            setTimeout(() => {
              item.remove();
              this.count = Math.max(0, this.count - 1);
              this.updateCounter();

              // Se não há mais notificações, atualizar a lista
              if (
                this.count === 0 &&
                this.list.querySelectorAll(".notification-item").length === 0
              ) {
                this.list.innerHTML =
                  '<li class="empty-notification">Nenhuma notificação no momento</li>';
              }
            }, 300);
          } else {
            // Se o item não foi passado, recarregar todas as notificações
            this.loadNotifications();
          }
        }
      })
      .catch((error) => {
        //console.error("Erro ao marcar notificação como lida:", error);
      });
  },

  // Marca todas as notificações como lidas
  markAllAsRead: function () {
    fetch(AgendaUFPR.config.baseUrl + this.config.markAllReadEndpoint, {
      method: "POST",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Atualizar UI
          this.count = 0;
          this.updateCounter();
          this.list.innerHTML =
            '<li class="empty-notification">Nenhuma notificação no momento</li>';

          // Feedback para o usuário
          AgendaUFPR.utils.showToast(
            "Todas as notificações foram marcadas como lidas",
            "success"
          );
        }
      })
      .catch((error) => {
        // console.error("Erro ao marcar todas as notificações como lidas:", error);
      });
  },
};

// Inicializar notificações quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function () {
  AgendaUFPR.notifications.init();
});
