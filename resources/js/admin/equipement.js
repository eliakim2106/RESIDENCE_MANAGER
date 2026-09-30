/* =====================================
   ÉQUIPEMENTS : SUGGESTION D'ICÔNE
   À la sortie du champ « Nom », une icône est proposée d'après les mots-clés ;
   elle reste modifiable à la main.
===================================== */

const iconSuggestions = {
  // INTERNET & TECHNOLOGIE
  wifi: "fa-wifi",
  internet: "fa-wifi",
  reseau: "fa-wifi",
  ordinateur: "fa-computer",
  laptop: "fa-laptop",
  imprimante: "fa-print",
  television: "fa-tv",
  tv: "fa-tv",
  smarttv: "fa-tv",

  // CONFORT
  climatisation: "fa-snowflake",
  climatiseur: "fa-snowflake",
  ventilation: "fa-fan",
  ventilateur: "fa-fan",
  chauffage: "fa-temperature-high",
  eau: "fa-faucet",
  eauchaude: "fa-faucet-drip",

  // CUISINE
  cuisine: "fa-kitchen-set",
  cuisiniere: "fa-fire-burner",
  four: "fa-fire-burner",
  microonde: "fa-microwave",
  refrigerateur: "fa-refrigerator",
  frigo: "fa-refrigerator",
  cafetiere: "fa-mug-hot",
  cafe: "fa-mug-hot",
  restaurant: "fa-utensils",
  repas: "fa-utensils",
  bar: "fa-martini-glass",
  petitdejeuner: "fa-mug-saucer",

  // CHAMBRE
  lit: "fa-bed",
  chambre: "fa-bed",
  suite: "fa-bed",
  dressing: "fa-shirt",
  armoire: "fa-shirt",

  // SALLE DE BAIN
  douche: "fa-shower",
  baignoire: "fa-bath",
  toilette: "fa-toilet",
  wc: "fa-toilet",
  savon: "fa-pump-soap",

  // EXTÉRIEUR
  jardin: "fa-tree",
  parc: "fa-tree",
  terrasse: "fa-umbrella-beach",
  balcon: "fa-building",
  plage: "fa-umbrella-beach",
  mer: "fa-water",
  ocean: "fa-water",
  vue: "fa-eye",

  // LOISIRS
  piscine: "fa-person-swimming",
  natation: "fa-person-swimming",
  sport: "fa-dumbbell",
  fitness: "fa-dumbbell",
  gym: "fa-dumbbell",
  billard: "fa-gamepad",
  jeux: "fa-gamepad",
  cinema: "fa-film",
  musique: "fa-music",

  // TRANSPORT
  parking: "fa-square-parking",
  garage: "fa-square-parking",
  voiture: "fa-car",
  taxi: "fa-taxi",
  navette: "fa-van-shuttle",
  bus: "fa-bus",
  moto: "fa-motorcycle",
  velo: "fa-bicycle",

  // SÉCURITÉ
  securite: "fa-shield-halved",
  surveillance: "fa-video",
  camera: "fa-video",
  coffre: "fa-vault",
  alarme: "fa-bell",
  gardien: "fa-user-shield",

  // SERVICES
  reception: "fa-bell-concierge",
  accueil: "fa-bell-concierge",
  concierge: "fa-bell-concierge",
  laverie: "fa-soap",
  blanchisserie: "fa-soap",
  nettoyage: "fa-broom",
  menage: "fa-broom",

  // ACCESSIBILITÉ
  ascenseur: "fa-elevator",
  handicap: "fa-wheelchair",
  acces: "fa-wheelchair",

  // ÉNERGIE
  electricite: "fa-bolt",
  groupeelectrogene: "fa-bolt",
  solaire: "fa-solar-panel",
  energie: "fa-bolt",

  // ANIMAUX
  animaux: "fa-paw",
  chien: "fa-dog",
  chat: "fa-cat",

  // SANTÉ
  pharmacie: "fa-kit-medical",
  secours: "fa-kit-medical",
  medecin: "fa-user-doctor",

  // COMMUNICATION
  telephone: "fa-phone",
  mobile: "fa-mobile-screen",
  email: "fa-envelope",

  // ÉTABLISSEMENTS
  hotel: "fa-hotel",
  residence: "fa-building",
  appartement: "fa-building-user",
  villa: "fa-house",
  auberge: "fa-bed",
  guesthouse: "fa-house-chimney",

  // DIVERS
  boutique: "fa-store",
  magasin: "fa-store",
  banque: "fa-building-columns",
  bureau: "fa-briefcase",
  salle: "fa-door-open",
  conference: "fa-people-group",
  reunion: "fa-people-group",
};

const equipmentName = document.getElementById("equipement-name");
const equipmentIcon = document.getElementById("equipement-icon");
const equipmentPreview = document.getElementById("equipment-icon-preview");

const showIcon = (icon) => {
  equipmentPreview.className = `fa-solid ${/^fa-[a-z0-9-]+$/.test(icon) ? icon : "fa-question"}`;
};

if (equipmentName && equipmentIcon && equipmentPreview) {
  equipmentName.addEventListener("blur", () => {
    const value = equipmentName.value
      .toLowerCase()
      .normalize("NFD")
      .replace(/\p{Diacritic}/gu, "")
      .replace(/\s+/g, "");

    const keyword = Object.keys(iconSuggestions).find((key) => value.includes(key));

    if (keyword) {
      equipmentIcon.value = iconSuggestions[keyword];
    }

    showIcon(equipmentIcon.value.trim());
  });

  equipmentIcon.addEventListener("input", () => showIcon(equipmentIcon.value.trim()));

  showIcon(equipmentIcon.value.trim());
}
