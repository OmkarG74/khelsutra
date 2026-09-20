# KhelSutra Database Migration Script (PowerShell)
param(
    [string]$DbUser = "root"
)

$mysqlCmd = "C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe"
if (-not (Test-Path $mysqlCmd)) {
    $mysqlCmd = "mysql"
}

Write-Host "Reloading KhelSutra Schema & Seeds..." -ForegroundColor Cyan
cmd /c "$mysqlCmd -u $DbUser khelsutra < database\sports_management_database.sql"
cmd /c "$mysqlCmd -u $DbUser khelsutra < database\seeds\roles.sql"
cmd /c "$mysqlCmd -u $DbUser khelsutra < database\seeds\permissions.sql"
cmd /c "$mysqlCmd -u $DbUser khelsutra < database\seeds\reference-data.sql"
Write-Host "Done!" -ForegroundColor Green
