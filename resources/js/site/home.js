/* =====================================
   PAGE D'ACCUEIL : DIAPORAMA, APPARITIONS, COMPTEURS, FAVORIS
===================================== */

const REDUCED_MOTION = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
const SLIDE_DURATION = 7000;

/* -------------------------------------
   Diaporama du hero
   La barre de progression de la pastille active sert de minuterie : à la fin de son animation,
   on passe à la diapositive suivante. Mettre en pause l'animation suspend donc le défilement.
------------------------------------- */

function initHeroSlider() {
  const hero = document.querySelector("[data-hero-slider]");

  if (!hero) {
    return;
  }

  const slides = [...hero.querySelectorAll(".home-hero-slide")];
  const dots = [...hero.querySelectorAll("[data-hero-dot]")];
  const current = hero.querySelector("[data-hero-current]");
  let index = 0;

  hero.style.setProperty("--hero-duration", `${SLIDE_DURATION}ms`);

  if (slides.length < 2) {
    return;
  }

  const goTo = (target) => {
    index = (target + slides.length) % slides.length;

    slides.forEach((slide, i) => {
      const active = i === index;
      slide.classList.toggle("is-active", active);
      slide.toggleAttribute("aria-hidden", !active);
      slide.querySelectorAll("a, button").forEach((el) => (el.tabIndex = active ? 0 : -1));
    });

    dots.forEach((dot, i) => {
      dot.classList.remove("is-active");
      dot.classList.toggle("is-done", i < index);
      dot.setAttribute("aria-selected", String(i === index));
    });

    // Relance l'animation de la barre de progression
    void dots[index]?.offsetWidth;
    dots[index]?.classList.add("is-active");

    if (current) {
      current.textContent = String(index + 1).padStart(2, "0");
    }
  };

  dots.forEach((dot, i) => {
    dot.addEventListener("click", () => goTo(i));
    dot.querySelector(".home-hero-dot-bar")?.addEventListener("animationend", () => {
      if (i === index) {
        goTo(index + 1);
      }
    });
  });

  hero.querySelector("[data-hero-prev]")?.addEventListener("click", () => goTo(index - 1));
  hero.querySelector("[data-hero-next]")?.addEventListener("click", () => goTo(index + 1));

  // Pause au survol et quand un élément du hero a le focus
  const pause = () => hero.classList.add("is-paused");
  const resume = () => {
    if (!REDUCED_MOTION && !hero.matches(":focus-within")) {
      hero.classList.remove("is-paused");
    }
  };

  hero.addEventListener("mouseenter", pause);
  hero.addEventListener("mouseleave", resume);
  hero.addEventListener("focusin", pause);
  hero.addEventListener("focusout", () => setTimeout(resume, 0));

  // Mouvement réduit demandé : pas de défilement automatique
  if (REDUCED_MOTION) {
    pause();
  }

  // Clavier
  hero.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") {
      goTo(index - 1);
    } else if (event.key === "ArrowRight") {
      goTo(index + 1);
    }
  });

  // Glissement au doigt
  let startX = null;

  hero.addEventListener("pointerdown", (event) => {
    if (event.pointerType !== "mouse") {
      startX = event.clientX;
    }
  });

  hero.addEventListener("pointerup", (event) => {
    if (startX === null) {
      return;
    }

    const delta = event.clientX - startX;
    startX = null;

    if (Math.abs(delta) > 50) {
      goTo(index + (delta < 0 ? 1 : -1));
    }
  });

  goTo(0);
}

/* -------------------------------------
   Apparition des éléments au défilement
------------------------------------- */

function initReveal() {
  const elements = document.querySelectorAll("[data-reveal]");

  const reveal = (el) => {
    el.classList.add("is-visible");

    // Une fois apparu, l'élément retrouve ses transitions de survol
    el.addEventListener(
      "transitionend",
      (event) => {
        if (event.propertyName === "opacity") {
          el.classList.add("is-revealed");
        }
      },
      { once: false },
    );
  };

  if (REDUCED_MOTION || !("IntersectionObserver" in window)) {
    elements.forEach((el) => el.classList.add("is-visible", "is-revealed"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          reveal(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -8% 0px" },
  );

  elements.forEach((el) => observer.observe(el));
}

/* -------------------------------------
   Compteurs animés
------------------------------------- */

function initCounters() {
  const counters = document.querySelectorAll("[data-count]");

  if (counters.length === 0 || REDUCED_MOTION || !("IntersectionObserver" in window)) {
    return;
  }

  const animate = (el) => {
    const target = parseFloat(el.dataset.count);
    const decimals = parseInt(el.dataset.decimals ?? "0", 10);
    const format = new Intl.NumberFormat("fr-FR", { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    const duration = 1800;
    const start = performance.now();

    const step = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = format.format(target * eased);

      if (progress < 1) {
        requestAnimationFrame(step);
      }
    };

    requestAnimationFrame(step);
  };

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animate(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.6 },
  );

  counters.forEach((el) => observer.observe(el));
}

/* -------------------------------------
   Favoris (affichage uniquement)
------------------------------------- */

function initFavorites() {
  document.querySelectorAll("[data-favorite]").forEach((button) => {
    button.addEventListener("click", () => {
      const active = button.getAttribute("aria-pressed") !== "true";
      const icon = button.querySelector("i");

      button.setAttribute("aria-pressed", String(active));
      icon?.classList.toggle("fa-solid", active);
      icon?.classList.toggle("fa-regular", !active);
    });
  });
}

export function initHome() {
  initHeroSlider();
  initReveal();
  initCounters();
  initFavorites();
}
