import * as bootstrap from "bootstrap";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

import { initAlerts } from "./alerts";
import { initDatepickers } from "./datepicker";
import { initPhoneFields } from "./phone-field";
import { initNavigation } from "./site/navigation";
import { initResidenceDetails } from "./site/residence-details";
import { initAuthForms } from "./site/auth";
import { initHome } from "./site/home";
import { initListing } from "./site/listing";
import { initFaq } from "./site/faq";

window.bootstrap = bootstrap;

// Le JavaScript est disponible : les animations d’apparition peuvent masquer les éléments avant leur entrée
document.documentElement.classList.remove("no-js");

// Les images du marqueur Leaflet sont servies par Vite. La détection automatique du chemin
// ajouterait un préfixe à ces adresses déjà complètes (marqueur introuvable avec npm run dev) : on la désactive.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow,
});

initAlerts();
initDatepickers();
initPhoneFields();
initNavigation();
initResidenceDetails(L);
initAuthForms();
initHome();
initListing();
initFaq();
