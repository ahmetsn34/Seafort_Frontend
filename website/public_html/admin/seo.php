<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/seafort/app/bootstrap.php';
sf_admin_session();
if(empty($_SESSION['authenticated'])){header('Location: /admin/',true,303);exit;}
header('X-Robots-Tag: noindex, nofollow');header('Cache-Control: no-store, private');
require_once SF_ROOT.'/app/seo.php';
$catalog=sf_seo_public_routes();ksort($catalog);
$route=is_string($_POST['route']??$_GET['route']??null)?($_POST['route']??$_GET['route']):'/';
if(!isset($catalog[$route]))$route='/';
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    try{
        sf_require_csrf();
        if(($_POST['action']??'')==='logout'){sf_logout();header('Location: /admin/',true,303);exit;}
        if(($_POST['action']??'')!=='save')throw new InvalidArgumentException('İşlem geçersiz.');
        if(!is_string($_POST['route']??null)||!isset($catalog[$_POST['route']]))throw new InvalidArgumentException('Yayımlanmış bir sayfa seçin.');
        $revision=is_string($_POST['revision']??null)?$_POST['revision']:'';
        if($revision===''||!hash_equals(sf_seo_revision(),$revision))throw new InvalidArgumentException('SEO ayarları başka bir sekmede değişti. Sayfayı yenileyin.');
        $input=$_POST;
        // Validate text before accepting any uploaded image.
        foreach(['site_name'=>80,'default_image_alt'=>240,'title'=>120,'description'=>320,'image_alt'=>240] as $key=>$limit)sf_seo_text($input[$key]??'',$limit,$key==='site_name');
        foreach(['default_image','image'] as $field){
            $upload=$_FILES[$field.'_upload']??[];
            if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)$input[$field]=sf_store_upload($upload);
        }
        sf_seo_save_settings($input,$revision);
        $_SESSION['seo_notice']='SEO başlığı, açıklama ve paylaşım ayarları kaydedildi.';
        header('Location: /admin/seo.php?'.http_build_query(['route'=>$route]),true,303);exit;
    }catch(InvalidArgumentException $exception){http_response_code(400);$error=$exception->getMessage();}
    catch(Throwable $exception){http_response_code(500);error_log('Seafort SEO settings: '.$exception->getMessage());$error='SEO ayarları kaydedilemedi. Mevcut ayarlar korunuyor.';}
}elseif(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){header('Allow: GET, POST');http_response_code(405);exit;}
$settings=sf_seo_settings();$settingsRevision=sf_content_digest($settings);$entry=$settings['pages'][$route]??['title'=>'','description'=>'','image'=>'','image_alt'=>''];
$config=array_replace(require SF_ROOT.'/app/config.php',sf_site_settings());
$source=$catalog[$route];$preview=sf_seo_document((string)$source['title'],$route,(string)$source['description'],$config);
$notice=is_string($_SESSION['seo_notice']??null)?$_SESSION['seo_notice']:'';unset($_SESSION['seo_notice']);
$images=[];
foreach(['/assets','/uploads'] as $directory){
    $root=SF_PUBLIC_DIR.$directory;if(!is_dir($root))continue;
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){
        if(count($images)>=250)break;
        if(!$file->isFile()||$file->isLink()||!preg_match('/\.(?:jpe?g|png|webp)\z/i',$file->getFilename()))continue;
        $path=str_replace('\\','/',substr($file->getPathname(),strlen(SF_PUBLIC_DIR)));
        if(str_starts_with($path,'/assets/references/'))continue;
        if(sf_cover_url($path)!=='')$images[]=$path;
    }
}
sort($images);
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>SEO ve paylaşım | Seafort</title><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/admin.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/content.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/clarity.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/seo.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/fonts/brand-fonts.css'))?>"></head>
<body class="admin-shell"><a class="skip-link" href="#content">İçeriğe geç</a><aside class="sidebar"><a class="sidebar-brand" href="/admin/"><img src="/assets/logo-white.png" alt="Seafort Service"></a><p class="sidebar-label">İÇERİK YÖNETİMİ</p><nav aria-label="Yönetim menüsü"><a class="side-link" href="/admin/">Yönetim paneli</a><a class="side-link active" href="/admin/seo.php">SEO ve paylaşım</a><a class="side-link" href="/admin/content.php?type=home">Ana sayfa metinleri</a><a class="side-link" href="/admin/content.php?type=pages">Sayfa içerikleri</a><a class="side-link" href="/admin/content.php?type=settings">İletişim ve sosyal hesaplar</a><a class="side-link" href="/admin/enquiries.php">Formlar ve başvurular</a></nav><div class="sidebar-bottom"><form method="post"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="logout"><button class="logout">Oturumu kapat</button></form></div></aside>
<div class="admin-main"><header class="topbar"><p>SEAFORT / SEO ve paylaşım</p><a href="<?=sf_escape($route)?>" target="_blank" rel="noopener">Sayfayı görüntüle</a></header><main class="workspace" id="content"><div class="page-heading"><div><p class="eyebrow">ARAMA VE PAYLAŞIM</p><h1>SEO ve paylaşım</h1><p class="muted">Başlıkları ve açıklamaları sayfadaki doğrulanmış bilgilerle tutarlı tutun. Boş bırakılan sayfa alanlarında mevcut içerik kullanılır.</p></div></div>
<?php if($notice):?><div class="alert alert-success" role="status"><?=sf_escape($notice)?></div><?php endif;?><?php if($error):?><div class="alert alert-error" role="alert"><?=sf_escape($error)?></div><?php endif;?>
<section class="panel"><form method="get" class="seo-page-select"><label for="seo-route">Düzenlenecek sayfa<select id="seo-route" name="route"><?php foreach($catalog as $path=>$record):?><option value="<?=sf_escape($path)?>" <?=$route===$path?'selected':''?>><?=sf_escape($path.' — '.sf_seo_clean((string)$record['title']))?></option><?php endforeach;?></select></label><button class="button button-ghost">Sayfayı aç</button></form><p class="muted"><?php if(($source['eligible']??true)!==true):?>Bu sayfada henüz yayımlanmış içerik yok. İçerik yayımlanana kadar arama motorlarına indekslenmemesi bildirilir.<?php elseif(($config['indexable']??false)!==true):?>Bu kurulum önizleme olduğu için arama motorlarına kapalıdır. SEO ayarları canlı kopyada kullanılabilir.<?php else:?>Bu yayımlanmış sayfa tarama ve indeksleme için uygundur. Sonuçlarda yer alma kararını arama motorları verir.<?php endif;?></p></section>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="route" value="<?=sf_escape($route)?>"><input type="hidden" name="revision" value="<?=sf_escape($settingsRevision)?>"><datalist id="seo-images"><?php foreach($images as $image):?><option value="<?=sf_escape($image)?>"><?=sf_escape(basename($image))?></option><?php endforeach;?></datalist>
<section class="panel"><h2>Bu sayfanın arama ve paylaşım metinleri</h2><div class="seo-fields"><label for="seo-title">SEO başlığı <span class="muted">· marka eki otomatik eklenir</span><input id="seo-title" name="title" maxlength="120" value="<?=sf_escape($entry['title']??'')?>" placeholder="Mevcut başlığı kullan"></label><label for="seo-description">Arama açıklaması<textarea id="seo-description" name="description" rows="3" maxlength="320" placeholder="Mevcut sayfa açıklamasını kullan"><?=sf_escape($entry['description']??'')?></textarea></label><label for="seo-image">Bu sayfanın paylaşım görseli<input id="seo-image" name="image" list="seo-images" value="<?=sf_escape($entry['image']??'')?>" placeholder="Sayfa görselini veya varsayılanı kullan"></label><label for="seo-image-upload">Ya da yeni sayfa görseli yükle<input id="seo-image-upload" type="file" name="image_upload" accept="image/jpeg,image/png,image/webp"><span class="muted">JPEG, PNG veya WebP. Yeni yükleme yukarıdaki görselin yerine geçer.</span></label><label for="seo-image-alt">Sayfa görselinin kısa açıklaması<input id="seo-image-alt" name="image_alt" maxlength="240" value="<?=sf_escape($entry['image_alt']??'')?>"></label></div></section>
<section class="panel"><h2>Tüm site için varsayılanlar</h2><p class="muted">Bu bölümdeki değişiklikler diğer sayfalara da uygulanır. Şirket adı, e-posta ve resmî sosyal medya bağlantıları <a href="/admin/content.php?type=settings">İletişim ve sosyal hesaplar</a> ekranından yönetilir.</p><div class="seo-fields"><label for="seo-site-name">Arama sonuçlarındaki site / marka adı<input id="seo-site-name" name="site_name" maxlength="80" required value="<?=sf_escape($settings['site_name'])?>"></label><label for="seo-default-image">Varsayılan paylaşım görseli<input id="seo-default-image" name="default_image" list="seo-images" value="<?=sf_escape($settings['default_image'])?>"></label><label for="seo-default-image-upload">Ya da yeni varsayılan görsel yükle<input id="seo-default-image-upload" type="file" name="default_image_upload" accept="image/jpeg,image/png,image/webp"></label><label for="seo-default-image-alt">Varsayılan görselin kısa açıklaması<input id="seo-default-image-alt" name="default_image_alt" maxlength="240" value="<?=sf_escape($settings['default_image_alt'])?>"></label></div></section>
<section class="panel seo-search-preview" aria-label="Kaydedilmiş arama görünümü"><p class="eyebrow">KAYDEDİLMİŞ GÖRÜNÜM</p><p class="seo-preview-url"><?=sf_escape($preview['canonical'])?></p><h2><?=sf_escape($preview['title'])?></h2><p><?=sf_escape($preview['description'])?></p><?php if($preview['image']):?><img src="<?=sf_escape(parse_url($preview['image']['url'],PHP_URL_PATH)?:'')?>" alt="<?=sf_escape($preview['image_alt'])?>" width="480" height="270"><?php endif;?><p class="muted">Bu görünüm bilgilendirme amaçlıdır. Arama motorları başlığı ve açıklamayı sorguya göre değiştirebilir.</p></section><button class="button" type="submit">SEO ayarlarını kaydet</button></form></main></div></body></html>
