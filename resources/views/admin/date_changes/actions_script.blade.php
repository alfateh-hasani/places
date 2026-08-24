{{-- عند الضغط على زر إجراء: نعرض نافذة تأكيد أنيقة، ثم نبني نموذج POST ديناميكياً مع رمز CSRF
     (لتفادي إظهار الرمز داخل الجدول). ندعم SweetAlert2 و SweetAlert v2 (المضمّنة في Backpack)
     مع تراجع إلى نافذة المتصفح إن لم تتوفر أي منهما. --}}
@push('after_scripts')
<script>
    jQuery(function ($) {
        var CANCEL = @json(__('cms.cancel'));

        function buildAndSubmit(url) {
            var $form = $('<form>', { method: 'POST', action: url }).css('display', 'none');
            $form.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));
            $('body').append($form);
            $form.trigger('submit');
        }

        $(document).on('click', '.dc-action-btn', function () {
            var $btn = $(this);
            var url = $btn.data('url');
            var text = String($btn.data('confirm') || '');
            var confirmBtn = String($btn.data('confirm-btn') || @json(__('cms.confirm')));
            var title = String($btn.data('title') || confirmBtn);
            var icon = String($btn.data('icon') || 'warning');
            var danger = !!$btn.data('danger');
            var color = String($btn.data('color') || '#3085d6');

            // 1) SweetAlert2 (has .fire)
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: title, text: text, icon: icon,
                    showCancelButton: true, reverseButtons: true, focusCancel: true,
                    confirmButtonText: confirmBtn, cancelButtonText: CANCEL,
                    confirmButtonColor: color, cancelButtonColor: '#6c757d',
                }).then(function (r) { if (r && r.isConfirmed) { buildAndSubmit(url); } });
                return;
            }

            // 2) SweetAlert v2 (t4t5) — swal({...}).then(confirmed) ; icons: warning/error/success/info
            if (typeof window.swal === 'function') {
                var v2icon = (icon === 'question') ? 'info' : icon;
                window.swal({
                    title: title, text: text, icon: v2icon,
                    buttons: [CANCEL, confirmBtn], dangerMode: danger,
                }).then(function (confirmed) { if (confirmed) { buildAndSubmit(url); } });
                return;
            }

            // 3) Fallback
            if (!text || window.confirm(text)) { buildAndSubmit(url); }
        });
    });
</script>
@endpush
