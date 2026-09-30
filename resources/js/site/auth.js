/* =====================================
   CONNEXION ET INSCRIPTION
===================================== */

// Choix du profil à l'inscription
function initAccountTypeChoice() {
  const continueBtn = document.getElementById("continueBtn");

  if (!continueBtn) {
    return;
  }

  continueBtn.addEventListener("click", () => {
    const selected = document.querySelector('input[name="account_type"]:checked');

    if (!selected) {
      alert("Veuillez choisir un profil.");
      return;
    }

    const url = continueBtn.dataset[selected.value === "owner" ? "ownerUrl" : "clientUrl"];

    if (url) {
      window.location.href = url;
    }
  });
}

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
  document.querySelectorAll(".toggle-password").forEach((button) => {
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.target);
      const icon = button.querySelector("i");

      if (!input) {
        return;
      }

      const hidden = input.type === "password";

      input.type = hidden ? "text" : "password";
      icon?.classList.toggle("fa-eye", !hidden);
      icon?.classList.toggle("fa-eye-slash", hidden);
    });
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

    passwordMatch.textContent = same
      ? "✓ Les mots de passe correspondent"
      : "✗ Les mots de passe ne correspondent pas";
    passwordMatch.className = "password-match " + (same ? "success" : "error");
  };

  password.addEventListener("input", check);
  confirmPassword.addEventListener("input", check);
}

export function initAuthForms() {
  initAccountTypeChoice();
  initPhoneFormat();
  initPasswordToggles();
  initPasswordMatch();
}
