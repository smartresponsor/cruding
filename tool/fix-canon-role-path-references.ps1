$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

$badConfiguration = Join-Path $root 'src/DependencyInjection/CrudCrudConfiguration.php'
$goodConfiguration = Join-Path $root 'src/DependencyInjection/CrudConfiguration.php'
if (Test-Path -LiteralPath $badConfiguration) {
    if (Test-Path -LiteralPath $goodConfiguration) { throw "Configuration target already exists: $goodConfiguration" }
    Move-Item -LiteralPath $badConfiguration -Destination $goodConfiguration
}

$paths = [ordered]@{
    'src/Service/Operation/Create/CrudCreateViewBuilder.php' = 'src/Builder/Operation/Create/CrudCreateViewBuilder.php'
    'src/Service/Resource/CrudInterfacingProviderResourceBuilder.php' = 'src/Builder/Resource/CrudInterfacingProviderResourceBuilder.php'
    'src/Service/Resource/CrudResourceActionBuilder.php' = 'src/Builder/Resource/CrudResourceActionBuilder.php'
    'src/Service/Resource/CrudResourceColumnBuilder.php' = 'src/Builder/Resource/CrudResourceColumnBuilder.php'
    'src/Service/Resource/CrudResourceFilterBuilder.php' = 'src/Builder/Resource/CrudResourceFilterBuilder.php'
    'src/Service/Resource/CrudResourceFormFieldBuilder.php' = 'src/Builder/Resource/CrudResourceFormFieldBuilder.php'
    'src/Service/Resource/CrudResourceLocationBuilder.php' = 'src/Builder/Resource/CrudResourceLocationBuilder.php'
    'src/Service/Resource/CrudResourcePayloadBuilder.php' = 'src/Builder/Resource/CrudResourcePayloadBuilder.php'
    'src/Service/Resource/CrudResourceRowBuilder.php' = 'src/Builder/Resource/CrudResourceRowBuilder.php'
    'src/Service/Resource/CrudResourceWorkbenchBuilder.php' = 'src/Builder/Resource/CrudResourceWorkbenchBuilder.php'
    'src/Service/Runtime/CrudRuntimeRouteGuardPolicyBuilder.php' = 'src/Builder/Runtime/CrudRuntimeRouteGuardPolicyBuilder.php'
    'src/Service/Api/CrudApiProblemResponseFactory.php' = 'src/Factory/Api/CrudApiProblemResponseFactory.php'
    'src/Service/Resource/CrudResourceContractFactory.php' = 'src/Factory/Resource/CrudResourceContractFactory.php'
    'src/Service/CrudRouteTokenNormalizer.php' = 'src/Normalizer/CrudRouteTokenNormalizer.php'
    'src/Service/Resource/CrudRouteValueNormalizer.php' = 'src/Normalizer/Resource/CrudRouteValueNormalizer.php'
    'src/Service/Runtime/CrudRuntimeTokenNormalizer.php' = 'src/Normalizer/Runtime/CrudRuntimeTokenNormalizer.php'
    'src/Service/CrudReservedRouteTokenPolicy.php' = 'src/Policy/CrudReservedRouteTokenPolicy.php'
    'src/Service/CrudTokenizedRouteIntentResolver.php' = 'src/Resolver/CrudTokenizedRouteIntentResolver.php'
    'src/Service/Operation/Create/CrudCreateRedirectResolver.php' = 'src/Resolver/Operation/Create/CrudCreateRedirectResolver.php'
    'src/Service/Resource/CrudResourceOperationResolver.php' = 'src/Resolver/Resource/CrudResourceOperationResolver.php'
    'src/Service/Resource/CrudResourceServiceClassResolver.php' = 'src/Resolver/Resource/CrudResourceServiceClassResolver.php'
    'src/Service/Resource/CrudResourceServiceResolver.php' = 'src/Resolver/Resource/CrudResourceServiceResolver.php'
    'src/Service/Resource/CrudRouteGrammarResolver.php' = 'src/Resolver/Resource/CrudRouteGrammarResolver.php'
    'src/Service/Resource/CrudRouteProviderKeyResolver.php' = 'src/Resolver/Resource/CrudRouteProviderKeyResolver.php'
    'src/Service/Resource/CrudRouteShapeResolver.php' = 'src/Resolver/Resource/CrudRouteShapeResolver.php'
    'src/Service/Resource/CrudRouteTemplateCandidateResolver.php' = 'src/Resolver/Resource/CrudRouteTemplateCandidateResolver.php'
    'src/Service/Resource/CrudRouteTemplateResolver.php' = 'src/Resolver/Resource/CrudRouteTemplateResolver.php'
    'src/Service/Resource/CrudRouteViewResolver.php' = 'src/Resolver/Resource/CrudRouteViewResolver.php'
    'src/Service/CrudApiResponder.php' = 'src/Responder/CrudApiResponder.php'
    'src/Dispatcher/CrudServiceDispatcher.php' = 'src/Service/Dispatch/CrudServiceDispatcher.php'
    'src/Routing/CrudingRouteManifest.php' = 'src/Service/Routing/CrudRouteManifest.php'
    'src/Value/Api/CrudApiResponseTitle.php' = 'src/ValueObject/Api/CrudApiResponseTitle.php'
    'src/Value/Resource/CrudResourceContract.php' = 'src/ValueObject/Resource/CrudResourceContract.php'
}

$extensions = @('.php','.yaml','.yml','.json','.md','.adoc')
Get-ChildItem -LiteralPath $root -Recurse -File | Where-Object {
    $relative = $_.FullName.Substring($root.Length).TrimStart('\','/')
    $segments = $relative -split '[\/]'
    $_.Extension -in $extensions -and -not ($segments | Where-Object { $_ -in @('.git','vendor','var','node_modules','.gating') })
} | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName)
    $updated = $content
    foreach ($entry in $paths.GetEnumerator()) { $updated = $updated.Replace($entry.Key, $entry.Value) }
    if ($updated -cne $content) { [System.IO.File]::WriteAllText($_.FullName, $updated, [System.Text.UTF8Encoding]::new($false)) }
}

Write-Host 'Canonical role path references normalized.'
