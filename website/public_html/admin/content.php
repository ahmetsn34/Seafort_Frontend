<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/seafort/app/bootstrap.php';
require_once dirname(__DIR__, 2) . '/seafort/app/content.php';
sf_admin_session();
if (empty($_SESSION['authenticated'])) { header('Location: /admin/', true, 303); exit; }
$type = is_string($_GET['type'] ?? null) ? $_GET['type'] : 'settings';
if (!in_array($type, ['settings', 'home', 'pages'], true)) { http_response_code(404); exit('Bölüm bulunamadı.'); }
$meta = ['settings'=>['title'=>'İletişim ve WhatsApp','intro'=>'E-posta adresi, sosyal hesaplar ve sağ kenardaki WhatsApp düğmesini buradan yönetin. Boş bırakılan hesap ve WhatsApp numarası sitede görünmez.'], 'home'=>['title'=>'Site metinleri','intro'=>'Ana sayfa, FPU video alanı ve ekip sayfasının metinlerini düzenleyin. Slider görselleri kendi bölümündedir.'], 'pages'=>['title'=>'Sayfa içerikleri','intro'=>'Sayfa başlıklarını, açıklamalarını ve bölüm metinlerini kodla uğraşmadan düzenleyin.']][$type];
$meta['intro'] .= ' Site ana dili İngilizcedir. Diğer dil seçenekleri bu sürümün çevirilerini gösterir; sonradan eklediğiniz yeni metinler, çevirileri güncellenene kadar İngilizce görünür.';
$defaults = sf_content_defaults($type);
$content = sf_content_read($type);
$revision = sf_content_revision($type);
$slug = is_string($_GET['page'] ?? null) ? $_GET['page'] : (string) ($content['pages'][0]['slug'] ?? '');
$pageIndex = null;
if ($type === 'pages') {
    foreach ($content['pages'] as $index => $page) if (($page['slug'] ?? '') === $slug) $pageIndex = $index;
    if ($pageIndex === null) { http_response_code(404); exit('Sayfa bulunamadı.'); }
}
$base = '/admin/content.php?type=' . $type . ($type === 'pages' ? '&page=' . rawurlencode($slug) : '');
$error = '';
$notice = '';

function content_label(string $key): string
{
    static $labels = ['archive'=>'Kurumsal kaynak arşivi (yalnızca yönetici)','folder_url'=>'Ana SharePoint / OneDrive klasör bağlantısı','note'=>'Arşiv / teslim notu','stats_review'=>'Rakamların doğrulanması','status'=>'Onay durumu','source'=>'Bilgi kaynağı / onaylayan kişi','checked_at'=>'Teyit tarihi (YYYY-AA-GG)','sustainability'=>'Sustainability sayfa düzeni','navigation'=>'Sayfa içi bölüm bağlantıları','overview'=>'Giriş ve tanıtım','pillars'=>'Üç ana odak kartı','approach'=>'Görselli yaklaşım bölümü','commitments'=>'Çalışma ilkeleri','documents'=>'Broşür ve doküman alanı','whatsapp'=>'WhatsApp destek düğmesi','message'=>'Hazır WhatsApp mesajı','stats'=>'Çalışan ve deneyim göstergeleri','value'=>'Gösterge değeri','social'=>'Sosyal medya hesapları','linkedin_url'=>'LinkedIn adresi','instagram_url'=>'Instagram adresi','youtube_url'=>'YouTube adresi','facebook_url'=>'Facebook adresi','projects'=>'Projeler sayfası','page_eyebrow'=>'Sayfa üst etiketi','detail_eyebrow'=>'Ayrıntı üst etiketi','detail_title'=>'Ayrıntı başlığı','detail_intro'=>'Ayrıntı açıklaması','hero_link_label'=>'Üst bölüm bağlantı metni','hero_positioning'=>'Üst bölüm kısa mesajı','hero_positioning_emphasis'=>'Üst bölüm vurgu metni','team'=>'Ekip sayfası','meta'=>'Ana sayfa üst bilgileri','tab'=>'Sekme başlığı','hero_title'=>'Üst bölüm erişilebilir başlığı','directory_eyebrow'=>'Rehber üst etiketi','directory_title'=>'Rehber başlığı','directory_intro'=>'Rehber açıklaması','careers_eyebrow'=>'Kariyer üst etiketi','careers_title'=>'Kariyer başlığı','careers_body'=>'Kariyer açıklaması','careers_label'=>'Kariyer düğmesi','image_label'=>'Görsel etiketi','detail'=>'Ayrıntı metni','settings'=>'Site bilgileri','company'=>'Şirket adı','email'=>'Genel iletişim e-postası','footer_tagline'=>'Alt bilgi açıklaması','services'=>'Hizmetler','capabilities'=>'Yetkinlikler','process'=>'Çalışma yaklaşımı','values'=>'Değerler','careers'=>'Kariyer alanı','quote'=>'İletişim alanı','cta'=>'Yönlendirme','fpu'=>'FPU çelik işleri ve video','eyebrow'=>'Üst etiket','title'=>'Başlık','intro'=>'Giriş metni','description'=>'Açıklama','body'=>'Metin','text'=>'Metin','heading'=>'Başlık','label'=>'Etiket','href'=>'Bağlantı','link_href'=>'Bağlantı','url'=>'Bağlantı','link_label'=>'Düğme metni','button_label'=>'Düğme metni','button_href'=>'Düğme bağlantısı','image'=>'Görsel','image_alt'=>'Görsel açıklaması','cover'=>'Görsel','cover_alt'=>'Görsel açıklaması','poster'=>'Video kapak görseli','poster_alt'=>'Kapak görselinin açıklaması','video_url'=>'Video dosyası','video_caption'=>'Video altı açıklama','video_title'=>'Video başlığı','video_placeholder'=>'Video hazırlık metni','video_note'=>'Video açıklaması','items'=>'İçerikler','cards'=>'Kartlar','tabs'=>'Sekmeler','steps'=>'Adımlar','sections'=>'İçerik bölümleri','list'=>'Maddeler','bullet'=>'Madde','bullets'=>'Maddeler','summary'=>'Kısa açıklama','footer'=>'Alt bilgi','tagline'=>'Kısa tanıtım','number'=>'Numara / sıra etiketi','photo_label'=>'Görsel etiketi','secondary_label'=>'İkinci düğme metni','secondary_href'=>'İkinci düğme bağlantısı','primary_label'=>'Düğme metni','primary_href'=>'Düğme bağlantısı','blog'=>'Blog alanı','empty'=>'Boş durum metni','hero'=>'Üst bölüm','seo'=>'Arama motoru metinleri','meta_title'=>'Sayfa başlığı','meta_description'=>'Sayfa açıklaması','slug'=>'Sayfa adresi'];
    return $labels[$key] ?? (ctype_digit($key) ? (string) ((int) $key + 1) . '. öğe' : ucwords(str_replace('_', ' ', $key)));
}

function content_field_id(array $path): string { return 'field-' . substr(hash('sha256', implode('.', $path)), 0, 20); }
function content_is_image(string $key): bool { return (bool) preg_match('/(?:^|_)(poster|image|cover|photo)$/D', $key); }
function content_assign(array &$data, array $path, string $value): void
{
    $target = &$data;
    foreach ($path as $key) $target = &$target[$key];
    $target = $value;
}
function content_uploads(array $defaults, array &$input, array $path = []): void
{
    foreach ($defaults as $key => $value) {
        $next = [...$path, $key];
        if (is_array($value)) { content_uploads($value, $input, $next); continue; }
        $id = content_field_id($next);
        if (content_is_image((string) $key) && isset($_FILES['images']['error'][$id]) && $_FILES['images']['error'][$id] !== UPLOAD_ERR_NO_FILE) {
            $file = []; foreach (['name','type','tmp_name','error','size'] as $part) $file[$part] = $_FILES['images'][$part][$id] ?? null;
            content_assign($input, $next, sf_store_upload($file));
        }
        if ($key === 'video_url') {
            if (isset($_FILES['video_upload']) && ($_FILES['video_upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) content_assign($input, $next, sf_store_video_upload($_FILES['video_upload']));
            elseif (($_POST['remove_video'] ?? '') === '1') content_assign($input, $next, '');
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        sf_require_csrf();
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'logout') { sf_logout(); header('Location: /admin/', true, 303); exit; }
        if ($action === 'export') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="seafort-' . $type . '-' . gmdate('Y-m-d') . '.json"');
            echo json_encode(['section'=>$type,'exported_at'=>sf_now(),'content'=>$content], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR); exit;
        }
        if ($action !== 'save') throw new InvalidArgumentException('İşlem tanınmadı.');
        $submitted = $_POST['content'] ?? null;
        if (!is_array($submitted)) throw new InvalidArgumentException('İçerik alanları eksik geldi. Sayfayı yenileyin.');
        $revision = is_string($_POST['revision'] ?? null) ? $_POST['revision'] : '';
        if ($type === 'pages') {
            $input = $content;
            $input['pages'][$pageIndex] = $submitted;
        } else $input = $submitted;
        sf_content_validate_tree($defaults, $input);
        if (!hash_equals(sf_content_revision($type), $revision)) throw new InvalidArgumentException('İçerik başka bir sekmede değiştirildi. Metninizi kopyalayın ve sayfayı yenileyin. Değişiklikleriniz kaydedilmedi.');
        if ($type === 'home') content_uploads($defaults, $input);
        elseif ($type === 'pages') content_uploads($defaults['pages'][$pageIndex], $input['pages'][$pageIndex]);
        sf_save_content($type, $input, $revision);
        $_SESSION['content_notice'] = 'Değişiklikler kaydedildi. Güncel içeriği sitede görüntüleyebilirsiniz.';
        header('Location: ' . $base, true, 303); exit;
    } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); if (isset($input) && is_array($input)) $content = $input; }
    catch (Throwable $exception) { error_log('Seafort content: ' . $exception->getMessage()); $error = 'İçerik kaydedilemedi. Mevcut sürüm korunuyor; dosya izinlerini ve sunucu kayıtlarını kontrol edin.'; }
}
if (isset($_SESSION['content_notice'])) { $notice = (string) $_SESSION['content_notice']; unset($_SESSION['content_notice']); }

function render_content_fields(array $data, array $path = [], int $depth = 0): void
{
    foreach ($data as $key => $value) {
        $next = [...$path, $key];
        $id = content_field_id($next);
        $name = 'content' . implode('', array_map(static fn($part): string => '[' . $part . ']', $next));
        if (is_array($value)) { ?>
        <details class="content-group depth-<?=$depth?>" <?=$depth < 1 ? 'open' : ''?>><summary><?=sf_escape(is_int($key) ? ($value['title'] ?? $value['tab'] ?? $value['heading'] ?? content_label((string)$key)) : content_label((string)$key))?></summary><div class="content-group-fields"><?php render_content_fields($value, $next, $depth + 1); ?></div></details>
        <?php continue; }
        if ($key === 'slug') { ?><input type="hidden" name="<?=sf_escape($name)?>" value="<?=sf_escape($value)?>"><?php continue; }
        $image = content_is_image((string)$key);
        $long = !$image && (str_contains((string)$value, "\n") || sf_strlen((string)$value) > 100 || preg_match('/(?:body|description|intro|text|caption|placeholder|tagline|note)$/D', (string)$key));
        ?><div class="content-field"><label for="<?=sf_escape($id)?>"><?=sf_escape(in_array('whatsapp',$path,true)?($key==='number'?'WhatsApp numarası (ülke koduyla)':($key==='label'?'WhatsApp düğme metni':content_label((string)$key))):content_label((string)$key))?></label>
        <?php if ($key==='status' && in_array('stats_review',$path,true)): ?><select id="<?=sf_escape($id)?>" name="<?=sf_escape($name)?>"><option value="pending" <?=$value==='pending'?'selected':''?>>Teyit bekliyor — rakamlar gizli</option><option value="approved" <?=$value==='approved'?'selected':''?>>Teyit edildi — rakamları göster</option></select><p class="field-help">Çalışan sayısı ve deneyim süresi şirket tarafından teyit edilmeden yayımlanmaz. Teyit edildi seçeneği için bilgi kaynağı ve tarihi girin.</p>
        <?php elseif ($long): ?><textarea id="<?=sf_escape($id)?>" name="<?=sf_escape($name)?>" rows="<?=sf_strlen((string)$value)>300?'5':'3'?>" maxlength="12000"><?=sf_escape($value)?></textarea>
        <?php else: ?><input id="<?=sf_escape($id)?>" name="<?=sf_escape($name)?>" type="<?=$key==='email'?'email':'text'?>" value="<?=sf_escape($value)?>" maxlength="12000" <?=$key==='email'?'required':''?>><?php endif; ?>
        <?php if ($image): $source = sf_cover_url((string)$value); if ($source !== ''): ?><img class="content-image" src="<?=sf_escape($source)?>" alt="Mevcut görsel" loading="lazy"><?php endif; ?><label class="content-upload-label" for="upload-<?=sf_escape($id)?>">Görseli değiştir</label><input id="upload-<?=sf_escape($id)?>" type="file" name="images[<?=sf_escape($id)?>]" accept="image/jpeg,image/png,image/webp"><p class="field-help">JPEG, PNG veya WebP; en fazla 5 MB. Kaydettiğinizde yüklenir.</p>
        <?php elseif ($key === 'video_url'): ?><label class="content-upload-label" for="video-upload">MP4 video yükle</label><input type="file" id="video-upload" name="video_upload" accept="video/mp4"><p class="field-help">En fazla 100 MB; sunucunun daha düşük yükleme sınırı varsa o sınır geçerlidir. Video otomatik başlamaz. Video eklenene kadar kapak görseli gösterilir.</p><?php if ($value !== ''): ?><label class="checkbox-label"><input type="checkbox" name="remove_video" value="1"> Mevcut videoyu kaldır</label><?php endif; ?>
        <?php elseif (in_array('whatsapp', $path, true) && $key==='number'): ?><p class="field-help">Numarayı ülke koduyla girin. Boş bırakırsanız sağ kenardaki WhatsApp düğmesi gizlenir. Numara ve hazır mesajı istediğiniz zaman değiştirebilirsiniz.</p><?php elseif (in_array('archive', $path, true)): ?><p class="field-help">Yalnızca yönetim panelinde tutulur. Bu alan arşivi otomatik taşımaz veya erişim izinlerini değiştirmez. Doğrulanmış ana klasör bağlantısı yoksa boş bırakın.</p><?php elseif (in_array('social', $path, true)): ?><p class="field-help">Bu platformdaki hesabın tam HTTPS adresini yazın. Boş bırakırsanız bağlantı gizlenir.</p><?php elseif (is_string($key) && preg_match('/(?:href|url)$/D',$key)): ?><p class="field-help">Sitedeki sayfa adresi; örnek: /projects/ veya /get-a-quote/.</p><?php endif; ?></div><?php
    }
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#103447"><title><?=sf_escape($meta['title'])?> | Seafort</title><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/admin.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/content.css'))?>"><script src="<?=sf_escape(sf_asset_url('/admin/content.js'))?>" defer></script><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/fonts/brand-fonts.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/clarity.css'))?>"></head><body class="admin-shell">
<a class="skip-link" href="#content">İçeriğe geç</a><aside class="sidebar"><a class="sidebar-brand" href="/admin/"><img src="/assets/logo-white.png" alt="Seafort Service" width="440" height="109"></a><p class="sidebar-label">İÇERİK YÖNETİMİ</p><nav aria-label="Yönetim menüsü"><a class="side-link" href="/admin/enquiries.php">Formlar ve başvurular</a><a class="side-link" href="/admin/">Blog yazıları</a><a class="side-link" href="/admin/manage.php?type=projects">Projeler</a><a class="side-link" href="/admin/manage.php?type=slides">Ana sayfa sliderı</a><a class="side-link <?=$type==='home'?'active':''?>" href="/admin/content.php?type=home">Site metinleri</a><a class="side-link <?=$type==='pages'?'active':''?>" href="/admin/content.php?type=pages">Sayfa içerikleri</a><a class="side-link <?=$type==='settings'?'active':''?>" href="/admin/content.php?type=settings">İletişim ve WhatsApp</a><a class="side-link" href="/" target="_blank" rel="noopener">Siteyi görüntüle</a><a class="side-link" href="/admin/brochures.php">Broşürler / PDF</a><a class="side-link" href="/admin/documents.php">Belge merkezi</a><a class="side-link" href="/admin/references.php">Referanslar ve firmalar</a><a class="side-link" href="/admin/translations.php">Çeviriler</a></nav><div class="sidebar-bottom"><div class="user"><span class="user-avatar" aria-hidden="true"><?=sf_escape(strtoupper(substr((string)$_SESSION['username'],0,1)))?></span><div><strong><?=sf_escape($_SESSION['username'])?></strong><span>Yönetici</span></div></div><form method="post" action="<?=sf_escape($base)?>"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="logout"><button class="logout" type="submit">Oturumu kapat</button></form></div></aside>
<div class="admin-main"><header class="topbar"><p>SEAFORT <span>/</span> <?=sf_escape($meta['title'])?></p><a href="<?=$type==='pages'?'/'.sf_escape($slug).'/':'/'?>" target="_blank" rel="noopener">Sitede görüntüle</a></header><main id="content" class="workspace"><div class="page-heading"><div><p class="eyebrow">SİTE İÇERİĞİ</p><h1><?=sf_escape($meta['title'])?></h1><p class="muted"><?=sf_escape($meta['intro'])?></p></div><form method="post" action="<?=sf_escape($base)?>"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="export"><button class="backup-button" type="submit">Bu bölümün yedeğini indir</button></form></div>
<?php if ($notice !== ''): ?><div class="alert alert-success" role="status"><?=sf_escape($notice)?></div><?php endif; ?><?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?=sf_escape($error)?></div><?php endif; ?>
<?php if ($type === 'pages'): ?><section class="panel page-select-panel"><form method="get" action="/admin/content.php"><input type="hidden" name="type" value="pages"><label for="edit-page">Düzenlemek istediğiniz sayfa</label><div class="page-select-row"><select name="page" id="edit-page"><?php foreach($content['pages'] as $entry): ?><option value="<?=sf_escape($entry['slug'])?>" <?=$entry['slug']===$slug?'selected':''?>><?=sf_escape($entry['slug']==='about'?'About Seafort':$entry['title'])?></option><?php endforeach; ?></select><button class="button button-ghost">Sayfayı aç</button></div></form></section><?php endif; ?>
<form class="site-content-form" method="post" enctype="multipart/form-data" action="<?=sf_escape($base)?>"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="revision" value="<?=sf_escape($revision)?>"><input type="hidden" name="action" value="save"><section class="panel content-fields" aria-label="Düzenlenebilir metinler"><?php render_content_fields($type==='pages'?$content['pages'][$pageIndex]:$content); ?></section><div class="content-save-bar"><p>Metinler düz yazı olarak gösterilir. Kaydettiğiniz değişiklikler sitede görünür.</p><button class="button" type="submit">Değişiklikleri kaydet</button></div></form></main><footer class="admin-footer">SEAFORT <span>İçerik yönetimi</span><a href="/" target="_blank" rel="noopener">Siteye dön</a></footer></div></body></html>
