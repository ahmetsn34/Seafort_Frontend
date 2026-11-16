<?php
declare(strict_types=1);

function sf_document_categories(): array
{
    return ['brochure'=>'Brochures','certificate'=>'Certificates','wps'=>'Welding documents','sustainability'=>'Sustainability reports'];
}

function sf_document_languages(): array
{
    return ['en'=>'English','tr'=>'Türkçe','ar'=>'العربية','ru'=>'Русский','zh-CN'=>'简体中文','other'=>'Other'];
}

function sf_documents_settings(): array
{
    $defaults=['title'=>'Company documents','intro'=>'Brochures, technical documents and sustainability information in one place.','empty_title'=>'Need a document?','empty_text'=>'Contact our team for the documentation relevant to your project.'];
    $saved=sf_read_json('document-settings.json');
    foreach($defaults as $key=>$value)if(is_string($saved[$key]??null))$defaults[$key]=$saved[$key];
    return $defaults;
}

function sf_documents_save_settings(array $input,string $revision): void
{
    $defaults=sf_documents_settings();$next=[];
    foreach($defaults as $field=>$value){
        $next[$field]=sf_document_text($input[$field]??'',str_contains($field,'title')?180:1500,true);
    }
    sf_update_json('document-settings.json',static function(array &$data)use($next,$revision):void{
        if($revision===''||!hash_equals(sf_content_digest(sf_documents_settings()),$revision))throw new InvalidArgumentException('Sayfa metni başka bir sekmede değişti. Sayfayı yenileyin.');
        $data=$next;
    });
}

function sf_document_text(mixed $value,int $max,bool $required=false): string
{
    if(!is_string($value)||!preg_match('//u',$value)||str_contains($value,"\0"))throw new InvalidArgumentException('Metin biçimi geçersiz.');
    $value=trim(str_replace(["\r\n","\r"],"\n",$value));
    if(($required&&$value==='')||sf_strlen($value)>$max)throw new InvalidArgumentException('Metin alanını kontrol edin (en fazla '.$max.' karakter).');
    return $value;
}

function sf_document_pdf_path(array $item): string
{
    $filename=$item['file']??'';
    if(!is_string($filename)||!preg_match('/\A[a-f0-9]{48}\.pdf\z/D',$filename))throw new RuntimeException('Document not found.');
    $path=SF_STORAGE_DIR.'/document-files/'.$filename;
    if(!is_file($path))throw new RuntimeException('Document not found.');
    return $path;
}

function sf_documents_list(bool $publishedOnly=true,string $category='',string $language=''): array
{
    $items=[];
    foreach(sf_read_json('documents.json',['version'=>1,'items'=>[]])['items']??[] as $item){
        if(!is_array($item))continue;
        if($publishedOnly){
            if(($item['status']??'draft')!=='published'||empty($item['permission_approved']))continue;
            try{sf_document_pdf_path($item);}catch(RuntimeException){continue;}
        }
        if($category!==''&&($item['category']??'')!==$category)continue;
        if($language!==''&&($item['language']??'')!==$language)continue;
        $items[]=$item;
    }
    usort($items,static fn(array $a,array $b):int=>((int)($a['order']??0)<=>(int)($b['order']??0))?:strcmp($b['document_date']??'',$a['document_date']??'')?:strcmp($b['updated_at']??'',$a['updated_at']??''));
    return $items;
}

function sf_document_find(string $id): ?array
{
    if(!preg_match('/\A[a-f0-9]{32}\z/D',$id))return null;
    $item=sf_read_json('documents.json',['items'=>[]])['items'][$id]??null;
    return is_array($item)?$item:null;
}

function sf_document_upload(array $file): array
{
    if(($file['error']??null)!==UPLOAD_ERR_OK||!is_string($file['tmp_name']??null)||!is_string($file['name']??null)||!is_uploaded_file($file['tmp_name']))throw new InvalidArgumentException('PDF yüklenemedi. Dosyayı yeniden seçin.');
    $size=filesize($file['tmp_name']);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $handle=fopen($file['tmp_name'],'rb');$signature=$handle===false?'':fread($handle,5);if(is_resource($handle))fclose($handle);
    if($size===false||$size<20||$size>10*1024*1024||$mime!=='application/pdf'||$signature!=='%PDF-'||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='pdf')throw new InvalidArgumentException('Geçerli bir PDF seçin; en fazla 10 MB.');
    sf_storage_ready();$dir=SF_STORAGE_DIR.'/document-files';
    if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Document storage unavailable.');
    $filename=bin2hex(random_bytes(24)).'.pdf';
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$filename))throw new RuntimeException('Document could not be stored.');
    @chmod($dir.'/'.$filename,0600);
    return ['file'=>$filename,'bytes'=>$size];
}

function sf_document_save(array $input,?array $upload,string $id='',string $revision=''): string
{
    $current=$id!==''?sf_document_find($id):null;
    if($id!==''&&!$current)throw new InvalidArgumentException('Belge bulunamadı.');
    $item=['id'=>$current['id']??bin2hex(random_bytes(16)),'created_at'=>$current['created_at']??sf_now()];
    foreach(['title'=>180,'description'=>1500,'version'=>60,'document_date'=>10] as $field=>$max)$item[$field]=sf_document_text($input[$field]??'',$max,$field==='title');
    if(!array_key_exists($input['category']??'',sf_document_categories()))throw new InvalidArgumentException('Belge kategorisini seçin.');
    if(!array_key_exists($input['language']??'',sf_document_languages()))throw new InvalidArgumentException('Belge dilini seçin.');
    if(!in_array($input['status']??null,['draft','published'],true))throw new InvalidArgumentException('Yayın durumunu seçin.');
    $date=$item['document_date'];
    if($date!==''&&(!preg_match('/\A\d{4}-\d{2}-\d{2}\z/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))))throw new InvalidArgumentException('Belge tarihini geçerli bir tarih olarak yazın.');
    $order=filter_var($input['order']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>999]]);
    if($order===false)throw new InvalidArgumentException('Sıralama 0–999 arasında olmalıdır.');
    $item+=['category'=>$input['category'],'language'=>$input['language'],'status'=>$input['status'],'permission_approved'=>($input['permission_approved']??'')==='1','order'=>$order,'updated_at'=>sf_now(),'file'=>$current['file']??'','bytes'=>$current['bytes']??0];
    if($item['status']==='published'&&!$item['permission_approved'])throw new InvalidArgumentException('Yayımlamak için belgenin web yayın onayını işaretleyin.');
    $newFile=null;
    try{
        if($upload!==null&&($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$newFile=sf_document_upload($upload);$item=array_replace($item,$newFile);}
        if($item['status']==='published'){
            try{sf_document_pdf_path($item);}catch(RuntimeException){throw new InvalidArgumentException('Yayımlamak için geçerli bir PDF yükleyin.');}
        }
        sf_update_json('documents.json',static function(array &$data)use($item,$current,$revision):void{
            $data['version']=1;$data['items']??=[];
            if($current!==null){$latest=$data['items'][$current['id']]??null;if(!$latest||$revision===''||!hash_equals((string)$latest['updated_at'],$revision))throw new InvalidArgumentException('Belge başka bir sekmede değişti. Sayfayı yenileyin.');}
            $data['items'][$item['id']]=$item;
        },['version'=>1,'items'=>[]]);
    }catch(Throwable $error){if($newFile!==null){try{@unlink(sf_document_pdf_path($newFile));}catch(RuntimeException){}}throw $error;}
    if($newFile!==null&&$current!==null&&$current['file']!==$item['file']){try{@unlink(sf_document_pdf_path($current));}catch(RuntimeException){}}
    return $item['id'];
}

function sf_document_delete(string $id): void
{
    sf_update_json('documents.json',static function(array &$data)use($id):void{
        $item=$data['items'][$id]??null;if(!is_array($item))throw new InvalidArgumentException('Belge bulunamadı.');
        try{$path=sf_document_pdf_path($item);}catch(RuntimeException){$path=null;}
        if($path!==null&&!unlink($path))throw new RuntimeException('Document could not be deleted.');
        unset($data['items'][$id]);
    },['version'=>1,'items'=>[]]);
}

function sf_document_send(array $item): never
{
    try{$path=sf_document_pdf_path($item);}catch(RuntimeException){http_response_code(404);exit('Document not found.');}
    header('Content-Type: application/pdf');header('Content-Length: '.filesize($path));
    header('Content-Disposition: attachment; filename="seafort-document-'.substr($item['id'],0,8).'.pdf"');
    header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");readfile($path);exit;
}

function sf_documents_download(string $id): never
{
    $item=sf_document_find($id);
    if(!$item||$item['status']!=='published'||empty($item['permission_approved'])){http_response_code(404);header('Content-Type: text/plain; charset=utf-8');exit('Document not found.');}
    sf_document_send($item);
}

function sf_documents_render(string $category=''): void
{
    $language=is_string($_GET['language']??null)&&array_key_exists($_GET['language'],sf_document_languages())?$_GET['language']:'';
    $items=sf_documents_list(true,$category,$language);$settings=sf_documents_settings();
    if($items===[]){echo '<div class="documents-empty"><span class="eyebrow">DOCUMENTATION</span><h2>'.sf_escape($settings['empty_title']).'</h2><p>'.sf_escape($settings['empty_text']).'</p><a class="button" href="/get-a-quote/">Contact our team</a></div>';return;}
    echo '<div class="documents-grid">';
    foreach($items as $item){
        echo '<article class="document-card"><div class="document-card-top"><span class="document-kind">'.sf_escape(sf_document_categories()[$item['category']]??'Document').'</span><span class="document-format">PDF</span></div><h2>'.sf_escape($item['title']).'</h2>';
        if($item['description']!=='')echo '<p class="document-description">'.sf_escape($item['description']).'</p>';
        echo '<dl class="document-meta"><div><dt>Language</dt><dd>'.sf_escape(sf_document_languages()[$item['language']]??$item['language']).'</dd></div>';
        if($item['document_date']!=='')echo '<div><dt>Date</dt><dd>'.sf_escape($item['document_date']).'</dd></div>';
        if($item['version']!=='')echo '<div><dt>Version</dt><dd>'.sf_escape($item['version']).'</dd></div>';
        echo '</dl><a class="document-download" href="/documents/'.rawurlencode($item['id']).'/download/"><span>Download PDF</span><svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2v10m-4-4 4 4 4-4M3 14v3h14v-3"/></svg></a></article>';
    }
    echo '</div>';
}
