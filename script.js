const firebaseConfig = {
  apiKey: "AIzaSyBT7SOtJquiu6YCWmiMEyqp5KxLnwtJXBs",
  authDomain: "canlisohbetproj.firebaseapp.com",
  databaseURL: "https://canlisohbetproj-default-rtdb.firebaseio.com",
  projectId: "canlisohbetproj",
  storageBucket: "canlisohbetproj.firebasestorage.app",
  messagingSenderId: "823447599986",
  appId: "1:823447599986:web:373833f729df42d187fbeb",
  measurementId: "G-6ZL71MSCPQ"
};

firebase.initializeApp(firebaseConfig);
const db = firebase.database();

db.ref("damarfm").on("value", (snap) => {
  const data = snap.val();
  if (data) {
    document.getElementById("artist").textContent = data.artist || "-";
    document.getElementById("title").textContent = data.title || "-";
  }
});