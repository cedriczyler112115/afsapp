<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '4Ps AFS-IS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  </head>
  <body>
    
    <nav class="navbar navbar-dark bg-dark">
      <div class="container-fluid d-flex align-items-center justify-content-between responsive-container">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu" aria-controls="sideMenu">Menu</button>
          <a class="navbar-brand mb-0 h1" href="{{ route('dashboard') }}">PANTAWID AFS-IS</a>
        </div>
        <div class="d-flex align-items-center gap-3 ms-auto">
          <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="languageMenu" data-bs-toggle="dropdown" aria-expanded="false">
              {{ strtoupper(app()->getLocale()) }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageMenu">
              <li><a class="dropdown-item @if(app()->getLocale()==='en') active @endif" href="{{ route('locale.set', ['locale' => 'en']) }}">English</a></li>
              <li><a class="dropdown-item @if(app()->getLocale()==='fil') active @endif" href="{{ route('locale.set', ['locale' => 'fil']) }}">Filipino</a></li>
            </ul>
          </div>
          <span class="text-white">Welcome, {{ Auth::user()->name }}</span>
          <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button class="btn btn-outline-light" type="submit">Logout</button>
          </form>
        </div>
      </div>
    </nav>

    <x-sidebar />

    <div class="@yield('container_class', 'container-fluid') mt-5 responsive-container">

      @yield('content')
    </div>

    <!-- Loader -->
    <style>
        /* Responsive Container */
        .responsive-container {
            width: 98%; /* Mobile width */
            margin: 0 auto;
        }
        @media (min-width: 768px) {
            .responsive-container {
                width: 90%; /* Desktop width */
            }
        }

        /* Zoom tables by 80% */
        .table {
            zoom: 80%;
        }

        .loading-dots::after {
            content: '';
            animation: loading-dots-animation 1.5s infinite steps(4);
            display: inline-block;
            width: 1.5em; /* Ensure enough width for 3 dots */
            text-align: left;
        }
        @keyframes loading-dots-animation {
            0% { content: ''; }
            25% { content: '.'; }
            50% { content: '..'; }
            75% { content: '...'; }
        }
    </style>
    <div id="loader" class="d-none position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center" style="background: rgba(0, 0, 0, 0.6); z-index: 9999;">
        <div class="spinner-border text-light mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="text-white fw-bold fs-5">
            Loading<span class="loading-dots"></span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function() {
            // Global AJAX setup for CSRF token
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            // Toastr options
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
            };

            @if(session('success'))
                toastr.success("{{ session('success') }}");
            @endif

            @if(session('error'))
                toastr.error("{{ session('error') }}");
            @endif

            @if($errors->has('error'))
                toastr.error("{{ $errors->first('error') }}");
            @endif

            @if($errors->any() && !$errors->has('error'))
                @foreach($errors->all() as $error)
                    toastr.error("{{ $error }}");
                @endforeach
            @endif

            // Global Loader Handler
            $(document).ajaxStart(function() {
                $('#loader').removeClass('d-none');
            }).ajaxStop(function() {
                $('#loader').addClass('d-none');
            });
        });
    </script>
    @stack('scripts')
  </body>
</html>
