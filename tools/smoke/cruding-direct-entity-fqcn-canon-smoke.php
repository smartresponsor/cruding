<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

if (!interface_exists('Doctrine\Persistence\ManagerRegistry')) {
    eval(<<<'PHPSTUB'
namespace Doctrine\Persistence;
interface ManagerRegistry { public function getManagers(): array; }
interface ObjectManager { public function getMetadataFactory(): object; }
PHPSTUB);
}

require_once $root.'/src/Parser/Crud/CrudResourcePathParser.php';
require_once $root.'/src/Resolver/Crud/CrudEntityClassResolver.php';

use App\Cruding\Resolver\Crud\CrudEntityClassResolver;
use App\Cruding\Parser\Crud\CrudResourcePathParser;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

final class SmokeMetadata
{
    public function __construct(private string $nameEntity) {}
    public function getName(): string { return $this->nameEntity; }
}

final class SmokeMetadataFactory
{
    /** @param list<string> $classes */
    public function __construct(private array $classes) {}

    /** @return list<SmokeMetadata> */
    public function getAllMetadata(): array
    {
        return array_map(static fn (string $class): SmokeMetadata => new SmokeMetadata($class), $this->classes);
    }
}

final class SmokeObjectManager implements ObjectManager
{
    /** @param list<string> $classes */
    public function __construct(private array $classes) {}
    public function getMetadataFactory(): object { return new SmokeMetadataFactory($this->classes); }
}

final class SmokeRegistry implements ManagerRegistry
{
    /** @param list<string> $classes */
    public function __construct(private array $classes) {}
    public function getManagers(): array { return [new SmokeObjectManager($this->classes)]; }
}

$classes = [
    'App\\Vendoring\\Entity\\Vendor\\VendorEntity',
    'App\\Vendoring\\Entity\\Vendor\\VendorAttachmentEntity',
    'App\\Vendoring\\Entity\\Vendor\\VendorAttachmentDocumentEntity',
    'App\\Vendoring\\Entity\\Vendor\\Catalog\\VendorCatalogAttachmentMediaEntity',
    'App\\Attaching\\Entity\\Attachment\\AttachmentEntity',
];

$resolver = new CrudEntityClassResolver(new SmokeRegistry($classes), new CrudResourcePathParser());

assert('VendorEntity' === $resolver->canonicalEntityShortName('vendor'));
assert('VendorAttachmentEntity' === $resolver->canonicalEntityShortName('vendor/attachment'));
assert('VendorAttachmentDocumentEntity' === $resolver->canonicalEntityShortName('vendor/attachment/document'));
assert('VendorCatalogAttachmentMediaEntity' === $resolver->canonicalEntityShortName('vendor/catalog/attachment/media'));

assert($classes[0] === $resolver->resolve('vendor'));
assert($classes[1] === $resolver->resolve('vendor/attachment'));
assert($classes[2] === $resolver->resolve('vendor/attachment/document'));
assert($classes[3] === $resolver->resolve('vendor/catalog/attachment/media'));
assert($classes[4] === $resolver->resolve('attachment'));
assert(null === $resolver->tryResolve('vendor/catalog/attachment'));

$source = file_get_contents($root.'/src/Resolver/Crud/CrudEntityClassResolver.php');
assert(is_string($source));
assert(!str_contains($source, '->tail('), 'Entity resolver must not fallback from vendor/attachment to bare attachment.');
assert(!str_contains($source, 'normalizeEntityAlias'), 'Entity resolver must not use basename alias as semantic fallback.');
assert(str_contains($source, 'canonicalEntityShortName'), 'Entity resolver must expose canonical short-nameEntity builder.');

fwrite(STDOUT, "PASS: Entity FQCN canon is direct all-business-token resolution without tail fallback.\n");
