<?php
$copy=$homeContent['projects'];
$projects=sf_collection('projects',true);
$category=is_string($_GET['category'] ?? null)?trim($_GET['category']):'';
$categories=array_values(array_filter(array_unique(array_column($projects,'category'))));sort($categories);
$filtered=array_values(array_filter($projects,static fn(array $p):bool=>$category==='' || ($p['category'] ?? '')===$category));
site_header('Projects',$route,'Project information and work scopes from Seafort’s marine and offshore activities.');
?>
<main id="main">
<section class="projects-hero" aria-labelledby="project-heading">
    <div class="wrap">
        <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span>Projects</span></nav>
        <div class="projects-title">
            <div><p class="eyebrow eyebrow-light"><?=h($copy['eyebrow'])?></p><h1 id="project-heading"><?=site_lines($copy['title'])?></h1></div>
            <p><?=h($projects ? $copy['description'] : $copy['activities_intro'])?></p>
        </div>
        <div class="work-photo-grid">
            <?php foreach($copy['activities'] as $activity): ?>
            <figure class="work-photo">
                <img src="<?=h($activity['image'])?>" alt="<?=h($activity['image_alt'])?>" width="960" height="720">
                <figcaption>
                    <h2><?=h($activity['title'])?></h2>
                    <p><?=h($activity['body'])?></p>
                    <a class="text-link light" href="<?=h($activity['link_href'])?>"><?=h($activity['label'])?></a>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php if($projects): ?>
<section class="section projects-list"><div class="wrap"><div class="section-intro"><div><p class="eyebrow"><?=h($copy['directory_eyebrow'])?></p><h2><?=site_lines($copy['directory_title'])?></h2></div><p class="section-description"><?=h($copy['directory_intro'])?></p></div>
<nav class="project-topics" aria-label="Filter projects by category"><a href="/projects/" <?=$category===''?'aria-current="page"':''?>>All Projects</a><?php foreach($categories as $cat): ?><a href="/projects/?<?=h(http_build_query(['category'=>$cat]))?>" <?=$category===$cat?'aria-current="page"':''?>><?=h($cat)?></a><?php endforeach; ?></nav>
<?php if($filtered): ?><div class="project-grid"><?php foreach($filtered as $item):$cover=sf_cover_url($item['cover'] ?? ''); ?><article class="project-card"><a class="project-cover" href="/projects/<?=h($item['slug'])?>/"><?php if($cover): ?><img src="<?=h($cover)?>" alt="<?=h($item['cover_alt'] ?? '')?>" width="960" height="720" loading="lazy"><?php endif; ?></a><div class="project-card-copy"><div class="project-kicker"><span><?=h($item['category'] ?? '')?></span><?php if(!empty($item['year'])): ?><span><?=h($item['year'])?></span><?php endif; ?></div><h3><a href="/projects/<?=h($item['slug'])?>/"><?=h($item['title'])?></a></h3><p><?=h($item['summary'])?></p><a class="text-link" href="/projects/<?=h($item['slug'])?>/">Explore Project</a></div></article><?php endforeach; ?></div>
<?php else: ?>
    <div class="project-empty-result"><p>No projects in this category yet.</p><a class="text-link" href="/projects/">All Projects</a></div>
<?php endif; ?>
</div></section>
<?php endif; ?>
<?php site_cta(); ?></main><?php site_footer(); ?>
