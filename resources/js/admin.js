import * as bootstrap from "bootstrap";

import { initAlerts } from "./alerts";

window.bootstrap = bootstrap;

/* =====================================
   MENU LATÉRAL
===================================== */

function initSidebar() {
  const toggleBtn = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (!toggleBtn || !sidebar || !mainContent) {
    return;
  }

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");
  });
}

/* =====================================
   MODALE DE SUPPRESSION
   Chaque bouton .delete-btn porte data-url (route DELETE) et data-name.
===================================== */

function initDeleteModal() {
  const modal = document.getElementById("deleteModal");
  const form = document.getElementById("deleteForm");
  const deleteText = document.getElementById("deleteText");
  const cancelDelete = document.getElementById("cancelDelete");

  if (!modal || !form || !deleteText || !cancelDelete) {
    return;
  }

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const name = document.createElement("strong");
      name.textContent = btn.dataset.name ?? "";

      deleteText.replaceChildren(
        "Vous êtes sur le point de supprimer ",
        name,
        ".",
        document.createElement("br"),
        "Cette action est irréversible.",
      );

      form.action = btn.dataset.url;
      modal.classList.add("show");
    });
  });

  const close = () => modal.classList.remove("show");

  cancelDelete.addEventListener("click", close);

  modal.addEventListener("click", (event) => {
    if (event.target === modal) {
      close();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      close();
    }
  });
}

/* =====================================
   PRÉCHARGEMENT
===================================== */

window.addEventListener("load", () => {
  const loader = document.getElementById("page-loader");

  if (!loader) {
    return;
  }

  loader.style.opacity = "0";
  setTimeout(() => (loader.style.display = "none"), 800);
});

initSidebar();
initDeleteModal();
initAlerts();
