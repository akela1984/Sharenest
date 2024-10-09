self.addEventListener('install', event => {
  event.waitUntil(
    caches.open('sharenest-cache').then(cache => {
      return cache.addAll([
        '/',
        '/index.php',
        '/css/styles.css',
        '/js/scripts.js'
      ]);
    })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});
