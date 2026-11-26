<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="header">
        <x-page-header title="Dashboard" :subtitle="'Hello, '.auth()->user()->name.' – here is your work at a glance.'">
            <x-slot name="actions">
                <x-button-link :href="route('jobs.create')">+ New job</x-button-link>
                <x-button-link :href="route('customers.create')" variant="secondary">+ New customer</x-button-link>
            </x-slot>
        </x-page-header>
    </x-slot>

    <section aria-labelledby="status-counts">
        <h2 id="status-counts" class="sr-only">Jobs per status</h2>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            @foreach (\App\Enums\JobStatus::cases() as $status)
                <a href="{{ route('jobs.index', ['status' => $status->value]) }}"
                   class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-400">
                    <x-status-badge :status="$status" />
                    <p class="mt-3 text-3xl font-bold" data-count="{{ $status->value }}">{{ $counts[$status->value] }}</p>
                    <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::plural('job', $counts[$status->value]) }}</p>
                </a>
            @endforeach
            <a href="{{ route('customers.index') }}" class="col-span-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-400 lg:col-span-1">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">Customers</span>
                <p class="mt-3 text-3xl font-bold">{{ $customerCount }}</p>
                <p class="text-sm text-gray-600">in your list</p>
            </a>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="this-week">
        <div class="flex items-baseline justify-between">
            <h2 id="this-week" class="text-lg font-semibold">This week's jobs</h2>
            <p class="text-sm text-gray-600">{{ $weekStart->format('M j') }} – {{ $weekEnd->format('M j, Y') }}</p>
        </div>

        @if ($thisWeek->isEmpty())
            <p class="mt-3 rounded-xl bg-white p-6 text-gray-700 shadow-sm ring-1 ring-gray-200">No jobs scheduled this week.</p>
        @else
            @include('jobs._list', ['jobs' => $thisWeek, 'showCustomer' => true])
        @endif
    </section>
</x-app-layout>
