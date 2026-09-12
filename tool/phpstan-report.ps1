$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$reportDir = Join-Path $root 'var/phpstan'
if (-not (Test-Path -LiteralPath $reportDir)) {
    New-Item -ItemType Directory -Path $reportDir -Force | Out-Null
}

$report = Join-Path $reportDir 'report.json'
& php vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=1G --error-format=json *> $report
$exit = $LASTEXITCODE
Write-Host $report
exit $exit
