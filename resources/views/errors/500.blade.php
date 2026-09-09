<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.consult_admin') }}</title>
    <link rel="icon" href="{{ asset('images/nembo2.png') }}" type="image/png">
    <style>
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }
        .card {
            width: 100%;
            max-width: 28rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
        img { width: 3.5rem; height: 3.5rem; object-fit: contain; margin-bottom: 1rem; }
        h1 { font-size: 1.15rem; line-height: 1.45; margin: 0; font-weight: 700; }
        p { margin: 0.75rem 0 0; color: #64748b; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/nembo2.png') }}" alt="">
        <h1>{{ __('messages.consult_admin') }}</h1>
    </div>
</body>
</html>
