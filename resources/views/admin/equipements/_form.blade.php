{{-- Formulaire commun création / modification d'un équipement. L'icône est proposée d'après le nom (equipement.js). --}}
@php
    use App\Enums\EquipmentCategory;

    $icon = old('icon', $equipement->exists ? $equipement->fa_icon : '');
    $category = old('category', $equipement->category?->value ?? EquipmentCategory::General->value);
    $active = old('statut', $equipement->exists && ! $equipement->isActive() ? 'inactif' : 'actif') === 'actif';
    $popular = (bool) old('is_popular', $equipement->is_popular);
@endphp

<div class="form-layout">

    <section class="form-section">
        <header class="form-section-header">
            <h2>Informations</h2>
            <p>Le nom et l'icône affichés sur les fiches des établissements et des unités.</p>
        </header>

        <div class="form-grid">
            <div class="form-group">
                <label for="equipement-name">Nom de l'équipement <span class="required">*</span></label>
                <input type="text" id="equipement-name" name="nom" value="{{ old('nom', $equipement->name) }}" placeholder="Ex : Cuisine équipée" required data-preview-source="name">
                @error('nom') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="equipement-category">Catégorie <span class="required">*</span></label>
                <select id="equipement-category" name="category" data-preview-source="category">
                    @foreach (EquipmentCategory::options() as $value => $label)
                        <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="form-group full-width">
                <label for="equipement-icon">Icône Font Awesome <span class="required">*</span></label>
                <div class="input-affix">
                    <span><i class="fa-solid fa-icons"></i></span>
                    <input type="text" id="equipement-icon" name="icon" value="{{ $icon }}" placeholder="fa-wifi">
                </div>
                <p class="field-help">Proposée d'après le nom. Vous pouvez saisir tout nom d'icône Font Awesome, par exemple <code>fa-mug-hot</code>.</p>
                @error('icon') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <aside class="form-aside">
        <section class="form-section">
            <header class="form-section-header">
                <h2>Aperçu</h2>
                <p>Tel qu'il apparaîtra sur les fiches.</p>
            </header>

            <div class="preview-card">
                <span class="preview-icon"><i id="equipment-icon-preview" class="fa-solid {{ $icon ?: 'fa-question' }}"></i></span>
                <div class="preview-body">
                    <strong>
                        <span data-preview="name" data-preview-empty="Nom de l'équipement">{{ old('nom', $equipement->name) ?: "Nom de l'équipement" }}</span>
                        <i class="fa-solid fa-star preview-star" data-preview-popular @if (! $popular) hidden @endif></i>
                    </strong>
                    <p data-preview="category">{{ EquipmentCategory::options()[$category] ?? '' }}</p>
                    <span class="{{ $active ? 'badge-success' : 'badge-neutral' }}" data-preview-status>{{ $active ? 'Actif' : 'Inactif' }}</span>
                </div>
            </div>
        </section>

        <section class="form-section">
            <header class="form-section-header">
                <h2>Publication</h2>
            </header>

            <input type="hidden" name="statut" value="inactif">
            <label class="switch-field">
                <input type="checkbox" name="statut" value="actif" @checked($active) data-status-toggle>
                <span class="switch-ui" aria-hidden="true"></span>
                <span class="switch-text">
                    <strong>Équipement actif</strong>
                    <small>Sélectionnable sur les établissements et les unités.</small>
                </span>
            </label>

            <input type="hidden" name="is_popular" value="0">
            <label class="switch-field">
                <input type="checkbox" name="is_popular" value="1" @checked($popular) data-popular-toggle>
                <span class="switch-ui" aria-hidden="true"></span>
                <span class="switch-text">
                    <strong>Mettre en avant</strong>
                    <small>Affiché en priorité et proposé comme filtre sur la page des résidences.</small>
                </span>
            </label>
        </section>
    </aside>

</div>
