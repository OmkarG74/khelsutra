<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — KhelSutra Sports ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border-radius: 0.75rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary mb-1"><i class="bi bi-trophy-fill text-warning me-2"></i>KhelSutra</h3>
            <p class="text-muted small">Multi-Organisation Sports Management Platform</p>
        </div>

        <form action="/dashboard" method="GET">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Email or Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="text" class="form-control" name="email" value="admin@khelsutra.com" required placeholder="name@example.com">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" name="password" value="SecretPassword123" required placeholder="••••••••">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Organisation Code (Optional)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                    <input type="text" class="form-control" name="organization_code" value="ORG-DEMO" placeholder="e.g. ORG-DEMO">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" checked>
                    <label class="form-check-label small" for="rememberMe">Remember me</label>
                </div>
                <a href="#forgot" class="small text-decoration-none">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                Sign In to Portal
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-muted small">
            Secured by Multi-Tenant Access Control & RBAC
        </div>
    </div>
</body>
</html>
