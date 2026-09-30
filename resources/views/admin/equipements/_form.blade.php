@php
    use App\Enums\EquipmentCategory;

    $icon = old('icon', $equipement->exists ? $equipement->fa_icon : '');
    $category = old('category', $equipement->category?->value ?? EquipmentCategory::General->value);
    $status = old('status', $equipement->exists && ! $equipement->is_active ? 'inactif' : 'actif');
@endphp

<div class="form-grid">

    <div class="form-group">
        <label for="equipement-name">Nom de l'équipement</label>
        <input type="text" id="equipement-name" name="nom" value="{{ old('nom', $equipement->name) }}" placeholder="Ex : Cuisine équipée" required>
    </div>

    <div class="form-group">
        <label for="equipement-icon">Icône Font Awesome</label>
        <input type="text" id="equipement-icon" name="icon" value="{{ $icon }}" placeholder="Proposée d'après le nom, ex. fa-wifi">
    </div>

    <div class="form-group">
        <label>Aperçu</label>
        <div class="icon-preview-card">
            <i id="equipment-icon-preview" class="fa-solid {{ $icon ?: 'fa-question' }}"></i>
        </div>
    </div>

    <div class="form-group">
        <label for="equipement-category">Catégorie</label>
        <select id="equipement-category" name="category">
            @foreach (EquipmentCategory::options() as $value => $label)
                <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="equipement-status">Statut</label>
        <select id="equipement-status" name="status">
            <option value="actif" @selected($status === 'actif')>Actif</option>
            <option value="inactif" @selected($status === 'inactif')>Inactif</option>
        </select>
    </div>

    <div class="form-group">
        <label for="equipement-popular">Mise en avant</label>
        <label class="d-flex align-items-center gap-2 fw-normal">
            <input type="hidden" name="is_popular" value="0">
            <input type="checkbox" id="equipement-popular" name="is_popular" value="1" class="form-check-input mt-0"
                @checked(old('is_popular', $equipement->is_popular))>
            Équipement populaire (affiché en priorité)
        </label>
    </div>

</div>
