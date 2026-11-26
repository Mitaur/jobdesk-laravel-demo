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
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" maxlength="120" required autocomplete="off"
                          :value="old('name', $customer->name)" placeholder="e.g. Olivia Bennett" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" maxlength="40" autocomplete="off"
                              :value="old('phone', $customer->phone)" placeholder="(555) 010-1234" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" maxlength="191" autocomplete="off"
                              :value="old('email', $customer->email)" placeholder="name@example.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="address" value="Address" />
            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" maxlength="255" autocomplete="off"
                          :value="old('address', $customer->address)" placeholder="Street, city, ZIP" />
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <x-primary-button>{{ $submitLabel }}</x-primary-button>
            <a href="{{ $cancelUrl }}" class="text-sm font-semibold text-gray-800 hover:underline">Cancel</a>
        </div>
    </div>
</form>
