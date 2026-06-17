<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111827">
    <title>{{ $title ?? 'AcadCheck AI' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #F4F1E8;
            --bg-card: #FBFAF5;
            --bg-input: #FCFCFA;
            --border-soft: #D8D5CB;
            --text-heading: #0F2D24;
            --text-body: #5F6F66;
            --text-muted: #6E7A70;
            --primary: #263238;
            --primary-hover: #111827;
            --green-dark: #263238;
            --action-soft: #E7E1D4;
            --action-soft-text: #4F4638;
            --table-head: #E4E9DF;
            --ready-bg: #DDEEDB;
            --ready-text: #256B45;
            --revision-bg: #F6E2D5;
            --revision-text: #A4572A;
        }

        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--bg-main);
            color: var(--text-body);
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="@yield('page')" class="min-h-screen">
    @yield('content')
</body>
</html>
