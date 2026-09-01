/**
 * Web Push subscription bootstrap for the Places web app.
 * Reads config from window.WebPushConfig (injected by the Blade partial):
 *   { vapidPublicKey, subscribeUrl, unsubscribeUrl, csrf }
 * Registers the service worker, asks for notification permission, subscribes to
 * the browser Push service, and stores the subscription server-side.
 */
(function () {
    'use strict';

    var config = window.WebPushConfig || {};

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return; // Browser has no web push support (e.g. iOS Safari < 16.4).
    }

    if (!config.vapidPublicKey || !config.subscribeUrl) {
        return;
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(base64);
        var output = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; ++i) {
            output[i] = raw.charCodeAt(i);
        }
        return output;
    }

    async function storeSubscription(subscription) {
        var json = subscription.toJSON();
        await fetch(config.subscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: json.keys,
                contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
            }),
        });
    }

    async function subscribe(registration) {
        var existing = await registration.pushManager.getSubscription();
        if (existing) {
            await storeSubscription(existing); // keep server in sync (endpoints rotate)
            return;
        }

        var subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(config.vapidPublicKey),
        });

        await storeSubscription(subscription);
    }

    async function promptAndSubscribe(registration) {
        var permission = await Notification.requestPermission();
        if (permission === 'granted') {
            await subscribe(registration);
        }
    }

    async function init() {
        try {
            var registration = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            if (Notification.permission === 'granted') {
                await subscribe(registration); // already allowed → keep server in sync
                return;
            }

            if (Notification.permission === 'denied') {
                return; // user blocked it; can only be changed from browser settings
            }

            // permission === 'default': Chrome suppresses prompts fired on page load
            // (quieter UI). Request on the first real user gesture instead, which is
            // the reliable way to surface the native prompt.
            var onGesture = function () {
                document.removeEventListener('pointerdown', onGesture);
                document.removeEventListener('keydown', onGesture);
                promptAndSubscribe(registration);
            };
            document.addEventListener('pointerdown', onGesture, { once: true });
            document.addEventListener('keydown', onGesture, { once: true });
        } catch (e) {
            // Never let a push failure break the page.
            if (window.console) {
                console.warn('[web-push] init failed:', e);
            }
        }
    }

    window.addEventListener('load', init);
})();
