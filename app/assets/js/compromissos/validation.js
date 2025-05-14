document.addEventListener("DOMContentLoaded", function () {
  const startDatetimeField = document.getElementById("start_datetime");
  const endDatetimeField = document.getElementById("end_datetime");
  const agendaIdField = document.querySelector('input[name="agenda_id"]');
  const submitButton = document.querySelector('button[type="submit"]');

  if (!startDatetimeField || !endDatetimeField || !agendaIdField) return;

  // Obter dados da agenda
  const agendaId = agendaIdField.value;
  const minTimeBefore = startDatetimeField.dataset.minTime || 0;
  const formElement = startDatetimeField.closest("form");

  // Definir data e hora atual
  const now = new Date();

  // Calcular data mínima com base na antecedência
  const minDate = new Date(now);
  if (minTimeBefore > 0) {
    minDate.setHours(minDate.getHours() + parseInt(minTimeBefore));
  }

  // Formatar para datetime-local
  const minDateString = formatDateTimeLocal(minDate);

  // Definir valor mínimo para os campos de data
  startDatetimeField.setAttribute("min", minDateString);

  // Verificar se estamos na página de edição
  const isEditPage = window.location.href.includes("/compromissos/edit");

  // Adicionar validação antes do envio do formulário
  if (formElement) {
    formElement.addEventListener("submit", function (event) {
      const isValid = validateAllDates();

      if (!isValid) {
        event.preventDefault();
      }
    });
  }

  // Eventos de alteração para cálculos, mas não alerta
  startDatetimeField.addEventListener("change", function () {
    // Ao alterar a data inicial, apenas atualiza o valor mínimo da data final
    updateEndDateMin();
  });

  endDatetimeField.addEventListener("change", function () {
    // Nada de alertas, apenas armazenar estado para validação no envio
  });

  // Função para validar todas as datas antes do envio
  function validateAllDates() {
    let isValid = true;
    const startValid = validateStartDate(true);

    if (!startValid) {
      isValid = false;
    } else {
      // Só validar data final se a inicial estiver correta
      const endValid = validateEndDate(true);
      if (!endValid) {
        isValid = false;
      }
    }

    return isValid;
  }

  // Validar data de início
  function validateStartDate(showAlert = false) {
    const startValue = startDatetimeField.value;
    if (!startValue) return true; // Validação vazia é responsabilidade do HTML required

    const startDate = new Date(startValue);

    // Verificar se a data está no futuro
    if (startDate <= now) {
      if (showAlert) {
        alert("A data e hora de início deve ser no futuro");
      }
      startDatetimeField.setCustomValidity(
        "A data e hora de início deve ser no futuro"
      );
      return false;
    }

    // Verificar tempo mínimo de antecedência (apenas para criar novos compromissos)
    if (!isEditPage && minTimeBefore > 0 && startDate < minDate) {
      if (showAlert) {
        alert(
          `A data e hora de início deve ter pelo menos ${minTimeBefore} horas de antecedência`
        );
      }
      startDatetimeField.setCustomValidity(
        `A data de início deve ter pelo menos ${minTimeBefore} horas de antecedência`
      );
      return false;
    }

    startDatetimeField.setCustomValidity("");
    return true;
  }

  // Atualizar valor mínimo da data de término
  function updateEndDateMin() {
    const startValue = startDatetimeField.value;
    if (!startValue) return;

    // Data de início + 1 minuto como mínimo para término
    const startDate = new Date(startValue);
    const minEndDate = new Date(startDate);
    minEndDate.setMinutes(minEndDate.getMinutes() + 1);

    // Definir valor mínimo para data de término
    endDatetimeField.setAttribute("min", formatDateTimeLocal(minEndDate));
  }

  // Validar data de término
  function validateEndDate(showAlert = false) {
    const endValue = endDatetimeField.value;
    const startValue = startDatetimeField.value;

    if (!endValue || !startValue) return true; // Validação vazia é responsabilidade do HTML required

    const endDate = new Date(endValue);
    const startDate = new Date(startValue);

    // Verificar se o término é depois do início
    if (endDate <= startDate) {
      if (showAlert) {
        alert(
          "A data e hora de término deve ser posterior à data e hora de início"
        );
      }
      endDatetimeField.setCustomValidity(
        "A data de término deve ser posterior à data de início"
      );
      return false;
    }

    endDatetimeField.setCustomValidity("");
    return true;
  }

  // Formatar data para o formato datetime-local
  function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    let month = date.getMonth() + 1;
    let day = date.getDate();
    let hour = date.getHours();
    let minute = date.getMinutes();

    // Adicionar zeros à esquerda
    month = month < 10 ? "0" + month : month;
    day = day < 10 ? "0" + day : day;
    hour = hour < 10 ? "0" + hour : hour;
    minute = minute < 10 ? "0" + minute : minute;

    return `${year}-${month}-${day}T${hour}:${minute}`;
  }

  // Inicializar campos mínimos
  updateEndDateMin();
});
