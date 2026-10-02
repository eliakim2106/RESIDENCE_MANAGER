/* =====================================================================
   PARAMÈTRES DU SITE (super administrateur)
   Onglet Général : aperçus en direct (Google, pied de page, règles), compteurs, boutons +/-.
   Onglet Contenu des pages : blocs repliables, listes (ajout, retrait, flèches, glisser-déposer),
   résumés et aperçus en direct.
   Les deux : barre d'enregistrement qui apparaît dès qu'un champ change, alerte avant de quitter sans enregistrer.
===================================================================== */

const form = document.querySelector("[data-dirty-form]");

/* -------------------------------------
   Barre d'enregistrement et modifications en cours
------------------------------------- */

function initDirtyTracking() {
  const bar = document.querySelector("[data-savebar]");

  if (!form || !bar) {
    return () => {};
  }

  const text = bar.querySelector("[data-savebar-text]");
  const hasErrors = form.querySelector(".field-error") !== null;
  let leaving = false;

  // État du formulaire : valeurs, ordre des listes, nom des images choisies
  const snapshot = () =>
    JSON.stringify(
      [...new FormData(form).entries()]
        .filter(([name]) => name !== "_token" && name !== "_method")
        .map(([name, value]) => [name.replace(/\[items\]\[[^\]]+\]/, "[items][]"), value instanceof File ? value.name : value]),
    );

  let initial = snapshot();

  const check = () => {
    const dirty = hasErrors || snapshot() !== initial;
    bar.hidden = !dirty;
    document.body.classList.toggle("has-savebar", dirty);
    if (dirty && !hasErrors) {
      text.textContent = "Modifications non enregistrées";
    }
    return dirty;
  };

  form.addEventListener("input", check);
  form.addEventListener("change", check);

  // Quitter la page (sauf en enregistrant ou en annulant) : demande de confirmation
  document.addEventListener("submit", (event) => {
    if (!event.defaultPrevented) {
      leaving = true;
      if (event.target === form) {
        bar.querySelector("[data-savebar-submit]")?.classList.add("is-loading");
      }
    }
  });
  bar.querySelector("[data-savebar-cancel]")?.addEventListener("click", () => {
    leaving = true;
  });
  window.addEventListener("beforeunload", (event) => {
    if (!leaving && check() && !hasErrors) {
      event.preventDefault();
      event.returnValue = "";
    }
  });

  // L'état initial est relevé une fois les champs améliorés (téléphone) en place
  setTimeout(() => {
    initial = snapshot();
    check();
  }, 0);

  return check;
}

/* -------------------------------------
   Compteurs de caractères
   « 142 / 180 » ; vert dans la longueur conseillée (data-char-ideal), orange près de la limite.
------------------------------------- */

function initCharCounters() {
  const update = (field) => {
    const counter = field.closest(".form-group")?.querySelector("[data-char-counter]");
    const max = parseInt(field.getAttribute("maxlength") || "0", 10);
    const ideal = parseInt(field.dataset.charIdeal || "0", 10);

    if (!counter || !max) {
      return;
    }

    const length = field.value.length;
    counter.textContent = `${length} / ${max}`;
    counter.classList.toggle("is-ideal", ideal > 0 && length >= ideal - 15 && length <= ideal);
    counter.classList.toggle("is-over", length > (ideal || max * 0.9));
  };

  document.addEventListener("input", (event) => {
    if (event.target.matches?.("[data-char-count]")) {
      update(event.target);
    }
  });

  document.querySelectorAll("[data-char-count]").forEach(update);
}

/* -------------------------------------
   Onglet Général : aperçus en direct, boutons +/-, liens des réseaux
------------------------------------- */

function initGeneralPreviews() {
  if (!form || !form.querySelector("[name=site_name]")) {
    return;
  }

  const field = (name) => form.querySelector(`[name="${name}"]`);

  const render = () => {
    document.querySelectorAll("[data-preview]").forEach((element) => {
      const source = field(element.dataset.preview);
      if (!source) return;
      const limit = parseInt(element.dataset.previewLimit || "0", 10);
      const value = source.value.trim();
      element.textContent = limit && value.length > limit ? `${value.slice(0, limit - 1)}…` : value;
    });

    document.querySelectorAll("[data-preview-row]").forEach((row) => {
      row.hidden = (field(row.dataset.previewRow)?.value.trim() ?? "") === "";
    });

    document.querySelectorAll("[data-preview-phone]").forEach((element) => {
      const name = element.dataset.previewPhone;
      const number = field(name)?.value.trim() ?? "";
      const dial = field(`${name}_dial`)?.value ?? "";
      element.textContent = number ? `${dial} ${number}` : "";
    });

    form.querySelectorAll("[data-social-input]").forEach((input) => {
      const link = input.parentElement.querySelector("[data-social-open]");
      const valid = /^https?:\/\/\S+\.\S+/.test(input.value.trim());
      link.hidden = !valid;
      if (valid) link.href = input.value.trim();
    });
  };

  form.addEventListener("input", render);
  form.addEventListener("change", render);
  render();

  // Boutons − / + des règles de réservation
  form.querySelectorAll(".prm-stepper").forEach((stepper) => {
    const input = stepper.querySelector("input");
    stepper.querySelectorAll("[data-step]").forEach((button) => {
      button.addEventListener("click", () => {
        const step = parseFloat(button.dataset.step);
        const min = parseFloat(input.min);
        const max = parseFloat(input.max);
        const next = Math.min(max, Math.max(min, (parseFloat(input.value) || 0) + step));
        input.value = Number.isInteger(next) ? String(next) : next.toFixed(1);
        input.dispatchEvent(new Event("input", { bubbles: true }));
      });
    });
  });
}

/* -------------------------------------
   Onglet Contenu : blocs repliables
------------------------------------- */

function initBlocks() {
  const blocks = [...document.querySelectorAll("[data-block]")];

  if (blocks.length === 0) {
    return;
  }

  const setOpen = (block, open) => {
    block.classList.toggle("is-open", open);
    block.querySelector("[data-block-toggle]")?.setAttribute("aria-expanded", open ? "true" : "false");
  };

  blocks.forEach((block) => {
    block.querySelector("[data-block-toggle]")?.addEventListener("click", () => setOpen(block, !block.classList.contains("is-open")));
  });

  document.querySelectorAll("[data-blocks-toggle]").forEach((button) => {
    button.addEventListener("click", () => blocks.forEach((block) => setOpen(block, button.dataset.blocksToggle === "open")));
  });

  // Sommaire : ouvre le bloc visé
  document.querySelectorAll("[data-block-link]").forEach((link) => {
    link.addEventListener("click", () => {
      const block = document.getElementById(`bloc-${link.dataset.blockLink}`);
      if (block) setOpen(block, true);
    });
  });

  // Ouverture initiale : bloc de l'adresse (#bloc-…), sinon blocs en erreur, sinon le premier
  const target = location.hash ? document.querySelector(location.hash) : null;
  if (target?.matches("[data-block]")) {
    setOpen(target, true);
    target.scrollIntoView({ block: "start" });
  } else if (!blocks.some((block) => block.classList.contains("is-open"))) {
    setOpen(blocks[0], true);
  }

  // Bloc masqué : grisé, et pastille « Masqué »
  document.addEventListener("change", (event) => {
    if (event.target.matches?.("[data-visible-toggle]")) {
      const block = event.target.closest("[data-block]");
      block.classList.toggle("is-hidden", !event.target.checked);
      const chip = block.querySelector("[data-hidden-chip]");
      if (chip) chip.hidden = event.target.checked;
    }
  });
}

/* -------------------------------------
   Onglet Contenu : images, icônes et résumés des éléments
------------------------------------- */

function initLivePreviews() {
  document.addEventListener("change", (event) => {
    const input = event.target;

    if (input.matches?.("[data-image-input]") && input.files?.[0]) {
      const url = URL.createObjectURL(input.files[0]);
      const field = input.closest("[data-image-field]");
      field.querySelector("[data-image-preview]").src = url;
      field.querySelector("[data-image-pending]").hidden = false;
      field.querySelector("[data-image-label]").textContent = input.files[0].name;

      if (input.dataset.summarySource === "thumb") {
        const thumb = input.closest("[data-list-item]")?.querySelector("[data-summary-thumb]");
        if (thumb) thumb.src = url;
      }
    }

    if (input.matches?.("[data-icon-select]")) {
      const icon = `fa-solid ${input.value}`;
      input.closest(".prm-icon-select").querySelector("[data-icon-preview]").className = icon;

      if (input.dataset.summarySource === "icon") {
        const summary = input.closest("[data-list-item]")?.querySelector("[data-summary-icon]");
        if (summary) summary.className = icon;
      }
    }
  });

  document.addEventListener("input", (event) => {
    const input = event.target;
    const role = input.dataset?.summarySource;

    if (role !== "title" && role !== "subtitle") {
      return;
    }

    const target = input.closest("[data-list-item]")?.querySelector(role === "title" ? "[data-summary-title]" : "[data-summary-subtitle]");
    if (target) {
      const value = input.value.trim();
      target.textContent = role === "title" ? value || target.dataset.empty : value.length > 90 ? `${value.slice(0, 89)}…` : value;
    }
  });
}

/* -------------------------------------
   Onglet Contenu : listes (ajout, retrait, flèches, glisser-déposer)
------------------------------------- */

function initLists(onChange) {
  document.querySelectorAll("[data-content-list]").forEach((list) => {
    const items = list.querySelector("[data-list-items]");
    const template = list.querySelector("[data-list-template]");
    const addButton = list.querySelector("[data-list-add]");
    const block = list.closest("[data-block]");
    const count = block?.querySelector("[data-list-count]");
    const min = parseInt(list.dataset.min || "0", 10);
    const max = parseInt(list.dataset.max || "99", 10);

    // Numéro libre pour les noms des champs d'un nouvel élément (l'ordre envoyé suit celui de la page)
    let nextIndex = items.children.length;
    items.querySelectorAll("[name]").forEach((field) => {
      const match = field.name.match(/\[items\]\[(\d+)\]/);
      if (match) nextIndex = Math.max(nextIndex, parseInt(match[1], 10) + 1);
    });

    const refresh = () => {
      const rows = [...items.children];
      rows.forEach((row, position) => {
        row.querySelector("[data-item-number]").textContent = position + 1;
        row.querySelector("[data-item-up]").disabled = position === 0;
        row.querySelector("[data-item-down]").disabled = position === rows.length - 1;
        row.querySelector("[data-item-remove]").disabled = rows.length <= min;
      });
      addButton.disabled = rows.length >= max;
      addButton.title = rows.length >= max ? `${max} au plus` : "";
      if (count) count.textContent = rows.length;
      onChange();
    };

    const setOpen = (row, open) => {
      row.classList.toggle("is-open", open);
      row.querySelector("[data-item-toggle]").setAttribute("aria-expanded", open ? "true" : "false");
    };

    addButton.addEventListener("click", () => {
      items.insertAdjacentHTML("beforeend", template.innerHTML.replaceAll("__INDEX__", String(nextIndex++)));
      const row = items.lastElementChild;
      row.classList.add("is-new");
      setOpen(row, true);
      refresh();
      row.querySelectorAll("[data-char-count]").forEach((field) => field.dispatchEvent(new Event("input", { bubbles: true })));
      row.scrollIntoView({ behavior: "smooth", block: "center" });
      row.querySelector(".prm-item-body input:not([type=hidden]):not([type=file]), .prm-item-body textarea")?.focus({ preventScroll: true });
    });

    items.addEventListener("click", (event) => {
      const button = event.target.closest("button");
      const row = button?.closest("[data-list-item]");

      if (!button || !row || button.disabled) {
        return;
      }

      if (button.matches("[data-item-toggle]")) {
        setOpen(row, !row.classList.contains("is-open"));
        return;
      }

      if (button.matches("[data-item-up]") && row.previousElementSibling) {
        row.previousElementSibling.before(row);
      } else if (button.matches("[data-item-down]") && row.nextElementSibling) {
        row.nextElementSibling.after(row);
      } else if (button.matches("[data-item-remove]")) {
        const title = row.querySelector("[data-summary-title]")?.textContent.trim();
        const ask = window.dsConfirm ?? (({ title: question, message }) => Promise.resolve(window.confirm(`${question}\n${message}`)));

        // Confirmation dans la modale de l'administration (admin/modals.js)
        ask({ title: `Retirer « ${title} » ?`, message: "Le retrait sera définitif à l’enregistrement.", confirmLabel: "Retirer", icon: "fa-trash-can", tone: "danger" }).then((accepted) => {
          if (accepted) {
            row.remove();
            refresh();
          }
        });
        return;
      } else {
        return;
      }

      refresh();
    });

    /* Glisser-déposer : la poignée rend l'élément déplaçable */
    let dragged = null;

    items.addEventListener("pointerdown", (event) => {
      const handle = event.target.closest("[data-drag-handle]");
      if (handle) handle.closest("[data-list-item]").draggable = true;
    });

    items.addEventListener("dragstart", (event) => {
      dragged = event.target.closest("[data-list-item]");
      if (!dragged) return;
      dragged.classList.add("is-dragging");
      event.dataTransfer.effectAllowed = "move";
      event.dataTransfer.setData("text/plain", "");
    });

    items.addEventListener("dragover", (event) => {
      if (!dragged) return;
      event.preventDefault();
      const over = event.target.closest("[data-list-item]");
      if (!over || over === dragged) return;
      const box = over.getBoundingClientRect();
      over[event.clientY > box.top + box.height / 2 ? "after" : "before"](dragged);
    });

    items.addEventListener("dragend", () => {
      if (!dragged) return;
      dragged.classList.remove("is-dragging");
      dragged.draggable = false;
      dragged = null;
      refresh();
    });

    refresh();
  });
}

const checkDirty = initDirtyTracking();
initCharCounters();
initGeneralPreviews();
initBlocks();
initLivePreviews();
initLists(checkDirty);
