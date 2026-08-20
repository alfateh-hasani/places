@php($tiles = $widget['tiles'] ?? [])
<style>
    .refund-kpi .kpi-card { border-radius: 12px; }
    .refund-kpi .kpi-card .card-body { padding: 1rem 1.1rem; }
    .refund-kpi .kpi-icon { font-size: 2.2rem; opacity: .55; }
    @media (max-width: 767.98px) {
        .refund-kpi > [class*="col-"] { margin-bottom: .9rem; padding-left: 8px; padding-right: 8px; }
        .refund-kpi .kpi-card .card-body { padding: .95rem .9rem; }
        .refund-kpi .kpi-value { font-size: 1.25rem !important; }
        .refund-kpi .kpi-icon { font-size: 1.9rem; }
    }
</style>
<div class="row mb-3 refund-kpi">
    @foreach ($tiles as $tile)
        <div class="col-6 col-md-3 mb-2">
            <div class="card kpi-card mb-0 border-0 shadow-sm" style="background: {{ $tile['color'] }};">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="text-start" style="min-width:0;">
                        <div style="color:#fff; opacity:.85; font-size:.8rem; line-height:1.25;">{{ $tile['label'] }}</div>
                        <div class="kpi-value" style="color:#fff; font-size:1.45rem; font-weight:700; line-height:1.25; white-space:nowrap;">{{ $tile['value'] }}</div>
                    </div>
                    <i class="la {{ $tile['icon'] }} kpi-icon" style="color:#fff;"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>
