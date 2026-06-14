<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#081c15">
    <title>{{ $title ?? 'AcadCheck AI' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="@yield('page')" class="min-h-screen">
    @yield('content')
</body>
</html>
