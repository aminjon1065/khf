self.addEventListener('push', (event) => {
    if (self.Notification?.permission !== 'granted' || !event.data) {
        return;
    }

    event.waitUntil(
        Promise.resolve()
            .then(() => event.data.json())
            .then((message) =>
                self.registration.showNotification(message.title, {
                    body: message.body,
                    icon: message.icon || '/images/pwa-192.png',
                    badge: '/images/pwa-192.png',
                    actions: message.actions,
                    data: message.data,
                }),
            )
            .catch(() => undefined),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = event.notification.data?.url;

    if (!target) {
        return;
    }

    const targetUrl = new URL(target, self.location.origin).href;

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then(async (clients) => {
                const existingClient = clients.find(
                    (client) => client.url === targetUrl,
                );

                if (existingClient) {
                    return existingClient.focus();
                }

                return self.clients.openWindow(targetUrl);
            }),
    );
});
