<?php
declare(strict_types=1);
header('Content-Type: application/xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$routes = ['/', '/blog/', '/projects/'];
foreach ($siteData['pages'] as $page) $routes[] = '/' . trim($page['slug'], '/') . '/';
foreach (sf_posts(true) as $post) $routes[] = '/blog/' . $post['slug'] . '/';
foreach (sf_collection('projects', true) as $project) $routes[] = '/projects/' . $project['slug'] . '/';
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (array_unique($routes) as $path) {
    $url = rtrim($siteConfig['base_url'], '/') . $path;
    echo '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
}
echo '</urlset>';
