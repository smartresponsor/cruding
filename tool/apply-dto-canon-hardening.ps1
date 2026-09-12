$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$dto = Join-Path $root 'src\Dto'
$dtoCanonical = Join-Path $root 'src\DTO'
$dtoTemp = Join-Path $root 'src\__DTO_CANON_TMP__'

if (Test-Path $dto) {
    if (Test-Path $dtoTemp) { throw "Temporary DTO path already exists: $dtoTemp" }
    Move-Item -LiteralPath $dto -Destination $dtoTemp
    Move-Item -LiteralPath $dtoTemp -Destination $dtoCanonical
}

$roots = @('src', 'tests', 'tools', 'docs', 'config')
$files = foreach ($relativeRoot in $roots) {
    $path = Join-Path $root $relativeRoot
    if (Test-Path $path) {
        Get-ChildItem -LiteralPath $path -Recurse -File | Where-Object {
            $_.Extension -in @('.php', '.md', '.adoc', '.json', '.yaml', '.yml', '.xml', '.neon')
        }
    }
}
$files += Get-ChildItem -LiteralPath $root -File | Where-Object {
    $_.Extension -in @('.php', '.md', '.adoc', '.json', '.yaml', '.yml', '.xml', '.neon')
}

foreach ($file in $files | Sort-Object FullName -Unique) {
    $text = [System.IO.File]::ReadAllText($file.FullName)
    $updated = $text.Replace('App\Cruding\Dto\', 'App\Cruding\DTO\')
    $updated = $updated.Replace('src/Dto/', 'src/DTO/')
    $updated = $updated.Replace('`Dto/`', '`DTO/`')
    if ($updated -cne $text) {
        [System.IO.File]::WriteAllText($file.FullName, $updated, [System.Text.UTF8Encoding]::new($false))
    }
}

Write-Host 'DTO canon hardening applied.'
