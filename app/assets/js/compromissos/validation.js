document.addEventListener("DOMContentLoaded", function () {
  console.log("🚀 Validation.js Ultra-Defensivo carregado");

  // AGUARDAR UM POUCO PARA GARANTIR QUE OUTROS SCRIPTS CARREGARAM
  setTimeout(function () {
    initializeValidation();
  }, 100);

  function initializeValidation() {
    // Buscar formulário por id primeiro, depois por classe como fallback
    let form = document.getElementById("compromisso-form");
    if (!form) {
      form = document.querySelector(".compromisso-form");
      console.log("📝 Formulário encontrado por classe");
    } else {
      console.log("📝 Formulário encontrado por id");
    }

    if (!form) {
      console.error("❌ Formulário não encontrado!");
      return;
    }

    const startDatetimeInput = document.getElementById("start_datetime");
    const endDatetimeInput = document.getElementById("end_datetime");
    const errorContainer = document.getElementById("error-container");
    const errorList = document.getElementById("error-list");

    console.log("🔧 Iniciando desabilitação agressiva da validação nativa...");

    // ULTRA-CRÍTICO: Desabilitar TODAS as validações nativas de forma agressiva
    form.setAttribute("novalidate", "novalidate");
    form.noValidate = true;

    // REMOVER TODOS OS ATRIBUTOS DE VALIDAÇÃO DE TODOS OS INPUTS
    const allInputs = form.querySelectorAll("input, textarea, select");
    allInputs.forEach((input) => {
      // Remover atributos de validação
      input.removeAttribute("required");
      input.removeAttribute("pattern");
      input.removeAttribute("min");
      input.removeAttribute("max");
      input.removeAttribute("step");
      input.removeAttribute("minlength");
      input.removeAttribute("maxlength");

      // Desabilitar validação customizada do HTML5
      input.setCustomValidity("");

      // Forçar noValidate
      if (input.form) {
        input.form.noValidate = true;
      }

      console.log(
        "🧹 Limpeza de validação para:",
        input.name || input.id || input.type
      );
    });

    // REMOVER TODOS OS EVENT LISTENERS EXISTENTES DE VALIDAÇÃO
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    form = newForm;

    // RECRIAR REFERÊNCIAS DOS INPUTS APÓS CLONAGEM
    const newStartInput = document.getElementById("start_datetime");
    const newEndInput = document.getElementById("end_datetime");

    console.log("🛡️ Interceptando submit com máxima prioridade...");

    // INTERCEPTAR SUBMIT COM MÁXIMA PRIORIDADE
    form.addEventListener(
      "submit",
      function (event) {
        console.log("🛑 Submit interceptado - validação customizada");

        // FORÇA STOP EM TUDO
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        // Validar o formulário
        const errors = validateForm();

        if (errors.length > 0) {
          console.log("❌ Erros encontrados:", errors);
          displayErrors(errors);
          return false;
        } else {
          console.log("✅ Formulário válido, enviando...");
          // Criar um novo formulário temporário para envio
          const tempForm = document.createElement("form");
          tempForm.action = form.action;
          tempForm.method = form.method;
          tempForm.style.display = "none";

          // Copiar todos os dados
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
          return false;
        }
      },
      true
    ); // Capture = true para interceptar antes

    // FUNÇÃO PARA SINCRONIZAR DATAS SEM VALIDAÇÃO
    if (newStartInput && newEndInput) {
      // REMOVER QUALQUER EVENT LISTENER DE VALIDAÇÃO DOS INPUTS
      ["input", "change", "blur", "invalid", "keyup"].forEach((eventType) => {
        newStartInput.addEventListener(
          eventType,
          function (e) {
            e.stopPropagation();
            // Limpar mensagens de erro
            if (errorContainer) {
              errorContainer.style.display = "none";
            }
          },
          true
        );

        newEndInput.addEventListener(
          eventType,
          function (e) {
            e.stopPropagation();
            // Limpar mensagens de erro
            if (errorContainer) {
              errorContainer.style.display = "none";
            }
          },
          true
        );
      });

      // Sincronização inteligente apenas no change
      newStartInput.addEventListener("change", function () {
        if (!newStartInput.value) return;

        try {
          const startDate = new Date(newStartInput.value);

          // Apenas sincronizar data de término se estiver vazia
          if (!newEndInput.value) {
            const newEndDate = new Date(startDate);
            newEndDate.setHours(newEndDate.getHours() + 1);
            newEndInput.value = formatDateTime(newEndDate);
          }
        } catch (e) {
          console.log("⚠️ Erro ao processar data:", e);
        }
      });
    }

    // Função para formatar data
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

    // Função para validar formulário completo
    function validateForm() {
      const errors = [];

      // 1. Validar título
      const titleInput = document.getElementById("title");
      if (!titleInput || !titleInput.value.trim()) {
        errors.push("O título é obrigatório");
      }

      // 2. Validar data e hora de início
      if (!newStartInput || !newStartInput.value) {
        errors.push("A data e hora de início são obrigatórias");
      } else {
        try {
          const startDate = new Date(newStartInput.value);
          const now = new Date();

          if (isNaN(startDate.getTime())) {
            errors.push("Data de início inválida");
          } else if (startDate <= now) {
            errors.push("A data e hora de início deve ser no futuro");
          } else {
            // Verificar antecedência mínima
            const minTimeBefore = parseInt(newStartInput.dataset.minTime || 0);
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
      if (!newEndInput || !newEndInput.value) {
        errors.push("A data e hora de término são obrigatórias");
      } else if (newStartInput && newStartInput.value) {
        try {
          const startDate = new Date(newStartInput.value);
          const endDate = new Date(newEndInput.value);

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
        console.error("❌ Containers de erro não encontrados");
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
  }
});
