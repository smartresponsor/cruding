$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$src = Join-Path $root 'src'

$changed = 0
Get-ChildItem -LiteralPath $src -Recurse -File -Filter '*.php' | ForEach-Object {
    $relativeDirectory = $_.DirectoryName.Substring($src.Length).TrimStart('\', '/')
    $expectedNamespace = 'App\Cruding'
    if ('' -ne $relativeDirectory) {
        $expectedNamespace += '\' + $relativeDirectory.Replace('/', '\')
    }

    $content = [System.IO.File]::ReadAllText($_.FullName)
    $pattern = '(?m)^namespace\s+App\\Cruding(?:\\[A-Za-z0-9_]+)*;'
    $replacement = 'namespace ' + $expectedNamespace + ';'
    $updated = [regex]::Replace($content, $pattern, $replacement, 1)

    if ($updated -eq $content -and $content -notmatch $pattern) {
        throw "No App\\Cruding namespace declaration found: $($_.FullName)"
    }

    if ($updated -cne $content) {
        [System.IO.File]::WriteAllText($_.FullName, $updated, [System.Text.UTF8Encoding]::new($false))
        $changed++
    }
}

Write-Host ("Normalized PSR-4 namespaces in {0} file(s)." -f $changed)
