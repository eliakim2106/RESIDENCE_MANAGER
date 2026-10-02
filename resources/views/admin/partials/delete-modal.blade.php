{{-- Suppression : ouverte par un bouton .delete-btn (data-url, data-name, data-detail) — admin/modals.js --}}
<div class="modal-overlay" id="deleteModal">

    <div class="modal-card modal-confirm" role="alertdialog" aria-modal="true" aria-labelledby="deleteModalTitle" aria-describedby="deleteText">

        <div class="modal-icon">
            <i class="fa-solid fa-trash-can"></i>
        </div>

        <h3 id="deleteModalTitle">Confirmer la suppression ?</h3>

        <p id="deleteText"></p>

        <p class="modal-note"><i class="fa-solid fa-circle-info"></i> Cette action est irréversible.</p>

        <form method="POST" id="deleteForm" class="modal-actions">
            @csrf
            @method('DELETE')

            <button type="button" class="btn-cancel" data-modal-close>Annuler</button>

            <button type="submit" class="btn-delete"><i class="fa-solid fa-trash-can"></i> Supprimer</button>
        </form>

    </div>

</div>

{{-- Confirmation d'une action : formulaires data-confirm, window.dsConfirm() — admin/modals.js --}}
<div class="modal-overlay" id="confirmModal">

    <div class="modal-card modal-confirm" role="alertdialog" aria-modal="true" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalText">

        <div class="modal-icon modal-icon-info">
            <i class="fa-solid fa-circle-question"></i>
        </div>

        <h3 id="confirmModalTitle" data-confirm-title></h3>

        <p id="confirmModalText" data-confirm-text></p>

        <div class="modal-actions">
            <button type="button" class="btn-cancel" data-modal-close>Annuler</button>

            <button type="button" class="btn-save" data-confirm-accept>Confirmer</button>
        </div>

    </div>

</div>
