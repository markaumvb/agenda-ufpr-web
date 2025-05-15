/**
 * Script para a página Meus Compromissos
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

  // Event listeners para os botões de aprovar
  document.querySelectorAll(".approve-btn").forEach(function (button) {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      var id = this.getAttribute("data-id");
      document.getElementById("approve-id").value = id;
      document.getElementById("approveForm").submit();
    });
  });

  // Event listeners para os botões de rejeitar
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

  // Função para expandir/retrair descrições ao clicar nas linhas
  compromissoRows.forEach(function (row) {
    row.addEventListener("click", function (e) {
      // Não expandir se clicou em um botão ou link dentro da linha
      if (
        e.target.tagName === "A" ||
        e.target.tagName === "BUTTON" ||
        e.target.closest("a") ||
        e.target.closest("button") ||
        e.target.closest(".dropdown")
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
      // Resetar os valores dos filtros
      if (filterAgenda) filterAgenda.value = "all";
      if (filterStatus) filterStatus.value = "all";
      if (filterPeriod) filterPeriod.value = "all";
      if (filterSearch) filterSearch.value = "";

      // Aplicar filtros resetados
      applyFilters();
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
