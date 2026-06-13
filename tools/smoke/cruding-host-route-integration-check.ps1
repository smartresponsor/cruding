<#
Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp.

Host-side Cruding route integration verifier.

This script is intentionally read-only. It does not modify the host application.
Run it from the Symfony host application root, where bin/console exists.

Examples:
  .\tools\smoke\cruding-host-route-integration-check.ps1 -ResourcePath alpha -NestedResourcePath alpha/attachment/media
  .\tools\smoke\cruding-host-route-integration-check.ps1 -ResourcePath beta -NestedResourcePath beta/profile/document
#>

param(
    [string] $Php = 'php',
    [string] $Console = 'bin/console',
    [string] $ResourcePath = 'alpha',
    [string] $NestedResourcePath = 'alpha/attachment/media',
    [string] $Slug = 'sample-entry',
    [string] $Id = '123',
    [switch] $SkipCachePurge
)

$ErrorActionPreference = 'Stop'

function Fail([string] $Message) {
    Write-Error $Message
    exit 1
}

function Run-Console([string[]] $Arguments) {
    $output = & $Php $Console @Arguments 2>&1
    $exitCode = $LASTEXITCODE

    return [PSCustomObject]@{
        ExitCode = $exitCode
        Text = ($output | Out-String)
        Raw = $output
    }
}

function Assert-Ok([string] $Label, [object] $Result) {
    if ($Result.ExitCode -ne 0) {
        Write-Host $Result.Text
        Fail "$Label failed with exit code $($Result.ExitCode)."
    }
}

function Assert-Contains([string] $Label, [string] $Text, [string] $Needle) {
    if ($Text -notmatch [regex]::Escape($Needle)) {
        Write-Host $Text
        Fail "$Label did not contain expected text: $Needle"
    }
}

function Assert-NotContains([string] $Label, [string] $Text, [string] $Needle) {
    if ($Text -match [regex]::Escape($Needle)) {
        Write-Host $Text
        Fail "$Label contained forbidden text: $Needle"
    }
}

if (-not (Test-Path $Console)) {
    Fail "Symfony console was not found at '$Console'. Run from the host application root or pass -Console."
}

if (-not $SkipCachePurge) {
    Remove-Item -Recurse -Force '.\var\cache\dev' -ErrorAction SilentlyContinue
    Remove-Item -Recurse -Force '.\var\cache\test' -ErrorAction SilentlyContinue
}

$warmup = Run-Console @('cache:warmup', '-vvv')
Assert-Ok 'cache:warmup' $warmup

$parameter = Run-Console @('debug:container', '--parameter=cruding.identity_slug_requirement')
Assert-Ok 'debug:container cruding.identity_slug_requirement' $parameter
Assert-Contains 'identity slug parameter' $parameter.Text 'cruding.identity_slug_requirement'

$indexRoute = Run-Console @('debug:router', 'cruding_tokenized_catch_all', '-vvv')
Assert-Ok 'debug:router cruding_tokenized_catch_all' $indexRoute
Assert-Contains 'cruding_tokenized_catch_all route' $indexRoute.Text '/{crudPath}'

$showSlugRoute = Run-Console @('debug:router', 'cruding_tokenized_catch_all', '-vvv')
Assert-Ok 'debug:router cruding_tokenized_catch_all' $showSlugRoute
Assert-Contains 'cruding_tokenized_catch_all route' $showSlugRoute.Text '/{crudPath}'

$resourceIndexPath = '/' + $ResourcePath.Trim('/') + '/index'
$resourceShowSlugPath = '/' + $ResourcePath.Trim('/') + '/' + $Slug.Trim('/')
$nestedEditIdPath = '/' + $NestedResourcePath.Trim('/') + '/edit/' + $Id.Trim('/')
$nestedArchiveSlugPath = '/' + $NestedResourcePath.Trim('/') + '/archive/' + $Slug.Trim('/')

$indexMatch = Run-Console @('router:match', $resourceIndexPath, '--method=GET')
Assert-Ok "router:match $resourceIndexPath" $indexMatch
Assert-Contains "router:match $resourceIndexPath" $indexMatch.Text 'cruding_tokenized_catch_all'
Assert-Contains "router:match $resourceIndexPath" $indexMatch.Text '_crud_operation'
Assert-Contains "router:match $resourceIndexPath" $indexMatch.Text 'index'
Assert-Contains "router:match $resourceIndexPath" $indexMatch.Text 'resourcePath'
Assert-Contains "router:match $resourceIndexPath" $indexMatch.Text $ResourcePath.Trim('/')
Assert-NotContains "router:match $resourceIndexPath" $indexMatch.Text 'cruding_tokenized_catch_all'

$showSlugMatch = Run-Console @('router:match', $resourceShowSlugPath, '--method=GET')
Assert-Ok "router:match $resourceShowSlugPath" $showSlugMatch
Assert-Contains "router:match $resourceShowSlugPath" $showSlugMatch.Text 'cruding_tokenized_catch_all'
Assert-Contains "router:match $resourceShowSlugPath" $showSlugMatch.Text $Slug.Trim('/')

$editIdMatch = Run-Console @('router:match', $nestedEditIdPath, '--method=GET')
Assert-Ok "router:match $nestedEditIdPath" $editIdMatch
Assert-Contains "router:match $nestedEditIdPath" $editIdMatch.Text '_crud_operation'
Assert-Contains "router:match $nestedEditIdPath" $editIdMatch.Text 'edit'
Assert-Contains "router:match $nestedEditIdPath" $editIdMatch.Text $NestedResourcePath.Trim('/')
Assert-NotContains "router:match $nestedEditIdPath" $editIdMatch.Text 'cruding_tokenized_catch_all'

$archiveSlugMatch = Run-Console @('router:match', $nestedArchiveSlugPath, '--method=GET')
Assert-Ok "router:match $nestedArchiveSlugPath" $archiveSlugMatch
Assert-Contains "router:match $nestedArchiveSlugPath" $archiveSlugMatch.Text '_crud_operation'
Assert-Contains "router:match $nestedArchiveSlugPath" $archiveSlugMatch.Text 'archive'
Assert-Contains "router:match $nestedArchiveSlugPath" $archiveSlugMatch.Text $NestedResourcePath.Trim('/')
Assert-NotContains "router:match $nestedArchiveSlugPath" $archiveSlugMatch.Text 'cruding_tokenized_catch_all'

Write-Host 'PASS: Cruding host route integration is consistent.'
Write-Host "PASS: $resourceIndexPath matched cruding_tokenized_catch_all."
Write-Host "PASS: $resourceShowSlugPath matched cruding_tokenized_catch_all."
Write-Host "PASS: $nestedEditIdPath matched positional CRUD edit operation."
Write-Host "PASS: $nestedArchiveSlugPath matched positional CRUD archive operation."
