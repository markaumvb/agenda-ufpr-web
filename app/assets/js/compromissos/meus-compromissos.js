/**
 * Script para a página de Meus Compromissos
 * Funcionalidades:
 * - Filtros dinâmicos
 * - Expansão de descrições
 * - Dropdowns de ações
 * - Confirmações antes de ações
 */

document.addEventListener("DOMContentLoaded", function () {
  // Seletores de elementos principais
  const filterAgenda = document.getElementById("filter-agenda");
  const filterStatus = document.getElementById("filter-status");
  const filterPeriod = document.getElementById("filter-period");
  const filterSearch = document.getElementById("filter-search");
  const clearFilters = document.getElementById("clear-filters");
  const resetFilters = document.getElementById("reset-filters");
  const compromissoRows = document.querySelectorAll(".compromisso-row");
  const noResults = document.querySelector(".no-results");

  // Botões de ação
  const cancelBtns = document.querySelectorAll(".cancel-btn");
  const approveBtns = document.querySelectorAll(".approve-btn");
  const rejectBtns = document.querySelectorAll(".reject-btn");
  const deleteBtns = document.querySelectorAll(".delete-btn");

  // Formulários para submissão
  const cancelForm = document.getElementById("cancelForm");
  const approveForm = document.getElementById("approveForm");
  const rejectForm = document.getElementById("rejectForm");
  const deleteForm = document.getElementById("deleteForm");

  // Inicializar filtros a partir da URL
  initFiltersFromUrl();

  // Marcar linhas que têm descrição
  compromissoRows.forEach((row) => {
    const id = row.dataset.id;
    const descRow = document.getElementById("desc-" + id);

    if (descRow) {
      row.classList.add("has-description");
    }

    // Toggle para expandir descrição
    row.addEventListener("click", function (e) {
      // Ignorar clique em botões e links
      if (
        e.target.tagName === "BUTTON" ||
        e.target.tagName === "A" ||
        e.target.closest("button") ||
        e.target.closest("a") ||
        e.target.closest(".dropdown")
      ) {
        return;
      }

      const id = this.dataset.id;
      const descRow = document.getElementById("desc-" + id);

      if (descRow) {
        if (descRow.style.display === "none" || descRow.style.display === "") {
          // Fechar todas as outras descrições
          document.querySelectorAll(".description-row").forEach((r) => {
            if (r.id !== "desc-" + id) {
              r.style.display = "none";
            }
          });

          // Remover classe expanded de todas as outras linhas
          document.querySelectorAll(".compromisso-row").forEach((r) => {
            if (r !== this) {
              r.classList.remove("expanded");
            }
          });

          // Expandir esta descrição
          descRow.style.display = "table-row";
          this.classList.add("expanded");
        } else {
          // Colapsar esta descrição
          descRow.style.display = "none";
          this.classList.remove("expanded");
        }
      }
    });
  });

  // Aplicar filtros quando alterados
  if (filterAgenda)
    filterAgenda.addEventListener("change", applyFiltersToTable);
  if (filterStatus)
    filterStatus.addEventListener("change", applyFiltersToTable);
  if (filterPeriod)
    filterPeriod.addEventListener("change", applyFiltersToTable);
  if (filterSearch) filterSearch.addEventListener("input", applyFiltersToTable);

  // Botão para limpar filtros
  if (clearFilters) {
    clearFilters.addEventListener("click", function () {
      clearAllFilters();
      applyFiltersToTable();
    });
  }

  if (resetFilters) {
    resetFilters.addEventListener("click", function () {
      clearAllFilters();
      applyFiltersToTable();
    });
  }

  // Botões de ação com confirmação
  cancelBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      if (confirm("Tem certeza que deseja cancelar este compromisso?")) {
        document.getElementById("cancel-id").value = this.dataset.id;
        cancelForm.submit();
      }
    });
  });

  approveBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      if (confirm("Deseja aprovar este compromisso?")) {
        document.getElementById("approve-id").value = this.dataset.id;
        approveForm.submit();
      }
    });
  });

  rejectBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      if (
        confirm(
          "Tem certeza que deseja rejeitar este compromisso? Ele será cancelado."
        )
      ) {
        document.getElementById("reject-id").value = this.dataset.id;
        rejectForm.submit();
      }
    });
  });

  deleteBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      if (
        confirm(
          "Tem certeza que deseja excluir este compromisso? Esta ação não pode ser desfeita."
        )
      ) {
        document.getElementById("delete-id").value = this.dataset.id;
        deleteForm.submit();
      }
    });
  });

  // Aplicar filtros iniciais
  applyFiltersToTable();

  /**
   * Aplica filtros à tabela de compromissos
   */
  function applyFiltersToTable() {
    const agendaFilter = filterAgenda ? filterAgenda.value : "all";
    const statusFilter = filterStatus ? filterStatus.value : "all";
    const periodFilter = filterPeriod ? filterPeriod.value : "all";
    const searchFilter = filterSearch
      ? filterSearch.value.toLowerCase().trim()
      : "";

    // Datas para filtro de período
    const today = new Date().toISOString().split("T")[0];
    const tomorrow = new Date(new Date().setDate(new Date().getDate() + 1))
      .toISOString()
      .split("T")[0];

    // Início e fim da semana atual
    const weekStart = getWeekStartDate().toISOString().split("T")[0];
    const weekEnd = getWeekEndDate().toISOString().split("T")[0];

    // Início e fim do mês atual
    const monthStart = new Date(
      new Date().getFullYear(),
      new Date().getMonth(),
      1
    )
      .toISOString()
      .split("T")[0];
    const monthEnd = new Date(
      new Date().getFullYear(),
      new Date().getMonth() + 1,
      0
    )
      .toISOString()
      .split("T")[0];

    let visibleCount = 0;

    // Aplicar filtros às linhas
    compromissoRows.forEach((row) => {
      const agenda = row.dataset.agenda;
      const status = row.dataset.status;
      const date = row.dataset.date; // Formato: YYYY-MM-DD
      const searchText = row.dataset.search || "";

      // Verificar correspondência com filtros
      const agendaMatch = agendaFilter === "all" || agenda === agendaFilter;
      const statusMatch = statusFilter === "all" || status === statusFilter;
      const searchMatch = !searchFilter || searchText.includes(searchFilter);

      // Filtro de período
      let periodMatch = true;
      if (periodFilter !== "all") {
        switch (periodFilter) {
          case "today":
            periodMatch = date === today;
            break;
          case "tomorrow":
            periodMatch = date === tomorrow;
            break;
          case "week":
            periodMatch = date >= weekStart && date <= weekEnd;
            break;
          case "month":
            periodMatch = date >= monthStart && date <= monthEnd;
            break;
          case "past":
            periodMatch = date < today;
            break;
        }
      }

      // Exibir ou esconder a linha
      const isVisible =
        agendaMatch && statusMatch && periodMatch && searchMatch;

      if (isVisible) {
        row.style.display = "table-row";
        visibleCount++;

        // Esconder a linha de descrição para manter consistência
        const id = row.dataset.id;
        const descRow = document.getElementById("desc-" + id);
        if (descRow) {
          descRow.style.display = "none";
        }
        row.classList.remove("expanded");
      } else {
        row.style.display = "none";

        // Esconder também a linha de descrição
        const id = row.dataset.id;
        const descRow = document.getElementById("desc-" + id);
        if (descRow) {
          descRow.style.display = "none";
        }
      }
    });

    // Mostrar/esconder mensagem de "nenhum resultado"
    if (noResults) {
      if (visibleCount === 0) {
        noResults.style.display = "block";
      } else {
        noResults.style.display = "none";
      }
    }

    // Atualizar a URL com os filtros (para permitir bookmark/compartilhamento)
    updateUrlWithFilters(
      agendaFilter,
      statusFilter,
      periodFilter,
      searchFilter
    );
  }

  /**
   * Inicializa os filtros com base nos parâmetros da URL
   */
  function initFiltersFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);

    // Definir valores dos filtros
    if (filterAgenda && urlParams.has("agenda")) {
      filterAgenda.value = urlParams.get("agenda");
    }

    if (filterStatus && urlParams.has("status")) {
      filterStatus.value = urlParams.get("status");
    }

    if (filterPeriod && urlParams.has("period")) {
      filterPeriod.value = urlParams.get("period");
    }

    if (filterSearch && urlParams.has("search")) {
      filterSearch.value = urlParams.get("search");
    }
  }

  /**
   * Atualiza a URL com os filtros selecionados
   */
  function updateUrlWithFilters(agenda, status, period, search) {
    const url = new URL(window.location);

    // Remover parâmetros existentes
    url.searchParams.delete("agenda");
    url.searchParams.delete("status");
    url.searchParams.delete("period");
    url.searchParams.delete("search");

    // Adicionar novos parâmetros se não forem padrão
    if (agenda && agenda !== "all") {
      url.searchParams.set("agenda", agenda);
    }

    if (status && status !== "all") {
      url.searchParams.set("status", status);
    }

    if (period && period !== "all") {
      url.searchParams.set("period", period);
    }

    if (search) {
      url.searchParams.set("search", search);
    }

    // Manter o parâmetro de página se existir
    const pageParam = new URLSearchParams(window.location.search).get("page");
    if (pageParam) {
      url.searchParams.set("page", pageParam);
    }

    // Atualizar URL sem recarregar a página
    window.history.replaceState({}, "", url);
  }

  /**
   * Limpa todos os filtros
   */
  function clearAllFilters() {
    if (filterAgenda) filterAgenda.value = "all";
    if (filterStatus) filterStatus.value = "all";
    if (filterPeriod) filterPeriod.value = "all";
    if (filterSearch) filterSearch.value = "";
  }

  /**
   * Retorna a data de início da semana atual (domingo)
   */
  function getWeekStartDate() {
    const now = new Date();
    const dayOfWeek = now.getDay(); // 0 = Domingo, 1 = Segunda, ...
    const diff = now.getDate() - dayOfWeek;
    return new Date(now.setDate(diff));
  }

  /**
   * Retorna a data de fim da semana atual (sábado)
   */
  function getWeekEndDate() {
    const now = new Date();
    const dayOfWeek = now.getDay(); // 0 = Domingo, 1 = Segunda, ...
    const diff = now.getDate() + (6 - dayOfWeek);
    return new Date(now.setDate(diff));
  }

  /**
   * Controle de dropdown para dispositivos de toque
   */
  document.querySelectorAll(".dropdown-toggle").forEach((toggle) => {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const dropdown = this.parentNode;
      const menu = dropdown.querySelector(".dropdown-menu");

      // Fechar todos os outros dropdowns
      document.querySelectorAll(".dropdown-menu").forEach((m) => {
        if (m !== menu) {
          m.style.display = "none";
        }
      });

      // Alternar visibilidade
      if (menu.style.display === "block") {
        menu.style.display = "none";
      } else {
        menu.style.display = "block";

        // Posicionar o menu para garantir que fique visível
        const rect = menu.getBoundingClientRect();
        if (rect.right > window.innerWidth) {
          menu.style.right = "0";
        }
        if (rect.bottom > window.innerHeight) {
          menu.style.bottom = "100%";
          menu.style.top = "auto";
        }
      }
    });
  });

  // Fechar todos os dropdowns ao clicar fora
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown-menu").forEach((menu) => {
        menu.style.display = "none";
      });
    }
  });
});
