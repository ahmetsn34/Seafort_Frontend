<?php
declare(strict_types=1);
$flow=$page['sustainability'];
site_header($page['title'],$route,$page['intro']);
?>
<main id="main">
<section class="sustain-hero" aria-labelledby="sustain-title">
  <img class="sustain-hero-image" src="<?=h($page['cover'])?>" alt="<?=h($page['cover_alt'])?>" width="1800" height="1350" fetchpriority="high">
  <div class="sustain-hero-shade" aria-hidden="true"></div>
  <div class="wrap sustain-hero-content">
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span><?=h($page['title'])?></span></nav>
    <div class="sustain-hero-copy"><p class="eyebrow eyebrow-light"><?=h($page['eyebrow'])?></p><h1 id="sustain-title"><?=h($page['title'])?></h1><p class="sustain-hero-intro"><?=h($page['intro'])?></p><a class="sustain-outline sustain-outline-light" href="<?=h($flow['hero_link_href'])?>"><?=h($flow['hero_link_label'])?><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 3v13m-5-5 5 5 5-5"/></svg></a></div>
  </div>
</section>
<nav class="sustain-jump" aria-label="Explore this page"><div class="wrap"><?php foreach($flow['navigation'] as $link): ?><a href="<?=h($link['href'])?>"><?=h($link['label'])?></a><?php endforeach; ?></div></nav>

<section class="sustain-overview"><div class="sustain-reading wrap"><p class="eyebrow"><?=h($flow['overview']['eyebrow'])?></p><h2><?=site_lines($flow['overview']['title'])?></h2><p class="sustain-lead"><?=h($flow['overview']['body'])?></p><a class="sustain-outline" href="<?=h($flow['overview']['link_href'])?>"><?=h($flow['overview']['link_label'])?><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div></section>

<section class="sustain-focus" id="our-focus" aria-labelledby="sustain-focus-title"><div class="wrap"><div class="sustain-section-heading"><div><p class="eyebrow"><?=h($flow['pillars']['eyebrow'])?></p><h2 id="sustain-focus-title"><?=site_lines($flow['pillars']['title'])?></h2></div><p><?=h($flow['pillars']['intro'])?></p></div><div class="sustain-pillar-grid">
<?php foreach($flow['pillars']['items'] as $item): ?><article class="sustain-pillar"><a class="sustain-pillar-photo" href="<?=h($item['link_href'])?>" tabindex="-1" aria-hidden="true"><img src="<?=h($item['image'])?>" alt="<?=h($item['image_alt'])?>" width="960" height="720" loading="lazy" decoding="async"></a><div class="sustain-pillar-copy"><p class="eyebrow"><?=h($item['eyebrow'])?></p><h3><a href="<?=h($item['link_href'])?>"><?=h($item['title'])?></a></h3><p><?=h($item['body'])?></p><a class="sustain-card-link" href="<?=h($item['link_href'])?>"><?=h($item['link_label'])?><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div></article><?php endforeach; ?>
</div></div></section>

<section class="sustain-approach" id="our-approach" aria-labelledby="sustain-approach-title"><div class="wrap sustain-approach-grid"><figure><img src="<?=h($flow['approach']['image'])?>" alt="<?=h($flow['approach']['image_alt'])?>" width="1200" height="900" loading="lazy" decoding="async"></figure><div class="sustain-approach-copy"><p class="eyebrow"><?=h($flow['approach']['eyebrow'])?></p><h2 id="sustain-approach-title"><?=site_lines($flow['approach']['title'])?></h2><p><?=h($flow['approach']['body'])?></p><a class="sustain-outline" href="<?=h($flow['approach']['link_href'])?>"><?=h($flow['approach']['link_label'])?><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div></div></section>

<section class="sustain-principles" id="working-principles" aria-labelledby="sustain-principles-title"><div class="wrap"><div class="sustain-section-heading"><div><p class="eyebrow eyebrow-light"><?=h($flow['commitments']['eyebrow'])?></p><h2 id="sustain-principles-title"><?=site_lines($flow['commitments']['title'])?></h2></div><p><?=h($flow['commitments']['intro'])?></p></div><div class="sustain-principle-grid"><?php foreach($flow['commitments']['items'] as $i=>$item): ?><article><span class="sustain-step" aria-hidden="true"><?=sprintf('%02d',$i+1)?></span><h3><?=h($item['title'])?></h3><p><?=h($item['body'])?></p></article><?php endforeach; ?></div></div></section>

<section class="sustain-documents" id="documents" aria-labelledby="sustain-documents-title"><div class="wrap sustain-documents-grid"><div class="sustain-document-heading"><svg viewBox="0 0 80 90" aria-hidden="true"><path d="M17 7h33l16 17v56H17ZM49 7v19h17M29 40h25M29 51h25M29 62h15"/><path d="M8 18v69h48"/></svg><p class="eyebrow"><?=h($flow['documents']['eyebrow'])?></p><h2 id="sustain-documents-title"><?=site_lines($flow['documents']['title'])?></h2></div><div class="sustain-documents-copy"><p><?=h($flow['documents']['body'])?></p><div class="sustain-document-actions"><?php $brochure=sf_brochure_entry('sustainability');if($brochure): ?><a class="sustain-outline" href="/brochure/?page=sustainability"><?=h($flow['documents']['link_label'])?><small>PDF</small></a><?php endif; ?><a class="sustain-outline" href="/documents/?category=sustainability">Browse documents</a><a class="sustain-card-link" href="<?=h($flow['documents']['secondary_href'])?>"><?=h($flow['documents']['secondary_label'])?><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div></div></div></section>
<?php if(sf_documents_list(true,'sustainability')): ?><section class="section"><div class="wrap"><?php sf_documents_render('sustainability'); ?></div></section><?php endif; ?>
</main>
<?php site_footer(); ?>
