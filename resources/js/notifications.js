/* =====================================
   NOTIFICATIONS (menu cloche de l'administration)

   Le badge et la liste se mettent à jour sans recharger la page :
   - toutes les 30 secondes tant que l'onglet est visible, et dès qu'on y revient ;
   - à l'ouverture du menu.
   « Tout marquer comme lu » agit sans quitter la page. La cloche sonne quand une notification arrive.
===================================== */

const POLL_MS = 30_000;

export function initNotifications() {
  const root = document.querySelector("[data-notifications]");

  if (!root) {
    return;
  }

  const toggle = root.querySelector("[data-notifications-toggle]");
  const badge = root.querySelector("[data-notifications-badge]");
  const list = root.querySelector("[data-notifications-list]");
  const readAll = root.querySelector("[data-notifications-read-all]");
  const feedUrl = root.dataset.feedUrl;

  let count = Number.parseInt(badge.textContent, 10) || 0;
  let timer = null;
  let loading = false;

  const render = (data) => {
    const arrived = data.count > count;
    count = data.count;

    badge.textContent = count > 9 ? "9+" : String(count);
    badge.hidden = count === 0;
    readAll.hidden = count === 0;
    toggle.setAttribute("aria-label", count > 0 ? `Notifications : ${count} non lue${count > 1 ? "s" : ""}` : "Notifications");
    list.innerHTML = data.html;

    if (arrived) {
      toggle.classList.remove("is-ringing");
      void toggle.offsetWidth; // relance l'animation
      toggle.classList.add("is-ringing");
    }
  };

  const refresh = async () => {
    if (loading) {
      return;
    }

    loading = true;

    try {
      const response = await fetch(feedUrl, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      });

      // Session expirée : inutile de continuer à interroger
      if (response.status === 401 || response.status === 419 || response.redirected) {
        stop();
        return;
      }

      if (response.ok) {
        render(await response.json());
      }
    } catch {
      // Réseau indisponible : nouvel essai au prochain passage
    } finally {
      loading = false;
    }
  };

  const start = () => {
    stop();
    timer = window.setInterval(refresh, POLL_MS);
  };

  function stop() {
    if (timer) {
      window.clearInterval(timer);
      timer = null;
    }
  }

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) {
      stop();
    } else {
      refresh();
      start();
    }
  });

  root.addEventListener("show.bs.dropdown", refresh);
  toggle.addEventListener("animationend", () => toggle.classList.remove("is-ringing"));

  readAll.addEventListener("submit", async (event) => {
    event.preventDefault();

    try {
      const response = await fetch(readAll.action, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": readAll.querySelector('input[name="_token"]').value,
        },
        credentials: "same-origin",
      });

      if (!response.ok) {
        throw new Error(String(response.status));
      }

      await refresh();
    } catch {
      readAll.submit(); // repli : envoi classique du formulaire
    }
  });

  start();
}
