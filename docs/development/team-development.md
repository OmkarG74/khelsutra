# KhelSutra Team Development Architecture & Ownership Guide

## Overview & Purpose
This document defines the strict separation of concerns between **Shared / Core Infrastructure** and **Independent Feature Modules** within the KhelSutra application. This design prevents merge conflicts and regressions when multiple developers collaborate simultaneously on different functional areas.

---

## 1. Architectural Boundaries

```
c:\wamp64\www\KhelSutra\
├── backend\
│   ├── app\
│   │   ├── Helpers\          <-- [SHARED CORE] Auth, Context, View utilities
│   │   ├── Middleware\       <-- [SHARED CORE] Session, Auth, CSRF, RBAC
│   │   ├── Services\
│   │   │   ├── BaseService.php <-- [SHARED CORE] Base tenant-aware DB abstraction
│   │   │   ├── Auth\         <-- [SHARED CORE] Central authentication
│   │   │   ├── RBAC\         <-- [SHARED CORE] Roles & Permissions
│   │   │   ├── Audit\        <-- [SHARED CORE] Audit Logging
│   │   │   ├── Athletes\     <-- [MODULE] Athletes feature
│   │   │   ├── Coaches\      <-- [MODULE] Coaches feature
│   │   │   ├── Teams\        <-- [MODULE] Teams feature
│   │   │   ├── Training\     <-- [MODULE] Training sessions & attendance
│   │   │   ├── Tournaments\  <-- [MODULE] Competitions & fixtures
│   │   │   ├── Venues\       <-- [MODULE] Facilities & bookings
│   │   │   ├── Inventory\    <-- [MODULE] Equipment & stock tracking
│   │   │   ├── Employees\    <-- [MODULE] Staff & Employee management
│   │   │   ├── Leave\        <-- [MODULE] Leave requests & approvals
│   │   │   ├── Payroll\      <-- [MODULE] Payroll calculation & disbursement
│   │   │   └── Reports\      <-- [MODULE] Reports & Analytics
│   ├── resources\views\
│   │   ├── layouts\app.blade.php  <-- [SHARED CORE] Master layout shell (Header, Sidebar, Navbar, Footer)
│   │   ├── components\            <-- [SHARED CORE] Shared UI partials & navigation
│   │   ├── sports\                <-- [MODULE] Athletes, Coaches, Teams, Training views
│   │   ├── competitions\          <-- [MODULE] Tournaments & fixtures views
│   │   ├── venues\                <-- [MODULE] Venues & booking views
│   │   ├── inventory\             <-- [MODULE] Inventory & items views
│   │   ├── hr\                    <-- [MODULE] Employees, Leave, Payroll views
│   │   ├── reports\               <-- [MODULE] Operational report views
│   │   └── settings\              <-- [MODULE] Organization settings view
│   ├── public\
│   │   ├── css\                   <-- [SHARED CORE] Master design system tokens & styles
│   │   ├── js\                    <-- [SHARED CORE] Master application scripts & CSRF handling
│   │   └── index.php              <-- [SHARED CORE] Central bootstrap & router
│   └── routes\
│       ├── web.php                <-- [SHARED CORE / CO-OWNED] Route registration
│       └── web_actions.php        <-- [SHARED CORE / CO-OWNED] POST action handlers
```

---

## 2. File Ownership Rules

### A. Shared / Core (Protected)
The following components are **Core Platform Assets**. Individual feature developers must **never** modify these files to fulfill module-specific requirements:

1. **Master Shell Layout**: `backend/resources/views/layouts/app.blade.php`
   - Governs `<html>`, `<head>`, CSS asset inclusion, top navigation bar, sidebar drawer, flash notifications, and footer.
2. **Navigation Components**:
   - `backend/resources/views/components/sidebar.blade.php`
   - `backend/resources/views/components/navbar.blade.php`
   - `backend/resources/views/components/header.blade.php`
   - `backend/resources/views/components/footer.blade.php`
3. **Core CSS / JS**:
   - `backend/public/css/style.css` (Design tokens, layout grid, typography, cards, tables, badges)
   - `backend/public/js/app.js` (CSRF token attachment, modal management, toasts)
4. **Authentication & Tenant Context**:
   - `backend/app/Helpers/AuthContext.php` (`current_organization_id()`, `current_user_id()`, `current_role_id()`)
   - `backend/app/Services/Auth/AuthService.php`
   - `backend/app/Services/RBAC/RBACService.php`
   - `backend/app/Services/BaseService.php`

### B. Feature Modules (Module-Owned)
Developers working on individual feature modules own their respective controllers/actions, services, and view files:

| Feature Area | Module Views Directory | Module Service Classes |
| :--- | :--- | :--- |
| **Athletes** | `backend/resources/views/sports/athletes*.blade.php` | `App\Services\Athletes\AthleteService` |
| **Coaches** | `backend/resources/views/sports/coaches*.blade.php` | `App\Services\Coaches\CoachService` |
| **Teams** | `backend/resources/views/sports/teams*.blade.php` | `App\Services\Teams\TeamService` |
| **Training** | `backend/resources/views/sports/training*.blade.php` | `App\Services\Training\TrainingService` |
| **Tournaments** | `backend/resources/views/competitions/tournaments*.blade.php` | `App\Services\Tournaments\TournamentService` |
| **Venues** | `backend/resources/views/venues/venues*.blade.php` | `App\Services\Venues\VenueService` |
| **Inventory** | `backend/resources/views/inventory/inventory*.blade.php` | `App\Services\Inventory\InventoryService` |
| **Staff & HR** | `backend/resources/views/hr/employees*.blade.php` | `App\Services\Employees\EmployeeService` |
| **Leave** | `backend/resources/views/leave/index.blade.php` | `App\Services\Leave\LeaveService` |
| **Payroll** | `backend/resources/views/payroll/index.blade.php` | `App\Services\Payroll\PayrollService` |
| **Reports** | `backend/resources/views/reports/index.blade.php` | `App\Services\Reports\ReportService` |
| **Settings** | `backend/resources/views/settings/organization.blade.php` | `App\Services\Organization\OrganizationSettingsService` |

---

## 3. Standard Module View Pattern

To guarantee that module pages render seamlessly within the shared layout without altering core files, all module view templates MUST follow the standard output-buffering layout inclusion pattern:

```php
<?php
// 1. Define page metadata and active navigation target
$pageTitle = 'Coaches — KhelSutra';
$activePage = 'coaches';
$orgId = current_organization_id();

// 2. Buffer the page content
ob_start();
?>

<!-- Module-specific HTML content -->
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Coaches</h1>
        <p class="ks-page-subtitle">Manage tactical coaching staff and sport specializations.</p>
    </div>
    <div class="ks-header-actions">
        <a href="/coaches/create" class="ks-btn ks-btn-primary">
            <i class="bi bi-plus-lg"></i> Add Coach
        </a>
    </div>
</div>

<div class="ks-table-card">
    <!-- Table content with safe HTML escaping -->
    <table class="ks-table">
        ...
    </table>
</div>

<?php
// 3. Capture buffered output into $slot and include shared layout shell
$slot = ob_get_clean();
include __DIR__ . '/../layouts/app.blade.php';
?>
```

### Critical Rules for Module Developers:
1. **Never include header/sidebar directly**: Do not call `include 'components.sidebar'` or `include 'components.header'` inside a view. The master layout (`layouts/app.blade.php`) handles the full shell automatically.
2. **Never hardcode organization IDs**: Always call `current_organization_id()` to get the authenticated user's organization context.
3. **Use Dedicated Pages for Forms**: Create and Edit forms must open dedicated pages (e.g., `/coaches/create`, `/teams/1/edit`), not modal popups.
4. **Strict Output Escaping**: Always escape all dynamic output using `<?= htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8') ?>`.
5. **Standard Service Return Contracts**: List methods must return predictable data sets (e.g., array of associative rows or `['data' => [...], 'total' => N]`).
