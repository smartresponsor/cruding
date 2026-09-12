$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$dtoRoot = Join-Path $root 'src\DTO'

Get-ChildItem -LiteralPath $dtoRoot -Recurse -File -Filter '*.php' | ForEach-Object {
    $content = Get-Content -LiteralPath $_.FullName -Raw
    if ($content.Contains('namespace App\Cruding\Dto;')) {
        $content = $content.Replace('namespace App\Cruding\Dto;', 'namespace App\Cruding\DTO;')
        Set-Content -LiteralPath $_.FullName -Value $content -NoNewline
    }
}

Write-Host 'Cruding DTO namespace casing normalized.'
