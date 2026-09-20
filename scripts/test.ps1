# KhelSutra Test Runner Script (PowerShell)
Write-Host "Running PHP Syntax Checks across Backend..." -ForegroundColor Cyan
$phpFiles = Get-ChildItem -Path "backend" -Filter "*.php" -Recurse
$hasError = $false
foreach ($file in $phpFiles) {
    $res = php -l $file.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "SYNTAX ERROR: $($file.FullName)" -ForegroundColor Red
        Write-Host $res -ForegroundColor Red
        $hasError = $true
    }
}
if (-not $hasError) {
    Write-Host "All PHP files passed syntax analysis!" -ForegroundColor Green
}

Write-Host "Running Flutter Static Analysis..." -ForegroundColor Cyan
Push-Location "mobile"
flutter analyze
Pop-Location

Write-Host "Tests Complete." -ForegroundColor Green
