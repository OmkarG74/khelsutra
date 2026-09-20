# KhelSutra Project Setup Script (PowerShell)
param(
    [string]$DbUser = "root",
    [string]$DbPass = ""
)

Write-Host "=== KhelSutra Project Initialization ===" -ForegroundColor Cyan

# 1. Check environment file
if (-not (Test-Path "backend\.env")) {
    Write-Host "Creating backend/.env from .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" "backend\.env"
}

# 2. Check Database Connectivity & Seed
Write-Host "Initializing MySQL Database..." -ForegroundColor Yellow
$mysqlCmd = "C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe"
if (Test-Path $mysqlCmd) {
    cmd /c "$mysqlCmd -u $DbUser khelsutra < database\sports_management_database.sql"
    cmd /c "$mysqlCmd -u $DbUser khelsutra < database\seeds\roles.sql"
    cmd /c "$mysqlCmd -u $DbUser khelsutra < database\seeds\permissions.sql"
    cmd /c "$mysqlCmd -u $DbUser khelsutra < database\seeds\reference-data.sql"
    Write-Host "Database schema and seeds loaded successfully." -ForegroundColor Green
} else {
    Write-Host "MySQL CLI not found at default path. Ensure khelsutra DB is created and seeded." -ForegroundColor Red
}

Write-Host "Setup Complete!" -ForegroundColor Green
