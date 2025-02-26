function toggleRepeatOptions() {
  const repeatType = document.querySelector(
    'input[name="repeat_type"]:checked'
  ).value;
  const repeatUntilContainer = document.getElementById(
    "repeat_until_container"
  );
  const repeatDaysContainer = document.getElementById("repeat_days_container");

  // Mostrar/esconder a opção de "até quando"
  if (repeatType === "none") {
    repeatUntilContainer.style.display = "none";
    repeatDaysContainer.style.display = "none";
  } else {
    repeatUntilContainer.style.display = "block";

    // Mostrar/esconder dias da semana apenas para a opção "specific_days"
    if (repeatType === "specific_days") {
      repeatDaysContainer.style.display = "block";
    } else {
      repeatDaysContainer.style.display = "none";
    }
  }
}

// Verificar conflitos de horário ao mudar as datas
document
  .getElementById("start_datetime")
  .addEventListener("change", checkTimeConflict);
document
  .getElementById("end_datetime")
  .addEventListener("change", checkTimeConflict);

function checkTimeConflict() {
  const startDatetime = document.getElementById("start_datetime").value;
  const endDatetime = document.getElementById("end_datetime").value;
  const agendaId = document
    .querySelector("form")
    .getAttribute("data-agenda-id");
  const compromissoId = document.querySelector('input[name="id"]').value;

  if (startDatetime && endDatetime) {
    // Verificar se a data final é maior que a inicial
    if (new Date(endDatetime) <= new Date(startDatetime)) {
      alert(
        "A data e hora de término deve ser posterior à data e hora de início."
      );
      return;
    }
  }
}

// Inicializar a exibição das opções de repetição
document.addEventListener("DOMContentLoaded", function () {
  toggleRepeatOptions();
});
