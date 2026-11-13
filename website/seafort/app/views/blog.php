<?php
$copy=$homeContent['blog'];
$q=is_string($_GET['q'] ?? null)?mb_safe_query($_GET['q']):'';$category=is_string($_GET['category'] ?? null)?mb_safe_query($_GET['category']):'';
$all=sf_posts(true);$categories=array_values(array_unique(array_column($all,'category')));sort($categories);
$filtered=array_values(array_filter($all,static function(array $p) use($q,$category):bool {
    return ($category==='' || $p['category']===$category) && ($q==='' || stripos($p['title'].' '.$p['excerpt'].' '.$p['body'],$q)!==false);
}));
$current=max(1,(int)($_GET['page'] ?? 1));$count=count($filtered);$last=max(1,(int)ceil($count/6));$current=min($current,$last);$posts=array_slice($filtered,($current-1)*6,6);
site_header('Marine Operations Blog',$route,'Practical notes on vessel maintenance, underwater work and technical supply.'); ?>
<main id="main"><section class="editorial-hero"><div class="wrap"><nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span>Blog</span></nav><div class="editorial-title"><div><p class="eyebrow eyebrow-light"><?=h($copy['page_eyebrow'])?></p><h1><?=site_lines($copy['title'])?></h1></div><p><?=h($copy['description'])?></p></div></div></section>
<section class="section blog-content"><div class="wrap"><form class="blog-filters" method="get" action="/blog/" role="search"><div class="search-field"><label for="blog-search">Search articles</label><input id="blog-search" type="search" name="q" value="<?=h($q)?>" maxlength="100" placeholder="Search the journal…"></div><div><label for="blog-category">Topic</label><select id="blog-category" name="category"><option value="">All topics</option><?php foreach($categories as $cat): ?><option value="<?=h($cat)?>" <?=$category===$cat?'selected':''?>><?=h($cat)?></option><?php endforeach; ?></select></div><button class="button" type="submit">Search</button><?php if($q!=='' || $category!==''): ?><a class="filter-clear" href="/blog/">Clear filters</a><?php endif; ?></form>
<p class="result-label"><?=h($count)?> article<?=$count===1?'':'s'?><?=$q!==''?' matching “'.h($q).'”':''?></p>
<?php if($posts): ?><div class="post-grid"><?php foreach($posts as $post) site_post_card($post); ?></div><?php else: ?><div class="empty-state"><h2>No articles found.</h2><p>Try another search term or view all topics.</p><a class="text-link" href="/blog/">Show All Articles</a></div><?php endif; ?>
<?php if($last>1): ?><nav class="pagination" aria-label="Blog pages"><?php for($i=1;$i<=$last;$i++): ?><a href="/blog/?<?=h(http_build_query(['q'=>$q,'category'=>$category,'page'=>$i]))?>" <?=$i===$current?'aria-current="page"':''?>><?=$i?></a><?php endfor; ?></nav><?php endif; ?>
</div></section><?php site_cta(); ?></main><?php site_footer(); ?>
