{{--
    Wishlist (favorites) client.

    Kept as a Blade partial (not a public/front asset) on purpose: public/front is
    a separate git reference, so shipping the logic here keeps the whole feature in
    the main repository and deployed with the views. Config is rendered server-side;
    the module below is framework-agnostic and delegates a single click handler.
--}}
<script>
  window.Dyafa = Object.assign(window.Dyafa || {}, {
    auth: @json(auth('customer')->check()),
    csrf: '{{ csrf_token() }}',
    routes: {
      wishlistToggle: '{{ route('customer.toggle.favorite') }}'
    },
    i18n: {
      loginRequired: @json(__('apartment.favorite_login')),
      added: @json(__('apartment.favorite_added')),
      removed: @json(__('apartment.favorite_removed')),
      error: @json(__('apartment.favorite_failed'))
    }
  });
</script>

<script>
/**
 * One delegated handler for every heart button on the site.
 *
 * A button opts in with:  data-wishlist-toggle  data-apartment-id="123"
 * and (optionally, for icon-swap buttons) an inner
 *   <img data-wishlist-icon data-icon-active="..." data-icon-inactive="...">
 *
 * State is mirrored across ALL buttons that share an apartment id (grid card +
 * detail page). The listener is delegated on `document`, so dynamically injected
 * cards (infinite scroll) work without re-binding. Guests cannot favorite: a
 * click opens the login modal (#popup-5) instead of calling the server.
 */
(function (window, document) {
    'use strict';

    var $ = window.jQuery;
    var cfg = window.Dyafa || {};
    var i18n = cfg.i18n || {};
    var routes = cfg.routes || {};
    var SELECTOR = '[data-wishlist-toggle]';

    function buttonsFor(id) {
        return document.querySelectorAll(SELECTOR + '[data-apartment-id="' + id + '"]');
    }

    function paint(id, favorited) {
        buttonsFor(id).forEach(function (btn) {
            btn.classList.toggle('favorite-active', favorited);
            btn.setAttribute('aria-pressed', favorited ? 'true' : 'false');

            var icon = btn.querySelector('[data-wishlist-icon]');
            if (icon) {
                icon.setAttribute('src', favorited ? icon.dataset.iconActive : icon.dataset.iconInactive);
            }
        });
    }

    function setCount(count) {
        if (typeof count !== 'number') {
            return;
        }
        document.querySelectorAll('[data-wishlist-count]').forEach(function (el) {
            el.textContent = count;
        });
    }

    function openLogin() {
        if ($ && $.fancybox) {
            $.fancybox.open({ src: '#popup-5', type: 'inline' });
        }
    }

    function toast(icon, title) {
        if (window.Swal && title) {
            window.Swal.fire({ icon: icon, title: title, timer: 1600, showConfirmButton: false });
        }
    }

    function removeCardIfPresent(btn) {
        var card = btn.closest('[data-wishlist-card]');
        if (!card) {
            return;
        }
        card.remove();

        var grid = document.querySelector('[data-wishlist-grid]');
        if (grid && grid.querySelectorAll('[data-wishlist-card]').length === 0) {
            var empty = grid.querySelector('[data-wishlist-empty]');
            if (empty) {
                empty.classList.remove('hidden');
            }
        }
    }

    function toggle(btn) {
        var id = btn.getAttribute('data-apartment-id');
        if (!id) {
            return;
        }

        if (!cfg.auth) {
            openLogin();
            return;
        }

        if (btn.dataset.wishlistBusy) {
            return;
        }
        btn.dataset.wishlistBusy = '1';

        var wasFavorited = btn.classList.contains('favorite-active');
        paint(id, !wasFavorited); // optimistic

        $.ajax({
            url: routes.wishlistToggle,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: { apartment_id: id, _token: cfg.csrf }
        }).done(function (res) {
            if (!res || !res.success) {
                paint(id, wasFavorited);
                toast('error', (res && res.message) || i18n.error);
                return;
            }

            var favorited = res.action === 'added';
            paint(id, favorited);
            setCount(res.count);

            if (!favorited) {
                removeCardIfPresent(btn);
            }

            document.dispatchEvent(new CustomEvent('wishlist:changed', {
                detail: { apartmentId: id, favorited: favorited, count: res.count }
            }));
        }).fail(function (xhr) {
            paint(id, wasFavorited);
            if (xhr.status === 401 || xhr.status === 403 || xhr.status === 419) {
                openLogin();
            } else {
                toast('error', i18n.error);
            }
        }).always(function () {
            delete btn.dataset.wishlistBusy;
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest(SELECTOR);
        if (!btn) {
            return;
        }
        e.preventDefault();
        toggle(btn);
    });
})(window, document);
</script>
