{{--
    Self-contained SVG version of the Saudi Riyal symbol (official glyph, U+20C1).

    Unlike the webfont-based x-riyal component, this inlines the vector directly
    with fill:currentColor and its own sizing, so it needs no font load and no
    external CSS. Use it in the Backpack admin (which has its own layout) or
    anywhere a font dependency is undesirable (print, PDF).

    Usage (x-riyal-svg):
        :amount="$booking->total_price"   -> 1,234.00 <glyph>
        :amount="$p" :format="false"      -> 1234.5 <glyph>
        (no amount)                       -> <glyph> only
        class="text-danger"               -> passed through; glyph inherits color
--}}
@props([
    'amount' => null,
    'decimals' => 2,
    'format' => true,
])

@php
    $hasAmount = ! is_null($amount) && $amount !== '';
    $display = $hasAmount
        ? ($format ? number_format((float) $amount, $decimals) : $amount)
        : null;
    $label = __('apartment.currency');
@endphp
<span {{ $attributes->merge(['class' => 'sar-price']) }}>@if($hasAmount)<span class="sar-amount">{{ $display }}</span> @endif<svg viewBox="0 0 1124.14 1256.39" role="img" aria-label="{{ $label }}" focusable="false" style="height:0.85em;width:auto;display:inline-block;vertical-align:-0.08em;fill:currentColor"><path d="M699.62,1113.02h0c-20.06,44.48-33.32,92.75-38.4,143.37l424.51-90.24c20.06-44.47,33.31-92.75,38.4-143.37l-424.51,90.24Z"/><path d="M1085.73,895.8c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.33v-135.2l292.27-62.11c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.27V66.13c-50.67,28.45-95.67,66.32-132.25,110.99v403.35l-132.25,28.11V0c-50.67,28.44-95.67,66.32-132.25,110.99v525.69l-295.91,62.88c-20.06,44.47-33.33,92.75-38.42,143.37l334.33-71.05v170.26l-358.3,76.14c-20.06,44.47-33.32,92.75-38.4,143.37l375.04-79.7c30.53-6.35,56.77-24.4,73.83-49.24l68.78-101.97v-.02c7.14-10.55,11.3-23.27,11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28Z"/></svg></span>
