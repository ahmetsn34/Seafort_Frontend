# Seafort 1.3.1 — kurulum ve geliştirme

Gereksinimler: **PHP 8.2+**, `fileinfo`, `json` ve `session` desteği. Veritabanı, Composer veya npm kurulumu gerekmez.

## Hızlı başlangıç

Repo kökünden çalıştırın:

```sh
php website/tools/setup.php
php -S 127.0.0.1:8082 -t website/public_html website/tools/router.php
```

Adres: <http://127.0.0.1:8082>. PHP dosyaları anlık okunur; HTML, CSS ve JavaScript değişikliklerinden sonra sayfayı yenileyin. Ayrı bir build adımı yoktur.

Windows’ta PHP komutu tanınmıyorsa XAMPP yolu ile:

```powershell
& 'C:\xampp\php\php.exe' website/tools/setup.php
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8082 -t website/public_html website/tools/router.php
```

`setup.php`, `config.example.php` üzerinden yerel ayar dosyasını ve `seed` klasöründeki yayımlanmış içeriklerden başlangıç verilerini oluşturur. Yeniden çalıştırıldığında mevcut dosyaları korur.

## Yönetici hesabı

```sh
php website/seafort/bin/create-admin.php --username seafort-editor
```

Şifrenizi terminal sorunca kendiniz belirleyin. Hazır hesap veya parola yoktur. Giriş: <http://127.0.0.1:8082/admin/>. Windows’ta aynı komutu yukarıdaki XAMPP PHP yolu ile çalıştırabilirsiniz.

- **Sayfa içerikleri:** Sustainability dahil mevcut sayfaların metin ve görselleri.
- **Broşürler / PDF:** Genel veya sayfaya özel, en fazla 10 MB PDF. PDF yokken indirme düğmesi pasiftir.
- **İletişim ve WhatsApp:** Genel e-posta, sosyal bağlantılar, WhatsApp numarası ve hazır mesaj. Numara boşsa destek düğmesi gizlenir.
- Blog, projeler ve slider ayrı bölümlerden yönetilir. Kaldırılan Our People editörü kapalıdır.

Diğer dil seçenekleri sürümle birlikte gelen çeviri kataloglarını kullanır. Sonradan düzenlenen İngilizce metinlerin çevirileri `public_html/assets/locales` içinde ayrıca güncellenmelidir.

## Sunucuya kurulum

Klasörleri kardeş olarak yerleştirin:

```text
domain-root/
├── public_html/       Web kökü
└── seafort/           Özel uygulama, içerik ve yönetici hesabı
```

`seafort` klasörü web kökünün dışında kalmalıdır. `seafort/storage` ve `public_html/uploads` PHP kullanıcısı tarafından yazılabilir olmalıdır. Önerilen başlangıç izinleri: storage `0700`, JSON dosyaları `0600`, uploads `0755`.

Yeni kurulumda `tools/setup.php` çalıştırın veya örnek ayar/başlangıç dosyalarını uygun konumlara kopyalayın. Alan adını `seafort/app/config.php`, `public_html/.htaccess` ve `public_html/robots.txt` içinde hedef sunucuya göre düzenleyin. `.htaccess` HTTPS ve canonical alan adı yönlendirmesi içerir; yerel PHP sunucusu bu dosyayı kullanmaz. Test alt alanında `indexable` kapalı kalmalıdır.

Mevcut site güncellenirken canlı `config.php`, `seafort/storage` ve `public_html/uploads` korunmalıdır. Başlangıç verileri canlı içeriklerin üzerine kopyalanmaz. Bu repo Hostinger’e otomatik dağıtım yapmaz.

## Dosyalar

```text
public_html/           Ziyaretçiye açık giriş dosyaları ve statik medya
seafort/app/           PHP uygulaması ve sayfa şablonları
seafort/bin/           Terminalden yönetici oluşturma aracı
seafort/seed/          Yayımlanmış başlangıç içerikleri
seafort/storage/       Yerelde/sunucuda oluşur; Git dışında tutulur
tools/setup.php       Mevcut içeriği koruyan ilk kurulum
tools/router.php      Yerel PHP geliştirme sunucusu yönlendirmesi
```

Tanıtım videosu ve görseller repoya dahildir. Yönetici bilgileri, canlı iletişim ayarları, özel çalışan kayıtları, yayımlanmamış yazılar ve yüklenen PDF/görseller dahil değildir. Teklif ve kariyer sayfaları e-posta taslağı hazırlar; SMTP gönderimi bağlı değildir.
