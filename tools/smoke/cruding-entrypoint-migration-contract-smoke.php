<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$mapAudit = $root.'/tools/cruding/entrypoint-map-audit.php';
$skeletonPreview = $root.'/tools/cruding/entrypoint-skeleton-preview.php';
$doc = $root.'/docs/cruding/cruding-entrypoint-migration-contract.md';

foreach ([$mapAudit, $skeletonPreview, $doc] as $path) {
    assert(is_file($path), sprintf('Missing migration contract artifact: %s', $path));
}

foreach ([$mapAudit, $skeletonPreview] as $path) {
    $code = file_get_contents($path);
    assert(false !== $code, sprintf('Cannot read %s.', $path));
    assert(!str_contains($code, 'file_put_contents'), sprintf('%s must be read-only and must not write skeletons.', $path));
    assert(!str_contains($code, 'mkdir('), sprintf('%s must be read-only and must not create directories.', $path));
    assert(!str_contains($code, 'VendorCrudService'), sprintf('%s must not promote a mega-service contract.', $path));
}

$php = PHP_BINARY;
$command = escapeshellcmd($php).' '.escapeshellarg($mapAudit).' --path=/alpha/index --path=/alpha/attachment/media/edit/123';
$mapOutput = shell_exec($command);
assert(is_string($mapOutput), 'Map audit did not return output.');
assert(str_contains($mapOutput, 'writeAction: false'), 'Map audit must be explicitly read-only.');
assert(str_contains($mapOutput, 'AlphaIndexService'), 'Map audit must derive AlphaIndexService for /alpha/index.');
assert(str_contains($mapOutput, 'AlphaAttachmentMediaEditService'), 'Map audit must derive deep self-documenting edit entrypoint.');
assert(str_contains($mapOutput, 'serviceLayerPrefix #1:'), 'Map audit must expose the service-layer lookup prefix.');
assert(!str_contains($mapOutput, 'AlphaCrudService'), 'Map audit must not collapse resource operations into AlphaCrudService.');

$skeletonCommand = escapeshellcmd($php).' '.escapeshellarg($skeletonPreview).' --path=/alpha/attachment/media/archive/sample-entry --style=abstract';
$skeletonOutput = shell_exec($skeletonCommand);
assert(is_string($skeletonOutput), 'Skeleton preview did not return output.');
assert(str_contains($skeletonOutput, 'writeAction: false'), 'Skeleton preview must be explicitly read-only.');
assert(str_contains($skeletonOutput, 'namespace App\Service\Alpha\Attachment\Media;'), 'Skeleton preview must keep service-layer namespace.');
assert(str_contains($skeletonOutput, 'final class AlphaAttachmentMediaArchiveService extends AbstractCrudService'), 'Skeleton preview must generate a self-documenting abstract-based entrypoint.');
assert(!str_contains($skeletonOutput, 'AlphaCrudService'), 'Skeleton preview must not generate a resource mega-service.');

$getCommand = escapeshellcmd($php).' '.escapeshellarg($skeletonPreview).' --path=/alpha/index --style=get';
$getOutput = shell_exec($getCommand);
assert(is_string($getOutput), 'GET skeleton preview did not return output.');
assert(str_contains($getOutput, 'implements CrudGetServiceInterface'), 'GET skeleton preview must use optional method-specific interface.');
assert(str_contains($getOutput, 'public function get(CrudServiceContext $context): ?CrudServiceResult'), 'GET skeleton preview must include the optional get hook.');

fwrite(STDOUT, "PASS: Entrypoint migration contract tooling is read-only, URI-derived, self-documenting, and does not promote a mega-service.\n");
