{{-- Bouton d'export Excel : reprend les filtres de la liste en cours. Paramètres : $route (nom de la route d'export), $params (facultatif) --}}
<a href="{{ route($route, array_merge(request()->except('page'), $params ?? [])) }}" class="btn-secondary btn-export" title="Exporter la liste au format Excel">
    <i class="fa-solid fa-file-excel"></i>
    Exporter
</a>
