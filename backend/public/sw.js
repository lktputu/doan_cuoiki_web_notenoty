const CACHE_NAME = 'note-app-cache-v1';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/offline' // Trang dự phòng khi mất mạng
];

// 1. Khi cài đặt: Lưu các file quan trọng vào Cache
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Đã mở bộ nhớ đệm (Cache)');
                return cache.addAll(urlsToCache);
            })
    );
});

// 2. Bắt các yêu cầu mạng: Lấy từ Internet, nếu mất mạng thì lấy từ Cache
self.addEventListener('fetch', event => {
    // Chỉ can thiệp các lệnh GET (Tải trang), không can thiệp lệnh POST (Lưu, Sửa, Xóa)
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request).then(response => {
                // Trả về file từ Cache, nếu không có thì trả về trang /offline
                return response || caches.match('/offline');
            });
        })
    );
});