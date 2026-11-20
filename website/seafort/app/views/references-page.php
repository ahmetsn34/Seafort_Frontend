<?php
$referenceData=sf_reference_data();$referenceCopy=$referenceData['settings'];
$referenceLogos=sf_reference_items('logos');$referenceQuotes=sf_reference_items('quotes');
require_once __DIR__.'/reference-blocks.php';
site_header($referenceCopy['page_title'],$route,$referenceCopy['page_intro']);
?>
<main id="main" class="reference-portfolio">
<section class="reference-page-opening"><div class="wrap"><nav class="breadcrumbs"><a href="/">Home</a><span aria-hidden="true">/</span><span>References</span></nav><p class="eyebrow">SEAFORT / REFERENCES</p><h1><?=h($referenceCopy['page_title'])?></h1><p class="reference-page-lead"><?=h($referenceCopy['page_intro'])?></p>
<?php if($referenceQuotes): ?>
<nav class="reference-page-jump" aria-label="Reference sections"><a href="#companies">Company references <span aria-hidden="true">↘</span></a><a href="#written-references">Written references <span aria-hidden="true">↘</span></a></nav>
<?php endif; ?></div></section>
<section class="section reference-company-section" id="companies"><div class="wrap"><div class="reference-section-heading"><div><p class="eyebrow"><?=h($referenceCopy['logos_eyebrow'])?></p><h2><?=h($referenceCopy['logos_title'])?></h2></div><?php if($referenceCopy['logos_intro']!==''):?><p><?=h($referenceCopy['logos_intro'])?></p><?php endif;?></div><div class="reference-company-grid"><?php foreach($referenceLogos as $referenceLogo)sf_reference_logo_markup($referenceLogo);?></div><?php if(!$referenceLogos):?><p class="reference-empty-note">Company references will appear here when added.</p><?php endif;?></div></section>
<?php if($referenceQuotes): ?>
<section class="section reference-written-section" id="written-references"><div class="wrap"><div class="reference-section-heading"><div><p class="eyebrow"><?=h($referenceCopy['quotes_eyebrow'])?></p><h2><?=h($referenceCopy['quotes_title'])?></h2></div><?php if($referenceCopy['quotes_intro']!==''):?><p><?=h($referenceCopy['quotes_intro'])?></p><?php endif;?></div>
<div class="reference-quotes-grid"><?php foreach($referenceQuotes as $referenceQuote)sf_reference_quote_card($referenceQuote);?></div>
</div></section><?php endif; site_cta();?></main><?php site_footer(); ?>
