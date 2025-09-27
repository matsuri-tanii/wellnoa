<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wellnoa</title>
    {{-- Bootstrap CSS --}}

</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">Wellnoa</a>
        </div>
    </nav>

    <main class="container" style="margin: 20px;">
        {{-- 各ページの中身がここに差し込まれる --}}

        {{-- フラッシュメッセージ --}}
        @if (session('success'))
            <div style="padding:10px; margin-bottom:15px; background:#e6ffed; border:1px solid #b2f2bb; color:#2b8a3e;">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>