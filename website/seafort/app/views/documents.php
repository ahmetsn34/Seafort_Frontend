<?php
$documentSettings=sf_documents_settings();
$category=is_string($_GET['category']??null)&&array_key_exists($_GET['category'],sf_document_categories())?$_GET['category']:'';
$language=is_string($_GET['language']??null)&&array_key_exists($_GET['language'],sf_document_languages())?$_GET['language']:'';
$documents=sf_documents_list();
$generalBrochure=sf_brochure_entry('general');
$matches=array_filter($documents, static fn(array $item): bool =>
    ($category === '' || $item['category'] === $category) && ($language === '' || $item['language'] === $language)
);
site_header($documentSettings['title'],'/documents/',$documentSettings['intro']);
?>
<main id="main"><section class="documents-hero"><div class="wrap"><nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span>Documents</span></nav><div class="documents-hero-content"><div><p class="eyebrow eyebrow-light">SEAFORT / DOCUMENTATION</p><h1><?=h($documentSettings['title'])?></h1><p><?=h($documentSettings['intro'])?></p></div><svg viewBox="0 0 110 120" class="documents-hero-icon" aria-hidden="true"><path d="M29 9h39l22 24v72H29ZM67 9v26h23M41 52h35M41 66h35M41 80h22M17 23v92h60"/></svg></div></div></section>
<section class="section documents-section"><div class="wrap">
<?php if ($generalBrochure): ?>
    <div class="company-brochure">
        <h2>Brochures</h2>
        <a class="button" href="/brochure/?page=general"><span><?=h($generalBrochure['label'])?></span><small>PDF</small></a>
    </div>
<?php endif; ?>
<?php if ($documents): ?>
    <form class="documents-filters" method="get" action="/documents/">
        <div><label for="document-category">Document type</label><select id="document-category" name="category"><option value="">All documents</option><?php foreach(sf_document_categories() as $key=>$label):?><option value="<?=h($key)?>" <?=$category===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></div>
        <div><label for="document-language">Language</label><select id="document-language" name="language"><option value="">All languages</option><?php foreach(sf_document_languages() as $key=>$label):?><option value="<?=h($key)?>" <?=$language===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></div>
        <button class="button" type="submit">Apply filters</button>
    </form>
    <?php if ($matches): sf_documents_render($category, $matches); else: ?>
        <div class="documents-empty"><h2>No documents match these filters.</h2><a class="text-link" href="/documents/">Clear filters</a></div>
    <?php endif; ?>
<?php elseif (!$generalBrochure): ?>
    <div class="documents-empty">
        <h2><?=h($documentSettings['empty_title'])?></h2><p><?=h($documentSettings['empty_text'])?></p>
        <a class="button" href="mailto:<?=h(rawurlencode($siteConfig['email']))?>?subject=Brochure%20and%20document%20request">Request a brochure</a>
    </div>
<?php endif; ?>
</div></section></main>
<?php site_footer(); ?>
