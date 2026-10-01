{{-- أداة رفع قفل إرسال رمز التحقق برقم الجوال — تغطي الأرقام المحظورة التي لا تملك حساباً بعد،
     فلا تظهر في جدول العملاء. منتقي مفتاح دولة قابل للبحث (مبني داخلياً بلا مكتبات خارجية لتفادي
     هشاشة تحميل الأصول داخل Backpack). يُبنى الرقم بصيغة دولية كاملة (+9665XXXXXXXX). --}}
@php
    $otpDialCodes = [
        '+966' => 'السعودية',
        '+971' => 'الإمارات',
        '+965' => 'الكويت',
        '+973' => 'البحرين',
        '+968' => 'عُمان',
        '+974' => 'قطر',
        '+967' => 'اليمن',
        '+20'  => 'مصر',
        '+962' => 'الأردن',
        '+963' => 'سوريا',
        '+964' => 'العراق',
        '+961' => 'لبنان',
        '+970' => 'فلسطين',
        '+249' => 'السودان',
        '+212' => 'المغرب',
        '+213' => 'الجزائر',
        '+216' => 'تونس',
        '+218' => 'ليبيا',
        '+90'  => 'تركيا',
        '+44'  => 'بريطانيا',
        '+1'   => 'أمريكا/كندا',
    ];
@endphp

@push('after_styles')
<style>
    .otp-cc { position: relative; width: 210px; }
    /* Positioned with position:fixed via JS so no parent overflow can clip it. */
    .otp-cc-list {
        position: fixed; z-index: 100060;
        margin: 0; padding: .25rem 0; list-style: none; max-height: 260px; overflow-y: auto;
        background: #fff; border: 1px solid #ced4da; border-radius: .375rem;
        box-shadow: 0 6px 18px rgba(0,0,0,.15);
    }
    .otp-cc-list li { padding: .4rem .75rem; cursor: pointer; white-space: nowrap; color: #1f2d3d; }
    .otp-cc-list li:hover, .otp-cc-list li.is-active { background: #f0ad4e; color: #fff; }
</style>
@endpush

<div class="card otp-unlock-card">
    <div class="card-body">
        <form id="otp-unlock-form" method="POST"
              action="{{ url(config('backpack.base.route_prefix').'/customer/reset-otp-by-phone') }}">
            @csrf
            <label class="form-label mb-1 d-block">{{ __('cms.reset_otp_by_phone') }}</label>

            <div class="d-flex align-items-start flex-wrap" style="gap:.5rem;">
                <input type="tel" name="phone" dir="ltr" required placeholder="5XXXXXXXX"
                       class="form-control" style="max-width:200px;direction:ltr;text-align:left;">

                <div class="otp-cc">
                    <input type="text" id="otp-cc-input" class="form-control" autocomplete="off"
                           dir="ltr" value="{{ $otpDialCodes['+966'] }} +966"
                           aria-label="{{ __('cms.reset_otp_by_phone') }}">
                    <input type="hidden" name="dial_code" id="otp-cc-value" value="+966">
                    <ul id="otp-cc-list" class="otp-cc-list" style="display:none;">
                        @foreach ($otpDialCodes as $code => $name)
                            <li data-code="{{ $code }}" data-label="{{ $name }} {{ $code }}">{{ $name }} {{ $code }}</li>
                        @endforeach
                    </ul>
                </div>

                <button type="submit" class="btn btn-warning">
                    <i class="la la-unlock"></i> {{ __('cms.reset_otp') }}
                </button>
            </div>
            <small class="text-muted d-block mt-2">{{ __('cms.reset_otp_by_phone_hint') }}</small>
        </form>
    </div>
</div>

@push('after_scripts')
<script>
    (function () {
        var wrap  = document.querySelector('.otp-cc');
        if (!wrap) { return; }
        var input = wrap.querySelector('#otp-cc-input');
        var hidden = wrap.querySelector('#otp-cc-value');
        var list  = wrap.querySelector('#otp-cc-list');
        var items = Array.prototype.slice.call(list.querySelectorAll('li'));

        function positionList() {
            var r = input.getBoundingClientRect();
            list.style.top = (r.bottom + 2) + 'px';
            list.style.left = r.left + 'px';
            list.style.width = r.width + 'px';
        }
        function isOpen() { return list.style.display === 'block'; }
        function open()  { positionList(); list.style.display = 'block'; }
        function close() { list.style.display = 'none'; }

        window.addEventListener('scroll', function () { if (isOpen()) { positionList(); } }, true);
        window.addEventListener('resize', function () { if (isOpen()) { positionList(); } });

        function filter(q) {
            q = (q || '').trim().toLowerCase();
            items.forEach(function (li) {
                var hay = li.getAttribute('data-label').toLowerCase();
                li.style.display = (q === '' || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        }

        function selectItem(li) {
            input.value = li.getAttribute('data-label');
            hidden.value = li.getAttribute('data-code');
            close();
        }

        function currentLabel() {
            var cur = items.find(function (li) { return li.getAttribute('data-code') === hidden.value; });
            return cur ? cur.getAttribute('data-label') : input.value;
        }

        input.addEventListener('focus', function () { input.select(); filter(''); open(); });
        input.addEventListener('input', function () { open(); filter(input.value); });

        items.forEach(function (li) {
            li.addEventListener('mousedown', function (e) { e.preventDefault(); selectItem(li); });
        });

        input.addEventListener('blur', function () {
            setTimeout(function () {
                var typed = input.value.trim().toLowerCase();
                var match = items.find(function (li) {
                    return li.getAttribute('data-label').toLowerCase() === typed
                        || li.getAttribute('data-code') === input.value.trim();
                });
                if (match) { selectItem(match); }
                else { input.value = currentLabel(); }
                close();
            }, 150);
        });

        document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) { close(); } });
    })();
</script>
@endpush
