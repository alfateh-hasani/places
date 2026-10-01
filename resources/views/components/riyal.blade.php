{{--
    Reusable Saudi Riyal price/symbol component.

    Renders an amount followed by the official Saudi Riyal symbol (Unicode
    U+20C1, approved by SAMA in 2025). The glyph is drawn with the "saudi_riyal"
    webfont loaded in layouts/parts/head.blade.php and styled by .sar-symbol.

    Usage (x-riyal):
        :amount="$booking->total_price"       -> 1,234.00 SAR-symbol
        :amount="$price" :decimals="0"        -> 1,234 SAR-symbol
        :amount="$raw" :format="false"        -> 1234.5 SAR-symbol (no number_format)
        (no amount)                           -> symbol only
        :amount="$p" bold class="float-right" -> bold glyph + passed classes

    The number carries the value; the symbol is decorative and exposed to screen
    readers via aria-label (localized "SAR" / "ريال").
--}}
@props([
    'amount' => null,
    'decimals' => 2,
    'format' => true,
    'bold' => false,
])

@php
    $hasAmount = ! is_null($amount) && $amount !== '';
    $display = $hasAmount
        ? ($format ? number_format((float) $amount, $decimals) : $amount)
        : null;
@endphp
<span {{ $attributes->merge(['class' => 'sar-price']) }}>@if($hasAmount)<span class="sar-amount">{{ $display }}</span> @endif<span class="sar-symbol{{ $bold ? ' sar-symbol--bold' : '' }}" role="img" aria-label="{{ __('apartment.currency') }}">&#x20C1;</span></span>
