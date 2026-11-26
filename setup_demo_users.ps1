$ErrorActionPreference = 'Stop'

$projectRoot = $PSScriptRoot
$wwwRoot = Split-Path -Parent $projectRoot
$uwampRoot = $wwwRoot
$phpRoot = Join-Path $uwampRoot 'bin\php'

if (-not (Test-Path -LiteralPath $phpRoot)) {
    throw "Could not find UwAmp PHP folder: $phpRoot"
}

$php = Get-ChildItem -LiteralPath $phpRoot -Filter 'php.exe' -File -Recurse |
    Sort-Object FullName -Descending |
    Select-Object -First 1

if (-not $php) {
    throw "Could not find php.exe under $phpRoot"
}

$setupScript = Join-Path $projectRoot 'create_demo_users.php'
& $php.FullName $setupScript
if ($LASTEXITCODE -ne 0) {
    throw 'Demo account setup failed. Confirm the SQLite extensions are enabled and the NEIS database exists.'
}
