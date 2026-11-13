<?php
declare(strict_types=1);

/** Projects, employee profiles and hero slides share the existing atomic JSON store. */
function sf_collection_file(string $type): string
{
    if (!in_array($type, ['team', 'projects', 'slides'], true)) {
        throw new InvalidArgumentException('İçerik bölümü tanınmadı.');
    }
    return $type . '.json';
}

function sf_collection(string $type, bool $publishedOnly = true): array
{
    $data = sf_read_json(sf_collection_file($type), ['version' => 1, 'items' => []]);
    $items = array_values(array_filter($data['items'] ?? [], static fn(mixed $item): bool => is_array($item) && (!$publishedOnly || ($item['status'] ?? '') === 'published')));
    usort($items, static fn(array $a, array $b): int => ((int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0)) ?: strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? '')));
    return $items;
}

function sf_collection_item(string $type, string $slugOrId, bool $publishedOnly = true): ?array
{
    foreach (sf_collection($type, $publishedOnly) as $item) {
        if (($item['id'] ?? '') === $slugOrId || ($item['slug'] ?? '') === $slugOrId) return $item;
    }
    return null;
}

function sf_collection_link(string $href): string
{
    $href = trim($href);
    if ($href === '') return '';
    if (!preg_match('~\A/(?!/)[a-zA-Z0-9/_-]*(?:\#[a-zA-Z][a-zA-Z0-9_-]*)?\z~D', $href)) {
        throw new InvalidArgumentException('Bağlantı yalnızca sitedeki bir sayfaya gitmelidir; örneğin /projects/.');
    }
    $path = explode('#', $href, 2)[0];
    $known = ['/', '/about/', '/values/', '/manpower-team/', '/marmaris/', '/references/', '/careers/', '/get-a-quote/', '/maintenance-repair/', '/marine-services-2/', '/ship-supply/', '/marine-services/', '/services/', '/blog/', '/projects/', '/sustainability/', '/ship-construction/', '/rigging-oilfield/', '/shipyard-services/'];
    if (!in_array($path, $known, true) && !preg_match('~\A/projects/[a-z0-9]+(?:-[a-z0-9]+)*/\z~D', $path)) {
        throw new InvalidArgumentException('Sitedeki geçerli sayfalardan birinin adresini kullanın.');
    }
    return $href;
}

function sf_collection_validate(string $type, array $input): array
{
    sf_collection_file($type);
    $keys = match ($type) {
        'team' => ['name', 'role', 'department', 'email', 'phone', 'photo'],
        'projects' => ['slug', 'title', 'summary', 'body', 'category', 'location', 'year', 'cover', 'cover_alt'],
        'slides' => ['title', 'description', 'eyebrow', 'cover', 'cover_alt', 'link_label', 'link_href'],
    };
    $fields = [];
    foreach (array_merge($keys, ['status']) as $key) {
        $value = $input[$key] ?? ($key === 'status' ? 'draft' : '');
        if (!is_string($value) || str_contains($value, "\0") || !preg_match('//u', $value)) throw new InvalidArgumentException('Geçersiz metin biçimi.');
        $fields[$key] = trim(str_replace(["\r\n", "\r"], "\n", $value));
    }
    if (!in_array($fields['status'], ['draft', 'published'], true)) throw new InvalidArgumentException('Geçersiz yayın durumu.');
    $order = $input['order'] ?? '0';
    if ((!is_string($order) && !is_int($order)) || !preg_match('/\A[0-9]{1,3}\z/D', (string) $order)) throw new InvalidArgumentException('Sıralama 0–999 arasında bir tam sayı olmalıdır.');
    $fields['order'] = (int) $order;
    $published = $fields['status'] === 'published';
    $lengths = match ($type) {
        'team' => ['name' => 120, 'role' => 120, 'department' => 120, 'email' => 180, 'phone' => 40],
        'projects' => ['title' => 180, 'summary' => 500, 'body' => 100000, 'category' => 60, 'location' => 150, 'cover_alt' => 240],
        'slides' => ['title' => 110, 'description' => 320, 'eyebrow' => 70, 'cover_alt' => 240, 'link_label' => 60],
    };
    foreach ($lengths as $key => $limit) {
        if (sf_strlen($fields[$key]) > $limit) throw new InvalidArgumentException('Alan sınırı aşıldı: ' . $key . ' (en fazla ' . $limit . ' karakter).');
    }
    $titleField = $type === 'team' ? 'name' : 'title';
    if (sf_strlen($fields[$titleField]) < 2) throw new InvalidArgumentException($type === 'team' ? 'Çalışanın adını ve soyadını girin.' : 'En az 2 karakterlik bir başlık girin.');
    $imageField = $type === 'team' ? 'photo' : 'cover';
    $fields[$imageField] = sf_cover_path($fields[$imageField]);
    if ($type !== 'team' && $published && $fields[$imageField] === '') throw new InvalidArgumentException('Yayımlamadan önce bir görsel ekleyin.');
    if ($type === 'team') {
        $digits = preg_replace('/\D/', '', $fields['phone']);
        if ($fields['phone'] !== '' && (!preg_match('/\A\+?[0-9 ()-]+\z/D', $fields['phone']) || strlen($digits) < 7 || strlen($digits) > 15)) throw new InvalidArgumentException('Telefonu ülke koduyla, 7–15 rakam içerecek biçimde girin.');
        if ($fields['email'] !== '' && (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $fields['email']))) throw new InvalidArgumentException('Geçerli bir e-posta adresi girin veya alanı boş bırakın.');
        if ($published && $fields['phone'] === '' && $fields['email'] === '') throw new InvalidArgumentException('Yayımlamak için en az bir iletişim bilgisi (telefon veya e-posta) girin.');
    }
    if ($type === 'projects') {
        if (strlen($fields['slug']) > 150 || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $fields['slug'])) throw new InvalidArgumentException('Proje adresi küçük İngilizce harf, rakam ve kısa çizgi içermelidir.');
        if ($fields['year'] !== '' && !preg_match('/\A(?:19|20)[0-9]{2}\z/D', $fields['year'])) throw new InvalidArgumentException('Yılı dört rakamla yazın veya boş bırakın.');
        if ($published && (sf_strlen($fields['summary']) < 10 || sf_strlen($fields['body']) < 20)) throw new InvalidArgumentException('Yayımlamak için kısa açıklamayı (en az 10) ve proje metnini (en az 20 karakter) tamamlayın.');
    }
    if ($type !== 'team' && $published && sf_strlen($fields['cover_alt']) < 3) throw new InvalidArgumentException('Görseli açıklayan en az 3 karakterlik bir alternatif metin girin.');
    if ($type === 'slides') {
        $fields['link_href'] = sf_collection_link($fields['link_href']);
        if (($fields['link_label'] === '') !== ($fields['link_href'] === '')) throw new InvalidArgumentException('Düğme metni ile bağlantı adresini birlikte doldurun veya ikisini de boş bırakın.');
    }
    return $fields;
}

function sf_collection_save(string $type, array $input, ?string $id = null, ?string $expectedUpdatedAt = null): string
{
    $fields = sf_collection_validate($type, $input);
    return sf_update_json(sf_collection_file($type), static function (array &$data) use ($type, $fields, $id, $expectedUpdatedAt): string {
        $data['version'] = 1; $data['items'] ??= []; $found = null;
        foreach ($data['items'] as $index => $item) {
            if ($type === 'projects' && ($item['slug'] ?? '') === $fields['slug'] && ($item['id'] ?? '') !== $id) throw new InvalidArgumentException('Bu proje adresi başka bir kayıtta kullanılıyor.');
            if (($item['id'] ?? '') === $id) $found = $index;
        }
        if ($id !== null && $found === null) throw new InvalidArgumentException('Düzenlenecek kayıt bulunamadı.');
        if ($found !== null) {
            if (($data['items'][$found]['status'] ?? '') === 'archived') throw new InvalidArgumentException('Arşivdeki kaydı önce taslak olarak geri alın.');
            if ($expectedUpdatedAt === null || !hash_equals((string) ($data['items'][$found]['updated_at'] ?? ''), $expectedUpdatedAt)) throw new InvalidArgumentException('Bu kayıt başka bir sekmede değiştirildi. Değişiklikleriniz kaydedilmedi. Metninizi kopyalayın, sayfayı yenileyin ve güncel kayıt üzerinde düzenleyin.');
        }
        $now = sf_now();
        $item = $found !== null ? $data['items'][$found] : ['id' => bin2hex(random_bytes(12)), 'created_at' => $now, 'published_at' => null];
        $item = array_merge($item, $fields, ['updated_at' => $now]);
        if ($item['status'] === 'published' && empty($item['published_at'])) $item['published_at'] = $now;
        if ($found === null) $data['items'][] = $item; else $data['items'][$found] = $item;
        return (string) $item['id'];
    }, ['version' => 1, 'items' => []]);
}

function sf_collection_status(string $type, string $id, string $action): void
{
    sf_update_json(sf_collection_file($type), static function (array &$data) use ($id, $action): void {
        foreach ($data['items'] as &$item) {
            if (($item['id'] ?? '') !== $id) continue;
            if ($action === 'archive') $item['status'] = 'archived';
            elseif ($action === 'restore' && ($item['status'] ?? '') === 'archived') $item['status'] = 'draft';
            elseif ($action === 'unpublish' && ($item['status'] ?? '') !== 'archived') $item['status'] = 'draft';
            else throw new InvalidArgumentException('İşlem bu kaydın durumuna uygun değil.');
            $item['updated_at'] = sf_now(); return;
        }
        throw new InvalidArgumentException('Kayıt bulunamadı.');
    }, ['version' => 1, 'items' => []]);
}
