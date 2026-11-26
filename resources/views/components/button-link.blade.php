@props(['variant' => 'primary'])
@php
$classes = match ($variant) {
    'secondary' => 'bg-white text-gray-800 ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
    default => 'bg-indigo-700 text-white hover:bg-indigo-800',
};
@endphp
<a {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1 rounded-md px-4 py-2 text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 '.$classes]) }}>{{ $slot }}</a>
