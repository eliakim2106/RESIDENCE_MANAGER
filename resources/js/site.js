import * as bootstrap from "bootstrap";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

import { initAlerts } from "./alerts";
import { initDatepickers } from "./datepicker";
import { initNavigation } from "./site/navigation";
import { initResidenceDetails } from "./site/residence-details";
import { initAuthForms } from "./site/auth";
import { initHome } from "./site/home";
import { initListing } from "./site/listing";

window.bootstrap = bootstrap;

// Le JavaScript est disponible : les animations d’apparition peuvent masquer les éléments avant leur entrée
document.documentElement.classList.remove("no-js");

// Les images du marqueur Leaflet sont servies par Vite
L.Icon.Default.mergeOptions({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow,
});

initAlerts();
initDatepickers();
initNavigation();
initResidenceDetails(L);
initAuthForms();
initHome();
initListing();
