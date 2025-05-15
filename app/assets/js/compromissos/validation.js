document.addEventListener("DOMContentLoaded", function () {
  // Selecionar elementos do formulário
  const form = document.querySelector("form");
  const startDatetimeInput = document.getElementById("start_datetime");
  const endDatetimeInput = document.getElementById("end_datetime");
  const errorContainer = document.getElementById("error-container");
  const errorList = document.getElementById("error-list");

  // Sem eventos de validação nos campos de data e hora - validaremos apenas no envio!

  // Validar o formulário apenas no envio
  if (form) {
    form.addEventListener("submit", function (event) {
      const errors = validateForm();

      if (errors.length > 0) {
        // Impedir o envio do formulário
        event.preventDefault();

        // Exibir erros
        displayErrors(errors);
      }
    });
  }

  // Opcional: atualizar automaticamente a data de término quando a data de início for completamente modificada
  if (startDatetimeInput && endDatetimeInput) {
    startDatetimeInput.addEventListener("change", function () {
      if (!startDatetimeInput.value) return;

      const startValue = new Date(startDatetimeInput.value);
      let endValue = endDatetimeInput.value
        ? new Date(endDatetimeInput.value)
        : null;

      // Atualizar data de término somente se estiver vazia ou for anterior à data de início
      if (!endDatetimeInput.value || !endValue || endValue <= startValue) {
        const newEndDate = new Date(startValue);
        newEndDate.setHours(newEndDate.getHours() + 1);

        // Formatar para datetime-local (YYYY-MM-DDThh:mm)
        endDatetimeInput.value = formatDateTimeLocal(newEndDate);
      }
    });
  }

  // Funções auxiliares para validação

  // Função para validar o formulário completo
  function validateForm() {
    const errors = [];

    // Validar título
    const title = document.getElementById("title");
    if (title && !title.value.trim()) {
      errors.push("O título é obrigatório");
    }

    // Validar data e hora de início
    if (startDatetimeInput && !startDatetimeInput.value) {
      errors.push("A data e hora de início são obrigatórias");
    } else if (startDatetimeInput && startDatetimeInput.value) {
      const now = new Date();
      const startDate = new Date(startDatetimeInput.value);

      // Verificar se a data é futura
      if (startDate <= now) {
        errors.push("A data e hora de início deve ser no futuro");
      } else {
        // Verificar tempo mínimo de antecedência
        const minTimeBefore = parseInt(startDatetimeInput.dataset.minTime || 0);
        if (minTimeBefore > 0) {
          const minDate = new Date();
          minDate.setHours(minDate.getHours() + minTimeBefore);

          if (startDate < minDate) {
            errors.push(
              `A data e hora de início deve ter pelo menos ${minTimeBefore} horas de antecedência`
            );
          }
        }
      }
    }

    // Validar data e hora de término
    if (endDatetimeInput && !endDatetimeInput.value) {
      errors.push("A data e hora de término são obrigatórias");
    } else if (
      endDatetimeInput &&
      startDatetimeInput &&
      endDatetimeInput.value &&
      startDatetimeInput.value
    ) {
      const startDate = new Date(startDatetimeInput.value);
      const endDate = new Date(endDatetimeInput.value);

      if (endDate <= startDate) {
        errors.push(
          "A data e hora de término deve ser posterior à data e hora de início"
        );
      }
    }

    // Validar recorrência
    const repeatType = document.querySelector(
      'input[name="repeat_type"]:checked'
    );
    if (repeatType && repeatType.value !== "none") {
      const repeatUntil = document.getElementById("repeat_until");

      if (repeatUntil && !repeatUntil.value) {
        errors.push(
          "Para eventos recorrentes, é necessário definir uma data final"
        );
      }

      if (repeatType.value === "specific_days") {
        const repeatDays = document.querySelectorAll(
          'input[name="repeat_days[]"]:checked'
        );
        if (repeatDays.length === 0) {
          errors.push(
            "Selecione pelo menos um dia da semana para a recorrência"
          );
        }
      }
    }

    return errors;
  }

  // Função para exibir erros
  function displayErrors(errors) {
    if (!errorList) return;

    // Limpar erros anteriores
    errorList.innerHTML = "";

    // Adicionar cada erro à lista
    errors.forEach(function (error) {
      const li = document.createElement("li");
      li.textContent = error;
      errorList.appendChild(li);
    });

    // Mostrar o contêiner de erros
    if (errorContainer) {
      errorContainer.style.display = "block";

      // Rolar até o topo do formulário para ver os erros
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  }

  // Função auxiliar para formatar data em formato datetime-local
  function formatDateTimeLocal(date) {
    return (
      date.getFullYear() +
      "-" +
      String(date.getMonth() + 1).padStart(2, "0") +
      "-" +
      String(date.getDate()).padStart(2, "0") +
      "T" +
      String(date.getHours()).padStart(2, "0") +
      ":" +
      String(date.getMinutes()).padStart(2, "0")
    );
  }
});
