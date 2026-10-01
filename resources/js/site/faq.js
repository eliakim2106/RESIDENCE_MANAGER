/* =====================================
   QUESTIONS FRÉQUENTES : recherche instantanée
   Les questions qui contiennent les mots tapés restent affichées (et ouvertes) ; les thèmes vides sont masqués.
===================================== */

const normalize = (text) => text.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");

export function initFaq() {
  const root = document.querySelector("[data-faq]");
  const input = root?.querySelector("[data-faq-search]");

  if (!root || !input) {
    return;
  }

  const items = Array.from(root.querySelectorAll("[data-faq-item]"));
  const groups = Array.from(root.querySelectorAll("[data-faq-group]"));
  const empty = root.querySelector("[data-faq-empty]");

  input.addEventListener("input", () => {
    const words = normalize(input.value.trim()).split(/\s+/).filter(Boolean);

    items.forEach((item) => {
      const text = normalize(item.textContent);
      const match = words.every((word) => text.includes(word));
      item.hidden = !match;
      item.open = words.length > 0 && match;
    });

    groups.forEach((group) => {
      group.hidden = group.querySelectorAll("[data-faq-item]:not([hidden])").length === 0;
    });

    if (empty) {
      empty.hidden = items.some((item) => !item.hidden);
    }
  });
}
