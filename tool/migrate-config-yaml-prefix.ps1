$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$renames = [ordered]@{
    'config/crud_reserved_token.yaml' = 'config/crud_reserved_token.yaml'
    'config/routes/crud_api_crud.yaml' = 'config/routes/crud_api_crud.yaml'
    'config/routes/crud_crud.yaml' = 'config/routes/crud_crud.yaml'
    'config/routes/crud_resource.yaml' = 'config/routes/crud_resource.yaml'
}

foreach ($entry in $renames.GetEnumerator()) {
    $source = Join-Path $root $entry.Key
    $target = Join-Path $root $entry.Value
    if (Test-Path -LiteralPath $source) {
        $targetDir = Split-Path -Parent $target
        if (-not (Test-Path -LiteralPath $targetDir)) {
            New-Item -ItemType Directory -Path $targetDir | Out-Null
        }
        if (Test-Path -LiteralPath $target) {
            throw "Target already exists: $target"
        }
        Move-Item -LiteralPath $source -Destination $target
    }
}

$extensions = @('.php', '.md', '.adoc', '.yaml', '.yml', '.json', '.ps1')
Get-ChildItem -LiteralPath $root -Recurse -File | Where-Object {
    $relative = $_.FullName.Substring($root.Length).TrimStart('\', '/')
    $segments = $relative -split '[\/]'
    -not ($segments | Where-Object { $_ -in @('.git', 'vendor', 'var', 'node_modules', '.gating') }) -and
    $_.Extension -in $extensions
} | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName)
    $updated = $content
    foreach ($entry in $renames.GetEnumerator()) {
        $oldLeaf = [System.IO.Path]::GetFileName($entry.Key)
        $newLeaf = [System.IO.Path]::GetFileName($entry.Value)
        $updated = $updated.Replace($entry.Key, $entry.Value).Replace($oldLeaf, $newLeaf)
    }
    if ($updated -cne $content) {
        [System.IO.File]::WriteAllText($_.FullName, $updated, [System.Text.UTF8Encoding]::new($false))
    }
}

Write-Host 'Cruding YAML filenames and references migrated to crud_* prefix.'
