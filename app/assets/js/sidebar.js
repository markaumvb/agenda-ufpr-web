document.addEventListener("DOMContentLoaded", function () {
  // Verificar se estamos na página de login ou registro através da classe do body
  const isAuthPage = document.body.classList.contains("auth-page");

  // Se for página de autenticação, não inicializar o sidebar
  if (isAuthPage) {
    return;
  }

  // Continuar com a inicialização do sidebar apenas para páginas regulares
  const sidebar = document.querySelector(".sidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const mainContent = document.querySelector(".main-content");
  const mobileToggle = document.querySelector(".mobile-menu-toggle");
  const sidebarOverlay = document.querySelector(".sidebar-overlay");

  // Se algum dos elementos essenciais não existir, não continuar
  if (!sidebar || !mainContent) {
    console.warn("Elementos essenciais do sidebar não encontrados");
    return;
  }

  // Toggle sidebar na versão desktop
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", function (e) {
      e.preventDefault(); // Prevenir comportamento padrão
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("expanded");

      // Salvar o estado no localStorage
      localStorage.setItem(
        "sidebarCollapsed",
        sidebar.classList.contains("collapsed")
      );

      // Atualizar visibilidade dos textos no sidebar
      updateSidebarTextVisibility();
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
    mobileToggle.addEventListener("click", function (e) {
      e.preventDefault(); // Prevenir comportamento padrão
      sidebar.classList.add("mobile-visible");
      if (sidebarOverlay) sidebarOverlay.classList.add("visible");
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

  // Função para atualizar a visibilidade do texto no sidebar
  function updateSidebarTextVisibility() {
    const sidebarTexts = document.querySelectorAll(".sidebar-link span");
    const isMobile = window.innerWidth <= 768;

    if (
      sidebar.classList.contains("collapsed") &&
      !sidebar.classList.contains("mobile-visible")
    ) {
      // Ocultar texto quando colapsado
      sidebarTexts.forEach((span) => {
        span.style.display = "none";
      });
    } else if (isMobile && !sidebar.classList.contains("mobile-visible")) {
      // Ocultar texto quando em modo mobile e sidebar não está visível
      sidebarTexts.forEach((span) => {
        span.style.display = "none";
      });
    } else {
      // Mostrar texto em todos os outros casos
      sidebarTexts.forEach((span) => {
        span.style.display = "";
      });
    }
  }

  // Executar a primeira vez
  updateSidebarTextVisibility();

  // Atualizar quando a janela for redimensionada
  window.addEventListener("resize", updateSidebarTextVisibility);
});
