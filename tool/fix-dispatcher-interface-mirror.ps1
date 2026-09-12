$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$source = Join-Path $root 'src/Service/Dispatch/CrudServiceDispatcher.php'
$target = Join-Path $root 'src/Service/Entrypoint/CrudServiceDispatcher.php'

if (Test-Path -LiteralPath $source) {
    $targetDir = Split-Path -Parent $target
    if (-not (Test-Path -LiteralPath $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
    }
    if (Test-Path -LiteralPath $target) { throw "Dispatcher target already exists: $target" }
    Move-Item -LiteralPath $source -Destination $target
}

$old = 'App\Cruding\Service\Dispatch\CrudServiceDispatcher'
$new = 'App\Cruding\Service\Entrypoint\CrudServiceDispatcher'
Get-ChildItem -LiteralPath $root -Recurse -File | Where-Object {
    $relative = $_.FullName.Substring($root.Length).TrimStart('\','/')
    $segments = $relative -split '[\/]'
    $_.Extension -in @('.php','.yaml','.yml','.json','.md','.adoc') -and
    -not ($segments | Where-Object { $_ -in @('.git','vendor','var','node_modules','.gating') })
} | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName)
    $updated = $content.Replace($old, $new).Replace('src/Service/Dispatch/CrudServiceDispatcher.php', 'src/Service/Entrypoint/CrudServiceDispatcher.php')
    if ($updated -cne $content) { [System.IO.File]::WriteAllText($_.FullName, $updated, [System.Text.UTF8Encoding]::new($false)) }
}

Write-Host 'CrudServiceDispatcher moved to the ServiceInterface mirror path.'
