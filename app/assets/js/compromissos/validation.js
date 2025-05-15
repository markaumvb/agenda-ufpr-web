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

  // Verificar se estamos na página de edição
  const isEditPage = window.location.href.includes("/compromissos/edit");

  // REMOVER TODOS OS ATRIBUTOS HTML DE VALIDAÇÃO DOS CAMPOS DE DATA
  // Isso evita que o navegador tente validar durante a digitação
  startDatetimeField.removeAttribute("min");
  startDatetimeField.removeAttribute("max");
  endDatetimeField.removeAttribute("min");
  endDatetimeField.removeAttribute("max");

  // Adicionar validação APENAS no envio do formulário
  if (formElement) {
    formElement.addEventListener("submit", function (event) {
      // Verificar todas as validações apenas no envio
      const now = new Date();
      const startDate = new Date(startDatetimeField.value);
      const endDate = new Date(endDatetimeField.value);

      // 1. Verificar se a data de início está no futuro
      if (startDate <= now) {
        event.preventDefault();
        alert("A data e hora de início deve ser no futuro");
        return;
      }

      // 2. Verificar tempo mínimo de antecedência (apenas para novos compromissos)
      if (!isEditPage && minTimeBefore > 0) {
        const minDate = new Date(now);
        minDate.setHours(minDate.getHours() + parseInt(minTimeBefore));

        if (startDate < minDate) {
          event.preventDefault();
          alert(
            `A data e hora de início deve ter pelo menos ${minTimeBefore} horas de antecedência`
          );
          return;
        }
      }

      // 3. Verificar se o término é posterior ao início
      if (endDate <= startDate) {
        event.preventDefault();
        alert(
          "A data e hora de término deve ser posterior à data e hora de início"
        );
        return;
      }
    });
  }

  // Sem event listeners para os campos de data para evitar qualquer validação durante a edição

  // Único listener para atualizar logicamente o valor min do campo de término quando o início muda
  startDatetimeField.addEventListener("change", function () {
    // NÃO fazer validações aqui - apenas atualizar o estado interno
    const startDate = new Date(startDatetimeField.value);
    if (!isNaN(startDate.getTime())) {
      // A data é válida, podemos fazer cálculos com ela
      // Mas não mostrar alertas
    }
  });

  // Auxiliar para formatar data (mantido para referência)
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
});
