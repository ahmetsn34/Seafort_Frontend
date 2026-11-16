<?php
$casePreview = false;
site_header((string) $project['title'], $route, (string) ($project['summary'] ?? ''));
?>
<main id="main">
<?php require __DIR__.'/project-case.php'; ?>
<?php site_cta(); ?>
</main>
<?php site_footer(); ?>
