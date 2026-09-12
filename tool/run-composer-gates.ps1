$ErrorActionPreference = 'Stop'
$PSNativeCommandUseErrorActionPreference = $true
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

composer validate --strict
composer dump-autoload
composer check:cruding

if (Get-Command phpstan -ErrorAction SilentlyContinue) {
    phpstan analyse --configuration=phpstan.neon --memory-limit=1G
}

Write-Host 'Composer/runtime RC gates OK'

