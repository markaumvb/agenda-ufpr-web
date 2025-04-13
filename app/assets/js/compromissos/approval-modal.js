document.addEventListener("DOMContentLoaded", function () {
  // Verifica se há compromissos aguardando aprovação
  checkPendingApprovals();

  // Função que busca compromissos aguardando aprovação
  function checkPendingApprovals() {
    // Buscar o contador no DOM - adicionado como badge na navbar
    const notificationBadge = document.querySelector(".notification-badge");
    if (!notificationBadge) return;

    // Se existe um contador e ele tiver valor maior que zero, mostrar o modal
    const pendingCount = parseInt(notificationBadge.textContent);
    if (pendingCount > 0) {
      fetchPendingCompromissos();
    }
  }

  // Busca os compromissos aguardando aprovação via AJAX
  function fetchPendingCompromissos() {
    fetch(AgendaUFPR.config.baseUrl + "/api/pending-approvals")
      .then((response) => response.json())
      .then((data) => {
        if (data.count > 0) {
          showApprovalModal(data.compromissos);
        }
      })
      .catch((error) => {
        console.error("Erro ao buscar compromissos pendentes:", error);
      });
  }

  // Exibe o modal com os compromissos aguardando aprovação
  function showApprovalModal(compromissos) {
    // Criar o modal se não existir
    let modal = document.getElementById("approval-modal");
    if (!modal) {
      modal = document.createElement("div");
      modal.id = "approval-modal";
      modal.className = "modal";
      document.body.appendChild(modal);

      // Criar conteúdo do modal
      modal.innerHTML = `
        <div class="modal-content">
          <div class="modal-header">
            <h2>Compromissos Aguardando Aprovação</h2>
            <span class="modal-close">&times;</span>
          </div>
          <div class="modal-body">
            <div id="pending-compromissos-list"></div>
          </div>
          <div class="modal-footer">
            <button id="review-later" class="btn btn-secondary">Revisar depois</button>
          </div>
        </div>
      `;

      // Configurar eventos de fechamento
      const closeBtn = modal.querySelector(".modal-close");
      const reviewLaterBtn = modal.querySelector("#review-later");

      closeBtn.addEventListener("click", () => {
        modal.style.display = "none";
      });

      reviewLaterBtn.addEventListener("click", () => {
        modal.style.display = "none";
      });

      // Fechar ao clicar fora do conteúdo
      window.addEventListener("click", (event) => {
        if (event.target === modal) {
          modal.style.display = "none";
        }
      });
    }

    // Atualizar a lista de compromissos
    const compromissosList = modal.querySelector("#pending-compromissos-list");
    compromissosList.innerHTML = "";

    // Adicionar cada compromisso à lista
    compromissos.forEach((compromisso) => {
      const startDate = new Date(compromisso.start_datetime);
      const endDate = new Date(compromisso.end_datetime);

      const card = document.createElement("div");
      card.className = "approval-card";
      card.innerHTML = `
        <div class="approval-card-header">
          <h3>${compromisso.title}</h3>
          <div class="approval-meta">
            <div class="approval-user">Criado por: ${
              compromisso.created_by_name
            }</div>
            <div class="approval-agenda">Agenda: ${
              compromisso.agenda_title
            }</div>
          </div>
        </div>
        <div class="approval-card-body">
          <div class="approval-datetime">
            <span class="approval-date">
              <i class="icon-calendar"></i> ${startDate.toLocaleDateString(
                "pt-BR"
              )}
            </span>
            <span class="approval-time">
              <i class="icon-clock"></i> ${startDate.toLocaleTimeString(
                "pt-BR",
                { hour: "2-digit", minute: "2-digit" }
              )} 
              às ${endDate.toLocaleTimeString("pt-BR", {
                hour: "2-digit",
                minute: "2-digit",
              })}
            </span>
          </div>
          ${
            compromisso.location
              ? `<div class="approval-location"><i class="icon-location"></i> ${compromisso.location}</div>`
              : ""
          }
          ${
            compromisso.description
              ? `<div class="approval-description">${compromisso.description}</div>`
              : ""
          }
        </div>
        <div class="approval-actions">
          <button class="btn btn-success approve-btn" data-id="${
            compromisso.id
          }">Aprovar</button>
          <button class="btn btn-danger reject-btn" data-id="${
            compromisso.id
          }">Rejeitar</button>
          <a href="${AgendaUFPR.config.baseUrl}/compromissos/view?id=${
        compromisso.id
      }" class="btn btn-secondary">Detalhes</a>
        </div>
      `;

      // Adicionar eventos aos botões
      const approveBtn = card.querySelector(".approve-btn");
      const rejectBtn = card.querySelector(".reject-btn");

      approveBtn.addEventListener("click", () => {
        approveCompromisso(compromisso.id, card);
      });

      rejectBtn.addEventListener("click", () => {
        rejectCompromisso(compromisso.id, card);
      });

      compromissosList.appendChild(card);
    });

    // Exibir o modal
    modal.style.display = "block";
  }

  // Aprovar um compromisso
  function approveCompromisso(id, card) {
    const formData = new FormData();
    formData.append("id", id);

    fetch(AgendaUFPR.config.baseUrl + "/meuscompromissos/approve", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Adicionar classe de aprovado ao card
          card.classList.add("approved");

          // Mostrar mensagem de sucesso
          const statusMsg = document.createElement("div");
          statusMsg.className = "status-message success";
          statusMsg.textContent = "Compromisso aprovado com sucesso!";
          card.querySelector(".approval-actions").replaceWith(statusMsg);

          // Atualizar contador de notificações
          updateNotificationCounter(-1);

          // Remover o card após alguns segundos
          setTimeout(() => {
            card.remove();

            // Se não houver mais cards, fechar o modal
            const remainingCards =
              document.querySelectorAll(".approval-card").length;
            if (remainingCards === 0) {
              document.getElementById("approval-modal").style.display = "none";
            }
          }, 2000);
        } else {
          // Mostrar erro
          alert(
            "Erro ao aprovar compromisso: " +
              (data.message || "Erro desconhecido")
          );
        }
      })
      .catch((error) => {
        console.error("Erro na requisição:", error);
        alert("Erro ao comunicar com o servidor");
      });
  }

  // Rejeitar um compromisso
  function rejectCompromisso(id, card) {
    const formData = new FormData();
    formData.append("id", id);

    fetch(AgendaUFPR.config.baseUrl + "/meuscompromissos/reject", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Adicionar classe de rejeitado ao card
          card.classList.add("rejected");

          // Mostrar mensagem de rejeição
          const statusMsg = document.createElement("div");
          statusMsg.className = "status-message rejected";
          statusMsg.textContent = "Compromisso rejeitado!";
          card.querySelector(".approval-actions").replaceWith(statusMsg);

          // Atualizar contador de notificações
          updateNotificationCounter(-1);

          // Remover o card após alguns segundos
          setTimeout(() => {
            card.remove();

            // Se não houver mais cards, fechar o modal
            const remainingCards =
              document.querySelectorAll(".approval-card").length;
            if (remainingCards === 0) {
              document.getElementById("approval-modal").style.display = "none";
            }
          }, 2000);
        } else {
          // Mostrar erro
          alert(
            "Erro ao rejeitar compromisso: " +
              (data.message || "Erro desconhecido")
          );
        }
      })
      .catch((error) => {
        console.error("Erro na requisição:", error);
        alert("Erro ao comunicar com o servidor");
      });
  }

  // Atualiza o contador de notificações
  function updateNotificationCounter(change) {
    const badge = document.querySelector(".notification-badge");
    if (badge) {
      const currentCount = parseInt(badge.textContent);
      const newCount = Math.max(0, currentCount + change);

      badge.textContent = newCount;

      if (newCount === 0) {
        badge.classList.add("hidden");
      }
    }
  }
});
