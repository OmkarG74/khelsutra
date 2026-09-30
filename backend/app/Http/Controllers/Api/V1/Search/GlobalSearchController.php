<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Services\BaseService;
use PDO;

class GlobalSearchController extends Controller
{
    /**
     * Platform Global Search for Super Admin & Authorized Users
     *
     * @param array $requestData
     * @param array $currentUser
     * @param bool $isSuperAdmin
     * @return array
     */
    public function search(array $requestData, array $currentUser = [], bool $isSuperAdmin = true): array
    {
        $query = trim($requestData['q'] ?? ($_GET['q'] ?? ''));
        if (mb_strlen($query) < 1) {
            return ApiResponse::success([
                'query' => $query,
                'total' => 0,
                'organizations' => [],
                'users' => [],
                'sections' => []
            ], 'Empty search query', 200);
        }

        $pdo = BaseService::getDatabaseConnection();
        $results = [
            'query' => $query,
            'organizations' => [],
            'users' => [],
            'sections' => []
        ];

        // 1. Search Platform Sections (always available, fast in-memory keyword matching)
        $sections = [
            [
                'title' => 'Dashboard',
                'subtitle' => 'Platform executive overview & statistics',
                'url' => '/dashboard',
                'icon' => 'bi-speedometer2',
                'keywords' => ['dashboard', 'home', 'overview', 'metrics', 'stats', 'analytics']
            ],
            [
                'title' => 'Organisations',
                'subtitle' => 'Manage sports academies & organisations directory',
                'url' => '/super-admin/organizations',
                'icon' => 'bi-building',
                'keywords' => ['organisations', 'organizations', 'academies', 'sports clubs', 'institutions', 'orgs']
            ],
            [
                'title' => 'Users',
                'subtitle' => 'Global user accounts, roles & directory',
                'url' => '/users',
                'icon' => 'bi-people',
                'keywords' => ['users', 'accounts', 'directory', 'people', 'staff', 'employees', 'profiles', 'admins']
            ],
            [
                'title' => 'Audit Logs',
                'subtitle' => 'Platform security & operational activity logs',
                'url' => '/audit',
                'icon' => 'bi-shield-check',
                'keywords' => ['audit', 'logs', 'history', 'security', 'activity', 'events', 'tracking']
            ],
            [
                'title' => 'Account Setting',
                'subtitle' => 'Personal account settings & security',
                'url' => '/settings',
                'icon' => 'bi-person-circle',
                'keywords' => ['profile', 'account', 'settings', 'password', 'preferences', 'my account']
            ],
            [
                'title' => 'Notifications',
                'subtitle' => 'Alerts, updates & activity notifications',
                'url' => '/notifications',
                'icon' => 'bi-bell',
                'keywords' => ['notifications', 'alerts', 'updates', 'messages', 'notifs']
            ],
            [
                'title' => 'Roles & Permissions',
                'subtitle' => 'RBAC access control & authorization',
                'url' => '/roles',
                'icon' => 'bi-shield-lock',
                'keywords' => ['roles', 'permissions', 'rbac', 'access', 'privileges', 'authorization']
            ]
        ];

        $lowerQuery = mb_strtolower($query);
        foreach ($sections as $s) {
            $matched = (mb_stripos($s['title'], $lowerQuery) !== false)
                    || (mb_stripos($s['subtitle'], $lowerQuery) !== false);
            if (!$matched) {
                foreach ($s['keywords'] as $kw) {
                    if (mb_stripos($kw, $lowerQuery) !== false || mb_stripos($lowerQuery, $kw) !== false) {
                        $matched = true;
                        break;
                    }
                }
            }
            if ($matched) {
                $results['sections'][] = [
                    'id' => $s['url'],
                    'title' => $s['title'],
                    'subtitle' => $s['subtitle'],
                    'url' => $s['url'],
                    'icon' => $s['icon']
                ];
            }
        }

        if ($pdo) {
            $like = '%' . $query . '%';

            // 2. Search Organisations
            $orgSql = "
                SELECT id, name, organization_code, legal_name, email, phone, status, plan_name
                FROM organizations
                WHERE deleted_at IS NULL
                  AND (
                      name LIKE :q1
                      OR organization_code LIKE :q2
                      OR legal_name LIKE :q3
                      OR email LIKE :q4
                      OR phone LIKE :q5
                  )
            ";
            $orgParams = [
                ':q1' => $like,
                ':q2' => $like,
                ':q3' => $like,
                ':q4' => $like,
                ':q5' => $like,
            ];

            if (!$isSuperAdmin) {
                $userOrgId = (int)($currentUser['organization']['id'] ?? ($currentUser['organization_id'] ?? 1));
                $orgSql .= " AND id = :user_org ";
                $orgParams[':user_org'] = $userOrgId;
            }

            $orgSql .= " ORDER BY name ASC LIMIT 6";

            try {
                $orgStmt = $pdo->prepare($orgSql);
                $orgStmt->execute($orgParams);
                $orgRows = $orgStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach ($orgRows as $row) {
                    $results['organizations'][] = [
                        'id' => (int)$row['id'],
                        'title' => $row['name'],
                        'subtitle' => ($row['organization_code'] ? $row['organization_code'] . ' · ' : '') . ($row['plan_name'] ?? 'Standard'),
                        'badge' => ucfirst($row['status'] ?? 'Active'),
                        'status' => $row['status'] ?? 'active',
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'code' => $row['organization_code'] ?? null,
                        'url' => '/super-admin/organizations/' . (int)$row['id'],
                        'icon' => 'bi-building'
                    ];
                }
            } catch (\Throwable $e) {}

            // 3. Search Users / Accounts
            $userSql = "
                SELECT u.id, u.first_name, u.last_name, u.username, u.email, u.phone, u.status,
                       r.name as role_name, o.name as org_name
                FROM users u
                LEFT JOIN organization_users ou ON u.id = ou.user_id
                LEFT JOIN roles r ON ou.role_id = r.id
                LEFT JOIN organizations o ON ou.organization_id = o.id
                WHERE u.deleted_at IS NULL
                  AND (
                      u.first_name LIKE :q1
                      OR u.last_name LIKE :q2
                      OR CONCAT(u.first_name, ' ', u.last_name) LIKE :q3
                      OR u.email LIKE :q4
                      OR u.username LIKE :q5
                      OR u.phone LIKE :q6
                      OR r.name LIKE :q7
                  )
            ";
            $userParams = [
                ':q1' => $like,
                ':q2' => $like,
                ':q3' => $like,
                ':q4' => $like,
                ':q5' => $like,
                ':q6' => $like,
                ':q7' => $like,
            ];

            if (!$isSuperAdmin) {
                $userOrgId = (int)($currentUser['organization']['id'] ?? ($currentUser['organization_id'] ?? 1));
                $userSql .= " AND ou.organization_id = :user_org ";
                $userParams[':user_org'] = $userOrgId;
            }

            $userSql .= " GROUP BY u.id ORDER BY u.id DESC LIMIT 6";

            try {
                $userStmt = $pdo->prepare($userSql);
                $userStmt->execute($userParams);
                $userRows = $userStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach ($userRows as $row) {
                    $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    if (empty($fullName)) {
                        $fullName = $row['username'] ?? 'User';
                    }
                    $roleDisplay = ((int)($row['role_id'] ?? 0) === 2 || ($row['role_name'] ?? '') === 'Sports Administrator')
                        ? 'Organisation Admin'
                        : ($row['role_name'] ?? 'Member');

                    $subtitle = $row['email'] ?? ($row['username'] ? '@' . $row['username'] : '');
                    if ($roleDisplay) {
                        $subtitle .= ' · ' . $roleDisplay;
                    }

                    $results['users'][] = [
                        'id' => (int)$row['id'],
                        'title' => $fullName,
                        'subtitle' => $subtitle,
                        'badge' => $roleDisplay,
                        'status' => $row['status'] ?? 'active',
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'username' => $row['username'] ?? null,
                        'url' => '/users/' . (int)$row['id'],
                        'icon' => 'bi-person'
                    ];
                }
            } catch (\Throwable $e) {}
        }

        $totalCount = count($results['organizations']) + count($results['users']) + count($results['sections']);
        $results['total'] = $totalCount;

        return ApiResponse::success($results, "Found {$totalCount} results", 200);
    }
}
