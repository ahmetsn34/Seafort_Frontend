<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/seafort/app/bootstrap.php';

sf_admin_session();
$error = '';
$notice = '';
$configured = sf_admin_config() !== null;
$authenticated = !empty($_SESSION['authenticated']);
$editId = isset($_GET['edit']) && is_string($_GET['edit']) ? $_GET['edit'] : null;
$isNew = isset($_GET['new']);
$draftInput = null;
$postRevision = null;

function admin_redirect(string $query = ''): never
{
    header('Location: /admin/' . ($query !== '' ? '?' . $query : ''), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        sf_require_csrf();
        $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
        if ($action === 'login') {
            $username = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if (!sf_login($username, $password)) {
                throw new InvalidArgumentException('Kullanıcı adı veya şifre doğru değil.');
            }
            admin_redirect();
        }
        if (!$authenticated) {
            throw new InvalidArgumentException('İşlem için giriş yapmalısınız.');
        }
        if ($action === 'logout') {
            sf_logout();
            admin_redirect();
        }
        if ($action === 'export') {
            $backup = ['version' => 1, 'exported_at' => sf_now(), 'posts' => sf_posts(false)];
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="seafort-blog-' . gmdate('Y-m-d') . '.json"');
            echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            exit;
        }
        $id = is_string($_POST['id'] ?? null) && $_POST['id'] !== '' ? $_POST['id'] : null;
        if ($action === 'save') {
            $draftInput = array_intersect_key($_POST, array_flip(['title', 'slug', 'excerpt', 'body', 'category', 'status', 'cover']));
            foreach ($draftInput as $key => $value) {
                if (!is_string($value)) {
                    $draftInput[$key] = '';
                }
            }
            $editId = $id;
            $isNew = $id === null;
            $postRevision = is_string($_POST['post_revision'] ?? null) ? $_POST['post_revision'] : '';
            // Validate form fields before accepting an upload, then validate again with its actual path.
            sf_validate_post($draftInput);
            if (isset($_FILES['cover_upload']) && ($_FILES['cover_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $draftInput['cover'] = sf_store_upload($_FILES['cover_upload']);
            }
            if (isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
                $draftInput['cover'] = '';
            }
            $savedId = sf_save_post($draftInput, $id, $id !== null ? $postRevision : null);
            $_SESSION['notice'] = $draftInput['status'] === 'published' ? 'Yazı yayımlandı. Blog sayfasında görüntülenebilir.' : 'Taslak kaydedildi. Ziyaretçiler bu yazıyı görmez.';
            admin_redirect(http_build_query(['edit' => $savedId]));
        }
        if (in_array($action, ['archive', 'restore', 'unpublish'], true) && $id !== null) {
            sf_change_post_status($id, $action);
            $_SESSION['notice'] = match ($action) {
                'archive' => 'Yazı arşive taşındı. İstediğiniz zaman taslak olarak geri alabilirsiniz.',
                'restore' => 'Yazı taslak olarak geri alındı. Yayımlamadan önce gözden geçirin.',
                default => 'Yazı yayından kaldırılıp taslağa alındı.',
            };
            admin_redirect();
        }
        throw new InvalidArgumentException('İşlem tanınmadı.');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('Seafort CMS: ' . $exception->getMessage());
        $error = 'İşlem tamamlanamadı. Dosya izinlerini ve sunucu kayıtlarını kontrol edin; mevcut içeriği değiştirmeden yeniden deneyin.';
    }
}

if (isset($_SESSION['notice'])) {
    $notice = (string) $_SESSION['notice'];
    unset($_SESSION['notice']);
}

$post = null;
$posts = [];
$counts = ['all' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0];
$filter = isset($_GET['status']) && is_string($_GET['status']) && in_array($_GET['status'], ['published', 'draft', 'archived'], true) ? $_GET['status'] : 'all';
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$previewPost = null;
if ($authenticated) {
    try {
        $posts = sf_posts(false);
        foreach ($posts as $item) {
            $status = $item['status'] ?? 'draft';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            if ($status !== 'archived') {
                $counts['all']++;
            }
        }
        if ($editId !== null) {
            $post = sf_find_post($editId);
            if ($post === null && $draftInput === null) {
                $error = 'Bu yazı bulunamadı.';
                $editId = null;
            } elseif (($post['status'] ?? '') === 'archived') {
                $error = 'Arşivdeki yazıyı önce taslak olarak geri alın.';
                $editId = null;
                $post = null;
            }
        }
        if ($isNew) {
            $post = ['id' => '', 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'category' => '', 'cover' => '', 'status' => 'draft'];
        }
        if ($draftInput !== null) {
            $post = array_merge($post ?? [], $draftInput, ['id' => $editId ?? '']);
        }
        if (isset($_GET['preview']) && is_string($_GET['preview'])) {
            $previewPost = sf_find_post($_GET['preview']);
            if ($previewPost === null) {
                $error = 'Önizlenecek yazı bulunamadı.';
            }
        }
    } catch (Throwable $exception) {
        error_log('Seafort CMS read: ' . $exception->getMessage());
        $error = 'İçerik dosyası okunamadı. Sunucu kayıtlarını ve posts.json yedeğini kontrol edin.';
    }
}
$statusLabels = ['published' => 'Yayında', 'draft' => 'Taslak', 'archived' => 'Arşiv'];
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#103447">
    <title><?= $authenticated ? ($post ? 'Yazı editörü' : 'Blog yönetimi') : 'Yönetici girişi' ?> | Seafort</title>
    <link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/admin.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/content.css'))?>">
    <script src="<?=sf_escape(sf_asset_url('/admin/admin.js'))?>" defer></script>
<link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/fonts/brand-fonts.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/clarity.css'))?>"></head>
<body class="<?= $authenticated ? 'admin-shell' : 'login-shell' ?>">
<?php if (!$authenticated): ?>
    <main class="login-card">
        <a class="login-brand" href="/"><img src="/assets/logo-dark.png" alt="Seafort Service" width="445" height="117"></a>
        <p class="eyebrow">SEAFORT · İÇERİK YÖNETİMİ</p>
        <h1><?= $configured ? 'İyi çalışmalar.' : 'Kurulum bekleniyor.' ?></h1>
        <p class="muted"><?= $configured ? 'Blog, projeler, broşürler ve site içeriklerini yönetmek için giriş yapın.' : 'Yönetici hesabı henüz oluşturulmadı. Kurulum belgesindeki hesap oluşturma adımını tamamlayın.' ?></p>
        <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= sf_escape($error) ?></div><?php endif ?>
        <?php if ($configured): ?>
        <form method="post" class="login-form">
            <input type="hidden" name="csrf" value="<?= sf_escape(sf_csrf()) ?>">
            <input type="hidden" name="action" value="login">
            <label for="username">Kullanıcı adı</label>
            <input id="username" name="username" autocomplete="username" required maxlength="80" autofocus>
            <label for="password">Şifre</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required maxlength="72">
            <button class="button button-full" type="submit">Yönetim paneline gir </button>
        </form>
        <?php else: ?>
        <div class="setup-note"><strong>Sunucu yöneticisi için</strong><p>Proje klasöründe <code>php bin/create-admin.php --username kullanici</code> komutunu çalıştırın. Paket varsayılan şifre içermez.</p></div>
        <?php endif ?>
        <a class="quiet-link" href="/">Siteye dön</a>
    </main>
    <p class="login-footer">Marine &amp; offshore services</p>
<?php else: ?>
    <a class="skip-link" href="#content">İçeriğe geç</a>
    <aside class="sidebar">
        <a class="sidebar-brand" href="/admin/"><img src="/assets/logo-white.png" alt="Seafort Service" width="440" height="109"></a>
        <p class="sidebar-label">İÇERİK YÖNETİMİ</p>
        <nav aria-label="Yönetim menüsü"><a class="side-link" href="/admin/enquiries.php">Formlar ve başvurular</a><a class="side-link" href="/admin/brochures.php">Broşürler / PDF</a>
            <a class="side-link <?= !$post ? 'active' : '' ?>" href="/admin/"> Blog yazıları</a>
            <a class="side-link <?= $isNew ? 'active' : '' ?>" href="/admin/?new=1"> Yeni yazı</a>
            <a class="side-link" href="/admin/manage.php?type=projects"> Projeler</a>

            <a class="side-link" href="/admin/manage.php?type=slides"> Ana sayfa slider</a>
            <a class="side-link" href="/admin/content.php?type=home">Site metinleri</a>
            <a class="side-link" href="/admin/content.php?type=pages">Sayfa içerikleri</a>
            <a class="side-link" href="/admin/content.php?type=settings">İletişim ve WhatsApp</a>
            <a class="side-link" href="/blog/" target="_blank" rel="noopener"> Blogu görüntüle</a>
            <a class="side-link" href="/" target="_blank" rel="noopener"> Siteyi görüntüle</a>
        <a class="side-link" href="/admin/documents.php">Belge merkezi</a><a class="side-link" href="/admin/references.php">Referanslar ve firmalar</a><a class="side-link" href="/admin/translations.php">Çeviriler</a></nav>
        <div class="sidebar-bottom">
            <div class="user"><span class="user-avatar" aria-hidden="true"><?= sf_escape(strtoupper(substr((string) $_SESSION['username'], 0, 1))) ?></span><div><strong><?= sf_escape($_SESSION['username']) ?></strong><span>Yönetici</span></div></div>
            <form method="post"><input type="hidden" name="csrf" value="<?= sf_escape(sf_csrf()) ?>"><input type="hidden" name="action" value="logout"><button class="logout" type="submit">Oturumu kapat </button></form>
        </div>
    </aside>
    <div class="admin-main">
        <header class="topbar"><p>SEAFORT <span>/</span> Blog <span>/</span> <?= $post ? ($isNew ? 'Yeni yazı' : 'Düzenle') : 'Yazılar' ?></p><a href="/" target="_blank" rel="noopener">Siteye git </a></header>
        <main id="content" class="workspace">
        <?php if ($notice !== ''): ?><div class="alert alert-success" role="status"><?= sf_escape($notice) ?></div><?php endif ?>
        <?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?= sf_escape($error) ?></div><?php endif ?>
        <?php if ($previewPost !== null): ?>
            <div class="draft-preview">
                <div class="preview-toolbar"><div><p class="eyebrow">YAZI ÖNİZLEMESİ</p><h1>Yayımlamadan önce gözden geçirin.</h1></div><a class="button button-ghost" href="/admin/?edit=<?= rawurlencode((string) $previewPost['id']) ?>">Editöre dön</a></div>
                <p class="preview-banner">Bu önizleme yalnızca giriş yapmış yöneticiye açıktır. Son kaydettiğiniz sürümü gösterir. Durum: <?= sf_escape($statusLabels[$previewPost['status']] ?? 'Taslak') ?>.</p>
                <article class="panel preview-article"><p class="eyebrow"><?= sf_escape($previewPost['category'] ?? '') ?></p><h1><?= sf_escape($previewPost['title'] ?? '') ?></h1><p class="preview-excerpt"><?= sf_escape($previewPost['excerpt'] ?? '') ?></p><?php if (sf_cover_url((string) ($previewPost['cover'] ?? '')) !== ''): ?><img src="<?= sf_escape(sf_cover_url($previewPost['cover'])) ?>" alt=""><?php endif ?><div class="preview-body"><?= sf_paragraphs((string) ($previewPost['body'] ?? '')) ?></div></article>
            </div>
        <?php elseif ($post !== null): ?>
            <div class="page-heading"><div><p class="eyebrow">BLOG EDİTÖRÜ</p><h1><?= $isNew ? 'Yeni bir yazı hazırlayın.' : 'Yazınızı düzenleyin.' ?></h1><p class="muted">Açık, doğru ve Seafort’un çalışma alanıyla ilgili içerikler.</p></div><a class="button button-ghost" href="/admin/">Yazılara dön</a></div>
            <form method="post" enctype="multipart/form-data" id="post-form" class="editor-layout">
                <input type="hidden" name="csrf" value="<?= sf_escape(sf_csrf()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= sf_escape($post['id'] ?? '') ?>">
                <input type="hidden" name="post_revision" value="<?= sf_escape($postRevision ?? $post['updated_at'] ?? '') ?>">
                <section class="panel editor-fields" aria-label="Yazı içeriği">
                    <label for="title">Yazı başlığı</label><input class="title-input" id="title" name="title" value="<?= sf_escape($post['title'] ?? '') ?>" required minlength="3" maxlength="180" placeholder="Yazının konusunu açıkça anlatan bir başlık">
                    <label for="excerpt">Kısa açıklama <span>Blog listesinde gösterilir · en fazla 400 karakter</span></label><textarea id="excerpt" name="excerpt" rows="3" maxlength="400" placeholder="Yazıda okuyucuyu neyin beklediğini birkaç cümlede anlatın."><?= sf_escape($post['excerpt'] ?? '') ?></textarea>
                    <div class="body-heading"><label for="body">Yazı metni</label><span id="word-count" aria-live="polite"></span></div>
                    <p class="field-help">Paragraflar arasında boş satır bırakın. Ara başlık için “## Başlık”, liste için her satırda “- madde” kullanın. HTML kodu çalıştırılmaz.</p>
                    <textarea class="body-input" id="body" name="body" rows="20" required minlength="20" maxlength="100000" placeholder="Yazı metninizi buraya yazın..."><?= sf_escape($post['body'] ?? '') ?></textarea>
                    <div class="editor-footnote"> Teknik iddiaları, proje bilgilerini ve şirket adına verilen sözleri yayımlamadan önce doğrulayın.</div>
                </section>
                <aside class="editor-settings" aria-label="Yayın ayarları">
                    <section class="panel"><h2>Yayın ayarları</h2><label for="status">Durum</label><select id="status" name="status"><option value="draft" <?= ($post['status'] ?? 'draft') !== 'published' ? 'selected' : '' ?>>Taslak</option><option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Yayında</option></select><p class="field-help" id="status-help">Taslaklar yalnızca yönetim panelinde görünür. “Yayında” seçildiğinde kaydetmek yazıyı ziyaretçilere açar.</p><button class="button button-full" type="submit">Değişiklikleri kaydet </button>
                    <?php if (($post['status'] ?? '') === 'published'): ?><a class="preview-link" href="/blog/<?= rawurlencode((string) $post['slug']) ?>/" target="_blank" rel="noopener">Yayımlanan yazıyı aç</a><?php endif ?>
                    <?php if (!empty($post['id'])): ?><a class="preview-link" href="/admin/?preview=<?= rawurlencode((string) $post['id']) ?>" target="_blank" rel="noopener">Kayıtlı sürümü önizle</a><?php endif ?>
                    <?php if (!empty($post['updated_at'])): ?><p class="timestamp">Son düzenleme: <?= sf_escape(sf_date($post['updated_at'])) ?></p><?php endif ?></section>
                    <section class="panel"><h2>Düzen</h2><label for="slug">Yazı adresi</label><div class="slug-prefix">/blog/</div><input id="slug" name="slug" value="<?= sf_escape($post['slug'] ?? '') ?>" required maxlength="150" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="yazi-basligi"><p class="field-help">Küçük İngilizce harf, rakam ve kısa çizgi kullanın. Yayımlanmış adresi değiştirmek eski bağlantıyı etkiler.</p><label for="category">Kategori</label><input id="category" name="category" value="<?= sf_escape($post['category'] ?? '') ?>" required minlength="2" maxlength="60" placeholder="Örn. Marine Maintenance" list="category-list"><datalist id="category-list"><?php foreach (array_unique(array_column($posts, 'category')) as $category): ?><option value="<?= sf_escape($category) ?>"><?php endforeach ?></datalist></section>
                    <section class="panel"><h2>Kapak görseli</h2><?php if (!empty($post['cover'])): ?><img class="cover-preview" src="<?= sf_escape($post['cover']) ?>" alt="Mevcut yazı kapağı"><?php else: ?><div class="cover-placeholder"> Henüz görsel yok</div><?php endif ?><input type="hidden" name="cover" value="<?= sf_escape($post['cover'] ?? '') ?>"><label class="upload-label" for="cover_upload">Yeni görsel seç</label><input id="cover_upload" name="cover_upload" type="file" accept="image/jpeg,image/png,image/webp"><p class="field-help">JPEG, PNG veya WebP · en fazla 5 MB. Yüklenen görsel kaydettiğinizde kullanılır.</p><?php if (!empty($post['cover'])): ?><label class="checkbox-label"><input type="checkbox" name="remove_cover" value="1"> Kapak görselini kaldır</label><?php endif ?></section>
                </aside>
            </form>
            <?php if (!$isNew && !empty($post['id'])): ?><div class="archive-row"><p>Bu yazıya artık ihtiyaç yoksa arşive taşıyabilirsiniz.</p><form method="post"><input type="hidden" name="csrf" value="<?= sf_escape(sf_csrf()) ?>"><input type="hidden" name="id" value="<?= sf_escape($post['id']) ?>"><input type="hidden" name="action" value="archive"><button class="danger-link" type="submit">Arşive taşı</button></form></div><?php endif ?>
        <?php else: ?>
            <div class="page-heading"><div><p class="eyebrow">BLOG YÖNETİMİ</p><h1>İçeriğinize yön verin.</h1><p class="muted">Yazılarınızı hazırlayın, gözden geçirin ve yayımlayın.</p></div><a class="button" href="/admin/?new=1">Yeni yazı oluştur </a></div>
            <div class="stat-grid"><a class="stat-card" href="/admin/"><span>Toplam yazı</span><strong><?= $counts['all'] ?></strong><p>Arşiv dışındaki içerikler</p></a><a class="stat-card" href="/admin/?status=published"><span><i class="status-dot green"></i> Yayında</span><strong><?= $counts['published'] ?></strong><p>Blogda görünen yazılar</p></a><a class="stat-card" href="/admin/?status=draft"><span><i class="status-dot amber"></i> Taslak</span><strong><?= $counts['draft'] ?></strong><p>Hazırlanmakta olan yazılar</p></a></div>
            <section class="panel post-list" aria-labelledby="list-heading">
                <div class="list-toolbar"><div class="list-heading-actions"><h2 id="list-heading">Blog yazıları</h2><form method="post"><input type="hidden" name="csrf" value="<?= sf_escape(sf_csrf()) ?>"><input type="hidden" name="action" value="export"><button class="backup-button" type="submit">İçerik yedeği indir </button></form></div><form method="get" class="search-form"><input type="hidden" name="status" value="<?= sf_escape($filter) ?>"><label class="sr-only" for="search">Yazılarda ara</label><input type="search" id="search" name="q" value="<?= sf_escape($search) ?>" placeholder="Başlıkta ara"><button type="submit" aria-label="Ara">Ara</button></form></div>
                <nav class="filters" aria-label="Yazı durumuna göre filtrele"><?php foreach (['all' => 'Tümü', 'published' => 'Yayında', 'draft' => 'Taslak', 'archived' => 'Arşiv'] as $value => $label): ?><a class="<?= $filter === $value ? 'selected' : '' ?>" href="/admin/?<?= sf_escape(http_build_query(['status' => $value, 'q' => $search])) ?>"><?= $label ?> <span><?= $counts[$value] ?></span></a><?php endforeach ?></nav>
                <div class="table-wrap"><table><thead><tr><th scope="col">Yazı</th><th scope="col">Durum</th><th scope="col">Son düzenleme</th><th scope="col"><span class="sr-only">İşlemler</span></th></tr></thead><tbody>
                <?php $visible = 0; foreach ($posts as $item): $itemStatus = $item['status'] ?? 'draft'; if (($filter === 'all' && $itemStatus === 'archived') || ($filter !== 'all' && $itemStatus !== $filter) || ($search !== '' && stripos((string) ($item['title'] ?? ''), $search) === false)) continue; $visible++; ?>
                    <tr><td><div class="post-cell"><?php if (!empty($item['cover'])): ?><img src="<?= sf_escape($item['cover']) ?>" alt="" loading="lazy"><?php else: ?><span class="table-image-placeholder" aria-hidden="true">▧</span><?php endif ?><div><?php if ($itemStatus !== 'archived'): ?><a class="post-title" href="/admin/?edit=<?= rawurlencode((string) $item['id']) ?>"><?= sf_escape($item['title']) ?></a><?php else: ?><strong class="post-title"><?= sf_escape($item['title']) ?></strong><?php endif ?><p><?= sf_escape($item['category'] ?? '') ?></p></div></div></td><td><span class="status-badge <?= sf_escape($itemStatus) ?>"><?= sf_escape($statusLabels[$itemStatus] ?? 'Taslak') ?></span></td><td class="date-cell"><?= sf_escape(sf_date($item['updated_at'] ?? $item['published_at'] ?? null)) ?></td><td class="row-actions"><?php if ($itemStatus === 'archived'): ?><form method="post"><input type="hidden" name="csrf" value="<?= sf_escape(sf_csrf()) ?>"><input type="hidden" name="id" value="<?= sf_escape($item['id']) ?>"><input type="hidden" name="action" value="restore"><button type="submit">Geri al</button></form><?php else: ?><a href="/admin/?edit=<?= rawurlencode((string) $item['id']) ?>">Düzenle </a><?php if ($itemStatus === 'published'): ?><a class="view-link" href="/blog/<?= rawurlencode((string) $item['slug']) ?>/" target="_blank" rel="noopener" aria-label="<?= sf_escape($item['title']) ?> yazısını görüntüle">Görüntüle</a><?php endif ?><?php endif ?></td></tr>
                <?php endforeach ?>
                </tbody></table></div>
                <?php if ($visible === 0): ?><div class="empty-state"><h3><?= $search !== '' ? 'Eşleşen yazı bulunamadı.' : 'Burada henüz yazı yok.' ?></h3><p><?= $search !== '' ? 'Başka bir başlık arayın veya filtreyi değiştirin.' : ($filter === 'archived' ? 'Arşive taşıdığınız yazılar burada görünür.' : 'Yeni bir yazı oluşturarak başlayabilirsiniz.') ?></p><?php if ($search === '' && $filter !== 'archived'): ?><a class="button button-ghost" href="/admin/?new=1">Yeni yazı oluştur</a><?php endif ?></div><?php endif ?>
            </section>
            <div class="content-note"><p>Bu bölüm blog yazılarını yönetir. Projeler ve ana sayfa slider içeriklerine soldaki menüden ulaşabilirsiniz. Arşive alınan kayıtlar kalıcı olarak silinmez.</p></div>
        <?php endif ?>
        </main>
        <footer class="admin-footer">SEAFORT <span>İçerik yönetimi</span><a href="/" target="_blank" rel="noopener">seafortservice.com</a></footer>
    </div>
<?php endif ?>
</body>
</html>
