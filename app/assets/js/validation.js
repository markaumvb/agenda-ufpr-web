document.addEventListener("DOMContentLoaded", function () {
  // Buscar formulário
  let form = document.getElementById("compromisso-form");
  if (!form) {
    form = document.querySelector(".compromisso-form");
  }

  if (!form) {
    return;
  }

  const startDatetimeInput = document.getElementById("start_datetime");
  const endDatetimeInput = document.getElementById("end_datetime");
  const errorContainer = document.getElementById("error-container");
  const errorList = document.getElementById("error-list");

  // Desabilitar validação HTML5 nativa
  form.setAttribute("novalidate", "novalidate");

  // Remover required de todos os inputs para evitar validação nativa
  const allInputs = form.querySelectorAll("input, textarea, select");
  allInputs.forEach((input) => {
    if (input.hasAttribute("required")) {
      input.removeAttribute("required");
      input.setAttribute("data-was-required", "true");
    }
    input.setCustomValidity("");
  });

  // Interceptar submit do formulário
  form.addEventListener(
    "submit",
    function (event) {
      event.preventDefault();
      event.stopPropagation();

      const errors = validateForm();

      if (errors.length > 0) {
        displayErrors(errors);
      } else {
        // Remove o listener para evitar loop e submete o formulário
        form.removeEventListener("submit", arguments.callee);
        form.submit();
      }
    },
    true
  );

  // Função para formatar data para datetime-local
  function formatDateTime(date) {
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

  // Configurar sincronização de datas
  if (startDatetimeInput && endDatetimeInput) {
    // Sincronizar data de término quando sair do campo de início (apenas se tiver valor completo)
    startDatetimeInput.addEventListener("blur", function () {
      // Só sincroniza se o valor estiver completo e válido
      if (!startDatetimeInput.value || startDatetimeInput.value.length < 16)
        return;

      try {
        const startDate = new Date(startDatetimeInput.value);

        if (!isNaN(startDate.getTime()) && !endDatetimeInput.value) {
          const newEndDate = new Date(startDate);
          newEndDate.setHours(newEndDate.getHours() + 1);
          endDatetimeInput.value = formatDateTime(newEndDate);
        }
      } catch (e) {
        // Ignorar erros de conversão de data durante digitação
      }
    });

    // Limpar erros quando começar a digitar
    startDatetimeInput.addEventListener("focus", function () {
      hideErrors();
    });

    endDatetimeInput.addEventListener("focus", function () {
      hideErrors();
    });
  }

  // Função para validar todo o formulário
  function validateForm() {
    const errors = [];

    // Validar título
    const titleInput = document.getElementById("title");
    if (!titleInput || !titleInput.value.trim()) {
      errors.push("O título é obrigatório");
    }

    // Validar data de início
    if (!startDatetimeInput || !startDatetimeInput.value) {
      errors.push("A data e hora de início são obrigatórias");
    } else {
      try {
        const startDate = new Date(startDatetimeInput.value);
        const now = new Date();

        if (isNaN(startDate.getTime())) {
          errors.push("Data de início inválida");
        } else if (startDate <= now) {
          errors.push("A data e hora de início deve ser no futuro");
        } else {
          // Verificar antecedência mínima
          const minTimeBefore = parseInt(
            startDatetimeInput.dataset.minTime || 0
          );
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
      } catch (e) {
        errors.push("Data de início inválida");
      }
    }

    // Validar data de término
    if (!endDatetimeInput || !endDatetimeInput.value) {
      errors.push("A data e hora de término são obrigatórias");
    } else if (startDatetimeInput && startDatetimeInput.value) {
      try {
        const startDate = new Date(startDatetimeInput.value);
        const endDate = new Date(endDatetimeInput.value);

        if (isNaN(endDate.getTime())) {
          errors.push("Data de término inválida");
        } else if (!isNaN(startDate.getTime()) && endDate <= startDate) {
          errors.push(
            "A data e hora de término deve ser posterior à data e hora de início"
          );
        }
      } catch (e) {
        errors.push("Data de término inválida");
      }
    }

    // Validar recorrência
    const repeatTypeInputs = document.querySelectorAll(
      'input[name="repeat_type"]'
    );
    let selectedRepeatType = "none";

    repeatTypeInputs.forEach((input) => {
      if (input.checked) {
        selectedRepeatType = input.value;
      }
    });

    if (selectedRepeatType !== "none") {
      const repeatUntilInput = document.getElementById("repeat_until");

      if (!repeatUntilInput || !repeatUntilInput.value) {
        errors.push(
          "Para eventos recorrentes, é necessário definir uma data final"
        );
      }

      if (selectedRepeatType === "specific_days") {
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
    if (!errorList || !errorContainer) {
      alert("Erros encontrados:\n" + errors.join("\n"));
      return;
    }

    errorList.innerHTML = "";

    errors.forEach(function (error) {
      const li = document.createElement("li");
      li.textContent = error;
      errorList.appendChild(li);
    });

    errorContainer.style.display = "block";

    // Rolar para o topo
    window.scrollTo({
      top: form.offsetTop - 20,
      behavior: "smooth",
    });
  }

  // Função para esconder erros
  function hideErrors() {
    if (errorContainer) {
      errorContainer.style.display = "none";
    }
  }
});
