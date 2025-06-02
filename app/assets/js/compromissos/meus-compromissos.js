/**
 * Script para a página Meus Compromissos - COM SELEÇÃO EM MASSA
 */
document.addEventListener("DOMContentLoaded", function () {
  // Referências aos elementos DOM
  const filterAgenda = document.getElementById("filter-agenda");
  const filterStatus = document.getElementById("filter-status");
  const filterPeriod = document.getElementById("filter-period");
  const filterSearch = document.getElementById("filter-search");
  const clearFiltersBtn = document.getElementById("clear-filters");
  const resetFiltersBtn = document.getElementById("reset-filters");
  const compromissosTable = document.getElementById("compromissos-table");
  const compromissoRows = document.querySelectorAll(".compromisso-row");
  const noResults = document.querySelector(".no-results");

  // NOVOS ELEMENTOS PARA SELEÇÃO EM MASSA
  const bulkActionsContainer = document.getElementById(
    "bulk-actions-container"
  );
  const selectedCountElement = document.getElementById("selected-count");
  const selectAllCheckbox = document.getElementById("select-all-checkbox");
  const clearSelectionBtn = document.getElementById("clear-selection");
  const bulkApproveBtn = document.getElementById("bulk-approve");
  const bulkRejectBtn = document.getElementById("bulk-reject");
  const selectAllVisibleBtn = document.getElementById("select-all-visible");

  // VARIÁVEIS PARA CONTROLE DE SELEÇÃO
  let selectedCompromissos = new Set();

  // Event listeners para os botões de aprovar individuais
  document.querySelectorAll(".approve-btn").forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      var id = this.getAttribute("data-id");
      document.getElementById("approve-id").value = id;
      document.getElementById("approveForm").submit();
    });
  });

  // Event listeners para os botões de rejeitar individuais
  document.querySelectorAll(".reject-btn").forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      var id = this.getAttribute("data-id");
      document.getElementById("reject-id").value = id;
      document.getElementById("rejectForm").submit();
    });
  });

  // Event listeners para os botões de cancelar
  document.querySelectorAll(".cancel-btn").forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      if (confirm("Tem certeza que deseja cancelar este compromisso?")) {
        var id = this.getAttribute("data-id");
        document.getElementById("cancel-id").value = id;
        document.getElementById("cancelForm").submit();
      }
    });
  });

  // Event listeners para os botões de excluir
  document.querySelectorAll(".delete-btn").forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      if (confirm("Tem certeza que deseja excluir este compromisso?")) {
        var id = this.getAttribute("data-id");
        document.getElementById("delete-id").value = id;
        document.getElementById("deleteForm").submit();
      }
    });
  });

  // ===============================
  // NOVA FUNCIONALIDADE: SELEÇÃO EM MASSA
  // ===============================

  // Event listener para checkboxes individuais
  document.querySelectorAll(".bulk-checkbox").forEach(function (checkbox) {
    checkbox.addEventListener("change", function () {
      const compromissoId = this.value;

      if (this.checked) {
        selectedCompromissos.add(compromissoId);
      } else {
        selectedCompromissos.delete(compromissoId);
      }

      updateBulkActionsUI();
      updateSelectAllCheckbox();
    });
  });

  // Event listener para o checkbox "selecionar todos"
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener("change", function () {
      const isChecked = this.checked;
      const visibleCheckboxes = getVisibleBulkCheckboxes();

      visibleCheckboxes.forEach(function (checkbox) {
        checkbox.checked = isChecked;
        const compromissoId = checkbox.value;

        if (isChecked) {
          selectedCompromissos.add(compromissoId);
        } else {
          selectedCompromissos.delete(compromissoId);
        }
      });

      updateBulkActionsUI();
    });
  }

  // Event listener para "Selecionar Todos Visíveis"
  if (selectAllVisibleBtn) {
    selectAllVisibleBtn.addEventListener("click", function () {
      const visibleCheckboxes = getVisibleBulkCheckboxes();

      visibleCheckboxes.forEach(function (checkbox) {
        checkbox.checked = true;
        selectedCompromissos.add(checkbox.value);
      });

      updateBulkActionsUI();
      updateSelectAllCheckbox();
    });
  }

  // Event listener para "Limpar Seleção"
  if (clearSelectionBtn) {
    clearSelectionBtn.addEventListener("click", function () {
      clearAllSelections();
    });
  }

  // Event listener para "Aprovar Selecionados"
  if (bulkApproveBtn) {
    bulkApproveBtn.addEventListener("click", function () {
      if (selectedCompromissos.size === 0) {
        alert("Nenhum compromisso selecionado.");
        return;
      }

      const count = selectedCompromissos.size;
      const message =
        count === 1
          ? "Tem certeza que deseja aprovar 1 compromisso selecionado?"
          : `Tem certeza que deseja aprovar ${count} compromissos selecionados?`;

      if (confirm(message)) {
        const ids = Array.from(selectedCompromissos).join(",");
        document.getElementById("bulk-approve-ids").value = ids;
        document.getElementById("bulkApproveForm").submit();
      }
    });
  }

  // Event listener para "Rejeitar Selecionados"
  if (bulkRejectBtn) {
    bulkRejectBtn.addEventListener("click", function () {
      if (selectedCompromissos.size === 0) {
        alert("Nenhum compromisso selecionado.");
        return;
      }

      const count = selectedCompromissos.size;
      const message =
        count === 1
          ? "Tem certeza que deseja rejeitar 1 compromisso selecionado?"
          : `Tem certeza que deseja rejeitar ${count} compromissos selecionados?`;

      if (confirm(message)) {
        const ids = Array.from(selectedCompromissos).join(",");
        document.getElementById("bulk-reject-ids").value = ids;
        document.getElementById("bulkRejectForm").submit();
      }
    });
  }

  // FUNÇÕES AUXILIARES PARA SELEÇÃO EM MASSA

  function getVisibleBulkCheckboxes() {
    return Array.from(document.querySelectorAll(".bulk-checkbox")).filter(
      function (checkbox) {
        const row = checkbox.closest(".compromisso-row");
        return row && row.style.display !== "none";
      }
    );
  }

  function updateBulkActionsUI() {
    const count = selectedCompromissos.size;

    if (count > 0) {
      bulkActionsContainer.style.display = "block";
      selectedCountElement.textContent =
        count === 1
          ? "1 compromisso selecionado"
          : `${count} compromissos selecionados`;
    } else {
      bulkActionsContainer.style.display = "none";
    }
  }

  function updateSelectAllCheckbox() {
    const visibleCheckboxes = getVisibleBulkCheckboxes();
    const checkedVisibleCheckboxes = visibleCheckboxes.filter(
      (cb) => cb.checked
    );

    if (visibleCheckboxes.length === 0) {
      selectAllCheckbox.indeterminate = false;
      selectAllCheckbox.checked = false;
    } else if (checkedVisibleCheckboxes.length === visibleCheckboxes.length) {
      selectAllCheckbox.indeterminate = false;
      selectAllCheckbox.checked = true;
    } else if (checkedVisibleCheckboxes.length > 0) {
      selectAllCheckbox.indeterminate = true;
      selectAllCheckbox.checked = false;
    } else {
      selectAllCheckbox.indeterminate = false;
      selectAllCheckbox.checked = false;
    }
  }

  function clearAllSelections() {
    selectedCompromissos.clear();
    document.querySelectorAll(".bulk-checkbox").forEach(function (checkbox) {
      checkbox.checked = false;
    });
    updateBulkActionsUI();
    updateSelectAllCheckbox();
  }

  // ===============================
  // FUNCIONALIDADE ORIGINAL MANTIDA
  // ===============================

  // Função para expandir/retrair descrições ao clicar nas linhas
  compromissoRows.forEach(function (row) {
    row.addEventListener("click", function (e) {
      // Não expandir se clicou em um botão, link ou checkbox dentro da linha
      if (
        e.target.tagName === "A" ||
        e.target.tagName === "BUTTON" ||
        e.target.tagName === "INPUT" ||
        e.target.closest("a") ||
        e.target.closest("button") ||
        e.target.closest(".dropdown") ||
        e.target.closest(".col-checkbox")
      ) {
        return;
      }

      const compromissoId = this.getAttribute("data-id");
      const descRow = document.getElementById("desc-" + compromissoId);

      if (descRow) {
        const isVisible = descRow.style.display !== "none";
        descRow.style.display = isVisible ? "none" : "table-row";

        // Toggle classe para destacar linha selecionada
        this.classList.toggle("selected");
      }
    });
  });

  // Função para aplicar filtros
  function applyFilters() {
    const agendaFilter = filterAgenda ? filterAgenda.value : "all";
    const statusFilter = filterStatus ? filterStatus.value : "all";
    const periodFilter = filterPeriod ? filterPeriod.value : "all";
    const searchText = filterSearch ? filterSearch.value.toLowerCase() : "";

    let visibleCount = 0;

    // Data atual para filtros de período
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const weekStart = new Date(today);
    const dayOfWeek = today.getDay();
    const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); // Ajusta para começar na segunda-feira
    weekStart.setDate(diff);

    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);

    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    // Aplicar filtros a cada linha
    compromissoRows.forEach(function (row) {
      const rowAgendaId = row.getAttribute("data-agenda");
      const rowStatus = row.getAttribute("data-status");
      const rowDate = row.getAttribute("data-date");
      const rowSearchText = row.getAttribute("data-search");
      const rowDateObj = new Date(rowDate);

      // Verificar filtro de agenda
      const agendaMatch =
        agendaFilter === "all" || rowAgendaId === agendaFilter;

      // Verificar filtro de status
      const statusMatch = statusFilter === "all" || rowStatus === statusFilter;

      // Verificar filtro de período
      let periodMatch = true;

      if (periodFilter === "today") {
        periodMatch = rowDateObj.toDateString() === today.toDateString();
      } else if (periodFilter === "tomorrow") {
        periodMatch = rowDateObj.toDateString() === tomorrow.toDateString();
      } else if (periodFilter === "week") {
        periodMatch = rowDateObj >= weekStart && rowDateObj <= weekEnd;
      } else if (periodFilter === "month") {
        periodMatch = rowDateObj >= monthStart && rowDateObj <= monthEnd;
      } else if (periodFilter === "past") {
        periodMatch = rowDateObj < today;
      }

      // Verificar filtro de busca
      const searchMatch =
        !searchText || (rowSearchText && rowSearchText.includes(searchText));

      // Determinar se a linha deve ser exibida
      const isVisible =
        agendaMatch && statusMatch && periodMatch && searchMatch;

      // Exibir/ocultar a linha
      row.style.display = isVisible ? "" : "none";

      // Ocultar a linha de descrição associada
      const descRow = document.getElementById(
        "desc-" + row.getAttribute("data-id")
      );
      if (descRow) {
        descRow.style.display = "none";
      }

      // Contar linhas visíveis
      if (isVisible) {
        visibleCount++;
      }
    });

    // Mostrar mensagem de "nenhum resultado" se necessário
    if (noResults) {
      noResults.style.display = visibleCount === 0 ? "block" : "none";
    }

    // Atualizar UI de seleção em massa após aplicar filtros
    updateSelectAllCheckbox();
  }

  // Função para limpar todos os filtros
  function clearAllFilters() {
    // Resetar os valores dos filtros
    if (filterAgenda) filterAgenda.value = "all";
    if (filterStatus) filterStatus.value = "all";
    if (filterPeriod) filterPeriod.value = "all";
    if (filterSearch) filterSearch.value = "";

    // Limpar filtro de data
    window.selectedDate = null;

    // Remover destaque do calendário
    document.querySelectorAll(".calendar-day").forEach((day) => {
      day.classList.remove("selected-day");
    });

    // Aplicar filtros limpos
    applyFilters();
  }

  // Adicionar event listeners para os filtros
  if (filterAgenda) {
    filterAgenda.addEventListener("change", applyFilters);
  }

  if (filterStatus) {
    filterStatus.addEventListener("change", applyFilters);
  }

  if (filterPeriod) {
    filterPeriod.addEventListener("change", applyFilters);
  }

  if (filterSearch) {
    filterSearch.addEventListener("input", applyFilters);
  }

  // Botão para limpar filtros
  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener("click", function () {
      clearAllFilters();
    });
  }

  // Botão na mensagem de "nenhum resultado" para resetar filtros
  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener("click", function () {
      if (clearFiltersBtn) {
        clearFiltersBtn.click();
      }
    });
  }

  // Tornar as funções acessíveis globalmente
  window.applyFilters = applyFilters;
  window.clearAllFilters = clearAllFilters;

  // Inicializar os filtros
  if (compromissoRows.length > 0) {
    applyFilters();
  }

  // Expandir descrições para URLs com hash de compromisso
  const hash = window.location.hash;
  if (hash && hash.startsWith("#compromisso-")) {
    const compromissoId = hash.substring(13); // Remover '#compromisso-'
    const row = document.querySelector(
      `.compromisso-row[data-id="${compromissoId}"]`
    );

    if (row) {
      // Rolar para a linha
      row.scrollIntoView({ behavior: "smooth", block: "center" });

      // Destacar e expandir
      row.classList.add("highlighted");
      const descRow = document.getElementById("desc-" + compromissoId);

      if (descRow) {
        descRow.style.display = "table-row";
      }

      // Remover destaque após alguns segundos
      setTimeout(function () {
        row.classList.remove("highlighted");
      }, 3000);
    }
  }

  // Inicializar dropdowns
  document.querySelectorAll(".dropdown-toggle").forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const menu = this.nextElementSibling;
      const isOpen = menu.classList.contains("show");

      // Fechar todos os dropdowns abertos
      document
        .querySelectorAll(".dropdown-menu.show")
        .forEach(function (openMenu) {
          openMenu.classList.remove("show");
        });

      // Abrir/fechar o dropdown atual
      if (!isOpen) {
        menu.classList.add("show");
      }
    });
  });

  // Fechar dropdowns ao clicar fora
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown-menu.show").forEach(function (menu) {
        menu.classList.remove("show");
      });
    }
  });
});
