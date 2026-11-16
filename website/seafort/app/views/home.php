<?php $c = $homeContent; ?>
<main id="main">
<?php require __DIR__.'/hero-slider.php'; ?>
<section class="section services" id="services" aria-labelledby="services-heading"><div class="wrap">
  <div class="section-intro"><div><p class="eyebrow"><?=h($c['services']['eyebrow'])?></p><h2 id="services-heading"><?=site_lines($c['services']['title'])?></h2></div><p class="section-description"><?=h($c['services']['description'])?></p></div>
  <div class="service-grid"><?php foreach($c['services']['items'] as $i=>$item): ?>
    <article class="service"><div class="service-top"><span class="service-number"><?=sprintf('%02d',$i+1)?></span></div><h3><?=site_lines($item['title'])?></h3><p><?=h($item['body'])?></p><a class="text-link" href="<?=h($item['link_href'])?>"><?=h($item['label'])?></a></article>
  <?php endforeach; ?></div>
</div></section>

<section class="section fpu-section" id="fpu-steelworks" aria-labelledby="fpu-heading"><div class="wrap fpu-layout">
  <div class="fpu-copy"><p class="eyebrow"><?=h($c['fpu']['eyebrow'])?></p><h2 id="fpu-heading"><?=site_lines($c['fpu']['title'])?></h2><p class="fpu-intro"><?=h($c['fpu']['description'])?></p><p><?=h($c['fpu']['detail'])?></p><a class="button" href="<?=h($c['fpu']['link_href'])?>"><?=h($c['fpu']['label'])?></a></div>
  <figure class="fpu-media">
    <?php if($c['fpu']['video_url'] !== ''): ?>
      <video controls playsinline preload="none" poster="<?=h($c['fpu']['poster'])?>" aria-label="<?=h($c['fpu']['video_caption'])?>"><source src="<?=h($c['fpu']['video_url'])?>" type="video/mp4"><p>Your browser does not support this video. <a href="<?=h($c['fpu']['video_url'])?>">Open the video</a>.</p></video>
      <figcaption><?=h($c['fpu']['video_caption'])?></figcaption>
    <?php else: ?>
      <img src="<?=h($c['fpu']['poster'])?>" alt="<?=h($c['fpu']['poster_alt'])?>" width="960" height="720" loading="lazy">
    <?php endif; ?>
  </figure>
</div></section>

<section class="section capabilities" id="capabilities" aria-labelledby="capabilities-heading"><div class="wrap">
  <div class="section-intro compact"><div><p class="eyebrow"><?=h($c['capabilities']['eyebrow'])?></p><h2 id="capabilities-heading"><?=site_lines($c['capabilities']['title'])?></h2></div><p class="section-description"><?=h($c['capabilities']['description'])?></p></div>
  <div class="capability-tabs" role="tablist" aria-label="Explore field capabilities"><?php foreach($c['capabilities']['items'] as $i=>$item): ?>
    <button type="button" role="tab" id="tab-capability-<?=$i?>" aria-selected="<?=$i===0?'true':'false'?>" aria-controls="panel-capability-<?=$i?>" tabindex="<?=$i===0?'0':'-1'?>"><span><?=sprintf('%02d',$i+1)?></span><?=h($item['tab'])?></button>
  <?php endforeach; ?></div>
  <?php foreach($c['capabilities']['items'] as $i=>$item): ?>
    <div class="capability-panel" role="tabpanel" id="panel-capability-<?=$i?>" aria-labelledby="tab-capability-<?=$i?>" tabindex="0" <?=$i===0?'':'hidden'?>>
      <div class="capability-photo"><img src="<?=h($item['image'])?>" alt="<?=h($item['image_alt'])?>" width="960" height="720" loading="lazy"><span class="photo-label"><?=h($item['image_label'])?></span></div>
      <div class="capability-copy"><p class="eyebrow"><?=h($item['eyebrow'])?></p><h3><?=site_lines($item['title'])?></h3><p><?=h($item['body'])?></p><ul class="scope-list"><?php foreach($item['list'] as $li): ?><li><?=h($li)?></li><?php endforeach; ?></ul><a class="text-link" href="<?=h($item['link_href'])?>"><?=h($item['label'])?></a></div>
    </div>
  <?php endforeach; ?>
</div></section>

<section class="section process" id="approach" aria-labelledby="process-heading"><div class="wrap">
  <div class="section-intro"><div><p class="eyebrow"><?=h($c['process']['eyebrow'])?></p><h2 id="process-heading"><?=site_lines($c['process']['title'])?></h2></div><p class="section-description"><?=h($c['process']['description'])?></p></div>
  <ol class="process-grid"><?php foreach($c['process']['items'] as $i=>$item): ?><li><span class="step"><?=sprintf('%02d',$i+1)?></span><h3><?=h($item['title'])?></h3><p><?=h($item['body'])?></p></li><?php endforeach; ?></ol>
  <div class="values-strip"><div class="values-icon"><svg viewBox="0 0 40 44" aria-hidden="true"><path d="m20 3 15 6v12c0 10-15 19-15 19S5 31 5 21V9Z"/><path d="m13 21 5 5 10-11"/></svg></div><div><p class="eyebrow"><?=h($c['values']['eyebrow'])?></p><h3><?=h($c['values']['title'])?></h3><p><?=h($c['values']['body'])?></p></div><a class="text-link" href="<?=h($c['values']['link_href'])?>"><?=h($c['values']['label'])?></a></div>
</div></section>
<?php sf_reference_logos_render(); sf_reference_quotes_render(); site_blog_strip(); ?>
<section class="careers" aria-labelledby="careers-heading"><div class="wrap careers-inner"><div><p class="eyebrow"><?=h($c['careers']['eyebrow'])?></p><h2 id="careers-heading"><?=h($c['careers']['title'])?></h2><p><?=h($c['careers']['body'])?></p></div><a class="text-link" href="<?=h($c['careers']['link_href'])?>"><?=h($c['careers']['label'])?></a></div></section>
<section class="quote-section" aria-labelledby="quote-heading"><div class="wrap quote-inner"><div><p class="eyebrow eyebrow-light"><?=h($c['quote']['eyebrow'])?></p><h2 id="quote-heading"><?=site_lines($c['quote']['title'])?></h2><p><?=h($c['quote']['body'])?></p></div><div class="quote-actions"><a class="button" href="<?=h($c['quote']['link_href'])?>"><?=h($c['quote']['label'])?></a><a class="email-link" href="mailto:<?=h($siteConfig['email'])?>"><?=h($siteConfig['email'])?></a></div></div></section>
</main>
