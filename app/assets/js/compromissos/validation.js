document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
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
  startDatetimeInput.addEventListener("change", function () {
    // Apenas atualizar a data de término se estiver vazia ou for anterior à data de início
    const startValue = new Date(startDatetimeInput.value);
    let endValue = new Date(endDatetimeInput.value);

    if (!endDatetimeInput.value || endValue <= startValue) {
      // Adicionar 1 hora à data de início
      const newEndDate = new Date(startValue);
      newEndDate.setHours(newEndDate.getHours() + 1);

      // Formatar a nova data de término para o formato datetime-local
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

  // Função para validar o formulário
  function validateForm() {
    const errors = [];
    const title = document.getElementById("title").value.trim();
    const startDatetime = startDatetimeInput.value;
    const endDatetime = endDatetimeInput.value;

    // Validar título
    if (!title) {
      errors.push("O título é obrigatório");
    }

    // Validar data e hora de início
    if (!startDatetime) {
      errors.push("A data e hora de início são obrigatórias");
    } else {
      const now = new Date();
      const startDate = new Date(startDatetime);

      // Verificar se a data é futura
      if (startDate <= now) {
        errors.push("A data e hora de início deve ser no futuro");
      }

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

    // Validar data e hora de término
    if (!endDatetime) {
      errors.push("A data e hora de término são obrigatórias");
    } else if (startDatetime) {
      const startDate = new Date(startDatetime);
      const endDate = new Date(endDatetime);

      if (endDate <= startDate) {
        errors.push(
          "A data e hora de término deve ser posterior à data e hora de início"
        );
      }
    }

    // Validar recorrência
    const repeatType = document.querySelector(
      'input[name="repeat_type"]:checked'
    ).value;
    if (repeatType !== "none") {
      const repeatUntil = document.getElementById("repeat_until").value;

      if (!repeatUntil) {
        errors.push(
          "Para eventos recorrentes, é necessário definir uma data final"
        );
      }

      if (repeatType === "specific_days") {
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
  }
});
