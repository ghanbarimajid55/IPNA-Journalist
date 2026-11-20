#Requires -Version 5.1
<##
.SYNOPSIS
    Full E2E test for an installed IPNA PMS.
.DESCRIPTION
    Exercises admin, reporter, photographer, CRUD, reports, APIs, CSRF and access control.
    JSON endpoints are explicitly requested as XMLHttpRequest so controller contracts are tested
    instead of accidentally parsing normal HTML redirects.
#>
param(
    [string]$BaseUrl = 'http://ipna.local',
    [Parameter(Mandatory=$true)][string]$AdminUser,
    [Parameter(Mandatory=$true)][string]$AdminPass
)

$ErrorActionPreference = 'Stop'
$BaseUrl = $BaseUrl.TrimEnd('/')
$passed = 0; $failed = 0

function Test-Step([string]$Name, [scriptblock]$Action) {
    try {
        & $Action
        Write-Host "[PASS] $Name" -ForegroundColor Green
        $script:passed++
    } catch {
        Write-Host "[FAIL] $Name :: $($_.Exception.Message)" -ForegroundColor Red
        $script:failed++
    }
}

function Request($Session, [string]$Method, [string]$Path, $Body=$null, [int]$ExpectedMin=200, [int]$ExpectedMax=399, [hashtable]$Headers=$null) {
    $uri = if ($Path -match '^https?://') { $Path } else { "$BaseUrl$Path" }
    $opts = @{ Uri=$uri; Method=$Method; WebSession=$Session; UseBasicParsing=$true; MaximumRedirection=5 }
    if ($null -ne $Body) { $opts.Body=$Body }
    if ($null -ne $Headers) { $opts.Headers=$Headers }
    try {
        $r = Invoke-WebRequest @opts
    } catch {
        if ($_.Exception.Response) {
            $resp=$_.Exception.Response
            try { $code=[int]$resp.StatusCode } catch { throw $_ }
            if ($code -ge $ExpectedMin -and $code -le $ExpectedMax) { return $resp }
        }
        throw
    }
    $code=[int]$r.StatusCode
    if ($code -lt $ExpectedMin -or $code -gt $ExpectedMax) { throw "HTTP $code for $Method $Path" }
    return $r
}

function Csrf($Html) {
    if ($Html -match 'name="csrf_token" value="([^"]+)"') { return $Matches[1] }
    if ($Html -match '<meta\s+name="csrf-token"\s+content="([^"]+)"') { return $Matches[1] }
    throw 'CSRF token not found'
}

function Json($Response) {
    $text = if ($Response.Content) { [string]$Response.Content } else { [IO.StreamReader]::new($Response.GetResponseStream()).ReadToEnd() }
    $text = $text.TrimStart([char]0xFEFF).Trim()
    if ([string]::IsNullOrWhiteSpace($text)) { throw 'Expected JSON but response body is empty' }
    try { return ($text | ConvertFrom-Json) } catch { throw "Expected JSON but received: $($text.Substring(0,[Math]::Min(180,$text.Length)))" }
}

$xhr = @{ 'X-Requested-With' = 'XMLHttpRequest'; 'Accept' = 'application/json' }

Write-Host "`n=== IPNA FULL E2E TEST ===" -ForegroundColor Cyan
Write-Host "Base URL: $BaseUrl"

Test-Step 'HTTP root is reachable' {
    $r=Request (New-Object Microsoft.PowerShell.Commands.WebRequestSession) GET '/'
    if ([int]$r.StatusCode -ge 500) { throw 'root returned 5xx' }
}

$admin = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$loginPage = $null
Test-Step 'Admin login page' { $script:loginPage=Request $admin GET '/login'; if ($script:loginPage.Content -notmatch 'loginForm|name="password"') { throw 'login form missing' } }
Test-Step 'Admin login' {
    if ([string]::IsNullOrWhiteSpace($AdminUser) -or [string]::IsNullOrWhiteSpace($AdminPass)) { throw 'Admin credentials were empty.' }
    $token=Csrf $loginPage.Content
    Request $admin POST '/login' @{csrf_token=$token;username=$AdminUser;password=$AdminPass} | Out-Null
    $adminHome=Request $admin GET '/admin'
    if ($adminHome.Content -match 'loginForm|name="password"') { throw 'Admin login failed; session was not authenticated.' }
}

$adminPages=@('/admin','/admin/activities','/admin/team','/admin/users','/admin/targets','/admin/activity-types','/admin/reports/daily','/admin/reports/weekly','/admin/reports/monthly','/admin/import','/admin/settings')
foreach($p in $adminPages){ Test-Step "Admin GET $p" { $r=Request $admin GET $p; if([int]$r.StatusCode -ge 500){throw '5xx'} } }

$ranking=$null
Test-Step 'API team ranking' { $r=Request $admin GET '/api/team-ranking' $null 200 299 $xhr; $script:ranking=Json $r; if(-not $script:ranking.success){throw 'success=false'} }
Test-Step 'API dashboard stats' { $r=Request $admin GET '/api/dashboard-stats' $null 200 299 $xhr; $j=Json $r; if(-not $j.success){throw 'success=false'} }
Test-Step 'API activities' { $r=Request $admin GET '/api/activities?limit=10' $null 200 299 $xhr; $j=Json $r; if(-not $j.success){throw 'success=false'} }

$testReporter=$null; $testPhotographer=$null
if($ranking -and $ranking.ranking){ foreach($p in @($ranking.ranking)){ if(-not $testReporter -and $p.role -eq 'reporter'){$testReporter=$p}; if(-not $testPhotographer -and $p.role -eq 'photographer'){$testPhotographer=$p} } }
if(-not $testReporter){ $testReporter=@{user_id=1;role='reporter'} }
if(-not $testPhotographer){ $testPhotographer=@{user_id=2;role='photographer'} }

$activityId=$null
Test-Step 'Admin create activity' {
    $page=Request $admin GET '/admin/activities/create'; $token=Csrf $page.Content
    $body=@{csrf_token=$token;user_id=[int]$testReporter.user_id;activity_type_id=1;quantity=2;title='E2E Test Activity';date=(Get-Date -Format 'yyyy/MM/dd');link='';is_mission=0;description='Automated E2E test'}
    $r=Request $admin POST '/admin/activities/store' $body 200 299 $xhr; $j=Json $r
    if(-not $j.success -or -not $j.id){throw 'activity create did not return success/id'}
    $script:activityId=[int]$j.id
}
if($activityId){
    Test-Step 'Admin edit activity' { $page=Request $admin GET "/admin/activities/$activityId/edit"; $token=Csrf $page.Content; Request $admin POST "/admin/activities/$activityId/update" @{csrf_token=$token;activity_type_id=2;quantity=3;title='E2E Test Activity Updated';date=(Get-Date -Format 'yyyy/MM/dd');link='';is_mission=1;description='updated'} $null 200 299 $xhr | Out-Null }
    Test-Step 'Admin delete activity' { $page=Request $admin GET '/admin/activities'; $token=Csrf $page.Content; Request $admin POST "/admin/activities/$activityId/delete" @{csrf_token=$token;_method='DELETE'} $null 200 299 $xhr | Out-Null }
}

$activityTypeId=$null
$uniqueCode='e2e_test_' + (Get-Date -Format 'HHmmss')
Test-Step 'Activity type create' { $page=Request $admin GET '/admin/activity-types'; $token=Csrf $page.Content; $r=Request $admin POST '/admin/activity-types/store' @{csrf_token=$token;name='E2E Test Type';code=$uniqueCode;score=7;category='news'} $null 200 299 $xhr; $j=Json $r; if(-not $j.success -or -not $j.id){throw 'activity type create failed'}; $script:activityTypeId=[int]$j.id }
if($activityTypeId){
    Test-Step 'Activity type update score/status' { $page=Request $admin GET '/admin/activity-types'; $token=Csrf $page.Content; Request $admin POST "/admin/activity-types/update-score/$activityTypeId" @{csrf_token=$token;score=8} $null 200 299 $xhr | Out-Null; Request $admin POST "/admin/activity-types/toggle-status/$activityTypeId" @{csrf_token=$token;is_active=0} $null 200 299 $xhr | Out-Null }
    Test-Step 'Activity type deactivate/delete' { $page=Request $admin GET '/admin/activity-types'; $token=Csrf $page.Content; Request $admin POST "/admin/activity-types/$activityTypeId/delete" @{csrf_token=$token;_method='DELETE'} $null 200 299 $xhr | Out-Null }
}

Test-Step 'Monthly target save' { $page=Request $admin GET '/admin/targets'; $token=Csrf $page.Content; $r=Request $admin POST '/admin/targets/store' @{csrf_token=$token;user_id=[int]$testReporter.user_id;year=(Get-Date).Year;month=(Get-Date).Month;news_target=40;exclusive_news_target=8;interview_target=4;special_report_target=3;photo_target=0;gallery_target=0;video_target=5;score_target=90}; if([int]$r.StatusCode -ge 500){throw 'target save 5xx'} }
Test-Step 'Settings update' { $page=Request $admin GET '/admin/settings'; $token=Csrf $page.Content; Request $admin POST '/admin/settings/update' @{csrf_token=$token;report_page_size='20'} | Out-Null }
Test-Step 'Import page and CSV contract' { $page=Request $admin GET '/admin/import'; if($page.Content -notmatch 'CSV'){throw 'CSV import UI missing'}; if($page.Content -match 'xlsx|xls'){throw 'Unsupported Excel formats are still advertised'} }

$reporter=New-Object Microsoft.PowerShell.Commands.WebRequestSession
Test-Step 'Reporter login' { $p=Request $reporter GET '/login'; $t=Csrf $p.Content; Request $reporter POST '/login' @{csrf_token=$t;username='ipna_test_ali_rezaei';password='Test@12345'} | Out-Null; $reporterHome=Request $reporter GET '/reporter/activities'; if ($reporterHome.Content -match 'loginForm|name="password"') { throw 'Reporter login failed; test user may be missing or inactive.' } }
foreach($p in @('/dashboard','/reporter/activities','/reporter/targets','/reporter/reports/weekly','/reporter/reports/monthly')){ Test-Step "Reporter GET $p" { $r=Request $reporter GET $p; if([int]$r.StatusCode -ge 500){throw '5xx'} } }
Test-Step 'Reporter API is scoped to own activities (IDOR test)' { $r=Request $reporter GET "/api/activities?user_id=$([int]$testPhotographer.user_id)&limit=100" $null 200 299 $xhr; $j=Json $r; foreach($a in @($j.activities)){ if([int]$a.user_id -ne [int]$testReporter.user_id){ throw 'IDOR: foreign activity returned' } } }

$photo=New-Object Microsoft.PowerShell.Commands.WebRequestSession
Test-Step 'Photographer login' { $p=Request $photo GET '/login'; $t=Csrf $p.Content; Request $photo POST '/login' @{csrf_token=$t;username='ipna_test_pouya_rahimi';password='Test@12345'} | Out-Null; $photographerHome=Request $photo GET '/photographer/activities'; if ($photographerHome.Content -match 'loginForm|name="password"') { throw 'Photographer login failed; test user may be missing or inactive.' } }
foreach($p in @('/dashboard','/photographer/activities','/photographer/targets','/photographer/reports/weekly','/photographer/reports/monthly')){ Test-Step "Photographer GET $p" { $r=Request $photo GET $p; if([int]$r.StatusCode -ge 500){throw '5xx'} } }

Test-Step 'Invalid CSRF is rejected' { Request $admin POST '/admin/settings/update' @{csrf_token='INVALID';report_page_size='20'} 403 403 | Out-Null }

Write-Host "`n=== RESULT ===" -ForegroundColor Cyan
Write-Host "PASS: $passed" -ForegroundColor Green
Write-Host "FAIL: $failed" -ForegroundColor Red
if($failed -gt 0){ exit 1 }; exit 0
