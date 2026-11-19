<?php
$casePreview = false;
site_header((string) $project['title'], $route, (string) ($project['summary'] ?? ''), ['kind'=>'project','item'=>$project,'image'=>(string)($project['cover']??''),'image_alt'=>(string)($project['cover_alt']??'')]);
?>
<main id="main">
<?php require __DIR__.'/project-case.php'; ?>
<?php site_cta(); ?>
</main>
<?php site_footer(); ?>
