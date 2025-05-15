document.addEventListener("DOMContentLoaded", function () {
  // Referências aos elementos do formulário
  const form = document.getElementById("compromisso-form");
  const startDatetimeInput = document.getElementById("start_datetime");
  const endDatetimeInput = document.getElementById("end_datetime");
  const errorContainer = document.getElementById("error-container");
  const errorList = document.getElementById("error-list");

  // IMPORTANTE: Desabilitar validação nativa do HTML5
  if (form) {
    form.setAttribute("novalidate", "novalidate");

    // Validar apenas quando o formulário for enviado
    form.addEventListener("submit", function (event) {
      // Impedir o envio para validar primeiro
      event.preventDefault();

      // Validar o formulário
      const errors = validateForm();

      if (errors.length > 0) {
        // Exibir erros
        displayErrors(errors);
      } else {
        // Se não houver erros, enviar o formulário
        form.submit();
      }
    });
  }

  // Função para formatar data como string para datetime-local
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

  // Função para sincronizar data de término quando data de início for completamente alterada
  // (sem validação durante a mudança)
  if (startDatetimeInput && endDatetimeInput) {
    startDatetimeInput.addEventListener("change", function () {
      if (!startDatetimeInput.value) return;

      try {
        const startDate = new Date(startDatetimeInput.value);
        let endDate = null;

        if (endDatetimeInput.value) {
          endDate = new Date(endDatetimeInput.value);
        }

        // Só atualizar o fim se estiver vazio ou for anterior ao início
        if (!endDatetimeInput.value || !endDate || endDate <= startDate) {
          const newEndDate = new Date(startDate);
          newEndDate.setHours(newEndDate.getHours() + 1);
          endDatetimeInput.value = formatDateTime(newEndDate);
        }
      } catch (e) {
        // Ignorar erros de formatação de data
        console.log("Erro ao processar data:", e);
      }
    });
  }

  // Função para validar formulário completo
  function validateForm() {
    const errors = [];

    // 1. Validar título
    const titleInput = document.getElementById("title");
    if (!titleInput || !titleInput.value.trim()) {
      errors.push("O título é obrigatório");
    }

    // 2. Validar data e hora de início
    if (!startDatetimeInput || !startDatetimeInput.value) {
      errors.push("A data e hora de início são obrigatórias");
    } else {
      try {
        const startDate = new Date(startDatetimeInput.value);
        const now = new Date();

        // Verificar se é uma data futura
        if (startDate <= now) {
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

    // 3. Validar data e hora de término
    if (!endDatetimeInput || !endDatetimeInput.value) {
      errors.push("A data e hora de término são obrigatórias");
    } else if (startDatetimeInput && startDatetimeInput.value) {
      try {
        const startDate = new Date(startDatetimeInput.value);
        const endDate = new Date(endDatetimeInput.value);

        if (endDate <= startDate) {
          errors.push(
            "A data e hora de término deve ser posterior à data e hora de início"
          );
        }
      } catch (e) {
        errors.push("Data de término inválida");
      }
    }

    // 4. Validar recorrência
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
    if (!errorList || !errorContainer) return;

    // Limpar erros anteriores
    errorList.innerHTML = "";

    // Adicionar novos erros
    errors.forEach(function (error) {
      const li = document.createElement("li");
      li.textContent = error;
      errorList.appendChild(li);
    });

    // Mostrar contêiner de erros
    errorContainer.style.display = "block";

    // Rolar para o topo do formulário
    window.scrollTo({
      top: form.offsetTop - 20,
      behavior: "smooth",
    });
  }
});
