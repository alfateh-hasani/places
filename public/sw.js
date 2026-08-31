// Web Push service worker for the Places web app (staff dashboard + customer web).
// Independent of the mobile app's FCM notifications.
const SW_VERSION = '1.0.0';
const DEFAULT_ICON = '/img/places-logo-dark.png';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Incoming push payload (sent by minishlink/web-push via the WebPush channel).
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload = {};
    try {
        payload = event.data.json();
    } catch (e) {
        payload = { title: event.data.text() };
    }

    const title = payload.title || 'Places';
    const options = {
        body: payload.body || '',
        icon: payload.icon || DEFAULT_ICON,
        badge: payload.badge || DEFAULT_ICON,
        dir: 'auto',
        vibrate: [200, 100, 200],
        tag: payload.tag || undefined,
        renotify: false,
        data: {
            url: (payload.data && payload.data.action_url) || payload.action_url || '/',
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Focus an existing tab on the target URL, or open a new one.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});
