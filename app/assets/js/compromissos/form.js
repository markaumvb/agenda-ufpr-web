document.addEventListener("DOMContentLoaded", function () {
  // Verificar se estamos em um formulário de edição ou criação
  const isEditForm = window.location.href.includes("/edit");

  // Inicializar exibição das opções de repetição
  toggleRepeatOptions();

  // Configurar eventos de recorrência
  document.querySelectorAll('input[name="repeat_type"]').forEach((input) => {
    input.addEventListener("change", toggleRepeatOptions);
  });

  // Validação do formulário APENAS para o formulário principal de update
  const mainForm = document.querySelector("form[action*='update']");
  if (mainForm) {
    mainForm.addEventListener("submit", function (event) {
      // Apenas validações específicas do formulário principal
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

  // IMPORTANTE: NÃO adicionar NENHUM event listener para formulários de delete
  // Eles devem funcionar apenas com onsubmit inline

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
