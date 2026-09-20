# KhelSutra Development Server Launcher
Write-Host "Starting KhelSutra Laravel Backend Development Server on http://127.0.0.1:8000..." -ForegroundColor Cyan
Push-Location "backend"
php -S 127.0.0.1:8000 -t public
Pop-Location
