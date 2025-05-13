document.addEventListener("DOMContentLoaded", function () {
  // Usar a função copyToClipboard do namespace AgendaUFPR
  document.querySelectorAll(".input-group .btn").forEach((button) => {
    button.addEventListener("click", function () {
      const input = this.closest(".input-group").querySelector("input");
      if (input) {
        AgendaUFPR.utils.copyToClipboard(input.value);

        // Feedback visual
        const originalText = this.textContent;
        this.textContent = "Copiado!";
        setTimeout(() => {
          this.textContent = originalText;
        }, 2000);
      }
    });
  });
});
