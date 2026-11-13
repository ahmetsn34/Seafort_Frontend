<?php
declare(strict_types=1);
require __DIR__ . '/../seafort/app/site.php';
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$route = '/' . trim(rawurldecode($rawPath), '/') . '/';
if ($route === '//') $route = '/';
if ($rawPath === '/sitemap.xml') { require __DIR__.'/../seafort/app/sitemap.php'; exit; }
$aliases = ['/industries/'=>'/services/','/services/marine-services/'=>'/marine-services-2/','/team/'=>'/about/'];
if (isset($aliases[$route])) { header('Location: '.$aliases[$route],true,301); exit; }
foreach (sf_posts(true) as $oldPost) {
    if ($route === '/'.$oldPost['slug'].'/') { header('Location: /blog/'.rawurlencode($oldPost['slug']).'/',true,301); exit; }
}
if ($route === '/') {
    site_header($homeContent['meta']['title'],'/',$homeContent['meta']['description']);
    require __DIR__.'/../seafort/app/views/home.php'; site_footer(); exit;
}
if ($route === '/blog/') { require __DIR__.'/../seafort/app/views/blog.php'; exit; }
if ($route === '/brochure/') { sf_send_brochure(); }
if ($route === '/projects/') { require __DIR__.'/../seafort/app/views/projects.php'; exit; }
if (preg_match('~^/projects/([a-z0-9-]+)/$~D',$route,$match)) {
    $project=sf_collection_item('projects',$match[1],true);
    if($project) { require __DIR__.'/../seafort/app/views/project.php';exit; }
}
if (preg_match('~^/blog/([a-z0-9-]+)/$~D',$route,$match)) {
    $post = sf_post($match[1]); if ($post) { require __DIR__.'/../seafort/app/views/article.php'; exit; }
}
$page = null;
foreach($siteData['pages'] as $candidate) {
    if ('/'.trim($candidate['slug'],'/').'/' === $route) { $page=$candidate; break; }
}
if ($page) { require __DIR__.'/../seafort/app/views/page.php'; exit; }
http_response_code(404);site_header('Page not found',$route,'Find Seafort services, project support and contact details.'); ?>
<main id="main"><section class="error-page wrap"><p class="eyebrow">404 · PAGE NOT FOUND</p><h1>Let’s find the<br> right direction.</h1><p>The page you requested is not available.</p><div class="hero-actions"><a class="button" href="/">Back to Home</a><a class="text-link" href="/services/">Explore Our Services</a></div></section></main>
<?php site_footer(); ?>
