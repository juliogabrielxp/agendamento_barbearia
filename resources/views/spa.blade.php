<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $estabelecimento->nome }}</title>
    <meta name="description" content="Veja os serviços e agende seu horário no {{ $estabelecimento->nome }}.">
    <meta property="og:title" content="{{ $estabelecimento->nome }}">
    <meta property="og:description" content="Veja os serviços e agende seu horário online.">
    @if ($estabelecimento->imagem_url)
        <meta property="og:image" content="{{ $estabelecimento->imagem_url }}">
    @endif
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#171717">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-title" content="{{ $estabelecimento->nome }}">
</head>
<body>
    <div id="app"></div>
</body>
</html>
