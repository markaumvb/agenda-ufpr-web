document.addEventListener("DOMContentLoaded", function () {
  // Aguardar um pouco para garantir que a página carregou
  setTimeout(function () {
    initializeMainFormValidation();
  }, 100);

  function initializeMainFormValidation() {
    // Encontrar APENAS o formulário principal (update/create)
    const mainForm = document.querySelector(
      "form[action*='update'], form[action*='save']"
    );

    if (!mainForm) {
      return; // Não há formulário principal para validar
    }

    // Elementos de validação
    const errorContainer = document.getElementById("error-container");
    const errorList = document.getElementById("error-list");
    const startInput = document.getElementById("start_datetime");
    const endInput = document.getElementById("end_datetime");

    // Desabilitar validação HTML5 nativa apenas no formulário principal
    mainForm.setAttribute("novalidate", "novalidate");
    mainForm.noValidate = true;

    // Sincronização de datas
    if (startInput && endInput) {
      startInput.addEventListener("change", function () {
        if (!startInput.value) return;

        try {
          const startDate = new Date(startInput.value);
          if (!endInput.value && !isNaN(startDate.getTime())) {
            const endDate = new Date(startDate);
            endDate.setHours(endDate.getHours() + 1);
            endInput.value = formatDateTime(endDate);
          }
        } catch (e) {
          // Ignorar erros silenciosamente
        }
      });
    }

    // Interceptar submit APENAS do formulário principal
    mainForm.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const errors = validateMainForm();

      if (errors.length > 0) {
        showErrors(errors);
      } else {
        hideErrors();
        // Submeter o formulário de forma segura
        submitMainForm();
      }
    });

    // Função de validação
    function validateMainForm() {
      const errors = [];

      // Validar título
      const titleInput = document.getElementById("title");
      if (!titleInput || !titleInput.value.trim()) {
        errors.push("O título é obrigatório");
      }

      // Validar data de início
      if (!startInput || !startInput.value) {
        errors.push("A data e hora de início são obrigatórias");
      } else {
        const startDate = new Date(startInput.value);
        const now = new Date();

        if (isNaN(startDate.getTime())) {
          errors.push("Data de início inválida");
        } else if (startDate <= now) {
          errors.push("A data e hora de início devem ser posteriores");
        }
      }

      // Validar data de término
      if (!endInput || !endInput.value) {
        errors.push("A data e hora de término são obrigatórias");
      } else if (startInput && startInput.value) {
        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);

        if (isNaN(endDate.getTime())) {
          errors.push("Data de término inválida");
        } else if (!isNaN(startDate.getTime()) && endDate <= startDate) {
          errors.push(
            "A data e hora de término deve ser posterior à data e hora de início"
          );
        }
      }

      // Validar recorrência
      const repeatType =
        document.querySelector('input[name="repeat_type"]:checked')?.value ||
        "none";

      if (repeatType !== "none") {
        const repeatUntilInput = document.getElementById("repeat_until");

        if (!repeatUntilInput || !repeatUntilInput.value) {
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

    // Função para exibir erros
    function showErrors(errors) {
      if (!errorContainer || !errorList) {
        alert("Erros encontrados:\n" + errors.join("\n"));
        return;
      }

      errorList.innerHTML = "";
      errors.forEach((error) => {
        const li = document.createElement("li");
        li.textContent = error;
        errorList.appendChild(li);
      });

      errorContainer.style.display = "block";
      errorContainer.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    // Função para esconder erros
    function hideErrors() {
      if (errorContainer) {
        errorContainer.style.display = "none";
      }
    }

    // Função para submeter o formulário principal
    function submitMainForm() {
      // Remover o event listener temporariamente para evitar loop
      const tempForm = mainForm.cloneNode(true);

      // Copiar todos os dados do formulário original
      const formData = new FormData(mainForm);

      // Criar formulário temporário para submissão
      const submitForm = document.createElement("form");
      submitForm.action = mainForm.action;
      submitForm.method = mainForm.method;
      submitForm.style.display = "none";

      // Adicionar todos os dados como campos hidden
      for (let [key, value] of formData.entries()) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = value;
        submitForm.appendChild(input);
      }

      document.body.appendChild(submitForm);
      submitForm.submit();
    }

    // Função auxiliar para formatar data
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
  }
});
