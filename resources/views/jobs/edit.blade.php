<x-app-layout>
    <x-slot name="title">Edit job</x-slot>
    <x-slot name="header">
        <x-page-header title="Edit job" :subtitle="$job->title" />
    </x-slot>

    <div class="max-w-3xl">
        @include('jobs._form', [
            'action' => route('jobs.update', $job),
            'method' => 'PATCH',
            'submitLabel' => 'Save changes',
            'cancelUrl' => route('jobs.show', $job),
        ])
    </div>
</x-app-layout>
