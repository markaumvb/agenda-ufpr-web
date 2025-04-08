<?php
// Arquivo: app/views/compromissos/create.php
?>

<div class="form-container">
    <div class="form-header">
        <h1>Novo Compromisso</h1>
        <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agendaId ?>" class="btn btn-link">Voltar</a>
    </div>
    
    <form action="<?= BASE_URL ?>/compromissos/save" method="post">
        <input type="hidden" name="agenda_id" value="<?= $agendaId ?>">
        
        <div class="form-group">
            <label for="title">Título *</label>
            <input type="text" id="title" name="title" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="3" class="form-control"></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group form-group-half">
                <label for="start_datetime">Data e Hora de Início *</label>
                <input type="datetime-local" id="start_datetime" name="start_datetime" required class="form-control"
                       value="<?= htmlspecialchars($defaultStartDateTime) ?>">
            </div>
            
            <div class="form-group form-group-half">
                <label for="end_datetime">Data e Hora de Término *</label>
                <input type="datetime-local" id="end_datetime" name="end_datetime" required class="form-control"
                       value="<?= htmlspecialchars($defaultEndDateTime) ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="location">Local</label>
            <input type="text" id="location" name="location" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-control">
                <option value="pendente" selected>Pendente</option>
                <option value="realizado">Realizado</option>
                <option value="cancelado">Cancelado</option>
                <option value="aguardando_aprovacao">Aguardando Aprovação</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Recorrência</label>
            
            <div class="radio-group">
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="none" checked onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Não repetir
                </label>
                
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="daily" onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Repetir diariamente
                </label>
                
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="weekly" onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Repetir semanalmente
                </label>
                
                <label class="radio-container">
                    <input type="radio" name="repeat_type" value="specific_days" onchange="toggleRepeatOptions()">
                    <span class="radiomark"></span>
                    Repetir em dias específicos
                </label>
            </div>
            
            <div id="repeat_until_container" class="form-group repeat-option" style="display: none;">
                <label for="repeat_until">Repetir até</label>
                <input type="date" id="repeat_until" name="repeat_until" class="form-control">
            </div>
            
            <div id="repeat_days_container" class="form-group repeat-option" style="display: none;">
                <label>Dias da semana</label>
                <div class="checkbox-group">
                    <label class="checkbox-container">
                        <input type="checkbox" name="repeat_days[]" value="0">
                        <span class="checkmark"></span>
                        Domingo
                    </label>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" name="repeat_days[]" value="1">
                        <span class="checkmark"></span>
                        Segunda
                    </label>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" name="repeat_days[]" value="2">
                        <span class="checkmark"></span>
                        Terça
                    </label>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" name="repeat_days[]" value="3">
                        <span class="checkmark"></span>
                        Quarta
                    </label>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" name="repeat_days[]" value="4">
                        <span class="checkmark"></span>
                        Quinta
                    </label>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" name="repeat_days[]" value="5">
                        <span class="checkmark"></span>
                        Sexta
                    </label>
                    
                    <label class="checkbox-container">
                        <input type="checkbox" name="repeat_days[]" value="6">
                        <span class="checkmark"></span>
                        Sábado
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= BASE_URL ?>/compromissos?agenda_id=<?= $agendaId ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Script específico para eventos recorrentes
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
  const agendaId = document.querySelector('input[name="agenda_id"]').value;

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

// Validação de campos de recorrência
document.querySelector('form').addEventListener('submit', function(event) {
  const repeatType = document.querySelector('input[name="repeat_type"]:checked').value;
  
  // Se for um evento recorrente, verificar se a data final foi definida
  if (repeatType !== 'none') {
    const repeatUntil = document.getElementById('repeat_until').value;
    
    if (!repeatUntil) {
      event.preventDefault();
      alert('Para eventos recorrentes, é necessário definir uma data final');
      return;
    }
    
    // Para dias específicos, verificar se pelo menos um dia foi selecionado
    if (repeatType === 'specific_days') {
      const checkboxes = document.querySelectorAll('input[name="repeat_days[]"]:checked');
      
      if (checkboxes.length === 0) {
        event.preventDefault();
        alert('Selecione pelo menos um dia da semana para a recorrência');
        return;
      }
    }
  }
});

// Inicializar a exibição das opções de repetição
document.addEventListener('DOMContentLoaded', function() {
    toggleRepeatOptions();
});
</script>