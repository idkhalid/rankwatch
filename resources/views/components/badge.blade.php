@props(['tone' => 'slate'])
@php($colors = [
    'critical' => 'bg-red-50 text-red-700 ring-red-200',
    'high' => 'bg-orange-50 text-orange-700 ring-orange-200',
    'medium' => 'bg-amber-50 text-amber-700 ring-amber-200',
    'low' => 'bg-sky-50 text-sky-700 ring-sky-200',
    'slate' => 'bg-slate-50 text-slate-700 ring-slate-200',
])
<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset '.($colors[$tone] ?? $colors['slate'])]) }}>{{ $slot }}</span>
