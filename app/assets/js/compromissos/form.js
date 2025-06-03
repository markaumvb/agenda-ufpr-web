document.addEventListener("DOMContentLoaded", function () {
  // Verificar se estamos em um formulário de edição ou criação
  const isEditForm = window.location.href.includes("/edit");
  const compromissoId = isEditForm
    ? document.querySelector('input[name="id"]')?.value
    : null;

  // Inicializar exibição das opções de repetição
  toggleRepeatOptions();

  // Configurar eventos de recorrência
  document.querySelectorAll('input[name="repeat_type"]').forEach((input) => {
    input.addEventListener("change", toggleRepeatOptions);
  });

  // REMOVER completamente os listeners para verificação de conflitos durante a edição
  const startDatetime = document.getElementById("start_datetime");
  const endDatetime = document.getElementById("end_datetime");

  // Validação do formulário
  const form = document.querySelector("form");
  if (form) {
    form.addEventListener("submit", function (event) {
      // As validações de data já estão no arquivo validation.js

      // Apenas validações específicas do formulário
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
            alert("Selecione pelo menos um dia da semana para a recorrência");
            return;
          }
        }
      }
    });
  }

  if (isEditForm) {
    // Event listener específico para o botão cancelar (evitar interferência)
    const cancelBtn = document.getElementById("cancel-edit-btn");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", function (e) {
        // Não interceptar o clique do cancelar - deixar seguir o href normalmente
        return true;
      });
    }

    // Event listeners apenas para formulários de delete, não para links
    document.querySelectorAll("form.delete-form").forEach((form) => {
      form.addEventListener("submit", function (event) {
        const submitBtn = event.submitter;
        if (
          submitBtn &&
          submitBtn.textContent.includes("Excluir Este e Futuros")
        ) {
          if (
            !confirm(
              "Tem certeza que deseja excluir este compromisso e todas as suas ocorrências futuras?"
            )
          ) {
            event.preventDefault();
          }
        } else {
          if (!confirm("Tem certeza que deseja excluir este compromisso?")) {
            event.preventDefault();
          }
        }
      });
    });

    // Event listener para cancelar futuros
    document
      .querySelectorAll('form[action*="cancel-future"]')
      .forEach((form) => {
        form.addEventListener("submit", function (event) {
          if (
            !confirm(
              "Tem certeza que deseja cancelar todos os compromissos futuros desta série?"
            )
          ) {
            event.preventDefault();
          }
        });
      });
  }

  /**
   * Controla a exibição das opções de repetição baseado no tipo selecionado
   */
  function toggleRepeatOptions() {
    const repeatType = document.querySelector(
      'input[name="repeat_type"]:checked'
    ).value;
    const repeatUntilContainer = document.getElementById(
      "repeat_until_container"
    );
    const repeatDaysContainer = document.getElementById(
      "repeat_days_container"
    );

    // Mostrar/esconder a opção de "até quando"
    if (repeatType === "none") {
      repeatUntilContainer.style.display = "none";
      repeatDaysContainer.style.display = "none";
    } else {
      repeatUntilContainer.style.display = "block";

      // Mostrar/esconder dias da semana apenas para a opção "specific_days"
      if (repeatType === "specific_days") {
        repeatDaysContainer.style.display = "block";
      } else {
        repeatDaysContainer.style.display = "none";
      }
    }
  }
});
