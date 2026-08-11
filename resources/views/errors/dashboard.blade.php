<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Error</title>
    <style>
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif; }
        .card { max-width: 580px; margin: 12vh auto; padding: 28px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px rgba(15, 23, 42, .08); }
        h1 { margin: 0 0 12px; font-size: 22px; }
        p { margin: 8px 0; line-height: 1.55; }
        .reference { display: inline-block; padding: 6px 10px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; font-family: monospace; font-weight: 700; }
        a { display: inline-block; margin-top: 18px; color: #fff; background: #1d4ed8; padding: 9px 14px; border-radius: 7px; text-decoration: none; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Dashboard could not be loaded</h1>
        <p>{{ $diagnosis }}</p>
        <p>Error reference: <span class="reference">{{ $reference }}</span></p>
        <p>Please send this reference together with the message above to the administrator.</p>
        <a href="{{ url('/dashboard') }}">Try again</a>
    </main>
</body>
</html>
