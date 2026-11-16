<?php
declare(strict_types=1);

function sf_reference_logo_markup(array $item,bool $duplicate=false): void
{
    $href=sf_reference_link((string)($item['href']??''));$external=str_starts_with($href,'https://');
    $relationship=['customer'=>'Customer','partner'=>'Partner','supplier'=>'Supplier'][$item['relationship']??'']??'';
    $tag=$href!==''&&!$duplicate?'a':'div';
    echo '<'.$tag.' class="reference-logo-card"'.($tag==='a'?' href="'.sf_escape($href).'"'.($external?' target="_blank" rel="noopener noreferrer"':''):'').'>';
    echo '<img src="'.sf_escape(sf_cover_url($item['image'])).'" alt="'.sf_escape($duplicate?'':($item['image_alt']?:$item['name'])).'" width="240" height="120" loading="lazy">';
    echo '<span class="reference-logo-name">'.sf_escape($item['name']).'</span><span class="reference-relationship">'.sf_escape($relationship).'</span></'.$tag.'>';
}

function sf_reference_logos_markup(array $items,array $settings): void
{
    $moving=count($items)>=4;
    ?><section class="reference-logos-section section" aria-labelledby="reference-logos-title"><div class="wrap"><div class="reference-section-heading"><div><p class="eyebrow"><?=sf_escape($settings['logos_eyebrow'])?></p><h2 id="reference-logos-title"><?=sf_escape($settings['logos_title'])?></h2></div><?php if($settings['logos_intro']!==''):?><p><?=sf_escape($settings['logos_intro'])?></p><?php endif;?></div><div class="reference-logo-viewport <?=$moving?'has-motion':''?>" tabindex="0" role="region" aria-label="Company logos; focus to pause movement"><div class="reference-logo-track"><div class="reference-logo-set"><?php foreach($items as $item)sf_reference_logo_markup($item);?></div><?php if($moving):?><div class="reference-logo-set reference-logo-duplicate" aria-hidden="true"><?php foreach($items as $item)sf_reference_logo_markup($item,true);?></div><?php endif;?></div></div></div></section><?php
}

function sf_reference_quotes_markup(array $items,array $settings): void
{
    ?><section class="reference-quotes-section section" aria-labelledby="reference-quotes-title"><div class="wrap"><div class="reference-section-heading"><div><p class="eyebrow"><?=sf_escape($settings['quotes_eyebrow'])?></p><h2 id="reference-quotes-title"><?=sf_escape($settings['quotes_title'])?></h2></div><?php if($settings['quotes_intro']!==''):?><p><?=sf_escape($settings['quotes_intro'])?></p><?php endif;?></div><div class="reference-quotes-grid"><?php foreach($items as $item):?><figure class="reference-quote-card"><span class="reference-quote-mark" aria-hidden="true">“</span><blockquote><p><?=nl2br(sf_escape($item['quote']),false)?></p></blockquote><?php if($item['name']!==''||$item['role']!==''||$item['company']!==''):?><figcaption><?php if($item['name']!==''):?><strong><?=sf_escape($item['name'])?></strong><?php endif;?><?php if($item['role']!==''):?><span><?=sf_escape($item['role'])?></span><?php endif;?><?php if($item['company']!==''):?><span class="reference-quote-company"><?=sf_escape($item['company'])?></span><?php endif;?></figcaption><?php endif;?><?php if($item['href']!==''):$href=sf_reference_link($item['href']);?><a class="text-link" href="<?=sf_escape($href)?>" <?=str_starts_with($href,'https://')?'target="_blank" rel="noopener noreferrer"':''?>>View related work</a><?php endif;?></figure><?php endforeach;?></div></div></section><?php
}

/** Public page markup; the caller supplies site_header/site_footer. */
function sf_reference_custom_markup(array $page): void
{
    ?><main id="main"><section class="reference-detail-hero"><div class="wrap"><nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><a href="/references/">References</a><span aria-hidden="true">/</span><span><?=sf_escape($page['title'])?></span></nav><div class="reference-detail-grid <?=$page['cover']===''?'without-image':''?>"><div><p class="eyebrow eyebrow-light">WORK IN FOCUS</p><h1><?=sf_escape($page['title'])?></h1><p><?=sf_escape($page['intro'])?></p></div><?php if($page['cover']!==''):?><figure><img src="<?=sf_escape(sf_cover_url($page['cover']))?>" alt="<?=sf_escape($page['cover_alt'])?>" width="960" height="720" fetchpriority="high"></figure><?php endif;?></div></div></section><section class="section reference-detail-body"><div class="wrap reference-detail-content"><aside><p class="eyebrow">SEAFORT’S ROLE</p><h2>Our contribution</h2><p><?=nl2br(sf_escape($page['role']),false)?></p></aside><article class="prose"><?=sf_paragraphs($page['body'])?></article></div></section></main><?php
}
