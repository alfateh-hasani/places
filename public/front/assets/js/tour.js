/* Dyafa customer product tour — built on driver.js v1.3.x.
   Reads window.DyafaTour (injected by common/parts/tour.blade.php). */
(function () {
    'use strict';

    var cfg = window.DyafaTour;
    if (!cfg) { return; }

    // The IIFE build exposes window.driver.js.driver. Guard every level so a
    // blocked/missing library never throws (QA item: no console errors).
    var driverFactory = (window.driver && window.driver.js && window.driver.js.driver) || null;
    if (!driverFactory) { return; }

    var storageKey = 'dyafa_tour_' + cfg.key + '_v' + cfg.version;

    // An element only makes a usable tour step when it is actually rendered and
    // visible. The customer site duplicates many controls across a desktop and a
    // mobile layout (header nav, account sidebar, book/pay buttons), so the same
    // [data-tour] value can appear twice — we must highlight whichever copy the
    // current viewport is showing, not the first one in the DOM (often the hidden one).
    function isVisible(el) {
        if (!el) { return false; }
        if (!el.getClientRects().length) { return false; }
        var style = window.getComputedStyle(el);
        if (style.visibility === 'hidden' || style.display === 'none' || style.opacity === '0') { return false; }
        var rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    }

    function findAnchor(anchor) {
        var nodes = document.querySelectorAll('[data-tour="' + anchor + '"]');
        for (var i = 0; i < nodes.length; i++) {
            if (isVisible(nodes[i])) { return nodes[i]; }
        }
        // Fall back to the first match only if none are visible (lets driver.js
        // scroll it into view); returns null when the anchor is absent entirely.
        return nodes.length ? nodes[0] : null;
    }

    // Build a driver.js step from a DOM anchor + injected text.
    // Returns null (filtered out later) when the anchor or its copy is missing.
    function step(anchor, textKey, side, align, onStart) {
        var el = findAnchor(anchor);
        var t  = cfg.text[textKey];
        if (!el || !t) { return null; }
        var s = {
            element: el,
            popover: {
                title: t.title,
                description: t.body,
                side: side || 'bottom',
                // 'center' keeps the popover visually tied to the element in both
                // LTR and RTL (driver.js treats 'start'/'end' as physical, which
                // pins every popover to the left edge on an Arabic page).
                align: align || 'center',
            },
        };
        // Optional per-step side effect (e.g. open/close the mobile search overlay
        // so its field is actually visible before driver measures it).
        if (typeof onStart === 'function') { s.onHighlightStarted = onStart; }
        return s;
    }

    // A centered step with no element (welcome / finish).
    function centered(textKey) {
        var t = cfg.text[textKey];
        if (!t) { return null; }
        return { popover: { title: t.title, description: t.body } };
    }

    // Mobile layout (< lg = 1024px): the home search is a full-screen overlay
    // (section.search) hidden off-canvas until it gets the `active` class — the
    // same class main.js toggles via the "search" bar / close button.
    function isMobileView() {
        return (window.matchMedia && window.matchMedia('(max-width: 1023px)').matches)
            || window.innerWidth < 1024;
    }
    function setSearchOverlay(open) {
        var s = document.querySelector('section.search');
        if (!s) { return; }
        if (open) { s.classList.add('active'); } else { s.classList.remove('active'); }
    }

    var MAP = {
        home: function () {
            if (isMobileView()) {
                // On phones the search fields live behind a tap-to-open overlay, so
                // the tour points at the search bar, then opens the overlay itself
                // to reveal city/dates/guests. Each overlay step re-asserts the
                // open state so Back/Next navigation stays correct.
                var open = function () { setSearchOverlay(true); };
                var closed = function () { setSearchOverlay(false); };
                return [
                    centered('welcome'),
                    step('search-trigger', 'mobile_search', 'bottom', 'center', closed),
                    step('search-city', 'city', 'bottom', 'center', open),
                    step('search-dates', 'dates', 'bottom', 'center', open),
                    step('search-guests', 'guests', 'top', 'center', open),
                    step('search-submit', 'search', 'top', 'center', open),
                    step('featured-apartments', 'featured', 'top', 'center', closed),
                    cfg.isLoggedIn
                        ? step('nav-account', 'account', 'bottom', 'end')
                        : step('nav-login', 'login', 'bottom', 'end'),
                    centered('finish'),
                ];
            }
            return [
                centered('welcome'),
                step('search-city', 'city', 'bottom'),
                step('search-dates', 'dates', 'bottom'),
                step('search-guests', 'guests', 'bottom'),
                step('search-submit', 'search', 'bottom'),
                step('featured-apartments', 'featured', 'top'),
                cfg.isLoggedIn
                    ? step('nav-account', 'account', 'bottom', 'end')
                    : step('nav-login', 'login', 'bottom', 'end'),
                centered('finish'),
            ];
        },
        details: function () {
            return [
                step('pdp-gallery', 'gallery', 'bottom'),
                step('pdp-datepicker', 'dates', 'top'),
                step('pdp-price', 'price', 'top'),
                // 'bottom' (not 'left'/'right'): the favorite sits in the small
                // top-corner action cluster where a horizontal side can't fit and
                // driver.js falls back to a stale position far from the element.
                // Align toward the page interior so the popover never overflows the
                // viewport edge: the cluster is top-left in RTL (align 'start' →
                // extends right) and top-right in LTR (align 'end' → extends left).
                step('pdp-favorite', 'favorite', 'bottom', cfg.isRtl ? 'start' : 'end'),
                step('pdp-book', 'book', 'top'),
                step('pdp-reviews', 'reviews', 'bottom'),
            ];
        },
        booking: function () {
            return [
                step('booking-summary', 'summary', 'left'),
                step('booking-coupon', 'coupon', 'top'),
                step('booking-pay', 'pay', 'top'),
            ];
        },
        account: function () {
            return [
                step('acct-profile', 'profile', 'right'),
                step('acct-bookings', 'bookings', 'right'),
                step('acct-favorites', 'favorites', 'right'),
                step('acct-notifications', 'notifications', 'right'),
            ];
        },
        'booking-details': function () {
            return [
                step('bd-status', 'status', 'top'),
                step('bd-passcode', 'passcode', 'top'),
                step('bd-dates', 'dates', 'bottom'),
                step('bd-cancel', 'cancel', 'bottom'),
                step('bd-print', 'print', 'bottom'),
                step('bd-contact', 'contact', 'bottom'),
            ];
        },
    };

    function buildSteps() {
        var factory = MAP[cfg.key];
        if (!factory) { return []; }
        var steps = factory().filter(Boolean);
        return cfg.isRtl ? steps.map(flipSide) : steps;
    }

    // In RTL, swap horizontal popover sides so arrows point the right way.
    function flipSide(s) {
        if (!s.popover) { return s; }
        if (s.popover.side === 'left') { s.popover.side = 'right'; }
        else if (s.popover.side === 'right') { s.popover.side = 'left'; }
        return s;
    }

    var refreshing = false;

    function run() {
        var steps = buildSteps();
        if (!steps.length) { return; }

        var d = driverFactory({
            showProgress: steps.length > 1,
            allowClose: true,
            overlayColor: 'rgba(0,0,0,0.65)',
            stagePadding: 6,
            popoverClass: 'dyafa-theme',
            nextBtnText: cfg.i18n.next,
            prevBtnText: cfg.i18n.prev,
            doneBtnText: cfg.i18n.done,
            progressText: cfg.i18n.progress,
            steps: steps,
            onHighlighted: function () {
                // driver.js can position the popover against a stale rect when a
                // step scrolls the page (most visibly upward) or when a step opens
                // the mobile search overlay (a 0.4s slide), stranding the popover
                // far from its element. Refresh twice: at 60ms for scroll settle,
                // and at 520ms to catch the overlay transition. The flag stops
                // refresh() from recursing through this hook.
                if (refreshing) { return; }
                refreshing = true;
                setTimeout(function () { try { d.refresh(); } catch (e) {} }, 60);
                setTimeout(function () {
                    try { d.refresh(); } catch (e) {}
                    refreshing = false;
                }, 520);
            },
            onDestroyed: function () {
                // Always leave the mobile search overlay closed when the tour ends
                // (it may have been opened mid-tour), then mark this tour seen.
                setSearchOverlay(false);
                try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            },
        });
        d.drive();
    }

    function seen() {
        try { return localStorage.getItem(storageKey) === '1'; } catch (e) { return true; }
    }

    // Auto-run once per tour, after layout settles (AOS/slick/images/tabs).
    if (!seen()) {
        window.addEventListener('load', function () {
            setTimeout(run, 1200);
        });
    }

    // Manual replay: any element with [data-tour-start] re-runs the current page's tour.
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-tour-start]');
        if (trigger) {
            e.preventDefault();
            run();
        }
    });

    // Expose for console/debug and for a global "replay" link.
    cfg.run = run;
    cfg.reset = function () {
        try { localStorage.removeItem(storageKey); } catch (e) {}
    };
})();
