$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
Set-Location $root
Write-Host '=== ROOT FILES ==='
Get-ChildItem . -Force | Sort-Object PSIsContainer,Name | ForEach-Object { $k=if($_.PSIsContainer){'DIR '}else{'FILE'}; Write-Host ("{0} {1}" -f $k,$_.Name) }
Write-Host '=== DOC FILES ==='
if(Test-Path docs){Get-ChildItem docs -Recurse -File | ForEach-Object {$_.FullName.Substring($root.Length+1)} | Sort-Object}
$phpFiles=Get-ChildItem src -Recurse -File -Filter '*.php'
$classTotal=0;$classDoc=0;$methodTotal=0;$methodDoc=0;$missingClasses=@();$missingMethods=@()
foreach($file in $phpFiles){
  $lines=Get-Content $file.FullName
  for($i=0;$i -lt $lines.Count;$i++){
    $line=$lines[$i]
    if($line -match '^\s*(?:final\s+|abstract\s+|readonly\s+|final\s+readonly\s+|abstract\s+readonly\s+)*(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)'){
      $classTotal++;$symbol=$Matches[2];$j=$i-1
      while($j -ge 0 -and ($lines[$j].Trim() -eq '' -or $lines[$j].Trim().StartsWith('#['))){$j--}
      if($j -ge 0 -and $lines[$j].Trim().EndsWith('*/')){$classDoc++}else{$missingClasses+=($file.FullName.Substring($root.Length+1)+':'+($i+1)+':'+$symbol)}
    }
    if($line -match '^\s*public\s+(?:static\s+)?function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\('){
      $name=$Matches[1];if($name -eq '__construct'){continue};$methodTotal++;$j=$i-1
      while($j -ge 0 -and ($lines[$j].Trim() -eq '' -or $lines[$j].Trim().StartsWith('#['))){$j--}
      if($j -ge 0 -and $lines[$j].Trim().EndsWith('*/')){$methodDoc++}else{$missingMethods+=($file.FullName.Substring($root.Length+1)+':'+($i+1)+':'+$name)}
    }
  }
}
Write-Host '=== PHPDOC SUMMARY ==='
Write-Host ("classes: {0}/{1}" -f $classDoc,$classTotal)
Write-Host ("public methods excluding constructors: {0}/{1}" -f $methodDoc,$methodTotal)
Write-Host '=== MISSING CLASS DOCS ==='
$missingClasses|ForEach-Object{Write-Host $_}
Write-Host '=== MISSING PUBLIC METHOD DOCS ==='
$missingMethods|ForEach-Object{Write-Host $_}
Write-Host '=== PACKAGING EXPECTATIONS ==='
foreach($name in @('README.adoc','README.md','CHANGELOG.md','CONTRIBUTING.md','SECURITY.md','LICENSE','LICENSE.md','MANIFEST.json','phpunit.xml','.php-cs-fixer.php','.editorconfig','.gitattributes','.gitignore')){$p=if(Test-Path $name){'YES '}else{'NO  '};Write-Host ($p+$name)}
