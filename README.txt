=== RadioTheme v2.0 ===

Theme URI:         https://radio.apphux.com
Author:            Apphux
Requires at least: 6.0
Tested up to:      6.7
Requires PHP:      8.0
License:           GPLv2 or later
Text Domain:       radiotheme

== Gerekli Eklentiler ==

* Advanced Custom Fields (Free)  — ZORUNLU
* Yoast SEO Premium              — Önerilen (tam SEO için)
* Polylang                       — Önerilen (çokdil: EN, TR, DE, FR, ES)

== Kurulum ==

1. ZIP'i /wp-content/themes/ içine çıkar
2. WordPress'ten tema olarak etkinleştir
3. ACF eklentisini kur ve etkinleştir
4. Yoast SEO Premium kur (önerilen)
5. Polylang kur (önerilen)
6. Appearance → Menus → Primary ve Footer menülerini oluştur
7. Radio Stations → Import Stations ile RadioBrowser.info'dan toplu aktarım yap
   VEYA Radio Stations → Add New ile manuel ekle
8. Settings → Permalinks → Kaydet (rewrite flush)

== Özellikler ==

* Özel İçerik Türü: radio-station (slug: /station/...)
* Taksonomiler: Tür (radio-genre), Ülke (radio-country), Şehir (radio-city), Dil (radio-language)
* HTML5 Audio Player (AJAX sayfa geçişlerinde kesilmez)
* AJAX filtreleme + sonsuz kaydırma (sayfa yenilemesiz)
* RadioBrowser.info API toplu aktarım (admin paneli)
* Haftalık otomatik senkronizasyon (WP-Cron, isteğe bağlı)
* 3 sütunlu layout: İstasyon listesi | Sol sidebar | Sağ sidebar
* 5 reklam bölgesi: 300×250 (×2), 300×600, 160×600, 300×250
* Yoast SEO Premium tam entegrasyon
  - Otomatik meta açıklamaları
  - Otomatik sayfa başlıkları
  - Schema.org: BroadcastService, WebSite, ItemList
  - Sitemap: CPT ve tüm taksonomiler
  - Breadcrumb
* Polylang çokdil (5 dil)
* GeoIP tespiti (Cloudflare header destekli)
* WCAG 2.1 AA erişilebilirlik
* Performans: lazy images, deferred JS, temiz wp_head

== Changelog ==

= 2.0.0 =
* Sıfırdan yeniden yazıldı
* Orijinal tasarım birebir korundu (list-row kartlar, koyu player)
* Detay sayfası (single-radio-station.php) tamamen çalışır hale getirildi
* Polylang 5 dil desteği
* Yoast SEO Premium tam entegrasyon
* RadioBrowser.info API import paneli
* Schema.org BroadcastService yapılandırılmış veri
