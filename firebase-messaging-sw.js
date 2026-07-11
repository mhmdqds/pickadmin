importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyCXgsQ0vyOq3eTNX5iW1N2AurhYMNDUvlY",
    authDomain: "pikelsstore.firebaseapp.com",
    projectId: "pikelsstore",
    storageBucket: "pikelsstore.firebasestorage.app",
    messagingSenderId: "645849078423",
    appId: "1:645849078423:web:05b798b020b113a654e7d2",
    measurementId: "G-Q3M4YPVXEN"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) {
    return self.registration.showNotification(payload.data.title, {
        body: payload.data.body ? payload.data.body : '',
        icon: payload.data.icon ? payload.data.icon : ''
    });
});