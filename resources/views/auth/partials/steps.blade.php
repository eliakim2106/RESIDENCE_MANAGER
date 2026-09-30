{{-- Étapes de l'inscription. Paramètre : $current (1 : profil, 2 : informations, 3 : confirmation de l'email) --}}
@php
    $steps = ['Profil', 'Informations', 'Confirmation'];
@endphp

<ol class="auth-steps" aria-label="Étapes de l’inscription">
    @foreach ($steps as $index => $label)
        @php $number = $index + 1; @endphp
        <li class="{{ $number < $current ? 'is-done' : ($number === $current ? 'is-current' : '') }}"
            @if ($number === $current) aria-current="step" @endif>
            <span class="auth-step-dot">
                @if ($number < $current)
                    <i class="fa-solid fa-check"></i>
                @else
                    {{ $number }}
                @endif
            </span>
            <span class="auth-step-label">{{ $label }}</span>
        </li>
    @endforeach
</ol>
