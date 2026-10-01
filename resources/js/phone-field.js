/* =====================================
   CHAMP TÉLÉPHONE AVEC INDICATIF (partials/phone-field)

   - Le select natif « indicatif_telephone » est remplacé par une liste moderne :
     drapeaux, pays favoris en tête, recherche par nom ou indicatif, clavier (↑ ↓ Entrée Échap).
   - Le numéro est limité au nombre de chiffres du pays choisi et mis en forme pendant la saisie
     (07 01 23 45 67). Un « 0 » de tête local reste accepté là où il se compose (France, Ghana…).
   - Un numéro collé au format international (+225…) est ramené au numéro national.
   - L'indication sous le champ suit la saisie : chiffres restants, puis « Numéro complet ».
   input.dataset.phoneValid vaut « 1 » quand la longueur est correcte (utilisé par les formulaires).
===================================== */

const digitsOf = (value) => value.replace(/\D/g, "");

function countryOf(option) {
  return {
    dial: option.value,
    code: option.value.replace("+", ""),
    iso: option.dataset.iso,
    name: option.dataset.name,
    lengths: option.dataset.lengths.split(",").map(Number),
    groups: option.dataset.groups.split(",").map(Number),
    example: option.dataset.example,
    trunk: option.dataset.trunk === "1",
    preferred: option.dataset.preferred === "1",
  };
}

function group(digits, groups) {
  const parts = [];
  let position = 0;

  for (const size of groups) {
    if (position >= digits.length) {
      break;
    }

    parts.push(digits.slice(position, position + size));
    position += size;
  }

  if (position < digits.length) {
    parts.push(digits.slice(position));
  }

  return parts.join(" ");
}

/**
 * Chiffres saisis → { lead: « 0 » local éventuel, core: numéro national limité, display: texte affiché }.
 */
function shape(raw, country) {
  let digits = digitsOf(raw);
  const max = Math.max(...country.lengths);

  // Indicatif collé avec le numéro : +225 07…, 00225 07…
  for (const prefix of ["00" + country.code, country.code]) {
    if (digits.startsWith(prefix) && digits.length - prefix.length >= Math.min(...country.lengths)) {
      digits = digits.slice(prefix.length);
      break;
    }
  }

  const lead = country.trunk && digits.startsWith("0") ? "0" : "";
  const core = digits.slice(lead.length).slice(0, max);
  const display = lead + group(core, country.groups);

  return { lead, core, display };
}

function enhance(field) {
  const select = field.querySelector("[data-phone-dial]");
  const input = field.querySelector("[data-phone-number]");
  const nativeFlag = field.querySelector("[data-phone-native-flag]");
  const hint = document.getElementById(input.getAttribute("aria-describedby"));

  if (!select || !input || field.dataset.enhanced === "1") {
    return;
  }

  field.dataset.enhanced = "1";
  field.classList.add("is-enhanced");

  const options = [...select.options].map(countryOf);
  const listId = `${input.id}-countries`;

  /* ---------- Bouton et panneau ---------- */

  const toggle = document.createElement("button");
  toggle.type = "button";
  toggle.className = "phone-picker-toggle";
  toggle.setAttribute("aria-haspopup", "listbox");
  toggle.setAttribute("aria-expanded", "false");
  toggle.setAttribute("aria-controls", listId);

  const panel = document.createElement("div");
  panel.className = "phone-picker-panel";
  panel.hidden = true;
  panel.innerHTML = `
    <div class="phone-picker-search">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" placeholder="Rechercher un pays ou un indicatif" aria-label="Rechercher un pays" autocomplete="off">
    </div>
    <ul class="phone-picker-list" role="listbox" id="${listId}" aria-label="Indicatifs"></ul>`;

  const search = panel.querySelector("input");
  const list = panel.querySelector("ul");

  select.closest(".phone-field-dial").append(toggle, panel);

  const current = () => countryOf(select.options[select.selectedIndex]);

  const renderToggle = () => {
    const country = current();
    toggle.innerHTML = `<span class="fi fi-${country.iso} phone-flag" aria-hidden="true"></span><span class="phone-picker-dial">${country.dial}</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i>`;
    toggle.setAttribute("aria-label", `Indicatif : ${country.name} ${country.dial}. Changer de pays`);

    if (nativeFlag) {
      nativeFlag.className = `fi fi-${country.iso} phone-flag`;
    }
  };

  /* ---------- Liste des pays (favoris, puis tous les pays) ---------- */

  let activeIndex = -1;

  const renderList = (query = "") => {
    const needle = query
      .trim()
      .toLowerCase()
      .normalize("NFD")
      .replace(/[̀-ͯ]/g, "");
    const matches = options.filter((country) => {
      if (needle === "") {
        return true;
      }

      const name = country.name.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");

      return name.includes(needle) || country.dial.includes(needle.replace(/[^\d+]/g, "") || "§") || country.iso === needle;
    });

    const preferred = needle === "" ? matches.filter((country) => country.preferred) : [];
    const others = needle === "" ? matches.filter((country) => !country.preferred) : matches;
    const selectedDial = select.value;

    const item = (country) => `
      <li role="option" class="phone-picker-option ${country.dial === selectedDial ? "is-selected" : ""}" data-dial="${country.dial}" aria-selected="${country.dial === selectedDial}">
        <span class="fi fi-${country.iso} phone-flag" aria-hidden="true"></span>
        <span class="phone-picker-name">${country.name}</span>
        <span class="phone-picker-code">${country.dial}</span>
        <i class="fa-solid fa-check" aria-hidden="true"></i>
      </li>`;

    list.innerHTML =
      matches.length === 0
        ? `<li class="phone-picker-empty">Aucun pays ne correspond.</li>`
        : (preferred.length > 0 ? preferred.map(item).join("") + `<li class="phone-picker-separator" role="presentation">Tous les pays</li>` : "") +
          others.map(item).join("");

    activeIndex = -1;
  };

  const optionItems = () => [...list.querySelectorAll(".phone-picker-option")];

  const highlight = (index) => {
    const items = optionItems();

    if (items.length === 0) {
      return;
    }

    activeIndex = (index + items.length) % items.length;
    items.forEach((element, position) => element.classList.toggle("is-active", position === activeIndex));
    items[activeIndex].scrollIntoView({ block: "nearest" });
  };

  /* ---------- Ouverture / fermeture ---------- */

  const open = () => {
    renderList();
    panel.hidden = false;
    field.classList.add("is-open");
    toggle.setAttribute("aria-expanded", "true");
    search.value = "";
    search.focus();

    const selectedIndex = optionItems().findIndex((element) => element.dataset.dial === select.value);
    highlight(Math.max(0, selectedIndex));
  };

  const close = (focusToggle = false) => {
    panel.hidden = true;
    field.classList.remove("is-open");
    toggle.setAttribute("aria-expanded", "false");

    if (focusToggle) {
      toggle.focus();
    }
  };

  const choose = (dial) => {
    if (select.value !== dial) {
      select.value = dial;
      select.dispatchEvent(new Event("change", { bubbles: true }));
    }

    close();
    input.focus();
  };

  toggle.addEventListener("click", () => (panel.hidden ? open() : close(true)));

  search.addEventListener("input", () => {
    renderList(search.value);
    highlight(0);
  });

  search.addEventListener("keydown", (event) => {
    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
      event.preventDefault();
      highlight(activeIndex + (event.key === "ArrowDown" ? 1 : -1));
    } else if (event.key === "Enter") {
      event.preventDefault();
      const element = optionItems()[activeIndex];

      if (element) {
        choose(element.dataset.dial);
      }
    } else if (event.key === "Escape") {
      event.preventDefault();
      close(true);
    } else if (event.key === "Tab") {
      close();
    }
  });

  list.addEventListener("mousedown", (event) => event.preventDefault());
  list.addEventListener("click", (event) => {
    const element = event.target.closest(".phone-picker-option");

    if (element) {
      choose(element.dataset.dial);
    }
  });

  document.addEventListener("click", (event) => {
    if (!panel.hidden && !field.contains(event.target)) {
      close();
    }
  });

  /* ---------- Numéro ---------- */

  const updateHint = (state, country) => {
    const lengths = country.lengths;
    const valid = lengths.includes(state.core.length);

    input.dataset.phoneValid = valid ? "1" : "0";
    field.classList.toggle("is-complete", valid);

    if (!hint) {
      return;
    }

    hint.classList.toggle("is-valid", valid);

    if (valid) {
      hint.innerHTML = `<i class="fa-solid fa-circle-check" aria-hidden="true"></i> Numéro complet · ${country.dial} ${group(state.core, country.groups)}`;
    } else if (state.core.length > 0) {
      const target = lengths.find((length) => length > state.core.length) ?? Math.max(...lengths);
      const remaining = target - state.core.length;
      hint.textContent = `${state.core.length} / ${target} chiffres · encore ${remaining} chiffre${remaining > 1 ? "s" : ""}`;
    } else {
      hint.textContent = `${lengths.join(" ou ")} chiffres · ex. ${country.example}`;
    }
  };

  const format = () => {
    const country = current();
    const caretDigits = digitsOf(input.value.slice(0, input.selectionStart ?? input.value.length)).length;
    const atEnd = (input.selectionStart ?? input.value.length) >= input.value.length;
    const state = shape(input.value, country);

    input.value = state.display;
    updateHint(state, country);

    // Curseur replacé après le même nombre de chiffres (les espaces ajoutés ne le déplacent pas)
    if (document.activeElement === input && !atEnd) {
      let position = 0;
      let seen = 0;

      while (position < input.value.length && seen < caretDigits) {
        if (/\d/.test(input.value[position])) {
          seen++;
        }

        position++;
      }

      input.setSelectionRange(position, position);
    }
  };

  input.addEventListener("input", format);

  select.addEventListener("change", () => {
    const country = current();
    renderToggle();
    input.placeholder = country.example;
    format();
  });

  renderToggle();
  input.placeholder = current().example;
  format();
}

export function initPhoneFields(root = document) {
  root.querySelectorAll("[data-phone-field]").forEach(enhance);
}
