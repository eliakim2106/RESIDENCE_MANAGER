import * as bootstrap from "bootstrap";
import L from "leaflet";
import markerIcon from "leaflet/dist/images/marker-icon.png";
import markerIcon2x from "leaflet/dist/images/marker-icon-2x.png";
import markerShadow from "leaflet/dist/images/marker-shadow.png";

import { initAlerts } from "./alerts";
import { initNavigation } from "./site/navigation";
import { initResidenceDetails } from "./site/residence-details";
import { initAuthForms } from "./site/auth";

window.bootstrap = bootstrap;

// Les images du marqueur Leaflet sont servies par Vite
L.Icon.Default.mergeOptions({
  iconUrl: markerIcon,
  iconRetinaUrl: markerIcon2x,
  shadowUrl: markerShadow,
});

initAlerts();
initNavigation();
initResidenceDetails(L);
initAuthForms();
