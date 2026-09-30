<nav class="navbar navbar-expand-lg navbar-dark">

    <div class="container">

        <a class="navbar-brand" href="{{ route('home') }}">

            <img
                src="{{ asset('assets/images/logo/ds_holding_logo.png') }}"
                alt="DS HOLDING"
                class="logo">

        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu">

            <span class="navbar-toggler-icon"></span>

        </button>

        <div
            class="collapse navbar-collapse"
            id="navbarMenu">

            <ul class="navbar-nav ms-auto align-items-center">

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}">
                        Accueil
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('residences.index') }}">
                        Résidences
                    </a>
                </li>

                <li class="nav-item ms-3">

                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-login">
                            Mon espace
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-login">
                            Login
                        </a>
                    @endauth

                </li>

            </ul>

        </div>

    </div>

</nav>
