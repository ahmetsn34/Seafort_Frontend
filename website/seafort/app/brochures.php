<?php
declare(strict_types=1);

function sf_brochure_pages(): array
{
    static $cached=null;
    if($cached!==null)return $cached;
    require_once __DIR__.'/collections.php';
    $pages=['general'=>['route'=>'*','title'=>'Genel şirket broşürü'], 'home'=>['route'=>'/','title'=>'Home']];
    foreach(sf_site_pages()['pages'] as $page){$pages[$page['slug']]=['route'=>'/'.$page['slug'].'/','title'=>$page['title']];}
    $pages['projects']=['route'=>'/projects/','title'=>'Projects'];
    $pages['blog']=['route'=>'/blog/','title'=>'Blog'];
    foreach(sf_posts(true) as $post){$pages['blog--'.$post['slug']]=['route'=>'/blog/'.$post['slug'].'/','title'=>'Blog: '.$post['title']];}
    foreach(sf_collection('projects',true) as $project){$pages['project--'.$project['slug']]=['route'=>'/projects/'.$project['slug'].'/','title'=>'Project: '.$project['title']];}
    return $cached=$pages;
}

function sf_brochure_defaults(): array
{
    $result=[];
    foreach(sf_brochure_pages() as $key=>$page){$result[$key]=['label'=>'Download brochure','brochure_file'=>''];}
    return $result;
}

function sf_brochure_path(string $value): string
{
    if($value==='')return '';
    if(!preg_match('/\A[a-f0-9]{32}\.pdf\z/D',$value) || !is_file(SF_STORAGE_DIR.'/brochures/'.$value)){
        throw new InvalidArgumentException('Geçerli bir broşür PDF dosyası seçin.');
    }
    return $value;
}

function sf_store_brochure_upload(array $file): string
{
    $tmp=$file['tmp_name']??null;
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_string($tmp) || !is_uploaded_file($tmp))throw new InvalidArgumentException('PDF yüklenemedi. Dosyayı yeniden seçin.');
    $size=filesize($tmp);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $handle=fopen($tmp,'rb');$signature=$handle===false?'':fread($handle,5);if(is_resource($handle))fclose($handle);
    if($size===false || $size<20 || $size>10*1024*1024 || $mime!=='application/pdf' || $signature!=='%PDF-')throw new InvalidArgumentException('En fazla 10 MB boyutunda geçerli bir PDF dosyası yükleyin.');
    $dir=SF_STORAGE_DIR.'/brochures';
    if(!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir))throw new RuntimeException('Broşür klasörü oluşturulamadı.');
    $name=bin2hex(random_bytes(16)).'.pdf';
    if(!move_uploaded_file($tmp,$dir.'/'.$name))throw new RuntimeException('PDF kaydedilemedi.');
    @chmod($dir.'/'.$name,0600);return $name;
}

function sf_brochure_entry(string $key): ?array
{
    $pages=sf_brochure_pages();if(!isset($pages[$key]))return null;
    $saved=sf_content_read('brochures');$entry=$saved[$key]??null;
    if(!$entry || ($entry['brochure_file']??'')==='')$entry=$saved['general']??null;
    if(!$entry || ($entry['brochure_file']??'')==='')return null;
    try{sf_brochure_path($entry['brochure_file']);}catch(InvalidArgumentException){return null;}
    return $entry;
}

function site_brochure(string $route): void
{
    $key=null;foreach(sf_brochure_pages() as $candidate=>$page){if($page['route']===$route){$key=$candidate;break;}}
    if($key===null)return;
    $entry=sf_brochure_entry($key);
    $icon='<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2v10m-4-4 4 4 4-4M3 13v4h14v-4"/></svg>';
    echo '<div class="brochure-rail wrap">';
    if($entry){echo '<a class="brochure-button" href="/brochure/?page='.rawurlencode($key).'" aria-label="'.sf_escape($entry['label']).' (PDF)">'.$icon.'<span>'.sf_escape($entry['label']).'</span><small>PDF</small></a>';}
    else{echo '<button class="brochure-button brochure-pending" type="button" disabled title="Brochure will be available here" aria-label="Brochure not available yet">'.$icon.'<span>Brochure</span><small>PDF</small></button>';}
    echo '</div>';
}

function sf_send_brochure(): never
{
    $key=is_string($_GET['page']??null)?$_GET['page']:'';
    $entry=sf_brochure_entry($key);
    if(!$entry){http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'Brochure not available.';exit;}
    $file=SF_STORAGE_DIR.'/brochures/'.sf_brochure_path($entry['brochure_file']);
    header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="seafort-brochure.pdf"');
    header('Content-Length: '.filesize($file));header('X-Content-Type-Options: nosniff');header('X-Robots-Tag: noindex, nofollow');
    header("Content-Security-Policy: sandbox; default-src 'none'");header('Cache-Control: private, no-store');
    readfile($file);exit;
}
