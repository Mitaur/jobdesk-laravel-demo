<x-app-layout>
    <x-slot name="title">Jobs</x-slot>
    <x-slot name="header">
        <x-page-header title="Jobs" subtitle="Quotes, scheduled visits and finished work.">
            <x-slot name="actions">
                <x-button-link :href="route('jobs.create')">+ New job</x-button-link>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="GET" action="{{ route('jobs.index') }}" role="search"
          class="grid gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:grid-cols-2 lg:grid-cols-[1fr_180px_180px_auto]">
        <div>
            <label for="q" class="block text-sm font-medium text-gray-800">Search</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] }}" maxlength="100"
                   placeholder="Job title or customer name"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-800">Status</label>
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All statuses</option>
                @foreach (\App\Enums\JobStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="sort" class="block text-sm font-medium text-gray-800">Sort by date</label>
            <select id="sort" name="sort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="desc" @selected($filters['sort'] === 'desc')>Newest first</option>
                <option value="asc" @selected($filters['sort'] === 'asc')>Oldest first</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Apply</button>
            @if ($filters['q'] !== '' || $filters['status'] !== '' || $filters['sort'] !== 'desc')
                <a href="{{ route('jobs.index') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-gray-800 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Reset</a>
            @endif
        </div>
    </form>

    <p class="mt-4 text-sm text-gray-700" aria-live="polite">
        {{ $jobs->total() }} {{ \Illuminate\Support\Str::plural('job', $jobs->total()) }} found
        @if ($filters['q'] !== '') for “{{ $filters['q'] }}”@endif
        @if ($jobs->lastPage() > 1) · page {{ $jobs->currentPage() }} of {{ $jobs->lastPage() }}@endif
    </p>

    @if ($jobs->isEmpty())
        <div class="mt-3 rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200">
            <p class="text-gray-800">No jobs match your search.</p>
            <a href="{{ route('jobs.create') }}" class="mt-3 inline-block font-semibold text-indigo-700 hover:underline">Create a job</a>
        </div>
    @else
        @include('jobs._list', ['jobs' => $jobs, 'showCustomer' => true])
        <div class="mt-4">{{ $jobs->links() }}</div>
    @endif
</x-app-layout>
