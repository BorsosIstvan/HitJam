const CACHE_NAME = 'php-app-cache-v5'; // Verhoogd naar v5
const urlsToCache = [
  '/HitJam/',
  '/HitJam/index.php',
  '/HitJam/manifest.json'
];

// Installeer de service worker en sla basisbestanden op
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(urlsToCache);
    })
  );
});

// Zorg dat de app werkt, zelfs met een trage verbinding
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});
