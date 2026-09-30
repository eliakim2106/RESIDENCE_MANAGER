/* =====================================
   CONNEXION ET INSCRIPTION
===================================== */

// Format du numéro de téléphone selon l'indicatif
function initPhoneFormat() {
  const countrySelect = document.getElementById("countryCode");
  const phoneInput = document.getElementById("phoneNumber");
  const phoneHelp = document.getElementById("phoneHelp");

  if (!countrySelect || !phoneInput) {
    return;
  }

  const formats = {
    "+225": [2, 2, 2, 2, 2], // Côte d’Ivoire
    "+221": [2, 3, 2, 2], // Sénégal
    "+223": [2, 2, 2, 2], // Mali
    "+226": [2, 2, 2, 2], // Burkina Faso
    "+228": [2, 2, 2, 2], // Togo
    "+229": [2, 2, 2, 2], // Bénin
    "+233": [2, 3, 4], // Ghana
    "+224": [3, 3, 3], // Guinée
    "+33": [1, 2, 2, 2, 2], // France
    "+32": [3, 2, 2, 2], // Belgique
    "+1": [3, 3, 4], // Canada
  };

  const formatPhoneNumber = (value, countryCode) => {
    const groups = formats[countryCode];

    if (!groups) {
      return value;
    }

    const parts = [];
    let position = 0;

    groups.forEach((size) => {
      const part = value.substring(position, position + size);

      if (part) {
        parts.push(part);
      }

      position += size;
    });

    return parts.join(" ");
  };

  const formatInput = () => {
    const maxLength = parseInt(phoneInput.dataset.maxLength, 10);
    const digits = phoneInput.value.replace(/\D/g, "").substring(0, maxLength);

    phoneInput.value = formatPhoneNumber(digits, countrySelect.value);
  };

  const updatePhoneRules = (reset) => {
    const option = countrySelect.options[countrySelect.selectedIndex];

    phoneInput.dataset.maxLength = option.dataset.length;
    phoneInput.placeholder = option.dataset.placeholder;

    if (phoneHelp) {
      phoneHelp.textContent = "Format attendu : " + option.dataset.placeholder;
    }

    if (reset) {
      phoneInput.value = "";
    } else {
      formatInput();
    }
  };

  // Au chargement, on conserve la saisie renvoyée après une erreur de validation
  updatePhoneRules(false);
  countrySelect.addEventListener("change", () => updatePhoneRules(true));
  phoneInput.addEventListener("input", formatInput);
}

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
  initPhoneFormat();
  initPasswordToggles();
  initPasswordStrength();
  initPasswordMatch();
}
