document.addEventListener("DOMContentLoaded", function () {
  // Verificar se estamos na página de notificações
  const isNotificationsPage =
    window.location.pathname.includes("/notifications");

  if (!isNotificationsPage) return;

  // Inicializar comportamentos específicos da página de notificações
  initNotificationActions();

  /**
   * Inicializa ações para notificações na página de listagem
   */
  function initNotificationActions() {
    // Botão para marcar todas como lidas
    const markAllReadBtn = document.querySelector('.btn[type="submit"]');

    if (markAllReadBtn) {
      markAllReadBtn.addEventListener("click", function (e) {
        // Confirmar ação se houver muitas notificações
        const notificationsCount =
          document.querySelectorAll(".notification-item").length;

        if (notificationsCount > 10) {
          if (
            !confirm(
              `Tem certeza que deseja marcar todas as ${notificationsCount} notificações como lidas?`
            )
          ) {
            e.preventDefault();
          }
        }
      });
    }

    // Detectar se estamos na página de detalhes de notificação
    const isDetailPage = window.location.pathname.includes(
      "/notifications/view"
    );

    if (isDetailPage) {
      // Destacar botões de aprovar/rejeitar com animação sutil
      const actionButtons = document.querySelectorAll(
        ".notification-actions .btn"
      );

      if (actionButtons.length > 0) {
        // Adicionar uma animação sutil
        actionButtons.forEach((button) => {
          button.style.transition = "transform 0.3s ease";
          button.style.transform = "scale(1.05)";

          setTimeout(() => {
            button.style.transform = "scale(1)";
          }, 500);
        });
      }
    }
  }
});
