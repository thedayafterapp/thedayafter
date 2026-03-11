self.addEventListener('push', function (e) {
    const data = e.data ? e.data.json() : {};
    const title = data.title || 'TheDayAfter';
    const options = {
        body: data.body || '',
        icon: '/icon-192.png',
        badge: '/icon-192.png',
        data: { url: data.url || '/mood/history' },
    };
    e.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (e) {
    e.notification.close();
    e.waitUntil(clients.openWindow(e.notification.data.url));
});
