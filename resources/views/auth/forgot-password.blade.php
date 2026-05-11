<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - Stock Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --auth-bg-1: #0b1220;
            --auth-card: rgba(255, 255, 255, 0.92);
            --auth-primary: #2563eb;
            --auth-primary-2: #1d4ed8;
            --auth-radius: 20px;
        }
        body { margin: 0; background-color: var(--auth-bg-1); color: #0b1220; font-family: ui-sans-serif, system-ui; }
        .auth { min-height: 100vh; display: flex; align-items: top; justify-content: center; padding: clamp(18px, 3vw, 36px); }
        .auth-card { width: min(480px, 100%); border-radius: var(--auth-radius); background: var(--auth-card); border: 1px solid rgba(255,255,255,0.18); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.45); backdrop-filter: blur(14px); overflow: hidden; padding: 28px; margin-top: 10vh; }
        .form-control { border-radius: 14px; padding: 12px 14px; }
        .btn-auth-primary { color: #fff; background: linear-gradient(135deg, var(--auth-primary), var(--auth-primary-2)); border: 0; border-radius: 14px; padding: 12px 14px; font-weight: bold; width: 100%; box-shadow: 0 12px 34px rgba(37, 99, 235, 0.28); }
    </style>
</head>
<body>
<main class="auth">
    <div class="auth-card">
        <h3 class="fw-bold mb-1">Forgot Password</h3>
        <p class="text-secondary mb-4">Enter your email address to receive an OTP.</p>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 list-unstyled">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('password.otp.send') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="email" class="form-label fw-bold small mb-1">Email</label>
                <input type="email" name="email" class="form-control" id="email" required autofocus value="{{ old('email') }}">
            </div>
            <button class="btn btn-auth-primary" type="submit">Send OTP</button>
        </form>
        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="text-decoration-none text-secondary">Back to Login</a>
        </div>
    </div>
</main>
</body>
</html>
