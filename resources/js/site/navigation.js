/* =====================================
   EN-TÊTE, MENU MOBILE, LIEN ACTIF, RETOUR EN HAUT
===================================== */

const DESKTOP_QUERY = window.matchMedia("(min-width: 992px)");

function initHeader() {
  const header = document.getElementById("siteHeader");
  const backToTop = document.getElementById("backToTop");

  const onScroll = () => {
    header?.classList.toggle("is-scrolled", window.scrollY > 40);
    backToTop?.classList.toggle("show", window.scrollY > 400);
  };

  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  backToTop?.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}

function initMobileMenu() {
  const toggle = document.querySelector(".site-nav-toggle");
  const nav = document.getElementById("siteNav");

  if (!toggle || !nav) {
    return;
  }

  const setOpen = (open) => {
    document.body.classList.toggle("site-nav-open", open);
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Fermer le menu" : "Ouvrir le menu");
  };

  toggle.addEventListener("click", () => setOpen(!document.body.classList.contains("site-nav-open")));

  // Un lien choisi (y compris une ancre de la page) referme le menu
  nav.querySelectorAll("a").forEach((link) => link.addEventListener("click", () => setOpen(false)));

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && document.body.classList.contains("site-nav-open")) {
      setOpen(false);
      toggle.focus();
    }
  });

  DESKTOP_QUERY.addEventListener("change", () => setOpen(false));
}

// Sur l'accueil, le lien de la section visible devient actif (Accueil, Services, Avis, Contact)
function initScrollSpy() {
  const links = [...document.querySelectorAll(".site-nav-link[data-nav-section]")];
  const sections = links
    .map((link) => document.getElementById(link.dataset.navSection))
    .filter(Boolean);

  if (sections.length === 0 || !("IntersectionObserver" in window)) {
    return;
  }

  const setActive = (id) => {
    links.forEach((link) => {
      const active = link.dataset.navSection === id;
      link.classList.toggle("is-active", active);
      link.toggleAttribute("aria-current", active);
    });
  };

  const visible = new Map();

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => visible.set(entry.target.id, entry.isIntersecting));

      // La dernière section visible dans l'ordre du document l'emporte
      const current = sections.filter((section) => visible.get(section.id)).pop();

      if (current) {
        setActive(current.id);
      }
    },
    { rootMargin: "-45% 0px -50% 0px" },
  );

  sections.forEach((section) => observer.observe(section));
}

export function initNavigation() {
  initHeader();
  initMobileMenu();
  initScrollSpy();

  window.addEventListener("load", () => {
    const loader = document.getElementById("page-loader");

    if (!loader) {
      return;
    }

    loader.style.opacity = "0";
    setTimeout(() => (loader.style.display = "none"), 800);
  });
}
