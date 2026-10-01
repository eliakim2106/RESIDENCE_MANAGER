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
   GALERIE : la miniature cliquée s'affiche dans la zone principale
===================================== */

function initGallery() {
  const gallery = document.querySelector("[data-gallery]");
  const main = gallery?.querySelector("[data-gallery-main]");

  if (!gallery || !main) {
    return;
  }

  const thumbs = Array.from(gallery.querySelectorAll("[data-gallery-thumb]"));
  const counter = gallery.querySelector("[data-gallery-counter]");

  if (thumbs.length === 0) {
    return;
  }

  let current = 0;

  const show = (index) => {
    current = (index + thumbs.length) % thumbs.length;
    const thumb = thumbs[current];

    main.classList.add("is-changing");
    main.src = thumb.dataset.src;
    main.alt = thumb.dataset.alt ?? "";
    main.addEventListener("load", () => main.classList.remove("is-changing"), { once: true });
    if (main.complete) {
      main.classList.remove("is-changing");
    }

    thumbs.forEach((item, position) => {
      item.classList.toggle("is-active", position === current);
      item.setAttribute("aria-selected", position === current ? "true" : "false");
    });

    thumb.scrollIntoView({ behavior: reduceMotion() ? "auto" : "smooth", block: "nearest", inline: "center" });

    if (counter) {
      counter.textContent = `${current + 1} / ${thumbs.length}`;
    }
  };

  thumbs.forEach((thumb, index) => thumb.addEventListener("click", () => show(index)));
  gallery.querySelector("[data-gallery-prev]")?.addEventListener("click", () => show(current - 1));
  gallery.querySelector("[data-gallery-next]")?.addEventListener("click", () => show(current + 1));

  // Flèches du clavier quand la galerie a le focus
  gallery.addEventListener("keydown", (event) => {
    if (event.key === "ArrowRight") show(current + 1);
    if (event.key === "ArrowLeft") show(current - 1);
  });

  // Glisser du doigt sur la photo principale
  let startX = null;
  main.parentElement.addEventListener("touchstart", (event) => (startX = event.touches[0].clientX), { passive: true });
  main.parentElement.addEventListener("touchend", (event) => {
    if (startX === null) return;
    const delta = event.changedTouches[0].clientX - startX;
    if (Math.abs(delta) > 40) show(current + (delta < 0 ? 1 : -1));
    startX = null;
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
  initBookingSummary();
  initShare();
  initCollapsible();
  initBookingBar();
}
