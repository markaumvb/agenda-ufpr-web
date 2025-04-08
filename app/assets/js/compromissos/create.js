/**
 * Controla a exibição das opções de repetição baseado no tipo selecionado
 */
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

/**
 * Verifica conflitos de horário ao alterar datas
 */
function checkTimeConflict() {
  const startDatetime = document.getElementById("start_datetime").value;
  const endDatetime = document.getElementById("end_datetime").value;
  const agendaId =
    document.querySelector('input[name="agenda_id"]')?.value ||
    document.querySelector("form").getAttribute("data-agenda-id");
  const compromissoId = document.querySelector('input[name="id"]')?.value;

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

// Adicionar event listeners para datas
document.addEventListener("DOMContentLoaded", function () {
  const startDatetimeInput = document.getElementById("start_datetime");
  const endDatetimeInput = document.getElementById("end_datetime");

  if (startDatetimeInput) {
    startDatetimeInput.addEventListener("change", checkTimeConflict);
  }

  if (endDatetimeInput) {
    endDatetimeInput.addEventListener("change", checkTimeConflict);
  }

  // Para eventos recorrentes, adicionar validação adicional
  const repeatTypeInputs = document.querySelectorAll(
    'input[name="repeat_type"]'
  );
  const repeatUntilInput = document.getElementById("repeat_until");

  repeatTypeInputs.forEach((input) => {
    input.addEventListener("change", function () {
      // Se escolher um tipo de recorrência, validar a data final
      if (this.value !== "none" && repeatUntilInput) {
        if (!repeatUntilInput.value) {
          repeatUntilInput.classList.add("is-invalid");
        } else {
          repeatUntilInput.classList.remove("is-invalid");
        }
      }
    });
  });

  if (repeatUntilInput) {
    repeatUntilInput.addEventListener("change", function () {
      this.classList.remove("is-invalid");
    });
  }

  // Para eventos com dias específicos, validar a seleção de pelo menos um dia
  const repeatTypeSpecificDays = document.querySelector(
    'input[name="repeat_type"][value="specific_days"]'
  );
  const dayCheckboxes = document.querySelectorAll(
    'input[name="repeat_days[]"]'
  );

  if (repeatTypeSpecificDays && dayCheckboxes.length > 0) {
    const validateDaySelection = function () {
      if (repeatTypeSpecificDays.checked) {
        let anyChecked = false;
        dayCheckboxes.forEach((checkbox) => {
          if (checkbox.checked) anyChecked = true;
        });

        if (!anyChecked) {
          document
            .getElementById("repeat_days_container")
            .classList.add("has-error");
          alert(
            'Por favor, selecione pelo menos um dia da semana quando escolher "Repetir em dias específicos".'
          );
          return false;
        } else {
          document
            .getElementById("repeat_days_container")
            .classList.remove("has-error");
        }
      }
      return true;
    };

    // Adicionar validação ao formulário antes de enviar
    const form = document.querySelector("form");
    form.addEventListener("submit", function (event) {
      if (!validateDaySelection()) {
        event.preventDefault();
      }
    });

    // Atualizar a validação quando os checkboxes mudarem
    dayCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", validateDaySelection);
    });
  }

  // Inicializar a exibição das opções de repetição
  toggleRepeatOptions();
});

// Confirmação personalizada para exclusão de compromissos recorrentes
document.addEventListener("DOMContentLoaded", function () {
  const deleteFutureForms = document.querySelectorAll(
    'form[action*="delete"][action*="future"]'
  );

  deleteFutureForms.forEach((form) => {
    form.addEventListener("submit", function (event) {
      const confirmed = confirm(
        "Tem certeza que deseja excluir este compromisso e todas as suas ocorrências futuras?"
      );
      if (!confirmed) {
        event.preventDefault();
      }
    });
  });
});
