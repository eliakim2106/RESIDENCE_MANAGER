/* =====================================
   ALERTES (messages flash)
===================================== */

export function initAlerts() {
  document.querySelectorAll(".alert").forEach((alert) => {
    const closeButton = alert.querySelector(".alert-close");

    if (!closeButton) {
      return;
    }

    closeButton.addEventListener("click", () => {
      alert.style.opacity = "0";
      alert.style.transform = "translateY(-10px)";

      setTimeout(() => alert.remove(), 300);
    });
  });
}
