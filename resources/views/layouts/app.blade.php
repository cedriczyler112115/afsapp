<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '4Ps AFS-IS')</title>
    <!-- Google Fonts: Instrument Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .jconfirm .jconfirm-box {
            font-family: 'Instrument Sans', Arial, sans-serif !important;
            color: #0f172a !important;
            background: #fff !important;
            padding: 12px 14px 10px !important;
            border-radius: 10px !important;
        }
        .jconfirm .jconfirm-box-container {
            width: min(92vw, 390px) !important;
            max-width: 390px !important;
            margin-right: auto !important;
            margin-left: auto !important;
            float: none !important;
        }
        .jconfirm .jconfirm-title,
        .jconfirm .jconfirm-content,
        .jconfirm .jconfirm-content * {
            color: #0f172a !important;
            opacity: 1 !important;
        }
        .jconfirm .jconfirm-title-c {
            padding-bottom: 6px !important;
        }
        .jconfirm .jconfirm-title {
            font-size: .95rem !important;
            line-height: 1.25 !important;
            font-weight: 700 !important;
        }
        .jconfirm .jconfirm-icon-c {
            font-size: .95rem !important;
            margin-right: 6px !important;
        }
        .jconfirm .jconfirm-content-pane {
            margin-bottom: 9px !important;
        }
        .jconfirm .jconfirm-content {
            font-size: .79rem !important;
            line-height: 1.35 !important;
        }
        .jconfirm .jconfirm-buttons {
            padding-bottom: 0 !important;
        }
        .jconfirm .jconfirm-buttons button {
            font-family: 'Instrument Sans', Arial, sans-serif !important;
            min-height: 28px !important;
            margin: 0 0 0 6px !important;
            padding: 4px 11px !important;
            border-radius: 6px !important;
            font-size: .76rem !important;
            font-weight: 600 !important;
            opacity: 1 !important;
        }
        html.dark .jconfirm .jconfirm-box {
            color: #f8fafc !important;
            background: #1e293b !important;
        }
        html.dark .jconfirm .jconfirm-title,
        html.dark .jconfirm .jconfirm-content,
        html.dark .jconfirm .jconfirm-content * {
            color: #f8fafc !important;
        }
        .inline-native-validation {
            display: block;
            width: 100%;
            margin-top: .25rem;
            color: #dc3545;
            font-size: .78rem;
            line-height: 1.25;
        }
        .alert {
            padding: .42rem .65rem !important;
            margin-bottom: .5rem;
            border-radius: .45rem !important;
            font-size: .78rem;
            line-height: 1.3;
        }
        .alert .btn-close {
            padding: .58rem !important;
            background-size: .55rem !important;
        }
    </style>

    <!-- Theme Initialization block to prevent FOUC (Flash of Unstyled Content) -->
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Compile Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-slate-50 text-slate-900 flex flex-col">
    <!-- Page Wrapper -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <x-sidebar />

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 h-12 flex items-center justify-between px-3 sticky top-0 z-30">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-secondary p-1 px-2 border-0" type="button" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <span class="fw-semibold text-slate-800">PANTAWID AFS-IS</span>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Switcher -->
                    <button class="btn btn-outline-secondary p-1 px-2 border-0" type="button" id="themeToggleBtn" aria-label="Toggle Theme">
                        <i class="bi bi-sun d-none" id="themeSunIcon"></i>
                        <i class="bi bi-moon" id="themeMoonIcon"></i>
                    </button>

                    <!-- User menu -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar bg-slate-900 text-white rounded-full flex items-center justify-center font-semibold text-xs" style="width: 28px; height: 28px;">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="text-xs fw-medium text-slate-600 d-none d-md-inline">{{ Auth::user()->name }}</span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm px-2 border-0" type="submit" title="Logout">
                            <i class="bi bi-box-arrow-right fs-6"></i>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Scrollable content -->
            <main class="flex-1 overflow-y-auto p-3 md:p-4">
                <div class="@yield('container_class', 'container-fluid')">
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-slate-200 py-2 px-4 text-center md:text-start">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                    <div>
                        <span class="small fw-semibold text-slate-800">PANTAWID AFS-IS</span>
                        <span class="small text-slate-500 ms-1 d-none d-md-inline">| Admin Facilitation Section - Information System</span>
                    </div>
                    <span class="small text-slate-500 mt-1 mt-md-0">
                        &copy; {{ date('Y') }} DSWD. All rights reserved.
                    </span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Loader -->
    <style>
        /* Sidebar Backdrop */
        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 1040;
            display: none;
        }
        .sidebar-backdrop.show {
            display: block;
        }

        .loading-dots::after {
            content: '';
            animation: loading-dots-animation 1.5s infinite steps(4);
            display: inline-block;
            width: 1.5em;
            text-align: left;
        }

        @keyframes loading-dots-animation {
            0% { content: ''; }
            25% { content: '.'; }
            50% { content: '..'; }
            75% { content: '...'; }
        }

        .custom-loader-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loader-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            z-index: 2;
        }

        .loader-spinner {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid #ffffff;
            border-radius: 50%;
            animation: loader-spin 1s linear infinite;
            z-index: 1;
        }

        @keyframes loader-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <div id="loader"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center"
        style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999;">
        <div class="custom-loader-wrapper mb-3">
            <img src="{{ asset('storage/4ps-logo.png') }}" alt="4Ps Logo" class="loader-logo">
            <div class="loader-spinner"></div>
        </div>
        <div class="text-white fw-semibold small">
            Loading<span class="loading-dots"></span>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        if (window.jconfirm && window.jconfirm.defaults) {
            Object.assign(window.jconfirm.defaults, {
                draggable: true,
                dragWindowBorder: false,
                animation: 'scale',
                closeAnimation: 'scale',
            });
        }

        window.movableConfirm = function (options) {
            const settings = typeof options === 'string' ? { content: options } : (options || {});
            return new Promise(function (resolve) {
                $.confirm({
                    title: settings.title || 'Confirm Action',
                    content: settings.content || 'Are you sure you want to continue?',
                    icon: settings.icon || 'bi bi-question-circle',
                    type: settings.type || 'blue',
                    draggable: true,
                    dragWindowBorder: false,
                    escapeKey: 'cancel',
                    backgroundDismiss: false,
                    buttons: {
                        cancel: {
                            text: settings.cancelText || 'Cancel',
                            btnClass: 'btn-outline-secondary',
                            action: function () { resolve(false); },
                        },
                        confirm: {
                            text: settings.confirmText || 'Confirm',
                            btnClass: settings.confirmClass || 'btn-primary',
                            keys: ['enter'],
                            action: function () { resolve(true); },
                        },
                    },
                    onClose: function () { resolve(false); },
                });
            });
        };

        (function installInlineValidation() {
            const messages = new WeakMap();

            function clearFieldError(field) {
                field.classList.remove('is-invalid');
                const message = messages.get(field);
                if (message) {
                    message.remove();
                    messages.delete(field);
                }
            }

            function showFieldError(field) {
                clearFieldError(field);
                field.classList.add('is-invalid');

                const message = document.createElement('div');
                message.className = 'invalid-feedback inline-native-validation';
                message.setAttribute('role', 'alert');
                message.textContent = field.validationMessage || 'Please check this field.';

                let anchor = field;
                if (field.type === 'radio' || field.type === 'checkbox') {
                    anchor = field.closest('.form-check') || field;
                } else {
                    anchor = field.closest('.input-group') || field;
                }
                anchor.insertAdjacentElement('afterend', message);
                messages.set(field, message);
            }

            document.addEventListener('invalid', function (event) {
                const field = event.target;
                if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;
                event.preventDefault();
                showFieldError(field);
            }, true);

            ['input', 'change'].forEach(function (eventName) {
                document.addEventListener(eventName, function (event) {
                    const field = event.target;
                    if (!messages.has(field)) return;
                    if (field.validity.valid) clearFieldError(field);
                    else showFieldError(field);
                }, true);
            });

            document.addEventListener('submit', function (event) {
                const firstInvalid = event.target.querySelector(':invalid');
                if (firstInvalid) {
                    event.preventDefault();
                    window.setTimeout(function () {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus({ preventScroll: true });
                    }, 0);
                }
            }, true);
        })();

        $(document).on('submit', 'form[data-confirm-message]', function (event) {
            const form = this;
            if ($(form).data('confirmed-submit') === true) {
                $(form).removeData('confirmed-submit');
                return;
            }
            event.preventDefault();
            window.movableConfirm({
                title: form.dataset.confirmTitle || 'Confirm Action',
                content: form.dataset.confirmMessage,
                type: form.dataset.confirmType || 'red',
                confirmText: form.dataset.confirmText || 'Confirm',
                confirmClass: form.dataset.confirmClass || 'btn-danger',
            }).then(function (confirmed) {
                if (confirmed) {
                    $(form).data('confirmed-submit', true);
                    form.requestSubmit();
                }
            });
        });

        $(document).ready(function () {
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

            // Global Loader Handler (jQuery AJAX & Page Transitions)
            let activeRequests = 0;

            if (document.readyState === 'complete') {
                if (activeRequests === 0) $('#loader').addClass('d-none');
            } else {
                $(window).on('load', function () {
                    if (activeRequests === 0) $('#loader').addClass('d-none');
                });
            }

            $(window).on('beforeunload', function () {
                $('#loader').removeClass('d-none');
            });

            window.addEventListener('pageshow', function (event) {
                if (event.persisted && activeRequests === 0) {
                    $('#loader').addClass('d-none');
                }
            });

            function showLoader() {
                if (activeRequests === 0) {
                    $('#loader').removeClass('d-none');
                }
                activeRequests++;
            }

            function hideLoader() {
                activeRequests--;
                if (activeRequests <= 0) {
                    activeRequests = 0;
                    $('#loader').addClass('d-none');
                }
            }

            $(document).ajaxStart(function () {
                showLoader();
            }).ajaxStop(function () {
                hideLoader();
            });

            // Global Loader Handler (Fetch API)
            const originalFetch = window.fetch;
            window.fetch = function () {
                showLoader();
                return originalFetch.apply(this, arguments).then(function (response) {
                    hideLoader();
                    return response;
                }).catch(function (error) {
                    hideLoader();
                    throw error;
                });
            };

            // Sidebar Toggle Functionality (Responsive)
            const sidebar = document.getElementById('sidebar-container');
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const closeBtn = document.getElementById('sidebarCloseBtn');
            
            // Create sidebar backdrop element
            const backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);

            function toggleSidebar() {
                if (window.innerWidth < 768) {
                    sidebar.classList.toggle('mobile-open');
                    if (sidebar.classList.contains('mobile-open')) {
                        backdrop.classList.add('show');
                    } else {
                        backdrop.classList.remove('show');
                    }
                } else {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                }
            }
            
            if (sidebar && toggleBtn) {
                // Read preference (desktop only)
                if (window.innerWidth >= 768) {
                    const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                    if (isCollapsed) {
                        sidebar.classList.add('collapsed');
                    }
                }

                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleSidebar();
                });

                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        sidebar.classList.remove('mobile-open');
                        backdrop.classList.remove('show');
                    });
                }

                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    backdrop.classList.remove('show');
                });
            }

            // Dark Mode Theme Switcher Functionality
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const themeSunIcon = document.getElementById('themeSunIcon');
            const themeMoonIcon = document.getElementById('themeMoonIcon');

            function updateThemeUI(theme) {
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    themeSunIcon.classList.remove('d-none');
                    themeMoonIcon.classList.add('d-none');
                } else {
                    document.documentElement.classList.remove('dark');
                    themeSunIcon.classList.add('d-none');
                    themeMoonIcon.classList.remove('d-none');
                }
            }

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function () {
                    const isDark = document.documentElement.classList.contains('dark');
                    const nextTheme = isDark ? 'light' : 'dark';
                    localStorage.setItem('theme', nextTheme);
                    updateThemeUI(nextTheme);
                });
                
                // Initialize switcher UI
                updateThemeUI(localStorage.getItem('theme') || 'light');
            }
        });
    </script>
    @stack('scripts')
</body>

</html>
