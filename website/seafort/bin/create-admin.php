<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

$options = getopt('', ['username:', 'password-stdin', 'reset']);
$username = isset($options['username']) && is_string($options['username']) ? trim($options['username']) : '';
if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._@-]{2,79}\z/D', $username)) {
    fwrite(STDERR, "Kullanım: php bin/create-admin.php --username kullanici [--reset] [--password-stdin]\nKullanıcı adı 3–80 harf, rakam, nokta, @, _ veya - içermelidir.\n");
    exit(1);
}
if (sf_admin_config() !== null && !array_key_exists('reset', $options)) {
    fwrite(STDERR, "Bir yönetici hesabı zaten var. Şifreyi yenilemek ve mevcut oturumları kapatmak için --reset ekleyin.\n");
    exit(1);
}

function read_secret(string $prompt): string
{
    fwrite(STDERR, $prompt);
    $hidden = PHP_OS_FAMILY !== 'Windows' && function_exists('stream_isatty') && stream_isatty(STDIN) && function_exists('shell_exec');
    if ($hidden) {
        shell_exec('stty -echo');
    } elseif (PHP_OS_FAMILY === 'Windows' && function_exists('stream_isatty') && stream_isatty(STDIN)) {
        fwrite(STDERR, "\nNot: Windows terminalinde giriş görünür olabilir. Parolayı kimseyle paylaşmayın.\n");
    }
    try {
        $line = fgets(STDIN);
        if ($line === false) {
            throw new RuntimeException('Şifre okunamadı.');
        }
        return rtrim($line, "\r\n");
    } finally {
        if ($hidden) {
            shell_exec('stty echo');
        }
        fwrite(STDERR, "\n");
    }
}

try {
    $stdinMode = array_key_exists('password-stdin', $options);
    $password = read_secret($stdinMode ? '' : 'Yeni şifre (12–72 bayt): ');
    if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new InvalidArgumentException('Şifre 12–72 bayt olmalıdır.');
    }
    if (!$stdinMode) {
        $confirmation = read_secret('Şifreyi tekrar girin: ');
        if (!hash_equals($password, $confirmation)) {
            throw new InvalidArgumentException('Şifreler eşleşmiyor.');
        }
        unset($confirmation);
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    unset($password);
    sf_update_json('admin.json', static function (array &$data) use ($username, $hash): void {
        $data = ['username' => $username, 'password_hash' => $hash, 'auth_version' => bin2hex(random_bytes(24)), 'updated_at' => sf_now()];
    });
    fwrite(STDOUT, "Yönetici hesabı kaydedildi. Eski oturumlar geçersiz kılındı. /admin/ adresinden giriş yapabilirsiniz.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
