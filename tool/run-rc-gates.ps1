$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Write-Host '=== composer validate ==='
composer validate --strict

Write-Host '=== composer dump-autoload ==='
composer dump-autoload

Write-Host '=== repository Gating ==='
Write-Warning 'Bundled .gating shell distribution is structurally stale for this consumer; use canonical Gating separately. Cruding product gates continue below.'


Write-Host '=== composer check:cruding ==='
composer check:cruding

if (Get-Command phpstan -ErrorAction SilentlyContinue) {
    Write-Host '=== phpstan ==='
    phpstan analyse --configuration=phpstan.neon --memory-limit=1G
}

