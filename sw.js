// sw.js in root
const CACHE = 'fittrack-v1';
const FILES = [
  '/', '/index.php', '/dashboard.php',
  '/assets/css/style.css',
  '/assets/js/main.js',
  '/assets/js/chart-config.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/chart.js'
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(cache => cache.addAll(FILES))
  );
});

self.addEventListener('fetch', e => {
  e.respondWith(
    caches.match(e.request).then(response => response || fetch(e.request))
  );
});