@once
    <div id="copyPasscodeNotification" style="display: none; position: fixed; top: 20px; right: 20px; padding: 10px; background-color: #28a745; color: white; border-radius: 5px; z-index: 1000;">
        {{ __('booking.code_copied') }}
    </div>
    <script>
        function copyBookingPasscode(text, btn) {
            if (!text) {
                return;
            }

            var showNotification = function () {
                var notification = document.getElementById('copyPasscodeNotification');
                if (!notification) {
                    return;
                }
                notification.style.display = 'block';
                setTimeout(function () {
                    notification.style.display = 'none';
                }, 2000);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(showNotification);
                return;
            }

            var tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            tempInput.setSelectionRange(0, 99999);
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            showNotification();
        }
    </script>
@endonce
<button type="button" onclick="copyBookingPasscode('{{ $code }}', this)" class="text-price underline decoration-solid" title="{{ __('booking.copy_code') }}">
    {{ __('booking.copy_code') }}
</button>
