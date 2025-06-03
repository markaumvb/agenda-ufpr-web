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

  // Obter tempo mínimo da agenda
  const agendaContainer = document.querySelector("[data-min-time-before]");
  const minTimeBefore = agendaContainer
    ? parseInt(agendaContainer.dataset.minTimeBefore || 0)
    : 0;

  // Calcular data mínima (agora + tempo mínimo)
  const now = new Date();
  const minDate = new Date(now.getTime() + minTimeBefore * 60 * 60 * 1000);

  // Configurar Flatpickr para data de início
  let startPicker = null;
  if (startDatetimeInput) {
    startPicker = flatpickr(startDatetimeInput, {
      enableTime: true,
      dateFormat: "d/m/Y H:i",
      altInput: true,
      altFormat: "d/m/Y H:i",
      time_24hr: true,
      minDate: minDate,
      locale: "pt",
      defaultDate: minDate,
      onChange: function (selectedDates, dateStr, instance) {
        if (selectedDates.length > 0 && endPicker) {
          // Atualizar data de término (+ 1 hora)
          const endDate = new Date(selectedDates[0].getTime() + 60 * 60 * 1000);
          endPicker.setDate(endDate);
        }
        hideErrors();
      },
    });
  }

  // Configurar Flatpickr para data de término
  let endPicker = null;
  if (endDatetimeInput) {
    // Data padrão de término (data mínima + 1 hora)
    const defaultEndDate = new Date(minDate.getTime() + 60 * 60 * 1000);

    endPicker = flatpickr(endDatetimeInput, {
      enableTime: true,
      dateFormat: "d/m/Y H:i",
      altInput: true,
      altFormat: "d/m/Y H:i",
      time_24hr: true,
      minDate: minDate,
      locale: "pt",
      defaultDate: defaultEndDate,
      onChange: function () {
        hideErrors();
      },
    });
  }

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
        // Converter datas para formato MySQL antes de enviar
        if (startPicker && startPicker.selectedDates.length > 0) {
          const startDate = startPicker.selectedDates[0];
          startDatetimeInput.value = formatDateTimeForServer(startDate);
        }

        if (endPicker && endPicker.selectedDates.length > 0) {
          const endDate = endPicker.selectedDates[0];
          endDatetimeInput.value = formatDateTimeForServer(endDate);
        }

        // Remove o listener para evitar loop e submete o formulário
        form.removeEventListener("submit", arguments.callee);
        form.submit();
      }
    },
    true
  );

  // Função para formatar data para o servidor (MySQL datetime)
  function formatDateTimeForServer(date) {
    return (
      date.getFullYear() +
      "-" +
      String(date.getMonth() + 1).padStart(2, "0") +
      "-" +
      String(date.getDate()).padStart(2, "0") +
      " " +
      String(date.getHours()).padStart(2, "0") +
      ":" +
      String(date.getMinutes()).padStart(2, "0") +
      ":00"
    );
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
    if (!startPicker || !startPicker.selectedDates.length) {
      errors.push("A data e hora de início são obrigatórias");
    } else {
      const startDate = startPicker.selectedDates[0];
      const now = new Date();

      if (startDate <= now) {
        errors.push("A data e hora de início deve ser no futuro");
      }

      // Verificar antecedência mínima
      if (minTimeBefore > 0) {
        const requiredMinDate = new Date(
          now.getTime() + minTimeBefore * 60 * 60 * 1000
        );
        if (startDate < requiredMinDate) {
          errors.push(
            `A data e hora de início deve ter pelo menos ${minTimeBefore} horas de antecedência`
          );
        }
      }
    }

    // Validar data de término
    if (!endPicker || !endPicker.selectedDates.length) {
      errors.push("A data e hora de término são obrigatórias");
    } else if (startPicker && startPicker.selectedDates.length) {
      const startDate = startPicker.selectedDates[0];
      const endDate = endPicker.selectedDates[0];

      if (endDate <= startDate) {
        errors.push(
          "A data e hora de término deve ser posterior à data e hora de início"
        );
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
