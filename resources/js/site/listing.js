/* =====================================
   PAGE « NOS RÉSIDENCES » : FILTRES
   Grand écran : un filtre modifié relance la recherche aussitôt.
   Mobile : les filtres se règlent dans le tiroir puis s'appliquent avec « Voir les résultats ».
===================================== */

const DESKTOP_QUERY = window.matchMedia("(min-width: 992px)");

const formatPrice = (value) => new Intl.NumberFormat("fr-FR").format(value);

// Double curseur de budget, synchronisé avec les champs Min / Max
function initPriceRange(form, submit) {
  const range = form.querySelector("[data-price-range]");

  if (!range) {
    return;
  }

  const floor = Number(range.dataset.min);
  const ceil = Number(range.dataset.max);
  const sliderMin = range.querySelector("[data-range-min]");
  const sliderMax = range.querySelector("[data-range-max]");
  const inputMin = range.querySelector("[data-price-min]");
  const inputMax = range.querySelector("[data-price-max]");

  const paint = () => {
    const span = ceil - floor || 1;
    range.style.setProperty("--range-from", `${((Number(sliderMin.value) - floor) / span) * 100}%`);
    range.style.setProperty("--range-to", `${((Number(sliderMax.value) - floor) / span) * 100}%`);
  };

  // Curseurs → champs (une borne laissée à son extrémité ne filtre pas)
  const fromSliders = (moved) => {
    let min = Number(sliderMin.value);
    let max = Number(sliderMax.value);

    if (min > max) {
      if (moved === sliderMin) {
        min = max;
        sliderMin.value = String(min);
      } else {
        max = min;
        sliderMax.value = String(max);
      }
    }

    inputMin.value = min > floor ? String(min) : "";
    inputMax.value = max < ceil ? String(max) : "";
    paint();
  };

  // Champs → curseurs
  const fromInputs = () => {
    sliderMin.value = String(inputMin.value === "" ? floor : Math.max(floor, Math.min(Number(inputMin.value), ceil)));
    sliderMax.value = String(inputMax.value === "" ? ceil : Math.max(floor, Math.min(Number(inputMax.value), ceil)));
    paint();
  };

  [sliderMin, sliderMax].forEach((slider) => {
    slider.addEventListener("input", () => fromSliders(slider));
    slider.addEventListener("change", () => submit());
    slider.setAttribute("aria-valuetext", `${formatPrice(slider.value)} FCFA`);
    slider.addEventListener("input", () => slider.setAttribute("aria-valuetext", `${formatPrice(slider.value)} FCFA`));
  });

  [inputMin, inputMax].forEach((input) => {
    input.addEventListener("input", fromInputs);
    input.addEventListener("change", () => submit());
  });

  paint();
}

export function initListing() {
  const form = document.querySelector("[data-listing-form]");

  if (!form) {
    return;
  }

  // Envoi automatique : toujours pour le tri, seulement sur grand écran pour les filtres
  const submit = (always = false) => {
    if (always || DESKTOP_QUERY.matches) {
      form.requestSubmit();
    }
  };

  form.querySelectorAll("[data-auto-submit]").forEach((field) => {
    field.addEventListener("change", () => submit(field.hasAttribute("data-auto-submit-always")));
  });

  initPriceRange(form, () => submit());

  // Adresse propre : les champs vides ne sont pas envoyés
  form.addEventListener("submit", () => {
    form.querySelectorAll("input[name], select[name]").forEach((field) => {
      // Une case cochée a toujours une valeur ; le bouton radio « Toutes » envoie une valeur vide
      const empty = field.value === "" && field.type !== "checkbox";
      const defaultSort = field.name === "tri" && field.value === "recommandees";

      if (empty || defaultSort) {
        field.disabled = true;
      }
    });

    form.classList.add("is-loading");
  });

  // Retour arrière depuis le cache du navigateur : réactive les champs désactivés à l'envoi
  window.addEventListener("pageshow", () => {
    form.classList.remove("is-loading");
    form.querySelectorAll(":disabled").forEach((field) => (field.disabled = false));
  });
}
