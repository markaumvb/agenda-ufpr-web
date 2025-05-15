document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("compromisso-form");
  if (!form) return; // Sair se o formulário não for encontrado

  const startDatetimeInput = document.getElementById("start_datetime");
  const endDatetimeInput = document.getElementById("end_datetime");
  const errorContainer = document.getElementById("error-container");
  const errorList = document.getElementById("error-list");

  // Função para validar o formulário apenas no envio
  form.addEventListener("submit", function (event) {
    const errors = validateForm();

    if (errors.length > 0) {
      // Impedir o envio do formulário se houver erros
      event.preventDefault();

      // Exibir erros
      displayErrors(errors);
    }
  });

  // Atualizar automaticamente a data de término quando a data de início mudar
  if (startDatetimeInput) {
    startDatetimeInput.addEventListener("change", function () {
      if (!startDatetimeInput.value) return;

      // Calcular nova data de término (1 hora após o início)
      const startValue = new Date(startDatetimeInput.value);
      let endValue = endDatetimeInput.value
        ? new Date(endDatetimeInput.value)
        : null;

      // Atualizar data de término se vazia ou anterior à data de início
      if (!endDatetimeInput.value || !endValue || endValue <= startValue) {
        const newEndDate = new Date(startValue);
        newEndDate.setHours(newEndDate.getHours() + 1);

        // Formatar para datetime-local (YYYY-MM-DDThh:mm)
        const year = newEndDate.getFullYear();
        const month = String(newEndDate.getMonth() + 1).padStart(2, "0");
        const day = String(newEndDate.getDate()).padStart(2, "0");
        const hours = String(newEndDate.getHours()).padStart(2, "0");
        const minutes = String(newEndDate.getMinutes()).padStart(2, "0");

        endDatetimeInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
      }

      // Limpar mensagens de erro
      clearErrors();
    });
  }

  // Limpar erros relacionados à data final quando a data final mudar
  if (endDatetimeInput) {
    endDatetimeInput.addEventListener("change", function () {
      // Verificar se existem erros relativos à data final e removê-los
      if (errorContainer.style.display !== "none") {
        // Buscar através de todos os itens da lista de erros
        const items = errorList.querySelectorAll("li");
        let hasEndDateError = false;

        items.forEach((item) => {
          if (item.textContent.includes("data e hora de término")) {
            item.remove();
            hasEndDateError = true;
          }
        });

        // Se não houver mais erros, esconder o contêiner
        if (errorList.querySelectorAll("li").length === 0) {
          clearErrors();
        }

        // Se encontrou e removeu erros, marcar o campo como válido
        if (hasEndDateError) {
          endDatetimeInput.classList.remove("field-error");
        }
      }
    });
  }

  // Função para validar o formulário
  function validateForm() {
    const errors = [];
    const title = document.getElementById("title").value.trim();
    const startDatetime = startDatetimeInput ? startDatetimeInput.value : "";
    const endDatetime = endDatetimeInput ? endDatetimeInput.value : "";

    // Validar título
    if (!title) {
      errors.push("O título é obrigatório");
      highlightField("title");
    } else {
      resetField("title");
    }

    // Validar data e hora de início
    if (!startDatetime) {
      errors.push("A data e hora de início são obrigatórias");
      highlightField("start_datetime");
    } else {
      const now = new Date();
      const startDate = new Date(startDatetime);

      // Verificar se a data é futura
      if (startDate <= now) {
        errors.push("A data e hora de início deve ser no futuro");
        highlightField("start_datetime");
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
            highlightField("start_datetime");
          } else {
            resetField("start_datetime");
          }
        } else {
          resetField("start_datetime");
        }
      }
    }

    // Validar data e hora de término
    if (!endDatetime) {
      errors.push("A data e hora de término são obrigatórias");
      highlightField("end_datetime");
    } else if (startDatetime) {
      const startDate = new Date(startDatetime);
      const endDate = new Date(endDatetime);

      if (endDate <= startDate) {
        errors.push(
          "A data e hora de término deve ser posterior à data e hora de início"
        );
        highlightField("end_datetime");
      } else {
        resetField("end_datetime");
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
        highlightField("repeat_until");
      } else {
        resetField("repeat_until");
      }

      if (repeatType.value === "specific_days") {
        const repeatDays = document.querySelectorAll(
          'input[name="repeat_days[]"]:checked'
        );
        if (repeatDays.length === 0) {
          errors.push(
            "Selecione pelo menos um dia da semana para a recorrência"
          );
          // Destacar os checkboxes é um pouco mais complicado, então vamos pular isso
        }
      }
    }

    return errors;
  }

  // Função para destacar um campo com erro
  function highlightField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
      field.classList.add("field-error");
    }
  }

  // Função para remover destaque de erro de um campo
  function resetField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
      field.classList.remove("field-error");
    }
  }

  // Função para exibir os erros
  function displayErrors(errors) {
    // Limpar lista de erros
    errorList.innerHTML = "";

    // Adicionar cada erro à lista
    errors.forEach(function (error) {
      const li = document.createElement("li");
      li.textContent = error;
      errorList.appendChild(li);
    });

    // Exibir o contêiner de erros
    errorContainer.style.display = "block";

    // Rolar até o topo do formulário
    window.scrollTo({ top: form.offsetTop - 20, behavior: "smooth" });
  }

  // Função para limpar os erros
  function clearErrors() {
    errorList.innerHTML = "";
    errorContainer.style.display = "none";

    // Remover destaque de todos os campos
    document.querySelectorAll(".field-error").forEach((field) => {
      field.classList.remove("field-error");
    });
  }
});
