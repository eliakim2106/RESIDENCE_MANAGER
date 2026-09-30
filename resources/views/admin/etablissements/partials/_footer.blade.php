<div class="wizard-footer">

    <button
        type="button"
        id="btnPrecedent"
        class="btn-wizard btn-secondary">

        <i class="fa-solid fa-arrow-left"></i>

        Précédent

    </button>

    <button
        type="button"
        id="btnSuivant"
        class="btn-wizard btn-primary">

        Suivant

        <i class="fa-solid fa-arrow-right"></i>

    </button>

    <button
        type="submit"
        id="btnEnregistrer"
        class="btn-wizard btn-primary">

        <i class="fa-solid {{ $etablissement->exists ? 'fa-pen-to-square' : 'fa-floppy-disk' }}"></i>

        {{ $etablissement->exists ? 'Mettre à jour' : 'Enregistrer' }}

    </button>

</div>
