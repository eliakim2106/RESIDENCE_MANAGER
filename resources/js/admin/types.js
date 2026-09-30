/* =====================================
   TYPES D'ÉTABLISSEMENT ET TYPES D'UNITÉ
   Le choix d'un type remplit son icône et sa description.
===================================== */

const presets = {
  etablissement: {
    Hôtel: {
      icon: "fa-hotel",
      description: "Établissement hôtelier proposant des chambres et services d'hébergement.",
    },
    "Résidence meublée": {
      icon: "fa-building",
      description: "Résidence équipée destinée aux séjours de courte ou longue durée.",
    },
    Villa: {
      icon: "fa-house",
      description: "Habitation individuelle haut standing avec équipements privatifs.",
    },
    Appartement: {
      icon: "fa-building-user",
      description: "Logement indépendant situé dans un immeuble résidentiel.",
    },
    Auberge: {
      icon: "fa-bed",
      description: "Structure d'hébergement économique destinée aux voyageurs.",
    },
    "Guest House": {
      icon: "fa-house-chimney",
      description: "Maison d'hôtes offrant un hébergement convivial.",
    },
  },

  unite: {
    "Chambre Standard": {
      icon: "fa-bed",
      description: "Chambre confortable équipée des commodités essentielles pour un séjour agréable.",
    },
    "Chambre Deluxe": {
      icon: "fa-gem",
      description:
        "Chambre spacieuse offrant un niveau de confort supérieur avec des équipements haut de gamme.",
    },
    "Suite Junior": {
      icon: "fa-door-open",
      description:
        "Suite élégante comprenant un espace nuit et un coin salon pour un confort optimal.",
    },
    "Suite Présidentielle": {
      icon: "fa-crown",
      description:
        "Suite de prestige offrant des prestations luxueuses, de grands espaces et des services exclusifs.",
    },
    "Appartement entier": {
      icon: "fa-building",
      description:
        "Appartement entièrement privatif comprenant plusieurs espaces de vie pour un séjour en toute autonomie.",
    },
    "Villa entière": {
      icon: "fa-house-chimney",
      description:
        "Villa privative offrant de vastes espaces, des équipements complets et un confort haut de gamme.",
    },
    Studio: {
      icon: "fa-house",
      description: "Logement compact et entièrement équipé, idéal pour une ou deux personnes.",
    },
  },
};

const select = document.getElementById("type-name");
const iconPreview = document.getElementById("type-icon-preview");
const iconInput = document.getElementById("type-icon");
const description = document.getElementById("type-description");

if (select && iconPreview && iconInput && description) {
  const types = presets[select.dataset.presets] ?? {};

  select.addEventListener("change", () => {
    const selected = types[select.value];

    iconPreview.className = `fa-solid ${selected?.icon ?? "fa-question"}`;
    iconInput.value = selected?.icon ?? "";
    description.value = selected?.description ?? "";
  });
}
