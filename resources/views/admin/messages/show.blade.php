@extends('layouts.admin')

@php
    use App\Enums\ContactMessageStatus;

    $tone = match ($message->statut) {
        ContactMessageStatus::New => 'info',
        ContactMessageStatus::Read => 'neutral',
        ContactMessageStatus::Answered => 'good',
    };
    $reply = 'mailto:'.$message->email.'?subject='.rawurlencode('Re : '.$message->subject);
@endphp

@section('title', 'Message de '.$message->name)

@section('content')
    <header class="resa-header">
        <a href="{{ route('admin.messages.index') }}" class="resa-back">
            <i class="fa-solid fa-arrow-left"></i>
            Messages
        </a>

        <div class="resa-header-main">
            <div class="resa-header-title">
                <div class="resa-header-line">
                    <h1 class="plain-title">{{ $message->subject }}</h1>
                    <span class="status-pill status-{{ $tone }} status-pill-lg">{{ $message->statut->label() }}</span>
                </div>
                <p>Reçu le {{ $message->created_at->translatedFormat('d F Y à H:i') }} · {{ $message->created_at->diffForHumans() }}</p>
            </div>

            <div class="resa-actionbar">
                <a href="{{ $reply }}" class="btn-primary"><i class="fa-solid fa-reply"></i> Répondre par email</a>
                @if ($message->statut !== ContactMessageStatus::Answered)
                    <form method="POST" action="{{ route('admin.messages.update', $message) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="statut" value="{{ ContactMessageStatus::Answered->value }}">
                        <button type="submit" class="btn-secondary"><i class="fa-solid fa-check-double"></i> Marquer comme répondu</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.messages.update', $message) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="statut" value="{{ ContactMessageStatus::Read->value }}">
                        <button type="submit" class="btn-secondary"><i class="fa-solid fa-rotate-left"></i> Remettre à traiter</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Supprimer définitivement le message de {{ $message->name }} ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-outline-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
                </form>
            </div>
        </div>
    </header>

    @include('partials.flash')

    <div class="resa-layout">
        <div class="resa-main">
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Message</h2>
                    </div>
                </div>
                <div class="contact-message-body">{!! nl2br(e($message->message)) !!}</div>
            </section>
        </div>

        <aside class="resa-aside">
            <section class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <h2>Expéditeur</h2>
                    </div>
                </div>
                <dl class="resa-amounts">
                    <div><dt>Nom</dt><dd>{{ $message->name }}</dd></div>
                    <div><dt>Email</dt><dd><a href="{{ $reply }}">{{ $message->email }}</a></dd></div>
                    @if ($message->phone)
                        <div><dt>Téléphone</dt><dd><a href="tel:{{ $message->internationalPhone() }}">{{ $message->formattedPhone() }}</a></dd></div>
                    @endif
                    @if ($message->property)
                        <div><dt>Établissement</dt><dd><a href="{{ route('admin.etablissements.show', $message->property) }}">{{ $message->property->name }}</a></dd></div>
                    @endif
                </dl>
            </section>
        </aside>
    </div>
@endsection
