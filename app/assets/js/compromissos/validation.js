document.addEventListener("DOMContentLoaded", function () {
  protectDeleteForms();

  setTimeout(function () {
    initializeForm();
  }, 300);

  // FUNÇÃO PARA PROTEGER FORMULÁRIOS DE EXCLUSÃO
  function protectDeleteForms() {
    const deleteFormSelectors = [
      ".delete-form-individual",
      ".delete-form-future",
      ".cancel-form-all",
      'form[action*="/delete"]',
      'form[action*="/cancel-future"]',
    ];

    deleteFormSelectors.forEach((selector) => {
      const forms = document.querySelectorAll(selector);
      forms.forEach((form) => {
        // Marcar formulário como protegido
        form.setAttribute("data-protected", "true");
        form.setAttribute(
          "data-original-onsubmit",
          form.getAttribute("onsubmit") || ""
        );

        console.log("🛡️ Formulário de exclusão protegido:", form.className);
      });
    });
  }

  function initializeForm() {
    // Encontrar APENAS o formulário principal de edição/criação
    let form = document.getElementById("compromisso-form");
    if (!form) {
      form = document.querySelector(".compromisso-form");
    }

    // IMPORTANTE: Não interferir com formulários de exclusão
    if (!form) {
      console.log("❌ Formulário principal não encontrado");
      return;
    }

    // VERIFICAÇÃO: Se é um formulário protegido, não aplicar validação
    if (form.hasAttribute("data-protected")) {
      console.log("❌ Formulário protegido detectado - validação ignorada");
      return;
    }

    // VERIFICAÇÃO EXTRA: Se tem action de delete, não aplicar validação
    if (
      form.action &&
      (form.action.includes("/delete") || form.action.includes("/cancel"))
    ) {
      console.log("❌ Formulário com action de exclusão - validação ignorada");
      return;
    }

    console.log("✅ Formulário principal encontrado:", form);

    // DESABILITAR VALIDAÇÃO NATIVA
    form.setAttribute("novalidate", "novalidate");
    form.noValidate = true;

    // Elementos de erro
    const errorContainer = document.getElementById("error-container");
    const errorList = document.getElementById("error-list");

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
    form.addEventListener("submit", function (e) {
      // VERIFICAÇÃO TRIPLA: Garantir que é o formulário correto
      if (e.target !== form) {
        return; // Não interceptar
      }

      if (e.target.hasAttribute("data-protected")) {
        return; // Não interceptar formulários protegidos
      }

      if (
        e.target.action &&
        (e.target.action.includes("/delete") ||
          e.target.action.includes("/cancel"))
      ) {
        return; // Não interceptar ações de exclusão
      }

      console.log(
        "✅ Interceptando submit do formulário principal para validação"
      );

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
    });

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

  // DELEGAÇÃO DE EVENTOS PARA FORMULÁRIOS DE EXCLUSÃO - GARANTIR QUE FUNCIONEM
  document.addEventListener("DOMContentLoaded", function () {
    // Aguardar que todos os scripts carreguem
    setTimeout(function () {
      // Garantir que os formulários de exclusão funcionem
      const deleteforms = document.querySelectorAll(
        ".delete-form-individual, .delete-form-future, .cancel-form-all"
      );

      deleteforms.forEach(function (deleteForm) {
        // Remover qualquer event listener que possa ter sido adicionado
        const newForm = deleteForm.cloneNode(true);
        deleteForm.parentNode.replaceChild(newForm, deleteForm);

        console.log("✅ Formulário de exclusão limpo:", newForm.className);
      });
    }, 500);
  });
});
