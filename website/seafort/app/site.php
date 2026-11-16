<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/collections.php';
$siteConfig = require __DIR__ . '/config.php';
$siteSettings = sf_site_settings();
$siteConfig = array_replace($siteConfig, $siteSettings, ['careers_email' => sf_enquiry_public_form('career')['recipient'], 'phone' => '', 'careers_phone' => '', 'address' => '']);
$siteData = sf_site_pages();
$homeContent = sf_home_content();

function h(mixed $value): string { return sf_escape($value); }
function site_lines(string $value): string { return nl2br(h($value), false); }
function mb_safe_query(string $value): string { return trim(substr($value,0,200)); }
function site_social_icon(string $platform): string {
    $shapes = [
        'linkedin'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 10v7m0-10v.1M11 17v-7m0 3c0-4 6-4 6 0v4"/>',
        'instagram'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'youtube'=>'<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3Z"/>',
        'facebook'=>'<path d="M14 21v-8h3l.5-4H14V7c0-1.2.5-2 2-2h2V2h-3c-3 0-5 1.8-5 5v2H7v4h3v8"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($shapes[$platform] ?? '').'</svg>';
}
function site_image(string $key): array {
    $images = [
        'hero' => ['archive/marine-worksite.webp', 'Vessels alongside a marine worksite'],
        'steel' => ['archive/internal-welding.webp', 'Welder working on an internal steel structure'],
        'coatings' => ['archive/local-coating.webp', 'Coating work from scaffold access on an offshore structure'],
        'installation' => ['archive/installation-lifting.webp', 'Crane handling of a fabricated steel assembly'],
        'team' => ['archive/site-team.webp', 'Personnel coordinating work in a marine work area'],
        'underwater' => ['project/underwater-hull-detail.webp', 'Underwater view of a vessel hull opening and surrounding surface'],
        'reference-steel' => ['archive/plate-preparation.webp', 'Steel plate preparation with cutting equipment on deck'],
        'reference-coatings' => ['archive/roller-coating.webp', 'Worker applying coating with a roller on a vessel structure'],
        'reference-installation' => ['archive/module-handling.webp', 'A suspended marine component being handled by cranes'],
    ];
    return $images[$key] ?? $images['steel'];
}
function site_header(string $title, string $route, string $description = ''): void {
    global $siteConfig;
    $home = $route === '/';
    $indexable = $siteConfig['indexable'] && (http_response_code() ?: 200) < 400;
    $canonical = rtrim($siteConfig['base_url'], '/') . $route;
    $about = in_array($route, ['/about/','/values/','/marmaris/','/references/'], true);
    $services = in_array($route, ['/services/','/maintenance-repair/','/marine-services/','/marine-services-2/','/ship-supply/','/ship-construction/','/rigging-oilfield/','/shipyard-services/'], true);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; media-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    if (!$indexable) header('X-Robots-Tag: noindex, nofollow');
    ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=h($title)?> | Seafort</title><meta name="description" content="<?=h($description)?>">
<?php if (!$indexable): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
<link rel="canonical" href="<?=h($canonical)?>">
<meta property="og:url" content="<?=h($canonical)?>">
<meta property="og:site_name" content="Seafort">
<meta property="og:title" content="<?=h($title)?> | Seafort">
<meta property="og:description" content="<?=h($description)?>">
<meta name="theme-color" content="#103447"><link rel="icon" href="/assets/logo-dark.png" type="image/png">
<link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/fonts/fonts.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/site.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/pages.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/directory.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/updates.css'))?>">
<?php if($home): ?><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/slider.css'))?>"><?php endif; ?>
<?php if ($home): $firstSlides=sf_collection('slides',true);$preloadCover=sf_cover_url((string)($firstSlides[0]['cover'] ?? '/assets/hero.png')) ?: '/assets/hero.png'; ?><link rel="preload" href="<?=h($preloadCover)?>" as="image"><?php endif; ?>
<link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/refresh.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/fonts/brand-fonts.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/expansion.css'))?>">
<link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/refinements.css'))?>">
<?php if($home || in_array($route,['/references/','/ege-deniz/'],true)): ?><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/references.css'))?>"><?php endif; ?>
<?php if(str_starts_with($route,'/projects/')): ?><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/cases.css'))?>"><?php endif; ?>
<?php if(in_array($route,['/documents/','/sustainability/'],true)): ?><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/documents.css'))?>"><?php endif; ?>
<?php if($route==='/sustainability/'): ?><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/sustainability.css'))?>"><?php endif; ?>
<?php if(in_array($route,['/get-a-quote/','/careers/'],true)): ?><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/forms.css'))?>"><?php endif; ?>
</head><body id="top" class="<?=$home?'home-page':'interior-page'?><?=$route==='/sustainability/'?' sustainability-page':''?>">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" id="site-header"><div class="header-inner wrap">
<a class="brand" href="/" aria-label="Seafort home"><img class="logo logo-light" src="/assets/logo-white.png" width="440" height="109" alt="Seafort Service"><img class="logo logo-dark" src="/assets/logo-dark.png" width="445" height="117" alt="" aria-hidden="true"></a>
<button class="menu-toggle" id="menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="primary-nav"><span></span><span></span></button>
<nav class="primary-nav" id="primary-nav" aria-label="Main navigation">
<a class="nav-link <?=$home?'active':''?>" href="/" <?=$home?'aria-current="page"':''?>>Home</a>
<a class="nav-link <?=$about?'active':''?>" href="/about/" <?=$route==='/about/'?'aria-current="page"':''?>>About</a>
<a class="nav-link <?=$services?'active':''?>" href="/services/" <?=$route==='/services/'?'aria-current="page"':''?>>Services</a>
<a class="nav-link <?=$route==='/manpower-team/'?'active':''?>" href="/manpower-team/" <?=$route==='/manpower-team/'?'aria-current="page"':''?>>Manpower</a>
<a class="nav-link <?=str_starts_with($route,'/projects/')?'active':''?>" href="/projects/" <?=$route==='/projects/'?'aria-current="page"':''?>>Projects</a>
<a class="nav-link <?=$route==='/sustainability/'?'active':''?>" href="/sustainability/">Sustainability</a>
<a class="button button-small" href="/get-a-quote/" <?=$route==='/get-a-quote/'?'aria-current="page"':''?>>Get a Quote</a>
<div class="language-picker" data-no-translate><label class="sr-only" for="site-language">Language</label><select id="site-language" aria-label="Language"><option value="en" lang="en">English</option><option value="tr" lang="tr">Türkçe</option><option value="ar" lang="ar">العربية</option><option value="ru" lang="ru">Русский</option><option value="zh-CN" lang="zh-CN">简体中文</option></select></div>
</nav></div></header>
<p id="language-status" class="sr-only" role="status" data-no-translate></p>
<?php site_brochure($route); ?>
<?php }
function site_footer(): void { global $siteConfig; ?>
<footer class="footer"><div class="wrap"><div class="footer-top">
<div class="footer-brand"><a href="/" aria-label="Seafort home"><img src="/assets/logo-white.png" width="440" height="109" alt="Seafort Service" loading="lazy"></a><p><?=h($siteConfig['footer_tagline'])?></p><a class="footer-email" href="mailto:<?=h($siteConfig['email'])?>"><?=h($siteConfig['email'])?></a>
<?php $socialLabels=['linkedin'=>'LinkedIn','instagram'=>'Instagram','youtube'=>'YouTube','facebook'=>'Facebook'];$socialLinks=[];foreach($socialLabels as $platform=>$label){try{$url=sf_social_link($platform,(string)($siteConfig['social'][$platform.'_url']??''));if($url!=='')$socialLinks[]=[$platform,$label,$url];}catch(InvalidArgumentException $e){}} if($socialLinks): ?><nav class="footer-social" aria-label="Social media"><?php foreach($socialLinks as [$platform,$label,$url]): ?><a href="<?=h($url)?>" target="_blank" rel="noopener noreferrer" data-no-translate aria-label="<?=h($label)?> (opens in a new tab)"><?=site_social_icon($platform)?><span class="sr-only"><?=h($label)?></span></a><?php endforeach; ?></nav><?php endif; ?></div>
<nav class="footer-links" aria-label="Services and projects"><h2>Our Work</h2><a href="/services/">Our Services</a><a href="/projects/">Projects</a><a href="/references/">Work in Focus</a><a href="/manpower-team/">Manpower &amp; Team</a><a href="/get-a-quote/">Get a Quote</a></nav>
<nav class="footer-links" aria-label="Company"><h2>Company</h2><a href="/about/">About Seafort</a><a href="/values/">Our Values</a><a href="/sustainability/">Sustainability</a><a href="/marmaris/">Marmaris</a><a href="/careers/">Careers</a><a href="/blog/">Blog</a><a href="/documents/">Documents</a><a href="/contact/">Contact</a></nav>
</div><div class="footer-bottom"><p>© <?=date('Y')?> <?=h($siteConfig['company'])?> All rights reserved.</p><div class="footer-utility"><a href="/admin/">Content administration</a><a href="#top">Back to top</a></div></div></div></footer>
<?php $wa=$siteConfig['whatsapp']??[];$waNumber=preg_replace('/[\s()+-]/','',(string)($wa['number']??''));if(preg_match('/\A[1-9][0-9]{7,14}\z/D',$waNumber)): ?><a class="whatsapp-tab" href="https://wa.me/<?=h($waNumber)?>?text=<?=rawurlencode((string)($wa['message']??''))?>" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a8 8 0 0 1-8 8 8.7 8.7 0 0 1-3.6-.8L4 20l1.3-4.4A8 8 0 1 1 20 11.5Z"/><path d="M8.5 7.5c.2-.4 1-.4 1.2 0l.7 1.6c.1.3-.4.8-.7 1 .6 1.4 1.6 2.4 3 3 .2-.3.7-.8 1-.7l1.6.7c.4.2.4 1 0 1.2-1.6 1-3.8-.3-5.1-1.6S7.5 9.1 8.5 7.5Z"/></svg><span><?=h($wa['label']?:'WhatsApp support')?></span></a><?php endif; ?>
<script src="<?=sf_escape(sf_asset_url('/assets/site.js'))?>"></script><script src="<?=sf_escape(sf_asset_url('/assets/forms.js'))?>"></script><script src="<?=sf_escape(sf_asset_url('/assets/slider.js'))?>"></script><script src="<?=sf_escape(sf_asset_url('/assets/languages.js'))?>"></script></body></html>
<?php }
function site_cta(?string $label = null, ?string $href = null): void { global $homeContent; $copy = $homeContent['quote']; ?>
<section class="closing-cta"><div class="wrap"><div><p class="eyebrow eyebrow-light"><?=h($copy['eyebrow'])?></p><h2><?=site_lines($copy['title'])?></h2></div><a class="button" href="<?=h($href ?? $copy['link_href'])?>"><?=h($label ?? $copy['label'])?></a></div></section>
<?php }
function site_post_card(array $post): void {
    $cover = sf_cover_url((string)($post['cover'] ?? '')) ?: '/assets/project/steel-grinding.webp';
    ?>
<article class="post-card"><a class="post-image" href="/blog/<?=h($post['slug'])?>/"><img src="<?=h($cover)?>" alt="" loading="lazy" width="656" height="492"></a><div class="post-meta"><span><?=h($post['category'])?></span><?php if (!empty($post['published_at'])): ?><time datetime="<?=h($post['published_at'])?>"><?=h(date('d M Y', strtotime($post['published_at'])))?></time><?php endif; ?></div><h3><a href="/blog/<?=h($post['slug'])?>/"><?=h($post['title'])?></a></h3><p><?=h($post['excerpt'])?></p><a class="text-link" href="/blog/<?=h($post['slug'])?>/">Read Article</a></article>
<?php }
function site_blog_strip(): void {
    global $homeContent;
    $copy = $homeContent['blog'];
    $posts = array_slice(sf_posts(true),0,3); if (!$posts) return; ?>
<section class="section latest"><div class="wrap"><div class="section-intro"><div><p class="eyebrow"><?=h($copy['eyebrow'])?></p><h2><?=site_lines($copy['title'])?></h2></div><a class="text-link" href="/blog/"><?=h($copy['label'])?></a></div><div class="post-grid"><?php foreach($posts as $post) site_post_card($post); ?></div></div></section>
<?php }
