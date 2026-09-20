<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'KhelSutra — Sports Management Platform') ?></title>
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
</head>
<body>
    <div class="ks-app-layout">
        <!-- Sidebar Component -->
        <?php include __DIR__ . '/../components/sidebar.blade.php'; ?>

        <!-- Main Workspace -->
        <div class="ks-main-wrapper">
            <!-- Header Component -->
            <?php include __DIR__ . '/../components/navbar.blade.php'; ?>

            <!-- Page Body -->
            <main class="ks-page-body">
                <?= $slot ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Master Layout Mobile & Interactive Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.getElementById('ksMobileToggle');
            const sidebar = document.getElementById('ksSidebar');
            const overlay = document.getElementById('ksSidebarOverlay');

            if (toggle && sidebar && overlay) {
                toggle.addEventListener('click', () => {
                    sidebar.classList.toggle('ks-open');
                    overlay.classList.toggle('ks-open');
                });
                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('ks-open');
                    overlay.classList.remove('ks-open');
                });
            }
        });
    </script>
</body>
</html>
