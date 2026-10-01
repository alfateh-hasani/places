{{-- Staff notification bell (in-app web_notifications). Rendered to the left of the
     user avatar / logout dropdown. Backed by Admin\WebNotificationController. --}}
@php($isRtl = backpack_theme_config('html_direction') == 'rtl')

<li class="nav-item dropdown d-flex align-items-center" id="web-notif-bell">
    <a class="nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"
       style="display:inline-flex;align-items:center;">
        <span style="position:relative;display:inline-block;line-height:1;">
            <i class="la la-bell" style="font-size:1.4rem;"></i>
            <span class="badge badge-pill badge-danger" id="web-notif-badge"
                  style="position:absolute;top:-8px;{{ $isRtl ? 'left:-10px;' : 'right:-10px;' }}font-size:60%;display:none;">0</span>
        </span>
    </a>
    <div class="dropdown-menu {{ $isRtl ? 'dropdown-menu-left' : 'dropdown-menu-right' }}" style="width:340px;max-width:92vw;padding:0;">
        <div class="dropdown-header d-flex justify-content-between align-items-center" style="border-bottom:1px solid rgba(0,0,0,.08);">
            <strong>الإشعارات</strong>
            <a href="#" id="web-notif-mark-all" style="font-size:85%;">تعليم الكل كمقروء</a>
        </div>
        <div id="web-notif-list" style="max-height:360px;overflow-y:auto;">
            <div class="dropdown-item text-muted small">…</div>
        </div>
    </div>
</li>

<script>
    window.WebNotifConfig = {
        indexUrl: @json(route('admin.web-notifications.index')),
        readAllUrl: @json(route('admin.web-notifications.read-all')),
        readUrlBase: @json(url('admin/web-notifications')),
        csrf: @json(csrf_token()),
    };
</script>
{{-- url() (not asset()) so it loads from the app origin, not ASSET_URL's CDN host. --}}
<script src="{{ url('js/web-notifications-bell.js') }}?v=1"></script>
