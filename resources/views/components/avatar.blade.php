@props(['employee', 'size' => 34])

@php
    $photo = $employee?->photoUrl();
    $fontSize = max(10, (int) round($size * 0.36));
@endphp

<div
    {{ $attributes->merge(['class' => 'avatar-badge']) }}
    style="width: {{ $size }}px; height: {{ $size }}px; min-width: {{ $size }}px;
           border-radius: 50%; overflow: hidden; display: flex; align-items: center;
           justify-content: center; flex-shrink: 0; color: #fff; font-weight: 700;
           font-size: {{ $fontSize }}px; background: {{ $employee?->avatarColor() ?? '#ffbd08' }};"
>
    @if($photo)
        <img src="{{ $photo }}" alt="{{ $employee->full_name }}"
             style="width:100%; height:100%; object-fit:cover; display:block;">
    @else
        {{ $employee?->initials() ?? 'U' }}
    @endif
</div>