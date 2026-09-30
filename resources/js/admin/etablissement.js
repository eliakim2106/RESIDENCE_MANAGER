/*
|--------------------------------------------------------------------------
| FORMULAIRE D'ÉTABLISSEMENT EN 6 ÉTAPES
|--------------------------------------------------------------------------
| Informations, localisation, contact & accueil, médias, publication, SEO.
| window.mediaConfig est défini par la vue : logo actuel et galerie existante ({ id, url, cover }).
*/

/*
  |--------------------------------------------------------------------------
  | ETABLISSEMENT.JS
  | DS HOLDING
  |--------------------------------------------------------------------------
  */

document.addEventListener("DOMContentLoaded", function () {
  ("use strict");

  /*
    |--------------------------------------------------------------------------
    TIME INPUT
    |--------------------------------------------------------------------------
    */

  document.querySelectorAll(".time-input input").forEach(function (input) {
    input.addEventListener("change", function () {
      this.parentElement.classList.add("selected");

      setTimeout(() => {
        this.parentElement.classList.remove("selected");
      }, 250);
    });
  });

  /*
    |--------------------------------------------------------------------------
    | GPS
    |--------------------------------------------------------------------------
    */

  const btnGps = document.getElementById("btnGps");

  if (btnGps) {
    btnGps.addEventListener("click", function () {
      if (!navigator.geolocation) {
        alert("Votre navigateur ne prend pas en charge la géolocalisation.");

        return;
      }

      const latitude = document.getElementById("latitude");

      const longitude = document.getElementById("longitude");

      const gpsMessage = document.getElementById("gpsMessage");

      btnGps.disabled = true;

      btnGps.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Localisation...';

      gpsMessage.textContent = "Recherche de votre position...";

      navigator.geolocation.getCurrentPosition(
        function (position) {
          latitude.value = position.coords.latitude.toFixed(7);

          longitude.value = position.coords.longitude.toFixed(7);

          gpsMessage.textContent =
            "Votre position a été récupérée avec succès.";

          btnGps.disabled = false;

          btnGps.innerHTML =
            '<i class="fa-solid fa-location-crosshairs"></i> Utiliser ma position';
        },

        function (error) {
          let message = "Impossible de récupérer votre position.";

          switch (error.code) {
            case error.PERMISSION_DENIED:
              message = "Vous avez refusé l'accès à la localisation.";

              break;

            case error.POSITION_UNAVAILABLE:
              message = "Position indisponible.";

              break;

            case error.TIMEOUT:
              message = "Le délai de localisation est dépassé.";

              break;
          }

          gpsMessage.textContent = message;

          btnGps.disabled = false;

          btnGps.innerHTML =
            '<i class="fa-solid fa-location-crosshairs"></i> Utiliser ma position';
        },

        {
          enableHighAccuracy: true,

          timeout: 10000,

          maximumAge: 0,
        },
      );
    });
  }

  /*
    |--------------------------------------------------------------------------
    | WIZARD
    |--------------------------------------------------------------------------
    */

  const Wizard = {
    current: 1,

    total: 6,

    steps: document.querySelectorAll(".etape"),

    contents: document.querySelectorAll(".etape-contenu"),

    btnNext: document.getElementById("btnSuivant"),

    btnPrevious: document.getElementById("btnPrecedent"),

    btnSave: document.getElementById("btnEnregistrer"),

    progress: document.getElementById("progressionBarre"),
  };

  /*
    |--------------------------------------------------------------------------
    | OUTILS
    |--------------------------------------------------------------------------
    */

  function showError(input, errorId, message) {
    if (input) {
      input.classList.add("input-error");
    }

    const error = document.getElementById(errorId);

    if (error) {
      error.textContent = message;
    }
  }

  function clearError(input, errorId) {
    if (input) {
      input.classList.remove("input-error");
    }

    const error = document.getElementById(errorId);

    if (error) {
      error.textContent = "";
    }
  }

  /*
    |--------------------------------------------------------------------------
    | TYPE ETABLISSEMENT
    |--------------------------------------------------------------------------
    */

  document.querySelectorAll(".carte-type input").forEach(function (radio) {
    radio.addEventListener("change", function () {
      document.querySelectorAll(".carte-type").forEach(function (card) {
        card.classList.remove("selected");
      });

      radio.closest(".carte-type").classList.add("selected");

      clearError(null, "error-type");

      document.querySelectorAll(".carte-type-body").forEach(function (card) {
        card.classList.remove("card-error");
      });
    });
  });

  /*
    |--------------------------------------------------------------------------
    | FONCTION WIZARD.SHOW
    |--------------------------------------------------------------------------
    */

  Wizard.show = function () {
    /*------------------------------------------
      Affichage des étapes
      ------------------------------------------*/

    this.contents.forEach(function (content) {
      content.classList.remove("active");
    });

    const current = document.querySelector(
      '.etape-contenu[data-step="' + this.current + '"]',
    );

    if (current) {
      current.classList.add("active");
    }

    /*------------------------------------------
      Stepper
      ------------------------------------------*/

    this.steps.forEach((step, index) => {
      const cercle = step.querySelector(".etape-cercle");

      step.classList.remove(
        "active",

        "done",
      );

      cercle.innerHTML = index + 1;

      if (index + 1 < this.current) {
        step.classList.add("done");

        cercle.innerHTML = '<i class="fa-solid fa-check"></i>';
      }

      if (index + 1 === this.current) {
        step.classList.add("active");
      }
    });

    /*------------------------------------------
      Barre de progression
      ------------------------------------------*/

    if (this.progress) {
      const progress = ((this.current - 1) / (this.total - 1)) * 83.333;

      this.progress.style.width = progress + "%";
    }

    /*------------------------------------------
      Boutons
      ------------------------------------------*/

    if (this.btnPrevious) {
      this.btnPrevious.style.visibility =
        this.current === 1 ? "hidden" : "visible";
    }

    if (this.btnNext) {
      this.btnNext.style.display =
        this.current === this.total ? "none" : "inline-flex";
    }

    if (this.btnSave) {
      this.btnSave.style.display =
        this.current === this.total ? "inline-flex" : "none";
    }
  };

  /*
    |--------------------------------------------------------------------------
    | METHODE SUIVANT
    |--------------------------------------------------------------------------
    */

  Wizard.next = function () {
    if (!this.validate()) {
      return false;
    }

    if (this.current < this.total) {
      this.current++;

      this.show();

      window.scrollTo({
        top: 0,

        behavior: "smooth",
      });
    }
  };

  /*
    |--------------------------------------------------------------------------
    | METHODE PRECEDENT
    |--------------------------------------------------------------------------
    */

  Wizard.previous = function () {
    if (this.current > 1) {
      this.current--;

      this.show();

      window.scrollTo({
        top: 0,

        behavior: "smooth",
      });
    }
  };

  /*
    |--------------------------------------------------------------------------
    | ALLER A UNE ETAPE
    |--------------------------------------------------------------------------
    */

  Wizard.goTo = function (step) {
    if (step < this.current) {
      this.current = step;

      this.show();

      return;
    }

    if (step === this.current + 1) {
      if (this.validate()) {
        this.current = step;

        this.show();
      }
    }
  };

  /*
  |--------------------------------------------------------------------------
  | DEBUT DU CONTENU DES ETAPES
  |--------------------------------------------------------------------------
  */

  /*
  |--------------------------------------------------------------------------
  | VALIDATION ETAPE 1
  |--------------------------------------------------------------------------
  */

  function validateInformations() {
    let valid = true;

    const nom = document.getElementById("nom");

    clearError(nom, "error-nom");
    clearError(null, "error-type");

    /*
      |--------------------------------------------------------------------------
      | NOM
      |--------------------------------------------------------------------------
      */

    if (!nom || nom.value.trim().length < 3) {
      showError(
        nom,

        "error-nom",

        "Le nom de l'établissement doit contenir au moins 3 caractères.",
      );

      valid = false;
    }

    /*
      |--------------------------------------------------------------------------
      | TYPE ETABLISSEMENT
      |--------------------------------------------------------------------------
      */

    const type = document.querySelector(
      "input[name='type_etablissement_id']:checked",
    );

    const cartes = document.querySelectorAll(".carte-type-body");

    cartes.forEach(function (card) {
      card.classList.remove("card-error");
    });

    if (!type) {
      showError(
        null,

        "error-type",

        "Veuillez sélectionner un type d'établissement.",
      );

      cartes.forEach(function (card) {
        card.classList.add("card-error");
      });

      valid = false;
    }

    return valid;
  }

  /*
  |--------------------------------------------------------------------------
  | VALIDATION ETAPE 2
  |--------------------------------------------------------------------------
  */

  function validateLocalisation() {
    let valid = true;

    const ville = document.getElementById("ville");

    const commune = document.getElementById("commune");

    clearError(ville, "error-ville");
    clearError(commune, "error-commune");

    /*
      |--------------------------------------------------------------------------
      | VILLE
      |--------------------------------------------------------------------------
      */

    if (!ville || ville.value.trim() === "") {
      showError(
        ville,

        "error-ville",

        "Veuillez renseigner la ville.",
      );

      valid = false;
    }

    /*
      |--------------------------------------------------------------------------
      | COMMUNE
      |--------------------------------------------------------------------------
      */

    if (!commune || commune.value.trim() === "") {
      showError(
        commune,

        "error-commune",

        "Veuillez renseigner la commune.",
      );

      valid = false;
    }

    return valid;
  }

  /*
  |--------------------------------------------------------------------------
  | ETAPE 3 - CONTACT & ACCUEIL
  |--------------------------------------------------------------------------
  */

  /*
    |--------------------------------------------------------------------------
    | ETOILES
    |--------------------------------------------------------------------------
    */

  const etoileInput = document.getElementById("etoile");

  const stars = document.querySelectorAll(".rating-stars .star");

  if (stars.length) {
    stars.forEach(function (star) {
      star.addEventListener("click", function () {
        const value = parseInt(this.dataset.value);

        etoileInput.value = value;

        stars.forEach(function (item) {
          const current = parseInt(item.dataset.value);

          if (current <= value) {
            item.classList.remove("fa-regular");

            item.classList.add("fa-solid");

            item.classList.add("active");
          } else {
            item.classList.remove("fa-solid");

            item.classList.remove("active");

            item.classList.add("fa-regular");
          }
        });
      });
    });
  }

  // update stars based on the initial value of etoileInput
  function updateStars(value) {
    stars.forEach(function (item) {
      const current = parseInt(item.dataset.value);

      if (current <= value) {
        item.classList.remove("fa-regular");
        item.classList.add("fa-solid");
        item.classList.add("active");
      } else {
        item.classList.remove("fa-solid");
        item.classList.remove("active");
        item.classList.add("fa-regular");
      }
    });
  }

  // Initialisation (important pour la modification)
  if (etoileInput && stars.length) {
    updateStars(parseInt(etoileInput.value) || 0);

    stars.forEach(function (star) {
      star.addEventListener("click", function () {
        const value = parseInt(this.dataset.value);

        etoileInput.value = value;

        updateStars(value);
      });
    });
  }

  /*
    |--------------------------------------------------------------------------
    | SITE WEB
    |--------------------------------------------------------------------------
    */

  const siteWeb = document.getElementById("site_web");

  if (siteWeb) {
    siteWeb.addEventListener("blur", function () {
      let url = this.value.trim();

      if (url !== "") {
        if (!url.startsWith("http://") && !url.startsWith("https://")) {
          this.value = "https://" + url;
        }
      }
    });
  }

  /*
    |--------------------------------------------------------------------------
    | TELEPHONE
    |--------------------------------------------------------------------------
    */

  const telephone = document.getElementById("telephone");

  if (telephone) {
    telephone.addEventListener("input", function () {
      this.value = this.value.replace(/\D/g, "");

      clearError(this, "error-telephone");
    });
  }

  /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

  const email = document.getElementById("email");

  if (email) {
    email.addEventListener("input", function () {
      clearError(this, "error-email");
    });
  }

  /*
    |--------------------------------------------------------------------------
    | VALIDATION ETAPE 3
    |--------------------------------------------------------------------------
    */

  function validateContactAccueil() {
    let valid = true;

    /*
      ------------------------------------------------------
      TELEPHONE
      ------------------------------------------------------
      */

    if (telephone && telephone.value.trim() !== "") {
      if (telephone.value.length !== 10) {
        showError(
          telephone,

          "error-telephone",

          "Le numéro doit contenir 10 chiffres.",
        );

        valid = false;
      }
    }

    /*
      ------------------------------------------------------
      EMAIL
      ------------------------------------------------------
      */

    if (email && email.value.trim() !== "") {
      const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!regex.test(email.value)) {
        showError(
          email,

          "error-email",

          "Adresse email invalide.",
        );

        valid = false;
      }
    }

    /*
      ------------------------------------------------------
      CHECK IN / CHECK OUT
      ------------------------------------------------------
      */

    const checkIn = document.getElementById("check_in");

    const checkOut = document.getElementById("check_out");

    if (checkIn && checkOut && checkIn.value !== "" && checkOut.value !== "") {
      if (checkIn.value >= checkOut.value) {
        showError(
          checkOut,

          "error-checkout",

          "L'heure de départ doit être supérieure à l'heure d'arrivée.",
        );

        valid = false;
      }
    }

    return valid;
  }

  /*
  |--------------------------------------------------------------------------
  | ETAPE 4 - MEDIAS
  |--------------------------------------------------------------------------
  */

  const Media = {
    /*
      |--------------------------------------------------------------------------
      | Configuration
      |--------------------------------------------------------------------------
      */
    config: {
      maxLogoSize: 2 * 1024 * 1024,

      maxImageSize: 5 * 1024 * 1024,

      maxImages: 20,

      allowedTypes: ["image/jpeg", "image/jpg", "image/png", "image/webp"],
    },

    /*
      |--------------------------------------------------------------------------
      | DOM
      |--------------------------------------------------------------------------
      */
    dom: {
      logoInput: document.getElementById("logoInput"),

      logoDropzone: document.getElementById("logoDropzone"),

      logoPreview: document.getElementById("logoPreview"),

      btnChangeLogo: document.getElementById("btnChangeLogo"),

      galleryInput: document.getElementById("galleryInput"),

      galleryDropzone: document.getElementById("galleryDropzone"),

      galleryPreview: document.getElementById("galleryPreview"),

      galleryCount: document.getElementById("galleryCount"),

      progressBar: document.getElementById("uploadProgressBar"),

      deletedLogoInput: document.getElementById("deleted_logo"),

      principalInput: document.getElementById("principal_image"),

      deletedInput: document.getElementById("deleted_gallery"),

      coverInput: document.getElementById("gallery_cover"),

      galleryExistingInput: document.getElementById("gallery_existing"),
    },

    /*
      |--------------------------------------------------------------------------
      | Etat
      |--------------------------------------------------------------------------
      */
    state: {
      logo: {
        file: null,

        name: null,

        deleted: false,
      },

      gallery: [],

      deletedGallery: [],
    },
  };

  /*
  |--------------------------------------------------------------------------
  | Chargement du logo existant
  |--------------------------------------------------------------------------
  */

  Media.loadExistingLogo = function () {
    if (!window.mediaConfig?.logo) {
      return;
    }

    this.state.logo.name = window.mediaConfig.logo;
  };

  /*
  |--------------------------------------------------------------------------
  | Chargement de la galerie existante
  |--------------------------------------------------------------------------
  */

  Media.loadExistingGallery = function () {
    const gallery = window.mediaConfig.gallery ?? [];

    this.state.gallery = gallery.map((image) => ({
      id: image.id ?? null,

      type: "existing",

      file: null,

      filename: String(image.id),

      url: image.url,

      cover: Boolean(image.cover),
    }));
  };

  /*
  |--------------------------------------------------------------------------
  | Affichage du logo
  |--------------------------------------------------------------------------
  */

  Media.renderLogo = function () {
    if (!this.dom.logoPreview) {
      return;
    }

    // Nettoyage
    this.dom.logoPreview.innerHTML = "";

    /*
      |--------------------------------------------------------------------------
      | Aucun logo
      |--------------------------------------------------------------------------
      */

    if (
      this.state.logo.deleted ||
      (!this.state.logo.file && !this.state.logo.name)
    ) {
      this.dom.logoPreview.innerHTML = `
              <div class="logo-placeholder">

                  <i class="fa-regular fa-image"></i>

                  <h5>Aucun logo</h5>

                  <p>Sélectionnez le logo de votre établissement.</p>

              </div>
          `;

      if (this.dom.btnChangeLogo) {
        this.dom.btnChangeLogo.innerHTML = `
                  <i class="fa-solid fa-upload"></i>
                  Choisir un logo
              `;
      }

      return;
    }

    /*
      |--------------------------------------------------------------------------
      | Nouveau logo
      |--------------------------------------------------------------------------
      */

    const removeButton = `
        <button
            type="button"
            class="logo-remove"
            title="Supprimer le logo"
            aria-label="Supprimer le logo">

            <i class="fa-solid fa-xmark"></i>

        </button> 
    `;

    if (this.state.logo.file) {
      const url = URL.createObjectURL(this.state.logo.file);

      this.dom.logoPreview.innerHTML = `
        <img
            src="${url}"
            class="logo-image"
            alt="Logo">

        ${removeButton}
    `;

      const img = this.dom.logoPreview.querySelector(".logo-image");

      img.onload = () => URL.revokeObjectURL(url);
    } else {
      /*
          |--------------------------------------------------------------------------
          | Logo existant
          |--------------------------------------------------------------------------
          */

      this.dom.logoPreview.innerHTML = `
              <img
                  src="${window.mediaConfig.logoUrl}"
                  class="logo-image"
                  alt="Logo">

              ${removeButton}
          `;
    }

    if (this.dom.btnChangeLogo) {
      this.dom.btnChangeLogo.innerHTML = `
              <i class="fa-solid fa-rotate"></i>
              Remplacer le logo
          `;
    }
  };

  /*
  |--------------------------------------------------------------------------
  | Définition du logo
  |--------------------------------------------------------------------------
  */

  Media.setLogo = function (file) {
    this.state.logo.file = file;

    this.state.logo.name = null;

    this.state.logo.deleted = false;

    if (this.dom.deletedLogoInput) {
      this.dom.deletedLogoInput.value = "0";
    }

    this.renderLogo();
  };

  /*
  |--------------------------------------------------------------------------
  | Suppression du logo
  |--------------------------------------------------------------------------
  */

  Media.removeLogo = function () {
    this.state.logo.file = null;

    this.dom.logoInput.value = "";

    if (this.state.logo.name) {
      this.state.logo.deleted = true;
    }

    if (this.dom.deletedLogoInput) {
      this.dom.deletedLogoInput.value = "1";
    }

    this.renderLogo();
  };

  /*
  |--------------------------------------------------------------------------
  | Validation du logo
  |--------------------------------------------------------------------------
  */

  Media.validateLogo = function (file) {
    clearError(null, "error-logo");

    if (!this.config.allowedTypes.includes(file.type)) {
      showError(null, "error-logo", "Format de fichier non autorisé.");

      return false;
    }

    if (file.size > this.config.maxLogoSize) {
      showError(null, "error-logo", "Le logo dépasse 2 Mo.");

      return false;
    }

    return true;
  };

  /*
  |--------------------------------------------------------------------------
  | Evènements du logo
  |--------------------------------------------------------------------------
  */

  Media.bindLogoEvents = function () {
    /*
      |--------------------------------------------------------------------------
      | Bouton "Choisir un logo"
      |--------------------------------------------------------------------------
      */

    this.dom.btnChangeLogo?.addEventListener("click", () => {
      this.dom.logoInput.click();
    });

    /*
      |--------------------------------------------------------------------------
      | Clic sur la carte
      |--------------------------------------------------------------------------
      */

    this.dom.logoDropzone?.addEventListener("click", (e) => {
      if (e.target.closest(".logo-remove")) {
        return;
      }

      this.dom.logoInput.click();
    });

    /*
      |--------------------------------------------------------------------------
      | Sélection d'un fichier
      |--------------------------------------------------------------------------
      */

    this.dom.logoInput?.addEventListener("change", (e) => {
      const file = e.target.files[0];

      if (!file) {
        return;
      }

      if (!this.validateLogo(file)) {
        this.dom.logoInput.value = "";
        return;
      }

      this.setLogo(file);
    });

    /*
      |--------------------------------------------------------------------------
      | Suppression
      |--------------------------------------------------------------------------
      */

    this.dom.logoPreview?.addEventListener("click", (e) => {
      const btn = e.target.closest(".logo-remove");

      if (!btn) {
        return;
      }

      e.stopPropagation();

      this.removeLogo();
    });
  };

  /*
  |--------------------------------------------------------------------------
  | Drag & Drop du logo
  |--------------------------------------------------------------------------
  */

  Media.bindLogoDrag = function () {
    if (!this.dom.logoDropzone) {
      return;
    }

    const zone = this.dom.logoDropzone;

    ["dragenter", "dragover"].forEach((event) => {
      zone.addEventListener(event, (e) => {
        e.preventDefault();

        zone.classList.add("drag-over");
      });
    });

    ["dragleave", "drop"].forEach((event) => {
      zone.addEventListener(event, (e) => {
        e.preventDefault();

        zone.classList.remove("drag-over");
      });
    });

    zone.addEventListener("drop", (e) => {
      e.preventDefault();

      const file = e.dataTransfer.files[0];

      if (!file) {
        return;
      }

      if (!this.validateLogo(file)) {
        return;
      }

      this.setLogo(file);
    });
  };

  /*
  |--------------------------------------------------------------------------
  | Validation d'une image
  |--------------------------------------------------------------------------
  */

  Media.validateImage = function (file) {
    clearError(null, "error-gallery");

    if (!this.config.allowedTypes.includes(file.type)) {
      showError(null, "error-gallery", "Ce format d'image n'est pas autorisé.");

      return false;
    }

    if (file.size > this.config.maxImageSize) {
      showError(
        null,
        "error-gallery",
        "Chaque image doit faire moins de 5 Mo.",
      );

      return false;
    }

    return true;
  };

  /*
  |--------------------------------------------------------------------------
  | Ajout des images
  |--------------------------------------------------------------------------
  */

  Media.addFiles = function (files) {
    clearError(null, "error-gallery");
    Array.from(files).forEach((file) => {
      if (this.state.gallery.length >= this.config.maxImages) {
        showError(
          null,
          "error-gallery",
          `Maximum ${this.config.maxImages} images.`,
        );

        return;
      }

      if (!this.validateImage(file)) {
        return;
      }

      this.state.gallery.push({
        id: null,

        type: "new",

        file: file,

        filename: null,

        cover: this.state.gallery.length === 0,
      });
    });

    const coverIndex = this.state.gallery.findIndex((image) => image.cover);

    if (this.dom.principalInput) {
      this.dom.principalInput.value = coverIndex;
    }

    this.syncGalleryInput();

    this.renderGallery();
  };

  /*
  |--------------------------------------------------------------------------
  | Synchronisation de l'input file
  |--------------------------------------------------------------------------
  */

  Media.syncGalleryInput = function () {
    const dt = new DataTransfer();

    this.state.gallery
      .filter((image) => image.type === "new")
      .forEach((image) => {
        dt.items.add(image.file);
      });

    this.dom.galleryInput.files = dt.files;
  };

  /*
  |--------------------------------------------------------------------------
  | Synchronisation des champs cachés
  |--------------------------------------------------------------------------
  */

  Media.syncHiddenInputs = function () {
    // if (this.dom.deletedInput) {
    //   this.dom.deletedInput.value = JSON.stringify(
    //     this.state.deletedGallery.map((image) => image.id),
    //   );
    // }

    if (this.dom.deletedInput) {
      this.dom.deletedInput.value = JSON.stringify(
        this.state.deletedGallery.map((image) => image.filename),
      );
    }

    if (this.dom.coverInput) {
      const cover = this.state.gallery.find((image) => image.cover);

      // this.dom.coverInput.value = cover ? (cover.id ?? "") : "";
      this.dom.coverInput.value = cover
        ? (cover.filename ?? cover.file?.name ?? "")
        : "";
    }

    if (this.dom.galleryExistingInput) {
      this.dom.galleryExistingInput.value = JSON.stringify(
        this.state.gallery
          .filter((image) => image.type === "existing")
          .map((image) => ({
            file: image.filename,
            cover: image.cover,
          })),
      );
    }
  };

  /*
  |--------------------------------------------------------------------------
  | Compteur
  |--------------------------------------------------------------------------
  */

  Media.updateCounter = function () {
    if (!this.dom.galleryCount) {
      return;
    }

    this.dom.galleryCount.textContent = `${this.state.gallery.length} / ${this.config.maxImages} images`;
  };

  /*
  |--------------------------------------------------------------------------
  | Barre de progression
  |--------------------------------------------------------------------------
  */

  Media.updateProgress = function () {
    if (!this.dom.progressBar) {
      return;
    }

    const percent = (this.state.gallery.length / this.config.maxImages) * 100;

    this.dom.progressBar.style.width = percent + "%";
  };

  /*
  |--------------------------------------------------------------------------
  | Construction d'une carte image
  |--------------------------------------------------------------------------
  */

  Media.renderImage = function (image, index) {
    const card = document.createElement("div");

    card.className = "image-card";

    /*
      |--------------------------------------------------------------------------
      | Image
      |--------------------------------------------------------------------------
      */

    const img = document.createElement("img");

    if (image.type === "existing") {
      img.src = image.url;
    } else {
      // img.src = URL.createObjectURL(image.file);
      const objectUrl = URL.createObjectURL(image.file);

      img.src = objectUrl;

      img.onload = () => URL.revokeObjectURL(objectUrl);
    }

    img.alt = "Galerie";

    card.appendChild(img);

    /*
      |--------------------------------------------------------------------------
      | Badge principale
      |--------------------------------------------------------------------------
      */

    if (image.cover) {
      const badge = document.createElement("div");

      badge.className = "image-badge";

      badge.textContent = "Principale";

      card.appendChild(badge);
    }

    /*
      |--------------------------------------------------------------------------
      | Overlay
      |--------------------------------------------------------------------------
      */

    const overlay = document.createElement("div");

    overlay.className = "image-overlay";

    const coverButton = !image.cover
      ? `
              <button
                  type="button"
                  class="image-action btn-principal"
                  data-action="cover"
                  data-index="${index}">

                  <i class="fa-solid fa-star"></i>

              </button>
          `
      : "";

    overlay.innerHTML = `

          ${coverButton}

          <button
              type="button"
              class="image-action btn-delete"
              data-action="delete"
              data-index="${index}">

              <i class="fa-solid fa-trash"></i>

          </button>

      `;

    card.appendChild(overlay);

    return card;
  };

  /*
  |--------------------------------------------------------------------------
  | Affichage de la galerie
  |--------------------------------------------------------------------------
  */

  Media.renderGallery = function () {
    if (!this.dom.galleryPreview) {
      return;
    }

    this.dom.galleryPreview.innerHTML = "";

    this.state.gallery.forEach((image, index) => {
      this.dom.galleryPreview.appendChild(this.renderImage(image, index));
    });

    this.updateCounter();

    this.updateProgress();

    this.syncHiddenInputs();
  };

  /*
  |--------------------------------------------------------------------------
  | Définir l'image principale
  |--------------------------------------------------------------------------
  */

  Media.setCover = function (index) {
    if (!this.state.gallery[index]) {
      return;
    }

    this.state.gallery.forEach((image) => {
      image.cover = false;
    });

    this.state.gallery[index].cover = true;

    this.dom.principalInput.value = index;

    this.renderGallery();
  };

  /*
  |--------------------------------------------------------------------------
  | Suppression d'une image
  |--------------------------------------------------------------------------
  */

  Media.removeImage = function (index) {
    const image = this.state.gallery[index];

    if (!image) {
      return;
    }

    /*
      |--------------------------------------------------------------------------
      | Ancienne image
      |--------------------------------------------------------------------------
      */

    if (image.type === "existing") {
      this.state.deletedGallery.push(image);
    }

    this.state.gallery.splice(index, 1);

    /*
      |--------------------------------------------------------------------------
      | Toujours une image principale
      |--------------------------------------------------------------------------
      */

    if (
      this.state.gallery.length &&
      !this.state.gallery.some((img) => img.cover)
    ) {
      this.state.gallery[0].cover = true;
    }

    this.syncGalleryInput();

    this.renderGallery();
  };

  /*
  |--------------------------------------------------------------------------
  | Evènements de la galerie
  |--------------------------------------------------------------------------
  */

  Media.bindGalleryEvents = function () {
    /*
      |--------------------------------------------------------------------------
      | Ouverture du sélecteur
      |--------------------------------------------------------------------------
      */

    this.dom.galleryDropzone?.addEventListener("click", (e) => {
      if (e.target.closest(".image-action")) {
        return;
      }

      this.dom.galleryInput.click();
    });

    /*
      |--------------------------------------------------------------------------
      | Sélection de fichiers
      |--------------------------------------------------------------------------
      */

    this.dom.galleryInput?.addEventListener("change", (e) => {
      if (!e.target.files.length) {
        return;
      }

      this.addFiles(e.target.files);
    });

    /*
      |--------------------------------------------------------------------------
      | Drag & Drop
      |--------------------------------------------------------------------------
      */

    ["dragenter", "dragover"].forEach((event) => {
      this.dom.galleryDropzone?.addEventListener(event, (e) => {
        e.preventDefault();

        this.dom.galleryDropzone.classList.add("drag-over");
      });
    });

    ["dragleave", "drop"].forEach((event) => {
      this.dom.galleryDropzone?.addEventListener(event, (e) => {
        e.preventDefault();

        this.dom.galleryDropzone.classList.remove("drag-over");
      });
    });

    this.dom.galleryDropzone?.addEventListener("drop", (e) => {
      e.preventDefault();
      if (!e.dataTransfer.files.length) {
        return;
      }

      this.addFiles(e.dataTransfer.files);
    });
  };

  /*
  |--------------------------------------------------------------------------
  | Actions sur les cartes
  |--------------------------------------------------------------------------
  */

  Media.bindActions = function () {
    this.dom.galleryPreview?.addEventListener("click", (e) => {
      const button = e.target.closest(".image-action");

      if (!button) {
        return;
      }

      const action = button.dataset.action;

      const index = Number(button.dataset.index);

      switch (action) {
        case "cover":
          this.setCover(index);

          break;

        case "delete":
          this.removeImage(index);

          break;
      }
    });
  };

  /*
  |--------------------------------------------------------------------------
  | Initialisation
  |--------------------------------------------------------------------------
  */

  Media.start = function () {
    this.loadExistingLogo();

    this.loadExistingGallery();

    this.bindLogoEvents();

    this.bindLogoDrag();

    this.bindGalleryEvents();

    this.bindActions();

    this.renderLogo();

    this.renderGallery();
  };

  /*
  |--------------------------------------------------------------------------
  | Réinitialisation
  |--------------------------------------------------------------------------
  */

  Media.reset = function () {
    /*
      |--------------------------------------------------------------------------
      | Logo
      |--------------------------------------------------------------------------
      */

    this.state.logo = {
      file: null,

      name: null,

      deleted: false,
    };

    /*
      |--------------------------------------------------------------------------
      | Galerie
      |--------------------------------------------------------------------------
      */

    this.state.gallery = [];

    this.state.deletedGallery = [];

    /*
      |--------------------------------------------------------------------------
      | Inputs
      |--------------------------------------------------------------------------
      */

    if (this.dom.logoInput) {
      this.dom.logoInput.value = "";
    }

    if (this.dom.galleryInput) {
      this.dom.galleryInput.value = "";
    }

    if (this.dom.deletedInput) {
      this.dom.deletedInput.value = "";
    }

    if (this.dom.coverInput) {
      this.dom.coverInput.value = "";
    }

    if (this.dom.principalInput) {
      this.dom.principalInput.value = "";
    }

    if (this.dom.deletedLogoInput) {
      this.dom.deletedLogoInput.value = "0";
    }

    /*
      |--------------------------------------------------------------------------
      | Affichage
      |--------------------------------------------------------------------------
      */

    this.loadExistingLogo();

    this.loadExistingGallery();

    this.renderLogo();

    this.renderGallery();
  };

  /*
  |--------------------------------------------------------------------------
  | Démarrage
  |--------------------------------------------------------------------------
  */

  Media.start();

  /*
|--------------------------------------------------------------------------
| Validation des médias
|--------------------------------------------------------------------------
*/

  Media.validate = function () {
    let valid = true;

    clearError(null, "error-gallery");

    /*
  |--------------------------------------------------------------------------
  | Galerie obligatoire
  |--------------------------------------------------------------------------
  */

    if (this.state.gallery.length === 0) {
      showError(
        null,
        "error-gallery",
        "Veuillez ajouter au moins une image dans la galerie.",
      );

      valid = false;
    }

    return valid;
  };

  /*
  |--------------------------------------------------------------------------
  | ETAPE 5 - PUBLICATION
  |--------------------------------------------------------------------------
  */

  const Publication = {
    radios: document.querySelectorAll("input[name='status']"),

    resume: document.getElementById("publicationResume"),
  };

  /*==================================================
  INITIALISATION
  ==================================================*/

  Publication.init = function () {
    if (this.radios.length === 0) {
      return;
    }

    this.radios.forEach((radio) => {
      radio.addEventListener(
        "change",

        () => {
          this.updateResume();
        },
      );
    });

    this.updateResume();
  };

  /*==================================================
  MISE A JOUR
  ==================================================*/

  Publication.updateResume = function () {
    const actif = document.querySelector("input[name='status']:checked");

    if (!actif) {
      return;
    }

    // Le texte dépend du rôle et de l'état de l'établissement : il est fourni par la vue
    if (actif.dataset.resume) {
      this.resume.textContent = actif.dataset.resume;
    }
  };

  /*==================================================
  VALIDATION
  ==================================================*/

  Publication.validate = function () {
    const status = document.querySelector("input[name='status']:checked");

    if (!status) {
      return false;
    }

    return true;
  };

  /*==================================================
    LANCEMENT
    ==================================================*/

  Publication.init();

  /*
  |--------------------------------------------------------------------------
  | ETAPE 6 - SEO
  |--------------------------------------------------------------------------
  */

  const Seo = {
    title: document.getElementById("meta_title"),

    description: document.getElementById("meta_description"),

    slug: document.getElementById("slug"),

    titleCounter: document.getElementById("titleCounter"),

    descriptionCounter: document.getElementById("descriptionCounter"),

    googleTitle: document.getElementById("googleTitle"),

    googleUrl: document.getElementById("googleUrl"),

    googleDescription: document.getElementById("googleDescription"),

    nom: document.getElementById("nom"),
  };

  /*==================================================
  INITIALISATION
  ==================================================*/

  Seo.init = function () {
    if (!this.title) {
      return;
    }

    this.bindEvents();

    // En modification, on conserve l'URL déjà enregistrée
    if (this.slug.value.trim() === "") {
      this.autoGenerateSlug();
    }

    this.update();
  };

  /*==================================================
  EVENEMENTS
  ==================================================*/

  Seo.bindEvents = function () {
    this.title.addEventListener(
      "input",

      () => {
        this.update();
      },
    );

    this.description.addEventListener(
      "input",

      () => {
        this.update();
      },
    );

    this.slug.addEventListener(
      "input",

      () => {
        this.updateGoogle();
      },
    );

    /*
      ----------------------------------
      Nom établissement
      ----------------------------------
      */

    if (this.nom) {
      this.nom.addEventListener(
        "input",

        () => {
          this.autoGenerateSlug();
        },
      );
    }
  };

  /*==================================================
  SLUG
  ==================================================*/

  Seo.autoGenerateSlug = function () {
    if (!this.nom || !this.slug) {
      return;
    }

    const slug = this.nom.value

      .toLowerCase()

      .normalize("NFD")

      .replace(/[\u0300-\u036f]/g, "")

      .replace(/[^a-z0-9]+/g, "-")

      .replace(/^-+|-+$/g, "")

      .replace(/-{2,}/g, "-");

    this.slug.value = slug;

    this.updateGoogle();
  };

  /*==================================================
  COMPTEURS
  ==================================================*/

  Seo.updateCounters = function () {
    this.updateCounter(
      this.titleCounter,

      this.title.value.length,

      60,
    );

    this.updateCounter(
      this.descriptionCounter,

      this.description.value.length,

      160,
    );
  };

  Seo.updateCounter = function (
    element,

    length,

    max,
  ) {
    element.textContent = length;

    element.classList.remove(
      "counter-good",

      "counter-warning",

      "counter-danger",
    );

    const percent = (length / max) * 100;

    if (percent < 60) {
      element.classList.add("counter-warning");
    } else if (percent <= 100) {
      element.classList.add("counter-good");
    } else {
      element.classList.add("counter-danger");
    }
  };

  /*==================================================
  GOOGLE PREVIEW
  ==================================================*/

  Seo.updateGoogle = function () {
    this.googleTitle.textContent = this.title.value || "Titre SEO";

    this.googleDescription.textContent =
      this.description.value || "Votre description apparaîtra ici...";

    this.googleUrl.textContent =
      (this.slug.dataset.baseUrl ?? window.location.origin + "/etablissement/") +
      this.slug.value;
  };

  /*==================================================
  MISE A JOUR
  ==================================================*/

  Seo.update = function () {
    this.updateCounters();

    this.updateGoogle();
  };

  /*==================================================
  VALIDATION
  ==================================================*/

  Seo.validate = function () {
    let valid = true;

    clearError(
      null,

      "error-meta-title",
    );

    clearError(
      null,

      "error-meta-description",
    );

    clearError(
      null,

      "error-slug",
    );

    /*
      ----------------------------------
      Titre
      ----------------------------------
      */

    if (this.title.value.trim().length < 20) {
      showError(
        null,

        "error-meta-title",

        "Le titre SEO doit contenir au moins 20 caractères.",
      );

      valid = false;
    }

    /*
      ----------------------------------
      Description
      ----------------------------------
      */

    if (this.description.value.trim().length < 80) {
      showError(
        null,

        "error-meta-description",

        "La description doit contenir au moins 80 caractères.",
      );

      valid = false;
    }

    /*
      ----------------------------------
      Slug
      ----------------------------------
      */

    if (this.slug.value.trim() === "") {
      showError(
        null,

        "error-slug",

        "Le slug est obligatoire.",
      );

      valid = false;
    }

    return valid;
  };

  /*==================================================
  INITIALISATION
  ==================================================*/

  Seo.init();

  /*
  |--------------------------------------------------------------------------
  | FIN DU CONTENU DES ETAPES
  |--------------------------------------------------------------------------
  */

  /*
    |--------------------------------------------------------------------------
    | VALIDATION GLOBALE
    |--------------------------------------------------------------------------
    */

  Wizard.validate = function () {
    switch (this.current) {
      case 1:
        return validateInformations();
      // return true;

      case 2:
        return validateLocalisation();
      // return true;

      case 3:
        return validateContactAccueil();
      // return true;

      case 4:
        return Media.validate();
      // return true;

      case 5:
        return Publication.validate();
      // return true;

      case 6:
        return Seo.validate();
      // return true;

      default:
        return true;
    }
  };

  /*
    |--------------------------------------------------------------------------
    | VALIDATION EN TEMPS REEL
    |--------------------------------------------------------------------------
    */

  [
    {
      id: "nom",
      error: "error-nom",
    },
    {
      id: "ville",
      error: "error-ville",
    },
    {
      id: "commune",
      error: "error-commune",
    },
  ].forEach(function (field) {
    const input = document.getElementById(field.id);

    if (!input) {
      return;
    }

    input.addEventListener("input", function () {
      clearError(
        input,

        field.error,
      );
    });
  });

  /*
    |--------------------------------------------------------------------------
    | BOUTON SUIVANT
    |--------------------------------------------------------------------------
    */

  if (Wizard.btnNext) {
    Wizard.btnNext.addEventListener(
      "click",

      function () {
        Wizard.next();
      },
    );
  }

  /*
    |--------------------------------------------------------------------------
    | BOUTON PRECEDENT
    |--------------------------------------------------------------------------
    */

  if (Wizard.btnPrevious) {
    Wizard.btnPrevious.addEventListener(
      "click",

      function () {
        Wizard.previous();
      },
    );
  }

  /*
    |--------------------------------------------------------------------------
    | INITIALISATION
    |--------------------------------------------------------------------------
    */

  Wizard.steps.forEach(function (step, index) {
    step.addEventListener(
      "click",

      function () {
        Wizard.goTo(index + 1);
      },
    );
  });

  /*
    |--------------------------------------------------------------------------
    | AFFICHAGE DU WIZARD AVEC WZARD.SHOW
    |--------------------------------------------------------------------------
    */

  Wizard.show();
});
