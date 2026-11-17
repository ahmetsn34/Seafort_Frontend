<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/upgrades.php';
$result = sf_with_content_upgrade_lock('sf_upgrade_design_content');
echo "Design update complete. Original reference logos added: {$result['reference_logos']}. Existing owner edits preserved.\n";
