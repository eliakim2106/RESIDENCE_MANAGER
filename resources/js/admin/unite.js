/*
|--------------------------------------------------------------------------
| FORMULAIRE D'UNITÉ
|--------------------------------------------------------------------------
| Contrôles de saisie et galerie d'images (mêmes classes CSS que la galerie d'établissement).
| window.unitGallery est défini par la vue : images existantes ({ id, url }), la première étant la principale.
*/

const MAX_IMAGES = 20;
const MAX_SIZE = 5 * 1024 * 1024;
const ALLOWED_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];

function initValidation(form) {
  const nom = document.getElementById("nom");
  const prix = document.getElementById("prix");
  const errorNom = document.getElementById("error-nom");
  const errorType = document.getElementById("error-type_unite");
  const errorPrix = document.getElementById("error-prix");
  const typeCards = document.querySelectorAll(".types-etablissements .carte-type-body");
  const typeInputs = document.querySelectorAll('input[name="type_unite_id"]');

  form.addEventListener("submit", (event) => {
    let valid = true;

    // Valeurs par défaut des compteurs laissés vides
    ["nombre_unite", "nombre_chambre", "nombre_lit", "nombre_salle_bain"].forEach((id) => {
      const input = document.getElementById(id);

      if (input && input.value.trim() === "") {
        input.value = 1;
      }
    });

    if (!document.querySelector('input[name="type_unite_id"]:checked')) {
      typeCards.forEach((card) => card.classList.add("card-error"));
      errorType.textContent = "Le type d'unité est obligatoire !";
      valid = false;
    }

    if (nom.value.trim() === "") {
      errorNom.textContent = "Le nom est obligatoire !";
      nom.classList.add("input-error");
      valid = false;
    }

    if (prix.value.replace(/\D/g, "") === "") {
      errorPrix.textContent = "Le prix est obligatoire !";
      prix.classList.add("input-error");
      valid = false;
    }

    if (!valid) {
      event.preventDefault();
      form.querySelector(".card-error, .input-error")?.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });

  typeInputs.forEach((input) => {
    input.addEventListener("change", () => {
      errorType.textContent = "";
      typeCards.forEach((card) => card.classList.remove("card-error"));
    });
  });

  [
    [nom, errorNom],
    [prix, errorPrix],
  ].forEach(([input, error]) => {
    input.addEventListener("input", () => {
      if (input.value.trim() !== "") {
        error.textContent = "";
        input.classList.remove("input-error");
      }
    });
  });
}

function initGallery(form) {
  const input = document.getElementById("galleryInput");
  const dropzone = document.getElementById("galleryDropzone");
  const preview = document.getElementById("galleryPreview");
  const counter = document.getElementById("galleryCount");
  const progress = document.getElementById("uploadProgressBar");
  const error = document.getElementById("error-gallery");
  const deletedInput = document.getElementById("deleted_gallery");
  const coverInput = document.getElementById("gallery_cover");

  if (!input || !dropzone || !preview) {
    return;
  }

  // Chaque image : { id, url } si existante, { file } si nouvelle
  const gallery = (window.unitGallery ?? []).map((image) => ({ id: image.id, url: image.url, file: null }));
  const deleted = [];
  let coverIndex = 0;

  const showError = (message) => {
    if (error) {
      error.textContent = message;
    }
  };

  const syncInputs = () => {
    const transfer = new DataTransfer();
    gallery.filter((image) => image.file).forEach((image) => transfer.items.add(image.file));
    input.files = transfer.files;

    const cover = gallery[coverIndex];
    coverInput.value = cover ? (cover.file ? cover.file.name : String(cover.id)) : "";
    deletedInput.value = JSON.stringify(deleted);
  };

  const render = () => {
    preview.replaceChildren();

    gallery.forEach((image, index) => {
      const card = document.createElement("div");
      card.className = "image-card";

      const img = document.createElement("img");
      img.alt = "Image de l'unité";

      if (image.file) {
        const objectUrl = URL.createObjectURL(image.file);
        img.src = objectUrl;
        img.onload = () => URL.revokeObjectURL(objectUrl);
      } else {
        img.src = image.url;
      }

      card.appendChild(img);

      if (index === coverIndex) {
        const badge = document.createElement("div");
        badge.className = "image-badge";
        badge.textContent = "Principale";
        card.appendChild(badge);
      }

      const overlay = document.createElement("div");
      overlay.className = "image-overlay";
      overlay.innerHTML = `
        ${index === coverIndex ? "" : `<button type="button" class="image-action btn-principal" data-action="cover" data-index="${index}"><i class="fa-solid fa-star"></i></button>`}
        <button type="button" class="image-action btn-delete" data-action="delete" data-index="${index}"><i class="fa-solid fa-trash"></i></button>
      `;
      card.appendChild(overlay);

      preview.appendChild(card);
    });

    if (counter) {
      counter.textContent = `${gallery.length} / ${MAX_IMAGES} images`;
    }

    if (progress) {
      progress.style.width = (gallery.length / MAX_IMAGES) * 100 + "%";
    }

    syncInputs();
  };

  const addFiles = (files) => {
    showError("");

    Array.from(files).forEach((file) => {
      if (gallery.length >= MAX_IMAGES) {
        showError(`Maximum ${MAX_IMAGES} images.`);
        return;
      }

      if (!ALLOWED_TYPES.includes(file.type)) {
        showError("Ce format d'image n'est pas autorisé.");
        return;
      }

      if (file.size > MAX_SIZE) {
        showError(`L'image ${file.name} dépasse 5 Mo.`);
        return;
      }

      gallery.push({ id: null, url: null, file });
    });

    render();
  };

  dropzone.addEventListener("click", () => input.click());

  input.addEventListener("change", () => {
    // input.files est reconstruit à chaque rendu : on copie la sélection avant
    const files = Array.from(input.files).filter((file) => !gallery.some((image) => image.file === file));
    addFiles(files);
  });

  ["dragenter", "dragover"].forEach((name) => {
    dropzone.addEventListener(name, (event) => {
      event.preventDefault();
      dropzone.classList.add("drag-over");
    });
  });

  ["dragleave", "drop"].forEach((name) => {
    dropzone.addEventListener(name, (event) => {
      event.preventDefault();
      dropzone.classList.remove("drag-over");
    });
  });

  dropzone.addEventListener("drop", (event) => addFiles(event.dataTransfer.files));

  preview.addEventListener("click", (event) => {
    const button = event.target.closest(".image-action");

    if (!button) {
      return;
    }

    const index = Number(button.dataset.index);

    if (button.dataset.action === "cover") {
      coverIndex = index;
    } else {
      const [removed] = gallery.splice(index, 1);

      if (removed?.id) {
        deleted.push(removed.id);
      }

      if (coverIndex >= gallery.length || index === coverIndex) {
        coverIndex = 0;
      } else if (index < coverIndex) {
        coverIndex--;
      }
    }

    render();
  });

  form.addEventListener("submit", (event) => {
    if (gallery.length === 0) {
      event.preventDefault();
      showError("Ajoutez au moins une image de l'unité.");
    }
  });

  render();
}

const form = document.getElementById("uniteForm");

if (form) {
  initValidation(form);
  initGallery(form);
}
