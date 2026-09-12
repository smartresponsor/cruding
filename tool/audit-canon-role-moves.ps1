$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$src = Join-Path $root 'src'

$suffixRoots = [ordered]@{
    'Controller' = 'Controller'
    'Command' = 'Command'
    'Factory' = 'Factory'
    'Handler' = 'Handler'
    'Listener' = 'Listener'
    'Normalizer' = 'Normalizer'
    'Provider' = 'Provider'
    'Resolver' = 'Resolver'
    'Subscriber' = 'EventSubscriber'
    'Builder' = 'Builder'
    'Responder' = 'Responder'
    'Policy' = 'Policy'
    'Repository' = 'Repository'
    'Service' = 'Service'
    'Recorder' = 'Recorder'
    'Verifier' = 'Verifier'
    'Authenticator' = 'Authenticator'
    'Codec' = 'Codec'
}

Get-ChildItem -LiteralPath $src -Recurse -File -Filter '*.php' | ForEach-Object {
    $relative = $_.FullName.Substring($src.Length + 1).Replace('\', '/')
    $parts = $relative -split '/'
    $firstRoot = if ($parts.Length -gt 1) { $parts[0] } else { '' }
    $name = $_.BaseName
    foreach ($entry in $suffixRoots.GetEnumerator()) {
        if ($name.EndsWith($entry.Key, [System.StringComparison]::Ordinal) -and $firstRoot -ne $entry.Value) {
            [pscustomobject]@{ File = $relative; Name = $name; CurrentRoot = $firstRoot; ExpectedRoot = $entry.Value }
            break
        }
    }
} | Sort-Object ExpectedRoot, File | Format-Table -AutoSize

Write-Host '--- non-canonical first roots ---'
$allowed = @('Controller','Service','ServiceInterface','Repository','RepositoryInterface','Entity','DTO','Snapshot','Form','FormInterface','Policy','Builder','BuilderInterface','Responder','ResponderInterface','Command','Enum','Event','EventSubscriber','ValueObject','ValueObjectInterface','Exception','Message','Handler','Provider','ProviderInterface','Resolver','Factory','FactoryInterface','Recorder','RecorderInterface','Verifier','VerifierInterface','Authenticator','Clock','Codec','Context','Guard','Invoker','Parser','Runner','Contract','Kernel','Registry','Rule','Console','Inventory','Profile','Reporter','Target','DataFixtures','Validator','DependencyInjection','Normalizer')
Get-ChildItem -LiteralPath $src -Recurse -File -Filter '*.php' | ForEach-Object {
    $relative = $_.FullName.Substring($src.Length + 1).Replace('\', '/')
    $parts = $relative -split '/'
    if ($parts.Length -gt 1 -and $parts[0] -notin $allowed) { $relative }
} | Sort-Object -Unique
