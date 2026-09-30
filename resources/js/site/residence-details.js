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

export function initResidenceDetails(L) {
  initMap(L);
  initGallery();
}
