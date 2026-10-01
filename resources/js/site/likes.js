/* =====================================
   J'AIME : cœur des établissements (fiche, cartes de la liste et de l'accueil)
   Envoi sans recharger la page ; sans JavaScript, le formulaire reste fonctionnel.
   Tous les cœurs d'un même établissement présents sur la page sont mis à jour ensemble.
===================================== */

const formatCount = (count) => count.toLocaleString("fr-FR").replace(/ | /g, " ");

function render(id, liked, count, animate = false) {
  document.querySelectorAll(`[data-like-form][data-like-id="${id}"]`).forEach((form) => {
    const button = form.querySelector("[data-like-button]");
    const icon = button?.querySelector("i");
    const counter = form.querySelector("[data-like-count]");

    button?.setAttribute("aria-pressed", liked ? "true" : "false");
    button?.classList.toggle("is-active", liked);
    icon?.classList.toggle("fa-solid", liked);
    icon?.classList.toggle("fa-regular", !liked);

    if (counter) {
      counter.textContent = formatCount(count);
      counter.hidden = count <= 0;
    }

    if (animate && liked && button) {
      button.classList.remove("is-popping");
      void button.offsetWidth; // relance l'animation
      button.classList.add("is-popping");
    }
  });
}

export function initLikes() {
  document.addEventListener("submit", async (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.matches("[data-like-form]")) {
      return;
    }

    event.preventDefault();

    if (form.dataset.busy) {
      return;
    }

    const id = form.dataset.likeId;
    const button = form.querySelector("[data-like-button]");
    const wasLiked = button?.getAttribute("aria-pressed") === "true";
    const previousCount = parseInt((form.querySelector("[data-like-count]")?.textContent ?? "0").replace(/\D/g, ""), 10) || 0;

    // Affichage immédiat, confirmé (ou annulé) par la réponse
    render(id, !wasLiked, Math.max(0, previousCount + (wasLiked ? -1 : 1)), true);
    form.dataset.busy = "1";

    try {
      const response = await fetch(form.action, {
        method: "POST",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: new FormData(form),
        credentials: "same-origin",
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();
      render(id, Boolean(data.liked), Number(data.count) || 0);
    } catch {
      render(id, wasLiked, previousCount);
    } finally {
      delete form.dataset.busy;
    }
  });
}
