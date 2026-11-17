<?php
declare(strict_types=1);

/** Fixed, release-specific content upgrades. This private file is not an HTTP entry point. */
function sf_with_content_upgrade_lock(callable $upgrade): mixed
{
    sf_storage_ready();
    $lock = fopen(SF_STORAGE_DIR . '/content-upgrade.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Content upgrade lock could not be acquired.');
    }
    @chmod(SF_STORAGE_DIR . '/content-upgrade.lock', 0600);
    try { return $upgrade(); }
    finally { flock($lock, LOCK_UN); fclose($lock); }
}

function sf_upgrade_preview_content(): array
{
    // Replace only the retired-directory sentences. Preserve all other owner edits.
    $replacements = [
        'Find Human Resources in our team directory for recruitment enquiries. For a general question, use the contact address shown on this page.' => 'Use the application form on this page for recruitment enquiries. Include your discipline, experience and availability.',
        'Send your enquiry to the contact address shown on this page, or find the appropriate department in our team directory.' => 'Use the project enquiry form on this page. Our team will review the scope and contact you using the details you provide.',
    ];
    $changed = sf_update_json('pages.json', static function (array &$data) use ($replacements): int {
        $count = 0;
        foreach ($data['pages'] ?? [] as $index => $page) {
            if (!in_array($page['slug'] ?? '', ['careers', 'get-a-quote'], true)) continue;
            foreach ($page['sections'] ?? [] as $sectionIndex => $section) {
                $body = $section['body'] ?? '';
                if (isset($replacements[$body])) {
                    $data['pages'][$index]['sections'][$sectionIndex]['body'] = $replacements[$body];
                    $count++;
                }
            }
        }
        return $count;
    }, sf_content_defaults('pages'));
    $seed = json_decode((string) file_get_contents(SF_ROOT . '/seed/projects.json'), true, 64, JSON_THROW_ON_ERROR);
    $added = sf_update_json('projects.json', static function (array &$data) use ($seed): int {
        $data['items'] ??= [];
        $added = 0;
        foreach ($seed['items'] ?? [] as $candidate) {
            if (($candidate['status'] ?? '') !== 'draft') continue;
            $exists = false;
            foreach ($data['items'] as $existing) {
                if (($existing['id'] ?? '') === ($candidate['id'] ?? '') || ($existing['slug'] ?? '') === ($candidate['slug'] ?? '')) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) { $data['items'][] = $candidate; $added++; }
        }
        return $added;
    }, ['version' => 1, 'items' => []]);
    return ['retired_directory_references' => $changed, 'project_drafts' => $added];
}

function sf_upgrade_design_content(): array
{
    $about = json_decode((string) file_get_contents(SF_ROOT . '/app/about-layout-defaults.json'), true, 64, JSON_THROW_ON_ERROR);
    $seed = json_decode((string) file_get_contents(SF_ROOT . '/seed/references.json'), true, 64, JSON_THROW_ON_ERROR);
    $state = sf_read_json('design-20261117.json');
    $aboutApplied = false;
    if (empty($state['about_applied'])) {
        $old = sf_read_json('pages.json', sf_content_defaults('pages'));
        foreach ($old['pages'] ?? [] as $oldPage) {
            if (($oldPage['slug'] ?? '') === 'about') {
                sf_update_json('about-before-20261117.json', static function (array &$data) use ($oldPage): void {
                    if (!$data) $data = $oldPage;
                });
            }
        }
        sf_update_json('pages.json', static function (array &$data) use ($about): void {
            foreach ($data['pages'] ?? [] as $i => $page) {
                if (($page['slug'] ?? '') === 'about') $data['pages'][$i] = $about;
            }
        }, sf_content_defaults('pages'));
        sf_update_json('design-20261117.json', static function (array &$data): void {
            $data['about_applied'] = true;
            $data['updated_at'] = sf_now();
        });
        $aboutApplied = true;
    }
    $added = sf_update_json('references.json', static function (array &$saved) use ($seed): int {
        $new = !$saved;
        $data = sf_reference_normalise($saved);
        $added = 0;
        foreach ($seed['logos'] as $item) {
            $exists = false;
            foreach ($data['logos'] as $old) {
                if (($old['id'] ?? '') === $item['id'] || ($old['image'] ?? '') === $item['image']) { $exists = true; break; }
            }
            if (!$exists) { $data['logos'][] = $item; $added++; }
        }
        if ($new) $data['settings'] = array_replace($data['settings'], $seed['settings']);
        $saved = $data;
        return $added;
    }, []);
    return ['about_applied' => $aboutApplied, 'reference_logos' => $added];
}

/** Back up only affected content, then apply the fixed release in a serialized operation. */
function sf_apply_preview_release_upgrade(): array
{
    return sf_with_content_upgrade_lock(static function (): array {
        sf_update_json('upgrade-before-20261117.json', static function (array &$backup): void {
            if ($backup) return;
            $backup = ['version' => 1, 'created_at' => sf_now(), 'files' => []];
            foreach (['pages.json', 'projects.json', 'references.json', 'design-20261117.json'] as $name) {
                $backup['files'][$name] = ['existed' => is_file(sf_storage_path($name)), 'content' => sf_read_json($name)];
            }
        });
        return sf_upgrade_preview_content() + sf_upgrade_design_content();
    });
}
