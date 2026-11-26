<x-app-layout>
    <x-slot name="title">Edit customer</x-slot>
    <x-slot name="header">
        <x-page-header title="Edit customer" :subtitle="$customer->name" />
    </x-slot>

    <div class="max-w-3xl">
        @include('customers._form', [
            'action' => route('customers.update', $customer),
            'method' => 'PATCH',
            'submitLabel' => 'Save changes',
            'cancelUrl' => route('customers.show', $customer),
        ])
    </div>
</x-app-layout>
