<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="JobDesk (Demo) – a small Laravel job and quote tracker for home-service businesses.">
        <meta name="theme-color" content="#4338ca">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'JobDesk (Demo)') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        {{-- Auth pages need no JavaScript (Alpine is only used inside the app) → faster login page. --}}
        @vite('resources/css/app.css')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <main class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-8 pb-10 sm:pt-0 bg-gray-100">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-indigo-700">
                <x-application-logo class="h-10 w-10" />
                <span class="text-xl font-bold text-gray-900">JobDesk</span>
                <x-demo-badge />
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-5 bg-white shadow-md overflow-hidden rounded-lg">
                {{ $slot }}
            </div>

            @if (config('jobdesk.demo_mode'))
                <p class="mt-4 max-w-md text-center text-sm text-gray-700">
                    Demo login: <strong>{{ config('jobdesk.demo_email') }}</strong> / <strong>{{ config('jobdesk.demo_password') }}</strong><br>
                    Emails are not really sent (password-reset links go to the log).
                </p>
            @endif
        </main>
    </body>
</html>
