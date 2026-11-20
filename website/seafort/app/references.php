<?php
declare(strict_types=1);

/** References are empty until real content and its publication permission are supplied. */
function sf_reference_defaults(): array
{
    return [
        'version'=>2,
        'settings'=>[
            'page_title'=>'References', 'page_intro'=>'Companies from our reference archive and written feedback shared with permission.',
            'logos_eyebrow'=>'COMPANY REFERENCES', 'logos_title'=>'Companies in our reference archive.',
            'logos_intro'=>'', 'quotes_eyebrow'=>'PROJECT FEEDBACK', 'quotes_title'=>'In their words.', 'quotes_intro'=>'',
        ],
        'logos'=>[], 'quotes'=>[],
        'custom_page'=>[
            'enabled'=>false, 'title'=>'EGE DERİN', 'intro'=>'', 'body'=>'', 'cover'=>'',
            'cover_alt'=>'', 'role'=>'', 'source_note'=>'', 'permission'=>false,
        ],
    ];
}

function sf_reference_normalise(array $data): array
{
    $defaults=sf_reference_defaults();
    foreach (['logos','quotes'] as $type) $defaults[$type]=is_array($data[$type]??null)?array_values(array_filter($data[$type],'is_array')):[];
    // Keep legacy sample records editable as drafts on every installation.
    foreach ($defaults['quotes'] as $index=>$quote) {
        if (($quote['status']??'')==='published' && sf_reference_sample_quote($quote)) {
            $defaults['quotes'][$index]['status']='draft';
        }
    }
    foreach (['settings','custom_page'] as $group) {
        foreach ($defaults[$group] as $key=>$value) {
            if (isset($data[$group][$key]) && gettype($value)===gettype($data[$group][$key])) $defaults[$group][$key]=$data[$group][$key];
        }
    }
    $custom=$defaults['custom_page'];
    if (in_array($custom['title'],['Ege Deniz','EGE DENIZ'],true) && $custom['intro']==='' && $custom['body']==='' && $custom['role']==='' && !$custom['enabled']) $defaults['custom_page']['title']='EGE DERİN';
    return $defaults;
}

function sf_reference_data(): array { return sf_reference_normalise(sf_read_json('references.json')); }
function sf_reference_revision(): string { return sf_content_digest(sf_reference_data()); }

function sf_reference_type(string $type): string
{
    if (!in_array($type,['logos','quotes'],true)) throw new InvalidArgumentException('Referans bölümü bulunamadı.');
    return $type;
}

function sf_reference_text(mixed $value,int $limit,string $label): string
{
    if (!is_string($value) || !preg_match('//u',$value) || str_contains($value,"\0")) throw new InvalidArgumentException($label.' alanı geçersiz.');
    $value=trim(str_replace(["\r\n","\r"],"\n",$value));
    if (sf_strlen($value)>$limit) throw new InvalidArgumentException($label.' en fazla '.$limit.' karakter olabilir.');
    return $value;
}

function sf_reference_link(string $value): string
{
    $value=trim($value);
    if ($value==='') return '';
    if (str_starts_with($value,'/') && !str_starts_with($value,'//')) return sf_content_link($value);
    $parts=parse_url($value);
    if (strlen($value)>500 || preg_match('/[\x00-\x20\x7f]/',$value) || !filter_var($value,FILTER_VALIDATE_URL) || !is_array($parts) || ($parts['scheme']??'')!=='https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
        throw new InvalidArgumentException('Bağlantı site içi bir adres veya geçerli bir HTTPS adresi olmalıdır.');
    }
    return $value;
}

function sf_reference_sample_quote(array $item): bool
{
    foreach (['quote','name','role','company'] as $key) {
        if (preg_match('/^\s*lorem\s+[iİı]\x{0307}?psum\b/iu',(string)($item[$key]??''))===1) return true;
    }
    return false;
}

function sf_reference_items(string $type,bool $publishedOnly=true): array
{
    $items=sf_reference_data()[sf_reference_type($type)];
    if ($publishedOnly) $items=array_values(array_filter($items,static fn(array $item):bool=>($item['status']??'')==='published' && ($item['permission']??false)===true));
    usort($items,static fn(array $a,array $b):int=>((int)($a['order']??0)<=>(int)($b['order']??0))?:strcmp((string)($a['created_at']??''),(string)($b['created_at']??'')));
    return $items;
}

function sf_reference_find(string $type,string $id): ?array
{
    foreach (sf_reference_items($type,false) as $item) if (($item['id']??'')===$id) return $item;
    return null;
}

function sf_reference_validate(string $type,array $input): array
{
    sf_reference_type($type);
    $status=$input['status']??'draft';
    if (!in_array($status,['draft','published'],true)) throw new InvalidArgumentException('Yayın durumunu seçin.');
    $order=filter_var($input['order']??'0',FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>9999]]);
    if ($order===false) throw new InvalidArgumentException('Sıra numarası 0–9999 arasında olmalıdır.');
    $fields=['status'=>$status,'order'=>$order,'permission'=>in_array($input['permission']??false,[true,'1',1],true)];
    $limits=$type==='logos'?['name'=>180,'image'=>250,'image_alt'=>240,'href'=>500,'source_note'=>1500]:['quote'=>3000,'name'=>160,'role'=>180,'company'=>180,'href'=>500,'source_note'=>1500];
    foreach ($limits as $key=>$limit) $fields[$key]=sf_reference_text($input[$key]??'',$limit,$key);
    $fields['href']=sf_reference_link($fields['href']);
    if ($type==='logos') {
        $fields['image']=sf_cover_path($fields['image']);
        $relationship=$input['relationship']??'';
        if (!in_array($relationship,['','reference','customer','partner','supplier'],true)) throw new InvalidArgumentException('Firmanın ilişki türünü seçin.');
        $fields['relationship']=$relationship;
        if ($status==='published' && $relationship==='') throw new InvalidArgumentException('Yayımlamadan önce firmanın doğrulanmış ilişki türünü seçin.');
        if ($status==='published' && (sf_strlen($fields['name'])<2 || $fields['image']==='')) throw new InvalidArgumentException('Logo yayımlamak için gerçek firma adı ve görsel ekleyin.');
        if ($fields['image_alt']==='') $fields['image_alt']=$fields['name'];
    } elseif ($status==='published') {
        if (sf_reference_sample_quote($fields)) throw new InvalidArgumentException('Örnek metni taslak olarak kaydedebilir veya onaylı müşteri bilgileriyle değiştirebilirsiniz.');
        if (sf_strlen($fields['quote'])<10) throw new InvalidArgumentException('Yayımlamak için müşterinin onayladığı alıntıyı ekleyin.');
    }
    if ($status==='published' && !$fields['permission']) throw new InvalidArgumentException('Yayımlamadan önce içerik ve kullanım izinlerini doğrulayın.');
    return $fields;
}

function sf_reference_save(string $type,array $input,?string $id=null,?string $revision=null): string
{
    $fields=sf_reference_validate($type,$input);
    return sf_update_json('references.json',static function(array &$saved) use ($type,$fields,$id,$revision):string {
        $data=sf_reference_normalise($saved);$found=null;
        foreach ($data[$type] as $index=>$item) if (($item['id']??'')===$id) $found=$index;
        if ($id!==null && $found===null) throw new InvalidArgumentException('Düzenlenecek kayıt bulunamadı.');
        if ($found!==null && ($revision===null || !hash_equals((string)($data[$type][$found]['updated_at']??''),$revision))) throw new InvalidArgumentException('Bu kayıt başka bir sekmede değişti. Metninizi kopyalayıp sayfayı yenileyin.');
        if ($found===null && count($data[$type])>=200) throw new InvalidArgumentException('Bu bölümde en fazla 200 kayıt tutulabilir.');
        $now=sf_now();$item=$found===null?['id'=>bin2hex(random_bytes(12)),'created_at'=>$now]:$data[$type][$found];
        $item=array_replace($item,$fields,['updated_at'=>$now]);
        if ($found===null) $data[$type][]=$item; else $data[$type][$found]=$item;
        $saved=$data;return $item['id'];
    },sf_reference_defaults());
}

function sf_reference_delete(string $type,string $id,string $revision): void
{
    sf_reference_type($type);
    sf_update_json('references.json',static function(array &$saved) use ($type,$id,$revision):void {
        $data=sf_reference_normalise($saved);$found=false;
        foreach ($data[$type] as $index=>$item) if (($item['id']??'')===$id) {
            if ($revision==='' || !hash_equals((string)($item['updated_at']??''),$revision)) throw new InvalidArgumentException('Kayıt değişti. Sayfayı yenileyip tekrar deneyin.');
            unset($data[$type][$index]);$found=true;break;
        }
        if (!$found) throw new InvalidArgumentException('Kayıt bulunamadı.');
        $data[$type]=array_values($data[$type]);$saved=$data;
    },sf_reference_defaults());
}

function sf_reference_custom_ready(array $page): bool
{
    return ($page['enabled']??false)===true && ($page['permission']??false)===true && sf_strlen((string)($page['title']??''))>=2 && sf_strlen((string)($page['intro']??''))>=10 && sf_strlen((string)($page['body']??''))>=20 && sf_strlen((string)($page['role']??''))>=3;
}

function sf_reference_save_configuration(array $input,string $revision): void
{
    $defaults=sf_reference_defaults();$settings=[];$raw=is_array($input['settings']??null)?$input['settings']:[];
    foreach ($defaults['settings'] as $key=>$value) $settings[$key]=sf_reference_text($raw[$key]??'',str_ends_with($key,'intro')?1000:180,$key);
    if ($settings['logos_title']==='' || $settings['quotes_title']==='') throw new InvalidArgumentException('Bölüm başlıkları boş bırakılamaz.');
    $raw=is_array($input['custom_page']??null)?$input['custom_page']:[];$page=$defaults['custom_page'];
    foreach (['title'=>180,'intro'=>1800,'body'=>30000,'cover'=>250,'cover_alt'=>240,'role'=>2000,'source_note'=>3000] as $key=>$limit) $page[$key]=sf_reference_text($raw[$key]??'',$limit,$key);
    $page['enabled']=($raw['enabled']??'')==='1';$page['permission']=($raw['permission']??'')==='1';
    $page['cover']=sf_cover_path($page['cover']);
    if ($page['cover']!=='' && $page['cover_alt']==='') throw new InvalidArgumentException('Sayfa görseline kısa bir açıklama ekleyin.');
    if ($page['enabled'] && !sf_reference_custom_ready($page)) throw new InvalidArgumentException('EGE DERİN sayfasını açmak için başlık, giriş, içerik, Seafort’un rolü ve yayın iznini tamamlayın.');
    sf_update_json('references.json',static function(array &$saved) use ($revision,$settings,$page):void {
        $data=sf_reference_normalise($saved);
        if ($revision==='' || !hash_equals(sf_content_digest($data),$revision)) throw new InvalidArgumentException('Ayarlar başka bir sekmede değişti. Sayfayı yenileyin.');
        $data['settings']=$settings;$data['custom_page']=$page;$saved=$data;
    },sf_reference_defaults());
}

/** Returns approved public fields only; internal sources and permission notes stay private. */
function sf_reference_custom_page(string $slug,bool $allowPreview=false): ?array
{
    if ($slug!=='ege-derin') return null;
    $page=sf_reference_data()['custom_page'];
    $fields=array_intersect_key($page,array_flip(['title','intro','body','cover','cover_alt','role']));
    if (sf_reference_custom_ready($page)) return ['slug'=>'ege-derin','preview_placeholder'=>false]+$fields;
    if (!$allowPreview) return null;
    // Explicit preview copy is never saved as company evidence or published by this fallback.
    $fallback=[
        'title'=>'EGE DERİN',
        'intro'=>'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
        'body'=>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae arcu quis mauris luctus posuere. Sed dignissim, justo eget tempor facilisis, neque lectus volutpat eros.\n\nUt enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore.\n\nAenean sit amet erat sed nibh porttitor aliquet. Curabitur vitae magna non erat posuere tristique. Donec commodo sapien at enim consequat, non dictum nisl fermentum.",
        'role'=>'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae arcu quis mauris luctus posuere.',
        'cover'=>'/assets/project/underwater-hull-detail.webp',
        'cover_alt'=>'Illustrative underwater image from the Seafort media archive',
    ];
    foreach($fallback as $key=>$value) if(($fields[$key]??'')==='')$fields[$key]=$value;
    return ['slug'=>'ege-derin','preview_placeholder'=>true]+$fields;
}

function sf_reference_logos_render(): void
{
    $items=array_values(array_filter(sf_reference_items('logos'),static fn(array $item):bool=>sf_cover_url((string)($item['image']??''))!==''));
    if (!$items) return;
    $settings=sf_reference_data()['settings'];require_once __DIR__.'/views/reference-blocks.php';sf_reference_logos_markup($items,$settings);
}

function sf_reference_quotes_render(): void
{
    $items=sf_reference_items('quotes');if (!$items) return;
    $settings=sf_reference_data()['settings'];require_once __DIR__.'/views/reference-blocks.php';sf_reference_quotes_markup($items,$settings);
}

function sf_reference_custom_render(array $page): void
{
    require_once __DIR__.'/views/reference-blocks.php';sf_reference_custom_markup($page);
}
