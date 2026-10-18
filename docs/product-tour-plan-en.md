# Customer-Facing Product Tour — Build Plan

> Scope: a guided onboarding tour for the **customer website** (not the admin/Backpack dashboard).
> Status: design/plan only — nothing built yet. This document is the spec to build from later.
> Stack it targets: Laravel 11, plain Blade + jQuery, Tailwind, bilingual AR (RTL) / EN, locale-prefixed routes.

---

## 1. Goal & principles

**Goal:** help a first-time visitor understand, in ~30 seconds, how to find an apartment, pick dates, and book — without reading anything or contacting support.

**Principles**
- **Non-intrusive:** runs once automatically for a new visitor; after that it only re-runs if the user clicks a "?" help trigger.
- **Contextual, not one giant tour:** a short tour per key page (home, property details, account) instead of one long cross-page walkthrough. Each tour only points at elements that exist on that page.
- **Bilingual + RTL-safe:** all copy comes from Laravel lang files; tooltip arrows/placement must work in `dir="rtl"` (Arabic) and `dir="ltr"` (English).
- **Zero backend dependency to start:** "seen / not seen" is stored in `localStorage`. (Optional DB persistence described in §11.)
- **Guest vs logged-in:** a guest can only be shown public steps (home, search, details, login). Booking/account steps require `auth('customer')`.

---

## 2. Library choice

Use **[driver.js](https://driverjs.com) v1.3.x** (`driver.js` by kamranahmedse).

Why it fits this project:
- Vanilla JS, ~5 KB, **no dependencies** (doesn't need jQuery, Alpine, or a framework — we have none of those as a UI framework anyway).
- Built-in highlight overlay + popover with Next/Prev/Done buttons and progress ("2 of 5").
- Programmatic API — we define steps as a JS array, which maps cleanly to `data-tour` anchors in Blade.
- RTL works by setting popover side/align per step and letting the page `dir` drive text.
- Self-hostable (matches how this repo vendors slick/fancybox/select2 under `public/front/assets/vendor/`).

> Alternative considered: **Shepherd.js** (more features, heavier, needs Popper). **intro.js** (license cost for commercial). driver.js is the lightest good fit. Flowbite (already loaded) only has single popovers, no multi-step tour — not enough.

**Install (self-host, preferred — matches repo convention):**
Download these two files and commit them:
```
public/front/assets/vendor/driverjs/driver.css      (from driver.js dist)
public/front/assets/vendor/driverjs/driver.js.iife.js
```
Pin the version in a comment at the top of each file.

> CDN fallback (the repo already CDN-loads Flatpickr, so this is acceptable if you prefer):
> ```html
> <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
> <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
> ```

---

## 3. File map (what to create / edit)

**New files**
| File | Purpose |
|---|---|
| `public/front/assets/vendor/driverjs/driver.css` | library CSS (self-hosted) |
| `public/front/assets/vendor/driverjs/driver.js.iife.js` | library JS (self-hosted) |
| `public/front/assets/js/tour.js` | our tour controller: defines tours, triggers, storage |
| `resources/views/common/parts/tour.blade.php` | injects translated step text + flags into JS, loads library + `tour.js` |
| `resources/lang/en/tour.php` | English copy |
| `resources/lang/ar/tour.php` | Arabic copy |

**Edited files (add anchors + one include + one CSS line)**
| File | Change |
|---|---|
| `resources/views/layouts/parts/head.blade.php` | add `driver.css` (and optionally `custom.css` overrides) into the CSS bundle |
| `resources/views/layouts/parts/js.blade.php` | `@include('common.parts.tour')` near the end (after jQuery/main.js) |
| `resources/views/common/header.blade.php` | `data-tour` on nav, login button, account dropdown; a "?" help trigger |
| `resources/views/home/parts/search.blade.php` | `data-tour` on city / check-in / check-out / guests / search button |
| `resources/views/home/parts/apartments.blade.php` | `data-tour="featured-apartments"` on the featured list |
| `resources/views/apartment/show.blade.php` | `data-tour` on gallery, date picker, price, book button, favorite, reviews |
| `resources/views/booking/confirm-booking.blade.php` | `data-tour` on coupon, summary, pay button |
| `resources/views/customer/section/sidebar.blade.php` | `data-tour` on account / bookings / favorites links |

---

## 4. Tours & their steps

Four tours, keyed by page. Each step references a `data-tour` value (the anchor) and a lang key.

### Tour A — `home` (guest + logged-in, auto on first visit)
| # | Anchor (`data-tour`) | Element | Lang key |
|---|---|---|---|
| 1 | *(no element — centered welcome)* | — | `tour.home.welcome` |
| 2 | `search-city` | city `<select>` in `home/parts/search` | `tour.home.city` |
| 3 | `search-dates` | check-in/out wrapper | `tour.home.dates` |
| 4 | `search-guests` | guests `.persons` block | `tour.home.guests` |
| 5 | `search-submit` | the search button | `tour.home.search` |
| 6 | `featured-apartments` | featured list section | `tour.home.featured` |
| 7 | `nav-login` | login button (guest) **or** `nav-account` (logged-in) | `tour.home.login` / `tour.home.account` |
| 8 | *(centered)* | — | `tour.home.finish` |

> Step 7 is conditional: render anchor on whichever element exists (`@auth('customer')`). `tour.js` picks the step whose anchor is present.

### Tour B — `details` (property page, auto on first visit to any property)
| # | Anchor | Element | Lang key |
|---|---|---|---|
| 1 | `pdp-gallery` | photo gallery | `tour.details.gallery` |
| 2 | `pdp-datepicker` | Flatpickr date inputs | `tour.details.dates` |
| 3 | `pdp-price` | computed price block | `tour.details.price` |
| 4 | `pdp-favorite` | wishlist/heart toggle | `tour.details.favorite` |
| 5 | `pdp-book` | "Book now" button | `tour.details.book` |
| 6 | `pdp-reviews` | reviews section | `tour.details.reviews` |

### Tour C — `booking` (confirm-booking page)
| # | Anchor | Element | Lang key |
|---|---|---|---|
| 1 | `booking-summary` | price/stay summary | `tour.booking.summary` |
| 2 | `booking-coupon` | coupon input | `tour.booking.coupon` |
| 3 | `booking-pay` | pay button | `tour.booking.pay` |

### Tour D — `account` (customer account area, logged-in only)
| # | Anchor | Element | Lang key |
|---|---|---|---|
| 1 | `acct-profile` | profile link in sidebar | `tour.account.profile` |
| 2 | `acct-bookings` | my reservations link | `tour.account.bookings` |
| 3 | `acct-favorites` | favorites link | `tour.account.favorites` |
| 4 | `acct-notifications` | notifications link | `tour.account.notifications` |

---

## 5. Copy (all tooltip text — EN + AR)

Put these in the lang files. Keep each ≤ ~140 chars so tooltips stay small.

### `resources/lang/en/tour.php`
```php
<?php

return [
    'next'     => 'Next',
    'prev'     => 'Back',
    'done'     => 'Done',
    'skip'     => 'Skip tour',
    'progress' => '{{current}} of {{total}}',
    'help'     => 'Take a tour',

    'home' => [
        'welcome'  => ['title' => 'Welcome to Dyafa 👋', 'body' => 'Let’s take 30 seconds to show you how to find and book your next stay.'],
        'city'     => ['title' => 'Pick a city',        'body' => 'Start by choosing the city you want to stay in.'],
        'dates'    => ['title' => 'Choose your dates',  'body' => 'Select your check-in and check-out dates.'],
        'guests'   => ['title' => 'Add guests',         'body' => 'Tell us how many adults and children are coming.'],
        'search'   => ['title' => 'Search',             'body' => 'Hit search to see all available apartments for your trip.'],
        'featured' => ['title' => 'Featured stays',     'body' => 'Or browse our hand-picked apartments right here.'],
        'login'    => ['title' => 'Sign in',            'body' => 'Log in with your phone number to book and manage reservations.'],
        'account'  => ['title' => 'Your account',       'body' => 'Access your bookings, favorites, and profile from here.'],
        'finish'   => ['title' => 'You’re all set! 🎉', 'body' => 'That’s it. You can replay this tour anytime from the “?” button.'],
    ],

    'details' => [
        'gallery'  => ['title' => 'Photos',           'body' => 'Browse all photos of the apartment. Click any image to enlarge it.'],
        'dates'    => ['title' => 'Select your stay', 'body' => 'Pick your dates here — the price updates automatically.'],
        'price'    => ['title' => 'Total price',      'body' => 'Your total for the selected dates, all fees included.'],
        'favorite' => ['title' => 'Save it',          'body' => 'Tap the heart to save this apartment to your favorites.'],
        'book'     => ['title' => 'Book now',         'body' => 'Happy with it? Start your booking here.'],
        'reviews'  => ['title' => 'Guest reviews',    'body' => 'See what previous guests thought before you decide.'],
    ],

    'booking' => [
        'summary' => ['title' => 'Your stay',   'body' => 'Review your dates, guests, and the price breakdown.'],
        'coupon'  => ['title' => 'Promo code',  'body' => 'Have a discount code? Enter it here to apply it.'],
        'pay'     => ['title' => 'Confirm & pay','body' => 'Choose a payment method and confirm your reservation.'],
    ],

    'account' => [
        'profile'       => ['title' => 'Your profile',   'body' => 'Update your name, phone, and personal details here.'],
        'bookings'      => ['title' => 'My reservations','body' => 'View, print, or manage all your bookings.'],
        'favorites'     => ['title' => 'Favorites',      'body' => 'All the apartments you’ve saved live here.'],
        'notifications' => ['title' => 'Notifications',  'body' => 'Booking updates and messages show up here.'],
    ],
];
```

### `resources/lang/ar/tour.php`
```php
<?php

return [
    'next'     => 'التالي',
    'prev'     => 'السابق',
    'done'     => 'إنهاء',
    'skip'     => 'تخطّي الجولة',
    'progress' => '{{current}} من {{total}}',
    'help'     => 'جولة تعريفية',

    'home' => [
        'welcome'  => ['title' => 'مرحباً بك في ضيافة 👋', 'body' => 'خلّنا نوريك في ٣٠ ثانية كيف تلقى وتحجز إقامتك القادمة.'],
        'city'     => ['title' => 'اختر المدينة',          'body' => 'ابدأ باختيار المدينة اللي تبي تقيم فيها.'],
        'dates'    => ['title' => 'حدّد التواريخ',          'body' => 'اختر تاريخ الوصول وتاريخ المغادرة.'],
        'guests'   => ['title' => 'عدد الضيوف',             'body' => 'أخبرنا كم عدد البالغين والأطفال المرافقين.'],
        'search'   => ['title' => 'ابحث',                   'body' => 'اضغط بحث لعرض جميع الشقق المتاحة لإقامتك.'],
        'featured' => ['title' => 'شقق مختارة',             'body' => 'أو تصفّح الشقق المختارة بعناية من هنا مباشرة.'],
        'login'    => ['title' => 'تسجيل الدخول',           'body' => 'سجّل برقم جوالك لتحجز وتدير حجوزاتك.'],
        'account'  => ['title' => 'حسابك',                  'body' => 'من هنا توصل لحجوزاتك والمفضّلة وبياناتك.'],
        'finish'   => ['title' => 'تمام! 🎉',              'body' => 'هذا كل شي. تقدر تعيد الجولة وقت ما تبي من زر «؟».'],
    ],

    'details' => [
        'gallery'  => ['title' => 'الصور',          'body' => 'تصفّح كل صور الشقة. اضغط أي صورة لتكبيرها.'],
        'dates'    => ['title' => 'حدّد إقامتك',      'body' => 'اختر تواريخك من هنا — السعر يتحدّث تلقائياً.'],
        'price'    => ['title' => 'السعر الإجمالي',   'body' => 'إجمالي المبلغ للتواريخ المختارة شامل الرسوم.'],
        'favorite' => ['title' => 'احفظها',          'body' => 'اضغط القلب لإضافة الشقة إلى المفضّلة.'],
        'book'     => ['title' => 'احجز الآن',        'body' => 'عجبتك؟ ابدأ حجزك من هنا.'],
        'reviews'  => ['title' => 'تقييمات النزلاء',  'body' => 'شوف آراء النزلاء السابقين قبل ما تقرّر.'],
    ],

    'booking' => [
        'summary' => ['title' => 'تفاصيل إقامتك', 'body' => 'راجع التواريخ وعدد الضيوف وتفاصيل السعر.'],
        'coupon'  => ['title' => 'كود الخصم',     'body' => 'عندك كود خصم؟ أدخله هنا لتطبيقه.'],
        'pay'     => ['title' => 'التأكيد والدفع','body' => 'اختر طريقة الدفع وأكّد حجزك.'],
    ],

    'account' => [
        'profile'       => ['title' => 'ملفّك الشخصي', 'body' => 'عدّل اسمك وجوالك وبياناتك من هنا.'],
        'bookings'      => ['title' => 'حجوزاتي',      'body' => 'اعرض واطبع وأدر جميع حجوزاتك.'],
        'favorites'     => ['title' => 'المفضّلة',      'body' => 'كل الشقق اللي حفظتها تلقاها هنا.'],
        'notifications' => ['title' => 'الإشعارات',     'body' => 'تحديثات الحجوزات والرسائل تظهر هنا.'],
    ],
];
```

---

## 6. Blade partial — `resources/views/common/parts/tour.blade.php`

This bridges Laravel (lang + auth + current route) into JS. Include it once from `layouts/parts/js.blade.php`.

```blade
{{-- Product tour: loads driver.js and our controller, and hands PHP data to JS.
     Included near the end of layouts/parts/js.blade.php (after jQuery/main.js). --}}

@php
    // Decide which tour belongs to the current page from the route name.
    $tourKey = match (true) {
        request()->routeIs('home')                   => 'home',
        request()->routeIs('apartments.show')        => 'details',
        request()->routeIs('web-booking.confirm-booking') => 'booking',
        request()->routeIs('customer.account'), request()->routeIs('customer.booking'),
        request()->routeIs('customer.favorite'), request()->routeIs('customer.notifications') => 'account',
        default => null,
    };
@endphp

@if ($tourKey)
    <link rel="stylesheet" href="{{ asset('assets/vendor/driverjs/driver.css') }}">
    <script src="{{ asset('assets/vendor/driverjs/driver.js.iife.js') }}"></script>
    <script>
        window.DyafaTour = {
            key: @json($tourKey),
            isRtl: @json(app()->getLocale() === 'ar'),
            isLoggedIn: @json(auth('customer')->check()),
            // Bump this when tour copy/steps change so returning users see the new tour once.
            version: 1,
            i18n: {
                next: @json(__('tour.next')),
                prev: @json(__('tour.prev')),
                done: @json(__('tour.done')),
                skip: @json(__('tour.skip')),
                progress: @json(__('tour.progress')),
            },
            text: @json(__('tour.' . $tourKey)),
        };
    </script>
    <script src="{{ asset('assets/js/tour.js') }}"></script>
@endif
```

> `asset('assets/...')` resolves under `public/front/` in this project (per the custom asset root — same pattern the header/search views already use, e.g. `asset('assets/img/...')`).

---

## 7. Controller JS — `public/front/assets/js/tour.js`

```js
/* Dyafa customer product tour — built on driver.js v1.3.x.
   Reads window.DyafaTour (injected by common/parts/tour.blade.php). */
(function () {
    'use strict';

    var cfg = window.DyafaTour;
    if (!cfg || typeof window.driver === 'undefined') { return; }

    var driver = window.driver.js.driver; // IIFE build exposes window.driver.js.driver

    var storageKey = 'dyafa_tour_' + cfg.key + '_v' + cfg.version;

    // Build driver.js steps from DOM anchors + injected text.
    // Only anchors that actually exist on the page become steps.
    function step(anchor, textKey, side, align) {
        var el = document.querySelector('[data-tour="' + anchor + '"]');
        var t  = cfg.text[textKey];
        if (!el || !t) { return null; }
        return {
            element: el,
            popover: {
                title: t.title,
                description: t.body,
                side: side || 'bottom',
                align: align || 'start',
            },
        };
    }

    // A centered step with no element (welcome / finish).
    function centered(textKey) {
        var t = cfg.text[textKey];
        if (!t) { return null; }
        return { popover: { title: t.title, description: t.body } };
    }

    var MAP = {
        home: function () {
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
                step('pdp-favorite', 'favorite', 'left'),
                step('pdp-book', 'book', 'top'),
                step('pdp-reviews', 'reviews', 'top'),
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
    };

    function buildSteps() {
        var factory = MAP[cfg.key];
        if (!factory) { return []; }
        return factory().filter(Boolean);
    }

    function run() {
        var steps = buildSteps();
        if (!steps.length) { return; }

        var d = driver({
            showProgress: true,
            allowClose: true,
            overlayColor: 'rgba(0,0,0,0.65)',
            stagePadding: 6,
            nextBtnText: cfg.i18n.next,
            prevBtnText: cfg.i18n.prev,
            doneBtnText: cfg.i18n.done,
            progressText: cfg.i18n.progress,
            // RTL: driver.js reads document dir for text; flip arrow sides for 'left'/'right'.
            steps: cfg.isRtl ? steps.map(flipSide) : steps,
            onDestroyed: function () {
                try { localStorage.setItem(storageKey, '1'); } catch (e) {}
            },
        });
        d.drive();
    }

    // In RTL, swap horizontal popover sides so arrows point the right way.
    function flipSide(s) {
        if (s.popover && s.popover.side === 'left') { s.popover.side = 'right'; }
        else if (s.popover && s.popover.side === 'right') { s.popover.side = 'left'; }
        return s;
    }

    function seen() {
        try { return localStorage.getItem(storageKey) === '1'; } catch (e) { return false; }
    }

    // Auto-run once per tour, after layout settles (AOS/slick/images).
    if (!seen()) {
        window.addEventListener('load', function () {
            setTimeout(run, 1200);
        });
    }

    // Manual replay: any element with [data-tour-start] re-runs the current page's tour.
    document.querySelectorAll('[data-tour-start]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            run();
        });
    });

    // Expose for console/debug and for a global "replay" link.
    window.DyafaTour.run = run;
    window.DyafaTour.reset = function () {
        try { localStorage.removeItem(storageKey); } catch (e) {}
    };
})();
```

> Note on the driver.js global: the IIFE build exposes `window.driver.js.driver`. Confirm the exact path against the version you download (older 0.x exposed `window.Driver`). If you use a different build, adjust the `var driver = ...` line.

---

## 8. Anchor edits (exact elements)

Add `data-tour="..."` to the following existing elements. These are the real elements from the current views.

**`resources/views/common/header.blade.php`**
- Desktop login button (guest), line ~135:
  ```html
  <button type="button" data-tour="nav-login" data-src="#popup-5" data-fancybox ...>
  ```
- Desktop account dropdown (logged-in), the "Hello, name" `<span>` at line ~112 — add anchor to its wrapping `.login` div or the span:
  ```html
  <span data-tour="nav-account" class="font-normal ...">@lang('site.hello'), ...</span>
  ```
- Add a help/replay trigger (new) — put it in the header `right` cluster, near `.lang`:
  ```html
  <button type="button" data-tour-start aria-label="@lang('tour.help')"
          class="ltr:ml-3 rtl:mr-3 w-8 h-8 rounded-full border border-border text-black">?</button>
  ```

**`resources/views/home/parts/search.blade.php`**
- City wrapper (line ~26 div) → `data-tour="search-city"`
- Check-in div (line ~34) — to cover both dates, put `data-tour="search-dates"` on a wrapper around check-in + check-out, or just on check-in div.
- Guests `.persons` div (line ~52) → `data-tour="search-guests"`
- Search button wrapper (line ~123 div) → `data-tour="search-submit"`

**`resources/views/home/parts/apartments.blade.php`**
- The featured apartments section/grid → `data-tour="featured-apartments"`

**`resources/views/apartment/show.blade.php`** (confirm selectors when building)
- Gallery container → `data-tour="pdp-gallery"`
- Flatpickr date inputs wrapper → `data-tour="pdp-datepicker"`
- Price block → `data-tour="pdp-price"`
- Wishlist/heart button → `data-tour="pdp-favorite"`
- Book button → `data-tour="pdp-book"`
- Reviews section → `data-tour="pdp-reviews"`

**`resources/views/booking/confirm-booking.blade.php`**
- Summary partial wrapper → `data-tour="booking-summary"`
- Coupon input wrapper → `data-tour="booking-coupon"`
- Pay button → `data-tour="booking-pay"`

**`resources/views/customer/section/sidebar.blade.php`**
- Profile / bookings / favorites / notifications links → `data-tour="acct-profile"` etc.

> Rule: if an element conditionally doesn't render (e.g. no reviews yet), the step is skipped automatically by the `.filter(Boolean)` in `tour.js` — no error.

---

## 9. Wiring the includes

**`resources/views/layouts/parts/js.blade.php`** — add near the end, after `main.js` and `@include('partials.wishlist')`, before `@stack('js')` closes out:
```blade
@include('common.parts.tour')
```

**`resources/views/layouts/parts/head.blade.php`** — the project bundles CSS via `App\Support\CssBundle`. Two options:
- Simplest: the partial in §6 already emits the `driver.css` `<link>` inline per page — no head change needed.
- Cleaner: add `assets/vendor/driverjs/driver.css` to the `CssBundle` list and a small override block in `custom.css`. Only do this if you want the CSS in the concatenated bundle.

**Optional CSS polish — add to `public/front/assets/css/custom.css`:**
```css
/* Match tour buttons to the brand (price = brand accent used on the search button). */
.driver-popover.dyafa-theme .driver-popover-next-btn { background: var(--price, #f7bb8e); color:#fff; border:0; }
.driver-popover .driver-popover-title { font-weight: 700; }
html[dir="rtl"] .driver-popover { text-align: right; }
```
If you use the theme class, pass `popoverClass: 'dyafa-theme'` in the `driver({...})` config in `tour.js`.

---

## 10. RTL notes

- Page `dir` is already set by the layout (`dir="rtl"` for Arabic) — driver.js text inherits it.
- The only manual step is arrow direction for horizontally-placed popovers: handled by `flipSide()` in `tour.js` (swaps `left`/`right` when `isRtl`).
- Verify the account tour (D) especially — it uses `side:'right'`, which becomes `left` in Arabic.
- Test both locales: `/en` and `/ar`.

---

## 11. Persistence options

**Default (this plan):** `localStorage` key `dyafa_tour_<key>_v<version>`.
- Pros: zero backend, works for guests.
- Cons: per-device/per-browser; cleared with site data.

**Optional upgrade (logged-in users):** persist completion server-side so it follows the account across devices.
- Add a nullable JSON column `tour_state` to the `customers` table (migration — **you run it**, I only write the file).
- On `onDestroyed`, POST to a new route `customer.tour.complete` with the tour key; store `{home:1, details:1, ...}`.
- In the Blade partial, seed `window.DyafaTour.seenServer` from the customer model so logged-in users don't re-see a tour they finished elsewhere.
- Keep localStorage as the guest fallback.

> Per project rules, **do not run** any migration — if you add the column, I'll hand you the exact `php artisan migrate` command to run yourself.

---

## 12. Triggering summary

| Trigger | Behavior |
|---|---|
| First visit to a page with a tour | Auto-runs once (1.2s after `load`), then marks seen. |
| "?" button in header (`data-tour-start`) | Re-runs the current page's tour anytime. |
| `window.DyafaTour.run()` | Manual/console run. |
| `window.DyafaTour.reset()` | Clears "seen" for the current page's tour (for QA). |
| Bump `version` in the partial | All users see the updated tour once more. |

---

## 13. Build & deploy steps

1. Download & commit driver.js `driver.css` + `driver.js.iife.js` to `public/front/assets/vendor/driverjs/`.
2. Create `tour.js`, `common/parts/tour.blade.php`, `resources/lang/{en,ar}/tour.php`.
3. Add `data-tour` anchors to the views in §8.
4. Add `@include('common.parts.tour')` to `js.blade.php`.
5. If you added CSS to `custom.css`/CssBundle, rebuild Tailwind: **ask the user to run** `npm run build:front` (do not run formatters/builds silently). The tour itself needs no Tailwind classes, so a rebuild is only required if you touched bundled CSS.
6. Clear view/lang cache if needed: the user runs `php artisan optimize:clear`.

> No new PHP dependencies, no Composer changes, no DB changes in the default plan.

---

## 14. QA checklist

- [ ] Guest, EN `/en`: home tour auto-runs once, shows login step, doesn't re-run on reload.
- [ ] Guest, AR `/ar`: text is Arabic, arrows point correctly (RTL), tooltips don't overflow viewport.
- [ ] Logged-in: home tour shows the **account** step instead of login.
- [ ] Property page: date-picker + price + book steps anchor correctly; skips reviews step when a property has no reviews.
- [ ] "?" button re-runs the tour even after it was dismissed.
- [ ] Mobile (`lg:hidden` header): anchors exist or steps skip gracefully (the mobile header has different markup — verify nav anchors or add mobile-specific anchors).
- [ ] Skip/close marks the tour seen (no nag on next load).
- [ ] No JS console errors when driver.js is missing/blocked (guarded by the `typeof window.driver === 'undefined'` check).

---

## 15. Effort estimate

| Task | Est. |
|---|---|
| Vendor driver.js + partial + tour.js | 1–2h |
| Lang files (copy already in §5) | 30m |
| Anchors across views | 1–2h |
| RTL + brand CSS polish | 1h |
| QA both locales, guest + logged-in, mobile | 1–2h |
| **Total** | **~5–8h** |

Optional server-side persistence (§11): +2–3h.
