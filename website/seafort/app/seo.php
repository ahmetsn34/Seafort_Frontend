<?php
declare(strict_types=1);
require_once __DIR__ . '/collections.php';
require_once __DIR__ . '/discovery.php';

/** Metadata stays separate from visible copy; publication eligibility comes from discovery. */
function sf_seo_defaults(): array
{
    return ['version'=>1, 'site_name'=>'Seafort', 'default_image'=>'/assets/archive/marine-worksite.webp', 'default_image_alt'=>'Vessels alongside a marine worksite', 'pages'=>[]];
}

function sf_seo_text(mixed $value, int $limit, bool $required=false): string
{
    if (!is_string($value) || !preg_match('//u',$value) || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/',$value)) throw new InvalidArgumentException('SEO metni geçersiz.');
    $value=trim((string)preg_replace('/\s+/u',' ',strip_tags($value)));
    if (sf_strlen($value)>$limit || ($required && $value==='')) throw new InvalidArgumentException('SEO metni uzunluğunu kontrol edin (en fazla '.$limit.' karakter).');
    return $value;
}

function sf_seo_public_routes(): array { return sf_discovery_entries(false); }

function sf_seo_settings(): array
{
    $defaults=sf_seo_defaults();$stored=sf_read_json('seo.json');
    foreach (['site_name','default_image','default_image_alt'] as $field) {
        if (is_string($stored[$field]??null)) $defaults[$field]=$stored[$field];
    }
    foreach (is_array($stored['pages']??null)?$stored['pages']:[] as $route=>$entry) {
        if (!is_string($route) || !is_array($entry)) continue;
        $defaults['pages'][$route]=[];
        foreach (['title','description','image','image_alt'] as $field) $defaults['pages'][$route][$field]=is_string($entry[$field]??null)?$entry[$field]:'';
    }
    return $defaults;
}

function sf_seo_revision(): string { return sf_content_digest(sf_seo_settings()); }

/** Save one public page at a time, preserving overrides on other pages and detecting stale edits. */
function sf_seo_save_settings(array $input, string $revision): void
{
    $route=is_string($input['route']??null)?$input['route']:'';
    if (!isset(sf_seo_public_routes()[$route])) throw new InvalidArgumentException('SEO ayarı yalnızca yayımlanmış bir sayfaya eklenebilir.');
    $siteName=sf_seo_text($input['site_name']??'',80,true);
    $defaultImage=sf_cover_path(is_string($input['default_image']??null)?$input['default_image']:'');
    $defaultAlt=sf_seo_text($input['default_image_alt']??'',240);
    $entry=[];
    foreach (['title'=>120,'description'=>320,'image_alt'=>240] as $field=>$limit) $entry[$field]=sf_seo_text($input[$field]??'',$limit);
    $entry['image']=sf_cover_path(is_string($input['image']??null)?$input['image']:'');
    sf_update_json('seo.json',static function(array &$stored) use($route,$revision,$siteName,$defaultImage,$defaultAlt,$entry): void {
        if ($revision==='' || !hash_equals(sf_seo_revision(),$revision)) throw new InvalidArgumentException('SEO ayarları başka bir sekmede değişti. Sayfayı yenileyip tekrar deneyin.');
        $stored['version']=1;$stored['site_name']=$siteName;$stored['default_image']=$defaultImage;$stored['default_image_alt']=$defaultAlt;
        $stored['pages']=is_array($stored['pages']??null)?$stored['pages']:[];
        if (array_filter($entry,static fn(string $value):bool=>$value!=='')) $stored['pages'][$route]=$entry;
        else unset($stored['pages'][$route]);
        // This is a settings audit date, never represented as a content publication date.
        $stored['updated_at']=sf_now();
    });
}

function sf_seo_clean(string $value): string
{
    return trim((string)preg_replace('/\s+/u',' ',strip_tags($value)));
}

function sf_seo_origin(array $config): string
{
    $origin=rtrim((string)($config['base_url']??''),'/');$parts=parse_url($origin);
    if (!is_array($parts) || !in_array($parts['scheme']??'',['https','http'],true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || !empty($parts['path']) || preg_match('/[\x00-\x20\x7f]/',$origin)) throw new RuntimeException('Site origin configuration is invalid.');
    return $origin;
}

function sf_seo_date(mixed $value): ?string
{
    if (!is_string($value) || !preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})\z/D',$value)) return null;
    try {
        $date=new DateTimeImmutable($value);$errors=DateTimeImmutable::getLastErrors();
        if($errors!==false&&($errors['warning_count']!==0||$errors['error_count']!==0))return null;
        return $date->format(DATE_ATOM);
    } catch (Exception) { return null; }
}

function sf_seo_service_routes(): array
{
    return ['/maintenance-repair/','/marine-services-2/','/ship-supply/','/marine-services/','/rigging-oilfield/','/shipyard-services/','/ship-construction/'];
}

/** A shared post/video URL is not a corporate identity profile, even on an allowed platform. */
function sf_seo_social_profiles(array $social): array
{
    $profiles=[];
    $patterns=['linkedin'=>'~\A/company/[^/]+/?\z~D','instagram'=>'~\A/(?!p/|reel/|reels/|stories/|explore/)[a-zA-Z0-9_.]+/?\z~D','youtube'=>'~\A/(?:@[^/]+|channel/[^/]+|c/[^/]+|user/[^/]+)/?\z~D','facebook'=>'~\A/(?!share/|sharer/|watch/|reel/|reels/|posts/|photo/|profile\.php)[a-zA-Z0-9_.-]+/?\z~D'];
    foreach($patterns as $platform=>$pattern){
        try{$url=sf_social_link($platform,(string)($social[$platform.'_url']??''));}catch(InvalidArgumentException){continue;}
        $parts=parse_url($url);if(!is_array($parts)||!preg_match($pattern,$parts['path']??''))continue;
        $profiles[]='https://'.$parts['host'].$parts['path'];
    }
    return array_values(array_unique($profiles));
}

/** Only local, existing public images can become sharing/structured-data image URLs. */
function sf_seo_image(string $path, string $origin): array
{
    $path=sf_cover_url($path);if ($path==='') return [];
    $size=@getimagesize(SF_PUBLIC_DIR.$path);
    if (!$size || !in_array($size['mime']??'',['image/jpeg','image/png','image/webp'],true)) return [];
    return ['url'=>$origin.$path,'width'=>$size[0],'height'=>$size[1],'type'=>$size['mime']];
}

function sf_seo_document(string $title,string $route,string $description,array $config,array $context=[]): array
{
    $settings=sf_seo_settings();$catalog=sf_seo_public_routes();$entry=$catalog[$route]??[];
    $override=isset($catalog[$route])?($settings['pages'][$route]??[]):[];
    $origin=sf_seo_origin($config);$canonicalPath=sf_discovery_canonical_path($route);$url=$origin.$canonicalPath;
    $titles=[
        '/'=>'Marine Steel Works, Ship Repair & Coatings',
        '/about/'=>'About Our Marine & Offshore Project Support',
        '/maintenance-repair/'=>'Ship Repair, Steel Renewal & Protective Coatings',
        '/marine-services-2/'=>'Underwater Inspection, Cleaning & Repair Support',
        '/marine-services/'=>'Marine Steel Fabrication & Onboard Installation',
        '/ship-supply/'=>'Ship Supply, Marine Equipment & Rental',
        '/manpower-team/'=>'Marine & Offshore Manpower Support',
        '/get-a-quote/'=>'Discuss Your Marine or Offshore Project',
        '/careers/'=>'Careers & Crewing Opportunities',
    ];
    $baseTitle=sf_seo_clean((string)(($override['title']??'')?:($titles[$route]??$title)));
    $pagination=preg_match('/\?page=([0-9]+)\z/D',$canonicalPath,$pageMatch)?(int)$pageMatch[1]:1;
    if($pagination>1)$baseTitle.=' – Page '.$pagination;
    $brand=sf_seo_clean($settings['site_name']);if($brand==='')$brand='Seafort';
    $metaTitle=preg_match('/(?:\||–|-)\s*'.preg_quote($brand,'/').'\z/iu',$baseTitle)?$baseTitle:$baseTitle.' | '.$brand;
    $fallbackDescription=$route==='/'?'Steel renewal, ship repair, piping, surface preparation and protective coatings for vessels and offshore structures, from workshop preparation to onboard installation.':$description;
    $metaDescription=sf_seo_clean((string)(($override['description']??'')?:$fallbackDescription));
    if ($metaDescription==='') $metaDescription=sf_seo_clean((string)($config['footer_tagline']??'Marine and offshore repair, fabrication and installation support.'));
    if($pagination>1)$metaDescription='Page '.$pagination.'. '.$metaDescription;
    $imagePath=(string)(($override['image']??'')?:($context['image']??$entry['image']??''));
    $image=sf_seo_image($imagePath,$origin);
    if (!$image) $image=sf_seo_image($settings['default_image'],$origin);
    $imageAlt=sf_seo_clean((string)(($override['image_alt']??'')?:($context['image_alt']??($entry['item']['cover_alt']??$settings['default_image_alt']))));
    $kind=(string)($context['kind']??$entry['kind']??'page');
    $eligible=isset($catalog[$route])&&($entry['eligible']??true)===true;
    $indexable=($config['indexable']??false)===true && $eligible && !sf_discovery_filtered_request($route) && (http_response_code()?:200)<400;
    $orgId=$origin.'/#organization';$webId=$origin.'/#website';$pageId=$url.'#webpage';
    $organization=['@type'=>'Organization','@id'=>$orgId,'name'=>sf_seo_clean((string)($config['company']??'Seafort')),'url'=>$origin.'/'];
    $email=(string)($config['email']??'');if(filter_var($email,FILTER_VALIDATE_EMAIL))$organization['email']=$email;
    $logo=sf_seo_image('/assets/logo-dark.png',$origin);if($logo)$organization['logo']=['@type'=>'ImageObject','url'=>$logo['url'],'width'=>$logo['width'],'height'=>$logo['height']];
    $sameAs=sf_seo_social_profiles(is_array($config['social']??null)?$config['social']:[]);
    if($sameAs)$organization['sameAs']=$sameAs;
    $website=['@type'=>'WebSite','@id'=>$webId,'url'=>$origin.'/','name'=>$brand,'publisher'=>['@id'=>$orgId],'inLanguage'=>'en'];
    $type=match(true){$route==='/about/'=>'AboutPage',in_array($route,['/get-a-quote/','/careers/'],true)=>'ContactPage',in_array($kind,['blog','projects','documents','certificates','references'],true)=>'CollectionPage',default=>'WebPage'};
    $page=['@type'=>$type,'@id'=>$pageId,'url'=>$url,'name'=>$baseTitle,'description'=>$metaDescription,'isPartOf'=>['@id'=>$webId],'inLanguage'=>'en'];
    if($image)$page['primaryImageOfPage']=['@type'=>'ImageObject','url'=>$image['url'],'width'=>$image['width'],'height'=>$image['height']];
    $graph=[$organization,$website];
    if($route!=='/'){
        $crumbs=[['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>$origin.'/']];
        $parent=str_starts_with($route,'/blog/')&&$route!=='/blog/'?['Blog','/blog/']:(str_starts_with($route,'/projects/')&&$route!=='/projects/'?['Projects','/projects/']:(in_array($route,sf_seo_service_routes(),true)?['Services','/services/']:null));
        if($parent)$crumbs[]=['@type'=>'ListItem','position'=>2,'name'=>$parent[0],'item'=>$origin.$parent[1]];
        $crumbs[]=['@type'=>'ListItem','position'=>count($crumbs)+1,'name'=>sf_seo_clean($title),'item'=>$url];
        $graph[]=['@type'=>'BreadcrumbList','@id'=>$url.'#breadcrumb','itemListElement'=>$crumbs];$page['breadcrumb']=['@id'=>$url.'#breadcrumb'];
    }
    if(in_array($route,sf_seo_service_routes(),true)){
        $serviceId=$url.'#service';$page['mainEntity']=['@id'=>$serviceId];
        $graph[]=['@type'=>'Service','@id'=>$serviceId,'name'=>sf_seo_clean($title),'description'=>sf_seo_clean($description),'url'=>$url,'provider'=>['@id'=>$orgId]];
    }
    if($kind==='post'){
        $item=is_array($context['item']??null)?$context['item']:($entry['item']??[]);
        $article=['@type'=>'Article','@id'=>$url.'#article','headline'=>sf_seo_clean($title),'description'=>sf_seo_clean($description),'mainEntityOfPage'=>['@id'=>$pageId],'publisher'=>['@id'=>$orgId],'inLanguage'=>'en'];
        $published=sf_seo_date($item['published_at']??null);$modified=sf_seo_date($item['updated_at']??null);
        if($published)$article['datePublished']=$published;if($modified)$article['dateModified']=$modified;
        $articleImage=sf_seo_image((string)($item['cover']??''),$origin);if($articleImage)$article['image']=$articleImage['url'];
        $graph[]=$article;$page['mainEntity']=['@id'=>$url.'#article'];
    }
    $graph[]=$page;
    // Unpublished/404 routes never advertise structured content to search engines.
    return ['title'=>$metaTitle,'description'=>$metaDescription,'canonical'=>$url,'image'=>$image,'image_alt'=>$imageAlt,'type'=>$kind==='post'?'article':'website','indexable'=>$indexable,'graph'=>$eligible&&((http_response_code()?:200)<400)?['@context'=>'https://schema.org','@graph'=>$graph]:null];
}

function sf_seo_json(array $data): string
{
    return json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
}
