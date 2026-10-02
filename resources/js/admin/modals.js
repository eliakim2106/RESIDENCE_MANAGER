/* =====================================
   MODALES DE L'ADMINISTRATION
   - .modal-overlay > .modal-card : ouverture (.show), fermeture (×, Retour, clic à côté, Échap),
     focus gardé dans la modale puis rendu au bouton d'origine, page figée derrière.
   - [data-modal-open="id"] ouvre la modale #id. Une modale partagée entre plusieurs lignes reçoit
     du bouton data-form-action (adresse du formulaire) et data-name (affiché dans [data-modal-name]).
   - .delete-btn (data-url, data-name, data-detail) ouvre la modale de suppression.
   - form[data-confirm="Question ? Précision."] demande confirmation dans une modale (confirmAction).
===================================== */

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const openStack = [];

function isOpen(modal) {
  return modal.classList.contains("show");
}

export function openModal(modal, { trigger = document.activeElement, focus = null } = {}) {
  if (!modal || isOpen(modal)) {
    return;
  }

  modal.returnFocus = trigger instanceof HTMLElement ? trigger : null;
  modal.classList.add("show");
  modal.setAttribute("aria-hidden", "false");
  openStack.push(modal);
  document.body.classList.add("modal-open");

  // Le premier champ du formulaire, sinon le bouton principal, sinon la croix
  const target =
    focus ??
    modal.querySelector(".modal-card input:not([type=hidden]):not([disabled]), .modal-card select, .modal-card textarea") ??
    modal.querySelector(".modal-actions [type=submit], .modal-actions [data-confirm-accept]") ??
    modal.querySelector(".modal-close");

  requestAnimationFrame(() => target?.focus({ preventScroll: true }));
}

export function closeModal(modal) {
  if (!modal || !isOpen(modal)) {
    return;
  }

  modal.classList.remove("show");
  modal.setAttribute("aria-hidden", "true");
  openStack.splice(openStack.indexOf(modal), 1);

  if (openStack.length === 0) {
    document.body.classList.remove("modal-open");
  }

  modal.dispatchEvent(new CustomEvent("modal:closed"));
  modal.returnFocus?.focus({ preventScroll: true });
}

/**
 * Croix de fermeture, rôle de dialogue et fermeture au clic à côté, pour chaque modale.
 */
function enhance(modal) {
  const card = modal.querySelector(".modal-card");

  if (!card || modal.dataset.modalReady) {
    return;
  }

  modal.dataset.modalReady = "1";
  modal.setAttribute("aria-hidden", isOpen(modal) ? "false" : "true");

  if (!card.hasAttribute("role")) {
    card.setAttribute("role", "dialog");
    card.setAttribute("aria-modal", "true");
  }

  const title = card.querySelector("h3");
  if (title && !card.hasAttribute("aria-labelledby")) {
    title.id ||= `${modal.id || "modal"}-title`;
    card.setAttribute("aria-labelledby", title.id);
  }

  if (!card.querySelector(".modal-close")) {
    const close = document.createElement("button");
    close.type = "button";
    close.className = "modal-close";
    close.setAttribute("aria-label", "Fermer");
    close.dataset.modalClose = "";
    close.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
    card.prepend(close);
  }

  modal.addEventListener("click", (event) => {
    if (event.target === modal || event.target.closest("[data-modal-close]")) {
      closeModal(modal);
    }
  });
}

function trapFocus(event) {
  const modal = openStack[openStack.length - 1];

  if (!modal) {
    return;
  }

  if (event.key === "Escape") {
    event.preventDefault();
    closeModal(modal);
    return;
  }

  if (event.key !== "Tab") {
    return;
  }

  const items = [...modal.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);

  if (items.length === 0) {
    return;
  }

  const first = items[0];
  const last = items[items.length - 1];

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  } else if (!modal.contains(document.activeElement)) {
    event.preventDefault();
    first.focus();
  }
}

/* -------------------------------------
   Confirmation : remplace window.confirm
------------------------------------- */

/**
 * Ouvre la modale de confirmation ; la promesse renvoie true si l'utilisateur confirme.
 *
 * @param {{ title: string, message?: string, confirmLabel?: string, icon?: string, tone?: "danger"|"info" }} options
 * @returns {Promise<boolean>}
 */
export function confirmAction({ title, message = "", confirmLabel = "Confirmer", icon = "", tone = "info" }) {
  const modal = document.getElementById("confirmModal");

  if (!modal) {
    return Promise.resolve(window.confirm([title, message].filter(Boolean).join("\n")));
  }

  const danger = tone === "danger";
  const accept = modal.querySelector("[data-confirm-accept]");
  const text = modal.querySelector("[data-confirm-text]");

  modal.querySelector("[data-confirm-title]").textContent = title;
  text.textContent = message;
  text.hidden = message === "";

  modal.querySelector(".modal-icon").className = `modal-icon ${danger ? "" : "modal-icon-info"}`.trim();
  modal.querySelector(".modal-icon i").className = `fa-solid ${icon || (danger ? "fa-triangle-exclamation" : "fa-circle-question")}`;

  accept.className = danger ? "btn-delete" : "btn-save";
  accept.textContent = confirmLabel;

  return new Promise((resolve) => {
    let accepted = false;

    const onAccept = () => {
      accepted = true;
      closeModal(modal);
    };

    accept.addEventListener("click", onAccept, { once: true });
    modal.addEventListener(
      "modal:closed",
      () => {
        accept.removeEventListener("click", onAccept);
        resolve(accepted);
      },
      { once: true },
    );

    openModal(modal, { focus: accept });
  });
}

/**
 * Libellés tirés du formulaire : la question devient le titre, la suite la précision ;
 * le bouton de confirmation reprend le libellé et l'icône du bouton d'envoi.
 */
function confirmOptions(form, submitter) {
  const raw = form.dataset.confirm.trim();
  const split = raw.indexOf("? ");
  const button = submitter ?? form.querySelector("[type=submit]");
  const label = (button?.getAttribute("title") || button?.textContent || "").replace(/\s+/g, " ").trim();
  const danger = form.dataset.confirmTone === "danger" || /delete|danger/.test(button?.className ?? "");
  const icon = [...(button?.querySelector("i")?.classList ?? [])].find((name) => name.startsWith("fa-") && !["fa-solid", "fa-regular"].includes(name));

  return {
    title: split === -1 ? raw : raw.slice(0, split + 1),
    message: split === -1 ? "" : raw.slice(split + 2),
    confirmLabel: form.dataset.confirmLabel || label || "Confirmer",
    icon,
    tone: danger ? "danger" : "info",
  };
}

/* -------------------------------------
   Initialisation
------------------------------------- */

export function initModals() {
  document.querySelectorAll(".modal-overlay").forEach(enhance);
  document.addEventListener("keydown", trapFocus);

  // Ouverture d'une modale d'action
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

      openModal(modal, { trigger: btn });
    });
  });

  // Rouvre la modale après une erreur de validation
  document.querySelectorAll(".modal-overlay[data-open-on-load]").forEach((modal) => openModal(modal));

  // Suppression
  const deleteModal = document.getElementById("deleteModal");
  const deleteForm = document.getElementById("deleteForm");
  const deleteText = document.getElementById("deleteText");

  if (deleteModal && deleteForm && deleteText) {
    document.querySelectorAll(".delete-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const name = document.createElement("strong");
        name.textContent = btn.dataset.name ?? "";

        deleteText.replaceChildren(
          "Vous êtes sur le point de supprimer ",
          name,
          ".",
          // Précision facultative : ce qui sera retiré en même temps (data-detail)
          ...(btn.dataset.detail ? [document.createElement("br"), btn.dataset.detail] : []),
        );

        deleteForm.action = btn.dataset.url;
        openModal(deleteModal, { trigger: btn, focus: deleteModal.querySelector("[data-modal-close]:not(.modal-close)") });
      });
    });
  }

  // Confirmation avant l'envoi d'un formulaire
  document.addEventListener("submit", (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
      return;
    }

    if (form.dataset.confirmed) {
      delete form.dataset.confirmed;
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const submitter = event.submitter;

    confirmAction(confirmOptions(form, submitter)).then((accepted) => {
      if (accepted) {
        form.dataset.confirmed = "1";
        form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
      }
    });
  }, true);

  window.dsConfirm = confirmAction;
}
