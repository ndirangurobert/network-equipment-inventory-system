$ErrorActionPreference = 'Stop'

# Find the UwAmp installation folder even if the project is inside an extra
# extracted ZIP folder.
$folder = Get-Item -LiteralPath $PSScriptRoot
$uwampRoot = $null
while ($null -ne $folder) {
    if (Test-Path -LiteralPath (Join-Path $folder.FullName 'bin\php')) {
        $uwampRoot = $folder.FullName
        break
    }
    $folder = $folder.Parent
}

if (-not $uwampRoot) {
    throw "Could not locate the UwAmp installation above $PSScriptRoot. Make sure this project is inside UwAmp\www."
}

$phpRoot = Join-Path $uwampRoot 'bin\php'
$php = Get-ChildItem -LiteralPath $phpRoot -Filter 'php.exe' -File -Recurse |
    Sort-Object FullName -Descending |
    Select-Object -First 1

if (-not $php) {
    throw "Could not find php.exe under $phpRoot"
}

$setupScript = Join-Path $PSScriptRoot 'create_demo_users.php'
& $php.FullName $setupScript
if ($LASTEXITCODE -ne 0) {
    throw 'Demo account setup failed. Confirm the SQLite extensions are enabled and the NEIS database exists.'
}
