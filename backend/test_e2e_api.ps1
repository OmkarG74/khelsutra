$athleteBody = @{
    email = "athlete@khelsutra.local"
    password = "KhelSutra@123"
} | ConvertTo-Json

$athleteLogin = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -ContentType "application/json" -Body $athleteBody
Write-Host "=== ATHLETE LOGIN ==="
Write-Host "Token: $($athleteLogin.data.token)"
Write-Host "User ID: $($athleteLogin.data.user.id)"
Write-Host "Athlete ID: $($athleteLogin.data.user.athlete_id)"
Write-Host "Role: $($athleteLogin.data.user.roles[0].name)"

$aHeader = @{ Authorization = "Bearer $($athleteLogin.data.token)" }

# 1. Profile
$athProfile = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/athletes/$($athleteLogin.data.user.athlete_id)" -Headers $aHeader
Write-Host "Athlete Name: $($athProfile.data.first_name) $($athProfile.data.last_name)"
Write-Host "Athlete Code: $($athProfile.data.athlete_code)"
Write-Host "Athlete Sport: $($athProfile.data.sport_name)"

# 2. Training
$athTraining = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/training-sessions?athlete_id=$($athleteLogin.data.user.athlete_id)" -Headers $aHeader
Write-Host "Athlete Training Sessions Count: $($athTraining.data.Count)"

# 3. Attendance
$athAttendance = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/attendance/training/history?athlete_id=$($athleteLogin.data.user.athlete_id)" -Headers $aHeader
Write-Host "Athlete Attendance History Count: $($athAttendance.data.Count)"

# 4. Performance
$athPerformance = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/performance?athlete_id=$($athleteLogin.data.user.athlete_id)" -Headers $aHeader
Write-Host "Athlete Performance Records: $($athPerformance.data.Count)"

# 5. Achievements
$athAchievements = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/achievements?athlete_id=$($athleteLogin.data.user.athlete_id)" -Headers $aHeader
Write-Host "Athlete Achievements Count: $($athAchievements.data.Count)"

# 6. Notifications
$athNotifications = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/notifications" -Headers $aHeader
Write-Host "Athlete Notifications Count: $($athNotifications.data.Count)"

Write-Host "`n=== COACH LOGIN ==="
$coachBody = @{
    email = "coach@khelsutra.local"
    password = "KhelSutra@123"
} | ConvertTo-Json

$coachLogin = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method Post -ContentType "application/json" -Body $coachBody
Write-Host "Coach Token: $($coachLogin.data.token)"
Write-Host "Coach ID: $($coachLogin.data.user.coach_id)"
Write-Host "Employee ID: $($coachLogin.data.user.employee_id)"
Write-Host "Role: $($coachLogin.data.user.roles[0].name)"

$cHeader = @{ Authorization = "Bearer $($coachLogin.data.token)" }

# 1. Coach Profile
$cProfile = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/coaches/$($coachLogin.data.user.coach_id)" -Headers $cHeader
Write-Host "Coach Name: $($cProfile.data.first_name) $($cProfile.data.last_name)"
Write-Host "Coach Teams Count: $($cProfile.data.teams.Count)"

# 2. Coach Dashboard
$cDashboard = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/coaches/dashboard" -Headers $cHeader
Write-Host "Coach Dashboard Athletes Count: $($cDashboard.data.athletes_count)"
Write-Host "Coach Dashboard Teams Count: $($cDashboard.data.teams_count)"
Write-Host "Coach Dashboard Attendance Rate: $($cDashboard.data.attendance_rate)%"

# 3. Coach Athletes
$cAthletes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/coaches/athletes" -Headers $cHeader
Write-Host "Coach Athletes List Count: $($cAthletes.data.Count)"

# 4. Performance Metrics
$metrics = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/v1/performance/metrics" -Headers $cHeader
Write-Host "Performance Metrics Available: $($metrics.data.Count)"
