<?php
// Arquivo: app/views/compromissos/create.php
?>

<div class="form-container">
    <div class="form-header">
        <h1>Novo Compromisso</h1>
        <a href="<?= BASE_URL ?>/public/compromissos?agenda_id=<?= $agendaId ?>" class="btn btn-link">Voltar</a>
    </div>
    
    <form action="<?= BASE_URL ?>/public/compromissos/save" method="post">
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
            <a href="<?= BASE_URL ?>/public/compromissos?agenda_id=<?= $agendaId ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
function toggleRepeatOptions() {
    const repeatType = document.querySelector('input[name="repeat_type"]:checked').value;
    const repeatUntilContainer = document.getElementById('repeat_until_container');
    const repeatDaysContainer = document.getElementById('repeat_days_container');
    
    // Mostrar/esconder a opção de "até quando"
    if (repeatType === 'none') {
        repeatUntilContainer.style.display = 'none';
        repeatDaysContainer.style.display = 'none';
    } else {
        repeatUntilContainer.style.display = 'block';
        
        // Mostrar/esconder dias da semana apenas para a opção "specific_days"
        if (repeatType === 'specific_days') {
            repeatDaysContainer.style.display = 'block';
        } else {
            repeatDaysContainer.style.display = 'none';
        }
    }
}

// Verificar conflitos de horário ao mudar as datas
document.getElementById('start_datetime').addEventListener('change', checkTimeConflict);
document.getElementById('end_datetime').addEventListener('change', checkTimeConflict);

function checkTimeConflict() {
    const startDatetime = document.getElementById('start_datetime').value;
    const endDatetime = document.getElementById('end_datetime').value;
    const agendaId = <?= $agendaId ?>;
    
    if (startDatetime && endDatetime) {
        // Verificar se a data final é maior que a inicial
        if (new Date(endDatetime) <= new Date(startDatetime)) {
            alert('A data e hora de término deve ser posterior à data e hora de início.');
            return;
        }
        
        // Aqui você poderia fazer uma verificação assíncrona para conflitos
        // usando uma chamada AJAX para verificar no servidor
        // Esta é uma implementação básica
        
        /*
        // Exemplo de verificação AJAX (não implementada)
        fetch(`${BASE_URL}/public/compromissos/check-conflict?agenda_id=${agendaId}&start=${encodeURIComponent(startDatetime)}&end=${encodeURIComponent(endDatetime)}`)
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    alert('Existe um conflito de horário com outro compromisso!');
                }
            });
        */
    }
}

// Inicializar a exibição das opções de repetição
document.addEventListener('DOMContentLoaded', function() {
    toggleRepeatOptions();
});
</script>

<style>
/* Estilos específicos para o formulário de compromisso */
.form-container {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

.form-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #004a8f;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group-half {
    flex: 1;
    margin-bottom: 0;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.radio-group, .checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.radio-container, .checkbox-container {
    display: block;
    position: relative;
    padding-left: 30px;
    margin-bottom: 12px;
    cursor: pointer;
    font-weight: normal;
}

.radio-container input, .checkbox-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.radiomark, .checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 20px;
    width: 20px;
    background-color: #eee;
}

.radiomark {
    border-radius: 50%;
}

.checkmark {
    border-radius: 4px;
}

.radio-container:hover input ~ .radiomark, .checkbox-container:hover input ~ .checkmark {
    background-color: #ccc;
}

.radio-container input:checked ~ .radiomark, .checkbox-container input:checked ~ .checkmark {
    background-color: #004a8f;
}

.radiomark:after, .checkmark:after {
    content: "";
    position: absolute;
    display: none;
}

.radio-container input:checked ~ .radiomark:after, .checkbox-container input:checked ~ .checkmark:after {
    display: block;
}

.radio-container .radiomark:after {
    top: 6px;
    left: 6px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: white;
}

.checkbox-container .checkmark:after {
    left: 7px;
    top: 3px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 3px 3px 0;
    transform: rotate(45deg);
}

.repeat-option {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 2rem;
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.btn {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background-color: #004a8f;
    color: #fff;
}

.btn-primary:hover {
    background-color: #003366;
}

.btn-secondary {
    background-color: #f5f5f5;
    color: #004a8f;
    border: 1px solid #004a8f;
}

.btn-secondary:hover {
    background-color: #e5e5e5;
}

.btn-link {
    background-color: transparent;
    color: #004a8f;
    padding: 0;
    text-decoration: underline;
}

.btn-link:hover {
    color: #003366;
}
</style>