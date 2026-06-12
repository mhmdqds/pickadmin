importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyDdEJeUfZV2yq3Lpz7Pa-1LvjNwxadkn8Y",
    authDomain: "pikeles.firebaseapp.com",
    projectId: "pikeles",
    storageBucket: "pikeles.firebasestorage.app",
    messagingSenderId: "661936871581",
    appId: "1:661936871581:web:fe06362ae1209e5f715c74",
    measurementId: "G-9MVSVCXSDB"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) {
    return self.registration.showNotification(payload.data.title, {
        body: payload.data.body ? payload.data.body : '',
        icon: payload.data.icon ? payload.data.icon : ''
    });
});