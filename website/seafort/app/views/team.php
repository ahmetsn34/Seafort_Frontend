<?php
$members = sf_collection('team', true);
$teamCopy = sf_home_content()['team'];
$departments = [];
foreach ($members as $member) {
    $department = trim((string) ($member['department'] ?? '')) ?: 'Team Contacts';
    $departments[$department][] = $member;
}
site_header('Our People', $route, $teamCopy['intro']);
?>
<main id="main">
    <section class="people-hero people-hero-compact">
        <div class="wrap">
            <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span>Our People</span></nav>
            <p class="eyebrow eyebrow-light"><?=h($teamCopy['eyebrow'])?></p>
            <div class="people-directory-heading"><h1><?=nl2br(h($teamCopy['title']), false)?></h1><p><?=h($teamCopy['intro'])?><br><a href="mailto:<?=h($siteConfig['email'])?>"><?=h($siteConfig['email'])?></a></p></div>
        </div>
    </section>
    <section class="section directory-section" id="team-directory">
        <div class="wrap">
            <div class="directory-intro"><p class="eyebrow"><?=h($teamCopy['directory_eyebrow'])?></p><h2><?=nl2br(h($teamCopy['directory_title']), false)?></h2><p class="directory-description"><?=h($teamCopy['directory_intro'])?></p></div>
            <?php if ($members): ?>
            <nav class="department-index" aria-label="Departments">
                <?php $departmentNumber = 0; foreach ($departments as $department => $contacts): $departmentNumber++; ?>
                <a href="#department-<?=$departmentNumber?>"><?=h($department)?></a>
                <?php endforeach; ?>
            </nav>
            <div class="department-groups">
                <?php $departmentNumber = 0; foreach ($departments as $department => $contacts): $departmentNumber++; ?>
                <section class="department-group" aria-labelledby="department-<?=$departmentNumber?>">
                    <h2 id="department-<?=$departmentNumber?>"><?=h($department)?></h2>
                    <div class="department-contacts">
                        <?php foreach ($contacts as $member):
                            $photo = sf_cover_url((string) ($member['photo'] ?? ''));
                            $phone = trim((string) ($member['phone'] ?? ''));
                            $tel = preg_replace('/[^+0-9]/', '', $phone);
                            $email = trim((string) ($member['email'] ?? ''));
                        ?>
                        <article class="directory-person">
                            <div class="directory-person-identity">
                                <div class="directory-avatar">
                                    <?php if ($photo !== ''): ?><img src="<?=h($photo)?>" alt="<?=h($member['name'])?>" width="96" height="96" loading="lazy">
                                    <?php else: ?><svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="17" r="7"/><path d="M10 40v-4a14 14 0 0 1 28 0v4Z"/></svg><?php endif; ?>
                                </div>
                                <div><h3><?=h($member['name'])?></h3><?php if (!empty($member['role'])): ?><p><?=h($member['role'])?></p><?php endif; ?></div>
                            </div>
                            <div class="directory-contact directory-email">
                                <?php if ($email !== ''): ?><span class="directory-contact-label">Email</span><a href="mailto:<?=h($email)?>"><?=h($email)?></a><?php endif; ?>
                            </div>
                            <?php if ($phone !== ''): ?><div class="directory-contact directory-telephone">
                                <span class="directory-contact-label">Telephone</span><a href="tel:<?=h($tel)?>"><?=h($phone)?></a>
                            </div><?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="directory-empty"><div><p class="eyebrow">TEAM CONTACTS</p><h3>Start with a conversation.</h3><p>For help finding the right contact, send your enquiry to our general email address.</p><a class="text-link" href="mailto:<?=h($siteConfig['email'])?>"><?=h($siteConfig['email'])?></a></div></div>
            <?php endif; ?>
        </div>
    </section>
    <section class="people-careers"><div class="wrap"><div><p class="eyebrow"><?=h($teamCopy['careers_eyebrow'])?></p><h2><?=nl2br(h($teamCopy['careers_title']), false)?></h2></div><div><p><?=h($teamCopy['careers_body'])?></p><a class="button" href="/careers/"><?=h($teamCopy['careers_label'])?></a></div></div></section>
</main>
<?php site_footer(); ?>
