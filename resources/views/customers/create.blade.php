<x-app-layout>
    <x-slot name="title">New customer</x-slot>
    <x-slot name="header">
        <x-page-header title="New customer" />
    </x-slot>

    <div class="max-w-3xl">
        @include('customers._form', [
            'action' => route('customers.store'),
            'method' => 'POST',
            'submitLabel' => 'Add customer',
            'cancelUrl' => route('customers.index'),
        ])
    </div>
</x-app-layout>
