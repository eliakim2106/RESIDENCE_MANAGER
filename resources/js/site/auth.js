/* =====================================
   CONNEXION ET INSCRIPTION
===================================== */

// Afficher / masquer un mot de passe
function initPasswordToggles() {
  document.querySelectorAll(".auth-toggle-password").forEach((button) => {
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.target);
      const icon = button.querySelector("i");

      if (!input) {
        return;
      }

      const hidden = input.type === "password";

      input.type = hidden ? "text" : "password";
      button.setAttribute("aria-label", hidden ? "Masquer le mot de passe" : "Afficher le mot de passe");
      icon?.classList.toggle("fa-eye", !hidden);
      icon?.classList.toggle("fa-eye-slash", hidden);
    });
  });
}

// Force du mot de passe : longueur, lettres, chiffres, majuscules et caractères spéciaux
function initPasswordStrength() {
  const input = document.querySelector("[data-password-strength]");
  const meter = document.querySelector("[data-strength-meter]");

  if (!input || !meter) {
    return;
  }

  const label = meter.querySelector(".auth-strength-label");
  const hint = label?.textContent ?? "";
  const levels = ["", "Trop faible", "Moyen", "Bon", "Excellent"];

  const evaluate = (value) => {
    if (value.length < 8 || !/[a-z]/i.test(value) || !/\d/.test(value)) {
      return value === "" ? 0 : 1;
    }

    let score = 2;
    score += /[A-Z]/.test(value) && /[a-z]/.test(value) ? 1 : 0;
    score += /[^A-Za-z0-9]/.test(value) || value.length >= 12 ? 1 : 0;

    return Math.min(score, 4);
  };

  input.addEventListener("input", () => {
    const level = evaluate(input.value);
    meter.dataset.level = String(level);

    if (label) {
      label.textContent = level === 0 ? hint : `Sécurité : ${levels[level]}`;
    }
  });
}

// Correspondance des mots de passe
function initPasswordMatch() {
  const password = document.getElementById("password");
  const confirmPassword = document.getElementById("confirmPassword");
  const passwordMatch = document.getElementById("passwordMatch");

  if (!password || !confirmPassword || !passwordMatch) {
    return;
  }

  const check = () => {
    if (confirmPassword.value === "") {
      passwordMatch.textContent = "";
      return;
    }

    const same = password.value === confirmPassword.value;

    passwordMatch.textContent = same ? "Les mots de passe correspondent." : "Les mots de passe ne correspondent pas.";
    passwordMatch.className = "auth-match " + (same ? "is-ok" : "is-ko");
  };

  password.addEventListener("input", check);
  confirmPassword.addEventListener("input", check);
}

export function initAuthForms() {
  initPasswordToggles();
  initPasswordStrength();
  initPasswordMatch();
}
