<script>
    function copyPasscodeToClipboard(text, btn) {
        if (!text) {
            return;
        }

        var onCopied = function () {
            if (!btn) {
                return;
            }
            var original = btn.innerHTML;
            btn.innerHTML = '<i class="la la-check text-success"></i>';
            setTimeout(function () {
                btn.innerHTML = original;
            }, 1500);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(onCopied);
            return;
        }

        var tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999);
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        onCopied();
    }
</script>
