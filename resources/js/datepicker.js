/* =====================================
   CALENDRIER DS HOLDING (site et administration)

   Tout champ portant data-datepicker reçoit le calendrier personnalisé :
     <input type="date" name="arrivee" data-datepicker>

   Options par attributs :
     min / max                     bornes (AAAA-MM-JJ), ou « today » via data-datepicker-min
     data-placeholder              texte affiché quand aucune date n’est choisie
     data-datepicker-start="x"     début d'une période nommée x (ex. arrivée)
     data-datepicker-end="x"       fin de la période x (ex. départ) : au moins le lendemain du début

   Le champ d'origine garde son nom et envoie AAAA-MM-JJ ; l'utilisateur voit « lun. 12 oct. 2026 ».
   Sans JavaScript, le champ date natif reste utilisable.
===================================== */

import flatpickr from "flatpickr";
import { French } from "flatpickr/dist/l10n/fr.js";

flatpickr.localize(French);

const DAY_MS = 86_400_000;
const WIDE_QUERY = window.matchMedia("(min-width: 768px)");

const pickers = new WeakMap();

const startOfDay = (date) => {
  const copy = new Date(date);
  copy.setHours(0, 0, 0, 0);
  return copy;
};

const addDays = (date, days) => new Date(startOfDay(date).getTime() + days * DAY_MS);

const nightsBetween = (start, end) => Math.round((startOfDay(end) - startOfDay(start)) / DAY_MS);

const formatShort = (date) =>
  date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" });

/* -------------------------------------
   Périodes (arrivée → départ)
------------------------------------- */

function rangeOf(input) {
  const name = input.dataset.datepickerStart ?? input.dataset.datepickerEnd;

  if (!name) {
    return null;
  }

  const start = document.querySelector(`[data-datepicker-start="${name}"]`);
  const end = document.querySelector(`[data-datepicker-end="${name}"]`);

  return { start: start && pickers.get(start), end: end && pickers.get(end) };
}

// Colore la période sélectionnée dans les deux calendriers
function decorateDay(input, dayElem) {
  const range = rangeOf(input);

  if (!range?.start || !range?.end) {
    return;
  }

  const from = range.start.selectedDates[0];
  const to = range.end.selectedDates[0];
  const day = startOfDay(dayElem.dateObj).getTime();

  if (from && day === startOfDay(from).getTime()) {
    dayElem.classList.add("ds-range-start");
  }

  if (to && day === startOfDay(to).getTime()) {
    dayElem.classList.add("ds-range-end");
  }

  if (from && to && day > startOfDay(from).getTime() && day < startOfDay(to).getTime()) {
    dayElem.classList.add("ds-in-range");
  }
}

// Pied du calendrier : résumé de la période et bouton « Effacer »
function buildFooter(instance, input) {
  const footer = document.createElement("div");
  footer.className = "ds-calendar-footer";

  const summary = document.createElement("span");
  summary.className = "ds-calendar-summary";

  const clear = document.createElement("button");
  clear.type = "button";
  clear.className = "ds-calendar-clear";
  clear.textContent = "Effacer";
  clear.addEventListener("click", () => {
    instance.clear();
    instance.close();
  });

  footer.append(summary, clear);
  instance.calendarContainer.append(footer);

  instance._dsUpdateSummary = () => {
    const range = rangeOf(input);
    const from = range?.start?.selectedDates[0];
    const to = range?.end?.selectedDates[0];

    if (from && to) {
      const nights = nightsBetween(from, to);
      summary.textContent = `${formatShort(from)} → ${formatShort(to)} · ${nights} nuit${nights > 1 ? "s" : ""}`;
    } else if (range && from) {
      summary.textContent = `Arrivée le ${formatShort(from)} · choisissez le départ`;
    } else if (range) {
      summary.textContent = "Choisissez votre date d’arrivée";
    } else {
      const date = instance.selectedDates[0];
      summary.textContent = date ? date.toLocaleDateString("fr-FR", { dateStyle: "full" }) : "Aucune date choisie";
    }
  };

  instance._dsUpdateSummary();
}

/* -------------------------------------
   Positionnement
   Remplace celui de flatpickr : sous le champ (au-dessus s'il manque de la place),
   toujours à au moins 12 px des bords de l'écran. Celui d'origine échoue sur les écrans
   étroits dès qu'une feuille de style externe (Google Fonts) est chargée.
------------------------------------- */

const VIEWPORT_MARGIN = 12;
const FIELD_GAP = 8;

// Champ dans un bloc fixe ou collant (ex. carte de réservation) : il ne bouge pas quand la page défile
function isInFixedContext(element) {
  for (let node = element.parentElement; node && node !== document.body; node = node.parentElement) {
    const { position, display } = getComputedStyle(node);

    // Un bloc en display: contents n'a pas de boîte : sa position n'a aucun effet
    if (display !== "contents" && (position === "fixed" || position === "sticky")) {
      return true;
    }
  }

  return false;
}

function positionCalendar(instance, customElement) {
  const calendar = instance.calendarContainer;
  const target = customElement ?? instance._positionElement ?? instance.altInput ?? instance.input;

  if (!calendar || !target) {
    return;
  }

  const field = target.getBoundingClientRect();
  const viewportWidth = document.documentElement.clientWidth;
  const width = calendar.offsetWidth;
  const height = calendar.offsetHeight;

  const spaceBelow = window.innerHeight - field.bottom;
  const showOnTop = spaceBelow < height + FIELD_GAP && field.top > spaceBelow;

  let top = window.scrollY + (showOnTop ? field.top - height - FIELD_GAP : field.bottom + FIELD_GAP);

  // Faire défiler la page ne rapprocherait pas un champ fixe : le calendrier est recalé dans l'écran
  if (isInFixedContext(target) && height + 2 * VIEWPORT_MARGIN <= window.innerHeight) {
    const minTop = window.scrollY + VIEWPORT_MARGIN;
    const maxTop = window.scrollY + window.innerHeight - height - VIEWPORT_MARGIN;
    top = Math.min(Math.max(top, minTop), maxTop);
  }

  const maxLeft = window.scrollX + viewportWidth - width - VIEWPORT_MARGIN;
  const left = Math.max(window.scrollX + VIEWPORT_MARGIN, Math.min(window.scrollX + field.left, maxLeft));

  calendar.style.top = `${top}px`;
  calendar.style.left = `${left}px`;
  calendar.style.right = "auto";
  calendar.classList.toggle("arrowTop", !showOnTop);
  calendar.classList.toggle("arrowBottom", showOnTop);
}

// À l'ouverture, fait défiler la page juste assez pour que le calendrier soit entièrement visible
function revealCalendar(instance) {
  if (isInFixedContext(instance.altInput ?? instance.input)) {
    return;
  }

  requestAnimationFrame(() => {
    const box = instance.calendarContainer.getBoundingClientRect();
    const overflowBottom = box.bottom - (window.innerHeight - VIEWPORT_MARGIN);
    const overflowTop = VIEWPORT_MARGIN - box.top;

    if (overflowBottom > 0 && box.height < window.innerHeight) {
      window.scrollBy({ top: overflowBottom, behavior: "smooth" });
    } else if (overflowTop > 0) {
      window.scrollBy({ top: -overflowTop, behavior: "smooth" });
    }
  });
}

function refreshRange(input) {
  const range = rangeOf(input);

  [range?.start, range?.end].filter(Boolean).forEach((picker) => {
    picker.redraw();
    picker._dsUpdateSummary?.();
  });
}

/* -------------------------------------
   Initialisation
------------------------------------- */

function readBound(input, attribute) {
  const value = input.dataset[`datepicker${attribute}`] ?? input.getAttribute(attribute.toLowerCase());

  if (!value) {
    return undefined;
  }

  return value === "today" ? "today" : value;
}

function createPicker(input) {
  const isRange = Boolean(input.dataset.datepickerStart || input.dataset.datepickerEnd);
  const label = input.id ? document.querySelector(`label[for="${input.id}"]`) : null;

  const instance = flatpickr(input, {
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "D j M Y",
    altInputClass: `${input.className} ds-date-input`.trim(),
    disableMobile: true,
    minDate: readBound(input, "Min"),
    maxDate: readBound(input, "Max"),
    showMonths: isRange && WIDE_QUERY.matches ? 2 : 1,
    monthSelectorType: "static",
    position: positionCalendar,
    prevArrow: '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>',
    nextArrow: '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>',
    onDayCreate: (_dates, _str, _fp, dayElem) => decorateDay(input, dayElem),
    onReady: (_dates, _str, fp) => {
      fp.calendarContainer.classList.add("ds-calendar");

      if (isRange) {
        fp.calendarContainer.classList.add("ds-calendar-range");
      }

      // Le champ visible reprend l'identifiant : le libellé et le JavaScript existant le ciblent
      if (input.id) {
        fp.altInput.id = input.id;
        input.removeAttribute("id");
      }

      fp.altInput.placeholder = input.dataset.placeholder || "jj/mm/aaaa";
      fp.altInput.setAttribute("autocomplete", "off");
      fp.altInput.setAttribute("aria-haspopup", "dialog");

      if (label) {
        label.htmlFor = fp.altInput.id;
      }

      buildFooter(fp, input);
    },
    onOpen: (_dates, _str, fp) => {
      fp.redraw();
      fp._dsUpdateSummary?.();

      // Repositionne avec la hauteur définitive (pied rempli), puis amène le calendrier à l'écran
      positionCalendar(fp);
      revealCalendar(fp);

      // Le calendrier reste attaché à son champ pendant le défilement
      fp._dsFollow = () => positionCalendar(fp);
      window.addEventListener("scroll", fp._dsFollow, { passive: true });
    },
    onClose: (_dates, _str, fp) => {
      window.removeEventListener("scroll", fp._dsFollow);
    },
    onChange: (dates) => {
      const range = rangeOf(input);

      if (range && input.dataset.datepickerStart && range.end) {
        const start = dates[0];

        if (start) {
          // Départ au plus tôt le lendemain de l'arrivée ; une date devenue impossible est effacée
          range.end.set("minDate", addDays(start, 1));

          const end = range.end.selectedDates[0];

          if (end && end <= start) {
            range.end.clear();
          }

          // L'arrivée choisie, on enchaîne sur le départ
          setTimeout(() => {
            if (!range.end.selectedDates[0]) {
              range.end.jumpToDate(start);
              range.end.open();
            }
          }, 120);
        } else {
          range.end.set("minDate", readBound(range.end.element, "Min") ?? "today");
        }
      }

      refreshRange(input);
      pickers.get(input)?._dsUpdateSummary?.();
    },
  });

  pickers.set(input, instance);

  return instance;
}

export function initDatepickers(root = document) {
  root.querySelectorAll("input[data-datepicker]").forEach((input) => {
    if (!pickers.has(input)) {
      createPicker(input);
    }
  });
}
