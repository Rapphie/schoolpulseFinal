# SchoolPulse Final — Automated Project Setup (Windows 11)
#
# Usage: .\setup.ps1 [options]
#
# Options:
#   -SkipClone           Skip git clone (use when repo already exists locally)
#   -SkipDbCreate        Skip MySQL database creation
#   -SkipSqlImport        Skip importing the .sql dump file
#   -SkipComposer        Skip composer install
#   -SkipNpm             Skip npm install & build
#   -SkipMigrate         Skip artisan migrate
#   -SkipStorageLink     Skip artisan storage:link
#   -NoPrompt            Use all DB defaults without prompting (host=127.0.0.1,
#                        port=3306, db=schoolpulse, user=root, password empty)
#   -ProjectDir <path>   Target directory (default: schoolpulseFinal)
#   -Help                Show this help message
#
# Run from PowerShell:
#   Set-ExecutionPolicy -Scope CurrentUser RemoteSigned   (one-time, if scripts are blocked)
#   .\setup.ps1
#   .\setup.ps1 -SkipClone -NoPrompt
#   .\setup.ps1 -SkipNpm -SkipComposer -ProjectDir C:\sites\schoolpulseFinal
#
[CmdletBinding()]
param(
    [switch]$SkipClone,
    [switch]$SkipDbCreate,
    [switch]$SkipSqlImport,
    [switch]$SkipComposer,
    [switch]$SkipNpm,
    [switch]$SkipMigrate,
    [switch]$SkipStorageLink,
    [switch]$NoPrompt,
    [string]$ProjectDir = ""
)

# ─── Help ─────────────────────────────────────────────────────────────────────
if ($Help) {
    Write-Host @"

`e[1mSchoolPulse Final — Setup Script`e[0m

`e[36mUsage:`e[0m  .\setup.ps1 [options]

`e[36mOptions:`e[0m
  -SkipClone           Skip git clone (use when repo already exists locally)
  -SkipDbCreate        Skip MySQL database creation
  -SkipSqlImport       Skip importing the .sql dump file
  -SkipComposer        Skip composer install
  -SkipNpm             Skip npm install & build
  -SkipMigrate         Skip artisan migrate
  -SkipStorageLink     Skip artisan storage:link
  -NoPrompt            Use all DB defaults without prompting:
                         DB_HOST=127.0.0.1  DB_PORT=3306
                         DB_DATABASE=schoolpulse  DB_USERNAME=root
                         DB_PASSWORD=(empty)
  -ProjectDir <path>   Target directory (default: schoolpulseFinal)
  -Help                Show this help message

`e[36mExamples:`e[0m
  .\setup.ps1                                 Full interactive setup
  .\setup.ps1 -SkipClone -NoPrompt           Repo exists locally, use DB defaults
  .\setup.ps1 -SkipNpm -SkipComposer         Skip dependency install (already installed)
  .\setup.ps1 -SkipMigrate -SkipSqlImport   Only configure .env & dependencies
"@
    exit 0
}

$ErrorActionPreference = "Stop"

# ─── Colors ───────────────────────────────────────────────────────────────────
function Write-Info($msg)    { Write-Host "`e[36m[INFO]`e[0m  $msg" }
function Write-Success($msg) { Write-Host "`e[32m[OK]`e[0m    $msg" }
function Write-Warn($msg)     { Write-Host "`e[33m[WARN]`e[0m  $msg" }
function Write-Error2($msg)   { Write-Host "`e[31m[ERROR]`e[0m $msg"; exit 1 }
function Write-Step($msg)     { Write-Host ""; Write-Host "`e[1m--- $msg ---`e[0m" }

# ─── Defaults ─────────────────────────────────────────────────────────────────
$RepoUrl = "https://github.com/Rapphie/schoolpulseFinal.git"
if (-not $ProjectDir) {
    $ProjectDir = "schoolpulseFinal"
}

# ─── Step 1: Prerequisite Checks ─────────────────────────────────────────────
Write-Step "Checking prerequisites"

$prereqsMet = $true
$prereqs = @("git", "php", "composer", "node", "npm")

foreach ($cmd in $prereqs) {
    try {
        $cmdPath = Get-Command $cmd -ErrorAction Stop | Select-Object -ExpandProperty Source
        $version = ""
        switch ($cmd) {
            "php"      { try { $version = php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>$null } catch { $version = "found" } }
            "node"     { try { $version = (node --version 2>$null).Trim() } catch { $version = "found" } }
            "npm"      { try { $version = (npm --version 2>$null).Trim() } catch { $version = "found" } }
            "composer" { try { $v = composer --version 2>$null; $version = ($v -split ' ')[2] } catch { $version = "found" } }
            "git"      { try { $v = git --version 2>$null; $version = ($v -split ' ')[2] } catch { $version = "found" } }
            default    { $version = "found" }
        }
        Write-Success "$cmd $version"
    } catch {
        Write-Warn "$cmd not found"
        $prereqsMet = $false
    }
}

# Check MySQL separately (it might not be on PATH on Windows)
$mysqlFound = $false
try {
    Get-Command mysql -ErrorAction Stop | Out-Null
    $mysqlFound = $true
    $mysqlVersion = (mysql --version 2>$null)
    Write-Success "mysql found"
} catch {
    # Check common Windows MySQL paths
    $mysqlPaths = @(
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
        "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"
        "C:\Program Files\MySQL\MySQL Server 9.0\bin\mysql.exe"
        "C:\xampp\mysql\bin\mysql.exe"
        "${env:ProgramFiles}\MySQL\MySQL Server 8.0\bin\mysql.exe"
    )
    foreach ($p in $mysqlPaths) {
        if (Test-Path $p) {
            $mysqlFound = $true
            Write-Success "mysql found at $p"
            break
        }
    }
    if (-not $mysqlFound) {
        Write-Warn "mysql not found (check if MySQL is installed or on PATH)"
        $prereqsMet = $false
    }
}

# PHP version check (need 8.2+)
try {
    $phpMajor = php -r "echo PHP_MAJOR_VERSION;" 2>$null
    $phpMinor = php -r "echo PHP_MINOR_VERSION;" 2>$null
    if ([int]$phpMajor -lt 8 -or ([int]$phpMajor -eq 8 -and [int]$phpMinor -lt 2)) {
        Write-Error2 "PHP 8.2+ is required. Found PHP $phpMajor.$phpMinor"
    }
} catch {
    # Already warned above
}

if (-not $prereqsMet) {
    Write-Host ""
    Write-Warn "Some prerequisites are missing."
    Write-Host "  Install them before continuing:"
    Write-Host "    `e[36mPHP:`e[0m       https://windows.php.net/download/"
    Write-Host "    `e[36mComposer:`e[0m  https://getcomposer.org/download/"
    Write-Host "    `e[36mNode.js:`e[0m   https://nodejs.org/"
    Write-Host "    `e[36mMySQL:`e[0m     https://dev.mysql.com/downloads/installer/"
    Write-Host "    `e[36mGit:`e[36m      https://git-scm.com/download/win"
    Write-Host "    `e[36mHerd:`e[0m      https://herd.laravel.com/ (recommended for Laravel on Windows)"
    Write-Host ""
    $continue = Read-Host "Continue anyway? [y/N]"
    if ($continue -notmatch "^[yY]$") {
        Write-Error2 "Aborted by user."
    }
}

# ─── Step 2: Clone Repository ─────────────────────────────────────────────────
Write-Step "Cloning repository"

$projectFullPath = if ([System.IO.Path]::IsPathRooted($ProjectDir)) { $ProjectDir } else { Join-Path $PWD $ProjectDir }

if ($SkipClone) {
    Write-Info "Skipping clone (-SkipClone flag)"
} elseif (Test-Path $projectFullPath) {
    Write-Warn "Directory '$ProjectDir' already exists. Skipping clone."
    Write-Info "If you want a fresh clone, remove the directory first."
} else {
    Write-Info "Cloning from $RepoUrl ..."
    git clone $RepoUrl $ProjectDir
    Write-Success "Repository cloned."
}

Set-Location $projectFullPath
if (-not (Test-Path "artisan")) {
    Write-Error2 "Cannot find artisan in '$projectFullPath'. Is this a Laravel project?"
}

# ─── Step 3: Environment Setup ───────────────────────────────────────────────
Write-Step "Setting up .env"

if (Test-Path ".env") {
    $timestamp = Get-Date -Format "yyyyMMddHHmmss"
    Write-Info ".env already exists. Backing up to .env.backup.$timestamp"
    Copy-Item ".env" ".env.backup.$timestamp"
}

Copy-Item ".env.example" ".env"
Write-Success ".env created from .env.example"

# Prompt for DB credentials
if ($NoPrompt) {
    $dbHost     = "127.0.0.1"
    $dbPort     = "3306"
    $dbDatabase = "schoolpulse"
    $dbUsername = "root"
    $dbPasswordPlain = ""
    Write-Info "Using DB defaults (-NoPrompt): ${dbHost}:${dbPort} / ${dbDatabase} / ${dbUsername}"
} else {
    Write-Host ""
    Write-Host "`e[1mDatabase Configuration`e[0m"
    Write-Host "  Press Enter to accept defaults."
    Write-Host ""

    $dbHost     = Read-Host "  DB_HOST [127.0.0.1]"
    if (-not $dbHost)     { $dbHost = "127.0.0.1" }

    $dbPort     = Read-Host "  DB_PORT [3306]"
    if (-not $dbPort)     { $dbPort = "3306" }

    $dbDatabase = Read-Host "  DB_DATABASE [schoolpulse]"
    if (-not $dbDatabase) { $dbDatabase = "schoolpulse" }

    $dbUsername = Read-Host "  DB_USERNAME [root]"
    if (-not $dbUsername) { $dbUsername = "root" }

    $dbPassword = Read-Host "  DB_PASSWORD (hidden)" -AsSecureString
    $dbPasswordPlain = [System.Net.NetworkCredential]::new("", $dbPassword).Password
}

# Update .env file with DB credentials
$envContent = Get-Content ".env" -Raw

$envContent = $envContent -replace '(?m)^DB_CONNECTION=.*', "DB_CONNECTION=mysql"
$envContent = $envContent -replace '(?m)^DB_HOST=.*', "DB_HOST=$dbHost"
$envContent = $envContent -replace '(?m)^DB_PORT=.*', "DB_PORT=$dbPort"
$envContent = $envContent -replace '(?m)^DB_DATABASE=.*', "DB_DATABASE=$dbDatabase"
$envContent = $envContent -replace '(?m)^DB_USERNAME=.*', "DB_USERNAME=$dbUsername"
$envContent = $envContent -replace '(?m)^DB_PASSWORD=.*', "DB_PASSWORD=$dbPasswordPlain"

# If DB_CONNECTION was commented out, add it
if ($envContent -notmatch 'DB_CONNECTION=') {
    $envContent = $envContent -replace '(?m)(^DB_HOST=.*)', "DB_CONNECTION=mysql`n`$1"
}

Set-Content ".env" $envContent -NoNewline

# Update APP_URL
$envContent = Get-Content ".env" -Raw
$envContent = $envContent -replace '(?m)^APP_URL=.*', "APP_URL=http://localhost:8000"
Set-Content ".env" $envContent -NoNewline

Write-Success ".env configured with database credentials"
Write-Info "  DB_HOST:     $dbHost"
Write-Info "  DB_PORT:     $dbPort"
Write-Info "  DB_DATABASE: $dbDatabase"
Write-Info "  DB_USERNAME: $dbUsername"

# Generate app key
Write-Info "Generating application key..."
php artisan key:generate --force
Write-Success "APP_KEY generated."

# ─── Step 4: Install Dependencies ─────────────────────────────────────────────
Write-Step "Installing dependencies"

if ($SkipComposer) {
    Write-Info "Skipping composer install (-SkipComposer)"
} else {
    Write-Info "Installing Composer dependencies..."
    composer install --no-interaction
    Write-Success "Composer dependencies installed."
}

if ($SkipNpm) {
    Write-Info "Skipping npm install & build (-SkipNpm)"
} else {
    Write-Info "Installing Node.js dependencies..."
    npm install
    Write-Success "Node.js dependencies installed."

    Write-Info "Building frontend assets..."
    npm run build
    Write-Success "Frontend assets built."
}

# ─── Step 5: Database Creation & SQL Import ───────────────────────────────────
Write-Step "Setting up database"

# Resolve mysql command (might not be on PATH on Windows)
$mysqlCmd = "mysql"
if (-not (Get-Command mysql -ErrorAction SilentlyContinue)) {
    $mysqlSearchPaths = @(
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
        "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"
        "C:\Program Files\MySQL\MySQL Server 9.0\bin\mysql.exe"
        "C:\xampp\mysql\bin\mysql.exe"
        "${env:ProgramFiles}\MySQL\MySQL Server 8.0\bin\mysql.exe"
    )
    foreach ($p in $mysqlSearchPaths) {
        if (Test-Path $p) {
            $mysqlCmd = $p
            Write-Info "Using MySQL at: $p"
            break
        }
    }
}

# Create database
if ($SkipDbCreate) {
    Write-Info "Skipping database creation (-SkipDbCreate)"
} else {
    Write-Info "Creating database '$dbDatabase' if it doesn't exist..."
    $env:mysqlPwd = $dbPasswordPlain
    try {
        & $mysqlCmd -h $dbHost -P $dbPort -u $dbUsername --password=$dbPasswordPlain -e "CREATE DATABASE IF NOT EXISTS ``$dbDatabase`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null
        Write-Success "Database '$dbDatabase' ready."
    } catch {
        Write-Warn "Could not create database with provided credentials."
        Write-Warn "You may need to create it manually:"
        Write-Warn "  mysql -u $dbUsername -p -e `"CREATE DATABASE $dbDatabase`""
    }
}

# Auto-detect and import .sql file
if ($SkipSqlImport) {
    Write-Info "Skipping SQL import (-SkipSqlImport)"
} else {
    $sqlFile = ""
    $sqlCandidates = @()
    
    # Priority: check for ps1-setup.sql first (bundled with project)
    $priorityFiles = @(".\ps1-setup.sql")
    foreach ($pf in $priorityFiles) {
        if (Test-Path $pf) {
            $sqlCandidates += @(Resolve-Path $pf).Path
        }
    }

    # Also search for any other .sql files
    $searchDirs = @(".", ".\database", ".\sql", ".\dump")
    foreach ($dir in $searchDirs) {
        if (Test-Path $dir) {
            $found = Get-ChildItem -Path $dir -Filter "*.sql" -File -ErrorAction SilentlyContinue
            $sqlCandidates += @($found | ForEach-Object { $_.FullName })
        }
    }
    # Remove duplicates
    $sqlCandidates = $sqlCandidates | Select-Object -Unique

    if ($sqlCandidates.Count -eq 0) {
        Write-Warn "No .sql file found in the project."
        Write-Warn "Place your SQL dump in the project root or database/ directory and run:"
        Write-Warn "  mysql -h $dbHost -P $dbPort -u $dbUsername -p $dbDatabase < your_dump.sql"
    } elseif ($sqlCandidates.Count -eq 1) {
        $sqlFile = $sqlCandidates[0]
        Write-Success "Found SQL file: $sqlFile"
    } else {
        Write-Host ""
        Write-Host "`e[33mMultiple .sql files found:`e[0m"
        for ($i = 0; $i -lt $sqlCandidates.Count; $i++) {
            Write-Host "  [$i] $($sqlCandidates[$i])"
        }
        Write-Host "  [S] Skip import"
        Write-Host ""
        $choice = Read-Host "Select which file to import [0-$($sqlCandidates.Count - 1) or S]"
        if ($choice -match "^[sS]$") {
            $sqlFile = ""
        } elseif ($choice -match "^\d+$" -and [int]$choice -lt $sqlCandidates.Count) {
            $sqlFile = $sqlCandidates[[int]$choice]
        } else {
            Write-Warn "Invalid selection. Skipping SQL import."
            $sqlFile = ""
        }
    }

    if ($sqlFile) {
        Write-Info "Importing '$sqlFile' into '$dbDatabase' ..."
        Write-Info "This may take a while for large dumps..."
        try {
            & $mysqlCmd -h $dbHost -P $dbPort -u $dbUsername --password=$dbPasswordPlain $dbDatabase -e "source $sqlFile" 2>&1
            Write-Success "SQL file imported successfully."
        } catch {
            # Retry showing errors
            $result = & $mysqlCmd -h $dbHost -P $dbPort -u $dbUsername --password=$dbPasswordPlain $dbDatabase -e "source $sqlFile" 2>&1
            if ($LASTEXITCODE -ne 0) {
                Write-Error2 "Failed to import SQL file: $result`nCheck your credentials and try manually."
            }
        }
    }
}

# ─── Step 6: Laravel Finalization ─────────────────────────────────────────────
Write-Step "Finalizing Laravel setup"

if ($SkipStorageLink) {
    Write-Info "Skipping storage:link (-SkipStorageLink)"
} else {
    Write-Info "Linking storage..."
    try {
        php artisan storage:link 2>$null
        Write-Success "Storage linked."
    } catch {
        Write-Warn "storage:link failed (may already be linked)"
    }
}

Write-Info "Caching configuration..."
php artisan config:cache
Write-Success "Configuration cached."

if ($SkipMigrate) {
    Write-Info "Skipping migrations (-SkipMigrate)"
} else {
    Write-Info "Running migrations (to catch any new schema changes)..."
    try {
        php artisan migrate --force 2>$null
        Write-Success "Migrations complete."
    } catch {
        Write-Warn "Migration had issues - this is normal if the SQL dump already created all tables."
    }
}

# ─── Step 7: Done! ────────────────────────────────────────────────────────────
Write-Step "Setup Complete!"

Write-Host ""
Write-Host "`e[32m`e[1mSchoolPulse Final is ready!`e[0m"
Write-Host ""
Write-Host "`e[36mQuick start:`e[0m"
Write-Host "  cd $ProjectDir"
Write-Host "  php artisan serve"
Write-Host ""
Write-Host "`e[36mOr use Laravel Herd:`e[0m"
Write-Host "  The site should be available at https://$ProjectDir.test"
Write-Host ""
Write-Host "`e[36mDatabase:`e[0m"
Write-Host "  Host:     ${dbHost}:${dbPort}"
Write-Host "  Database: $dbDatabase"
Write-Host "  User:     $dbUsername"
Write-Host ""
Write-Host "`e[36mEnvironment:`e[0m"
Write-Host "  Edit .env for additional configuration (mail, cache, etc.)"
Write-Host ""