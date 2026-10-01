{{--
    Barre d'enregistrement : apparaît dès qu'un champ du formulaire change (parametres.js).
    Paramètres : $form (identifiant du formulaire), $label (texte du bouton)
--}}
<div class="prm-savebar" data-savebar role="region" aria-label="Enregistrement" @unless ($errors->any()) hidden @endunless>
    <div class="prm-savebar-inner">
        <span class="prm-savebar-status">
            <span class="prm-savebar-pulse" aria-hidden="true"></span>
            <span data-savebar-text>{{ $errors->any() ? 'Corrigez les champs signalés puis enregistrez.' : 'Modifications non enregistrées' }}</span>
        </span>

        <div class="prm-savebar-actions">
            <a href="{{ url()->current() }}" class="prm-btn-ghost" data-savebar-cancel>Annuler</a>
            <button type="submit" form="{{ $form }}" class="prm-btn-primary" data-savebar-submit>
                <i class="fa-solid fa-floppy-disk"></i>
                {{ $label }}
            </button>
        </div>
    </div>
</div>
