<?php
declare(strict_types=1);

/**
 * Small single-editor CMS. Keep app/, storage/ and bin/ outside the document root.
 * Public content is plain text. No database or WordPress installation is required.
 */
if (!defined('SF_ROOT')) {
    define('SF_ROOT', dirname(__DIR__));
}
if (!defined('SF_STORAGE_DIR')) {
    define('SF_STORAGE_DIR', getenv('SEAFORT_STORAGE_DIR') ?: SF_ROOT . '/storage');
}
if (!defined('SF_PUBLIC_DIR')) {
    define('SF_PUBLIC_DIR', SF_ROOT . '/public');
}

function sf_escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sf_paragraphs(string $body): string
{
    $parts = preg_split('/\n\s*\n/u', trim(str_replace(["\r\n", "\r"], "\n", $body))) ?: [];
    return implode("\n", array_map(static function (string $part): string {
        $part = trim($part);
        if (preg_match('/\A## ([^\n]+)\z/u', $part, $match)) {
            return '<h2>' . sf_escape($match[1]) . '</h2>';
        }
        $lines = explode("\n", $part);
        if (array_reduce($lines, static fn(bool $valid, string $line): bool => $valid && str_starts_with($line, '- '), true)) {
            return '<ul>' . implode('', array_map(static fn(string $line): string => '<li>' . sf_escape(substr($line, 2)) . '</li>', $lines)) . '</ul>';
        }
        return '<p>' . nl2br(sf_escape($part), false) . '</p>';
    }, array_filter($parts, static fn(string $part): bool => trim($part) !== '')));
}

function sf_now(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.uP');
}

function sf_strlen(string $text): int
{
    return preg_match_all('/./us', $text) ?: 0;
}

function sf_storage_path(string $name): string
{
    if (!preg_match('/\A[a-z][a-z0-9-]*\.json\z/D', $name)) {
        throw new InvalidArgumentException('Invalid storage name.');
    }
    return SF_STORAGE_DIR . '/' . $name;
}

function sf_storage_ready(): void
{
    if (!is_dir(SF_STORAGE_DIR) && !mkdir(SF_STORAGE_DIR, 0700, true) && !is_dir(SF_STORAGE_DIR)) {
        throw new RuntimeException('Storage directory could not be created.');
    }
    if (!is_writable(SF_STORAGE_DIR)) {
        throw new RuntimeException('Storage directory is not writable.');
    }
    @chmod(SF_STORAGE_DIR, 0700);
}

function sf_read_json(string $name, array $fallback = []): array
{
    $path = sf_storage_path($name);
    if (!is_file($path)) {
        return $fallback;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Content could not be read.');
    }
    $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid content format.');
    }
    return $data;
}

/** A dedicated lock protects the entire read/modify/atomic-replace transaction. */
function sf_update_json(string $name, callable $mutator, array $fallback = []): mixed
{
    sf_storage_ready();
    $path = sf_storage_path($name);
    $lock = fopen($path . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Content lock could not be acquired.');
    }
    @chmod($path . '.lock', 0600);
    $temporary = null;
    try {
        $data = sf_read_json($name, $fallback);
        $result = $mutator($data);
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $temporary = tempnam(SF_STORAGE_DIR, 'sf-');
        if ($temporary === false || file_put_contents($temporary, $encoded, LOCK_EX) === false) {
            throw new RuntimeException('Content could not be written.');
        }
        @chmod($temporary, 0600);
        if (!rename($temporary, $path)) {
            throw new RuntimeException('Content could not be committed.');
        }
        $temporary = null;
        return $result;
    } finally {
        if ($temporary !== null && is_file($temporary)) {
            @unlink($temporary);
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function sf_posts(bool $publishedOnly = true): array
{
    $data = sf_read_json('posts.json', ['version' => 1, 'posts' => []]);
    $posts = array_values(array_filter($data['posts'] ?? [], static fn(mixed $post): bool => is_array($post) && (!$publishedOnly || ($post['status'] ?? '') === 'published')));
    usort($posts, static fn(array $a, array $b): int => strcmp((string) ($b['published_at'] ?? $b['updated_at'] ?? ''), (string) ($a['published_at'] ?? $a['updated_at'] ?? '')));
    return $posts;
}

function sf_post(string $slug): ?array
{
    foreach (sf_posts(true) as $post) {
        if (($post['slug'] ?? '') === $slug) {
            return $post;
        }
    }
    return null;
}

function sf_find_post(string $id): ?array
{
    foreach (sf_posts(false) as $post) {
        if (($post['id'] ?? '') === $id) {
            return $post;
        }
    }
    return null;
}

function sf_cover_path(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (str_contains($path, '..') || !preg_match('~\A/(?:assets|uploads)/[a-zA-Z0-9_./-]+\.(?:jpe?g|png|webp)\z~D', $path)) {
        throw new InvalidArgumentException('Kapak görseli assets veya uploads klasöründe bir JPEG, PNG veya WebP olmalıdır.');
    }
    if (!is_file(SF_PUBLIC_DIR . $path)) {
        throw new InvalidArgumentException('Kapak görseli bulunamadı.');
    }
    return $path;
}

function sf_cover_url(string $path): string
{
    try {
        return sf_cover_path($path);
    } catch (InvalidArgumentException) {
        return '';
    }
}

function sf_validate_post(array $input): array
{
    $fields = [];
    foreach (['title', 'slug', 'excerpt', 'body', 'category', 'status', 'cover'] as $key) {
        $value = $input[$key] ?? '';
        if (!is_string($value) || str_contains($value, "\0") || !preg_match('//u', $value)) {
            throw new InvalidArgumentException('Geçersiz metin biçimi.');
        }
        $fields[$key] = trim(str_replace(["\r\n", "\r"], "\n", $value));
    }
    if (sf_strlen($fields['title']) < 3 || sf_strlen($fields['title']) > 180) {
        throw new InvalidArgumentException('Başlık 3–180 karakter olmalıdır.');
    }
    if (!preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $fields['slug']) || strlen($fields['slug']) > 150) {
        throw new InvalidArgumentException('Adres yalnızca küçük İngilizce harf, rakam ve kelimeler arasında kısa çizgi içermelidir.');
    }
    if (sf_strlen($fields['excerpt']) > 400) {
        throw new InvalidArgumentException('Kısa açıklama en fazla 400 karakter olabilir.');
    }
    if (sf_strlen($fields['body']) < 20 || sf_strlen($fields['body']) > 100000) {
        throw new InvalidArgumentException('Yazı 20–100.000 karakter olmalıdır.');
    }
    if (sf_strlen($fields['category']) < 2 || sf_strlen($fields['category']) > 60) {
        throw new InvalidArgumentException('Kategori 2–60 karakter olmalıdır.');
    }
    if (!in_array($fields['status'], ['draft', 'published'], true)) {
        throw new InvalidArgumentException('Geçersiz yayın durumu.');
    }
    $fields['cover'] = sf_cover_path($fields['cover']);
    return $fields;
}

function sf_save_post(array $input, ?string $id, ?string $expectedUpdatedAt = null): string
{
    $fields = sf_validate_post($input);
    return sf_update_json('posts.json', static function (array &$data) use ($fields, $id, $expectedUpdatedAt): string {
        $data['version'] = 1;
        $data['posts'] ??= [];
        $found = null;
        foreach ($data['posts'] as $index => $post) {
            if (($post['slug'] ?? '') === $fields['slug'] && ($post['id'] ?? '') !== $id) {
                throw new InvalidArgumentException('Bu adres başka bir yazıda kullanılıyor.');
            }
            if (($post['id'] ?? '') === $id) {
                $found = $index;
            }
        }
        if ($id !== null && $found === null) {
            throw new InvalidArgumentException('Düzenlenecek yazı bulunamadı.');
        }
        if ($found !== null && $expectedUpdatedAt !== null && !hash_equals((string) ($data['posts'][$found]['updated_at'] ?? ''), $expectedUpdatedAt)) {
            throw new InvalidArgumentException('Bu yazı başka bir sekmede değiştirildi. Metninizi bir yere kopyalayın, sayfayı yenileyin ve son sürüm üzerinde düzenleyin. Değişiklikleriniz kaydedilmedi.');
        }
        $now = sf_now();
        $post = $found !== null ? $data['posts'][$found] : ['id' => bin2hex(random_bytes(12)), 'created_at' => $now, 'published_at' => null];
        $post = array_merge($post, $fields, ['updated_at' => $now]);
        if ($post['status'] === 'published' && empty($post['published_at'])) {
            $post['published_at'] = $now;
        }
        unset($post['restore_status']);
        if ($found === null) {
            $data['posts'][] = $post;
        } else {
            $data['posts'][$found] = $post;
        }
        return (string) $post['id'];
    }, ['version' => 1, 'posts' => []]);
}

function sf_change_post_status(string $id, string $action): void
{
    sf_update_json('posts.json', static function (array &$data) use ($id, $action): void {
        $data['posts'] ??= [];
        foreach ($data['posts'] as &$post) {
            if (($post['id'] ?? '') !== $id) {
                continue;
            }
            if ($action === 'archive') {
                $post['restore_status'] = ($post['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
                $post['status'] = 'archived';
            } elseif ($action === 'restore' && ($post['status'] ?? '') === 'archived') {
                // Restore into a draft so a review always precedes republication.
                $post['status'] = 'draft';
                unset($post['restore_status']);
            } elseif ($action === 'unpublish') {
                $post['status'] = 'draft';
            } else {
                throw new InvalidArgumentException('Geçersiz işlem.');
            }
            $post['updated_at'] = sf_now();
            return;
        }
        throw new InvalidArgumentException('Yazı bulunamadı.');
    }, ['version' => 1, 'posts' => []]);
}

function sf_store_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Görsel yüklenemedi. Dosyayı yeniden seçin.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size < 1 || $size > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Görsel en fazla 5 MB olabilir.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $image = @getimagesize($file['tmp_name']);
    if (!isset($extensions[$mime]) || !$image || ($image['mime'] ?? '') !== $mime || $image[0] > 10000 || $image[1] > 10000 || $image[0] * $image[1] > 40000000) {
        throw new InvalidArgumentException('Geçerli bir JPEG, PNG veya WebP görseli seçin (en fazla 10.000 × 10.000 piksel).');
    }
    $directory = SF_PUBLIC_DIR . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Görsel klasörü oluşturulamadı.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
        throw new RuntimeException('Görsel kaydedilemedi.');
    }
    @chmod($directory . '/' . $name, 0644);
    return '/uploads/' . $name;
}

function sf_admin_config(): ?array
{
    $config = sf_read_json('admin.json');
    if (empty($config['username']) || empty($config['password_hash']) || empty($config['auth_version'])) {
        return null;
    }
    return $config;
}

function sf_admin_session(): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (!$secure && !$local) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Yönetim paneli HTTPS bağlantısı gerektirir. Güvenli adresi açın.';
        exit;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '1800');
    ini_set('session.use_trans_sid', '0');
    session_name('seafort_editor');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/admin/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    $config = sf_admin_config();
    $now = time();
    if (!empty($_SESSION['authenticated']) && (!$config || !hash_equals((string) $config['auth_version'], (string) ($_SESSION['auth_version'] ?? '')) || $now - (int) ($_SESSION['last_seen'] ?? 0) > 1800 || $now - (int) ($_SESSION['authenticated_at'] ?? $_SESSION['last_seen'] ?? 0) > 28800)) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    if (!empty($_SESSION['authenticated'])) {
        $_SESSION['last_seen'] = $now;
        $_SESSION['authenticated_at'] ??= $now;
    }
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function sf_csrf(): string
{
    return (string) ($_SESSION['csrf'] ?? '');
}

function sf_require_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(sf_csrf(), $token)) {
        throw new InvalidArgumentException('Oturum doğrulaması geçersiz. Sayfayı yenileyip yeniden deneyin.');
    }
}

function sf_login(string $username, string $password): bool
{
    $config = sf_admin_config();
    if ($config === null) {
        return false;
    }
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $key = hash('sha256', $ip);
    $now = time();
    $accountKey = 'account-' . hash('sha256', (string) $config['username']);
    $allowed = sf_update_json('login-attempts.json', static function (array &$data) use ($key, $accountKey, $now): bool {
        foreach ($data as $hash => $entry) {
            if (!is_array($entry) || $now - (int) ($entry['start'] ?? 0) > 900) {
                unset($data[$hash]);
            }
        }
        $data[$key] ??= ['start' => $now, 'count' => 0];
        $data[$accountKey] ??= ['start' => $now, 'count' => 0];
        if ($data[$key]['count'] >= 5 || $data[$accountKey]['count'] >= 50) {
            return false;
        }
        $data[$key]['count']++;
        $data[$accountKey]['count']++;
        return true;
    });
    if (!$allowed) {
        throw new InvalidArgumentException('Çok fazla giriş denemesi. 15 dakika sonra yeniden deneyin.');
    }
    $validPassword = strlen($password) <= 72 && password_verify($password, (string) $config['password_hash']);
    if (!hash_equals((string) $config['username'], trim($username)) || !$validPassword) {
        return false;
    }
    sf_update_json('login-attempts.json', static function (array &$data) use ($key, $accountKey): void { unset($data[$key], $data[$accountKey]); });
    session_regenerate_id(true);
    $_SESSION = ['authenticated' => true, 'username' => $config['username'], 'auth_version' => $config['auth_version'], 'authenticated_at' => $now, 'last_seen' => $now, 'csrf' => bin2hex(random_bytes(32))];
    return true;
}

function sf_logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function sf_date(?string $value): string
{
    if (!$value) {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Istanbul'))->format('d.m.Y');
    } catch (Exception) {
        return '—';
    }
}
