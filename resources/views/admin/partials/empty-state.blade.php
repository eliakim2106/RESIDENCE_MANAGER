{{-- Liste vide. Paramètres : $icon, $search (recherche en cours, facultative) --}}
<div class="empty-state">
    <i class="fa-solid {{ $icon }}"></i>
    @if (($search ?? '') !== '' || collect(request()->except('page'))->filter()->isNotEmpty())
        <strong>Aucun résultat</strong>
        <span>Aucun élément ne correspond à ces critères.</span>
        <a href="{{ url()->current() }}" class="btn-secondary">Réinitialiser la liste</a>
    @else
        <strong>Rien pour le moment</strong>
        <span>Les éléments que vous ajouterez apparaîtront ici.</span>
    @endif
</div>
