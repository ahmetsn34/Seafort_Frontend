<?php
declare(strict_types=1);
header('Content-Type: application/xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');
require_once __DIR__.'/discovery.php';
if (($siteConfig['indexable']??false)!==true) header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-cache');
$entries=sf_discovery_entries();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($entries as $path=>$entry) {
    if (empty($entry['eligible'])) continue;
    $url = rtrim($siteConfig['base_url'], '/') . $path;
    echo '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>';
    if (isset($entry['lastmod'])) echo '<lastmod>'.htmlspecialchars($entry['lastmod'],ENT_XML1|ENT_QUOTES,'UTF-8').'</lastmod>';
    echo '</url>' . "\n";
}
echo '</urlset>';
