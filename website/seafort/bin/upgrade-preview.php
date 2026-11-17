<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/upgrades.php';
$result = sf_with_content_upgrade_lock('sf_upgrade_preview_content');
echo "Form migration complete: {$result['retired_directory_references']} retired directory references updated. Other content preserved.\n";
echo "Project draft migration complete: {$result['project_drafts']} draft added. Existing projects preserved.\n";
