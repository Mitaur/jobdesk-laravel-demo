<x-app-layout>
    <x-slot name="title">{{ $job->title }}</x-slot>
    <x-slot name="header">
        <x-page-header :title="$job->title" :subtitle="'Job #'.$job->id">
            <x-slot name="actions">
                <x-button-link :href="route('jobs.edit', $job)">Edit</x-button-link>
                <form method="POST" action="{{ route('jobs.destroy', $job) }}" onsubmit="return confirm('Delete this job? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">Delete</button>
                </form>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-3">
        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 lg:col-span-2" aria-labelledby="details">
            <h2 id="details" class="sr-only">Job details</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-600">Status</dt>
                    <dd class="mt-1"><x-status-badge :status="$job->status" /></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600">Price</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $job->price !== null ? '$'.number_format((float) $job->price, 2) : 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600">Scheduled</dt>
                    <dd class="mt-1">{{ $job->scheduled_at?->format('l, F j, Y · g:i A') ?? 'No date yet' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600">Last updated</dt>
                    <dd class="mt-1">{{ $job->updated_at->diffForHumans() }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-600">Notes</dt>
                    <dd class="mt-1 whitespace-pre-line text-gray-900">{{ $job->notes ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <aside class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200" aria-labelledby="customer">
            <h2 id="customer" class="text-sm font-medium text-gray-600">Customer</h2>
            <p class="mt-1 text-lg font-semibold">
                <a href="{{ route('customers.show', $job->customer) }}" class="text-indigo-700 hover:underline">{{ $job->customer->name }}</a>
            </p>
            <ul class="mt-2 space-y-1 text-sm text-gray-800">
                @if ($job->customer->phone)<li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $job->customer->phone) }}" class="hover:underline">{{ $job->customer->phone }}</a></li>@endif
                @if ($job->customer->email)<li><a href="mailto:{{ $job->customer->email }}" class="hover:underline">{{ $job->customer->email }}</a></li>@endif
                @if ($job->customer->address)<li>{{ $job->customer->address }}</li>@endif
            </ul>
        </aside>
    </div>

    <p class="mt-6"><a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-indigo-700 hover:underline">← Back to all jobs</a></p>
</x-app-layout>
