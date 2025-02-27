/**
 * Script para interação com o calendário público
 * Arquivo: public/assets/js/compromissos/public-calendar.js
 */

document.addEventListener('DOMContentLoaded', function() {
  // Adicionar evento de clique para cada dia do calendário
  setupCalendarDayClicks();
  setupCloseButton();
});

/**
* Configura os eventos de clique nos dias do calendário
*/
function setupCalendarDayClicks() {
  const calendarDays = document.querySelectorAll('.calendar-day:not(.empty-day)');
  
  calendarDays.forEach(day => {
      day.addEventListener('click', function() {
          const date = this.dataset.date;
          if (!date) return;
          
          showDayEvents(this, date);
      });
  });
}

/**
* Configura o botão de fechar o painel de compromissos
*/
function setupCloseButton() {
  const closeButton = document.querySelector('.day-events-close');
  if (closeButton) {
      closeButton.addEventListener('click', function() {
          hideDayEvents();
      });
  }
}

/**
* Exibe os compromissos de um dia específico
*/
function showDayEvents(dayElement, date) {
  // Formatar a data para exibição
  const dateObj = new Date(date + 'T00:00:00');
  const formattedDate = dateObj.toLocaleDateString('pt-BR', { 
      weekday: 'long', 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
  });
  
  // Atualizar o título
  document.getElementById('day-events-title').textContent = 'Compromissos de ' + formattedDate;
  
  // Buscar compromissos do dia
  const dayEvents = dayElement.querySelectorAll('.event');
  
  const eventsList = document.getElementById('day-events-list');
  eventsList.innerHTML = '';
  
  if (dayEvents.length === 0) {
      eventsList.innerHTML = '<p class="no-events">Não há compromissos para este dia.</p>';
  } else {
      // Para cada evento, criar um card
      dayEvents.forEach(event => {
          const title = event.dataset.title || event.querySelector('.event-title').textContent;
          const description = event.dataset.description || '';
          const start = new Date(event.dataset.start || event.querySelector('.event-time').textContent);
          const end = new Date(event.dataset.end || '');
          const status = event.dataset.status || event.className.match(/event-status-(\w+)/)[1];
          
          const card = document.createElement('div');
          card.className = `event-card event-status-${status}`;
          
          // Formatar horário
          const formattedTime = `${start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}${end ? ' - ' + end.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) : ''}`;
          
          card.innerHTML = `
              <div class="event-card-header">
                  <h4>${title}</h4>
                  <span class="event-time">${formattedTime}</span>
              </div>
              ${description ? `<div class="event-description