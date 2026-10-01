{{--
    Email-safe Saudi Riyal symbol.

    Email clients (Gmail, Outlook) strip webfonts and inline SVG, so emails must
    use a raster <img>. This points at a public CDN copy of the official glyph so
    it loads in the recipient's inbox regardless of our app domain.

    The src MUST be an absolute, PUBLICLY reachable URL — email clients (Gmail,
    Outlook) fetch it from their own servers, which cannot reach a local/dev host
    like places.test. So:
      - Local/dev testing  -> use the public CDN default below.
      - Production          -> set RIYAL_SYMBOL_URL to your self-hosted copy,
                               e.g. https://your-domain.com/front/img/sar-symbol.png
                               (the file already exists at public/front/img/sar-symbol.png).
    Controlled via config('app.riyal_symbol_url') so no code change is needed.

    Usage (x-riyal-email): place it right after the amount —
        {{ number_format($booking->total_price, 2) }} <x-riyal-email />
--}}
@php($riyalSymbolUrl = config('app.riyal_symbol_url') ?: 'https://cdn.jsdelivr.net/gh/abdulrysrr/new-saudi-riyal-symbol@main/png/Saudi_Riyal_Symbol.png')
<img src="{{ $riyalSymbolUrl }}"
     alt="{{ __('apartment.currency') }}"
     width="13" height="15"
     style="display:inline-block;height:0.95em;width:auto;vertical-align:-0.12em;border:0;" />
