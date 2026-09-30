/* =====================================
   DÉTAIL D'UNE RÉSIDENCE : CARTE ET GALERIE
===================================== */

function initMap(L) {
  const container = document.getElementById("residence-map");

  if (!container) {
    return;
  }

  const latitude = parseFloat(container.dataset.latitude ?? "5.348");
  const longitude = parseFloat(container.dataset.longitude ?? "-4.027");
  const title = container.dataset.title ?? "Résidence Premium";
  const subtitle = container.dataset.subtitle ?? "Votre hébergement à Abidjan";

  const map = L.map(container).setView([latitude, longitude], 15);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  const popup = document.createElement("div");
  const strong = document.createElement("strong");
  strong.textContent = title;
  popup.append(strong, document.createElement("br"), subtitle);

  L.marker([latitude, longitude]).addTo(map).bindPopup(popup).openPopup();
}

function initGallery() {
  const mainImage = document.getElementById("mainResidenceImage");
  const prevBtn = document.querySelector(".gallery-nav.prev");
  const nextBtn = document.querySelector(".gallery-nav.next");

  if (!mainImage || !prevBtn || !nextBtn) {
    return;
  }

  const counter = document.querySelector(".gallery-counter");
  const totalPhotos = document.querySelector(".gallery-overlay span");

  const images = [
    mainImage.src,
    ...Array.from(document.querySelectorAll(".gallery-thumb img")).map((img) => img.src),
    document.querySelector(".gallery-last img")?.src,
  ].filter(Boolean);

  let currentIndex = 0;

  const updateImage = () => {
    mainImage.src = images[currentIndex];

    if (counter) {
      counter.textContent = `${currentIndex + 1} / ${images.length}`;
    }
  };

  nextBtn.addEventListener("click", () => {
    currentIndex = (currentIndex + 1) % images.length;
    updateImage();
  });

  prevBtn.addEventListener("click", () => {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    updateImage();
  });

  if (totalPhotos) {
    totalPhotos.textContent = `${images.length} photos`;
  }

  updateImage();
}

/* =====================================
   BARRE DE RÉSERVATION MOBILE
   Le bouton mène à la carte de réservation ; la barre s'efface quand cette carte est à l'écran.
===================================== */

function initBookingBar() {
  const bar = document.getElementById("bookingBar");
  const bookingCard = document.getElementById("reservation");

  if (!bar || !bookingCard) {
    return;
  }

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  bar.querySelector("[data-scroll-to-booking]")?.addEventListener("click", (event) => {
    event.preventDefault();

    bookingCard.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });

    // Mise en évidence puis focus sur la date d'arrivée, une fois le défilement terminé
    setTimeout(() => {
      bookingCard.classList.remove("is-highlighted");
      void bookingCard.offsetWidth;
      bookingCard.classList.add("is-highlighted");

      document.getElementById("booking-check-in")?.focus({ preventScroll: true });
    }, reduceMotion ? 0 : 450);

    history.replaceState(null, "", "#reservation");
  });

  if ("IntersectionObserver" in window) {
    new IntersectionObserver(
      ([entry]) => bar.classList.toggle("is-hidden", entry.isIntersecting),
      { threshold: 0.25 },
    ).observe(bookingCard);
  }
}

export function initResidenceDetails(L) {
  initMap(L);
  initGallery();
  initBookingBar();
}
