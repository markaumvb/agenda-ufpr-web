/**
 * JavaScript para página de agendas compartilhadas
 * Arquivo: app/assets/js/shares/shared.js
 */
document.addEventListener("DOMContentLoaded", function () {
  // Formulário de busca
  const searchForm = document.querySelector(".search-form");
  const searchInput = document.getElementById("search");
  const clearSearchBtn = document.querySelector(".search-form .btn-secondary");

  // Paginação
  const paginationLinks = document.querySelectorAll(
    ".pagination-link:not(.disabled)"
  );

  // Event listeners
  if (searchForm) {
    searchForm.addEventListener("submit", function (e) {
      // Evitar submissão se campo de busca estiver vazio
      if (!searchInput.value.trim()) {
        e.preventDefault();
        window.location.href = searchForm.getAttribute("action");
      }
    });
  }

  if (clearSearchBtn) {
    clearSearchBtn.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = searchForm.getAttribute("action");
    });
  }

  // Highlight para a página atual
  const currentPage =
    parseInt(new URLSearchParams(window.location.search).get("page")) || 1;
  paginationLinks.forEach((link) => {
    const linkPage = new URLSearchParams(new URL(link.href).search).get("page");
    if (parseInt(linkPage) === currentPage) {
      link.classList.add("current");
    }
  });
});
