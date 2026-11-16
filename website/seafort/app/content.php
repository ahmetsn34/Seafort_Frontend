<?php
declare(strict_types=1);

/** Plain-text site content. File paths and links have explicit allowlists. */
function sf_content_defaults(string $type): array
{
    if ($type === 'settings') {
        return ['company' => 'Seafort Denizcilik A.Ş.', 'email' => 'contact@seafortservice.com', 'footer_tagline' => 'Marine and offshore repair and project support.', 'archive' => ['folder_url'=>'','note'=>''], 'whatsapp' => ['number'=>'','label'=>'WhatsApp support','message'=>'Hello Seafort, I would like to discuss a project.'], 'social' => [
            'linkedin_url' => 'https://www.linkedin.com/company/seafort-service/',
            'instagram_url' => 'https://www.instagram.com/seafortservice/',
            'youtube_url' => 'https://www.youtube.com/@SeafortService',
            'facebook_url' => 'https://www.facebook.com/share/1ETZDA9FHA/?mibextid=wwXIfr',
        ]];
    }
    if ($type === 'brochures') return sf_brochure_defaults();
    $file = match ($type) { 'home' => 'home-defaults.json', 'pages' => 'pages.json', default => throw new InvalidArgumentException('İçerik bölümü bulunamadı.') };
    $raw = file_get_contents(__DIR__ . '/' . $file);
    $data = $raw === false ? null : json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($data) || $data === []) throw new RuntimeException('Varsayılan içerik okunamadı.');
    return $data;
}

function sf_content_schema_merge(array $defaults, array $saved): array
{
    $result = [];
    foreach ($defaults as $key => $value) {
        $provided = $saved[$key] ?? null;
        $result[$key] = is_array($value) ? sf_content_schema_merge($value, is_array($provided) ? $provided : []) : (is_string($provided) ? $provided : $value);
    }
    return $result;
}

function sf_content_read(string $type): array
{
    $defaults = sf_content_defaults($type);
    return sf_content_schema_merge($defaults, sf_read_json($type . '.json', $defaults));
}

function sf_site_settings(): array { return sf_content_read('settings'); }
function sf_home_content(): array { return sf_content_read('home'); }
function sf_site_pages(): array { return sf_content_read('pages'); }

function sf_content_digest(array $data): string
{
    return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
}
function sf_content_revision(string $type): string { return sf_content_digest(sf_content_read($type)); }

function sf_content_link(string $value): string
{
    if ($value === '') return '';
    if (strlen($value) > 240 || str_contains($value, '..') || !preg_match('~\A(?:/[a-zA-Z0-9_/-]*(?:#[a-zA-Z0-9_-]+)?|#[a-zA-Z0-9_-]+)\z~D', $value) || str_starts_with($value, '//')) {
        throw new InvalidArgumentException('Bağlantılar sitedeki bir sayfaya yönelmelidir. Örnek: /projects/ veya #services.');
    }
    return $value;
}

/** Social links are optional HTTPS URLs limited to the selected platform. */
function sf_social_link(string $platform, string $value): string
{
    $value = trim($value);
    if ($value === '') return '';
    $hosts = ['linkedin'=>['linkedin.com','www.linkedin.com'], 'instagram'=>['instagram.com','www.instagram.com'], 'youtube'=>['youtube.com','www.youtube.com','youtu.be'], 'facebook'=>['facebook.com','www.facebook.com','m.facebook.com','fb.com','www.fb.com']];
    $parts = parse_url($value);
    if (strlen($value) > 500 || preg_match('/[\x00-\x20\x7f]/', $value) || !filter_var($value, FILTER_VALIDATE_URL) || !is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || !in_array(strtolower($parts['host'] ?? ''), $hosts[$platform] ?? [], true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
        throw new InvalidArgumentException('Sosyal medya bağlantısı, seçilen platformun https:// ile başlayan adresi olmalıdır. Boş bırakılan hesap sitede görünmez.');
    }
    return $value;
}

function sf_video_path(string $value): string
{
    $value = trim($value);
    if ($value === '') return '';
    if (str_contains($value, '..') || !preg_match('~\A/(?:uploads|assets)/[a-zA-Z0-9_/-]+\.mp4\z~D', $value) || !is_file(SF_PUBLIC_DIR . $value)) {
        throw new InvalidArgumentException('Video, siteye yüklenmiş bir MP4 dosyası olmalıdır. Dosyayı aşağıdaki video alanından yükleyin.');
    }
    return $value;
}

function sf_content_validate_tree(array $defaults, array $input, string $prefix = ''): array
{
    $result = [];
    if (array_diff(array_keys($input), array_keys($defaults))) throw new InvalidArgumentException('İçerik alanları tanınmadı. Sayfayı yenileyin.');
    foreach ($defaults as $key => $default) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
        if (!array_key_exists($key, $input)) throw new InvalidArgumentException('Bazı alanlar eksik geldi. Sayfayı yenileyip yeniden deneyin.');
        $value = $input[$key];
        if (is_array($default)) {
            if (!is_array($value)) throw new InvalidArgumentException('İçerik yapısı geçersiz.');
            $result[$key] = sf_content_validate_tree($default, $value, $path);
            continue;
        }
        if (!is_string($value) || !preg_match('//u', $value) || str_contains($value, "\0")) throw new InvalidArgumentException('Geçersiz metin biçimi.');
        $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
        if (sf_strlen($value) > 12000) throw new InvalidArgumentException('Bir metin alanı en fazla 12.000 karakter olabilir.');
        if ($key === 'slug' && $value !== $default) throw new InvalidArgumentException('Mevcut sayfaların adresi bu ekrandan değiştirilemez.');
        if ($key === 'email' && (!filter_var($value, FILTER_VALIDATE_EMAIL) || strlen($value) > 180)) throw new InvalidArgumentException('Geçerli bir iletişim e-posta adresi yazın.');
        if ($path === 'archive.folder_url') {
            if ($value!=='') {
                $parts=parse_url($value);
                if (!filter_var($value,FILTER_VALIDATE_URL) || ($parts['scheme']??'')!=='https' || !in_array(strtolower($parts['host']??''),['cavitechdenizcilik.sharepoint.com','cavitechdenizcilik-my.sharepoint.com'],true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) throw new InvalidArgumentException('Kurumsal SharePoint / OneDrive klasörünün HTTPS bağlantısını girin.');
            }
        }
        elseif ($path === 'whatsapp.number') {
            $value = preg_replace('/[\s()+-]/', '', $value);
            if ($value !== '' && !preg_match('/\A[1-9][0-9]{7,14}\z/D', $value)) throw new InvalidArgumentException('WhatsApp numarasını ülke koduyla yazın. Örnek biçim: 90 ile başlayan numara; boş bırakırsanız düğme gizlenir.');
        }
        elseif ($key === 'brochure_file') $value = sf_brochure_path($value);
        elseif (str_starts_with($path, 'social.') && is_string($key) && str_ends_with($key, '_url')) $value = sf_social_link(substr($key, 0, -4), $value);
        elseif ($key === 'video_url') $value = sf_video_path($value);
        elseif (is_string($key) && preg_match('/(?:^|_)(href|url)$/D', $key)) $value = sf_content_link($value);
        elseif (is_string($key) && preg_match('/(?:^|_)(poster|image|cover|photo)$/D', $key)) $value = sf_cover_path($value);
        if (in_array($key, ['title', 'company', 'email'], true) && $value === '') throw new InvalidArgumentException('Başlık ve iletişim alanları boş bırakılamaz.');
        $result[$key] = $value;
    }
    if (isset($result['stats_review'])) {
        $review=$result['stats_review'];
        if (!in_array($review['status'], ['pending','approved'], true)) throw new InvalidArgumentException('Rakamlar için geçerli bir teyit durumu seçin.');
        if ($review['status']==='approved') {
            $date=$review['checked_at'];
            if ($review['source']==='' || !preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/D',$date,$dateParts) || !checkdate((int)$dateParts[2],(int)$dateParts[3],(int)$dateParts[1])) {
                throw new InvalidArgumentException('Rakamları yayımlamak için teyit kaynağını ve geçerli teyit tarihini girin.');
            }
        }
    }
    return $result;
}

function sf_save_content(string $type, array $input, string $revision): void
{
    $defaults = sf_content_defaults($type);
    $validated = sf_content_validate_tree($defaults, $input);
    sf_update_json($type . '.json', static function (array &$data) use ($defaults, $validated, $revision): void {
        $current = sf_content_schema_merge($defaults, $data);
        if ($revision === '' || !hash_equals(sf_content_digest($current), $revision)) {
            throw new InvalidArgumentException('İçerik başka bir sekmede değiştirildi. Metninizi kopyalayın, sayfayı yenileyin ve son sürüm üzerinde düzenleyin. Değişiklikleriniz kaydedilmedi.');
        }
        $data = $validated;
    }, $defaults);
}

function sf_store_video_upload(array $file): string
{
    $temporary = $file['tmp_name'] ?? null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($temporary) || !is_uploaded_file($temporary)) throw new InvalidArgumentException('Video yüklenemedi. Sunucunun yükleme sınırına uygun bir MP4 seçin.');
    $size = filesize($temporary);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporary);
    $handle = fopen($temporary, 'rb');
    $signature = $handle === false ? '' : (string) fread($handle, 12);
    if (is_resource($handle)) fclose($handle);
    if ($size === false || $size < 12 || $size > 100 * 1024 * 1024 || $mime !== 'video/mp4' || substr($signature, 4, 4) !== 'ftyp') throw new InvalidArgumentException('En fazla 100 MB boyutunda geçerli bir MP4 video seçin.');
    $directory = SF_PUBLIC_DIR . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Video klasörü oluşturulamadı.');
    $name = bin2hex(random_bytes(16)) . '.mp4';
    if (!move_uploaded_file($temporary, $directory . '/' . $name)) throw new RuntimeException('Video kaydedilemedi.');
    @chmod($directory . '/' . $name, 0644);
    return '/uploads/' . $name;
}
