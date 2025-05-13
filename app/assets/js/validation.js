document.addEventListener("DOMContentLoaded", function () {
  const startDatetimeField = document.getElementById("start_datetime");
  const agendaIdField = document.querySelector('input[name="agenda_id"]');

  if (startDatetimeField && agendaIdField) {
    // Definir o mínimo como a data e hora atual
    const now = new Date();
    const year = now.getFullYear();
    let month = now.getMonth() + 1;
    let day = now.getDate();
    let hour = now.getHours();
    let minute = now.getMinutes();

    // Formatar com zeros à esquerda
    month = month < 10 ? "0" + month : month;
    day = day < 10 ? "0" + day : day;
    hour = hour < 10 ? "0" + hour : hour;
    minute = minute < 10 ? "0" + minute : minute;

    const nowString = `${year}-${month}-${day}T${hour}:${minute}`;

    // Definir o valor mínimo para a data de início
    startDatetimeField.setAttribute("min", nowString);

    // Validar a data de início ao mudar
    startDatetimeField.addEventListener("change", function () {
      validateStartDate();
    });

    // Função para validar a data de início
    function validateStartDate() {
      const startValue = startDatetimeField.value;
      if (!startValue) return;

      const startDate = new Date(startValue);
      const nowDate = new Date();

      // Verificar se a data está no futuro
      if (startDate <= nowDate) {
        alert("A data e hora de início deve ser no futuro");
        startDatetimeField.setCustomValidity(
          "A data e hora de início deve ser no futuro"
        );
      } else {
        startDatetimeField.setCustomValidity("");

        // Verificar se respeita o tempo mínimo de antecedência
        checkMinTimeBeforeConstraint(startDate);
      }
    }

    // Função para verificar a restrição de tempo mínimo
    function checkMinTimeBeforeConstraint(startDate) {
      const agendaId = agendaIdField.value;

      // Fazer uma requisição AJAX para verificar a restrição de tempo mínimo
      fetch(
        `${BASE_URL}/api/check-min-time-before?agenda_id=${agendaId}&start=${startDate.toISOString()}`
      )
        .then((response) => response.json())
        .then((data) => {
          if (data.error) {
            alert(data.error);
            startDatetimeField.setCustomValidity(data.error);
          } else {
            startDatetimeField.setCustomValidity("");
          }
        })
        .catch((error) => {
          console.error("Erro ao verificar tempo mínimo:", error);
        });
    }

    // Executar a validação inicial
    validateStartDate();
  }
});
