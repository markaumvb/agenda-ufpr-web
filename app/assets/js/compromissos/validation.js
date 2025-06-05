document.addEventListener("DOMContentLoaded", function () {
  // Aguardar um pouco para garantir que a página carregou completamente
  setTimeout(function () {
    initializeForm();
  }, 200);

  function initializeForm() {
    // Encontrar APENAS o formulário principal de edição/criação
    let form = document.getElementById("compromisso-form");
    if (!form) {
      form = document.querySelector(".compromisso-form");
    }

    // IMPORTANTE: Não interferir com formulários de exclusão
    if (
      !form ||
      form.classList.contains("delete-form-individual") ||
      form.classList.contains("delete-form-future") ||
      form.classList.contains("cancel-form-all")
    ) {
      console.log("❌ Formulário de exclusão detectado - validação ignorada");
      return;
    }

    console.log("✅ Formulário principal encontrado:", form);

    // DESABILITAR VALIDAÇÃO NATIVA DE FORMA BRUTAL
    form.setAttribute("novalidate", "novalidate");
    form.noValidate = true;

    // Elementos de erro
    const errorContainer = document.getElementById("error-container");
    const errorList = document.getElementById("error-list");

    // INTERCEPTAR E CANCELAR QUALQUER EVENTO DE VALIDAÇÃO
    const eventsToBlock = ["invalid", "oninvalid"];

    eventsToBlock.forEach((eventType) => {
      form.addEventListener(
        eventType,
        function (e) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();
          return false;
        },
        true
      );
    });

    // REMOVER VALIDAÇÃO DE TODOS OS INPUTS DE FORMA AGRESSIVA
    const allInputs = form.querySelectorAll("input, textarea, select");
    allInputs.forEach((input) => {
      // Remover todos os atributos de validação
      const validationAttrs = [
        "required",
        "pattern",
        "min",
        "max",
        "step",
        "minlength",
        "maxlength",
      ];
      validationAttrs.forEach((attr) => input.removeAttribute(attr));

      // Desabilitar validação customizada
      input.setCustomValidity("");

      // BLOQUEAR EVENTOS DE VALIDAÇÃO EM CADA INPUT
      eventsToBlock.forEach((eventType) => {
        input.addEventListener(
          eventType,
          function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
          },
          true
        );
      });

      // Para inputs datetime-local, interceptar mudanças de valor
      if (input.type === "datetime-local") {
        // Interceptar TODOS os eventos que podem disparar validação
        ["input", "change", "blur", "keyup", "keydown", "focus"].forEach(
          (eventType) => {
            input.addEventListener(eventType, function (e) {
              // NÃO fazer validação, apenas limpar erros se existirem
              if (errorContainer && errorContainer.style.display === "block") {
                errorContainer.style.display = "none";
              }

              // Forçar setCustomValidity vazio para evitar validação nativa
              input.setCustomValidity("");
            });
          }
        );
      }
    });

    // CONFIGURAR SINCRONIZAÇÃO DE DATAS SEM VALIDAÇÃO
    const startInput = document.getElementById("start_datetime");
    const endInput = document.getElementById("end_datetime");

    if (startInput && endInput) {
      startInput.addEventListener("change", function () {
        if (!startInput.value) return;

        try {
          const startDate = new Date(startInput.value);

          // Sincronizar apenas se o campo final estiver vazio
          if (!endInput.value && !isNaN(startDate.getTime())) {
            const endDate = new Date(startDate);
            endDate.setHours(endDate.getHours() + 1);
            endInput.value = formatDateTime(endDate);
          }
        } catch (e) {}
      });
    }

    // INTERCEPTAR SUBMIT APENAS PARA O FORMULÁRIO PRINCIPAL
    form.addEventListener(
      "submit",
      function (e) {
        // IMPORTANTE: Não interceptar formulários de exclusão
        if (
          e.target.classList.contains("delete-form-individual") ||
          e.target.classList.contains("delete-form-future") ||
          e.target.classList.contains("cancel-form-all") ||
          e.target.action.includes("/delete") ||
          e.target.action.includes("/cancel-future")
        ) {
          console.log("🗑️ Formulário de exclusão - não interceptar");
          return true; // Deixar enviar normalmente
        }

        e.preventDefault();
        e.stopPropagation();

        // Executar validação customizada
        const errors = validateFormData();

        if (errors.length > 0) {
          showErrors(errors);
        } else {
          // Enviar formulário sem validação
          submitFormSafely();
        }
      },
      true
    );

    // FUNÇÃO DE VALIDAÇÃO (executada apenas no submit)
    function validateFormData() {
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

    // FUNÇÃO PARA EXIBIR ERROS
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

    // FUNÇÃO PARA ENVIAR FORMULÁRIO COM SEGURANÇA
    function submitFormSafely() {
      // Criar um formulário temporário limpo
      const tempForm = document.createElement("form");
      tempForm.action = form.action;
      tempForm.method = form.method;
      tempForm.style.display = "none";

      // Copiar todos os dados para o formulário temporário
      const formData = new FormData(form);
      for (let [key, value] of formData.entries()) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = value;
        tempForm.appendChild(input);
      }

      document.body.appendChild(tempForm);
      tempForm.submit();
    }

    // FUNÇÃO AUXILIAR PARA FORMATAR DATA
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
