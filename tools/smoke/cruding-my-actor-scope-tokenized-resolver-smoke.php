<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

foreach ([
    'src/Dto/Crud/CrudContext.php',
    'src/Dto/Crud/CrudTokenizedRouteIntent.php',
    'src/Service/Crud/CrudRouteTokenNormalizer.php',
    'src/Service/Crud/CrudReservedRouteTokenPolicy.php',
    'src/Service/Crud/CrudTokenizedRouteIntentResolver.php',
    'src/Resolver/Crud/CrudActorScopeContextResolver.php',
    'src/Dto/Crud/Entrypoint/CrudServiceContext.php',
    'src/Resolver/Crud/CrudServiceClassNameResolver.php',
] as $file) {
    require_once $root.'/'.$file;
}

if (!class_exists('Symfony\\Component\\HttpFoundation\\Request')) {
    eval(<<<'PHP'
namespace Symfony\Component\HttpFoundation;
final class ParameterBag
{
    /** @param array<string, mixed> $parameters */
    public function __construct(private array $parameters = []) {}
    public function set(string $key, mixed $value): void { $this->parameters[$key] = $value; }
    public function get(string $key, mixed $default = null): mixed { return $this->parameters[$key] ?? $default; }
}
final class Request
{
    public ParameterBag $attributes;
    private function __construct(private string $pathInfo, private string $method) { $this->attributes = new ParameterBag(); }
    public static function create(string $uri, string $method = 'GET'): self { return new self($uri, strtoupper($method)); }
    public function getPathInfo(): string { return $this->pathInfo; }
    public function getMethod(): string { return $this->method; }
}
PHP);
}

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Crud\CrudReservedRouteTokenPolicy;
use App\Cruding\Service\Crud\CrudRouteTokenNormalizer;
use App\Cruding\Service\Crud\CrudTokenizedRouteIntentResolver;
use App\Cruding\Resolver\Crud\CrudServiceClassNameResolver;
use Symfony\Component\HttpFoundation\Request;

$operationTokens = operationTokens($root.'/config/cruding_reserved_token.yaml');
$resolver = new CrudTokenizedRouteIntentResolver(
    new CrudRouteTokenNormalizer(),
    new CrudReservedRouteTokenPolicy([], $operationTokens),
    'ea',
);

$cases = [
    '/my/vendor/index' => ['resourcePath' => 'vendor', 'operation' => 'index', 'actorScope' => 'my', 'identifierField' => null, 'identifierValue' => null],
    '/my/vendor/attachment/index' => ['resourcePath' => 'vendor/attachment', 'operation' => 'index', 'actorScope' => 'my', 'identifierField' => null, 'identifierValue' => null],
    '/api/my/vendor/index' => ['resourcePath' => 'vendor', 'operation' => 'index', 'actorScope' => 'my', 'identifierField' => null, 'identifierValue' => null, 'api' => true],
    '/my/api/vendor/attachment/index' => ['resourcePath' => 'vendor/attachment', 'operation' => 'index', 'actorScope' => 'my', 'identifierField' => null, 'identifierValue' => null, 'api' => true],
    '/api/my/order/attachment/read/123' => ['resourcePath' => 'order/attachment', 'operation' => 'read', 'actorScope' => 'my', 'identifierField' => 'id', 'identifierValue' => '123', 'api' => true],
    '/ea/my/api/vendor/attachment/show/acme-file' => ['resourcePath' => 'vendor/attachment', 'operation' => 'show', 'actorScope' => 'my', 'identifierField' => 'slug', 'identifierValue' => 'acme-file', 'api' => true],
];

foreach ($cases as $path => $expected) {
    $request = Request::create($path, 'GET');
    $request->attributes->set('crudPath', trim($path, '/'));
    $intent = ($expected['api'] ?? false) ? $resolver->resolveApi($request) : $resolver->resolveWeb($request);

    assert(null !== $intent, sprintf('%s must resolve to a tokenized intent.', $path));
    assert($expected['resourcePath'] === $intent->resourcePath, sprintf('%s resourcePath mismatch: %s', $path, $intent->resourcePath));
    assert($expected['operation'] === $intent->operation, sprintf('%s operation mismatch: %s', $path, $intent->operation));
    assert($expected['actorScope'] === $intent->actorScope, sprintf('%s actorScope mismatch.', $path));
    assert($intent->isMyScoped(), sprintf('%s must be my-scoped.', $path));
    assert($expected['identifierField'] === $intent->identifierField, sprintf('%s identifierField mismatch.', $path));
    assert($expected['identifierValue'] === $intent->identifierValue, sprintf('%s identifierValue mismatch.', $path));
}

foreach (['/my/vendor/attachment/document/index', '/api/my/vendor/attachment/document/show/acme-file', '/ea/api/my/vendor/attachment/document/index'] as $path) {
    $request = Request::create($path, 'GET');
    $request->attributes->set('crudPath', trim($path, '/'));
    assert(null === $resolver->resolveWeb($request), sprintf('%s must exceed semantic CRUD resource depth after context-prefix trimming.', $path));
}

$classResolver = new CrudServiceClassNameResolver();
$context = new CrudContext(
    view: 'public',
    operation: 'index',
    resourcePath: 'vendor/attachment',
    entityClass: '',
    identifierField: 'slug',
    identifierValue: null,
    formTypeClass: null,
);
$candidates = $classResolver->candidateClassNames($context);
assert(in_array('App\\Service\\Http\\Vendor\\Attachment\\VendorAttachmentIndexService', $candidates, true), 'My scope must reuse normal URI-derived entrypoint by default.');
foreach ($candidates as $candidate) {
    assert(!str_contains($candidate, 'VendorMyAttachmentIndexService'), 'My scope must not require a *My* FQCN candidate by default.');
}

$entrypointContext = readFileStrict($root.'/src/Dto/Crud/Entrypoint/CrudServiceContext.php');
foreach (['isActorScoped', 'actorScope', 'isMyScoped', 'isActorGrounded', 'actorUserId', 'actorUserSlug', 'actorIdentityField', 'actorAdminIdentityField'] as $method) {
    assert(str_contains($entrypointContext, 'function '.$method), sprintf('CrudServiceContext missing %s().', $method));
}

fwrite(STDOUT, "PASS: context prefixes are trimmed before CRUD grammar depth checks and my scope remains actor context.\n");

/**
 * @return list<string>
 */
function operationTokens(string $path): array
{
    $tokens = [];
    $insideOperation = false;
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $trimmed = trim($line);
        if (str_starts_with($trimmed, 'cruding.reserved_route_token.operation:')) {
            $insideOperation = true;
            continue;
        }

        if ($insideOperation && preg_match('/^cruding\.reserved_route_token\.[a-zA-Z0-9_.-]+:/', $trimmed)) {
            $insideOperation = false;
        }

        if ($insideOperation && preg_match('/^-\s*([a-zA-Z0-9_-]+)$/', $trimmed, $matches)) {
            $tokens[] = strtolower($matches[1]);
        }
    }

    return array_values(array_unique($tokens));
}

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
