<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JobDesk (Demo) – Laravel job &amp; quote tracker for home-service businesses</title>
    <meta name="description" content="JobDesk (Demo): a small Laravel 13 web app to track customers, quotes and scheduled jobs for plumbers and HVAC teams. Login, CRUD, search, REST API with Sanctum. Fake sample data.">
    <meta name="theme-color" content="#4338ca">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="canonical" href="{{ url('/') }}">
    {{-- CSS only: the landing page needs no JavaScript (faster first load). --}}
    @vite('resources/css/app.css')
</head>
<body class="font-sans antialiased bg-white text-gray-900">
<div class="bg-amber-100 px-4 py-1.5 text-center text-xs font-medium text-amber-950">
    This is a <strong>demo project</strong> with fake sample data. Data and accounts reset every night.
</div>

<header class="border-b border-gray-200">
    <nav class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6" aria-label="Main">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-indigo-700">
            <x-application-logo class="h-8 w-8" />
            <span class="text-lg font-bold text-gray-900">JobDesk</span>
            <x-demo-badge />
        </a>
        <div class="flex items-center gap-2 text-sm font-semibold">
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-md bg-indigo-700 px-4 py-2 text-white hover:bg-indigo-800">Open dashboard</a>
            @else
                <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-gray-800 hover:bg-gray-100">Log in</a>
                <a href="{{ route('register') }}" class="rounded-md bg-indigo-700 px-4 py-2 text-white hover:bg-indigo-800">Register</a>
            @endauth
        </div>
    </nav>
</header>

<main>
    <section class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-20">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Laravel 13 · MySQL/MariaDB · REST API</p>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-5xl">Jobs, quotes and customers for a small home-service business</h1>
            <p class="mt-4 text-lg text-gray-700">A plumber or HVAC team can add customers, turn quotes into scheduled jobs, search everything and see this week's work at a glance – on the phone or the desktop.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="rounded-md bg-indigo-700 px-5 py-3 font-semibold text-white shadow-sm hover:bg-indigo-800">Try the demo</a>
                <a href="#api" class="rounded-md px-5 py-3 font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">See the API</a>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 shadow-sm">
            <h2 class="text-lg font-bold">Demo login</h2>
            <p class="mt-1 text-sm text-gray-700">Use this shared account, or register your own (no real emails are sent).</p>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-4 py-3 ring-1 ring-gray-200">
                    <dt class="font-medium text-gray-700">Email</dt>
                    <dd class="font-mono font-semibold">{{ config('jobdesk.demo_email') }}</dd>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-4 py-3 ring-1 ring-gray-200">
                    <dt class="font-medium text-gray-700">Password</dt>
                    <dd class="font-mono font-semibold">{{ config('jobdesk.demo_password') }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-gray-700">Comes with 10 sample customers and 40 jobs. All names, phones and addresses are fake.</p>
        </div>
    </section>

    <section class="border-t border-gray-200 bg-gray-50" aria-labelledby="features">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <h2 id="features" class="text-2xl font-bold">What the demo shows</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Login & accounts', 'Register, login, logout, password reset and profile edit (Laravel Breeze).'],
                    ['Customers & jobs CRUD', 'MySQL migrations with relationships: a customer has many jobs.'],
                    ['Search, filter, pagination', 'Search by job title or customer name, filter by status, sort by date, 10 per page.'],
                    ['Private data', 'Policies: every user sees and edits only their own customers and jobs.'],
                    ['REST API + tokens', 'Laravel Sanctum tokens, Form Request validation, clean JSON errors.'],
                    ['Scheduler & logs', 'A daily reminder command run by one cron line; daily log files kept 7 days.'],
                ] as [$title, $text])
                    <li class="rounded-xl bg-white p-5 ring-1 ring-gray-200">
                        <h3 class="font-semibold">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-gray-700">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section id="api" class="mx-auto max-w-6xl px-4 py-12 sm:px-6" aria-labelledby="api-title">
        <h2 id="api-title" class="text-2xl font-bold">API in two steps</h2>
        <p class="mt-2 text-gray-700">Get a token, then call the jobs endpoints with it.</p>
        <pre class="mt-4 overflow-x-auto whitespace-pre-wrap break-words rounded-xl bg-gray-900 p-4 text-xs leading-relaxed text-gray-100 [overflow-wrap:anywhere] sm:whitespace-pre sm:text-sm"><code># 1) Get a token
curl -X POST \
  {{ url('/api/tokens') }} \
  -H "Accept: application/json" \
  -d email={{ config('jobdesk.demo_email') }} \
  -d password={{ config('jobdesk.demo_password') }} \
  -d device_name=curl

# 2) List scheduled jobs (paginated)
curl -G \
  {{ url('/api/jobs') }} \
  -d status=scheduled -d search=repair \
  -H "Accept: application/json" \
  -H "Authorization: Bearer &lt;TOKEN&gt;"</code></pre>
        <p class="mt-3 text-sm text-gray-700">Endpoints: <code class="font-mono">GET /api/jobs</code>, <code class="font-mono">GET /api/jobs/{id}</code>, <code class="font-mono">POST /api/jobs</code>, <code class="font-mono">PATCH /api/jobs/{id}/status</code>.</p>
    </section>
</main>

<footer class="border-t border-gray-200">
    <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-6 text-sm text-gray-700 sm:flex-row sm:justify-between sm:px-6">
        <p>JobDesk (Demo) – a portfolio project, not a real business.</p>
        <p>Built by <a href="https://mitaur.com" class="font-semibold text-indigo-700 underline hover:text-indigo-900">Mitaur</a> with Laravel {{ \Illuminate\Foundation\Application::VERSION }}</p>
    </div>
</footer>
</body>
</html>
