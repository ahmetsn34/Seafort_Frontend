<?php
declare(strict_types=1);
// Development router only. Apache/LiteSpeed uses public_html/.htaccess.
$public=dirname(__DIR__).'/public_html';
$path=rawurldecode(explode('?',(string)($_SERVER['REQUEST_URI']??'/'),2)[0]);
if (str_contains($path,'..') || str_starts_with($path,'/.') || str_starts_with($path,'/seafort/')) {
    http_response_code(404);exit;
}
// Match production rewrites: robots follow the current preview/production config.
if ($path==='/robots.txt') { require $public.'/index.php';return true; }
if (is_file($public.$path)) return false;
if (is_dir($public.$path) && is_file($public.rtrim($path,'/').'/index.php')) {
    require $public.rtrim($path,'/').'/index.php';return true;
}
require $public.'/index.php';
