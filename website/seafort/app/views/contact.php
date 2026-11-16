<?php
$career = $slug === 'careers';
$formCopy = is_array($enquiryForm ?? null) ? $enquiryForm : [];
$formResult = is_array($enquiryResult ?? null) ? $enquiryResult : [];
$formValues = is_array($formResult['values'] ?? null) ? $formResult['values'] : [];
$formErrors = is_array($formResult['errors'] ?? null) ? $formResult['errors'] : [];
$formSuccess = ($formResult['success'] ?? false) === true;
$formPreview = ($formCopy['preview'] ?? (($formCopy['mode'] ?? 'preview') === 'preview')) === true;
$formEnabled = ($formCopy['enabled'] ?? true) === true;
$fieldLabels = is_array($formCopy['labels'] ?? null) ? $formCopy['labels'] : [];
$fieldDefaults = ['name'=>'Your name','email'=>'Email address','subject'=>$career?'Role / area of interest':'Vessel / company / project','message'=>$career?'Experience and availability':'Project requirements','attachment'=>$career?'Attach your CV':'Attach a document or photograph'];
$fieldLabel = static fn(string $key): string => (string) ($fieldLabels[$key] ?? $fieldDefaults[$key] ?? $key);
$fieldValue = static fn(string $key): string => is_string($formValues[$key] ?? null) ? $formValues[$key] : '';
$fieldError = static fn(string $key): string => is_string($formErrors[$key] ?? null) ? $formErrors[$key] : '';
$accept = (string) ($formCopy['attachment_accept'] ?? ($career ? '.pdf' : '.pdf,.jpg,.jpeg,.png'));
$limitMb = max(1, min(10, (int) ($formCopy['attachment_limit_mb'] ?? 5)));
$fileHint = (string) ($formCopy['file_hint'] ?? ($career ? 'PDF CV, up to '.$limitMb.' MB. Optional.' : 'PDF, JPG or PNG, up to '.$limitMb.' MB. Optional.'));
$confirmation = trim((string) ($formCopy['privacy_text'] ?? ''));
if ($confirmation === '') $confirmation = $career ? 'I confirm that these details may be used to respond to my application.' : 'I confirm that these details may be used to respond to my enquiry.';
$formAction = '/'.($career ? 'careers' : 'get-a-quote').'/';
$formReference = is_string($formResult['reference'] ?? null) ? $formResult['reference'] : '';
$hasErrors = $formErrors !== [];
$directEmail = (string) ($formCopy['direct_email'] ?? ($career ? ($siteConfig['careers_email'] ?? '') : $siteConfig['email']));
?>
<section class="section contact-section enquiry-section" id="contact-details">
<div class="wrap contact-layout enquiry-layout">
    <div class="contact-copy enquiry-copy">
        <p class="eyebrow"><?=h($page['eyebrow'])?></p><h2><?=h($page['sections'][0]['title'])?></h2><p><?=h($page['sections'][0]['body'])?></p>
        <div class="contact-note enquiry-notes"><?php foreach ($page['sections'] as $i=>$section): ?><?php if ($i>0): ?><h3><?=h($section['title'])?></h3><p><?=h($section['body'])?></p><?php endif; ?><?php if (!empty($section['list'])): ?><ul class="scope-list"><?php foreach ($section['list'] as $item): ?><li><?=h($item)?></li><?php endforeach; ?></ul><?php endif; ?><?php endforeach; ?></div>
        <div class="enquiry-alternative"><span class="eyebrow">DIRECT CONTACT</span><?php if ($directEmail!==''): ?><a class="enquiry-email" href="mailto:<?=h($directEmail)?>"><?=h($directEmail)?></a><?php endif; ?><a class="text-link" href="<?=$career?'/get-a-quote/':'/careers/'?>"><?=$career?'Have a project enquiry?':'Looking for a role with Seafort?'?><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div>
    </div>
    <div class="enquiry-panel">
        <div class="enquiry-panel-heading"><span class="enquiry-panel-marker" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h10l4 4v12H5ZM14 4v5h5M9 12h6M9 16h6"/></svg></span><div><p class="eyebrow"><?=$career?'CAREER APPLICATION':'PROJECT ENQUIRY'?></p><h2><?=h((string) ($formCopy['title']??($career?'Tell us about yourself.':'Tell us what you need.')))?></h2></div></div>
        <p class="enquiry-intro"><?=h((string) ($formCopy['intro']??($career?'Share your discipline, experience and availability with our recruitment team.':'Share the essentials so our team can review your requirements.')))?></p>
        <?php if ($formSuccess): ?>
            <div class="enquiry-feedback enquiry-feedback-success" id="enquiry-result" role="status" tabindex="-1" data-enquiry-feedback><span class="enquiry-feedback-symbol" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></span><h3><?=($formResult['preview']??false)?'Preview submission saved.':'Thank you for getting in touch.'?></h3><p><?=h((string) (($formResult['preview']??false)?($formCopy['preview_text']??'Your details have been recorded for this preview.'):($formCopy['success_text']??'Your message has been submitted.')))?></p><?php if ($formResult['preview']??false): ?><p class="enquiry-no-email">No email has been sent.</p><?php endif; ?><?php if ($formReference!==''): ?><p class="enquiry-reference">Reference <strong data-no-translate><?=h($formReference)?></strong></p><?php endif; ?><a class="button" href="<?=h($formAction)?>?new=1#contact-details">Start another message<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></a></div>
        <?php elseif (!$formEnabled): ?>
            <div class="enquiry-feedback enquiry-feedback-unavailable" role="status"><h3>This form is currently unavailable.</h3><p><?=h((string) ($formCopy['unavailable_text']??'Please use the direct contact address shown on this page.'))?></p></div>
        <?php else: ?>
            <?php if ($formPreview): ?><div class="enquiry-preview-note"><span class="enquiry-preview-dot" aria-hidden="true"></span><p><strong>Preview mode.</strong> Submissions are saved for review. No email will be sent.</p></div><?php endif; ?>
            <?php if ($hasErrors): ?><div class="enquiry-feedback enquiry-feedback-error" id="enquiry-errors" role="alert" tabindex="-1" data-enquiry-feedback><h3>Please check your message.</h3><ul><?php foreach ($formErrors as $key=>$message): ?><?php if (is_string($message)&&$message!==''): ?><li><?php if (in_array($key,['name','email','subject','message','attachment','consent'],true)): ?><a href="#enquiry-<?=h($key)?>"><?=h($message)?></a><?php else: ?><?=h($message)?><?php endif; ?></li><?php endif; ?><?php endforeach; ?></ul></div><?php endif; ?>
            <form class="enquiry-form" id="enquiry-form" action="<?=h($formAction)?>#contact-details" method="post" enctype="multipart/form-data" data-enquiry-form data-file-limit="<?=$limitMb*1024*1024?>" data-file-limit-label="<?=$limitMb?> MB">
                <input type="hidden" name="csrf" value="<?=h((string) ($enquiryCsrf??''))?>"><input type="hidden" name="submission_token" value="<?=h((string) ($enquiryNonce??''))?>">
                <div class="enquiry-honeypot" aria-hidden="true"><label for="enquiry-website">Leave this field empty</label><input id="enquiry-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                <p class="enquiry-required-note">Fields marked <span aria-hidden="true">*</span> are required.</p>
                <div class="enquiry-field-grid"><?php foreach (['name','email'] as $field): ?><div class="enquiry-field"><label for="enquiry-<?=h($field)?>"><?=h($fieldLabel($field))?> <span aria-hidden="true">*</span></label><input id="enquiry-<?=h($field)?>" name="<?=h($field)?>" type="<?=$field==='email'?'email':'text'?>" autocomplete="<?=h($field)?>" required maxlength="<?=$field==='email'?180:120?>" value="<?=h($fieldValue($field))?>"<?=$fieldError($field)!==''?' aria-invalid="true" aria-describedby="enquiry-'.$field.'-error"':''?>><?php if ($fieldError($field)!==''): ?><p class="enquiry-field-error" id="enquiry-<?=h($field)?>-error"><?=h($fieldError($field))?></p><?php endif; ?></div><?php endforeach; ?></div>
                <div class="enquiry-field"><label for="enquiry-subject"><?=h($fieldLabel('subject'))?></label><input id="enquiry-subject" name="subject" type="text" maxlength="160" value="<?=h($fieldValue('subject'))?>"<?=$fieldError('subject')!==''?' aria-invalid="true" aria-describedby="enquiry-subject-error"':''?>><?php if ($fieldError('subject')!==''): ?><p class="enquiry-field-error" id="enquiry-subject-error"><?=h($fieldError('subject'))?></p><?php endif; ?></div>
                <div class="enquiry-field"><label for="enquiry-message"><?=h($fieldLabel('message'))?> <span aria-hidden="true">*</span></label><textarea id="enquiry-message" name="message" required maxlength="6000" rows="6" aria-describedby="enquiry-message-hint<?=$fieldError('message')!==''?' enquiry-message-error':''?>"<?=$fieldError('message')!==''?' aria-invalid="true"':''?>><?=h($fieldValue('message'))?></textarea><p class="enquiry-field-hint" id="enquiry-message-hint"><?=$career?'Include relevant experience, qualifications and when you are available.':'Include the work location, target dates and the support you need.'?></p><?php if ($fieldError('message')!==''): ?><p class="enquiry-field-error" id="enquiry-message-error"><?=h($fieldError('message'))?></p><?php endif; ?></div>
                <?php if (!empty($formCopy['attachments'])): ?>
                <div class="enquiry-field enquiry-attachment"><label for="enquiry-attachment"><?=h($fieldLabel('attachment'))?> <span class="enquiry-optional">Optional</span></label><div class="enquiry-upload"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 12 6-6a3 3 0 0 1 4 4l-8 8a5 5 0 0 1-7-7l9-9M6 14l8-8"/></svg><input id="enquiry-attachment" name="attachment" type="file" accept="<?=h($accept)?>" aria-describedby="enquiry-file-hint enquiry-file-status<?=$fieldError('attachment')!==''?' enquiry-attachment-error':''?>"<?=$fieldError('attachment')!==''?' aria-invalid="true"':''?>></div><p class="enquiry-field-hint" id="enquiry-file-hint"><?=h($fileHint)?></p><p class="enquiry-file-status" id="enquiry-file-status" role="status"></p><?php if ($fieldError('attachment')!==''): ?><p class="enquiry-field-error" id="enquiry-attachment-error"><?=h($fieldError('attachment'))?></p><?php endif; ?><?php if ($hasErrors): ?><p class="enquiry-field-hint">If you attached a file, please select it again before submitting.</p><?php endif; ?></div>
                <?php endif; ?>
                <div class="enquiry-field enquiry-consent"><label for="enquiry-consent"><input id="enquiry-consent" name="consent" type="checkbox" value="1" required<?=$fieldValue('consent')==='1'?' checked':''?><?=$fieldError('consent')!==''?' aria-invalid="true" aria-describedby="enquiry-consent-error"':''?>><span><?=h($confirmation)?> <span aria-hidden="true">*</span></span></label><?php if ($fieldError('consent')!==''): ?><p class="enquiry-field-error" id="enquiry-consent-error"><?=h($fieldError('consent'))?></p><?php endif; ?></div>
                <div class="enquiry-form-actions"><button class="button enquiry-submit" type="submit"><span data-submit-label><?=h((string) ($formCopy['submit_label']??($career?'Submit application':'Send enquiry')))?></span><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg></button><p class="enquiry-submit-status sr-only" role="status" data-submit-status></p></div>
                <noscript><p class="enquiry-field-hint">You can submit this form without JavaScript.</p></noscript>
            </form>
        <?php endif; ?>
    </div>
</div>
</section>
