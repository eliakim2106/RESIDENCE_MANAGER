@if (session('success'))
    <div class="alert-success">
        <i class="fas fa-check-circle"></i>
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert-error">
        <i class="fas fa-circle-exclamation"></i>
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert-error">
        <ul>
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
