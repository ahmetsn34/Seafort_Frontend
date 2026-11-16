<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
define('SF_ROOT', dirname(__DIR__));
define('SF_PUBLIC_DIR', dirname(SF_ROOT) . '/public_html');
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/brochures.php';
require_once __DIR__ . '/enquiries.php';
require_once __DIR__ . '/documents.php';
require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/references.php';

/** Version static files by content so an update cannot reuse an older cached design. */
function sf_asset_url(string $path): string
{
    if (!preg_match('~\A/(?:assets|admin)/[a-zA-Z0-9_./-]+\.(?:css|js)\z~D', $path) || str_contains($path, '..')) {
        throw new InvalidArgumentException('Geçersiz kaynak yolu.');
    }
    $file = SF_PUBLIC_DIR . $path;
    $hash = is_file($file) ? hash_file('sha256', $file) : false;
    return $path . ($hash === false ? '' : '?v=' . substr($hash, 0, 12));
}
