// STU Key Management Service Worker
const CACHE_NAME = 'stu-keys-v1.0.0';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
    '/offline',
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    if (event.request.method !== 'GET') return;
    
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }

                return fetch(event.request)
                    .then(function(response) {
                        // Check if we received a valid response
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response
                        var responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(function() {
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline');
                        }
                    });
            }
        )
    );
});

self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(
            syncPendingActions()
        );
    }
});

async function syncPendingActions() {
    // Get pending actions from IndexedDB
    const pendingActions = await getPendingActions();
    
    for (const action of pendingActions) {
        try {
            const response = await fetch(action.url, {
                method: action.method,
                headers: action.headers,
                body: action.body
            });
            
            if (response.ok) {
                await removePendingAction(action.id);
            }
        } catch (error) {
            console.log('Sync failed for action:', action.id, error);
        }
    }
}

// Background sync for periodic updates
self.addEventListener('periodicsync', function(event) {
    if (event.tag === 'content-update') {
        event.waitUntil(updateCachedContent());
    }
});

async function updateCachedContent() {
    const cache = await caches.open(CACHE_NAME);
    const requests = await cache.keys();
    
    for (const request of requests) {
        try {
            const networkResponse = await fetch(request);
            if (networkResponse.ok) {
                await cache.put(request, networkResponse);
            }
        } catch (error) {
            console.log('Failed to update:', request.url);
        }
    }
}

// IndexedDB for offline actions
function getPendingActions() {
    return new Promise((resolve) => {
        const request = indexedDB.open('StuKeysOffline', 1);
        
        request.onupgradeneeded = function(event) {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pendingActions')) {
                db.createObjectStore('pendingActions', { keyPath: 'id' });
            }
        };
        
        request.onsuccess = function(event) {
            const db = event.target.result;
            const transaction = db.transaction(['pendingActions'], 'readonly');
            const store = transaction.objectStore('pendingActions');
            const getAll = store.getAll();
            
            getAll.onsuccess = function() {
                resolve(getAll.result);
            };
        };
        
        request.onerror = function() {
            resolve([]);
        };
    });
}

function removePendingAction(id) {
    return new Promise((resolve) => {
        const request = indexedDB.open('StuKeysOffline', 1);
        
        request.onsuccess = function(event) {
            const db = event.target.result;
            const transaction = db.transaction(['pendingActions'], 'readwrite');
            const store = transaction.objectStore('pendingActions');
            const deleteReq = store.delete(id);
            
            deleteReq.onsuccess = function() {
                resolve(true);
            };
            
            deleteReq.onerror = function() {
                resolve(false);
            };
        };
    });
}
