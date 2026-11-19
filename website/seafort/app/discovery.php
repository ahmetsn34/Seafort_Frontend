<?php
declare(strict_types=1);

/** Canonical, public routes shared by discovery and the SEO editor. Never expose this file directly. */
function sf_discovery_aliases(): array
{
    return [
        '/index.php'=>'/',
        '/ege-deniz/'=>'/ege-derin/',
        '/contact/'=>'/get-a-quote/',
        '/industries/'=>'/services/',
        '/services/marine-services/'=>'/marine-services-2/',
        '/team/'=>'/about/',
        '/category/uncategorized/'=>'/blog/',
        // Existing WordPress discovery endpoints continue to the new sitemap.
        '/sitemap.rss'=>'/sitemap.xml',
        '/post-sitemap.xml'=>'/sitemap.xml',
        '/page-sitemap.xml'=>'/sitemap.xml',
        '/category-sitemap.xml'=>'/sitemap.xml',
        '/wp-sitemap.xml'=>'/sitemap.xml',
        '/sitemap_index.xml'=>'/sitemap.xml',
    ];
}

/** Only byte-verified legacy logo URLs; other legacy media keep their original files. */
function sf_discovery_media_aliases(): array
{
    static $aliases=null;
    return $aliases??=json_decode((string)file_get_contents(__DIR__.'/legacy-media.json'),true,32,JSON_THROW_ON_ERROR);
}

function sf_discovery_slug(mixed $slug): ?string
{
    return is_string($slug) && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug) ? $slug : null;
}

/** Use stored content dates only. Undated pages deliberately have no lastmod. */
function sf_discovery_lastmod(array $item): ?string
{
    foreach (['updated_at','published_at'] as $field) {
        $date=$item[$field]??null;
        if (!is_string($date) || !preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})\z/D', $date)) continue;
        try {
            $parsed=new DateTimeImmutable($date);
            $errors=DateTimeImmutable::getLastErrors();
            if ($errors!==false && ($errors['warning_count']!==0 || $errors['error_count']!==0)) continue;
            return $parsed->format(DateTimeInterface::ATOM);
        } catch (Exception) { }
    }
    return null;
}

/**
 * Canonical path => public presentation data. Eligibility controls search inclusion,
 * not whether a useful empty directory remains accessible to visitors/admin editors.
 */
function sf_discovery_entries(bool $includePreview=false): array
{
    require_once __DIR__.'/collections.php';
    $home=sf_home_content();
    $posts=sf_posts(true);
    $projects=sf_collection('projects',true);
    $documents=sf_documents_list(true);
    $documentCopy=sf_documents_settings();
    $references=sf_reference_data()['settings'];
    $visibleLogos=array_filter(sf_reference_items('logos'),static fn(array $item):bool=>sf_cover_url((string)($item['image']??''))!=='');
    $entry=static fn(string $kind,string $title,string $description,array $item=[],bool $eligible=true):array=>[
        'kind'=>$kind,'title'=>$title,'description'=>$description,
        'image'=>(string)($item['cover']??''),'item'=>$item,'eligible'=>$eligible,
    ];
    $entries=[
        '/'=>$entry('home',(string)$home['meta']['title'],(string)$home['meta']['description'],$home),
        '/blog/'=>$entry('blog','Marine Operations Blog','Practical notes on vessel maintenance, underwater work and technical supply.',[],count($posts)>0),
        '/projects/'=>$entry('projects','Projects','Project information and work scopes from Seafort’s marine and offshore activities.',[],count($projects)>0),
        '/documents/'=>$entry('documents',$documentCopy['title'],$documentCopy['intro'],[],count($documents)>0),
        '/certificates/'=>$entry('certificates',$documentCopy['certificate_title'],$documentCopy['certificate_intro'],[],count(array_filter($documents,static fn(array $item):bool=>in_array($item['category']??'',['certificate','wps'],true)))>0),
        '/references/'=>$entry('references',$references['page_title'],$references['page_intro'],[],count($visibleLogos)+count(sf_reference_items('quotes'))>0),
    ];
    foreach (sf_site_pages()['pages'] as $page) {
        $slug=sf_discovery_slug($page['slug']??null);
        if ($slug===null) continue;
        $path='/'.$slug.'/';
        // The dedicated references view uses its own approved records and copy.
        if (isset($entries[$path]) || isset(sf_discovery_aliases()[$path])) continue;
        $entries[$path]=$entry('page',(string)($page['title']??''),(string)($page['intro']??''),$page);
    }
    $custom=sf_reference_custom_page('ege-derin',$includePreview);
    if ($custom!==null) $entries['/ege-derin/']=$entry('custom',(string)$custom['title'],(string)$custom['intro'],$custom,empty($custom['preview_placeholder']));
    foreach ($posts as $post) {
        $slug=sf_discovery_slug($post['slug']??null);
        if ($slug===null) continue;
        $path='/blog/'.$slug.'/';
        $entries[$path]=$entry('post',(string)($post['title']??''),(string)($post['excerpt']??''),$post);
        if (($date=sf_discovery_lastmod($post))!==null) $entries[$path]['lastmod']=$date;
    }
    foreach ($projects as $project) {
        $slug=sf_discovery_slug($project['slug']??null);
        if ($slug===null) continue;
        $path='/projects/'.$slug.'/';
        $entries[$path]=$entry('project',(string)($project['title']??''),(string)($project['summary']??''),$project);
        if (($date=sf_discovery_lastmod($project))!==null) $entries[$path]['lastmod']=$date;
    }
    return $entries;
}

/** Normalize only to a candidate path; the router redirects it only if it is known. */
function sf_discovery_path(string $rawPath): ?string
{
    if (preg_match('/%(?![a-f0-9]{2})/i',$rawPath)) return null;
    $decoded=rawurldecode($rawPath);
    if (preg_match('/[\x00-\x20\x7f\\\\?#]/',$decoded) || preg_match('~(?:^|/)\.{1,2}(?:/|$)~',$decoded)) return null;
    $path='/'.trim(strtolower((string)preg_replace('~/+~','/',$decoded)),'/');
    if ($path==='/') return '/';
    return preg_match('~\.[a-z0-9]{1,8}$~D',$path) ? $path : $path.'/';
}

/** Match the blog's real pagination; filters keep the clean archive canonical. */
function sf_discovery_canonical_path(string $route): string
{
    if ($route!=='/blog/' || sf_discovery_filtered_request($route)) return $route;
    $requested=$_GET['page']??'1';
    $page=is_string($requested) && preg_match('/\A[0-9]+\z/D',$requested)?max(1,(int)$requested):1;
    $last=max(1,(int)ceil(count(sf_posts(true))/6));
    $page=min($page,$last);
    return $page>1?'/blog/?page='.$page:$route;
}

function sf_discovery_filtered_request(string $route): bool
{
    $keys=match($route) {
        '/blog/'=>['q','category'],
        '/projects/'=>['category'],
        '/documents/'=>['category','language'],
        default=>[],
    };
    foreach ($keys as $key) if (is_string($_GET[$key]??null) && trim($_GET[$key])!=='') return true;
    return false;
}

/** Preserve form methods and all functional query parameters across a path redirect. */
function sf_discovery_redirect(string $path): never
{
    $query=(string)($_SERVER['QUERY_STRING']??'');
    if (preg_match('/[\x00-\x20\x7f]/',$query)) $query='';
    header('Location: '.$path.($query!==''?'?'.$query:''),true,in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)?301:308);
    exit;
}

function sf_discovery_robots(array $config): string
{
    if (($config['indexable']??false)!==true) return "User-agent: *\nDisallow: /\n";
    $base=rtrim((string)$config['base_url'],'/');
    // Wildcard access includes Google/Bing and AI search crawlers such as OAI-SearchBot.
    // Search access is independent of model-training preferences; no new training rule is set.
    return "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /seafort/\nDisallow: /tools/\nDisallow: /translations/\nDisallow: /brochure/\n\nSitemap: ".$base."/sitemap.xml\n";
}
