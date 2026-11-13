<?php
declare(strict_types=1);
// Development router only. Apache/LiteSpeed uses public_html/.htaccess.
$public=dirname(__DIR__).'/public_html';
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH) ?: '/');
if (str_contains($path,'..') || str_starts_with($path,'/.') || str_starts_with($path,'/seafort/')) {
    http_response_code(404);exit;
}
if (is_file($public.$path)) return false;
if (is_dir($public.$path) && is_file($public.rtrim($path,'/').'/index.php')) {
    require $public.rtrim($path,'/').'/index.php';return true;
}
require $public.'/index.php';
