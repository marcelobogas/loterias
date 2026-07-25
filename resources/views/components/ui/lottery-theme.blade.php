@props(['lottery'])

@php
    $accent = $lottery->color_hex ?? '#10b981';
    $onAccent = $lottery->accentForegroundHex();
@endphp

<div {{ $attributes->class(['space-y-6']) }}
    style="--lottery-accent: {{ $accent }}; --lottery-accent-hover: color-mix(in srgb, {{ $accent }} 85%, white); --lottery-on-accent: {{ $onAccent }};">
    {{ $slot }}
</div>
