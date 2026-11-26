{{-- Jobs list: a table on md+ screens, stacked cards on phones. --}}
@php($showCustomer = $showCustomer ?? true)

{{-- Phone: cards --}}
<ul class="mt-3 space-y-3 md:hidden">
    @foreach ($jobs as $job)
        <li>
            <a href="{{ route('jobs.show', $job) }}" class="block rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 hover:ring-indigo-400">
                <div class="flex items-start justify-between gap-3">
                    <p class="font-semibold text-gray-900">{{ $job->title }}</p>
                    <x-status-badge :status="$job->status" class="shrink-0" />
                </div>
                @if ($showCustomer)
                    <p class="mt-1 text-sm text-gray-700">{{ $job->customer->name }}</p>
                @endif
                <div class="mt-2 flex justify-between text-sm text-gray-600">
                    <span>{{ $job->scheduled_at?->format('D, M j · g:i A') ?? 'No date yet' }}</span>
                    <span class="font-medium text-gray-900">{{ $job->price !== null ? '$'.number_format((float) $job->price, 2) : '—' }}</span>
                </div>
            </a>
        </li>
    @endforeach
</ul>

{{-- Tablet / desktop: table --}}
<div class="mt-3 hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 md:block">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">
            <tr>
                <th scope="col" class="px-4 py-3">Job</th>
                @if ($showCustomer)<th scope="col" class="px-4 py-3">Customer</th>@endif
                <th scope="col" class="px-4 py-3">Status</th>
                <th scope="col" class="px-4 py-3">Scheduled</th>
                <th scope="col" class="px-4 py-3 text-right">Price</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($jobs as $job)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">
                        <a href="{{ route('jobs.show', $job) }}" class="text-indigo-700 hover:underline">{{ $job->title }}</a>
                    </td>
                    @if ($showCustomer)
                        <td class="px-4 py-3 text-gray-800">
                            <a href="{{ route('customers.show', $job->customer) }}" class="hover:underline">{{ $job->customer->name }}</a>
                        </td>
                    @endif
                    <td class="px-4 py-3"><x-status-badge :status="$job->status" /></td>
                    <td class="px-4 py-3 text-gray-700">{{ $job->scheduled_at?->format('D, M j · g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-medium">{{ $job->price !== null ? '$'.number_format((float) $job->price, 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
