// Função para copiar URL para a área de transferência
function copyToClipboard(elementId) {
  const copyText = document.getElementById(elementId);
  copyText.select();
  copyText.setSelectionRange(0, 99999); // Para dispositivos móveis
  document.execCommand("copy");

  // Feedback visual
  const button = copyText.nextElementSibling;
  const originalText = button.textContent;
  button.textContent = "Copiado!";
  setTimeout(() => {
    button.textContent = originalText;
  }, 2000);
}
