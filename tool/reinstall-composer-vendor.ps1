$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)

composer reinstall '*' --no-interaction --no-progress
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

composer dump-autoload --no-interaction
exit $LASTEXITCODE
