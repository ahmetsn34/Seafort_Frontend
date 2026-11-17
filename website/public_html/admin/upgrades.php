<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/seafort/app/bootstrap.php';
sf_admin_session();
if (empty($_SESSION['authenticated'])) {
    header('Location: /admin/', true, 303);
    exit;
}
$config = require SF_ROOT . '/app/config.php';
// This action belongs only to the non-indexable preview installation.
if (($config['indexable'] ?? null) !== false) { http_response_code(404); exit; }
require_once SF_ROOT . '/app/upgrades.php';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        sf_require_csrf();
        if (($_POST['action'] ?? '') !== 'apply_preview_release' || ($_POST['confirm_upgrade'] ?? '') !== '1') {
            throw new InvalidArgumentException('İçerik güncellemesini uygulamak için onay kutusunu işaretleyin.');
        }
        $result = sf_apply_preview_release_upgrade();
        $_SESSION['upgrade_notice'] = 'Önizleme güncellendi. About: ' . ($result['about_applied'] ? 'yeni düzen uygulandı' : 'önceden uygulanmış, mevcut düzenlemeler korundu') . '. Eklenen firma logosu: ' . $result['reference_logos'] . '. Eklenen proje taslağı: ' . $result['project_drafts'] . '. Güncellenen eski bağlantı açıklaması: ' . $result['retired_directory_references'] . '.';
        header('Location: /admin/upgrades.php', true, 303);
        exit;
    } catch (InvalidArgumentException $exception) {
        http_response_code(400);
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('Seafort preview upgrade: ' . $exception->getMessage());
        http_response_code(500);
        $error = 'Güncelleme tamamlanamadı. Özel içerik yedeği korunuyor; sunucu kayıtlarını kontrol edip yeniden deneyin.';
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET, POST'); http_response_code(405); exit;
}
$notice = is_string($_SESSION['upgrade_notice'] ?? null) ? $_SESSION['upgrade_notice'] : '';
unset($_SESSION['upgrade_notice']);
$applied = !empty(sf_read_json('design-20261117.json')['about_applied']);
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Önizleme güncellemesi | Seafort</title><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/admin.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/content.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/fonts/brand-fonts.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/clarity.css'))?>"></head>
<body class="admin-shell"><aside class="sidebar"><a class="sidebar-brand" href="/admin/"><img src="/assets/logo-white.png" alt="Seafort Service"></a><p class="sidebar-label">İÇERİK YÖNETİMİ</p><nav aria-label="Yönetim menüsü"><a class="side-link" href="/admin/">Yönetim paneline dön</a><a class="side-link active" href="/admin/upgrades.php">Önizleme güncellemesi</a></nav></aside><div class="admin-main"><header class="topbar"><p>SEAFORT / Önizleme güncellemesi</p><a href="/" target="_blank" rel="noopener">Siteyi görüntüle</a></header><main class="workspace" id="content"><div class="page-heading"><div><p class="eyebrow">17 KASIM 2026 · 1.5.0 PREVIEW</p><h1>Önizleme içeriğini güncelle</h1><p class="muted">Bu işlem yalnızca mevcut önizleme ortamında çalışır.</p></div></div>
<?php if ($notice !== ''): ?><div class="alert alert-success" role="status"><?=sf_escape($notice)?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error" role="alert"><?=sf_escape($error)?></div><?php endif; ?>
<section class="panel"><h2><?=$applied?'Tasarım güncellemesi uygulanmış':'Tasarım güncellemesi hazır'?></h2><p>İlk uygulamada About sayfası yeni kısa metin ve görsel düzeniyle değiştirilir. Eski About içeriği ve etkilenen içerik dosyaları önce özel depolama alanında yedeklenir.</p><p>Eski ekip dizini açıklamaları düzeltilir; eksik proje taslağı ve eski siteden alınan firma logoları eklenir. Yönetici hesabı, SMTP, formlar, yüklemeler ve diğer sayfa düzenlemeleri korunur. Tekrar çalıştırmak About üzerindeki sonraki düzenlemeleri sıfırlamaz.</p><form method="post" action="/admin/upgrades.php"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="apply_preview_release"><label class="checkbox-row"><input type="checkbox" name="confirm_upgrade" value="1" required>Bu içerik güncellemesini önizlemeye uygulamak istiyorum.</label><p><button class="button" type="submit">Önizleme içeriğini güncelle</button></p></form></section></main></div></body></html>
