<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
$about=json_decode((string)file_get_contents(SF_ROOT.'/app/about-layout-defaults.json'),true,64,JSON_THROW_ON_ERROR);
$seed=json_decode((string)file_get_contents(SF_ROOT.'/seed/references.json'),true,64,JSON_THROW_ON_ERROR);
$state=sf_read_json('design-20261117.json');
if(empty($state['about_applied'])){
    $old=sf_read_json('pages.json',sf_content_defaults('pages'));
    foreach($old['pages']??[] as $oldPage)if(($oldPage['slug']??'')==='about')sf_update_json('about-before-20261117.json',static function(array &$data)use($oldPage):void{if(!$data)$data=$oldPage;});
    sf_update_json('pages.json',static function(array &$data)use($about):void{foreach($data['pages']??[] as $i=>$page)if(($page['slug']??'')==='about')$data['pages'][$i]=$about;},sf_content_defaults('pages'));
    sf_update_json('design-20261117.json',static function(array &$data):void{$data['about_applied']=true;$data['updated_at']=sf_now();});
}
$added=sf_update_json('references.json',static function(array &$saved)use($seed):int{
    $new=!$saved;$data=sf_reference_normalise($saved);$added=0;
    foreach($seed['logos'] as $item){
        $exists=false;foreach($data['logos'] as $old)if(($old['id']??'')===$item['id']||($old['image']??'')===$item['image']){$exists=true;break;}
        if(!$exists){$data['logos'][]=$item;$added++;}
    }
    if($new)$data['settings']=array_replace($data['settings'],$seed['settings']);
    $saved=$data;return $added;
},[]);
echo "Design update complete. Original reference logos added: $added. Existing owner edits preserved.\n";
