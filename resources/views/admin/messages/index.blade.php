@extends('layouts.admin')

@php
    use App\Enums\ContactMessageStatus;

    $tone = fn (ContactMessageStatus $status): string => match ($status) {
        ContactMessageStatus::New => 'info',
        ContactMessageStatus::Read => 'neutral',
        ContactMessageStatus::Answered => 'good',
    };
@endphp

@section('title', 'Messages')

@section('content')
    <div class="admin-page-header">
        <div class="admin-page-heading">
            <span class="admin-page-icon"><i class="fa-solid fa-envelope"></i></span>
            <div>
                <h1>Messages</h1>
                <p>Les messages envoyés par le formulaire de contact du site.</p>
            </div>
        </div>

        <div class="admin-page-actions">
            @include('admin.partials.export-button', ['route' => 'admin.messages.export'])
        </div>
    </div>

    @include('partials.flash')

    {{-- ========== Synthèse ========== --}}
    <div class="resa-today">
        <div class="resa-today-card tone-info">
            <span class="resa-today-icon"><i class="fa-solid fa-envelope"></i></span>
            <span class="resa-today-text">
                <strong>{{ $counts['nouveaux'] }}</strong>
                <span>À lire</span>
            </span>
        </div>
        <div class="resa-today-card tone-warning">
            <span class="resa-today-icon"><i class="fa-solid fa-reply"></i></span>
            <span class="resa-today-text">
                <strong>{{ $counts['lus'] }}</strong>
                <span>Lu{{ $counts['lus'] > 1 ? 's' : '' }}, sans réponse</span>
            </span>
        </div>
        <div class="resa-today-card tone-good">
            <span class="resa-today-icon"><i class="fa-solid fa-check-double"></i></span>
            <span class="resa-today-text">
                <strong>{{ $counts['repondus'] }}</strong>
                <span>Répondu{{ $counts['repondus'] > 1 ? 's' : '' }}</span>
            </span>
        </div>
        <a href="{{ route('admin.messages.subscribers') }}" class="resa-today-card tone-gold" title="Télécharger la liste des abonnés (Excel)">
            <span class="resa-today-icon"><i class="fa-solid fa-paper-plane"></i></span>
            <span class="resa-today-text">
                <strong>{{ $subscribers }}</strong>
                <span>Abonné{{ $subscribers > 1 ? 's' : '' }} à la lettre · exporter</span>
            </span>
        </a>
    </div>

    {{-- ========== Filtres ========== --}}
    <section class="resa-filters">
        @include('admin.partials.list-toolbar', ['counts' => $counts, 'statut' => $statut, 'search' => $search, 'tabs' => $tabs, 'placeholder' => 'Rechercher un nom, un email, un sujet…'])
    </section>

    {{-- ========== Liste ========== --}}
    <div class="table-card resa-table-card">
        <table class="custom-table resa-table">
            <thead>
                <tr>
                    <th>Expéditeur</th>
                    <th>Message</th>
                    <th>Reçu</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($messages as $message)
                    <tr class="{{ $message->statut === ContactMessageStatus::New ? 'is-highlight-row' : '' }}">
                        <td class="cell-main">
                            <div class="cell-entity">
                                <span class="cell-icon"><i class="fa-solid {{ $message->statut === ContactMessageStatus::New ? 'fa-envelope' : 'fa-envelope-open' }}"></i></span>
                                <span class="cell-entity-text">
                                    <a href="{{ route('admin.messages.show', $message) }}" class="cell-title-link"><strong>{{ $message->name }}</strong></a>
                                    <small>{{ $message->email }}</small>
                                </span>
                            </div>
                        </td>
                        <td>
                            <strong>{{ Str::limit($message->subject, 50) }}</strong>
                            <small class="cell-muted d-block">{{ Str::limit($message->message, 80) }}</small>
                        </td>
                        <td>
                            {{ $message->created_at->format('d/m/Y') }}
                            <small class="cell-muted d-block">{{ $message->created_at->diffForHumans() }}</small>
                        </td>
                        <td><span class="status-pill status-{{ $tone($message->statut) }}">{{ $message->statut->label() }}</span></td>
                        <td>
                            <span class="invoice-actions">
                                <a href="{{ route('admin.messages.show', $message) }}" class="action-btn view" title="Lire" aria-label="Lire le message de {{ $message->name }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re : '.$message->subject) }}" class="action-btn edit" title="Répondre par email" aria-label="Répondre à {{ $message->name }}">
                                    <i class="fa-solid fa-reply"></i>
                                </a>
                                <button type="button" class="action-btn delete delete-btn" title="Supprimer" aria-label="Supprimer le message de {{ $message->name }}"
                                    data-url="{{ route('admin.messages.destroy', $message) }}"
                                    data-name="le message de {{ $message->name }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">
                            @include('admin.partials.empty-state', ['icon' => 'fa-envelope-open', 'search' => $search])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.pagination', ['paginator' => $messages, 'label' => 'message(s)'])
@endsection
