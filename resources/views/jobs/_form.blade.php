{{-- Shared create/edit form. $job, $customers, $action, $method --}}
<form method="POST" action="{{ $action }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200 sm:p-6" novalidate>
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="space-y-5">

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">
                Please fix the {{ $errors->count() === 1 ? 'error' : $errors->count().' errors' }} below.
            </div>
        @endif

        <div>
            <x-input-label for="customer_id" value="Customer" />
            @if ($customers->isEmpty())
                <p class="mt-1 text-sm text-gray-700">You have no customers yet. <a href="{{ route('customers.create') }}" class="font-semibold text-indigo-700 underline">Add a customer first</a>.</p>
            @endif
            <select id="customer_id" name="customer_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @error('customer_id') aria-invalid="true" aria-describedby="customer_id-error" @enderror>
                <option value="">Choose a customer…</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $job->customer_id) === (string) $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" id="customer_id-error" />
        </div>

        <div>
            <x-input-label for="title" value="Job title" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" maxlength="150" required
                          :value="old('title', $job->title)" placeholder="e.g. Fix leaking kitchen faucet" />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (\App\Enums\JobStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $job->status?->value) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="price" value="Price (USD)" />
                <x-text-input id="price" name="price" type="number" step="0.01" min="0" inputmode="decimal" class="mt-1 block w-full"
                              :value="old('price', $job->price)" placeholder="0.00" />
                <x-input-error :messages="$errors->get('price')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="scheduled_at" value="Scheduled date & time" />
                <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local" class="mt-1 block w-full"
                              :value="old('scheduled_at', $job->scheduled_at?->format('Y-m-d\TH:i'))" />
                <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="notes" value="Notes" />
            <textarea id="notes" name="notes" rows="4" maxlength="2000"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $job->notes) }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button>{{ $submitLabel }}</x-primary-button>
            <a href="{{ $cancelUrl }}" class="text-sm font-semibold text-gray-800 hover:underline">Cancel</a>
        </div>
    </div>
</form>
