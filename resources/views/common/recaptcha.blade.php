{{-- reCAPTCHA v3 token helper. The Google script is loaded on first use (a form submit)
     so pages that never submit a protected form don't pay for it.
     Usage: window.recaptchaToken('login').then(function (token) { ... }); --}}
<script>
  window.recaptchaToken = (function () {
    var siteKey = @json(config('googlerecaptchav3.site_key'));
    var loader = null;

    function load() {
      if (!loader) {
        loader = new Promise(function (resolve, reject) {
          var script = document.createElement('script');
          script.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(siteKey);
          script.async = true;
          script.onload = function () { grecaptcha.ready(resolve); };
          script.onerror = function () { loader = null; reject(new Error('reCAPTCHA failed to load')); };
          document.head.appendChild(script);
        });
      }
      return loader;
    }

    return function (action) {
      if (!siteKey) {
        return Promise.resolve('');
      }
      return load()
        .then(function () { return grecaptcha.execute(siteKey, { action: action }); })
        .catch(function () { return ''; });
    };
  })();
</script>
