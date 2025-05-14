/**
 * Validação de formulários de compromisso
 * app/assets/js/compromissos/validation.js
 */
document.addEventListener("DOMContentLoaded", function () {
  const startDatetimeField = document.getElementById("start_datetime");
  const endDatetimeField = document.getElementById("end_datetime");
  const agendaIdField = document.querySelector('input[name="agenda_id"]');

  if (!startDatetimeField || !endDatetimeField || !agendaIdField) return;

  // Obter dados da agenda
  const agendaId = agendaIdField.value;
  const minTimeBefore = startDatetimeField.dataset.minTime || 0;

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

  // Eventos de validação
  startDatetimeField.addEventListener("change", function () {
    validateStartDate();
    updateEndDateMin();
  });

  endDatetimeField.addEventListener("change", validateEndDate);

  // Validar data de início
  function validateStartDate() {
    const startValue = startDatetimeField.value;
    if (!startValue) return;

    const startDate = new Date(startValue);

    // Verificar se a data está no futuro
    if (startDate <= now) {
      alert("A data e hora de início deve ser no futuro");
      startDatetimeField.value = formatDateTimeLocal(minDate);
      return false;
    }

    // Verificar tempo mínimo de antecedência
    if (minTimeBefore > 0 && startDate < minDate) {
      alert(
        `A data e hora de início deve ter pelo menos ${minTimeBefore} horas de antecedência`
      );
      startDatetimeField.value = formatDateTimeLocal(minDate);
      return false;
    }

    return true;
  }

  // Atualizar valor mínimo da data de término
  function updateEndDateMin() {
    const startValue = startDatetimeField.value;
    if (!startValue) return;

    // Data de início + 30 minutos como mínimo para término
    const startDate = new Date(startValue);
    const minEndDate = new Date(startDate);
    minEndDate.setMinutes(minEndDate.getMinutes() + 30);

    // Definir valor mínimo para data de término
    endDatetimeField.setAttribute("min", formatDateTimeLocal(minEndDate));

    // Se a data de término for menor que a nova data mínima, ajustá-la
    const endDate = new Date(endDatetimeField.value);
    if (endDate < minEndDate) {
      endDatetimeField.value = formatDateTimeLocal(minEndDate);
    }
  }

  // Validar data de término
  function validateEndDate() {
    const endValue = endDatetimeField.value;
    const startValue = startDatetimeField.value;

    if (!endValue || !startValue) return;

    const endDate = new Date(endValue);
    const startDate = new Date(startValue);

    // Verificar se o término é depois do início
    if (endDate <= startDate) {
      alert(
        "A data e hora de término deve ser posterior à data e hora de início"
      );

      // Definir para data de início + 1 hora
      const suggestedEndDate = new Date(startDate);
      suggestedEndDate.setHours(suggestedEndDate.getHours() + 1);

      endDatetimeField.value = formatDateTimeLocal(suggestedEndDate);
      return false;
    }

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

  // Verificação inicial
  validateStartDate();
  updateEndDateMin();
});
