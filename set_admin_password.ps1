param(
    [string]$Username = 'admin'
)

$appRoot = Split-Path -Parent $PSScriptRoot
$wwwRoot = Split-Path -Parent $appRoot
$uwampRoot = Split-Path -Parent $wwwRoot
$phpRoot = Join-Path $uwampRoot 'bin\php'
$php = Get-ChildItem -LiteralPath $phpRoot -Directory |
    Sort-Object Name -Descending |
    ForEach-Object { Join-Path $_.FullName 'php.exe' } |
    Where-Object { Test-Path -LiteralPath $_ } |
    Select-Object -First 1

if (-not $php) {
    throw "Could not find php.exe under $phpRoot."
}

$resetScript = Join-Path $PSScriptRoot 'reset_admin_password.php'
$newPassword = Read-Host "New password for $Username (minimum 14 characters)" -AsSecureString
$confirmation = Read-Host 'Confirm new password' -AsSecureString
$newPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($newPassword)
$confirmationPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($confirmation)
$plainPassword = $null
$confirmedPassword = $null
$previousPassword = [Environment]::GetEnvironmentVariable('NEIS_ADMIN_PASSWORD', 'Process')

try {
    if ($newPassword.Length -lt 14) {
        throw 'Password must be at least 14 characters.'
    }

    $plainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($newPointer)
    $confirmedPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($confirmationPointer)
    if ($plainPassword -cne $confirmedPassword) {
        throw 'Passwords do not match.'
    }

    $env:NEIS_ADMIN_PASSWORD = $plainPassword
    & $php $resetScript $Username
    if ($LASTEXITCODE -ne 0) {
        throw 'Password update failed.'
    }
}
finally {
    if ($null -eq $previousPassword) {
        Remove-Item Env:\NEIS_ADMIN_PASSWORD -ErrorAction SilentlyContinue
    }
    else {
        $env:NEIS_ADMIN_PASSWORD = $previousPassword
    }
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($newPointer)
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmationPointer)
    $plainPassword = $null
    $confirmedPassword = $null
    $newPassword.Dispose()
    $confirmation.Dispose()
}
