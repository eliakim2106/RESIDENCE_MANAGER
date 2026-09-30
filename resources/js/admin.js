import * as bootstrap from "bootstrap";

import { initAlerts } from "./alerts";
import { initDatepickers } from "./datepicker";

window.bootstrap = bootstrap;

/* =====================================
   MENU LATÉRAL
===================================== */

// Grand écran : le bouton replie le menu (choix mémorisé). En dessous de 992 px : il ouvre le menu en tiroir.
const DRAWER_QUERY = window.matchMedia("(max-width: 991.98px)");
const COLLAPSED_KEY = "ds-admin-sidebar-collapsed";

function initSidebar() {
  const toggleBtn = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".sidebar");
  const mainContent = document.querySelector(".main-content");

  if (!toggleBtn || !sidebar || !mainContent) {
    return;
  }

  const setCollapsed = (collapsed) => {
    sidebar.classList.toggle("collapsed", collapsed);
    mainContent.classList.toggle("expanded", collapsed);
  };

  const setDrawerOpen = (open) => {
    document.body.classList.toggle("sidebar-open", open);
    toggleBtn.setAttribute("aria-expanded", String(open));
  };

  try {
    setCollapsed(localStorage.getItem(COLLAPSED_KEY) === "1");
  } catch {
    // Stockage indisponible : menu déplié par défaut
  }

  toggleBtn.addEventListener("click", () => {
    if (DRAWER_QUERY.matches) {
      setDrawerOpen(!document.body.classList.contains("sidebar-open"));
      return;
    }

    const collapsed = !sidebar.classList.contains("collapsed");
    setCollapsed(collapsed);

    try {
      localStorage.setItem(COLLAPSED_KEY, collapsed ? "1" : "0");
    } catch {
      // Préférence non mémorisée
    }
  });

  document.querySelectorAll("[data-sidebar-close]").forEach((el) => {
    el.addEventListener("click", () => setDrawerOpen(false));
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      setDrawerOpen(false);
    }
  });

  DRAWER_QUERY.addEventListener("change", () => setDrawerOpen(false));
}

/* =====================================
   TABLEAUX
   Sur mobile, chaque ligne s'affiche en carte : les cellules reprennent l'intitulé de leur colonne.
===================================== */

function initResponsiveTables() {
  document.querySelectorAll(".custom-table").forEach((table) => {
    const headers = [...table.querySelectorAll("thead th")].map((th) => th.textContent.trim());

    table.querySelectorAll("tbody tr").forEach((row) => {
      [...row.children].forEach((cell, index) => {
        if (!cell.hasAttribute("colspan") && headers[index] && !cell.hasAttribute("data-label")) {
          cell.setAttribute("data-label", headers[index]);
        }
      });
    });
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
   MODALES D'ACTION, CONFIRMATIONS, FILTRES
   [data-modal-open="id"] ouvre la modale #id, [data-modal-close] la ferme.
   Une modale partagée entre plusieurs lignes reçoit du bouton data-form-action (adresse du formulaire)
   et data-name (affiché dans [data-modal-name]).
   form[data-confirm] demande une confirmation avant l'envoi.
   select[data-auto-submit] envoie son formulaire dès qu'on change la valeur.
===================================== */

function initActionModals() {
  const close = (modal) => modal.classList.remove("show");

  document.querySelectorAll("[data-modal-open]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const modal = document.getElementById(btn.dataset.modalOpen);

      if (!modal) {
        return;
      }

      const form = modal.querySelector("form");

      if (btn.dataset.formAction && form) {
        form.action = btn.dataset.formAction;

        // Conservés pour rouvrir la modale sur la bonne ligne après une erreur de validation
        form.querySelector("input[name='_form_action']")?.setAttribute("value", btn.dataset.formAction);
        form.querySelector("input[name='_form_name']")?.setAttribute("value", btn.dataset.name ?? "");
      }

      if (btn.dataset.name) {
        modal.querySelectorAll("[data-modal-name]").forEach((el) => {
          el.textContent = btn.dataset.name;
        });
      }

      modal.classList.add("show");
      modal.querySelector("input:not([type=hidden]), select, textarea")?.focus();
    });
  });

  document.querySelectorAll(".modal-overlay[data-action-modal]").forEach((modal) => {
    modal.querySelectorAll("[data-modal-close]").forEach((btn) => btn.addEventListener("click", () => close(modal)));

    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        close(modal);
      }
    });

    // Rouvre la modale après une erreur de validation
    if (modal.dataset.openOnLoad !== undefined) {
      modal.classList.add("show");
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      document.querySelectorAll(".modal-overlay[data-action-modal].show").forEach(close);
    }
  });

  document.querySelectorAll("form[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!window.confirm(form.dataset.confirm)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll("select[data-auto-submit]").forEach((select) => {
    select.addEventListener("change", () => select.form?.requestSubmit());
  });
}

/* =====================================
   PHOTO DE PROFIL
   Aperçu immédiat de l'image choisie ; « Retirer la photo » affiche les initiales.
===================================== */

function initAvatarField() {
  const field = document.querySelector("[data-avatar-field]");

  if (!field) {
    return;
  }

  const preview = field.querySelector("[data-avatar-preview]");
  const input = field.querySelector("[data-avatar-input]");
  const remove = field.querySelector("[data-avatar-remove]");
  const original = preview.src;

  input?.addEventListener("change", () => {
    const file = input.files?.[0];

    if (!file) {
      return;
    }

    preview.src = URL.createObjectURL(file);

    if (remove) {
      remove.checked = false;
    }
  });

  remove?.addEventListener("change", () => {
    if (remove.checked && input) {
      input.value = "";
    }

    preview.src = remove.checked ? preview.dataset.fallback : original;
  });
}

/* =====================================
   CALENDRIER D'OCCUPATION
   La grille défile jusqu'au jour courant.
===================================== */

function initOccupancyGrid() {
  const scroller = document.querySelector(".occ-scroll");
  const today = scroller?.querySelector("thead th.is-today");

  if (!scroller || !today) {
    return;
  }

  const unitColumn = scroller.querySelector(".occ-unit-head")?.offsetWidth ?? 0;
  scroller.scrollLeft = Math.max(0, today.offsetLeft - unitColumn - today.offsetWidth * 3);
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

/* =====================================
   APERÇU EN DIRECT DES FORMULAIRES (types, équipements)
   [data-preview-source="x"] alimente [data-preview="x"] ; l'interrupteur de statut met à jour le badge.
===================================== */

function initLivePreview() {
  const form = document.querySelector(".admin-form");

  if (!form || !form.querySelector("[data-preview]")) {
    return;
  }

  const render = () => {
    form.querySelectorAll("[data-preview]").forEach((target) => {
      const source = form.querySelector(`[data-preview-source="${target.dataset.preview}"]`);

      if (!source) {
        return;
      }

      // Pour une liste : le libellé de l'option choisie, sauf l'option vide « Sélectionner… »
      const value = source.tagName === "SELECT"
        ? (source.value === "" ? "" : source.options[source.selectedIndex]?.text ?? "")
        : source.value;

      target.textContent = value.trim() || target.dataset.previewEmpty || "";
    });

    const status = form.querySelector("[data-status-toggle]");
    const badge = form.querySelector("[data-preview-status]");

    if (status && badge) {
      badge.textContent = status.checked ? "Actif" : "Inactif";
      badge.className = status.checked ? "badge-success" : "badge-neutral";
    }

    const popular = form.querySelector("[data-popular-toggle]");
    const star = form.querySelector("[data-preview-popular]");

    if (popular && star) {
      star.hidden = !popular.checked;
    }
  };

  // Les scripts des types remplissent la description au changement de type : on lit les champs juste après
  ["input", "change"].forEach((type) => form.addEventListener(type, () => setTimeout(render, 0)));
  render();
}

initSidebar();
initResponsiveTables();
initLivePreview();
initDeleteModal();
initActionModals();
initAvatarField();
initOccupancyGrid();
initAlerts();
initDatepickers();
