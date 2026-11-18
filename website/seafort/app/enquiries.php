<?php
declare(strict_types=1);

/** Private form settings, inbox and uploads. No mail is sent in preview mode. */
function sf_enquiry_defaults(): array
{
    $shared = [
        'enabled' => true, 'recipient' => 'contact@seafortservice.com',
        'submit_label' => 'Send enquiry',
        'privacy_text' => 'I confirm that the information is accurate and may be used to respond to this enquiry.',
        'success_text' => 'Your request has been accepted by our email server. Please keep your reference below.',
        'preview_text' => 'Your details have been saved for review.',
        'unavailable_text' => 'Online submission is temporarily unavailable. Please use the contact address on this page.',
        'attachment_limit_mb' => 5, 'attachments' => true,
        'labels' => ['name'=>'Your name','email'=>'Email address','subject'=>'Vessel / company / project','message'=>'Project requirements','attachment'=>'Supporting document'],
    ];
    return [
        'version'=>1, 'mode'=>'preview', 'retention_days'=>90,
        'forms'=>[
            'quote'=>array_replace($shared, [
                'title'=>'Tell us about your project.',
                'intro'=>'Share the work location, scope and timing. You can attach a drawing, photograph or PDF.',
                'email_title'=>'Discuss your project.',
                'email_intro'=>'Email the work location, scope and timing. Attach any relevant drawings or photographs.',
                'email_label'=>'Email your project details',
                'allowed_types'=>['pdf','jpg','png'],
            ]),
            'career'=>array_replace($shared, [
                'recipient'=>'careers@seafortservice.com',
                'title'=>'Share your experience.',
                'intro'=>'Tell us about your discipline, relevant experience and availability. You can attach your CV as a PDF.',
                'email_title'=>'Contact our recruitment team.',
                'email_intro'=>'Email your CV with your area of interest, relevant experience and availability.',
                'email_label'=>'Email your CV',
                'submit_label'=>'Send application', 'allowed_types'=>['pdf'],
                'privacy_text'=>'I confirm that the information is accurate and may be used to respond to this application.',
                'labels'=>['name'=>'Your name','email'=>'Email address','subject'=>'Role / area of interest','message'=>'Experience and availability','attachment'=>'CV / resume'],
            ]),
        ],
        'smtp'=>['host'=>'','port'=>587,'encryption'=>'tls','username'=>'','password'=>'','from_email'=>'contact@seafortservice.com','from_name'=>'Seafort'],
    ];
}

function sf_enquiry_merge(array $defaults, array $saved): array
{
    foreach ($defaults as $key=>$value) {
        if (!array_key_exists($key,$saved)) continue;
        if (is_array($value)) {
            $defaults[$key]=array_is_list($value) ? (is_array($saved[$key])?$saved[$key]:$value) : sf_enquiry_merge($value,is_array($saved[$key])?$saved[$key]:[]);
        } elseif (gettype($value)===gettype($saved[$key])) $defaults[$key]=$saved[$key];
    }
    return $defaults;
}

function sf_enquiry_settings(): array
{
    return sf_enquiry_merge(sf_enquiry_defaults(),sf_read_json('enquiry-settings.json'));
}

function sf_enquiry_settings_revision(): string
{
    return sf_content_digest(sf_enquiry_settings());
}

function sf_enquiry_clean_text(mixed $value,int $max,string $label,bool $required=false): string
{
    if (!is_string($value) || !preg_match('//u',$value) || str_contains($value,"\0")) throw new InvalidArgumentException($label.' alanı geçersiz.');
    $value=trim(str_replace(["\r\n","\r"],"\n",$value));
    if (($required && $value==='') || sf_strlen($value)>$max) throw new InvalidArgumentException($label.' alanını kontrol edin (en fazla '.$max.' karakter).');
    return $value;
}

function sf_enquiry_email(string $value,bool $allowEmpty=false): string
{
    $value=trim($value);
    if ($value==='' && $allowEmpty) return '';
    if (strlen($value)>180 || preg_match('/[\r\n\x00-\x1f\x7f]/',$value) || !filter_var($value,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Geçerli bir e-posta adresi yazın.');
    return $value;
}

function sf_enquiry_smtp_address(string $host): string
{
    $host=trim($host);
    if ($host==='' || strlen($host)>253 || !preg_match('/\A[a-zA-Z0-9.-]+\z/D',$host) || str_contains($host,'..')) throw new InvalidArgumentException('Geçerli bir SMTP sunucu adı yazın.');
    $testLocal=getenv('SF_ENQUIRY_TEST_SMTP')==='1' && in_array(PHP_SAPI,['cli','cli-server'],true);
    if ($testLocal && in_array($host,['localhost','127.0.0.1'],true)) return '127.0.0.1';
    $addresses=[];
    if (filter_var($host,FILTER_VALIDATE_IP)) $addresses[]=$host;
    else {
        foreach (dns_get_record($host,DNS_A|DNS_AAAA) ?: [] as $record) {
            $ip=$record['ip']??$record['ipv6']??'';
            if ($ip!=='') $addresses[]=$ip;
        }
    }
    if ($addresses===[]) throw new InvalidArgumentException('SMTP sunucu adresi çözümlenemedi. Sunucu adını kontrol edin.');
    foreach ($addresses as $ip) if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_GLOBAL_RANGE)) throw new InvalidArgumentException('SMTP sunucusu herkese açık, geçerli bir sunucu adresi olmalıdır.');
    return (string)$addresses[0];
}

function sf_enquiry_delivery_ready(array $settings): bool
{
    $smtp=$settings['smtp'];
    return $smtp['host']!=='' && $smtp['username']!=='' && $smtp['password']!=='' && $smtp['from_email']!=='';
}

/** Public production pages use email links until live SMTP delivery is configured. */
function sf_enquiry_email_only(array $settings): bool
{
    $config=require SF_ROOT.'/app/config.php';
    return ($config['indexable']??false)===true
        && ($settings['mode']!=='live' || !sf_enquiry_delivery_ready($settings));
}

function sf_enquiry_save_settings(array $input,string $revision): void
{
    $current=sf_enquiry_settings();$next=sf_enquiry_defaults();
    if (!in_array($input['mode']??null,['preview','live'],true)) throw new InvalidArgumentException('Gönderim modunu seçin.');
    $next['mode']=$input['mode'];
    $retention=filter_var($input['retention_days']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>365]]);
    if ($retention===false) throw new InvalidArgumentException('Saklama süresi 1–365 gün arasında olmalıdır.');
    $next['retention_days']=$retention;
    foreach (['quote','career'] as $kind) {
        $raw=is_array($input['forms'][$kind]??null)?$input['forms'][$kind]:[];
        $form=$next['forms'][$kind];
        $form['enabled']=($raw['enabled']??'')==='1';
        $form['attachments']=($raw['attachments']??'')==='1';
        $form['recipient']=sf_enquiry_email(is_string($raw['recipient']??null)?$raw['recipient']:'',true);
        foreach (['title'=>160,'intro'=>1500,'submit_label'=>70,'privacy_text'=>1500,'success_text'=>1000,'preview_text'=>1000,'unavailable_text'=>1000] as $field=>$limit) $form[$field]=sf_enquiry_clean_text($raw[$field]??'',$limit,$field,true);
        foreach (['email_title'=>160,'email_intro'=>1500,'email_label'=>70] as $field=>$limit) $form[$field]=sf_enquiry_clean_text($raw[$field]??$current['forms'][$kind][$field],$limit,$field,true);
        $limit=filter_var($raw['attachment_limit_mb']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>10]]);
        if ($limit===false) throw new InvalidArgumentException('Dosya sınırı 1–10 MB olmalıdır.');
        $form['attachment_limit_mb']=$limit;
        $types=is_array($raw['allowed_types']??null)?array_values(array_unique($raw['allowed_types'])):[];
        $allowed=$kind==='career'?['pdf']:['pdf','jpg','png'];
        if (array_diff($types,$allowed) || ($form['attachments'] && $types===[])) throw new InvalidArgumentException('İzin verilen dosya türlerini seçin.');
        $form['allowed_types']=$types;
        foreach ($form['labels'] as $field=>$label) $form['labels'][$field]=sf_enquiry_clean_text($raw['labels'][$field]??'',$field==='message'?120:100,'Alan başlığı',true);
        if ($next['mode']==='live' && $form['enabled'] && $form['recipient']==='') throw new InvalidArgumentException('Canlı gönderim için etkin formların alıcı adreslerini doldurun.');
        $next['forms'][$kind]=$form;
    }
    $raw=is_array($input['smtp']??null)?$input['smtp']:[];
    foreach (['host'=>253,'username'=>180,'from_name'=>120] as $field=>$limit) {
        $next['smtp'][$field]=sf_enquiry_clean_text($raw[$field]??'',$limit,'SMTP '.$field);
        if (preg_match('/[\r\n]/',$next['smtp'][$field])) throw new InvalidArgumentException('SMTP alanlarında satır sonu kullanılamaz.');
    }
    $next['smtp']['from_email']=sf_enquiry_email(is_string($raw['from_email']??null)?$raw['from_email']:'',true);
    $port=filter_var($raw['port']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
    if ($port===false) throw new InvalidArgumentException('SMTP portu geçersiz.');
    $next['smtp']['port']=$port;
    if (!in_array($raw['encryption']??null,['tls','ssl'],true)) throw new InvalidArgumentException('SMTP için STARTTLS veya TLS seçin.');
    $next['smtp']['encryption']=$raw['encryption'];
    $password=is_string($raw['password']??null)?$raw['password']:'';
    if (strlen($password)>1000 || preg_match('/[\r\n\x00]/',$password)) throw new InvalidArgumentException('SMTP parolası geçersiz.');
    $next['smtp']['password']=($raw['clear_password']??'')==='1'?'':($password!==''?$password:$current['smtp']['password']);
    if ($next['smtp']['host']!=='' && !preg_match('/\A[a-zA-Z0-9.-]+\z/D',$next['smtp']['host'])) throw new InvalidArgumentException('SMTP sunucu adı geçersiz.');
    if ($next['mode']==='live') {
        if (!sf_enquiry_delivery_ready($next)) throw new InvalidArgumentException('Canlı gönderim için SMTP sunucusu, kullanıcı adı, parola ve gönderen adresi gereklidir.');
        sf_enquiry_smtp_address($next['smtp']['host']);
    }
    sf_update_json('enquiry-settings.json',static function(array &$saved) use ($revision,$next):void {
        $existing=sf_enquiry_merge(sf_enquiry_defaults(),$saved);
        if ($revision==='' || !hash_equals(sf_content_digest($existing),$revision)) throw new InvalidArgumentException('Ayarlar başka bir sekmede değişti. Sayfayı yenileyin.');
        $saved=$next;
    });
}

function sf_enquiry_public_form(string $kind): array
{
    if (!in_array($kind,['quote','career'],true)) throw new InvalidArgumentException('Unknown enquiry kind.');
    $settings=sf_enquiry_settings();$form=$settings['forms'][$kind];
    try { $form['direct_email']=sf_enquiry_email($form['recipient'],true); }
    catch (InvalidArgumentException) { $form['direct_email']=''; }
    $form['email_only']=sf_enquiry_email_only($settings);
    $form['mode']=$settings['mode'];$form['preview']=$settings['mode']==='preview';
    $extensions=['pdf'=>'.pdf','jpg'=>'.jpg,.jpeg','png'=>'.png'];
    $types=array_values(array_intersect($form['allowed_types'],array_keys($extensions)));
    $form['attachment_accept']=implode(',',array_map(static fn(string $type):string=>$extensions[$type],$types));
    $form['file_hint']=$form['attachments'] ? strtoupper(implode(', ',$types)).' · maximum '.$form['attachment_limit_mb'].' MB · optional' : '';
    return $form;
}

function sf_enquiry_session(): void
{
    if (session_status()!==PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.use_trans_sid','0');
        $secure=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') || ($_SERVER['SERVER_PORT']??'')==='443';
        session_name('seafort_forms');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }
    $_SESSION['enquiry_csrf']??=bin2hex(random_bytes(32));
    $_SESSION['enquiry_tokens']??=[];
    foreach ($_SESSION['enquiry_tokens'] as $token=>$entry) if (time()-(int)($entry['created']??0)>7200) unset($_SESSION['enquiry_tokens'][$token]);
    header('Cache-Control: no-store, private');header('Pragma: no-cache');
}

function sf_enquiry_csrf(): string {sf_enquiry_session();return $_SESSION['enquiry_csrf'];}
function sf_enquiry_nonce(): string
{
    sf_enquiry_session();
    if (count($_SESSION['enquiry_tokens'])>=20) array_shift($_SESSION['enquiry_tokens']);
    $nonce=bin2hex(random_bytes(24));$_SESSION['enquiry_tokens'][$nonce]=['created'=>time()];return $nonce;
}

function sf_enquiry_rate_limit(): bool
{
    $salt=sf_update_json('enquiry-rate-key.json',static function(array &$data):string {$data['key']??=bin2hex(random_bytes(32));return $data['key'];});
    $key=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'),$salt);$now=time();
    return sf_update_json('enquiry-rate-limits.json',static function(array &$data) use ($key,$now):bool {
        foreach ($data as $id=>$entry) if (!is_array($entry) || $now-(int)($entry['start']??0)>1800) unset($data[$id]);
        $data[$key]??=['start'=>$now,'count'=>0];$data['global']??=['start'=>$now,'count'=>0];
        if ($data[$key]['count']>=8 || $data['global']['count']>=500) return false;
        $data[$key]['count']++;$data['global']['count']++;return true;
    });
}

function sf_enquiry_upload(?array $file,array $form): ?array
{
    if ($file===null || ($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    if (!$form['attachments']) throw new InvalidArgumentException('Attachments are not enabled for this form.');
    if (($file['error']??null)!==UPLOAD_ERR_OK || !is_string($file['tmp_name']??null) || !is_uploaded_file($file['tmp_name']) || !is_string($file['name']??null)) throw new InvalidArgumentException('The attachment could not be uploaded. Please select it again.');
    $size=filesize($file['tmp_name']);
    if ($size===false || $size<8 || $size>$form['attachment_limit_mb']*1024*1024) throw new InvalidArgumentException('The attachment exceeds the allowed size or is empty.');
    $extension=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));if($extension==='jpeg')$extension='jpg';
    if (!in_array($extension,$form['allowed_types'],true)) throw new InvalidArgumentException('Please select one of the allowed file types.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $mimes=['pdf'=>'application/pdf','jpg'=>'image/jpeg','png'=>'image/png'];
    if (($mimes[$extension]??null)!==$mime) throw new InvalidArgumentException('The attachment content does not match its file type.');
    $handle=fopen($file['tmp_name'],'rb');$signature=$handle===false?'':fread($handle,8);if(is_resource($handle))fclose($handle);
    if (($extension==='pdf' && !str_starts_with($signature,'%PDF-')) || ($extension==='jpg' && !str_starts_with($signature,"\xff\xd8\xff")) || ($extension==='png' && $signature!=="\x89PNG\r\n\x1a\n")) throw new InvalidArgumentException('The attachment format is invalid.');
    if (in_array($extension,['jpg','png'],true) && getimagesize($file['tmp_name'])===false) throw new InvalidArgumentException('Please upload a valid image.');
    sf_storage_ready();$dir=SF_STORAGE_DIR.'/enquiry-files';
    if(!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir))throw new RuntimeException('Attachment storage unavailable.');
    $name=bin2hex(random_bytes(24)).'.'.$extension;$path=$dir.'/'.$name;
    if(!move_uploaded_file($file['tmp_name'],$path))throw new RuntimeException('Attachment could not be stored.');
    @chmod($path,0600);
    $original=preg_replace('/[\x00-\x1f\x7f\\\\\/]/u','_',substr($file['name'],0,160));
    return ['file'=>$name,'name'=>$original?:'attachment.'.$extension,'mime'=>$mime,'size'=>$size];
}

function sf_enquiry_attachment_path(array $attachment): string
{
    $name=$attachment['file']??'';
    if (!is_string($name) || !preg_match('/\A[a-f0-9]{48}\.(?:pdf|jpg|png)\z/D',$name)) throw new RuntimeException('Attachment not found.');
    $path=SF_STORAGE_DIR.'/enquiry-files/'.$name;
    if(!is_file($path))throw new RuntimeException('Attachment not found.');
    return $path;
}

function sf_enquiry_purge(): void
{
    $days=sf_enquiry_settings()['retention_days'];$before=time()-$days*86400;
    $expired=sf_update_json('enquiries.json',static function(array &$data) use ($before):array {
        $expired=[];$data['items']??=[];
        foreach($data['items'] as $id=>$item) if(strtotime((string)($item['created_at']??''))<$before){if(is_array($item['attachment']??null))$expired[]=$item['attachment'];unset($data['items'][$id]);}
        return $expired;
    },['version'=>1,'items'=>[]]);
    foreach($expired as $attachment){try{@unlink(sf_enquiry_attachment_path($attachment));}catch(RuntimeException){}}
}

function sf_enquiry_list(): array
{
    sf_enquiry_purge();$items=array_values(sf_read_json('enquiries.json',['version'=>1,'items'=>[]])['items']??[]);
    usort($items,static fn(array $a,array $b):int=>strcmp($b['created_at'],$a['created_at']));return $items;
}

function sf_enquiry_find(string $id): ?array
{
    if(!preg_match('/\A[a-f0-9]{32}\z/D',$id))return null;
    return sf_read_json('enquiries.json',['items'=>[]])['items'][$id]??null;
}

function sf_enquiry_update(string $id,array $changes): void
{
    sf_update_json('enquiries.json',static function(array &$data) use ($id,$changes):void {
        if(!isset($data['items'][$id]))throw new InvalidArgumentException('Başvuru bulunamadı.');
        foreach($changes as $key=>$value)$data['items'][$id][$key]=$value;
    },['version'=>1,'items'=>[]]);
}

function sf_enquiry_delete(string $id): void
{
    sf_update_json('enquiries.json',static function(array &$data) use ($id):void {
        if(!isset($data['items'][$id]))throw new InvalidArgumentException('Başvuru bulunamadı.');
        $attachment=$data['items'][$id]['attachment']??null;
        if(is_array($attachment)){
            try{$path=sf_enquiry_attachment_path($attachment);}catch(RuntimeException){$path=null;}
            if($path!==null && !unlink($path))throw new RuntimeException('Attachment could not be deleted.');
        }
        unset($data['items'][$id]);
    },['version'=>1,'items'=>[]]);
}

function sf_enquiry_send(array $item,array $settings): void
{
    $smtp=$settings['smtp'];$ip=sf_enquiry_smtp_address($smtp['host']);
    foreach(['Exception','SMTP','PHPMailer'] as $class)require_once SF_ROOT.'/vendor/phpmailer/'.$class.'.php';
    $mail=new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();$mail->SMTPDebug=0;$mail->Timeout=15;
    // Pin the checked destination while verifying the certificate against its actual DNS name.
    $mail->Host=str_contains($ip,':')?'['.$ip.']':$ip;$mail->Port=$smtp['port'];
    $mail->SMTPAuth=true;$mail->Username=$smtp['username'];$mail->Password=$smtp['password'];
    $mail->SMTPSecure=$smtp['encryption']==='ssl'?\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAutoTLS=false;
    $mail->SMTPOptions=['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false,'peer_name'=>$smtp['host'],'SNI_enabled'=>true]];
    $mail->CharSet='UTF-8';$mail->Encoding='base64';
    $mail->setFrom($smtp['from_email'],$smtp['from_name']);$mail->addAddress($item['recipient']);
    $mail->addReplyTo($item['email'],$item['name']);
    $mail->Subject=($item['kind']==='career'?'Career application':'Project enquiry').' | '.$item['reference'].($item['subject']!==''?' | '.$item['subject']:'');
    $mail->isHTML(false);
    $mail->Body="Reference: ".$item['reference']."\nType: ".$item['kind']."\nName: ".$item['name']."\nEmail: ".$item['email']."\nSubject: ".$item['subject']."\n\n".$item['message']."\n\nConsent confirmed at: ".$item['created_at'];
    if(is_array($item['attachment']))$mail->addAttachment(sf_enquiry_attachment_path($item['attachment']),$item['attachment']['name']);
    $mail->send();
}

function sf_enquiry_handle(string $kind): array
{
    if(!in_array($kind,['quote','career'],true))throw new InvalidArgumentException('Unknown enquiry kind.');
    $settings=sf_enquiry_settings();$form=$settings['forms'][$kind];
    $result=['errors'=>[],'values'=>['name'=>'','email'=>'','subject'=>'','message'=>'','consent'=>''],'success'=>false,'preview'=>$settings['mode']==='preview','reference'=>''];
    // Check availability before starting a session, replaying a saved result or accepting files.
    if(!$form['enabled'] || sf_enquiry_email_only($settings)) {
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
            http_response_code(405);header('Allow: GET, HEAD');
            $result['errors']['form']=$form['enabled']?'Please send your message directly by email using the address on this page.':$form['unavailable_text'];
        }
        return $result;
    }
    sf_enquiry_session();
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')return $result;
    $csrf=$_POST['csrf']??'';$nonce=$_POST['submission_token']??'';
    if(!is_string($csrf)||$csrf===''||!hash_equals(sf_enquiry_csrf(),$csrf)||!is_string($nonce)||!isset($_SESSION['enquiry_tokens'][$nonce])){
        $result['errors']['form']='Your form session has expired. Refresh the page and try again.';return $result;
    }
    $previous=$_SESSION['enquiry_tokens'][$nonce]['result']??null;
    if(is_array($previous))return $previous;
    $origin=$_SERVER['HTTP_ORIGIN']??'';
    $requestHost=(string)parse_url('http://'.($_SERVER['HTTP_HOST']??''),PHP_URL_HOST);
    if($origin!=='' && strtolower((string)parse_url($origin,PHP_URL_HOST))!==strtolower($requestHost)){
        $result['errors']['form']='Please submit this form from our website.';return $result;
    }
    if(($_POST['website']??'')!==''){$result['errors']['form']='The request could not be accepted.';return $result;}
    if(!$form['enabled']){$result['errors']['form']=$form['unavailable_text'];return $result;}
    try{if(!sf_enquiry_rate_limit()){$result['errors']['form']='Too many requests. Please wait 30 minutes before trying again.';return $result;}}
    catch(Throwable){$result['errors']['form']='The form is temporarily unavailable. Please try again later.';return $result;}
    foreach(['name'=>120,'email'=>180,'subject'=>160,'message'=>6000] as $field=>$max){
        $value=$_POST[$field]??'';
        if(!is_string($value)||!preg_match('//u',$value)||str_contains($value,"\0")||sf_strlen($value)>$max){$result['errors'][$field]='Please enter a valid value (maximum '.$max.' characters).';continue;}
        $value=trim(str_replace(["\r\n","\r"],"\n",$value));$result['values'][$field]=$value;
        if($field!=='subject' && $value==='')$result['errors'][$field]='Please complete this field.';
        if($field!=='message' && preg_match('/[\r\n]/',$value))$result['errors'][$field]='Please use a single line.';
        if($field==='email' && !filter_var($value,FILTER_VALIDATE_EMAIL))$result['errors'][$field]='Please enter a valid email address.';
    }
    $result['values']['consent']=($_POST['consent']??'')==='1'?'1':'';
    if($result['values']['consent']!=='1')$result['errors']['consent']='Please confirm before submitting.';
    if($result['errors'])return $result;
    if($settings['mode']==='live' && (!sf_enquiry_delivery_ready($settings)||$form['recipient']==='')){$result['errors']['form']=$form['unavailable_text'];return $result;}
    $attachment=null;$id=null;
    try{
        try{$attachment=sf_enquiry_upload(isset($_FILES['attachment'])&&is_array($_FILES['attachment'])?$_FILES['attachment']:null,$form);}
        catch(InvalidArgumentException $error){$result['errors']['attachment']=$error->getMessage();return $result;}
        sf_enquiry_purge();$id=bin2hex(random_bytes(16));$reference='SF-'.gmdate('ymd').'-'.strtoupper(substr($id,0,8));
        $item=['id'=>$id,'reference'=>$reference,'kind'=>$kind,'name'=>$result['values']['name'],'email'=>$result['values']['email'],'subject'=>$result['values']['subject'],'message'=>$result['values']['message'],'recipient'=>$form['recipient'],'attachment'=>$attachment,'created_at'=>sf_now(),'read'=>false,'consent'=>true,'status'=>$settings['mode']==='preview'?'preview':'pending'];
        sf_update_json('enquiries.json',static function(array &$data) use ($id,$item):void{
            $data['items']??=[];if(count($data['items'])>=2000)throw new RuntimeException('Inbox storage limit reached.');$data['items'][$id]=$item;
        },['version'=>1,'items'=>[]]);
        $result['reference']=$reference;
        if($settings['mode']==='live'){
            // Persist the used nonce before SMTP: a worker interruption must not resend on refresh.
            $pending=$result;$pending['errors']['form']='Your request is recorded under reference '.$reference.'. The email result is not yet confirmed; please contact us before submitting again.';
            $_SESSION['enquiry_tokens'][$nonce]['result']=$pending;
            session_write_close();session_start();
            try{sf_enquiry_send($item,$settings);}
            catch(Throwable){
                $result['errors']['form']='Your request was saved, but email acceptance could not be confirmed. Please contact us with reference '.$reference.'.';
                $_SESSION['enquiry_tokens'][$nonce]['result']=$result;
                try{sf_enquiry_update($id,['status'=>'failed']);}catch(Throwable){}
                return $result;
            }
            $result['success']=true;
            $_SESSION['enquiry_tokens'][$nonce]['result']=$result;
            // A bookkeeping failure after SMTP acceptance is not an email failure.
            try{sf_enquiry_update($id,['status'=>'smtp_accepted','sent_at'=>sf_now()]);}catch(Throwable){}
        }
        $result['success']=true;
        $_SESSION['enquiry_tokens'][$nonce]['result']=$result;
        return $result;
    }catch(Throwable){
        if($id===null || sf_enquiry_find($id)===null){if(is_array($attachment)){try{@unlink(sf_enquiry_attachment_path($attachment));}catch(Throwable){}}}
        $result['errors']['form']='The request could not be saved. Please try again later.';return $result;
    }
}
