Param(
    [string]$DbRootUser = "root",
    [PSCredential]$DbRootCredential,
    [switch]$StartWeb
)

$ErrorActionPreference = "Stop"

Write-Host "[1/6] Verification des prerequis..." -ForegroundColor Cyan
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectRoot

$phpCandidates = @(
    "php",
    "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
)
$phpExe = $null
foreach ($candidate in $phpCandidates) {
    try {
        if ($candidate -eq "php") {
            $null = & php -v 2>$null
            if ($LASTEXITCODE -eq 0) { $phpExe = "php"; break }
        } elseif (Test-Path $candidate) {
            $phpExe = $candidate
            break
        }
    } catch {}
}
if (-not $phpExe) {
    throw "PHP introuvable. Installe PHP puis relance ce script."
}

$mariaDbCli = "C:\Program Files\MariaDB 12.2\bin\mariadb.exe"
$mariaDbServer = "C:\Program Files\MariaDB 12.2\bin\mariadbd.exe"
$mariaDbIni = "C:\Program Files\MariaDB 12.2\data\my.ini"
if (-not (Test-Path $mariaDbCli) -or -not (Test-Path $mariaDbServer)) {
    throw "MariaDB 12.2 non detecte. Verifie l'installation dans C:\Program Files\MariaDB 12.2"
}

Write-Host "[2/6] Demarrage MariaDB si necessaire..." -ForegroundColor Cyan
$listen = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
if (-not $listen) {
    $proc = Start-Process -FilePath $mariaDbServer -ArgumentList "--defaults-file=$mariaDbIni --standalone --console" -PassThru -WindowStyle Hidden
    Start-Sleep -Seconds 3
    $listen = Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue
    if (-not $listen) {
        throw "MariaDB n'ecoute pas sur le port 3306."
    }
    Write-Host "MariaDB lance (PID: $($proc.Id))." -ForegroundColor Green
} else {
    Write-Host "MariaDB deja actif sur 3306." -ForegroundColor Green
}

$effectiveRootUser = $DbRootUser
if ($DbRootCredential) {
    $effectiveRootUser = $DbRootCredential.UserName
}

$rootAuthArgs = @("-u", $effectiveRootUser)
$dbRootPasswordPlain = ""
if ($DbRootCredential) {
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($DbRootCredential.Password)
    try {
        $dbRootPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
}
if ($dbRootPasswordPlain -ne "") {
    $rootAuthArgs += "-p$dbRootPasswordPlain"
}

Write-Host "[3/6] Creation base/utilisateur applicatif..." -ForegroundColor Cyan
& $mariaDbCli @rootAuthArgs -e "CREATE DATABASE IF NOT EXISTS bd_matchmoove CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'userdb'@'127.0.0.1' IDENTIFIED BY 'root'; CREATE USER IF NOT EXISTS 'userdb'@'localhost' IDENTIFIED BY 'root'; GRANT ALL PRIVILEGES ON bd_matchmoove.* TO 'userdb'@'127.0.0.1'; GRANT ALL PRIVILEGES ON bd_matchmoove.* TO 'userdb'@'localhost'; FLUSH PRIVILEGES;"

Write-Host "[4/6] Import schema principal..." -ForegroundColor Cyan
& $mariaDbCli @rootAuthArgs bd_matchmoove -e "source mariadbsully.sql"

Write-Host "[5/6] Import seeders demo (Alex/Emma + activites)..." -ForegroundColor Cyan
& $mariaDbCli @rootAuthArgs bd_matchmoove -e "source seed_demo_matchmoov.sql"

Write-Host "[6/6] Preparation environnement..." -ForegroundColor Cyan
if (-not (Test-Path "$projectRoot\.env") -and (Test-Path "$projectRoot\.env.example")) {
    Copy-Item "$projectRoot\.env.example" "$projectRoot\.env"
    Write-Host "Fichier .env cree depuis .env.example" -ForegroundColor Green
}

Write-Host "Installation terminee avec succes." -ForegroundColor Green
Write-Host "Comptes demo:" -ForegroundColor Yellow
Write-Host "- alex.demo@matchmoov.local" -ForegroundColor Yellow
Write-Host "- emma.demo@matchmoov.local" -ForegroundColor Yellow
Write-Host "Mot de passe demo: secret123" -ForegroundColor Yellow

if ($StartWeb) {
    Write-Host "Demarrage serveur web: http://localhost:8000" -ForegroundColor Cyan
    & $phpExe -S localhost:8000
}
