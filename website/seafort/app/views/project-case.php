<?php
// Shared presentation for published cases and the authenticated administrator preview.
$casePreview = ($casePreview ?? false) === true;
$caseCover = sf_cover_url((string) ($project['cover'] ?? ''));
$caseStructured = sf_project_is_case($project);
$caseSections = ['need'=>['01','The need'],'scope'=>['02','Seafort’s scope'],'method'=>['03','The approach'],'result'=>['04','The verified outcome']];
$caseFacts = ['customer_name'=>'CLIENT / EMPLOYER','location'=>'LOCATION','timeframe'=>'WORK PERIOD','year'=>'YEAR','acceptance_status'=>'PROJECT STATUS'];
$caseStages = ['before'=>'Before','during'=>'During the work','after'=>'After'];
$caseHasFacts = false;
foreach ($caseFacts as $factKey=>$factLabel) { if (is_string($project[$factKey] ?? null) && trim($project[$factKey]) !== '') $caseHasFacts = true; }
?>
<div class="case-study">
    <section class="project-detail-hero case-hero">
        <div class="wrap">
            <?php if (!$casePreview): ?><nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><a href="/projects/">Projects</a><span aria-hidden="true">/</span><span><?=sf_escape((string) $project['title'])?></span></nav><?php endif; ?>
            <p class="eyebrow eyebrow-light"><?=sf_escape((string) (($project['category'] ?? '') ?: 'PROJECT CASE STUDY'))?></p>
            <?php if ($casePreview): ?><h2 class="case-title"><?=sf_escape((string) $project['title'])?></h2><?php else: ?><h1 class="case-title"><?=sf_escape((string) $project['title'])?></h1><?php endif; ?>
            <?php if (!empty($project['summary'])): ?><p class="case-summary"><?=sf_escape((string) $project['summary'])?></p><?php elseif ($casePreview): ?><p class="case-pending">Project introduction awaiting verified information.</p><?php endif; ?>
            <?php if ($caseHasFacts): ?><dl class="project-facts case-facts"><?php foreach ($caseFacts as $factKey=>$factLabel): ?><?php if (is_string($project[$factKey] ?? null) && trim($project[$factKey]) !== ''): ?><div><dt><?=sf_escape($factLabel)?></dt><dd><?=sf_escape($project[$factKey])?></dd></div><?php endif; ?><?php endforeach; ?></dl><?php endif; ?>
        </div>
    </section>
    <?php if ($caseCover !== ''): ?><figure class="project-main-cover case-main-cover wrap"><img src="<?=sf_escape($caseCover)?>" alt="<?=sf_escape((string) ($project['cover_alt'] ?? ''))?>" width="1680" height="1077"></figure><?php elseif ($casePreview): ?><div class="wrap"><div class="case-image-pending"><svg viewBox="0 0 48 48" aria-hidden="true"><path d="M7 10h34v28H7ZM7 30l10-10 9 9 6-6 9 10M29 17h1"/></svg><p>Project photograph awaiting verification.</p><span>No archive image has been assigned to this case.</span></div></div><?php endif; ?>

    <?php if ($caseStructured): ?>
        <section class="section case-detail-section"><div class="wrap case-content-grid">
            <aside class="case-outline"><p class="eyebrow">THE PROJECT</p><h2>From the need<br>to the outcome.</h2><nav aria-label="Case study sections"><?php foreach ($caseSections as $caseKey=>[$caseNumber,$caseTitle]): ?><?php if (!empty($project[$caseKey]) || $casePreview): ?><a href="#case-<?=sf_escape($caseKey)?>"><span><?=sf_escape($caseNumber)?></span><?=sf_escape($caseTitle)?></a><?php endif; ?><?php endforeach; ?></nav><?php if (!$casePreview): ?><a class="text-link" href="/projects/">All projects</a><?php endif; ?></aside>
            <div class="case-sections"><?php foreach ($caseSections as $caseKey=>[$caseNumber,$caseTitle]): ?><?php if (!empty($project[$caseKey]) || $casePreview): ?><article class="case-section" id="case-<?=sf_escape($caseKey)?>"><span class="case-section-number" aria-hidden="true"><?=sf_escape($caseNumber)?></span><div><h2><?=sf_escape($caseTitle)?></h2><?php if (!empty($project[$caseKey])): ?><div class="prose case-prose"><?=sf_paragraphs((string) $project[$caseKey])?></div><?php else: ?><p class="case-pending">Awaiting verified project information.</p><?php endif; ?></div></article><?php endif; ?><?php endforeach; ?><?php if (!empty($project['body'])): ?><article class="case-section case-additional-copy"><span class="case-section-number" aria-hidden="true">+</span><div><h2>Further project details</h2><div class="prose case-prose"><?=sf_paragraphs((string) $project['body'])?></div></div></article><?php endif; ?></div>
        </div></section>
    <?php elseif (!empty($project['body'])): ?>
        <section class="section project-detail-body"><div class="wrap project-body-grid"><aside><p class="eyebrow">THE PROJECT</p><h2>Understanding<br>the scope.</h2><a class="text-link" href="/projects/">All Projects</a></aside><article class="prose"><?=sf_paragraphs((string) $project['body'])?></article></div></section>
    <?php endif; ?>

    <?php if (!empty($project['gallery']) && is_array($project['gallery'])): ?>
        <section class="section case-gallery-section"><div class="wrap"><div class="case-gallery-heading"><div><p class="eyebrow">THE WORK IN VIEW</p><h2>Recorded through the work.</h2></div><p>Photographs and captions from this project.</p></div><div class="case-gallery"><?php foreach ($project['gallery'] as $casePhoto): ?><?php $casePhotoUrl=is_array($casePhoto)?sf_cover_url((string) ($casePhoto['image']??'')):'';if ($casePhotoUrl==='') continue; ?><figure><img src="<?=sf_escape($casePhotoUrl)?>" alt="<?=sf_escape((string) ($casePhoto['caption']??''))?>" width="960" height="720" loading="lazy"><figcaption><?php if (isset($caseStages[$casePhoto['stage']??''])): ?><span class="eyebrow"><?=sf_escape($caseStages[$casePhoto['stage']])?></span><?php endif; ?><?php if (!empty($casePhoto['caption'])): ?><p><?=sf_escape((string) $casePhoto['caption'])?></p><?php endif; ?></figcaption></figure><?php endforeach; ?></div></div></section>
    <?php endif; ?>
</div>
