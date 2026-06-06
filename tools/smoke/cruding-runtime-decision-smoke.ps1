param(
    [string]$Console = "php bin/console",
    [switch]$Strict
)

$ErrorActionPreference = "Stop"

Write-Host "Cruding runtime decision smoke" -ForegroundColor Cyan

if ($Strict) {
    Write-Host "Strict mode is configured through cruding.route_guard, not by this script." -ForegroundColor Yellow
}

Invoke-Expression "$Console crud:runtime:route-guard"
if ($LASTEXITCODE -ne 0) { throw "crud:runtime:route-guard failed" }

Invoke-Expression "$Console crud:runtime:decision"
if ($LASTEXITCODE -ne 0) { throw "crud:runtime:decision failed" }

Invoke-Expression "$Console crud:runtime:route-match-smoke"
if ($LASTEXITCODE -ne 0) { throw "crud:runtime:route-match-smoke failed" }

Write-Host "Cruding runtime decision smoke passed." -ForegroundColor Green
