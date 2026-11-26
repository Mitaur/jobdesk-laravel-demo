<x-app-layout>
    <x-slot name="title">{{ $customer->name }}</x-slot>
    <x-slot name="header">
        <x-page-header :title="$customer->name" subtitle="Customer">
            <x-slot name="actions">
                <x-button-link :href="route('jobs.create', ['customer_id' => $customer->id])">+ New job</x-button-link>
                <x-button-link :href="route('customers.edit', $customer)" variant="secondary">Edit</x-button-link>
                <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                      onsubmit="return confirm('Delete this customer and all of their jobs? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">Delete</button>
                </form>
            </x-slot>
        </x-page-header>
    </x-slot>

    <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200" aria-labelledby="contact">
        <h2 id="contact" class="sr-only">Contact details</h2>
        <dl class="grid gap-4 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-gray-600">Phone</dt>
                <dd class="mt-1">@if ($customer->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->phone) }}" class="text-indigo-700 hover:underline">{{ $customer->phone }}</a>@else — @endif</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-600">Email</dt>
                <dd class="mt-1 break-all">@if ($customer->email)<a href="mailto:{{ $customer->email }}" class="text-indigo-700 hover:underline">{{ $customer->email }}</a>@else — @endif</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-600">Address</dt>
                <dd class="mt-1">{{ $customer->address ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <section class="mt-6" aria-labelledby="customer-jobs">
        <h2 id="customer-jobs" class="text-lg font-semibold">Jobs ({{ $jobs->total() }})</h2>
        @if ($jobs->isEmpty())
            <p class="mt-3 rounded-xl bg-white p-6 text-gray-700 shadow-sm ring-1 ring-gray-200">No jobs for this customer yet.</p>
        @else
            @include('jobs._list', ['jobs' => $jobs, 'showCustomer' => false])
            <div class="mt-4">{{ $jobs->links() }}</div>
        @endif
    </section>

    <p class="mt-6"><a href="{{ route('customers.index') }}" class="text-sm font-semibold text-indigo-700 hover:underline">← Back to customers</a></p>
</x-app-layout>
