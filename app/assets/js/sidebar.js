document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.querySelector(".sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const mainContent = document.querySelector(".main-content");
  const mobileToggle = document.querySelector(".mobile-menu-toggle");
  const sidebarOverlay = document.querySelector(".sidebar-overlay");

  // Toggle sidebar na versão desktop
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function () {
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("expanded");

      // Salvar o estado no localStorage
      localStorage.setItem(
        "sidebarCollapsed",
        sidebar.classList.contains("collapsed")
      );
    });
  }

  // Verificar o estado salvo no localStorage
  const sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
  if (sidebarCollapsed) {
    sidebar.classList.add("collapsed");
    mainContent.classList.add("expanded");
  }

  // Para dispositivos móveis
  if (mobileToggle) {
    mobileToggle.addEventListener("click", function () {
      sidebar.classList.add("mobile-visible");
      sidebarOverlay.classList.add("visible");
    });
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", function () {
      sidebar.classList.remove("mobile-visible");
      sidebarOverlay.classList.remove("visible");
    });
  }

  // Adicionar classe 'active' ao link atual
  const currentPath = window.location.pathname;
  const sidebarLinks = document.querySelectorAll(".sidebar-link");

  sidebarLinks.forEach((link) => {
    const href = link.getAttribute("href");
    if (href && currentPath.includes(href) && href !== PUBLIC_URL + "/") {
      link.classList.add("active");
    } else if (currentPath === PUBLIC_URL + "/" && href === PUBLIC_URL + "/") {
      link.classList.add("active");
    }
  });
});
