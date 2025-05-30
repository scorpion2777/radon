const icy = require("icy");
const fetch = require("node-fetch");

const streamUrl = "https://yayin.damarfm.com/dinle/stream";
const firebaseUrl = "https://canlisohbetproj-default-rtdb.firebaseio.com/damarfm.json";

function updateNowPlaying() {
  icy.get(streamUrl, function (res) {
    res.on("metadata", function (metadata) {
      const parsed = icy.parse(metadata);
      const streamTitle = parsed.StreamTitle || "";
      let [artist, title] = streamTitle.split(" - ");
      artist = artist ? artist.trim() : "Bilinmiyor";
      title = title ? title.trim() : "Bilinmiyor";

      const data = {
        artist,
        title,
        timestamp: Date.now()
      };

      fetch(firebaseUrl, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      })
      .then(res => res.text())
      .then(txt => console.log("Güncellendi:", artist, "-", title))
      .catch(err => console.error("Firebase hatası:", err));
    });
  });
}

setInterval(updateNowPlaying, 30000);
updateNowPlaying();