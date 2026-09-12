$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$src = Join-Path $root 'src'

$moves = [ordered]@{
    'Service/Operation/Create/CrudCreateViewBuilder.php' = 'Builder/Operation/Create/CrudCreateViewBuilder.php'
    'Service/Resource/CrudInterfacingProviderResourceBuilder.php' = 'Builder/Resource/CrudInterfacingProviderResourceBuilder.php'
    'Service/Resource/CrudResourceActionBuilder.php' = 'Builder/Resource/CrudResourceActionBuilder.php'
    'Service/Resource/CrudResourceColumnBuilder.php' = 'Builder/Resource/CrudResourceColumnBuilder.php'
    'Service/Resource/CrudResourceFilterBuilder.php' = 'Builder/Resource/CrudResourceFilterBuilder.php'
    'Service/Resource/CrudResourceFormFieldBuilder.php' = 'Builder/Resource/CrudResourceFormFieldBuilder.php'
    'Service/Resource/CrudResourceLocationBuilder.php' = 'Builder/Resource/CrudResourceLocationBuilder.php'
    'Service/Resource/CrudResourcePayloadBuilder.php' = 'Builder/Resource/CrudResourcePayloadBuilder.php'
    'Service/Resource/CrudResourceRowBuilder.php' = 'Builder/Resource/CrudResourceRowBuilder.php'
    'Service/Resource/CrudResourceWorkbenchBuilder.php' = 'Builder/Resource/CrudResourceWorkbenchBuilder.php'
    'Service/Runtime/CrudRuntimeRouteGuardPolicyBuilder.php' = 'Builder/Runtime/CrudRuntimeRouteGuardPolicyBuilder.php'
    'Service/Api/CrudApiProblemResponseFactory.php' = 'Factory/Api/CrudApiProblemResponseFactory.php'
    'Service/Resource/CrudResourceContractFactory.php' = 'Factory/Resource/CrudResourceContractFactory.php'
    'Service/CrudRouteTokenNormalizer.php' = 'Normalizer/CrudRouteTokenNormalizer.php'
    'Service/Resource/CrudRouteValueNormalizer.php' = 'Normalizer/Resource/CrudRouteValueNormalizer.php'
    'Service/Runtime/CrudRuntimeTokenNormalizer.php' = 'Normalizer/Runtime/CrudRuntimeTokenNormalizer.php'
    'Service/CrudReservedRouteTokenPolicy.php' = 'Policy/CrudReservedRouteTokenPolicy.php'
    'Service/CrudTokenizedRouteIntentResolver.php' = 'Resolver/CrudTokenizedRouteIntentResolver.php'
    'Service/Operation/Create/CrudCreateRedirectResolver.php' = 'Resolver/Operation/Create/CrudCreateRedirectResolver.php'
    'Service/Resource/CrudResourceOperationResolver.php' = 'Resolver/Resource/CrudResourceOperationResolver.php'
    'Service/Resource/CrudResourceServiceClassResolver.php' = 'Resolver/Resource/CrudResourceServiceClassResolver.php'
    'Service/Resource/CrudResourceServiceResolver.php' = 'Resolver/Resource/CrudResourceServiceResolver.php'
    'Service/Resource/CrudRouteGrammarResolver.php' = 'Resolver/Resource/CrudRouteGrammarResolver.php'
    'Service/Resource/CrudRouteProviderKeyResolver.php' = 'Resolver/Resource/CrudRouteProviderKeyResolver.php'
    'Service/Resource/CrudRouteShapeResolver.php' = 'Resolver/Resource/CrudRouteShapeResolver.php'
    'Service/Resource/CrudRouteTemplateCandidateResolver.php' = 'Resolver/Resource/CrudRouteTemplateCandidateResolver.php'
    'Service/Resource/CrudRouteTemplateResolver.php' = 'Resolver/Resource/CrudRouteTemplateResolver.php'
    'Service/Resource/CrudRouteViewResolver.php' = 'Resolver/Resource/CrudRouteViewResolver.php'
    'Service/CrudApiResponder.php' = 'Responder/CrudApiResponder.php'
    'Dispatcher/CrudServiceDispatcher.php' = 'Service/Dispatch/CrudServiceDispatcher.php'
    'Routing/CrudRouteManifest.php' = 'Service/Routing/CrudRouteManifest.php'
    'Value/Api/CrudApiResponseTitle.php' = 'ValueObject/Api/CrudApiResponseTitle.php'
    'Value/Resource/CrudResourceContract.php' = 'ValueObject/Resource/CrudResourceContract.php'
    'DependencyInjection/CrudConfiguration.php' = 'DependencyInjection/CrudCrudConfiguration.php'
    'Provider/CrudDefaultOwnershipProvider.php' = 'Provider/CrudDefaultOwnershipProvider.php'
    'Service/CrudAbstractCreateService.php' = 'Service/CrudAbstractCreateService.php'
    'Service/CrudAbstractEditService.php' = 'Service/CrudAbstractEditService.php'
    'Service/CrudAbstractIndexService.php' = 'Service/CrudAbstractIndexService.php'
    'Service/CrudAbstractService.php' = 'Service/CrudAbstractService.php'
    'Service/CrudAbstractShowService.php' = 'Service/CrudAbstractShowService.php'
    'Service/CrudDefaultCreateOwnerBinder.php' = 'Service/CrudDefaultCreateOwnerBinder.php'
    'Service/CrudDefaultCreateService.php' = 'Service/CrudDefaultCreateService.php'
    'Service/CrudDefaultEditService.php' = 'Service/CrudDefaultEditService.php'
    'Service/CrudDefaultIndexService.php' = 'Service/CrudDefaultIndexService.php'
    'Service/CrudDefaultService.php' = 'Service/CrudDefaultService.php'
    'Service/CrudDefaultShowService.php' = 'Service/CrudDefaultShowService.php'
    'Service/CrudNullService.php' = 'Service/CrudNullService.php'
    'Service/CrudPassiveService.php' = 'Service/CrudPassiveService.php'
}

function Fqcn([string]$relative) {
    $withoutExtension = $relative.Substring(0, $relative.Length - 4)
    return 'App\Cruding\' + $withoutExtension.Replace('/', '\')
}

$replacements = [ordered]@{}
foreach ($entry in $moves.GetEnumerator()) {
    $replacements['src/' + $entry.Key] = 'src/' + $entry.Value
}

foreach ($entry in $moves.GetEnumerator()) {
    $oldPath = Join-Path $src $entry.Key
    $newPath = Join-Path $src $entry.Value
    if (-not (Test-Path -LiteralPath $oldPath)) { continue }
    if (Test-Path -LiteralPath $newPath) { throw "Target already exists: $newPath" }
    $newDir = Split-Path -Parent $newPath
    if (-not (Test-Path -LiteralPath $newDir)) { New-Item -ItemType Directory -Path $newDir -Force | Out-Null }
    $replacements[(Fqcn $entry.Key)] = Fqcn $entry.Value
    Move-Item -LiteralPath $oldPath -Destination $newPath
}

$replacements['CrudRouteManifest'] = 'CrudRouteManifest'
$replacements['CrudDefaultOwnershipProvider'] = 'CrudDefaultOwnershipProvider'
$replacements['CrudConfiguration'] = 'CrudCrudConfiguration'
$replacements['CrudAbstractCreateService'] = 'CrudAbstractCreateService'
$replacements['CrudAbstractEditService'] = 'CrudAbstractEditService'
$replacements['CrudAbstractIndexService'] = 'CrudAbstractIndexService'
$replacements['CrudAbstractService'] = 'CrudAbstractService'
$replacements['CrudAbstractShowService'] = 'CrudAbstractShowService'
$replacements['CrudDefaultCreateOwnerBinder'] = 'CrudDefaultCreateOwnerBinder'
$replacements['CrudDefaultCreateService'] = 'CrudDefaultCreateService'
$replacements['CrudDefaultEditService'] = 'CrudDefaultEditService'
$replacements['CrudDefaultIndexService'] = 'CrudDefaultIndexService'
$replacements['CrudDefaultService'] = 'CrudDefaultService'
$replacements['CrudDefaultShowService'] = 'CrudDefaultShowService'
$replacements['CrudNullService'] = 'CrudNullService'
$replacements['CrudPassiveService'] = 'CrudPassiveService'

$extensions = @('.php','.yaml','.yml','.json','.md','.adoc','.ps1')
Get-ChildItem -LiteralPath $root -Recurse -File | Where-Object {
    $relative = $_.FullName.Substring($root.Length).TrimStart('\','/')
    $segments = $relative -split '[\/]'
    $_.Extension -in $extensions -and -not ($segments | Where-Object { $_ -in @('.git','vendor','var','node_modules','.gating') })
} | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName)
    $updated = $content
    foreach ($entry in $replacements.GetEnumerator()) { $updated = $updated.Replace($entry.Key, $entry.Value) }
    if ($updated -cne $content) { [System.IO.File]::WriteAllText($_.FullName, $updated, [System.Text.UTF8Encoding]::new($false)) }
}

Write-Host ("Applied {0} canonical role/class moves." -f $moves.Count)
