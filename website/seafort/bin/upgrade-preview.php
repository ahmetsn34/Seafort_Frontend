<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';

// Replace only the two retired-directory sentences. Preserve other owner edits.
$replacements = [
    'Find Human Resources in our team directory for recruitment enquiries. For a general question, use the contact address shown on this page.' => 'Use the application form on this page for recruitment enquiries. Include your discipline, experience and availability.',
    'Send your enquiry to the contact address shown on this page, or find the appropriate department in our team directory.' => 'Use the project enquiry form on this page. Our team will review the scope and contact you using the details you provide.',
];
$changed = sf_update_json('pages.json', static function (array &$data) use ($replacements): int {
    $count=0;
    foreach ($data['pages'] ?? [] as $index=>$page) {
        if (!in_array($page['slug'] ?? '', ['careers','get-a-quote'], true)) continue;
        foreach ($page['sections'] ?? [] as $sectionIndex=>$section) {
            $body=$section['body'] ?? '';
            if (isset($replacements[$body])) {
                $data['pages'][$index]['sections'][$sectionIndex]['body']=$replacements[$body];
                $count++;
            }
        }
    }
    return $count;
}, sf_content_defaults('pages'));
echo "Form migration complete: $changed retired directory references updated. Other content preserved.\n";
$seed=json_decode((string)file_get_contents(SF_ROOT.'/seed/projects.json'),true,64,JSON_THROW_ON_ERROR);
$added=sf_update_json('projects.json',static function(array &$data)use($seed):int{
    $data['items']??=[];$added=0;
    foreach($seed['items']??[] as $candidate){
        if(($candidate['status']??'')!=='draft')continue;
        $exists=false;
        foreach($data['items'] as $existing){
            if(($existing['id']??'')===($candidate['id']??'')||($existing['slug']??'')===($candidate['slug']??'')){$exists=true;break;}
        }
        if(!$exists){$data['items'][]=$candidate;$added++;}
    }
    return $added;
},['version'=>1,'items'=>[]]);
echo "Project draft migration complete: $added draft added. Existing projects preserved.\n";
