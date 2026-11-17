<?php
declare(strict_types=1);
$aboutDefaults=json_decode((string)file_get_contents(dirname(__DIR__).'/about-layout-defaults.json'),true,64,JSON_THROW_ON_ERROR);
$editorial=array_replace($aboutDefaults['editorial'],is_array($page['editorial']??null)?$page['editorial']:[]);
site_header($page['title'],$route,$page['intro']);
?>
<main id="main" class="about-editorial">
<section class="about-opening"><div class="wrap">
    <nav class="breadcrumbs about-breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span>About Seafort</span></nav>
    <div class="about-opening-copy"><p class="eyebrow"><?=h($page['eyebrow'])?></p><h1><?=site_lines($page['title'])?></h1><p class="about-lead"><?=h($page['intro'])?></p></div>
    <div class="about-photo-pair">
        <figure><img src="<?=h($editorial['lead_image'])?>" alt="<?=h($editorial['lead_image_alt'])?>" width="1800" height="1350" fetchpriority="high"><figcaption><?=h($editorial['lead_caption'])?></figcaption></figure>
        <figure><img src="<?=h($editorial['detail_image'])?>" alt="<?=h($editorial['detail_image_alt'])?>" width="1800" height="1350" loading="lazy"><figcaption><?=h($editorial['detail_caption'])?></figcaption></figure>
    </div>
</div></section>
<section class="about-story" id="page-details"><div class="wrap">
    <div class="about-story-heading"><p class="eyebrow"><?=h($page['detail_eyebrow'])?></p><h2><?=site_lines($page['detail_title'])?></h2><?php if(trim((string)($page['detail_intro']??''))!==''): ?><p><?=h($page['detail_intro'])?></p><?php endif; ?></div>
    <div class="about-story-columns"><?php foreach($page['sections'] as $section): ?><article><h3><?=h($section['title'])?></h3><p><?=h($section['body'])?></p></article><?php endforeach; ?></div>
</div></section>
<section class="about-closing"><div class="wrap about-closing-grid">
    <figure><img src="<?=h($editorial['wide_image'])?>" alt="<?=h($editorial['wide_image_alt'])?>" width="1800" height="1350" loading="lazy"><figcaption><?=h($editorial['wide_caption'])?></figcaption></figure>
    <div class="about-next-step"><p class="eyebrow"><?=h($editorial['cta_eyebrow'])?></p><h2><?=site_lines($editorial['cta_title'])?></h2><p><?=h($editorial['cta_body'])?></p><a class="button" href="<?=h($page['cta']['href'])?>"><?=h($page['cta']['label'])?><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div>
</div></section>
</main>
<?php site_footer(); ?>
