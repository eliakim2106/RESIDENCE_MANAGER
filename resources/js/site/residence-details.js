/* =====================================
   FICHE D'UNE RÉSIDENCE : CARTE, GALERIE, RÉSERVATION
===================================== */

const reduceMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const money = (amount) => `${Math.round(amount).toLocaleString("fr-FR").replace(/ | /g, " ")} FCFA`;

function initMap(L) {
  const container = document.getElementById("residence-map");

  if (!container) {
    return;
  }

  const latitude = parseFloat(container.dataset.latitude);
  const longitude = parseFloat(container.dataset.longitude);

  if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
    return;
  }

  const map = L.map(container, { scrollWheelZoom: false }).setView([latitude, longitude], 15);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  const popup = document.createElement("div");
  const strong = document.createElement("strong");
  strong.textContent = container.dataset.title ?? "";
  popup.append(strong, document.createElement("br"), container.dataset.subtitle ?? "");

  L.marker([latitude, longitude]).addTo(map).bindPopup(popup);
}

/* =====================================
   GALERIE : mosaïque (carrousel sur mobile) et visionneuse plein écran
===================================== */

function initGallery() {
  const gallery = document.querySelector("[data-gallery]");
  const lightbox = gallery?.querySelector("[data-lightbox]");

  if (!gallery || !lightbox) {
    return;
  }

  const tiles = Array.from(gallery.querySelectorAll(".rd-gallery-tile"));
  const track = gallery.querySelector("[data-gallery-track]");
  const counter = gallery.querySelector("[data-gallery-counter]");
  const image = lightbox.querySelector("[data-lightbox-image]");
  const caption = lightbox.querySelector("[data-lightbox-caption]");
  const lightboxCounter = lightbox.querySelector("[data-lightbox-counter]");
  const thumbs = Array.from(lightbox.querySelectorAll("[data-lightbox-thumb]"));
  const stage = lightbox.querySelector("[data-lightbox-stage]");
  const total = tiles.length;
  let current = 0;

  // Carrousel mobile : compteur selon la photo visible
  track.addEventListener(
    "scroll",
    () => {
      if (!counter || track.clientWidth === 0) return;
      const index = Math.round(track.scrollLeft / track.clientWidth);
      counter.textContent = `${Math.min(index, total - 1) + 1} / ${total}`;
    },
    { passive: true },
  );

  const preload = (index) => {
    const tile = tiles[(index + total) % total];
    if (tile) new Image().src = tile.dataset.src;
  };

  const show = (index) => {
    current = (index + total) % total;
    const tile = tiles[current];

    image.classList.add("is-changing");
    image.src = tile.dataset.src;
    image.alt = tile.querySelector("img")?.alt ?? "";
    if (image.complete) {
      image.classList.remove("is-changing");
    } else {
      image.addEventListener("load", () => image.classList.remove("is-changing"), { once: true });
    }

    caption.textContent = tile.dataset.caption ?? "";
    caption.hidden = !tile.dataset.caption;
    lightboxCounter.textContent = `${current + 1} / ${total}`;

    thumbs.forEach((thumb, position) => {
      const active = position === current;
      thumb.classList.toggle("is-active", active);
      thumb.toggleAttribute("aria-current", active);
    });
    thumbs[current]?.scrollIntoView({ behavior: reduceMotion() ? "auto" : "smooth", block: "nearest", inline: "center" });

    preload(current + 1);
    preload(current - 1);
  };

  const open = (index) => {
    show(index);
    document.documentElement.classList.add("has-lightbox");
    lightbox.showModal();
  };

  gallery.querySelectorAll("[data-gallery-open]").forEach((button) => {
    button.addEventListener("click", () => open(parseInt(button.dataset.galleryOpen, 10) || 0));
  });

  thumbs.forEach((thumb, index) => thumb.addEventListener("click", () => show(index)));
  lightbox.querySelector("[data-lightbox-prev]")?.addEventListener("click", () => show(current - 1));
  lightbox.querySelector("[data-lightbox-next]")?.addEventListener("click", () => show(current + 1));
  lightbox.querySelector("[data-lightbox-close]").addEventListener("click", () => lightbox.close());

  // Fermeture (bouton, Échap) : défilement de la page rétabli, carrousel calé sur la dernière photo vue
  lightbox.addEventListener("close", () => {
    document.documentElement.classList.remove("has-lightbox");
    if (track.scrollWidth > track.clientWidth) {
      track.scrollTo({ left: current * track.clientWidth, behavior: "auto" });
    }
  });

  // Clic dans le vide autour de la photo : fermeture
  stage.addEventListener("click", (event) => {
    if (event.target === stage) lightbox.close();
  });

  lightbox.addEventListener("keydown", (event) => {
    if (event.key === "ArrowRight") show(current + 1);
    if (event.key === "ArrowLeft") show(current - 1);
  });

  // Glisser du doigt dans la visionneuse
  let startX = null;
  stage.addEventListener("touchstart", (event) => (startX = event.touches[0].clientX), { passive: true });
  stage.addEventListener("touchend", (event) => {
    if (startX === null || total < 2) return;
    const delta = event.changedTouches[0].clientX - startX;
    if (Math.abs(delta) > 40) show(current + (delta < 0 ? 1 : -1));
    startX = null;
  });
}

/* =====================================
   NOMBRE DE LOGEMENTS : boutons − / +, saisie bornée au disponible
===================================== */

function initSteppers() {
  document.querySelectorAll("[data-stepper]").forEach((stepper) => {
    const input = stepper.querySelector("input");
    const minus = stepper.querySelector('[data-step="-1"]');
    const plus = stepper.querySelector('[data-step="1"]');
    const max = parseInt(input.max, 10) || 0;

    const clamp = (value) => Math.min(max, Math.max(0, Number.isNaN(value) ? 0 : value));

    const refresh = () => {
      const value = parseInt(input.value, 10) || 0;
      minus.disabled = value <= 0;
      plus.disabled = value >= max;
      stepper.classList.toggle("has-value", value > 0);
    };

    const set = (value) => {
      const next = clamp(value);
      if (String(next) === input.value) return;
      input.value = next;
      input.dispatchEvent(new Event("change", { bubbles: true }));
    };

    stepper.querySelectorAll("[data-step]").forEach((button) => {
      button.addEventListener("click", () => set((parseInt(input.value, 10) || 0) + parseInt(button.dataset.step, 10)));
    });

    // Saisie au clavier : bornée à la sortie du champ, total mis à jour à chaque frappe valide
    input.addEventListener("input", () => {
      if (input.value !== "") input.dispatchEvent(new Event("change", { bubbles: true }));
    });
    input.addEventListener("blur", () => set(parseInt(input.value, 10)));
    input.addEventListener("change", () => {
      const value = parseInt(input.value, 10);
      if (input.value !== "" && value !== clamp(value)) input.value = clamp(value);
      refresh();
    });

    refresh();
  });
}

/* =====================================
   RÉCAPITULATIF : total mis à jour selon les logements choisis
===================================== */

function initBookingSummary() {
  const form = document.querySelector("[data-booking-summary]");

  if (!form) {
    return;
  }

  const selects = Array.from(document.querySelectorAll("[data-unit-select]"));
  const empty = form.querySelector("[data-summary-empty]");
  const linesBox = form.querySelector("[data-summary-lines]");
  const totalsBox = form.querySelector("[data-summary-totals]");
  const submit = form.querySelector("[data-booking-submit]");
  const warning = form.querySelector("[data-capacity-warning]");
  const serviceRate = parseFloat(form.dataset.serviceRate || "0");
  const guests = parseInt(form.dataset.guests || "1", 10);

  const set = (selector, value) => {
    const element = form.querySelector(selector);
    if (element) element.textContent = value;
  };

  const update = () => {
    let subtotal = 0;
    let cleaning = 0;
    let capacity = 0;
    linesBox.replaceChildren();

    selects.forEach((select) => {
      const quantity = parseInt(select.value, 10) || 0;
      select.closest(".rd-unit")?.classList.toggle("is-selected", quantity > 0);

      if (quantity === 0) return;

      const lineTotal = quantity * parseInt(select.dataset.subtotal, 10);
      subtotal += lineTotal;
      cleaning += quantity * parseInt(select.dataset.cleaning || "0", 10);
      capacity += quantity * parseInt(select.dataset.capacity || "0", 10);

      const row = document.createElement("div");
      const label = document.createElement("span");
      const value = document.createElement("b");
      label.textContent = `${quantity} × ${select.dataset.name}`;
      value.textContent = money(lineTotal);
      row.append(label, value);
      linesBox.append(row);
    });

    const service = Math.round((subtotal * serviceRate) / 100);
    const hasSelection = subtotal > 0;

    empty.hidden = hasSelection;
    linesBox.hidden = !hasSelection;
    totalsBox.hidden = !hasSelection;
    submit.disabled = !hasSelection;

    set("[data-total-subtotal]", money(subtotal));
    set("[data-total-cleaning]", money(cleaning));
    set("[data-total-service]", money(service));
    set("[data-total]", money(subtotal + cleaning + service));
    form.querySelector("[data-total-cleaning-row]")?.toggleAttribute("hidden", cleaning === 0);

    const tooSmall = hasSelection && capacity < guests;
    warning.hidden = !tooSmall;
    if (tooSmall) {
      warning.querySelector("span").textContent = `Ces logements accueillent ${capacity} voyageur${capacity > 1 ? "s" : ""} pour ${guests} indiqués : ajoutez un logement.`;
    }
  };

  selects.forEach((select) => select.addEventListener("change", update));
  update();
}

/* =====================================
   PARTAGE, TEXTE REPLIABLE, ACCÈS AUX DATES
===================================== */

function initShare() {
  document.querySelectorAll("[data-share]").forEach((button) => {
    button.addEventListener("click", async () => {
      const data = { title: button.dataset.shareTitle, text: button.dataset.shareText, url: window.location.href };

      try {
        if (navigator.share) {
          await navigator.share(data);
          return;
        }

        await navigator.clipboard.writeText(data.url);
        const label = button.querySelector("span");
        const previous = label.textContent;
        label.textContent = "Lien copié";
        setTimeout(() => (label.textContent = previous), 2000);
      } catch {
        // Partage annulé : rien à faire
      }
    });
  });
}

function initCollapsible() {
  document.querySelectorAll(".rd-description[data-collapsible]").forEach((box) => {
    const text = box.querySelector(".rd-description-text");
    const toggle = box.querySelector("[data-collapsible-toggle]");

    if (!text || !toggle || text.scrollHeight <= 220) {
      return;
    }

    box.classList.add("is-collapsed");
    toggle.hidden = false;
    toggle.addEventListener("click", () => {
      const collapsed = box.classList.toggle("is-collapsed");
      toggle.firstChild.textContent = collapsed ? "Lire la suite " : "Réduire ";
      toggle.querySelector("i").className = `fa-solid fa-chevron-${collapsed ? "down" : "up"}`;
    });
  });
}

function focusBooking(event) {
  const bookingCard = document.getElementById("reservation");

  if (!bookingCard) {
    return;
  }

  event?.preventDefault();
  bookingCard.scrollIntoView({ behavior: reduceMotion() ? "auto" : "smooth", block: "start" });

  // Mise en évidence puis focus sur la date d'arrivée, une fois le défilement terminé
  setTimeout(() => {
    bookingCard.classList.remove("is-highlighted");
    void bookingCard.offsetWidth;
    bookingCard.classList.add("is-highlighted");

    const checkIn = document.getElementById("booking-check-in");
    // Le sélecteur de dates remplace parfois le champ natif par un champ visible voisin
    const visible = checkIn?.offsetParent ? checkIn : checkIn?.parentElement.querySelector("input:not([type=hidden])");
    visible?.focus({ preventScroll: true });
  }, reduceMotion() ? 0 : 450);

  history.replaceState(null, "", "#reservation");
}

/* =====================================
   BARRE DE RÉSERVATION MOBILE
   Le bouton mène à la carte de réservation ; la barre s'efface quand cette carte est à l'écran.
===================================== */

function initBookingBar() {
  const bar = document.getElementById("bookingBar");
  const bookingCard = document.getElementById("reservation");

  document.querySelectorAll("[data-focus-dates]").forEach((link) => link.addEventListener("click", focusBooking));

  if (!bar || !bookingCard) {
    return;
  }

  bar.querySelector("[data-scroll-to-booking]")?.addEventListener("click", focusBooking);

  if ("IntersectionObserver" in window) {
    new IntersectionObserver(([entry]) => bar.classList.toggle("is-hidden", entry.isIntersecting), { threshold: 0.25 }).observe(bookingCard);
  }
}

export function initResidenceDetails(L) {
  initMap(L);
  initGallery();
  initSteppers();
  initBookingSummary();
  initShare();
  initCollapsible();
  initBookingBar();
}
