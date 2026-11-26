<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Page not found · JobDesk (Demo)</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite('resources/css/app.css')
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-900">
<main class="flex min-h-screen flex-col items-center justify-center px-4 text-center">
    <a href="{{ url('/') }}" class="flex items-center gap-2 text-indigo-700">
        <x-application-logo class="h-10 w-10" />
        <span class="text-xl font-bold text-gray-900">JobDesk</span>
        <x-demo-badge />
    </a>
    <p class="mt-8 text-6xl font-extrabold text-indigo-700">404</p>
    <h1 class="mt-3 text-2xl font-bold">We couldn't find that page</h1>
    <p class="mt-2 max-w-md text-gray-700">The link may be old, or this job or customer belongs to another account.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="rounded-md bg-indigo-700 px-5 py-2.5 font-semibold text-white hover:bg-indigo-800">
            {{ auth()->check() ? 'Go to dashboard' : 'Go to home page' }}
        </a>
        @auth
            <a href="{{ route('jobs.index') }}" class="rounded-md px-5 py-2.5 font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-white">All jobs</a>
        @endauth
    </div>
</main>
</body>
</html>
