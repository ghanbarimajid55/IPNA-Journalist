#Requires -Version 5.1
<#
.SYNOPSIS
    Repeatable end-to-end smoke test for IPNA PMS.
.DESCRIPTION
    Runs the installer against a throwaway database, logs in as admin/reporter/photographer,
    creates and updates an activity, exercises the API, and checks report pages.
    Restores the repository to a clean state afterwards.
#>
param(
    [int]$Port = 8090,
    [string]$DbName = 'ipna_pms_smoke',
    [string]$DbUser = 'root',
    [string]$DbPass = '',
    [string]$AdminUser = 'smokeadmin',
    [string]$AdminPass = 'SmokePass123!'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$php = 'C:\xampp\php\php.exe'
$mysql = 'C:\xampp\mysql\bin\mysql.exe'
$baseUrl = "http://127.0.0.1:$Port"
$router = Join-Path $root 'tests\router.php'

function Write-Step($msg) {
    Write-Host "`n[TEST] $msg" -ForegroundColor Cyan
}

function Invoke-Req($Method, $Uri, $Body = $null, $Session = $null, [switch]$AllowRedirect) {
    $opts = @{
        Uri              = $Uri
        Method           = $Method
        UseBasicParsing  = $true
    }
    if ($Body) { $opts.Body = $Body }
    if ($Session) { $opts.WebSession = $Session }
    if (-not $AllowRedirect) { $opts.MaximumRedirection = 0 }
    return Invoke-WebRequest @opts
}

function Get-CsrfToken($html) {
    if ($html -match '<meta\s+name="csrf-token"\s+content="([^"]+)"') { return $Matches[1] }
    if ($html -match 'name="csrf_token"\s+value="([^"]+)"') { return $Matches[1] }
    return ''
}

function ConvertFrom-JsonSafe {
    [CmdletBinding()]
    param (
        [Parameter(ValueFromPipeline = $true)]
        [AllowEmptyString()]
        [string]$Text
    )
    # Strip a leading UTF-8 BOM that the PHP built-in server may include.
    return ($Text.TrimStart([char]0xFEFF)) | ConvertFrom-Json
}

# Backup installer protection files (the installer rewrites these).
$htaccessOrig = Join-Path $root 'install\.htaccess'
$webconfigOrig = Join-Path $root 'install\web.config'
$htaccessBackup = Join-Path $root 'install\.htaccess.smokebak'
$webconfigBackup = Join-Path $root 'install\web.config.smokebak'
Copy-Item $htaccessOrig $htaccessBackup -Force -ErrorAction SilentlyContinue
Copy-Item $webconfigOrig $webconfigBackup -Force -ErrorAction SilentlyContinue

# Clean any leftovers from a previous aborted run.
Remove-Item (Join-Path $root 'config\config.php') -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $root 'config\database.php') -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $root '.installed') -Force -ErrorAction SilentlyContinue
& $mysql -u $DbUser --password=$DbPass -e "DROP DATABASE IF EXISTS $DbName;" 2>$null | Out-Null

# Start PHP development server.
$proc = Start-Process -FilePath $php -ArgumentList '-S', "127.0.0.1:$Port", '-t', $root, $router -PassThru -WindowStyle Hidden
Start-Sleep -Seconds 2

try {
    $session = $null

    Write-Step 'Fresh clone routes to installer'
    $r = Invoke-Req GET "$baseUrl/" -AllowRedirect
    if ($r.StatusCode -ne 200 -or $r.Content -notmatch 'dbHost') { throw 'Installer page not shown' }

    Write-Step 'Installer DB connection test'
    $r = Invoke-Req POST "$baseUrl/install/process.php" -Body @{
        action   = 'test-db'
        host     = 'localhost'
        dbname   = $DbName
        username = $DbUser
        password = $DbPass
    }
    $j = $r.Content | ConvertFrom-JsonSafe
    if (-not $j.success) { throw "test-db failed: $($j.message)" }

    Write-Step 'Run full installer'
    $r = Invoke-Req POST "$baseUrl/install/process.php" -Body @{
        action    = 'install'
        host      = 'localhost'
        dbname    = $DbName
        username  = $DbUser
        password  = $DbPass
        prefix    = ''
        adminName = 'Smoke Admin'
        adminEmail = 'smoke@ipna.local'
        adminUser = $AdminUser
        adminPass = $AdminPass
        baseUrl   = ''
        siteName  = 'IPNA Smoke Test'
        siteUrl   = ''
    }
    $j = $r.Content | ConvertFrom-JsonSafe
    if (-not $j.success) { throw "install failed: $($j.message)" }

    Write-Step 'Login as admin'
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginPage = Invoke-Req GET "$baseUrl/login" -Session $session -AllowRedirect
    $token = Get-CsrfToken $loginPage.Content
    $r = Invoke-Req POST "$baseUrl/login" -Session $session -Body @{
        csrf_token = $token
        username   = $AdminUser
        password   = $AdminPass
    } -AllowRedirect
    if ($r.StatusCode -ne 200 -and $r.StatusCode -ne 302) { throw 'Admin login failed' }

    Write-Step 'Admin dashboard'
    $r = Invoke-Req GET "$baseUrl/admin" -Session $session -AllowRedirect
    if ($r.StatusCode -ne 200) { throw 'Admin dashboard failed' }

    Write-Step 'Admin users list'
    $r = Invoke-Req GET "$baseUrl/admin/users" -Session $session -AllowRedirect
    if ($r.StatusCode -ne 200) { throw 'Admin users list failed' }

    Write-Step 'Admin activities list'
    $r = Invoke-Req GET "$baseUrl/admin/activities" -Session $session -AllowRedirect
    if ($r.StatusCode -ne 200) { throw 'Admin activities list failed' }

    Write-Step 'Monthly report page'
    $r = Invoke-Req GET "$baseUrl/admin/reports/monthly" -Session $session -AllowRedirect
    if ($r.StatusCode -ne 200) { throw 'Monthly report page failed' }

    Write-Step 'Create reporter user'
    $token = Get-CsrfToken $r.Content
    $r = Invoke-Req POST "$baseUrl/admin/users/store" -Session $session -Body @{
        csrf_token = $token
        name       = 'Smoke Reporter'
        username   = 'smoke_reporter'
        email      = 'reporter@ipna.local'
        password   = 'SmokePass123!'
        role       = 'reporter'
        position   = 'خبرنگار تست'
        status     = 'active'
    } -AllowRedirect
    if ($r.StatusCode -ne 200 -and $r.StatusCode -ne 302) { throw 'Create reporter failed' }

    Write-Step 'Login as reporter'
    $reporterSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginPage = Invoke-Req GET "$baseUrl/login" -Session $reporterSession -AllowRedirect
    $token = Get-CsrfToken $loginPage.Content
    $r = Invoke-Req POST "$baseUrl/login" -Session $reporterSession -Body @{
        csrf_token = $token
        username   = 'smoke_reporter'
        password   = 'SmokePass123!'
    } -AllowRedirect
    if ($r.StatusCode -ne 200 -and $r.StatusCode -ne 302) { throw 'Reporter login failed' }

    Write-Step 'Reporter activities page'
    $r = Invoke-Req GET "$baseUrl/reporter/activities" -Session $reporterSession -AllowRedirect
    if ($r.StatusCode -ne 200) { throw 'Reporter activities page failed' }

    Write-Step 'Create reporter activity'
    $token = Get-CsrfToken $r.Content
    $today = Get-Date -Format 'yyyy/MM/dd'
    $r = Invoke-Req POST "$baseUrl/reporter/activities/store" -Session $reporterSession -Body @{
        csrf_token       = $token
        date             = $today
        activity_type_id = 1
        quantity         = 3
        title            = 'Smoke news'
        link             = ''
        is_mission       = 0
        description      = ''
    } -AllowRedirect
    if ($r.StatusCode -ne 200 -and $r.StatusCode -ne 302) { throw 'Create reporter activity failed' }

    Write-Step 'Find activity in reporter list'
    $r = Invoke-Req GET "$baseUrl/reporter/activities" -Session $reporterSession -AllowRedirect
    if ($r.Content -notmatch '/reporter/activities/(\d+)/edit') { throw 'Created activity not found in list' }
    $activityId = $Matches[1]

    Write-Step "Update reporter activity $activityId"
    $token = Get-CsrfToken $r.Content
    $r = Invoke-Req POST "$baseUrl/reporter/activities/$activityId/update" -Session $reporterSession -Body @{
        csrf_token       = $token
        date             = $today
        activity_type_id = 2
        quantity         = 5
        title            = 'Smoke exclusive'
        link             = ''
        is_mission       = 1
        description      = 'updated'
    } -AllowRedirect
    if ($r.StatusCode -ne 200 -and $r.StatusCode -ne 302) { throw 'Update reporter activity failed' }

    Write-Step 'API activities (no filter)'
    $api = Invoke-Req GET "$baseUrl/api/activities?limit=10" -Session $reporterSession -AllowRedirect
    $apiJ = $api.Content | ConvertFrom-JsonSafe
    if (-not $apiJ.success) { throw 'API activities (no filter) failed' }

    Write-Step 'API activities with valid activity_type_id filter'
    $api = Invoke-Req GET "$baseUrl/api/activities?activity_type_id=2&limit=10" -Session $reporterSession -AllowRedirect
    $apiJ = $api.Content | ConvertFrom-JsonSafe
    if (-not $apiJ.success) { throw 'API activities filter failed' }

    Write-Step 'API activities with non-existing activity_type_id filter'
    $api = Invoke-Req GET "$baseUrl/api/activities?activity_type_id=99999&limit=10" -Session $reporterSession -AllowRedirect
    $apiJ = $api.Content | ConvertFrom-JsonSafe
    if (-not $apiJ.success) { throw 'API activities non-existing filter failed' }

    Write-Step 'Create photographer user as admin'
    $adminSession2 = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginPage = Invoke-Req GET "$baseUrl/login" -Session $adminSession2 -AllowRedirect
    $token = Get-CsrfToken $loginPage.Content
    $r = Invoke-Req POST "$baseUrl/login" -Session $adminSession2 -Body @{
        csrf_token = $token
        username   = $AdminUser
        password   = $AdminPass
    } -AllowRedirect
    $token = Get-CsrfToken $r.Content
    $r = Invoke-Req POST "$baseUrl/admin/users/store" -Session $adminSession2 -Body @{
        csrf_token = $token
        name       = 'Smoke Photographer'
        username   = 'smoke_photographer'
        email      = 'photo@ipna.local'
        password   = 'SmokePass123!'
        role       = 'photographer'
        position   = 'عکاس تست'
        status     = 'active'
    } -AllowRedirect
    if ($r.StatusCode -ne 200 -and $r.StatusCode -ne 302) { throw 'Create photographer failed' }

    Write-Step 'Login as photographer'
    $photoSession = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginPage = Invoke-Req GET "$baseUrl/login" -Session $photoSession -AllowRedirect
    $token = Get-CsrfToken $loginPage.Content
    $r = Invoke-Req POST "$baseUrl/login" -Session $photoSession -Body @{
        csrf_token = $token
        username   = 'smoke_photographer'
        password   = 'SmokePass123!'
    } -AllowRedirect
    if ($r.StatusCode -ne 200 -and $r.StatusCode -ne 302) { throw 'Photographer login failed' }

    Write-Step 'Photographer activities page'
    $r = Invoke-Req GET "$baseUrl/photographer/activities" -Session $photoSession -AllowRedirect
    if ($r.StatusCode -ne 200) { throw 'Photographer activities page failed' }

    Write-Host "`nAll smoke tests passed." -ForegroundColor Green
    exit 0
}
catch {
    Write-Host "`nSMOKE TEST FAILED: $_" -ForegroundColor Red
    exit 1
}
finally {
    if ($proc -and -not $proc.HasExited) { $proc | Stop-Process -Force }

    # Restore installer protection files.
    if (Test-Path $htaccessBackup) { Move-Item $htaccessBackup $htaccessOrig -Force }
    if (Test-Path $webconfigBackup) { Move-Item $webconfigBackup $webconfigOrig -Force }

    # Remove generated config/marker and the throwaway database.
    Remove-Item (Join-Path $root 'config\config.php') -Force -ErrorAction SilentlyContinue
    Remove-Item (Join-Path $root 'config\database.php') -Force -ErrorAction SilentlyContinue
    Remove-Item (Join-Path $root '.installed') -Force -ErrorAction SilentlyContinue
    & $mysql -u $DbUser --password=$DbPass -e "DROP DATABASE IF EXISTS $DbName;" 2>$null | Out-Null
}
