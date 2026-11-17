# Seafort Website

Seafort’un bağımsız PHP web sitesi ve içerik yönetimi. **1.5.0-preview** sürümü [website](website/) klasöründedir; bu sürüm yerel inceleme için hazırlanmıştır.

- Ayrı teklif ve kariyer formları, özel dosya ekleri ve yönetici gelen kutusu.
- Yapılandırılabilir SMTP; varsayılan önizleme modunda e-posta gönderilmez.
- Proje vakaları, açıklamalı fotoğraflar ve yöneticiye özel taslak önizlemesi.
- Broşür/rapor/sertifika merkezi, referanslar ve çeviri yönetimi.
- Blog, slider, sayfa içerikleri, WhatsApp ve sosyal medya ayarları.
- İngilizce ana dil; Türkçe, Arapça, Rusça ve sadeleştirilmiş Çince seçenekleri.

## Yerelde açma

Windows’ta **`website/START-PREVIEW.cmd`** dosyasını çalıştırın. Başlatıcı PHP’yi önce PATH’te, ardından `C:\xampp\php\php.exe` konumunda arar. Adres: <http://127.0.0.1:8123>.

Diğer sistemlerde, repo kökünden:

```sh
php website/tools/setup.php
php website/tools/migrate-forms.php
php -S 127.0.0.1:8123 -t website/public_html website/tools/router.php
```

[Kurulum, admin ve yayın notları](website/README.md)

Başlatıcı mevcut içerikleri veya yönetici hesabını sıfırlamaz. Kaynak, boş şirket verilerini uydurarak doldurmaz; Osmangazi örneği yayımlanmamış bir hazırlık taslağıdır. Gerçek dış e-posta teslimi henüz doğrulanmış değildir.

## Önceki statik site

Kökteki HTML/CSS/JavaScript dosyaları ve mevcut GitHub Pages yayını korunmuştur. PHP projesi GitHub Pages üzerinde çalışmaz. Bu önizleme, Hostinger veya ana alan adına otomatik dağıtım yapmaz.

[Önceki statik sürüm](docs/legacy-static-site.md)
