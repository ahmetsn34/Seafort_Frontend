<?php
declare(strict_types=1);
function sf_translation_languages(): array { return ['tr'=>'Türkçe','ar'=>'العربية','ru'=>'Русский','zh-CN'=>'简体中文']; }
function sf_translation_base(string $language): array {
    if(!isset(sf_translation_languages()[$language])) throw new InvalidArgumentException('Dil bulunamadı.');
    $data=json_decode((string)file_get_contents(SF_PUBLIC_DIR.'/assets/locales/'.$language.'.json'),true,64,JSON_THROW_ON_ERROR);
    return is_array($data)?$data:[];
}
function sf_translation_file(string $language): string {
    if(!isset(sf_translation_languages()[$language])) throw new InvalidArgumentException('Dil bulunamadı.');
    return 'translations-'.strtolower($language).'.json';
}
function sf_translation_read(string $language): array {
    $base=sf_translation_base($language);
    foreach(sf_read_json(sf_translation_file($language),['entries'=>[]])['entries']??[] as $key=>$value) {
        if(is_string($key)&&is_string($value)&&$value!=='')$base[$key]=$value;
    }
    return $base;
}
function sf_translation_save(string $language,array $entries,string $revision): void {
    $base=sf_translation_base($language);
    $clean=[];
    if(count($entries)>60)throw new InvalidArgumentException('Bir seferde en fazla 60 çeviri kaydedilebilir.');
    foreach($entries as $entry){
        if(!is_array($entry)||!is_string($entry['key']??null)||!is_string($entry['value']??null))throw new InvalidArgumentException('Çeviri alanı geçersiz.');
        $key=trim($entry['key']);$value=trim($entry['value']);
        if($key===''&&$value==='')continue;
        if($key===''||sf_strlen($key)>12000||sf_strlen($value)>12000||!preg_match('//u',$key.$value)||str_contains($key.$value,"\0"))throw new InvalidArgumentException('Çeviri metnini kontrol edin.');
        $clean[$key]=$value;
    }
    sf_update_json(sf_translation_file($language),static function(array &$data)use($base,$clean,$revision):void{
        $current=$base;
        foreach($data['entries']??[] as $key=>$value)if(is_string($value)&&$value!=='')$current[$key]=$value;
        if(!hash_equals(sf_content_digest($current),$revision))throw new InvalidArgumentException('Çeviriler başka bir sekmede değişti. Sayfayı yenileyin.');
        $data['entries']??=[];
        foreach($clean as $key=>$value){if($value==='')unset($data['entries'][$key]);else $data['entries'][$key]=$value;}
        $data['updated_at']=sf_now();
    },['entries'=>[]]);
}
