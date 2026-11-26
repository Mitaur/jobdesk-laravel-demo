<x-app-layout>
    <x-slot name="title">Customers</x-slot>
    <x-slot name="header">
        <x-page-header title="Customers" subtitle="People and businesses you work for.">
            <x-slot name="actions">
                <x-button-link :href="route('customers.create')">+ New customer</x-button-link>
            </x-slot>
        </x-page-header>
    </x-slot>

    <form method="GET" action="{{ route('customers.index') }}" role="search" class="flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="q" class="block text-sm font-medium text-gray-800">Search customers</label>
            <input id="q" name="q" type="search" value="{{ $term }}" maxlength="100" placeholder="Name, phone or email"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Search</button>
            @if ($term !== '')
                <a href="{{ route('customers.index') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-gray-800 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Reset</a>
            @endif
        </div>
    </form>

    <p class="mt-4 text-sm text-gray-700" aria-live="polite">{{ $customers->total() }} {{ \Illuminate\Support\Str::plural('customer', $customers->total()) }}@if ($customers->lastPage() > 1) · page {{ $customers->currentPage() }} of {{ $customers->lastPage() }}@endif</p>

    @if ($customers->isEmpty())
        <div class="mt-3 rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200">
            <p class="text-gray-800">No customers found.</p>
            <a href="{{ route('customers.create') }}" class="mt-3 inline-block font-semibold text-indigo-700 hover:underline">Add a customer</a>
        </div>
    @else
        {{-- Phone: cards --}}
        <ul class="mt-3 space-y-3 md:hidden">
            @foreach ($customers as $customer)
                <li>
                    <a href="{{ route('customers.show', $customer) }}" class="block rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-400">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold">{{ $customer->name }}</p>
                            <span class="shrink-0 text-sm text-gray-700">{{ $customer->jobs_count }} {{ \Illuminate\Support\Str::plural('job', $customer->jobs_count) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-700">{{ $customer->phone ?? '—' }}</p>
                        @if ($customer->address)<p class="text-sm text-gray-600">{{ $customer->address }}</p>@endif
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Desktop: table --}}
        <div class="mt-3 hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 md:block">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">
                    <tr>
                        <th scope="col" class="px-4 py-3">Name</th>
                        <th scope="col" class="px-4 py-3">Phone</th>
                        <th scope="col" class="px-4 py-3">Email</th>
                        <th scope="col" class="px-4 py-3">Address</th>
                        <th scope="col" class="px-4 py-3 text-right">Jobs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium"><a href="{{ route('customers.show', $customer) }}" class="text-indigo-700 hover:underline">{{ $customer->name }}</a></td>
                            <td class="px-4 py-3 text-gray-800">{{ $customer->phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $customer->email ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $customer->address ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ $customer->jobs_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $customers->links() }}</div>
    @endif
</x-app-layout>
