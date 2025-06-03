document.addEventListener("DOMContentLoaded", function () {
  console.log("Validation.js carregado");

  // Buscar formulário por id primeiro, depois por classe como fallback
  let form = document.getElementById("compromisso-form");
  if (!form) {
    form = document.querySelector(".compromisso-form");
    console.log("Formulário encontrado por classe");
  } else {
    console.log("Formulário encontrado por id");
  }

  if (!form) {
    console.error("Formulário não encontrado!");
    return;
  }

  const startDatetimeInput = document.getElementById("start_datetime");
  const endDatetimeInput = document.getElementById("end_datetime");
  const errorContainer = document.getElementById("error-container");
  const errorList = document.getElementById("error-list");

  // CRÍTICO: Desabilitar todas as validações nativas
  form.setAttribute("novalidate", "novalidate");

  // Remover qualquer atributo required de todos os inputs
  const allInputs = form.querySelectorAll("input, textarea, select");
  allInputs.forEach((input) => {
    if (input.hasAttribute("required")) {
      input.removeAttribute("required");
      input.setAttribute("data-was-required", "true");
      console.log("Removido required de:", input.name || input.id);
    }

    // Remover validação customizada do HTML5
    input.setCustomValidity("");
  });

  // Interceptar submit ANTES de qualquer validação
  form.addEventListener(
    "submit",
    function (event) {
      console.log("Submit interceptado");

      // SEMPRE prevenir o submit primeiro
      event.preventDefault();
      event.stopPropagation();

      // Validar o formulário
      const errors = validateForm();

      if (errors.length > 0) {
        console.log("Erros encontrados:", errors);
        displayErrors(errors);
      } else {
        console.log("Formulário válido, enviando...");
        // Remove o listener para evitar loop infinito
        form.removeEventListener("submit", arguments.callee);
        form.submit();
      }
    },
    true
  ); // Usar capture para interceptar antes

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

  // Sincronização de datas SEM validação
  if (startDatetimeInput && endDatetimeInput) {
    startDatetimeInput.addEventListener("input", function () {
      // Limpar mensagens de erro ao digitar
      if (errorContainer) {
        errorContainer.style.display = "none";
      }
    });

    startDatetimeInput.addEventListener("change", function () {
      if (!startDatetimeInput.value) return;

      try {
        const startDate = new Date(startDatetimeInput.value);

        // Apenas sincronizar data de término se estiver vazia
        if (!endDatetimeInput.value) {
          const newEndDate = new Date(startDate);
          newEndDate.setHours(newEndDate.getHours() + 1);
          endDatetimeInput.value = formatDateTime(newEndDate);
        }
      } catch (e) {
        console.log("Erro ao processar data:", e);
      }
    });

    endDatetimeInput.addEventListener("input", function () {
      // Limpar mensagens de erro ao digitar
      if (errorContainer) {
        errorContainer.style.display = "none";
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

    // 3. Validar data e hora de término
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
    if (!errorList || !errorContainer) {
      console.error("Containers de erro não encontrados");
      alert("Erros encontrados:\n" + errors.join("\n"));
      return;
    }

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

  console.log("Validation.js inicializado com sucesso");
});
