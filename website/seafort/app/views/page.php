<?php
if (($page['slug'] ?? '') === 'sustainability') { require __DIR__.'/sustainability.php'; return; }
$slug=$page['slug'];
$imageKeys=['about'=>'team','values'=>'coatings','manpower-team'=>'team','marmaris'=>'coatings','references'=>'installation','careers'=>'team','get-a-quote'=>'hero','maintenance-repair'=>'steel','marine-services-2'=>'underwater','ship-supply'=>'installation','marine-services'=>'installation','services'=>'hero'];
$image=$page['cover'];$alt=$page['cover_alt'];
$contact=in_array($slug,['careers','get-a-quote'],true);
if ($contact) {
    $enquiryKind = $slug === 'careers' ? 'career' : 'quote';
    $enquiryForm = sf_enquiry_public_form($enquiryKind);
    $enquiryResult = sf_enquiry_handle($enquiryKind);
    $enquiryCsrf = sf_enquiry_csrf();
    $enquiryNonce = sf_enquiry_nonce();
    header('Cache-Control: no-store, private');
}
site_header($page['title'],$route,$page['intro']);
?>
<main id="main"><section class="page-hero"><div class="wrap"><nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span><?=h($page['title'])?></span></nav><div class="page-hero-grid"><div class="page-hero-copy"><p class="eyebrow eyebrow-light"><?=h($page['eyebrow'])?></p><h1><?=h($page['title'])?></h1><p><?=h($page['intro'])?></p><a class="text-link light" href="<?=$contact?'#contact-details':'#page-details'?>"><?=$contact?'Get in Touch':(in_array($slug,['about','values'],true)?'Explore Our Approach':'Explore the Scope')?></a></div><figure class="page-hero-photo"><img src="<?=h($image)?>" alt="<?=h($alt)?>" width="870" height="816" fetchpriority="high"><?php if($slug==='marmaris'): ?><figcaption class="location-credit">Turunç, Marmaris district · <a href="https://commons.wikimedia.org/wiki/File:Turunc_panorama.jpg" rel="noreferrer">Photo: Przemysław Jahr / Wikimedia Commons</a> · Public domain.</figcaption><?php endif; ?></figure></div></div></section>

<?php if($slug==='services'): ?>
<section class="section" id="page-details"><div class="wrap"><div class="section-intro"><div><p class="eyebrow"><?=h($page['detail_eyebrow'])?></p><h2><?=site_lines($page['detail_title'])?></h2></div><p class="section-description"><?=h($page['detail_intro'])?></p></div><div class="service-directory">
<?php $serviceSlugs=['maintenance-repair','marine-services-2','ship-supply','marine-services','ship-construction','rigging-oilfield','shipyard-services'];foreach($serviceSlugs as $i=>$ss):foreach($siteData['pages'] as $sp){if($sp['slug']===$ss)break;} [$im,$ia]=site_image($imageKeys[$ss]??'steel'); ?>
<article class="directory-card"><a class="directory-image" href="/<?=h($ss)?>/"><img src="<?=h($sp['cover'])?>" alt="<?=h($sp['cover_alt'])?>" width="656" height="492" loading="lazy"></a><div class="directory-copy"><span class="service-number">0<?=$i+1?></span><h3><a href="/<?=h($ss)?>/"><?=h($sp['title'])?></a></h3><p><?=h($sp['intro'])?></p><a class="text-link" href="/<?=h($ss)?>/">Explore Service</a></div></article>
<?php endforeach; ?></div></div></section>

<?php elseif($slug==='references'): ?>
<section class="section work-focus" id="page-details"><div class="wrap"><div class="section-intro"><div><p class="eyebrow"><?=h($page['detail_eyebrow'])?></p><h2><?=site_lines($page['detail_title'])?></h2></div><p class="section-description"><?=h($page['detail_intro'])?></p></div>
<?php foreach($page['sections'] as $i=>$section):[$im,$ia]=site_image(['reference-steel','reference-coatings','reference-installation','installation','hero'][$i]??'steel'); ?><article class="focus-row"><figure><img src="/assets/<?=h($im)?>" alt="<?=h($ia)?>" width="960" height="720" loading="lazy"></figure><div><p class="eyebrow">0<?=$i+1?> / FIELD CAPABILITY</p><h2><?=h($section['title'])?></h2><p><?=h($section['body'])?></p><?php if(!empty($section['list'])): ?><ul class="scope-list"><?php foreach($section['list'] as $li): ?><li><?=h($li)?></li><?php endforeach; ?></ul><?php endif; ?></div></article><?php endforeach; ?></div></section>

<?php sf_reference_logos_render(); sf_reference_quotes_render(); ?>
<?php elseif($slug==='manpower-team'): ?>
<section class="section manpower-section" id="page-details"><div class="wrap">
<?php if(($page['stats_review']['status'] ?? 'pending')==='approved'): ?><dl class="manpower-stats"><?php foreach($page['stats'] as $stat): ?><div><dt><?=h($stat['label'])?></dt><dd><?=h($stat['value'])?></dd></div><?php endforeach; ?></dl><?php endif; ?>
<div class="section-intro manpower-intro"><div><p class="eyebrow"><?=h($page['detail_eyebrow'])?></p><h2><?=site_lines($page['detail_title'])?></h2></div><p class="section-description"><?=h($page['detail_intro'])?></p></div>
<div class="manpower-grid"><?php foreach($page['sections'] as $i=>$section): ?><article class="manpower-card <?=$i===3?'manpower-planning':''?>"><span class="eyebrow">0<?=$i+1?></span><h3><?=h($section['title'])?></h3><p><?=h($section['body'])?></p><?php if(!empty($section['list'])): ?><ul class="scope-list"><?php foreach($section['list'] as $item): ?><li><?=h($item)?></li><?php endforeach; ?></ul><?php endif; ?><?php if($i===3): ?><a class="text-link" href="/get-a-quote/">Discuss Requirements</a><?php endif; ?></article><?php endforeach; ?></div>
</div></section>

<?php elseif($contact):require __DIR__.'/contact.php'; ?>

<?php else: ?>
<section class="section page-details" id="page-details"><div class="wrap detail-layout"><aside class="detail-aside"><p class="eyebrow"><?=h($page['detail_eyebrow'])?></p><h2><?=site_lines($page['detail_title'])?></h2><a class="text-link" href="/get-a-quote/">Discuss Requirements</a></aside><div class="detail-sections"><?php foreach($page['sections'] as $i=>$section): ?><article class="detail-row"><span class="detail-number">0<?=$i+1?></span><div><h2><?=h($section['title'])?></h2><p><?=h($section['body'])?></p><?php if(!empty($section['list'])): ?><ul class="scope-list"><?php foreach($section['list'] as $li): ?><li><?=h($li)?></li><?php endforeach; ?></ul><?php endif; ?></div></article><?php endforeach; ?></div></div></section>
<?php if($slug==='about'): ?><section class="section page-crosslinks"><div class="wrap crosslinks-grid"><a href="/values/"><span class="eyebrow">01 / OUR APPROACH</span><h3>Our Values</h3><p>Preparation, coordination and responsibility.</p></a><a href="/manpower-team/"><span class="eyebrow">02 / OUR PEOPLE</span><h3>Manpower &amp; Team</h3><p>Technical and operational roles for the work.</p></a><a href="/references/"><span class="eyebrow">03 / FIELD CAPABILITIES</span><h3>Work in Focus</h3><p>A closer look at repair and installation activities.</p></a></div></section><?php endif; ?>
<?php endif; ?>
<?php if(!$contact) site_cta($page['cta']['label'],$page['cta']['href']); ?></main><?php site_footer(); ?>
