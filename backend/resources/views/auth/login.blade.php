<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — KhelSutra Sports Platform</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- KhelSutra Master Design System Stylesheet -->
    <link rel="stylesheet" href="/assets/css/khelsutra-design-system.css">
    <style>
        body {
            background-color: var(--ks-page-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            color: var(--ks-text);
            padding: 24px;
        }
        .ks-login-card {
            background: var(--ks-card-bg);
            border: 1px solid var(--ks-border);
            border-radius: var(--ks-radius-modal);
            box-shadow: var(--ks-shadow-card);
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
        }
        .ks-brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        .ks-brand-icon {
            color: var(--ks-gold);
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="ks-login-card">
        <div class="ks-brand-header">
            <div class="ks-brand-icon">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/>
                </svg>
            </div>
            <div>
                <h2 class="fw-bold text-navy mb-0" style="font-size: 24px; letter-spacing: -0.02em;">KhelSutra</h2>
                <div class="small text-muted fw-medium">Sports Academy</div>
            </div>
        </div>

        <div class="mb-4 text-center">
            <h3 class="fw-bold text-navy" style="font-size: 18px;">Sign in to Portal</h3>
            <p class="small text-muted mb-0">Enter your credentials to access operations dashboard</p>
        </div>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill text-danger"></i>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div class="mb-3">
                <label class="ks-form-label">Email Address *</label>
                <input type="email" class="ks-form-control" name="email" value="<?= htmlspecialchars($_GET['email'] ?? 'sportsadmin@khelsutra.local') ?>" required placeholder="name@example.com">
            </div>

            <div class="mb-3">
                <label class="ks-form-label">Password *</label>
                <input type="password" class="ks-form-control" name="password" required placeholder="••••••••">
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" checked>
                    <label class="form-check-label small text-muted" for="rememberMe">Remember this browser</label>
                </div>
                <a href="#" class="small text-primary text-decoration-none fw-medium">Forgot password?</a>
            </div>

            <button type="submit" class="ks-btn ks-btn-primary w-100 py-2 justify-content-center" style="height: 44px;">
                <span>Sign In to Dashboard</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-muted small" style="border-color: var(--ks-border-light) !important;">
            Secured Multi-Tenant RBAC Platform
        </div>
    </div>
</body>
</html>
