# Seafort 1.4.0-preview

PHP 8.2+ ve `fileinfo`, `json`, `session`, `openssl` eklentileri gerekir. Veritabanı, npm veya ayrı build adımı yoktur. SMTP kütüphanesi paketle birlikte gelir.

## Çalıştırma

Windows’ta **START-PREVIEW.cmd** dosyasını çift tıklayın. PHP önce PATH’te, sonra `C:\xampp\php\php.exe` konumunda aranır. Terminal açık kaldığı sürece site <http://127.0.0.1:8123> adresindedir; durdurmak için **Ctrl+C** kullanın.

PowerShell’den başka port veya yalnızca kurulum kontrolü:

```powershell
& .\website\tools\start-preview.ps1 -Port 8125
& .\website\tools\start-preview.ps1 -CheckOnly
```

Manuel komutlar, repo kökünden:

```sh
php website/tools/setup.php
php website/tools/migrate-forms.php
php -S 127.0.0.1:8123 -t website/public_html website/tools/router.php
```

PATH’te PHP yoksa komutlarda `php` yerine PowerShell’de `& 'C:\xampp\php\php.exe'` kullanın. Kod/CSS değişiklikleri için sayfayı yenilemeniz yeterlidir. Başlatıcı yalnızca kendi işlemi için PowerShell yürütme seçeneği kullanır; bilgisayarın genel güvenlik politikasını değiştirmez.

`setup.php` eksik ayar ve başlangıç dosyalarını oluşturur; mevcut dosyaları korur. `migrate-forms.php` kaldırılan ekip rehberine ait iki eski cümleyi günceller ve eksik başlangıç proje taslağını yalnızca aynı kayıt yoksa ekler. Yönetici hesabı oluşturulmaz, parola sıfırlanmaz. Mevcut projelerin üzerine yeni seed içeriği yazılmaz.

## Yönetim paneli

Giriş: <http://127.0.0.1:8123/admin/>. İlk kurulumda hesap oluşturmak için:

```sh
php website/seafort/bin/create-admin.php --username seafort-editor
```

Parolayı terminal istediğinde belirleyin; hazır hesap/parola yoktur. Mevcut hesabınız varsa bu adımı atlayın.

| Bölüm | Yönetilen içerik |
| --- | --- |
| Formlar ve başvurular | Alıcılar, form metinleri, ek dosya sınırları, özel gelen kutusu, SMTP ve önizleme/canlı modu |
| Projeler | İhtiyaç, Seafort kapsamı, yöntem, doğrulanmış sonuç, fotoğraf açıklamaları ve yayın onayı |
| Belgeler | Broşür, sertifika, kaynak dokümanı ve sürdürülebilirlik PDF’leri; dil, sürüm ve yayın izni |
| Referanslar | İzinli firma logoları, ilişki türü ve müşteri alıntıları |
| Çeviriler | İngilizce kaynak metinlerin diğer dillerdeki karşılıkları |
| Sayfalar / genel ayarlar | Metinler, görseller, iletişim, WhatsApp ve sosyal hesaplar |

Blog, ana sayfa slider’ı ve sayfa/projeye özel broşürler de panelden yönetilir. Ege Deniz içeriği ve Osmangazi vakası eksik şirket bilgileri tamamlanmadan hazır referans olarak yayımlanmaz. Tarihsel çalışan/deneyim rakamları ayrıca teyit gerektirir; onaysız sayılar gösterilmez.

## Formlar ve e-posta

- Varsayılan **önizleme modu**, başvuruyu özel gelen kutusuna kaydeder ve **e-posta göndermez**.
- Teklif alıcısı `contact@seafortservice.com`, kariyer alıcısı `careers@seafortservice.com`; ikisi ayrı ayrı değiştirilebilir.
- SMTP hesabını ve parolasını yönetim panelinden girin. Parola ziyaretçilere gösterilmez; özel storage içinde tutulur.
- Canlı moda geçmeden alıcıları, gönderici hesabını ve gerçek teslimi kontrollü bir testle doğrulayın. SMTP sunucusunun kabulü, alıcının gelen kutusuna teslimin kanıtı değildir. Bu sürüm için gerçek dış teslim testi tamamlandı iddiası yoktur.
- CV ve teklif ekleri web kökü dışında tutulur; yönetici girişi olmadan indirilemez. Uygulanan dosya türü/boyut sınırları panelden yönetilir.

## İçerik ve sunucuya aktarım

Yeni vakalar temel bilgiler, ana görsel ve yayın onayı tamamlanmadan yayımlanamaz. Taslak önizlemesi yalnızca yöneticilere açıktır. Osmangazi başlangıç taslağında sonuç, proje tarihi, müşteri ve fotoğraf uydurulmamıştır. Belge veya referans dosyası bulunmayan alanlar gerçek içerik gelene kadar boş kalır.

Sunucuda `public_html` web kökü, `seafort` ise bunun dışında kardeş klasör olmalıdır. CLI yardımcıları `tools` klasöründe bulunur. Mevcut kurulum güncellenirken **config.php, seafort/storage ve public_html/uploads korunmalıdır**; seed dosyaları canlı içeriklerin üzerine kopyalanmaz.

Sunucuda dosyalar yerleştirildikten sonra `php seafort/bin/upgrade-preview.php` komutu eski rehber atıflarını ve eksik proje taslağını mevcut içerikleri koruyarak günceller.

Alan adını `seafort/app/config.php`, `.htaccess` ve `robots.txt` içinde hedef ortama göre düzenleyin. Önizlemede indeksleme kapalı kalmalıdır. Yerel PHP sunucusu `.htaccess` kurallarını kullanmaz.

Özel ayarlar, yönetici bilgileri, başvurular, SMTP parolası ve kullanıcı yüklemeleri Git dışında tutulur. Bu sürüm yerel önizlemedir; Hostinger’e, WordPress’e veya GitHub Pages’e otomatik yayın yapmaz.
