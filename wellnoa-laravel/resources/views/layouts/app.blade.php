<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wellnoa</title>
    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .fade-out {
            opacity: 1;
            transition: opacity 1s ease-out;
        }
        .fade-out.hide {
            opacity: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">Wellnoa</a>
        </div>
    </nav>

    <main class="container" style="margin: 20px;">
        {{-- フラッシュメッセージ --}}
        @foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info'] as $key => $type)
            @if (session($key))
                <div id="flash-message-{{ $key }}" class="alert alert-{{ $type }} fade-out">
                    {{ session($key) }}
                </div>
            @endif
        @endforeach

        {{-- 各ページの中身 --}}
        @yield('content')
    </main>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 3秒後に自動フェードアウト
        window.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[id^="flash-message"]').forEach(flash => {
                setTimeout(() => {
                    flash.classList.add('hide');
                    setTimeout(() => flash.remove(), 1000);
                }, 3000);
            });
        });
    </script>
</body>
</html>