<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__);
$storage=$root.'/seafort/storage';
if (!is_dir($storage) && !mkdir($storage,0700,true) && !is_dir($storage)) {
    fwrite(STDERR,"İçerik klasörü oluşturulamadı.\n");exit(1);
}
$created=0;
$config=$root.'/seafort/app/config.php';
if (!file_exists($config)) {
    if (!copy($root.'/seafort/app/config.example.php',$config)) { fwrite(STDERR,"Ayar dosyası oluşturulamadı.\n");exit(1); }
    $created++;
}
foreach (glob($root.'/seafort/seed/*.json') ?: [] as $seed) {
    $target=$storage.'/'.basename($seed);
    if (file_exists($target)) continue;
    json_decode((string)file_get_contents($seed),true,64,JSON_THROW_ON_ERROR);
    if (!copy($seed,$target)) { fwrite(STDERR,"Başlangıç içeriği kopyalanamadı.\n");exit(1); }
    @chmod($target,0600);$created++;
}
echo "Kurulum hazır. $created yeni dosya oluşturuldu; mevcut ayarlar ve içerikler korundu.\n";
