<?php
$certificateCopy=sf_documents_settings();
$certificates=sf_documents_list(true,'certificate');
$weldingDocuments=sf_documents_list(true,'wps');
site_header($certificateCopy['certificate_title'],$route,$certificateCopy['certificate_intro']);
?>
<main id="main" class="certificates-page"><section class="documents-hero"><div class="wrap"><nav class="breadcrumbs"><a href="/">Home</a><span aria-hidden="true">/</span><span>Certificates</span></nav><p class="eyebrow eyebrow-light">SEAFORT / DOCUMENTATION</p><h1><?=h($certificateCopy['certificate_title'])?></h1><p class="certificate-lead"><?=h($certificateCopy['certificate_intro'])?></p></div></section>
<section class="section"><div class="wrap">
<?php if($certificates || $weldingDocuments):?>
    <?php if($certificates):?><div class="certificate-group"><p class="eyebrow">COMPANY DOCUMENTATION</p><h2>Certificates</h2><?php sf_documents_render('certificate', $certificates);?></div><?php endif;?>
    <?php if($weldingDocuments):?><div class="certificate-group"><p class="eyebrow">TECHNICAL DOCUMENTATION</p><h2>Welding documents</h2><?php sf_documents_render('wps', $weldingDocuments);?></div><?php endif;?>
<?php else:?>
    <div class="certificate-empty"><svg viewBox="0 0 80 90" aria-hidden="true"><path d="M17 7h33l16 17v56H17ZM49 7v19h17M29 40h25M29 51h25M29 62h15"/><circle cx="56" cy="65" r="11"/><path d="m49 74-2 12 9-5 9 5-2-12"/></svg>
        <div><h2><?=h($certificateCopy['certificate_empty_title'])?></h2><p><?=h($certificateCopy['certificate_empty_text'])?></p>
        <a class="button" href="mailto:<?=h(rawurlencode($siteConfig['email']))?>?subject=Certificate%20request">Request a certificate</a></div>
    </div>
<?php endif;?>
</div></section></main><?php site_footer(); ?>
