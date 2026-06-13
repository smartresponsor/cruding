param(
    [string]$RuntimeScope = "cruding,viewing,interfacing,administering,accessing",
    [string]$RuntimeEntity = "alpha,attachment,media,beta,gamma",
    [string]$RuntimeSurfaceToken = "show,card,table,gallery,compact,full,detail,list",
    [string]$RuntimeReserved = "",
    [switch]$SkipCacheClear,
    [switch]$FailOnEmptyEntity
)

$ErrorActionPreference = "Stop"

function Invoke-Step {
    param(
        [string]$Title,
        [scriptblock]$Script
    )

    Write-Host ""
    Write-Host "==> $Title" -ForegroundColor Cyan
    & $Script
    if ($LASTEXITCODE -ne 0) {
        throw "Step failed: $Title"
    }
}

if (-not (Test-Path ".\composer.json")) {
    throw "Run this script from a Symfony host app root. composer.json not found."
}

if (-not (Test-Path ".\bin\console")) {
    throw "Run this script from a Symfony host app root. bin/console not found."
}

$env:APP_RUNTIME_SCOPE = $RuntimeScope
$env:APP_RUNTIME_ENTITY = $RuntimeEntity
$env:APP_RUNTIME_SURFACE_TOKEN = $RuntimeSurfaceToken
if ($RuntimeReserved -ne "") {
    $env:APP_RUNTIME_RESERVED = $RuntimeReserved
}

Write-Host "APP_RUNTIME_SCOPE=$env:APP_RUNTIME_SCOPE"
Write-Host "APP_RUNTIME_ENTITY=$env:APP_RUNTIME_ENTITY"
Write-Host "APP_RUNTIME_SURFACE_TOKEN=$env:APP_RUNTIME_SURFACE_TOKEN"
if ($RuntimeReserved -ne "") {
    Write-Host "APP_RUNTIME_RESERVED=$env:APP_RUNTIME_RESERVED"
}

if (-not $SkipCacheClear) {
    Invoke-Step "cache clear" { php bin/console cache:clear }
}

Invoke-Step "runtime route guard" { php bin/console crud:runtime:route-guard }

$smokeArgs = @("bin/console", "crud:runtime:route-match-smoke")
if ($FailOnEmptyEntity) {
    $smokeArgs += "--fail-on-empty-entity"
}

Invoke-Step "runtime route match smoke" { php @smokeArgs }

Invoke-Step "show Cruding routes" { php bin/console debug:router | Select-String "crud|cruding" }

Write-Host ""
Write-Host "Cruding runtime route match smoke completed." -ForegroundColor Green
