// firebase-messaging-sw.js
// Service worker for Firebase background push notifications
// Place this file at: /customer-app/firebase-messaging-sw.js

importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// Config loaded dynamically from server - fallback to empty
var firebaseConfig = self._fcmConfig || {};

if (firebaseConfig.apiKey) {
  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  messaging.onBackgroundMessage(function(payload) {
    const title = payload.notification?.title || 'Hridya Tech';
    const body  = payload.notification?.body  || 'New notification';
    const icon  = '/customer-app/icon.png';

    self.registration.showNotification(title, {
      body:   body,
      icon:   icon,
      badge:  icon,
      data:   payload.data || {},
      vibrate: [200, 100, 200],
    });
  });
}

// Listen for clicks on notifications
self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('https://www.fixgrid.in/customer-app/customer.php')
  );
});
