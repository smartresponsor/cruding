<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Resource\CrudRouteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves a request into the canonical Cruding resource/view/action context.
 */
final readonly class CrudRouteShapeResolver
{
    private CrudRouteTemplateResolver $templateResolver;
    private CrudRouteSegmentReader $segmentReader;
    private CrudRouteGrammarResolver $grammarResolver;
    private CrudRouteViewResolver $viewResolver;
    private CrudRouteProviderKeyResolver $providerKeyResolver;
    private CrudRouteTemplateCandidateResolver $templateCandidateResolver;
    private CrudRouteParameterExtractor $parameterExtractor;
    private ?CrudRouteMapMatcher $routeMapMatcher;

    public function __construct(
        private RouterInterface $router,
        ?CrudRouteTemplateResolver $templateResolver = null,
        ?CrudRouteSegmentReader $segmentReader = null,
        ?CrudRouteGrammarResolver $grammarResolver = null,
        ?CrudRouteViewResolver $viewResolver = null,
        ?CrudRouteProviderKeyResolver $providerKeyResolver = null,
        ?CrudRouteTemplateCandidateResolver $templateCandidateResolver = null,
        ?CrudRouteParameterExtractor $parameterExtractor = null,
        ?CrudRouteMapMatcher $routeMapMatcher = null,
    ) {
        $normalizer = new CrudRouteValueNormalizer();

        $this->templateResolver = $templateResolver ?? new CrudRouteTemplateResolver($this->router);
        $this->segmentReader = $segmentReader ?? new CrudRouteSegmentReader($normalizer);
        $this->grammarResolver = $grammarResolver ?? new CrudRouteGrammarResolver($normalizer);
        $this->viewResolver = $viewResolver ?? new CrudRouteViewResolver();
        $this->providerKeyResolver = $providerKeyResolver ?? new CrudRouteProviderKeyResolver();
        $this->templateCandidateResolver = $templateCandidateResolver ?? new CrudRouteTemplateCandidateResolver();
        $this->parameterExtractor = $parameterExtractor ?? new CrudRouteParameterExtractor();
        $this->routeMapMatcher = $routeMapMatcher;
    }

    public function resolve(Request $request): ?CrudRouteContext
    {
        $routeName = $this->routeName($request);
        $routeTemplate = $this->templateResolver->routeTemplate($routeName);
        $segments = $this->segmentReader->segments($routeTemplate ?? $request->getPathInfo());

        if ([] === $segments) {
            return null;
        }

        $values = $this->segmentReader->segmentValues($segments, $request);
        $shape = $this->grammarResolver->resolve($values);
        if (null === $shape) {
            return null;
        }

        $providerKeys = $this->providerKeyResolver->providerKeys($shape->resource, $shape->viewPath, $shape->ViewToken, $shape->operation, $shape->subjectField, $shape->subjectValue);
        $templateCandidates = $this->templateCandidateResolver->templateCandidates($shape->resource, $shape->viewPath, $shape->ViewToken);
        $routeMapEntry = $this->routeMapMatcher?->match($request);
        if (null !== $routeMapEntry) {
            $providerKeys = array_values(array_unique(array_merge([$routeMapEntry->canonicalKey()], $providerKeys)));
            if (null !== $routeMapEntry->template && '' !== $routeMapEntry->template) {
                $templateCandidates = array_values(array_unique(array_merge([$routeMapEntry->template], $templateCandidates)));
            }
        }

        return new CrudRouteContext(
            resource: $shape->resource,
            resourcePath: $shape->resource,
            operation: $shape->operation,
            view: $this->viewResolver->viewFromOperation($shape->operation),
            viewPath: $shape->viewPath,
            ViewToken: $shape->ViewToken,
            subjectField: $shape->subjectField,
            subjectValue: $shape->subjectValue,
            itemField: $shape->itemField,
            itemValue: $shape->itemValue,
            routeName: $routeName,
            routeTemplate: $routeTemplate,
            routeParameters: $this->parameterExtractor->routeParameters($request),
            providerKeys: $providerKeys,
            templateCandidates: $templateCandidates,
            routeMapEntry: $routeMapEntry?->toArray(),
        );
    }

    private function routeName(Request $request): ?string
    {
        $routeName = $request->attributes->get('_route');

        return is_string($routeName) && '' !== $routeName ? $routeName : null;
    }
}
