const icy = require("icy");
const admin = require("firebase-admin");
const serviceAccount = require("./serviceAccountKey.json");

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
  databaseURL: "https://canlisohbetproj-default-rtdb.firebaseio.com"
});

const db = admin.database();
const streamUrl = "https://yayin.damarfm.com/dinle/stream";

function updateNowPlaying() {
  icy.get(streamUrl, function (res) {
    res.on("metadata", function (metadata) {
      const parsed = icy.parse(metadata);
      const streamTitle = parsed.StreamTitle || "";
      let [artist, title] = streamTitle.split(" - ");
      artist = artist ? artist.trim() : "Bilinmiyor";
      title = title ? title.trim() : "Bilinmiyor";

      db.ref("damarfm").set({
        artist,
        title,
        timestamp: Date.now()
      });
      console.log("Güncellendi:", artist, "-", title);
    });
  });
}

setInterval(updateNowPlaying, 30000);
updateNowPlaying();
