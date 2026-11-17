<?php
declare(strict_types=1);
function sf_reference_logo_markup(array $item,bool $duplicate=false): void
{
    $href=sf_reference_link((string)($item['href']??''));$external=str_starts_with($href,'https://');$tag=$href!==''&&!$duplicate?'a':'div';
    $name=(string)($item['name']??'');$generic=preg_match('/^Reference logo [0-9]+$/i',$name);
    echo '<'.$tag.' class="reference-logo-card"'.($tag==='a'?' href="'.sf_escape($href).'"'.($external?' target="_blank" rel="noopener noreferrer"':''):'').'>';
    echo '<img src="'.sf_escape(sf_cover_url($item['image'])).'" alt="'.sf_escape($duplicate?'':($item['image_alt']?:$name)).'" width="240" height="160" loading="lazy">';
    if(!$generic)echo '<span class="reference-logo-name">'.sf_escape($name).'</span>';
    echo '</'.$tag.'>';
}
function sf_reference_logos_markup(array $items,array $settings): void
{
    $moving=count($items)>=4;
    ?><section class="reference-logos-section section" aria-labelledby="reference-logos-title"><div class="wrap"><div class="reference-section-heading"><div><p class="eyebrow"><?=sf_escape($settings['logos_eyebrow'])?></p><h2 id="reference-logos-title"><?=sf_escape($settings['logos_title'])?></h2></div><a class="text-link" href="/references/">View all references <span aria-hidden="true">↗</span></a></div>
    <?php if($settings['logos_intro']!==''):?><p class="reference-section-intro"><?=sf_escape($settings['logos_intro'])?></p><?php endif;?>
    <?php if($moving):?><input class="reference-pause-input" type="checkbox" id="pause-reference-logos"><label class="reference-pause-label" for="pause-reference-logos">Pause logo animation</label><?php endif;?>
    <div class="reference-logo-viewport <?=$moving?'has-motion':''?>" tabindex="0" role="region" aria-label="Company references; focus to pause"><div class="reference-logo-track"><div class="reference-logo-set"><?php foreach($items as $item)sf_reference_logo_markup($item);?></div><?php if($moving):?><div class="reference-logo-set reference-logo-duplicate" aria-hidden="true"><?php foreach($items as $item)sf_reference_logo_markup($item,true);?></div><?php endif;?></div></div></div></section><?php
}
function sf_reference_quote_card(array $item): void
{
    ?><figure class="reference-quote-card"><span class="reference-quote-mark" aria-hidden="true">“</span><blockquote><p><?=nl2br(sf_escape($item['quote']),false)?></p></blockquote><?php if($item['name']!==''||$item['role']!==''||$item['company']!==''):?><figcaption><?php if($item['name']!==''):?><strong><?=sf_escape($item['name'])?></strong><?php endif;?><?php if($item['role']!==''):?><span><?=sf_escape($item['role'])?></span><?php endif;?><?php if($item['company']!==''):?><span class="reference-quote-company"><?=sf_escape($item['company'])?></span><?php endif;?></figcaption><?php endif;?><?php if($item['href']!==''):$href=sf_reference_link($item['href']);?><a class="text-link" href="<?=sf_escape($href)?>" <?=str_starts_with($href,'https://')?'target="_blank" rel="noopener noreferrer"':''?>>View related work</a><?php endif;?></figure><?php
}
function sf_reference_quotes_markup(array $items,array $settings): void
{
    ?><section class="reference-quotes-section section" aria-labelledby="reference-quotes-title"><div class="wrap"><div class="reference-section-heading"><div><p class="eyebrow"><?=sf_escape($settings['quotes_eyebrow'])?></p><h2 id="reference-quotes-title"><?=sf_escape($settings['quotes_title'])?></h2></div><?php if($settings['quotes_intro']!==''):?><p><?=sf_escape($settings['quotes_intro'])?></p><?php endif;?></div><div class="reference-quotes-grid"><?php foreach($items as $item)sf_reference_quote_card($item);?></div></div></section><?php
}
function sf_reference_custom_markup(array $page): void
{
    $placeholder=!empty($page['preview_placeholder']);
    ?><main id="main" class="ege-derin-page"><section class="reference-detail-hero"><div class="wrap"><nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span><?=sf_escape($page['title'])?></span></nav><div class="reference-detail-grid"><div><p class="eyebrow eyebrow-light">UNDERWATER</p><h1><?=sf_escape($page['title'])?></h1><?php if($placeholder):?><p class="reference-placeholder-label">Preview layout · placeholder text</p><?php endif;?><p><?=sf_escape($page['intro'])?></p></div><figure><img src="<?=sf_escape(sf_cover_url($page['cover']))?>" alt="<?=sf_escape($page['cover_alt'])?>" width="1200" height="900" fetchpriority="high"></figure></div></div></section><section class="section reference-detail-body"><div class="wrap"><div class="reference-detail-heading"><p class="eyebrow">EGE DERİN</p><h2><?=$placeholder?'Underwater works':'Our contribution'?></h2></div><div class="reference-detail-content"><div class="reference-role-copy"><p><?=nl2br(sf_escape($page['role']),false)?></p></div><article class="prose"><?=sf_paragraphs($page['body'])?></article></div></div></section><?php site_cta();?></main><?php
}
