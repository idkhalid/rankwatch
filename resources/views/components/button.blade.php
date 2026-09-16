@props(['href' => null, 'variant' => 'primary'])
@php($classes = match ($variant) {
    'secondary' => 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50',
    'subtle' => 'bg-slate-100 text-slate-700 hover:bg-slate-200',
    default => 'border border-slate-950 bg-slate-950 text-white hover:bg-slate-800',
})
@if ($href)
    <a {{ $attributes->merge(['href' => $href, 'class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500 {$classes}"]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500 {$classes}"]) }}>{{ $slot }}</button>
@endif
