// sw.js - Fixed Service Worker for your structure
const CACHE_NAME = 'fittrack-v1';
const APP_SHELL = [
  // Root files
  '/fitness-tracker/',
  '/fitness-tracker/index.php',
  '/fitness-tracker/login.php',
  '/fitness-tracker/register.php',
  '/fitness-tracker/dashboard.php',
  '/fitness-tracker/profile.php',
  '/fitness-tracker/manifest.json',
  
  // CSS files (only if they exist)
  '/fitness-tracker/assets/css/style.css',
  
  // JS files (only if they exist)
  '/fitness-tracker/assets/js/main.js',
  '/fitness-tracker/assets/js/chart-config.js',
  
  // Includes (for offline functionality)
  '/fitness-tracker/includes/header.php',
  '/fitness-tracker/includes/footer.php',
  
  // Images
  '/fitness-tracker/assets/img/favicon-16x16.png',
  '/fitness-tracker/assets/img/favicon-32x32.png'
];

const EXTERNAL_RESOURCES = [
  // Bootstrap CSS
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  // Bootstrap JS Bundle
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  // Font Awesome
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  // Google Fonts
  'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap',
  // Chart.js (use specific version)
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

self.addEventListener('install', event => {
  console.log('[Service Worker] Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[Service Worker] Caching app shell...');
        
        // Cache app shell with error handling
        const appShellPromises = APP_SHELL.map(url => {
          return fetch(url)
            .then(response => {
              if (response.ok) {
                return cache.put(url, response);
              }
              console.warn(`[SW] Failed to cache: ${url} (Status: ${response.status})`);
              return Promise.resolve();
            })
            .catch(error => {
              console.warn(`[SW] Error caching ${url}:`, error.message);
              return Promise.resolve();
            });
        });
        
        // Cache external resources
        const externalPromises = EXTERNAL_RESOURCES.map(url => {
          return fetch(url)
            .then(response => {
              if (response.ok) {
                return cache.put(url, response);
              }
              return Promise.resolve();
            })
            .catch(error => {
              console.warn(`[SW] Error caching external ${url}`);
              return Promise.resolve();
            });
        });
        
        return Promise.all([...appShellPromises, ...externalPromises]);
      })
      .then(() => {
        console.log('[Service Worker] App shell cached');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('[Service Worker] Installation failed:', error);
      })
  );
});

self.addEventListener('activate', event => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    })
    .then(() => {
      console.log('[Service Worker] Claiming clients');
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', event => {
  // Skip non-GET requests and Chrome extensions
  if (event.request.method !== 'GET' || 
      event.request.url.startsWith('chrome-extension://')) {
    return;
  }
  
  // Skip API calls and dynamic content
  if (event.request.url.includes('/api/') ||
      event.request.url.includes('clear-meals.php') ||
      event.request.url.includes('delete-meal.php') ||
      event.request.url.includes('nutrition.php') ||
      event.request.url.includes('save-meal.php') ||
      event.request.url.includes('workout-') ||
      event.request.url.includes('log-weight.php') ||
      event.request.url.includes('/uploads/')) {
    return fetch(event.request);
  }
  
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        // Return cached response if found
        if (cachedResponse) {
          // Update cache in background
          fetchAndCache(event.request);
          return cachedResponse;
        }
        
        // Otherwise fetch from network
        return fetch(event.request)
          .then(response => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // Cache the response
            const responseToCache = response.clone();
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
            
            return response;
          })
          .catch(error => {
            console.log('[SW] Fetch failed; returning offline page:', error);
            
            // If requesting an HTML page, return offline page
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/fitness-tracker/');
            }
            
            // For CSS/JS, return a fallback
            if (event.request.url.endsWith('.css')) {
              return new Response('/* Offline CSS */', {
                headers: { 'Content-Type': 'text/css' }
              });
            }
            
            if (event.request.url.endsWith('.js')) {
              return new Response('// Offline JS', {
                headers: { 'Content-Type': 'application/javascript' }
              });
            }
            
            return new Response('You are offline.');
          });
      })
  );
});

// Helper function to update cache in background
function fetchAndCache(request) {
  return fetch(request)
    .then(response => {
      if (response.ok) {
        const responseClone = response.clone();
        caches.open(CACHE_NAME)
          .then(cache => cache.put(request, responseClone));
      }
      return response;
    })
    .catch(() => {
      // Silently fail for background updates
    });
}

// Handle messages from main thread
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});