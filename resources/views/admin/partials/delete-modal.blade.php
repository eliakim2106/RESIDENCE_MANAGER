<div class="modal-overlay" id="deleteModal">

    <div class="modal-card">

        <div class="modal-icon">
            <i class="fa-solid fa-trash"></i>
        </div>

        <h3>Confirmer la suppression ?</h3>

        <p id="deleteText"></p>

        <form method="POST" id="deleteForm" class="modal-actions">
            @csrf
            @method('DELETE')

            <button type="button" id="cancelDelete" class="btn-cancel">Annuler</button>

            <button type="submit" class="btn-delete">Supprimer</button>
        </form>

    </div>

</div>
