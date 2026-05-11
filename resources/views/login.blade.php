<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Stock Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --auth-bg-1: #0b1220;
            --auth-bg-2: #0f1b33;
            --auth-card: rgba(255, 255, 255, 0.92);
            --auth-border: rgba(255, 255, 255, 0.18);
            --auth-text: #0b1220;
            --auth-muted: #5b6477;
            --auth-primary: #2563eb;
            --auth-primary-2: #1d4ed8;
            --auth-danger: #dc2626;
            --auth-radius: 20px;
        }

        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            color: var(--auth-text);
        }

        body.terms-open {
            overflow: hidden;
        }

        .auth {
            min-height: 100vh;
            display: flex;
            align-items: top;
            justify-content: center;
            padding: clamp(18px, 3vw, 36px);
        }

        .auth-card {
            width: min(980px, 100%);
            border-radius: var(--auth-radius);
            background: var(--auth-card);
            border: 1px solid var(--auth-border);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            overflow: hidden;
        }

        .auth-header {
            padding: 28px 28px 18px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 240px;
        }

        .auth-logo {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, var(--auth-primary), rgba(14, 165, 233, 0.92));
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.35);
        }

        .auth-title {
            font-weight: 800;
            font-size: 20px;
            line-height: 1.2;
            margin: 0;
        }

        .auth-subtitle {
            margin: 2px 0 0;
            color: var(--auth-muted);
            font-size: 13px;
        }

        .auth-body {
            padding: 22px 28px 28px;
        }

        .auth-panels {
            position: relative;
            min-height: 520px;
        }

        .auth-panel {
            transition: opacity 260ms ease, transform 260ms ease;
            will-change: transform, opacity;
        }

        .auth-panel--register {
            position: absolute;
            inset: 0;
            opacity: 0;
            transform: translateY(8px);
            pointer-events: none;
        }

        .auth.is-register .auth-panel--login {
            position: absolute;
            inset: 0;
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
        }

        .auth.is-register .auth-panel--register {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            position: relative;
            inset: auto;
        }

        .auth-panel--login {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .auth-card .form-control,
        .auth-card .form-select {
            border-radius: 14px;
            padding: 12px 14px;
            border-color: rgba(15, 23, 42, 0.12);
            background-color: rgba(255, 255, 255, 0.9);
        }

        .auth-card .form-control:focus,
        .auth-card .form-select:focus {
            border-color: rgba(37, 99, 235, 0.55);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.18);
        }

        .auth-card .invalid-feedback {
            font-size: 12px;
        }

        .auth-card .is-invalid {
            border-color: rgba(220, 38, 38, 0.55) !important;
        }

        .auth-card .is-invalid:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 38, 38, 0.16) !important;
        }

        .auth-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-auth {
            border-radius: 14px;
            padding: 12px 14px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .btn-auth-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--auth-primary), var(--auth-primary-2));
            border: 0;
            box-shadow: 0 12px 34px rgba(37, 99, 235, 0.28);
        }

        .btn-auth-primary:hover {
            filter: brightness(0.98);
        }

        .btn-auth-secondary {
            color: var(--auth-text);
            background: rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(15, 23, 42, 0.1);
        }

        .auth-split {
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            margin: 18px 0;
        }

        .register-divider {
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            padding-top: 1rem;
        }
        @media (min-width: 768px) {
            .register-divider {
                border-top: 0;
                border-left: 1px solid rgba(15, 23, 42, 0.08);
                padding-top: 0;
                padding-left: 1.5rem;
            }
        }

        .terms-link {
            font-weight: 700;
            color: var(--auth-primary);
            text-decoration: none;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        .terms-popover {
            position: fixed;
            inset: 0;
            z-index: 1060;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .terms-popover[hidden] {
            display: none;
        }

        .terms-popover__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(2, 6, 23, 0.55);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        .terms-popover__dialog {
            position: relative;
            width: min(860px, 100%);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(15, 23, 42, 0.12);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
            overflow: hidden;
        }

        .terms-popover__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.98);
        }

        .terms-popover__title {
            margin: 0;
            font-weight: 800;
            font-size: 16px;
        }

        .terms-popover__close {
            appearance: none;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: rgba(15, 23, 42, 0.05);
            color: var(--auth-text);
            border-radius: 12px;
            padding: 8px 10px;
            font-weight: 700;
            line-height: 1;
        }

        .terms-popover__close:focus {
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.18);
        }

        .terms-popover__body {
            padding: 18px 20px 20px;
            max-height: min(72vh, 680px);
            overflow: auto;
            color: rgba(15, 23, 42, 0.92);
            font-size: 14px;
            line-height: 1.6;
        }

        .terms-popover__body h6 {
            margin: 0 0 10px;
            font-weight: 800;
        }

        .terms-popover__body p {
            margin: 0 0 12px;
            color: rgba(15, 23, 42, 0.78);
        }
    </style>
</head>
<body>

<main class="auth {{ ($showRegister ?? false) ? 'is-register' : '' }}" id="authRoot">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-brand">
                <div class="auth-logo">
                    <i class="bi bi-box-seam-fill fs-4"></i>
                </div>
                <div>
                    <p class="auth-title">4Ps AFS-IS</p>
                    <p class="auth-subtitle">4Ps Administration and Finance Section Information System</p>
                </div>
            </div>
            <div class="auth-actions">
                <button type="button" class="btn btn-auth btn-auth-secondary" id="authToLogin">Login</button>
                <button type="button" class="btn btn-auth btn-auth-primary" id="authToRegister">Create account</button>
            </div>
        </div>

        <div class="auth-body">
            @if (session('status'))
                <div class="alert alert-success mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="auth-panels">
                <section id="loginWrapper" class="auth-panel auth-panel--login" aria-hidden="{{ ($showRegister ?? false) ? 'true' : 'false' }}">
                    <h3 class="fw-bold mb-1">Welcome back</h3>
                    <p class="text-secondary mb-4">Sign in to continue.</p>

                    @if ($errors->login->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0 list-unstyled">
                                @foreach ($errors->login->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST" autocomplete="off" id="loginForm" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold small mb-1">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control @error('email', 'login') is-invalid @enderror"
                                   id="email"
                                   placeholder="name@example.com"
                                   required
                                   autofocus
                                   value="{{ old('email') }}">
                            @error('email', 'login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold small mb-1">Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control @error('password', 'login') is-invalid @enderror"
                                   id="password"
                                   placeholder="Your password"
                                   required>
                            @error('password', 'login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="invalid-feedback">Password is required.</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                                <label class="form-check-label text-secondary" for="rememberMe">
                                    Keep me logged in
                                </label>
                            </div>
                            <a href="{{ route('password.request') }}" class="text-decoration-none text-secondary small">Forgot password?</a>
                        </div>

                        <button class="btn btn-auth btn-auth-primary w-100" type="submit">Log in</button>

                        <div class="auth-split text-center text-muted" style="position: relative; border: none; margin: 24px 0;">
                            <hr style="border-color: rgba(15,23,42,0.08); margin: 0;">
                            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--auth-card); padding: 0 10px; font-size: 13px;">or</span>
                        </div>

                        <a href="{{ route('auth.google') }}" class="btn btn-auth w-100" style="background: #fff; border: 1px solid rgba(15,23,42,0.12); color: #333; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                            Continue with Google
                        </a>

                        <div class="auth-split"></div>

                        <div class="text-center text-secondary">
                            Don't have an account?
                            <a href="{{ route('register') }}" class="text-decoration-none fw-semibold" id="showRegisterLink">Create one</a>
                        </div>
                    </form>
                </section>

                <section id="registerWrapper" class="auth-panel auth-panel--register" aria-hidden="{{ ($showRegister ?? false) ? 'false' : 'true' }}">
                    <h3 class="fw-bold mb-1">Create your account</h3>
                    <p class="text-secondary mb-4">Set up your profile and security.</p>

                    @if (!empty($registrationLoadError))
                        <div class="alert alert-warning mb-4">
                            {{ $registrationLoadError }}
                        </div>
                    @endif

                    @if ($errors->register->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0 list-unstyled">
                                @foreach ($errors->register->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('register.post') }}" method="POST" autocomplete="off" id="registerForm" novalidate>
                        @csrf

                        <div class="row g-4">
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5 class="mb-0 fw-bold text-dark">User Information</h5>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_name" class="form-label fw-bold small mb-1">Name</label>
                                    <input type="text"
                                           id="reg_name"
                                           name="name"
                                           class="form-control @error('name', 'register') is-invalid @enderror"
                                           required
                                           minlength="5"
                                           maxlength="150"
                                           placeholder="lastname, firstname middlename"
                                           value="{{ old('name') }}">
                                    @error('name', 'register')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback" id="nameClientError"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_email" class="form-label fw-bold small mb-1">Email</label>
                                    <input type="email"
                                           id="reg_email"
                                           name="email"
                                           class="form-control @error('email', 'register') is-invalid @enderror"
                                           required
                                           maxlength="255"
                                           placeholder="name@example.com"
                                           value="{{ old('email') }}">
                                    @error('email', 'register')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Please enter a valid email address.</div>
                                    @enderror
                                    <div class="invalid-feedback" id="regEmailExistsFeedback"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="level_id" class="form-label fw-bold small mb-1">User Level</label>
                                    <select id="level_id"
                                            name="level_id"
                                            class="form-select @error('level_id', 'register') is-invalid @enderror"
                                            required>
                                        <option value="">Select User Level</option>
                                        @foreach(($userLevels ?? collect()) as $level)
                                            <option value="{{ $level->level_id }}" {{ (string) old('level_id') === (string) $level->level_id ? 'selected' : '' }}>
                                               {{ $level->level_id }} - {{  $level->level_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('level_id', 'register')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Please select a user level.</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="division_id" class="form-label fw-bold small mb-1">Division</label>
                                    <select id="division_id"
                                            name="division_id"
                                            class="form-select @error('division_id', 'register') is-invalid @enderror"
                                            required>
                                        <option value="">Select Division</option>
                                        @foreach(($divisions ?? collect()) as $division)
                                            <option value="{{ $division->division_id }}" {{ (string) old('division_id') === (string) $division->division_id ? 'selected' : '' }}>
                                                {{ $division->division_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('division_id', 'register')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Please select a division.</div>
                                    @enderror
                                </div>

                                <div class="mb-2">
                                    <label for="section_id" class="form-label fw-bold small mb-1">Section</label>
                                    <select id="section_id"
                                            name="section_id"
                                            class="form-select @error('section_id', 'register') is-invalid @enderror"
                                            required
                                            disabled>
                                        <option value="">Select Section</option>
                                    </select>
                                    @error('section_id', 'register')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @else
                                        <div class="invalid-feedback">Please select a section.</div>
                                    @enderror
                                    <div class="form-text" id="sectionStatus"></div>
                                </div>

                                <div id="sectionExtras"></div>
                            </div>

                            <div class="col-12 col-md-6 register-divider">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h5 class="mb-0 fw-bold text-dark">Account Security</h5>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_password" class="form-label fw-bold small mb-1">Password</label>
                                    <input type="password"
                                           id="reg_password"
                                           name="password"
                                           class="form-control @error('password', 'register') is-invalid @enderror"
                                           required
                                           minlength="8"
                                           autocomplete="new-password">
                                    @error('password', 'register')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback" id="passwordClientError"></div>
                                    <div class="form-text" id="passwordStrengthText">Minimum 8 characters with uppercase, lowercase, number, and special character.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_password_confirmation" class="form-label fw-bold small mb-1">Confirm Password</label>
                                    <input type="password"
                                           id="reg_password_confirmation"
                                           name="password_confirmation"
                                           class="form-control"
                                           required
                                           minlength="8"
                                           autocomplete="new-password">
                                    <div class="invalid-feedback" id="confirmPasswordClientError"></div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input @error('terms', 'register') is-invalid @enderror"
                                               type="checkbox"
                                               value="1"
                                               id="terms"
                                               name="terms"
                                               {{ old('terms') ? 'checked' : '' }}
                                               required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" class="terms-link" id="termsPolicyLink">Terms and Policy</a>
                                        </label>
                                        @error('terms', 'register')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @else
                                            <div class="invalid-feedback">You must agree before submitting.</div>
                                        @enderror
                                    </div>
                                </div>

                                <button class="btn btn-auth btn-auth-primary w-100" type="submit" id="registerSubmit">Create account</button>

                                <div class="auth-split text-center text-muted" style="position: relative; border: none; margin: 24px 0;">
                                    <hr style="border-color: rgba(15,23,42,0.08); margin: 0;">
                                    <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--auth-card); padding: 0 10px; font-size: 13px;">or</span>
                                </div>

                                <a href="{{ route('auth.google') }}" class="btn btn-auth w-100" style="background: #fff; border: 1px solid rgba(15,23,42,0.12); color: #333; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                    Sign up with Google
                                </a>

                                <div class="text-center text-secondary mt-3">
                                    Already have an account?
                                    <a href="{{ route('login') }}" class="text-decoration-none fw-semibold" id="showLoginLink">Sign in</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>

<div class="terms-popover" id="termsPopover" hidden>
    <div class="terms-popover__backdrop" data-terms-close></div>
    <div class="terms-popover__dialog" role="dialog" aria-modal="true" aria-labelledby="termsPopoverTitle" tabindex="-1">
        <div class="terms-popover__header">
            <h5 class="terms-popover__title" id="termsPopoverTitle">Terms and Policy</h5>
            <button type="button" class="terms-popover__close" data-terms-close>Close</button>
        </div>
        <div class="terms-popover__body">
            <h6>4Ps AFS-IS</h6> 
            <p>The 4PS AFS-IS is developed to support the management, monitoring, and tracking of inventory items and official documents related to the operations of the Pantawid Pamilyang Pilipino Program (4Ps). By accessing or using this system, users acknowledge and agree to comply with the terms and conditions stated herein. Access to the system is strictly limited to authorized personnel such as 4Ps staff, administrative personnel, information technology personnel, and other individuals granted permission by the management. Users are required to use only their assigned credentials and must not allow unauthorized individuals to access the system using their accounts.</p>
            <p>All users are expected to utilize the system solely for official and work-related purposes. They are responsible for ensuring that all information encoded, updated, or maintained within the system is accurate, complete, and truthful. Users must maintain the confidentiality of their login credentials and must not share their usernames or passwords with others. Any suspected unauthorized access, system malfunction, or unusual activity must be immediately reported to the designated system administrator or responsible office.</p>
            <p>All data, records, and documents stored within the system are considered confidential and must be handled with care and responsibility. Users are required to observe and comply with the provisions of the Data Privacy Act of 2012 and other applicable policies governing the protection of personal and sensitive information. Access to information within the system must be limited only to what is necessary for the performance of official duties, and users are strictly prohibited from copying, disclosing, or distributing confidential data to unauthorized individuals.</p>
            <p>Users must not attempt to alter, manipulate, or delete records without proper authority. The system must not be used for personal, illegal, or unauthorized activities. Any attempt to bypass security controls, access restricted modules, or introduce malicious files, viruses, or harmful software into the system is strictly prohibited. Such actions may compromise the integrity, availability, and security of the system and its data.</p>
            <p>System administrators reserve the right to monitor system usage, conduct audits, and maintain logs of system activities to ensure security, compliance, and proper use of the system. The administrators may also perform system maintenance, updates, and improvements which may temporarily limit or restrict system access when necessary to maintain system reliability and security.</p>
            <p>Any violation of these terms may result in the suspension or termination of system access and may subject the user to administrative, disciplinary, or legal actions in accordance with existing policies, rules, and applicable laws. The management also reserves the right to modify or update these terms and conditions as necessary to ensure proper governance and security of the system.</p>
            <p>By continuing to access or use the 4Ps Storage Inventory and 4Ps Document Tracking Information System, the user confirms that they have read, understood, and agreed to comply with the provisions of this Terms and Agreement.</p>
        </div>
    </div>
</div>

<script>
    (function () {
        const showRegister = {{ ($showRegister ?? false) ? 'true' : 'false' }};
        const authRoot = document.getElementById('authRoot');
        const loginWrapper = document.getElementById('loginWrapper');
        const registerWrapper = document.getElementById('registerWrapper');
        const showRegisterLink = document.getElementById('showRegisterLink');
        const showLoginLink = document.getElementById('showLoginLink');
        const toLoginBtn = document.getElementById('authToLogin');
        const toRegisterBtn = document.getElementById('authToRegister');
        const loginUrl = "{{ route('login') }}";
        const registerUrl = "{{ route('register') }}";

        function toggleForms(isRegister) {
            if (!authRoot) return;
            authRoot.classList.toggle('is-register', isRegister);
            if (loginWrapper) loginWrapper.setAttribute('aria-hidden', isRegister ? 'true' : 'false');
            if (registerWrapper) registerWrapper.setAttribute('aria-hidden', isRegister ? 'false' : 'true');
        }

        if (showRegister) {
            toggleForms(true);
        }

        function go(isRegister, url) {
            toggleForms(isRegister);
            try {
                window.history.pushState({ auth: isRegister ? 'register' : 'login' }, '', url);
            } catch (e) {
                window.location.href = url;
            }
        }

        if (showRegisterLink) {
            showRegisterLink.addEventListener('click', function (e) {
                if (e.metaKey || e.ctrlKey) return;
                e.preventDefault();
                go(true, registerUrl);
            });
        }
        if (showLoginLink) {
            showLoginLink.addEventListener('click', function (e) {
                if (e.metaKey || e.ctrlKey) return;
                e.preventDefault();
                go(false, loginUrl);
            });
        }
        if (toLoginBtn) {
            toLoginBtn.addEventListener('click', function () {
                go(false, loginUrl);
            });
        }
        if (toRegisterBtn) {
            toRegisterBtn.addEventListener('click', function () {
                go(true, registerUrl);
            });
        }

        window.addEventListener('popstate', function () {
            const path = String(window.location.pathname || '');
            toggleForms(path.endsWith('/register'));
        });
    })();
</script>

<script>
    (function () {
        const loginForm = document.getElementById('loginForm');
        const divisionSelect = document.getElementById('division_id');
        const sectionSelect = document.getElementById('section_id');
        const sectionStatus = document.getElementById('sectionStatus');
        const sectionExtras = document.getElementById('sectionExtras');
        const registerSubmit = document.getElementById('registerSubmit');
        const registerForm = document.getElementById('registerForm');
        const regEmailInput = document.getElementById('reg_email');
        const regEmailExistsFeedback = document.getElementById('regEmailExistsFeedback');
        const termsPolicyLink = document.getElementById('termsPolicyLink');
        const termsPopover = document.getElementById('termsPopover');

        const nameInput = document.getElementById('reg_name');
        const nameClientError = document.getElementById('nameClientError');
        const passwordInput = document.getElementById('reg_password');
        const passwordClientError = document.getElementById('passwordClientError');
        const passwordStrengthText = document.getElementById('passwordStrengthText');
        const confirmPasswordInput = document.getElementById('reg_password_confirmation');
        const confirmPasswordClientError = document.getElementById('confirmPasswordClientError');

        const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        const sectionsUrl = "{{ route('register.sections') }}";
        const provincesUrl = "{{ route('register.provinces') }}";
        const citiesUrl = "{{ route('register.cities') }}";
        const checkEmailUrl = "{{ route('register.check-email') }}";

        const initialProvinceCode = "{{ old('province_code') }}";
        const initialMunicipalityCode = "{{ old('municipality_code') }}";
        const initialCluster = "{{ old('cluster') }}";
        const regEmailServerError = @json($errors->register->first('email'));

        const provinceServerError = @json($errors->register->first('province_code'));
        const municipalityServerError = @json($errors->register->first('municipality_code'));
        const clusterServerError = @json($errors->register->first('cluster'));

        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                loginForm.classList.add('was-validated');
                if (!loginForm.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }

        if (!registerForm) return;

        function openTermsPopover() {
            if (!termsPopover) return;
            termsPopover.hidden = false;
            document.body.classList.add('terms-open');
            const dialog = termsPopover.querySelector('.terms-popover__dialog');
            if (dialog) dialog.focus();
        }

        function closeTermsPopover() {
            if (!termsPopover) return;
            termsPopover.hidden = true;
            document.body.classList.remove('terms-open');
        }

        if (termsPolicyLink) {
            termsPolicyLink.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openTermsPopover();
            });
        }

        if (termsPopover) {
            termsPopover.addEventListener('click', function (e) {
                const target = e.target;
                if (target && target.matches && target.matches('[data-terms-close]')) {
                    closeTermsPopover();
                }
            });
        }

        window.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && termsPopover && termsPopover.hidden === false) {
                closeTermsPopover();
            }
        });

        if (regEmailInput) {
            regEmailInput.addEventListener('input', function () {
                if (regEmailInput.dataset.emailExists === '1') {
                    delete regEmailInput.dataset.emailExists;
                    regEmailInput.setCustomValidity('');
                    if (regEmailExistsFeedback) {
                        regEmailExistsFeedback.textContent = '';
                    }
                    if (!regEmailServerError) {
                        regEmailInput.classList.remove('is-invalid');
                    }
                }
            });
        }

        let currentSectionsAbortController = null;

        function setRegisterSubmitDisabled(disabled) {
            if (!registerSubmit) return;
            registerSubmit.disabled = disabled;
        }

        function clearSectionExtras() {
            if (!sectionExtras) return;
            sectionExtras.innerHTML = '';
        }

        function buildSelectGroup({ id, name, label, required, errorMessage, placeholder }) {
            if (!sectionExtras) return null;

            const wrapper = document.createElement('div');
            wrapper.className = 'mb-3';

            const labelEl = document.createElement('label');
            labelEl.className = 'form-label fw-bold small mb-1';
            labelEl.setAttribute('for', id);
            labelEl.textContent = label;

            const selectEl = document.createElement('select');
            selectEl.id = id;
            selectEl.name = name;
            selectEl.className = 'form-select';
            if (required) selectEl.required = true;

            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = placeholder || 'Select';
            selectEl.appendChild(defaultOpt);

            const feedbackEl = document.createElement('div');
            feedbackEl.className = 'invalid-feedback';
            feedbackEl.textContent = errorMessage || 'This field is required.';

            wrapper.appendChild(labelEl);
            wrapper.appendChild(selectEl);
            wrapper.appendChild(feedbackEl);
            sectionExtras.appendChild(wrapper);

            return selectEl;
        }

        async function postJson(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body || {}),
            });

            const data = await response.json().catch(function () { return null; });
            if (!response.ok || !data) {
                const message = (data && data.message) ? data.message : 'Request failed.';
                throw new Error(message);
            }
            return data;
        }

        function setEmailExistsInvalid(message) {
            if (!regEmailInput) return;
            regEmailInput.dataset.emailExists = '1';
            regEmailInput.classList.add('is-invalid');
            regEmailInput.setCustomValidity(message || 'Email already exists.');
            if (regEmailExistsFeedback) {
                regEmailExistsFeedback.textContent = message || 'Email already exists.';
            }
        }

        function clearEmailExistsInvalid() {
            if (!regEmailInput) return;
            if (regEmailInput.dataset.emailExists !== '1') return;
            delete regEmailInput.dataset.emailExists;
            regEmailInput.setCustomValidity('');
            if (regEmailExistsFeedback) {
                regEmailExistsFeedback.textContent = '';
            }
            if (!regEmailServerError) {
                regEmailInput.classList.remove('is-invalid');
            }
        }

        let checkEmailTimer = null;
        async function checkEmailExistsNow() {
            if (!regEmailInput) return;

            clearEmailExistsInvalid();

            const email = String(regEmailInput.value || '').trim();
            if (!email) return;
            if (!regEmailInput.checkValidity()) return;

            const data = await postJson(checkEmailUrl, { email });
            if (data && data.success === true && data.exists === true) {
                setEmailExistsInvalid('Email already exists.');
            } else {
                clearEmailExistsInvalid();
            }
        }

        async function loadProvinces(selectEl, selectedProvCode) {
            if (!selectEl) return;
            selectEl.disabled = true;
            selectEl.innerHTML = '';
            const loadingOpt = document.createElement('option');
            loadingOpt.value = '';
            loadingOpt.textContent = 'Loading…';
            selectEl.appendChild(loadingOpt);

            const data = await postJson(provincesUrl, {});
            if (data.success !== true) {
                throw new Error(data.message || 'Unable to load provinces.');
            }

            const provinces = Array.isArray(data.provinces) ? data.provinces : [];
            selectEl.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = 'Select Province';
            selectEl.appendChild(defaultOpt);

            provinces.forEach(function (p) {
                const opt = document.createElement('option');
                opt.value = String(p.prov_code);
                opt.textContent = p.prov_name;
                if (selectedProvCode && String(selectedProvCode) === String(p.prov_code)) {
                    opt.selected = true;
                }
                selectEl.appendChild(opt);
            });

            selectEl.disabled = false;
        }

        async function loadCities(selectEl, provCode, selectedCityCode) {
            if (!selectEl) return;
            if (!provCode) {
                selectEl.disabled = true;
                selectEl.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Select Municipality';
                selectEl.appendChild(opt);
                return;
            }

            selectEl.disabled = true;
            selectEl.innerHTML = '';
            const loadingOpt = document.createElement('option');
            loadingOpt.value = '';
            loadingOpt.textContent = 'Loading…';
            selectEl.appendChild(loadingOpt);

            const data = await postJson(citiesUrl, { prov_code: provCode });
            if (data.success !== true) {
                throw new Error(data.message || 'Unable to load municipalities.');
            }

            const cities = Array.isArray(data.cities) ? data.cities : [];
            selectEl.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = 'Select Municipality';
            selectEl.appendChild(defaultOpt);

            cities.forEach(function (c) {
                const opt = document.createElement('option');
                opt.value = String(c.city_code);
                opt.textContent = c.city_name;
                if (selectedCityCode && String(selectedCityCode) === String(c.city_code)) {
                    opt.selected = true;
                }
                selectEl.appendChild(opt);
            });

            selectEl.disabled = false;
        }

        function setSectionState({ disabled, statusText, options }) {
            if (!sectionSelect) return;
            sectionSelect.disabled = disabled;
            if (Array.isArray(options)) {
                sectionSelect.innerHTML = '';
                options.forEach(function (opt) {
                    const optionEl = document.createElement('option');
                    optionEl.value = opt.value;
                    optionEl.textContent = opt.label;
                    if (opt.selected) optionEl.selected = true;
                    sectionSelect.appendChild(optionEl);
                });
            }
            if (sectionStatus) {
                sectionStatus.textContent = statusText || '';
            }
        }

        function populateSections(sections, selectedId) {
            const opts = [{ value: '', label: 'Select Section', selected: !selectedId }];
            sections.forEach(function (s) {
                opts.push({
                    value: String(s.section_id),
                    label: s.section_name,
                    selected: selectedId && String(selectedId) === String(s.section_id),
                });
            });
            setSectionState({
                disabled: false,
                statusText: sections.length ? '' : 'No sections found for this division.',
                options: opts,
            });
        }

        async function loadSections(divisionId, selectedSectionId) {
            clearSectionExtras();
            if (!divisionId) {
                setSectionState({
                    disabled: true,
                    statusText: 'Select a division to load sections.',
                    options: [{ value: '', label: 'Select Section', selected: true }],
                });
                return;
            }

            if (currentSectionsAbortController) {
                currentSectionsAbortController.abort();
            }
            currentSectionsAbortController = new AbortController();

            setRegisterSubmitDisabled(true);
            setSectionState({
                disabled: true,
                statusText: 'Loading sections…',
                options: [{ value: '', label: 'Loading…', selected: true }],
            });

            try {
                const response = await fetch(sectionsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ division_id: divisionId }),
                    signal: currentSectionsAbortController.signal,
                });

                const data = await response.json().catch(function () { return null; });
                if (!response.ok || !data || data.success !== true) {
                    throw new Error((data && data.message) ? data.message : 'Unable to load sections.');
                }

                populateSections(data.sections || [], selectedSectionId);
                if (sectionSelect) {
                    sectionSelect.dispatchEvent(new Event('change'));
                }
            } catch (err) {
                if (err && err.name === 'AbortError') {
                    return;
                }

                setSectionState({
                    disabled: true,
                    statusText: (err && err.message) ? err.message : 'Unable to load sections due to a system error.',
                    options: [{ value: '', label: 'Select Section', selected: true }],
                });
            } finally {
                setRegisterSubmitDisabled(false);
            }
        }

        function setClientInvalid(inputEl, errorEl, message) {
            if (!inputEl || !errorEl) return;
            errorEl.textContent = message || '';
            if (message) {
                inputEl.classList.add('is-invalid');
            } else {
                inputEl.classList.remove('is-invalid');
            }
        }

        function validateName() {
            if (!nameInput) return true;
            const value = String(nameInput.value || '').trim();
            if (!value) {
                setClientInvalid(nameInput, nameClientError, 'Name is required.');
                return false;
            }

            const basicFormatOk = /^[^,]{2,},\s*[^,]{2,}$/.test(value);
            const allowedCharsOk = !/[^A-Za-zÀ-ÿ\s,.\'-]/.test(value);
            if (!basicFormatOk || !allowedCharsOk) {
                setClientInvalid(nameInput, nameClientError, 'Use format: lastname, firstname middlename.');
                return false;
            }

            setClientInvalid(nameInput, nameClientError, '');
            return true;
        }

        function validatePassword() {
            if (!passwordInput) return true;
            const value = String(passwordInput.value || '');
            const ok = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value);
            if (!ok) {
                setClientInvalid(passwordInput, passwordClientError, 'Password must meet the strength requirements.');
                if (passwordStrengthText) passwordStrengthText.classList.add('text-danger');
                return false;
            }
            setClientInvalid(passwordInput, passwordClientError, '');
            if (passwordStrengthText) passwordStrengthText.classList.remove('text-danger');
            return true;
        }

        function validateConfirmPassword() {
            if (!confirmPasswordInput || !passwordInput) return true;
            const pw = String(passwordInput.value || '');
            const cpw = String(confirmPasswordInput.value || '');
            if (!cpw) {
                setClientInvalid(confirmPasswordInput, confirmPasswordClientError, 'Please confirm your password.');
                return false;
            }
            if (pw !== cpw) {
                setClientInvalid(confirmPasswordInput, confirmPasswordClientError, 'Passwords do not match.');
                return false;
            }
            setClientInvalid(confirmPasswordInput, confirmPasswordClientError, '');
            return true;
        }

        if (divisionSelect) {
            divisionSelect.addEventListener('change', function () {
                const divisionId = divisionSelect.value;
                loadSections(divisionId, null);
            });
        }

        if (regEmailInput) {
            regEmailInput.addEventListener('change', function () {
                if (checkEmailTimer) window.clearTimeout(checkEmailTimer);
                checkEmailTimer = window.setTimeout(function () {
                    checkEmailExistsNow().catch(function () {});
                }, 250);
            });
        }

        if (sectionSelect) {
            sectionSelect.addEventListener('change', async function () {
                clearSectionExtras();
                sectionSelect.classList.remove('is-invalid');

                const sectionId = String(sectionSelect.value || '');
                if (!sectionId) return;

                if (sectionId === '59') {
                    const provinceSelect = buildSelectGroup({
                        id: 'province_code',
                        name: 'province_code',
                        label: 'Province',
                        required: true,
                        errorMessage: provinceServerError || 'Please select a province.',
                        placeholder: 'Select Province',
                    });

                    if (provinceSelect && provinceServerError) {
                        provinceSelect.classList.add('is-invalid');
                    }

                    try {
                        await loadProvinces(provinceSelect, initialProvinceCode || null);
                    } catch (e) {
                        if (provinceSelect) {
                            provinceSelect.disabled = true;
                            provinceSelect.innerHTML = '<option value="">Select Province</option>';
                            provinceSelect.classList.add('is-invalid');
                        }
                    }
                    return;
                }

                if (sectionId === '60') {
                    const clusterSelect = buildSelectGroup({
                        id: 'cluster',
                        name: 'cluster',
                        label: 'Cluster',
                        required: true,
                        errorMessage: clusterServerError || 'Please select a cluster.',
                        placeholder: 'Select Cluster',
                    });

                    if (clusterSelect) {
                        const opt1 = document.createElement('option');
                        opt1.value = '1';
                        opt1.textContent = 'Cluster 1';
                        const opt2 = document.createElement('option');
                        opt2.value = '2';
                        opt2.textContent = 'Cluster 2';
                        clusterSelect.appendChild(opt1);
                        clusterSelect.appendChild(opt2);
                        if (initialCluster) clusterSelect.value = String(initialCluster);
                        if (clusterServerError) clusterSelect.classList.add('is-invalid');
                    }
                    return;
                }

                if (sectionId === '61') {
                    const provinceSelect = buildSelectGroup({
                        id: 'province_code',
                        name: 'province_code',
                        label: 'Province',
                        required: true,
                        errorMessage: provinceServerError || 'Please select a province.',
                        placeholder: 'Select Province',
                    });
                    const municipalitySelect = buildSelectGroup({
                        id: 'municipality_code',
                        name: 'municipality_code',
                        label: 'Municipality',
                        required: true,
                        errorMessage: municipalityServerError || 'Please select a municipality.',
                        placeholder: 'Select Municipality',
                    });

                    if (provinceSelect && provinceServerError) provinceSelect.classList.add('is-invalid');
                    if (municipalitySelect && municipalityServerError) municipalitySelect.classList.add('is-invalid');

                    try {
                        await loadProvinces(provinceSelect, initialProvinceCode || null);
                    } catch (e) {
                        if (provinceSelect) {
                            provinceSelect.disabled = true;
                            provinceSelect.innerHTML = '<option value="">Select Province</option>';
                            provinceSelect.classList.add('is-invalid');
                        }
                        if (municipalitySelect) {
                            municipalitySelect.disabled = true;
                            municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
                        }
                        return;
                    }

                    if (provinceSelect) {
                        provinceSelect.addEventListener('change', function () {
                            loadCities(municipalitySelect, provinceSelect.value, null).catch(function () {});
                        });
                    }

                    await loadCities(municipalitySelect, (provinceSelect ? provinceSelect.value : null), initialMunicipalityCode || null).catch(function () {});
                    return;
                }
            });
        }

        if (nameInput) {
            nameInput.addEventListener('input', validateName);
            nameInput.addEventListener('blur', validateName);
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function () {
                validatePassword();
                validateConfirmPassword();
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', validateConfirmPassword);
        }

        registerForm.addEventListener('submit', function (e) {
            const okName = validateName();
            const okPassword = validatePassword();
            const okConfirm = validateConfirmPassword();

            registerForm.classList.add('was-validated');
            const nativeOk = registerForm.checkValidity();

            const divisionChosen = !!(divisionSelect && divisionSelect.value);
            const sectionReady = !!(sectionSelect && !sectionSelect.disabled);
            const sectionChosen = !!(sectionSelect && sectionSelect.value);
            const okSection = !divisionChosen || (sectionReady && sectionChosen);

            if (sectionSelect) {
                if (divisionChosen && (!sectionReady || !sectionChosen)) {
                    sectionSelect.classList.add('is-invalid');
                } else {
                    sectionSelect.classList.remove('is-invalid');
                }
            }

            if (!nativeOk || !okName || !okPassword || !okConfirm || !okSection) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        const initialDivisionId = "{{ old('division_id') }}";
        const initialSectionId = "{{ old('section_id') }}";

        if (divisionSelect && initialDivisionId) {
            loadSections(initialDivisionId, initialSectionId || null);
        } else {
            setSectionState({
                disabled: true,
                statusText: 'Select a division to load sections.',
                options: [{ value: '', label: 'Select Section', selected: true }],
            });
        }
    })();
</script>

</body>
</html>
