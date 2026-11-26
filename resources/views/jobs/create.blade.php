<x-app-layout>
    <x-slot name="title">New job</x-slot>
    <x-slot name="header">
        <x-page-header title="New job" subtitle="Add a quote or schedule a visit." />
    </x-slot>

    <div class="max-w-3xl">
        @include('jobs._form', [
            'action' => route('jobs.store'),
            'method' => 'POST',
            'submitLabel' => 'Create job',
            'cancelUrl' => route('jobs.index'),
        ])
    </div>
</x-app-layout>
