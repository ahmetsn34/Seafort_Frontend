<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/seafort/app/bootstrap.php';
require_once dirname(__DIR__,2).'/seafort/app/enquiries.php';
sf_admin_session();
if(empty($_SESSION['authenticated'])){header('Location: /admin/',true,303);exit;}
$tab=($_GET['tab']??'')==='settings'?'settings':'inbox';
$base='/admin/enquiries.php';$error='';$notice='';
$selectedId=is_string($_GET['id']??null)?$_GET['id']:'';
$settings=sf_enquiry_settings();

if(isset($_GET['download'])){
    try{sf_enquiry_purge();}catch(Throwable){http_response_code(503);exit('Dosya geçici olarak kullanılamıyor.');}
    $item=sf_enquiry_find(is_string($_GET['download'])?$_GET['download']:'');
    if(!$item || !is_array($item['attachment']??null)){http_response_code(404);exit('Dosya bulunamadı.');}
    try{$file=sf_enquiry_attachment_path($item['attachment']);}catch(RuntimeException){http_response_code(404);exit('Dosya bulunamadı.');}
    $extension=pathinfo($file,PATHINFO_EXTENSION);
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.filesize($file));
    header('Content-Disposition: attachment; filename="seafort-'.$item['reference'].'.'.$extension.'"');
    header("Content-Security-Policy: default-src 'none'; sandbox");
    header('X-Content-Type-Options: nosniff');readfile($file);exit;
}

if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
    try{
        sf_require_csrf();$action=is_string($_POST['action']??null)?$_POST['action']:'';
        if($action==='logout'){sf_logout();header('Location: /admin/',true,303);exit;}
        if($action==='save_settings'){
            sf_enquiry_save_settings($_POST,is_string($_POST['revision']??null)?$_POST['revision']:'');
            $_SESSION['enquiry_notice']='Form ve gönderim ayarları kaydedildi. Bu işlem test e-postası göndermez.';
            header('Location: '.$base.'?tab=settings',true,303);exit;
        }
        $id=is_string($_POST['id']??null)?$_POST['id']:'';
        if($action==='mark_read'){
            sf_enquiry_update($id,['read'=>true]);$_SESSION['enquiry_notice']='Kayıt okundu olarak işaretlendi.';
            header('Location: '.$base.'?id='.rawurlencode($id),true,303);exit;
        }
        if($action==='delete'){
            if(($_POST['confirm_delete']??'')!=='1')throw new InvalidArgumentException('Kalıcı silme onayını işaretleyin.');
            sf_enquiry_delete($id);$_SESSION['enquiry_notice']='Kayıt ve varsa özel dosya eki silindi.';
            header('Location: '.$base,true,303);exit;
        }
        throw new InvalidArgumentException('İşlem tanınmadı.');
    }catch(InvalidArgumentException $exception){$error=$exception->getMessage();}
    catch(Throwable){$error='İşlem tamamlanamadı. Mevcut kayıtlar korunuyor; dosya izinlerini kontrol edin.';}
}
if(isset($_SESSION['enquiry_notice'])){$notice=(string)$_SESSION['enquiry_notice'];unset($_SESSION['enquiry_notice']);}
$settings=sf_enquiry_settings();$revision=sf_enquiry_settings_revision();
$items=[];$selected=null;
try{$items=sf_enquiry_list();if($selectedId!==''){$selected=sf_enquiry_find($selectedId);if(!$selected)$error='Başvuru bulunamadı.';}}
catch(Throwable){$error='Başvuru kayıtları okunamadı. Sunucu dosya izinlerini kontrol edin.';}
$labels=['preview'=>'Önizleme kaydı','pending'=>'Sonuç bekleniyor','smtp_accepted'=>'SMTP kabul etti','failed'=>'SMTP teyit edilemedi'];
$counts=['all'=>count($items),'unread'=>0,'preview'=>0,'failed'=>0];
foreach($items as $item){if(empty($item['read']))$counts['unread']++;if(isset($counts[$item['status']]))$counts[$item['status']]++;}
$filter=in_array($_GET['filter']??'',['quote','career','preview','failed'],true)?$_GET['filter']:'';
function sf_enquiry_admin_field(string $name,string $label,string $value,string $type='text',int $max=180): void
{
    $id='field-'.preg_replace('/[^a-zA-Z0-9_-]/','-',$name);
    echo '<div class="enquiry-field"><label for="'.sf_escape($id).'">'.sf_escape($label).'</label><input id="'.sf_escape($id).'" name="'.sf_escape($name).'" type="'.sf_escape($type).'" maxlength="'.$max.'" value="'.sf_escape($value).'"></div>';
}
function sf_enquiry_admin_area(string $name,string $label,string $value,int $max=1500): void
{
    $id='field-'.preg_replace('/[^a-zA-Z0-9_-]/','-',$name);
    echo '<div class="enquiry-field"><label for="'.sf_escape($id).'">'.sf_escape($label).'</label><textarea id="'.sf_escape($id).'" name="'.sf_escape($name).'" rows="3" maxlength="'.$max.'">'.sf_escape($value).'</textarea></div>';
}
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Formlar ve başvurular | Seafort</title>
<link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/admin.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/content.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/assets/fonts/brand-fonts.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/clarity.css'))?>"><link rel="stylesheet" href="<?=sf_escape(sf_asset_url('/admin/enquiries.css'))?>"></head>
<body class="admin-shell"><a class="skip-link" href="#content">İçeriğe geç</a>
<aside class="sidebar"><a class="sidebar-brand" href="/admin/"><img src="/assets/logo-white.png" alt="Seafort Service"></a><p class="sidebar-label">İÇERİK YÖNETİMİ</p>
<nav aria-label="Yönetim menüsü"><a class="side-link" href="/admin/">Blog yazıları</a><a class="side-link" href="/admin/manage.php?type=projects">Projeler</a><a class="side-link" href="/admin/manage.php?type=slides">Ana sayfa sliderı</a><a class="side-link" href="/admin/content.php?type=home">Ana sayfa</a><a class="side-link" href="/admin/content.php?type=pages">Sayfa içerikleri</a><a class="side-link" href="/admin/brochures.php">Broşürler / PDF</a><a class="side-link" href="/admin/content.php?type=settings">İletişim ve WhatsApp</a><a class="side-link active" href="<?=$base?>">Formlar ve başvurular</a><a class="side-link" href="/admin/documents.php">Belge merkezi</a><a class="side-link" href="/admin/references.php">Referanslar ve firmalar</a><a class="side-link" href="/admin/translations.php">Çeviriler</a></nav>
<div class="sidebar-bottom"><form method="post"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="logout"><button class="logout" type="submit">Oturumu kapat</button></form></div></aside>
<div class="admin-main"><header class="topbar"><p>SEAFORT / Formlar ve başvurular</p><a href="/get-a-quote/" target="_blank" rel="noopener">Formu görüntüle</a></header>
<main class="workspace enquiry-workspace" id="content"><div class="page-heading"><div><p class="eyebrow">MÜŞTERİ VE ADAY İLETİŞİMİ</p><h1>Formlar ve başvurular</h1><p class="muted">Alıcıları, form metinlerini ve gönderim ayarlarını tek yerden yönetin.</p></div><span class="enquiry-mode <?=$settings['mode']==='preview'?'is-preview':'is-live'?>"><?=$settings['mode']==='preview'?'Önizleme modu · e-posta gönderilmez':'Canlı gönderim açık'?></span></div>
<?php if($notice!==''):?><div class="alert alert-success" role="status"><?=sf_escape($notice)?></div><?php endif;?><?php if($error!==''):?><div class="alert alert-error" role="alert"><?=sf_escape($error)?></div><?php endif;?>
<nav class="enquiry-tabs" aria-label="Form yönetimi"><a class="<?=$tab==='inbox'?'active':''?>" href="<?=$base?>">Gelen kayıtlar <span><?=$counts['all']?></span></a><a class="<?=$tab==='settings'?'active':''?>" href="<?=$base?>?tab=settings">Form ve e-posta ayarları</a></nav>

<?php if($tab==='settings'):?>
<form method="post" action="<?=$base?>?tab=settings" class="enquiry-settings"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="save_settings"><input type="hidden" name="revision" value="<?=sf_escape($revision)?>">
<section class="panel enquiry-panel"><div class="enquiry-section-heading"><span>01</span><div><h2>Çalışma modu ve kayıtlar</h2><p>Önizleme ortamında test formları kayıt alır, e-posta göndermez. Ana sitede canlı SMTP kurulana kadar ziyaretçiler doğrudan e-posta bağlantılarını görür.</p></div></div>
<div class="enquiry-field-grid"><div class="enquiry-field"><label for="delivery-mode">Gönderim modu</label><select name="mode" id="delivery-mode"><option value="preview" <?=$settings['mode']==='preview'?'selected':''?>>Önizleme — yalnızca özel kayıt oluştur</option><option value="live" <?=$settings['mode']==='live'?'selected':''?>>Canlı — SMTP ile e-posta gönder</option></select><p class="field-help">Canlı mod, geçerli alıcı ve SMTP ayarları olmadan açılamaz. SMTP kabulü, alıcı posta kutusuna teslim teyidi değildir.</p></div><div class="enquiry-field"><label for="retention">Kayıt saklama süresi (gün)</label><input type="number" name="retention_days" id="retention" min="1" max="365" value="<?=$settings['retention_days']?>" required><p class="field-help">Süreyi aşan kayıtlar ve ekler, sonraki form veya yönetim erişiminde temizlenir. Şirketinizin tercih ettiği süreyi belirleyin.</p></div></div></section>

<?php foreach(['quote'=>'Teklif ve proje formu','career'=>'Kariyer başvuru formu'] as $kind=>$title):$form=$settings['forms'][$kind];?>
<section class="panel enquiry-panel"><div class="enquiry-section-heading"><span><?=$kind==='quote'?'02':'03'?></span><div><h2><?=sf_escape($title)?></h2><p>İngilizce ana metinleri ve bu forma özel alıcı adresini düzenleyin.</p></div></div>
<label class="enquiry-check"><input type="checkbox" name="forms[<?=$kind?>][enabled]" value="1" <?=$form['enabled']?'checked':''?>>Bu formu etkinleştir</label>
<div class="enquiry-field-grid"><?php sf_enquiry_admin_field('forms['.$kind.'][recipient]','Başvuruların ulaşacağı e-posta',$form['recipient'],'email');sf_enquiry_admin_field('forms['.$kind.'][submit_label]','Gönder düğmesi',$form['submit_label'],'text',70);?></div>
<?php sf_enquiry_admin_field('forms['.$kind.'][title]','Form başlığı',$form['title'],'text',160);sf_enquiry_admin_area('forms['.$kind.'][intro]','Form açıklaması',$form['intro']);?>
<details class="enquiry-details"><summary>SMTP hazır olana kadar doğrudan e-posta alanı</summary><p class="field-help">Ana sitede SMTP hazır değilken formun yerini alır. E-posta bağlantısı yukarıdaki alıcı adresini kullanır; kapatılmış form için gösterilmez.</p><?php sf_enquiry_admin_field('forms['.$kind.'][email_title]','E-posta alanının başlığı',$form['email_title'],'text',160);sf_enquiry_admin_area('forms['.$kind.'][email_intro]','E-posta alanının açıklaması',$form['email_intro']);sf_enquiry_admin_field('forms['.$kind.'][email_label]','E-posta düğmesinin metni',$form['email_label'],'text',70);?></details>
<details class="enquiry-details"><summary>Alan başlıkları ve kullanıcı mesajları</summary><div class="enquiry-field-grid"><?php foreach(['name'=>'Ad soyad','email'=>'E-posta','subject'=>'Konu / pozisyon','message'=>'Mesaj','attachment'=>'Dosya eki'] as $key=>$label)sf_enquiry_admin_field('forms['.$kind.'][labels]['.$key.']',$label.' alanının başlığı',$form['labels'][$key],'text',100);?></div>
<?php sf_enquiry_admin_area('forms['.$kind.'][privacy_text]','Gönderim öncesi onay açıklaması',$form['privacy_text']);sf_enquiry_admin_area('forms['.$kind.'][success_text]','SMTP kabulünden sonra gösterilecek mesaj',$form['success_text'],1000);sf_enquiry_admin_area('forms['.$kind.'][preview_text]','Önizleme kaydından sonra gösterilecek mesaj',$form['preview_text'],1000);sf_enquiry_admin_area('forms['.$kind.'][unavailable_text]','Form kapalı veya gönderim hazır değilken gösterilecek mesaj',$form['unavailable_text'],1000);?>
<p class="field-help">Bu açıklama bir şirket gizlilik politikası yerine geçmez. Şirketinizin onayladığı metni kullanın; önizleme mesajında e-posta gönderilmediğini açık tutun.</p></details>
<div class="enquiry-upload-settings"><label class="enquiry-check"><input type="checkbox" name="forms[<?=$kind?>][attachments]" value="1" <?=$form['attachments']?'checked':''?>>Dosya eki kabul et</label><div class="enquiry-field-grid"><fieldset><legend>İzin verilen dosya türleri</legend><div class="enquiry-type-options"><?php foreach($kind==='career'?['pdf'=>'PDF']:['pdf'=>'PDF','jpg'=>'JPEG','png'=>'PNG'] as $type=>$label):?><label class="enquiry-check"><input type="checkbox" name="forms[<?=$kind?>][allowed_types][]" value="<?=$type?>" <?=in_array($type,$form['allowed_types'],true)?'checked':''?>><?=$label?></label><?php endforeach;?></div></fieldset><div class="enquiry-field"><label for="limit-<?=$kind?>">En fazla dosya boyutu (MB)</label><input type="number" name="forms[<?=$kind?>][attachment_limit_mb]" id="limit-<?=$kind?>" min="1" max="10" value="<?=$form['attachment_limit_mb']?>" required></div></div><p class="field-help">Ekler web kökünün dışında tutulur. Yalnızca giriş yapmış yönetici indirebilir. Dosya türü, içeriği ve boyutu sunucuda kontrol edilir.</p></div></section>
<?php endforeach;?>

<section class="panel enquiry-panel"><div class="enquiry-section-heading"><span>04</span><div><h2>Kurumsal SMTP bağlantısı</h2><p>Bilgileri e-posta sağlayıcınızdan alın. Kaydetmek e-posta göndermez; gerçek teslim testi ayrıca yapılmalıdır.</p></div></div>
<div class="enquiry-field-grid"><?php sf_enquiry_admin_field('smtp[host]','SMTP sunucusu',$settings['smtp']['host']);?><div class="enquiry-field"><label for="smtp-port">Port</label><input id="smtp-port" type="number" name="smtp[port]" min="1" max="65535" required value="<?=$settings['smtp']['port']?>"></div><div class="enquiry-field"><label for="smtp-encryption">Şifreli bağlantı</label><select id="smtp-encryption" name="smtp[encryption]"><option value="tls" <?=$settings['smtp']['encryption']==='tls'?'selected':''?>>STARTTLS — genellikle 587</option><option value="ssl" <?=$settings['smtp']['encryption']==='ssl'?'selected':''?>>TLS — genellikle 465</option></select></div><?php sf_enquiry_admin_field('smtp[username]','SMTP kullanıcı adı',$settings['smtp']['username']);sf_enquiry_admin_field('smtp[from_email]','Gönderen e-posta adresi',$settings['smtp']['from_email'],'email');sf_enquiry_admin_field('smtp[from_name]','Gönderen adı',$settings['smtp']['from_name'],'text',120);?></div>
<div class="enquiry-field"><label for="smtp-password">SMTP parolası</label><input id="smtp-password" name="smtp[password]" type="password" autocomplete="new-password" maxlength="1000" value=""><p class="field-help"><?=$settings['smtp']['password']!==''?'Kaydedilmiş bir parola var. Değiştirmek istemiyorsanız boş bırakın.':'Henüz parola kaydedilmedi.'?> Parola bu ekranda veya sayfa kaynağında gösterilmez.</p><label class="enquiry-check"><input type="checkbox" name="smtp[clear_password]" value="1">Kaydedilmiş SMTP parolasını kaldır</label></div>
<div class="enquiry-security-note"><strong>Bağlantı durumu:</strong> <?=sf_enquiry_delivery_ready($settings)?'Gerekli alanlar doldurulmuş. Bağlantı ve posta kutusu teslimi henüz bu ekran tarafından doğrulanmış değildir.':'SMTP kurulumu tamamlanmadı. Önizleme modu ile formları e-posta göndermeden deneyebilirsiniz.'?></div></section>
<div class="content-save-bar enquiry-save"><p>Ayarlar yalnızca bu uygulamanın formlarını etkiler.</p><button class="button" type="submit">Form ayarlarını kaydet</button></div></form>

<?php elseif($selected):?>
<a class="enquiry-back" href="<?=$base?>">← Gelen kayıtlara dön</a><section class="panel enquiry-panel enquiry-record"><div class="enquiry-record-heading"><div><p class="eyebrow"><?=sf_escape($selected['reference'])?></p><h2><?=sf_escape($selected['kind']==='career'?'Kariyer başvurusu':'Teklif / proje talebi')?></h2></div><span class="enquiry-status status-<?=sf_escape($selected['status'])?>"><?=sf_escape($labels[$selected['status']]??'Kayıt')?></span></div>
<dl class="enquiry-record-meta"><div><dt>Ad soyad</dt><dd><?=sf_escape($selected['name'])?></dd></div><div><dt>E-posta</dt><dd><a href="mailto:<?=sf_escape($selected['email'])?>"><?=sf_escape($selected['email'])?></a></dd></div><div><dt>Konu</dt><dd><?=sf_escape($selected['subject']!==''?$selected['subject']:'—')?></dd></div><div><dt>Kaydedildi</dt><dd><?=sf_escape($selected['created_at'])?></dd></div><div><dt>Alıcı</dt><dd><?=sf_escape($selected['recipient']!==''?$selected['recipient']:'Belirlenmedi')?></dd></div><div><dt>Gönderim onayı</dt><dd><?=!empty($selected['consent'])?'Kullanıcı tarafından işaretlendi':'Yok'?></dd></div></dl>
<div class="enquiry-message"><h3>Mesaj</h3><p><?=nl2br(sf_escape($selected['message']),false)?></p></div>
<?php if(is_array($selected['attachment'])):?><div class="enquiry-attachment"><div><strong>Özel dosya eki</strong><p><?=sf_escape($selected['attachment']['name'])?> · <?=number_format($selected['attachment']['size']/1024,0,',','.')?> KB</p></div><a class="button button-ghost" href="<?=$base?>?download=<?=rawurlencode($selected['id'])?>">Dosyayı indir</a></div><?php endif;?>
<div class="enquiry-security-note"><?php if($selected['status']==='preview'):?>Bu kayıt önizleme modunda oluşturuldu. Dışarıya e-posta gönderilmedi.<?php elseif($selected['status']==='smtp_accepted'):?>Mesaj SMTP sunucusu tarafından kabul edildi. Bu durum, alıcının gelen kutusuna teslim edildiğini tek başına kanıtlamaz.<?php elseif($selected['status']==='failed'):?>Kayıt korunuyor; SMTP kabulü doğrulanamadı. Yeniden gönderimden önce posta kutusunu kontrol edin. Otomatik yeniden gönderim yapılmaz.<?php else:?>Gönderim sonucu henüz kesinleşmedi. Yeni bir gönderim yapmadan önce posta kutusunu kontrol edin.<?php endif;?></div>
<form method="post" class="enquiry-read-action"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?=sf_escape($selected['id'])?>"><button class="button button-ghost" type="submit"><?=empty($selected['read'])?'Okundu olarak işaretle':'Okundu'?></button></form></section>
<details class="panel enquiry-delete"><summary>Kaydı kalıcı olarak sil</summary><p>Başvuru ve varsa özel dosya eki silinir. Bu işlem geri alınamaz.</p><form method="post"><input type="hidden" name="csrf" value="<?=sf_escape(sf_csrf())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=sf_escape($selected['id'])?>"><label class="enquiry-check"><input type="checkbox" name="confirm_delete" value="1" required>Bu kaydı ve dosya ekini silmek istiyorum.</label><button class="enquiry-danger-button" type="submit">Kaydı ve ekini sil</button></form></details>

<?php else:?>
<div class="enquiry-stat-grid"><div class="panel enquiry-stat"><span>Toplam kayıt</span><strong><?=$counts['all']?></strong></div><div class="panel enquiry-stat"><span>Okunmamış</span><strong><?=$counts['unread']?></strong></div><div class="panel enquiry-stat"><span>Önizleme kaydı</span><strong><?=$counts['preview']?></strong></div><div class="panel enquiry-stat"><span>Gönderim hatası</span><strong><?=$counts['failed']?></strong></div></div>
<section class="panel enquiry-panel"><div class="enquiry-list-heading"><div><h2>Gelen kayıtlar</h2><p class="muted">Teklifler ve kariyer başvuruları ayrı türlerle listelenir. Dışarıya otomatik yanıt gönderilmez.</p></div><a class="button button-ghost" href="<?=$base?>?tab=settings">Form ayarları</a></div>
<nav class="enquiry-filters" aria-label="Başvuru filtresi"><?php foreach([''=>'Tümü','quote'=>'Teklifler','career'=>'Kariyer','preview'=>'Önizleme','failed'=>'Gönderim hataları'] as $key=>$title):?><a class="<?=$filter===$key?'active':''?>" href="<?=$base.($key!==''?'?filter='.$key:'')?>"><?=sf_escape($title)?></a><?php endforeach;?></nav>
<div class="enquiry-list"><?php $visible=0;foreach($items as $item):if($filter!=='' && !in_array($filter,[$item['kind'],$item['status']],true))continue;$visible++;?><a class="enquiry-list-row <?=empty($item['read'])?'is-unread':''?>" href="<?=$base?>?id=<?=rawurlencode($item['id'])?>"><div class="enquiry-row-type"><?=sf_escape($item['kind']==='career'?'Kariyer':'Teklif')?></div><div class="enquiry-row-person"><strong><?=sf_escape($item['name'])?></strong><span><?=sf_escape($item['subject']!==''?$item['subject']:$item['email'])?></span><small><?=sf_escape($item['reference'])?></small></div><span class="enquiry-status status-<?=sf_escape($item['status'])?>"><?=sf_escape($labels[$item['status']]??'Kayıt')?></span><time datetime="<?=sf_escape($item['created_at'])?>"><?=sf_escape(substr($item['created_at'],0,10))?></time><span class="enquiry-row-open">İncele →</span></a><?php endforeach;?></div>
<?php if($visible===0):?><div class="enquiry-empty"><h3>Henüz kayıt yok.</h3><p>Önizleme modunda formu doldurarak akışı deneyebilirsiniz. Oluşturulan kayıt burada görünür; e-posta gönderilmez.</p><a class="button button-ghost" href="/get-a-quote/" target="_blank" rel="noopener">Teklif formunu aç</a></div><?php endif;?></section>
<?php endif;?></main><footer class="admin-footer">SEAFORT <span>Özel form yönetimi</span><a href="/" target="_blank" rel="noopener">Siteye dön</a></footer></div></body></html>
