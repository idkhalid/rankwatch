@props(['href' => null, 'variant' => 'primary'])
@php($classes = $variant === 'secondary' ? 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50' : 'bg-slate-950 text-white hover:bg-slate-800')
@if ($href)
    <a {{ $attributes->merge(['href' => $href, 'class' => "inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold {$classes}"]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold {$classes}"]) }}>{{ $slot }}</button>
@endif
