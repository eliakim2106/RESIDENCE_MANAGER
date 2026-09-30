/* =====================================
   NAVBAR, RETOUR EN HAUT, PARALLAXE CTA
===================================== */

export function initNavigation() {
  const navbar = document.querySelector(".navbar");
  const backToTop = document.getElementById("backToTop");
  const cta = document.querySelector(".cta-section");

  const onScroll = () => {
    navbar?.classList.toggle("scrolled", window.scrollY > 50);
    backToTop?.classList.toggle("show", window.scrollY > 400);

    if (cta) {
      cta.style.backgroundPositionY = window.scrollY * 0.4 + "px";
    }
  };

  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  backToTop?.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  window.addEventListener("load", () => {
    const loader = document.getElementById("page-loader");

    if (!loader) {
      return;
    }

    loader.style.opacity = "0";
    setTimeout(() => (loader.style.display = "none"), 800);
  });
}
